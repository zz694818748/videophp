<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2020/1/21
 * Time: 16:00
 */

namespace app\help;


use think\App;
use think\cache\Driver;

class Session extends Driver
{
    /**
     * 架构函数
     * @param App   $app
     * @param array $options 参数
     */
    public function __construct(App $app, array $options = [])
    {
        $options['RuntimePath'] = $app->getRuntimePath();
        $this->options = $options;
    }
    /**
     * 判断缓存
     * @access public
     * @param  string $name 缓存变量名
     * @return bool
     */
    function has($name){

    }

    /**
     * 读取缓存
     * @access public
     * @param  string $name 缓存变量名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    function get($name, $default=null){
        $list = explode(".",$name);
        $id = $list[0];
        $dir1 = ceil($id/10000);
        $filename = $this->options['RuntimePath'].$this->options['path']."\\".$dir1."\\".$id.'.txt';
        $data = [];
        $res = null;
        if(file_exists($filename)){
            $f = file_get_contents($filename);
            $data = json_decode(strstr($f,"\r\n"),true);
            $expire = strstr($f,"\r\n",true);
            if ( $expire!=0){
                $lasttime = filemtime($filename);
                $nowtime = time();
                if(($lasttime+$expire)<$nowtime){
                    $url = iconv('utf-8', 'gbk', $filename);
                    if (PATH_SEPARATOR == ':') { //linux
                        unlink($filename);
                    } else {  //Windows
                        unlink($url);
                    }
                    return $default;
                }
            }
        }
        $t = &$data;
        if(($count = count($list))>1){
            for ($i=1 ;$i<$count;$i++){
                $t = &$t[$list[$i]];
                if($i==$count-1){
                    $res = $t;
                }
            }
        }else{
            $res = $t;
        }
        return is_null($res) ? $default : $res;
    }


    /**
     * 写入缓存
     * @access public
     * @param  string            $name 缓存变量名
     * @param  mixed             $value  存储数据
     * @param  integer|\DateTime $expire  有效时间（秒）
     * @return bool
     */
    public function set($name, $value, $expire = null){
        $expire = $expire == null ? $this->options['expire'] : $expire;
        $list = explode(".",$name);
        $id = $list[0];
        $dir1 = ceil($id/10000);
        $filename = $this->options['RuntimePath'].$this->options['path']."\\".$dir1."\\".$id.'.txt';
        $old = [];
        if(!file_exists($filename)){
            $dir = dirname($filename);
            if(!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }else{
            $old = $this->get($list[0],[]);
        }
        $t = &$old;
        if(($count = count($list))>1){
            for ($i=1 ;$i<$count;$i++){
                $t = &$t[$list[$i]];
                if($i==$count-1){
                    $t = $value;
                }
            }
        }else{
            $t = $value;
        }
        $result = file_put_contents($filename,$expire."\r\n".json_encode($old));
        if ($result) {
            clearstatcache();
            return true;
        }
        return false;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param  string    $name 缓存变量名
     * @param  int       $step 步长
     * @return false|int
     */
    public function inc(string $name, int $step = 1){

    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param  string    $name 缓存变量名
     * @param  int       $step 步长
     * @return false|int
     */
    public function dec(string $name, int $step = 1){

    }

    /**
     * 删除缓存
     * @access public
     * @param  string $name 缓存变量名
     * @return bool
     */
    public function delete($name): bool{

    }

    /**
     * 清除缓存
     * @access public
     * @return bool
     */
    function clear(){

    }
    /**
     * 清除缓存
     * @access public
     * @return bool
     */
    function clearTag(array $keys){

    }
}