<?php
/**
 * Date: 2021/3/16
 * Time: 11:34
 */

namespace app\index\controller;


use app\api\controller\Member;
use app\BaseController;
use app\help\Login;
use think\facade\Cache;
use think\facade\Db;

class Mailres extends BaseController
{
    function register()
    {
        $cache = Cache::store('file');
        if( !$cache->has($this->param['mail']) || $cache->get($this->param['mail'])['token'] != $this->param['token']){
            $this->error('链接已过期','','',10000);
        }
        if($this->request->isGet()){
            return view('register',[
                'mail'=>$this->param['mail']
            ]);
        }
        return $this->dosave();

    }

    function index(){
        $cache = Cache::store('file');
        if( !$cache->has($this->param['mail']) || $cache->get($this->param['mail'])['code'] != $this->param['code']){
            $this->error('验证码已过期','','',0);
        }
        return $this->dosave($this->param['code']);
    }

    function dosave($code=''){
        $db = Db::name('login');
        $login = $db->where(['mail'=>$this->param['mail']])->limit(1)->select()->toArray();
        if($login){
            $this->error('该邮箱已注册');
        }

        $code = $code=='' ? substr(md5($this->request->server('REQUEST_TIME')),0,6) : $code;
        $inster = [
            'code' => $code,
            'pwd' => $this->param['pwd'],
            'mail' => $this->param['mail'],
            'createTime' => $this->request->server('REQUEST_TIME')
        ];
//        $id = $db->insertGetId($inster);
        $login = new Login();
        $id = $login->add($inster);
        Db::name('user')->insert(['id'=>$id]);
        $cache = Cache::store('file');
        $cache->delete($this->param['mail']);
        $this->success('注册成功');
    }

    function getcode(){
        $mem = new Member(app());
        return $mem->mailRegister();
    }
}