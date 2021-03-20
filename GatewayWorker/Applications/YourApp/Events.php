<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
global $roomcache;
global $mustparam;
global $ownerFunction;
$mustparam = ['type', 'roomid', 'fromid', 'toid', 'reqtime',
//  'client_id' //消息发起者连接
];
$ownerFunction = [
    'play','pause','seeked','synchro_seeked_group','synchro_playlist_rep'
];
global $closeTimer;
$closeTimer = [];//  退出操作计时器
class Events
{
    public static function onWorkerStart($businessWorker)
    {
        global $roomcache;
        global $db;
//        $db = new \GatewayWorker\Lib\DbConnection('127.0.0.1', '3306', 'root', 'root', 'synchronization');
//        $roomcache = dirname(__FILE__) . '/../../../runtime/index/roomcache/';
//        if (!is_dir($roomcache)) {
//            mkdir($roomcache, 0777, true);
//        }
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        var_dump($client_id);
        $message = json_encode([
            'type' => 'binduid',
            'client_id' => $client_id
        ]);
        Gateway::sendToClient($client_id, $message);
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        $data = [];
        try {
            $data = json_decode($message, true);
        } catch (Exception $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'code' => 10, 'msg' => '传入参数错误']));
        }
        if (is_array($data) && array_key_exists('type', $data)) {
            if ($data['type'] == 'ping') {
                Gateway::sendToClient($client_id, $message);
            } else {
                global $mustparam;
                $bool = true;
                $data['fromid'] = Gateway::getUidByClientId($client_id);
                foreach ($mustparam as $v) {
                    if (!array_key_exists($v, $data)) {
                        $bool = false;
                        break;
                    };
                }
                if ($bool) {
                    $clientlist = Gateway::getClientIdListByGroup($data['roomid']);
                    if (in_array($client_id, $clientlist)) {
                        $roominfo = getData($data['roomid']);
                        if ($roominfo === false || $roominfo['status'] == 0 || $roominfo['status'] == 3) {
                            $message = [
                                'type' => 'groupclose'
                            ];
                            Gateway::sendToClient($client_id, json_encode($message));
                        } else {
                            global $ownerFunction;
                            $fbool = in_array($data['type'],$ownerFunction);
                            if($fbool){
                                $ownbool =  $data['fromid'] == $roominfo['owner_id'];
                                if($ownbool){
                                    if(function_exists($data['type'])){
                                        $data['client_id'] = $client_id;
                                        call_user_func($data['type'], $data, $roominfo);
                                    }else{
                                        Gateway::sendToGroup($data['roomid'],json_encode($data));
                                    }
                                }
                            }else {
                                if(function_exists($data['type'])){
                                    $data['client_id'] = $client_id;
                                    call_user_func($data['type'], $data, $roominfo);
                                }else{
                                    Gateway::sendToGroup($data['roomid'],json_encode($data));
                                }
                            }
                        }
                    } else {
                        $message = [
                            'type' => 'nogroup'
                        ];
                        Gateway::sendToClient($client_id, json_encode($message));
                    }
                }
            }
        }
        // 向所有人发送
//        Gateway::sendToAll("$client_id said $message\r\n");
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        $session = $_SESSION;
        var_dump($session);
        if (is_array($session) && array_key_exists('roomid', $session)) {
            $roominfo = getData($session['roomid']);
            if ($roominfo['owner_id'] == $session['uid']) {
                global $closeTimer;
                $closeTimer[$roominfo['owner_id']] = Timer::add(10,function ()use($session){
                    global $closeTimer;
                    $uid = 0;
                    $clinets = Gateway::getClientIdListByGroup($session['roomid']);
                    $roominfo = getData($session['roomid']);
                    unset($closeTimer[$roominfo['owner_id']]);
                    foreach ($clinets as $v) {
                        $uid = Gateway::getUidByClientId($v);
                        if ($uid) {
                            break;
                        }
                    }
                    if($uid==0){
                        closeRoom($session);
                    }else{
                        changeOwner($session['roomid'],$session['uid'],$uid,$roominfo);
                    }
                },[$session],false);
            } else {
                $message = ['type' => 'menberExit', 'uid' => $session['uid']];
                Gateway::sendToGroup($session['roomid'], json_encode($message));
            }
        }
    }
}

/** 设置缓存
 * @param $data
 * @param $roomid
 */
function setData($data, $roomid)
{
    global $roomcache;
    $filename = $roomcache . $roomid . '.txt';
    $dir = dirname($filename);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $result = file_put_contents($filename, json_encode($data));
    if ($result) {
        return true;
    }
    return false;
}

/** 获取缓存
 * @param $roomid
 * @return bool|mixed
 */
function getData($roomid)
{
    global $roomcache;
    $filename = $roomcache . $roomid . '.txt';
    if (!file_exists($filename)) {
        return false;
    }
    $f = file_get_contents($filename);
    $old = json_decode($f, true);
    return $old;
}

/** 关闭房间 */
function closeRoom($session)
{
    $roomid = $session['roomid'];
    global $db;
    setData([
        'status'=>3,
        'playing'=>'',
        'people'=>1,
        'canpeople'=>2,
        'password'=>'',
        'cansex'=>0,
        'owner_id'=>0,
        'weight'=>0,
        'opentime'=>0
    ],$roomid);
    $row_count = $db->query("UPDATE `sz_room` SET `status` = 3, `people` = 1,`password` = '',`owner_id` = 0 WHERE id=$roomid");
}

/** 更换房主 */
function changeOwner($roomid,$oldOwner,$newOwner,$oldroom){
    if($newOwner!=0){
        $message = ['type' => 'ownerExit', 'uid' => $oldOwner];
        if ($newOwner != '0') {
            $message['newOwner'] = $newOwner;
        }
        $oldroom['owner_id'] = $newOwner;
        setData($oldroom,$roomid);
        global $db;
        $row_count = $db->query("UPDATE `sz_room` SET `owner_id` = $newOwner WHERE id=$roomid");
        Gateway::sendToUid($newOwner, json_encode($message));
    }
}

/** init加入房间初始化，init全面同步，synchro_playlist播放列表同步，synchro_seeked进度同步,synchro_seeked_group全员同步*/
function init($data, $roominfo){
    global $closeTimer;
    $uid =  $data['fromid'];
    if(array_key_exists($uid,$closeTimer)){
        Timer::del($closeTimer[$uid]);
        unset($closeTimer[$uid]);
    }
    Gateway::sendToUid($data['fromid'], json_encode(['type'=>'init']));
}

function init_req($data, $roominfo)
{
    Gateway::sendToUid($roominfo['owner_id'], json_encode($data));
}

function init_rep($data, $roominfo)
{
    Gateway::sendToUid($data['toid'], json_encode($data));
}

function synchro_playlist_req($data, $roominfo)
{
    Gateway::sendToUid($roominfo['owner_id'],json_encode($data));
}

function synchro_playlist_rep($data, $roominfo)
{
    Gateway::sendToUid($data['toid'],json_encode($data));
}

function synchro_seeked_req($data, $roominfo)
{
    Gateway::sendToUid($roominfo['owner_id'],json_encode($data));
}

function synchro_seeked_rep($data, $roominfo)
{
    Gateway::sendToUid($data['toid'],json_encode($data));
}

function synchro_group($data, $roominfo){

}

