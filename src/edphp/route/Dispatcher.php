<?php

namespace edphp\route;

use ReflectionMethod;
use edphp\exception\HttpException;
use edphp\exception\ClassNotFoundException;
use edphp\Request;
use edphp\Response;

class Dispatcher
{

    protected $request;
    protected $controller;
    protected $action;

    //TODO 支持多模块
    protected $module;

    protected $path;

    protected $var;
    
    protected $middleware;

    public function __construct(Request $request, Middleware $middleware)
    {
        $this->request = $request;
        $this->middleware = $middleware;
    }

    public function run()
    {
        //前置拦截器
        $preData = $this->middleware->preHandle();
        if (!is_null($preData)) {
            return $this->autoResponse($preData);
        }

        //执行控制器
        $data = $this->exec();

        //后置拦截器
        $afterData = $this->middleware->afterCompletion();
        if (!is_null($afterData)) {
            return $this->autoResponse($afterData);
        }
        return $this->autoResponse($data);
    }

    protected function autoResponse($data)
    {
        if ($data instanceof Response) {
            $response = $data;
        } elseif (!is_null($data)) {
            // 默认自动识别响应输出类型
            $isAjax = $this->request->isAjax();
            $type   = config('default_return_type');

            $response = Response::create($data, $type);
        } else {
            $data     = ob_get_clean();
            $data     = false === $data ? '' : $data;
            $status   = '' === $data ? 204 : 200;
            $response = Response::create($data, '', $status);
        }

        return $response;
    }

    public function exec()
    {
        if($this->request->isOptions()) {
            return;
        }
        // 解析默认的URL规则
        $path = $this->request->pathinfo();
        $controller = !empty($path) ? array_shift($path) : null;
        $action = !empty($path) ? array_shift($path) : null;
        if (empty($controller) || empty($action)) {
            throw new HttpException(404, 'controller or action null');
        }

        $controller = $this->parseController($controller);
        $action = $this->parseAction($action);
        $methodAction = $this->parseMethodAction($action);

        try {
            $instance = controller($this->parseName($controller, 1));
        } catch (ClassNotFoundException $e) {
            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }

        if (is_callable([$instance, $action])) {
            // 严格获取当前操作方法名
            $reflect = new ReflectionMethod($instance, $action);
        $call = [$instance, $action];
        } else if(is_callable([$instance, $methodAction])) {
            $reflect = new ReflectionMethod($instance, $methodAction);
        } else {
            // 操作不存在
            throw new HttpException(404, 'method not exists:' . get_class($instance) . '->' . $action . '()');
        }
        $methodName = $reflect->getName();
        $vars = $this->request->param();
        return $this->invokeReflectMethod($instance, $reflect, $vars);
    }

    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @access public
     * @param  string  $name 字符串
     * @param  integer $type 转换类型
     * @param  bool    $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

    public function parseController($controller)
    {
        return $this->parseName($controller, 1);
    }

    public function parseAction($action)
    {
        $action = $this->parseName($action, 1);
        return $action;
    }

    public function parseMethodAction($action)
    {
        $action = $this->parseName($action, 1);
        $method = strtolower($this->request->method());
        return $method . $action;
    }
        /**
     * 调用反射执行类的方法 支持参数绑定
     * @access public
     * @param  object  $instance 对象实例
     * @param  mixed   $reflect 反射类
     * @param  array   $vars   参数
     * @return mixed
     */
    public function invokeReflectMethod($instance, $reflect, $vars = [])
    {
        $args = $this->bindParams($reflect, $vars);

        return $reflect->invokeArgs($instance, $args);
    }

        /**
     * 绑定参数
     * @access protected
     * @param  \ReflectionMethod|\ReflectionFunction $reflect 反射类
     * @param  array                                 $vars    参数
     * @return array
     */
    protected function bindParams($reflect, $vars = [])
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type   = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();

        foreach ($params as $param) {
            $name  = $param->getName();
            $class = $param->getClass();

            if ($class) {
                //TODO 添加对象注入
                // $args[] = $this->getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && isset($vars[$name])) {
                $args[] = $vars[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss:' . $name);
            }
        }

        return $args;
    }
}
