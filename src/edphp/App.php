<?php
namespace edphp;

class App 
{
    /**
     * 容器对象实例
     * @var App
     */
    protected static $instance;

    /**
     * 应用开始时间
     * @var float
     */
    protected $beginTime;

    /**
     * 应用内存初始占用
     * @var integer
     */
    protected $beginMem;

    protected $config;

    protected $middleware;

    protected $route;

    protected $request;

    protected $response;

    protected $dispatcher;

    /**
     * 执行应用程序
     * @access public
     * @return Response
     * @throws Exception
     */
    public function run()
    {
        $this->initialize();

        // 获取应用调度信息
        // $path = $this->request->path();

        try {
            // 执行调度
            $data = $this->dispatcher->run();
        } catch (HttpResponseException $exception) {
            $data = $exception->getResponse();
        }

        // 输出数据到客户端
        if ($data instanceof Response) {
            $response = $data;
        } elseif (!is_null($data)) {
            // 默认自动识别响应输出类型
            $isAjax = $this->request->isAjax();
            //默认json类型返回
            $type = 'json';

            $response = Response::create($data, $type);
        } else {
            $data = ob_get_clean();
            $status = empty($data) ? 204 : 200;
            $response = Response::create($data, '', $status);
        }
        return $response;

    }

    /**
     * 初始化应用
     * @access public
     * @return void
     */
    public function initialize()
    {
        $this->beginTime = microtime(true);
        $this->beginMem = memory_get_usage();

        static::setInstance($this);

        $this->init();

        $this->registerCoreComponent();

        // 加载惯例配置文件
        // $this->config->set(include $this->thinkPath . 'convention.php');
    }

    public function init()
    {
        define('FRAMEWORK_ROOT', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
    }

    /**
     * 设置当前容器的实例
     * @access public
     * @param  object        $instance
     * @return void
     */
    public static function setInstance($instance)
    {
        static::$instance = $instance;
    }

    public function registerCoreComponent()
    {
        //TODO 改造成容器自动发现
        $this->config = new Config;
        $this->middleware = new Middleware;
        $this->request = new Request;
        $this->response = new Response;
        $this->dispatcher = new Dispatcher($this->request);
    }

}
