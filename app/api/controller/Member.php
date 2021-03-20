<?php
/**
 * Date: 2021/1/28
 * Time: 10:09
 */

namespace app\api\controller;


use app\BaseController;
use think\facade\Cache;
use think\facade\Db;
use Tool\Mail;

class Member extends BaseController
{
    function index(){
//        Cache::store('session')->get('1001');
        echo 'api/member/index';
    }


    function login(){

    }

    function mailRegister(){
        $db = Db::name('login');
        $login = $db->where(['mail'=>$this->param['mail']])->limit(1)->select()->toArray();
        if($login){
            $this->error('该邮箱已注册');
        }

        $mail = new Mail();
        $code = substr(md5($this->request->server('REQUEST_TIME')),0,6);
        $uuid = gen_uuid();
        $url = $this->app->config->get('app.app_host') . url('coderegister',['token'=>urlencode($uuid)]) . '?mail='.urlencode($this->param['mail']);

        $F = Cache::store('file');
        if($F->has($this->param['mail'])){
            $this->apierror('已向该邮箱发送邮件,请查看邮箱和垃圾箱,如需重发请30分钟后重试');
        }

        $cache = [
            'code' => $code,
            'token' => $uuid
        ];
        $F->set($this->param['mail'],$cache,1800);
        $body = '你正在注册爱播星球会员，你的验证码为：<span style="color: #4288ce">'
            . $code .
            '</span><br>你也可以点击连接完成注册，<a href="'
            . $url . '" target="_blank">'
            . $url . '</a><br>连接和验证码有效期30分钟，非本人操作请忽略本邮件。';
        $result = $mail->send('694818748@qq.com','注册通知',$body);
        if ($result){
            $this->apisuccess();
        }
        $this->apierror('邮件发送失败');
    }
}

if(!function_exists('gen_uuid')) {
    function gen_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}