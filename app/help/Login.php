<?php


namespace app\help;


use think\Model;

class Login extends Model
{
    private $error = '';
    function __construct(array $data = [],string $name='login')
    {
        parent::__construct($data);
        $this->name = $name;
    }

    function add($data){
        $data['pwd'] = sha1(md5($data['pwd']).$data['code']);
        return $this->insertGetId($data);
    }

    function login($param){
        $where = [];
        if($this->name=='login'){
            $key = filter_var($param['user'], FILTER_VALIDATE_EMAIL) ? 'mail' : 'phone';
        }else{
            $key = 'user';
        }
        $where[$key] = $param['user'];
        $info = $this->where($where)->findOrEmpty();
        if(empty($info)){
            $this->error = '用户不存在';
            return false;
        }
        $sha1 = sha1(md5($param['pwd']).$info['code']);
        if($sha1 != $info['pwd']){
            $this->error = '密码错误';
            return false;
        }
        return $info;
    }

    function geterror (){
        return $this->error;
    }
}