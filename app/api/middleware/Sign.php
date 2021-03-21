<?php
/**
 * Date: 2021/3/10
 * Time: 10:56
 */

namespace app\api\middleware;


use think\facade\Cache;
use think\facade\Log;

class Sign
{
    public function handle($request, \Closure $next, $param=null)
    {

        $res = [
            'code' => 1001,
            'msg' => '签名错误',
            'time' => 2
        ];
        if(!$request->isJson()){
            $res['msg'] = '请求方式错误';
            return json($res);
        }
        if ($param == 1) {
            if ($this->checkId($request->param('opid')) === true) return $next($request);
            return json($res);
        }

        if (($res['msg'] = $this->check($request)) === true) return $next($request);

        return json($res);

    }

    function check($request)
    {
        $input = $request->param();
        if (!$this->checkParam($input)) return '参数错误';

        if (!$this->checkTime($input,$request->server('REQUEST_TIME'))) return '时间差过大';

        if (!$this->checkSign($input)) return '签名不匹配';

        return true;
    }

    function checkParam($arr)
    {
        $checkParam = ['timestamp', 'opid', 'sign'];
        foreach ($checkParam as $v) {
            if (!array_key_exists($v, $arr) || !$arr[$v]) return false;
        }

        return strlen($arr['sign']) == 32;
    }

    function checkTime($input,$req_time)
    {
        $time = $input['timestamp'];
        $red = $req_time - $input['timestamp'];
        return $time ? ($red < 100 && $red >= 0) : false;
    }

    function checkSign($param)
    {
        $reqsign = $param['sign'];
        unset($param['sign']);
        ksort($param);
        $str = urldecode(http_build_query($param));
        if ($param['opid'] >= 1000) {
            $token = Cache::store('session')->get($param['opid'] . '.token');
        } else {
            $token = md5($param['timestamp']);
        }
        Log::write($str . '&' . $token);
        $sign = strtoupper(md5($str . '&' . $token));
        return $sign == $reqsign;
    }

    function checkId($opid)
    {
        return is_numeric($opid) ? (int)$opid >= 1000 : false;
    }
}