<?php
namespace edphp;

use edphp\route\Middleware;
use edphp\route\Dispatcher;

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
        $this->rootPath = $rootPath . DIRECTORY_SEPARATOR;
        $this->appPath = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
        $this->configPath = $this->rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $this->frameworkPath = __DIR__ . DIRECTORY_SEPARATOR;

        // 注册错误和异常处理机制
        Error::register();
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

        $response = $this->dispatcher->run();
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

        // 注册异常处理类
        if (config('app.exception_handle')) {
            Error::setExceptionHandler(config('app.exception_handle'));
        }
        

    }

    public function init($module = '')
    {
        $path = $this->appPath;

        //加载公共文件
        if (is_file($path . 'common.php')) {
            include $path . 'common.php';
        }

        //加载中间件
        if (is_dir($path . 'interceptor')) {
            $dir = $path . 'interceptor';
            $files = scandir($dir);
            foreach ($files as $file) {
                if ('.' . pathinfo($file, PATHINFO_EXTENSION) === '.php') {
                    $this->middleware->import(pathinfo($file, PATHINFO_FILENAME));
                }
            }
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

        // 加载环境配置
        $profile = getenv('EDPHP_PROFILE');
        if ($profile) {
            $profilePath = $this->rootPath . 'env.' . $profile;
            if (file_exists($profilePath)) {
                $env = parse_ini_file($profilePath, true, INI_SCANNER_TYPED);
                if(is_array($env)) {
                    foreach ($env as $item => $value)
                    $this->config->set($value, $item);
                }
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
        $this->dispatcher = new Dispatcher($this->request, $this->middleware);
    }

}
