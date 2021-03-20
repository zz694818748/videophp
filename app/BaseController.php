<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\HttpResponseException;
use think\Response;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    //静态模板生成目录
    protected $staticHtmlDir = "";
    //静态文件
    protected $staticHtmlFile = "";

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $this->param = $this->app->request->param();

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}


    /**
     * 判断是否存在静态
     *
     * @param string $key 静态文件名称，传入，方便出问题时候查看
     * @param string $index 静态文件存放一级文件夹
     */
    public function beforeBuild($key = "",$index = "index") {
        //生成静态
        $this->staticHtmlDir = "html".DS.$index.DS;
        //静态文件存放地址
        $this->staticHtmlFile = $this->staticHtmlDir . $key .'.html';

        //目录不存在，则创建
        if(!file_exists($this->staticHtmlDir)){
            mkdir($this->staticHtmlDir, 0777, true);
            chmod($this->staticHtmlDir, 0777);
        }

        //静态文件存在,并且没有过期
        if(file_exists($this->staticHtmlFile) && filectime($this->staticHtmlFile)>=time()-60*60*24*5) {
            header("Location:/" . $this->staticHtmlFile);
            exit();
        }

    }

    /**
     * 开始生成静态文件
     *
     * @param $html
     */
    public function afterBuild($html) {
        if(!empty($this->staticHtmlFile) && !empty($html)) {
            if(file_exists($this->staticHtmlFile)) {
                \unlink($this->staticHtmlFile);
            }
            if(file_put_contents($this->staticHtmlFile,$html)) {
                header("Location:/" . $this->staticHtmlFile);
                exit();
            }
        }
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }
    protected function success($msg = '', string $url = null, $data = '', int $wait = 3, array $header = []){
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : $this->app->route->buildUrl($url);
        }
        $result = [
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        if ($this->request->isJson() || $this->request->isAjax()) {
            $response = json($result);
        }else{
            $response = view('/jump',$result);
        }
        throw new HttpResponseException($response);
    }

    protected function error($msg = '', string $url = null, $data = '', int $wait = 3, array $header = []){
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : $this->app->route->buildUrl($url);
        }
        $result = [
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        if ($this->request->isJson() || $this->request->isAjax()) {
            $response = json($result);
        }else{
            $response = view('/jump',$result);
        }
        throw new HttpResponseException($response);
    }

    protected function apisuccess($data=[],$msg='ok'){
        $res = [
            'code'=>1,
            'data'=>$data,
            'msg'=>$msg,
        ];
        throw new HttpResponseException(json($res));
    }

    protected function apierror($msg='',$code=0){
        $res = [
            'code'=>$code,
            'msg'=>$msg,
        ];
        throw new HttpResponseException(json($res));
    }
}
