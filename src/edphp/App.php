<?php
namespace edphp;

use edphp\route\Dispatcher;
use edphp\route\Middleware;

class App
{
    const VERSION = '1.0.0';

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
     * 运行时目录
     * @var string
     */
    protected $runtimePath;

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
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;

        // 当前文件名
        if (!defined('_PHP_FILE_')) {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
        }

        //网站根目录，上传文件时，获取根目录，前后端分离时方便定位
        if (!defined('__ROOT__')) {
            $_root = rtrim(dirname(_PHP_FILE_), '/');
            define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
        }
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

        // 初始化用户配置
        $this->init();

        // 设置系统时区
        date_default_timezone_set(config('app.default_timezone'));

        // 注册异常处理类
        if (config('app.exception_handle')) {
            Error::setExceptionHandler(config('app.exception_handle'));
        }

        Db::init($this->config->pull('database'));

    }

    public function init($module = '')
    {
        $path = $this->appPath;

        //加载公共文件
        if (is_file($path . 'common.php')) {
            include $path . 'common.php';
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
        $profilePath = $this->rootPath . 'env.ini';
        if (file_exists($profilePath)) {
            $env = parse_ini_file($profilePath, true, INI_SCANNER_TYPED);
            if (is_array($env)) {
                foreach ($env as $item => $value) {
                    $this->config->set($value, $item);
                }

            }
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
        $this->request = Request::getInstance();
        $this->dispatcher = new Dispatcher($this->request, $this->middleware);
    }

    /**
     * 获取框架版本
     * @access public
     * @return string
     */
    public static function version()
    {
        return static::VERSION;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * 获取应用运行时目录
     * @access public
     * @return string
     */
    public function getRuntimePath()
    {
        return $this->runtimePath;
    }

    /**
     * 获取应用开启时间
     * @access public
     * @return float
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * 获取应用初始内存占用
     * @access public
     * @return integer
     */
    public function getBeginMem()
    {
        return $this->beginMem;
    }
}
