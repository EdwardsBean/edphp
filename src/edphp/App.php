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

    protected $initialized;

    /**
     * 应用类库命名空间
     * @var string
     */
    protected $namespace = 'app';

    /**
     * 应用类库目录
     * @var string
     */
    protected $appPath;

    /**
     * 框架目录
     * @var string
     */
    protected $frameworkPath;

    /**
     * 应用根目录
     * @var string
     */
    protected $rootPath;

    /**
     * 配置目录
     * @var string
     */
    protected $configPath;

    public function __construct($rootPath = '')
    {
        $this->rootPath = $rootPath;
        $this->appPath = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
        $this->configPath = $this->rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $this->frameworkPath = __DIR__ . DIRECTORY_SEPARATOR;
    }
    /**
     * 执行应用程序
     * @access public
     * @return Response
     * @throws Exception
     */
    public function run()
    {
        $this->initialize();

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
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $this->beginTime = microtime(true);
        $this->beginMem = memory_get_usage();

        static::setInstance($this);

        $this->registerCoreComponent();

        // 加载惯例配置文件
        $this->config->set(include $this->frameworkPath . 'convention.php');

        //初始化用户配置
        $this->init();

    }

    public function init($module = '')
    {
        $path = $this->appPath;

        //加载公共文件
        if (is_file($path . 'common.php')) {
            include $path . 'common.php';
        }

        //加载中间件
        if (is_file($path . 'middleware.php')) {
            include $path . 'middleware.php';
        }

        // 自动读取配置文件
        if (is_dir($path . 'config')) {
            $dir = $path . 'config';
        } elseif (is_dir($this->configPath . $module)) {
            $dir = $this->configPath . $module;
        }

        $files = isset($dir) ? scandir($dir) : [];

        foreach ($files as $file) {
            if ('.' . pathinfo($file, PATHINFO_EXTENSION) === '.php') {
                $filename = $dir . DIRECTORY_SEPARATOR . $file;
                $this->config->load($filename, pathinfo($file, PATHINFO_FILENAME));
            }
        }
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
        $config = Config::getInstance();
        $this->config = $config;
        $this->middleware = new Middleware;
        $this->request = new Request;
        $this->response = new Response;
        $this->dispatcher = new Dispatcher($this->request);
    }

}
