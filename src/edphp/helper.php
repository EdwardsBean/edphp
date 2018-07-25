<?php

use edphp\Db;
use edphp\Log;
use edphp\Request;
use edphp\Session;
use edphp\Response;
use edphp\exception\HttpException;
use edphp\exception\HttpResponseException;


if (!function_exists('abort')) {
    /**
     * 抛出HTTP异常
     * @param integer|Response      $code 状态码 或者 Response对象实例
     * @param string                $message 错误信息
     * @param array                 $header 参数
     */
    function abort($code, $message = null, $header = [])
    {
        if ($code instanceof Response) {
            throw new HttpResponseException($code);
        } else {
            throw new HttpException($code, $message, null, $header);
        }
    }
}

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     * @param string|array  $name 参数名.可以为app.,app.debug,debug(默认app.debug)
     * @param mixed         $value 参数值不为空，则为设置值
     * @return mixed
     */
    function config($name = '', $value = null)
    {
        $config = edphp\Config::getInstance();
        if (is_null($value) && is_string($name)) {
            //带点号，则获取该配置项,如trace.
            if ('.' == substr($name, -1)) {
                return $config->pull(substr($name, 0, -1));
            }

            return 0 === strpos($name, '?') ? $config->has(substr($name, 1)) : $config->get($name);
        } else {
            return $config->set($name, $value);
        }
    }
}

if (!function_exists('isDebug')) {
    /**
     * 是否调试模式
     */
    function isDebug()
    {
       return config('app.app_debug');
    }
}

if (!function_exists('db')) {
    /**
     * 实例化数据库类
     * @param string        $name 操作的数据表名称（不含前缀）
     * @param array|string  $config 数据库配置参数
     * @param bool          $force 是否强制使用新的链接 
     * @return \edphp\db\Query
     */
    function db($name = '', $config = [], $force = false)
    {
        return Db::connect($config, $force)->name($name);
    }
}

if (!function_exists('json')) {
    /**
     * 获取\edphp\response\Json对象实例
     * @param mixed   $data 返回的数据
     * @param integer $code 状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return \edphp\response\Json
     */
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'json', $code, $header, $options);
    }
}

if (!function_exists('controller')) {

    /**
     * 实例化（分层）控制器 格式：[模块名/]控制器名
     * @access public
     * @param  string $name              资源地址
     * @param  string $layer             控制层名称
     * @param  bool   $appendSuffix      是否添加类名后缀
     * @param  string $empty             空控制器名称
     * @return object
     * @throws ClassNotFoundException
     */
    function controller($name, $layer = 'controller', $appendSuffix = false, $empty = '')
    {
        $class = parseClass($layer, $name);

        if (class_exists($class)) {
            return invokeClass($class);
        }

        throw new HttpException(405, 'route not found');
    }
}

if (!function_exists('invokeClass')) {
    /**
     * 调用反射执行类的实例化
     * @access public
     * @param  string    $class 类名
     * @param  array     $vars  参数
     * @return mixed
     */
    function invokeClass($class, $vars = [])
    {
        try {
            $reflect = new ReflectionClass($class);

            $constructor = $reflect->getConstructor();

            $args = $constructor ? bindParams($constructor, $vars) : [];

            return $reflect->newInstanceArgs($args);

        } catch (ReflectionException $e) {
            throw new edphp\exception\ClassNotFoundException('class not exists: ' . $class, $class);
        }
    }
}

if (!function_exists('parseClass')) {
    /**
     * 解析应用类的类名
     * @access public
     * @param  string $layer  层名 controller model ...
     * @param  string $name   类名
     * @return string
     */
    function parseClass($layer, $name)
    {
        $name = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);

        return 'app\\' . (!empty($module) ? $module . '\\' : '') . $layer . '\\' . $name;
    }
}

if (!function_exists('invokeFunction')) {
    /**
     * 执行函数或者闭包方法 支持参数调用
     * @access public
     * @param  mixed  $function 函数或者闭包
     * @param  array  $vars     参数
     * @return mixed
     */
    function invokeFunction($function, $vars = [])
    {
        try {
            $reflect = new ReflectionFunction($function);

            $args = bindParams($reflect, $vars);

            return call_user_func_array($function, $args);
        } catch (ReflectionException $e) {
            throw new Exception('function not exists: ' . $function . '()');
        }
    }

}

if (!function_exists('bindParams')) {
        /**
     * 绑定参数
     * @access protected
     * @param  \ReflectionMethod|\ReflectionFunction $reflect 反射类
     * @param  array                                 $vars    参数
     * @return array
     */
    function bindParams($reflect, $vars = [])
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
                $args[] = getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && isset($vars[$name])) {
                $args[] = $vars[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException('method param miss:' . $name);
            }
        }

        return $args;
    }

    function getObjectParam($className, &$vars)
    {
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = invokeClass($className);
        }

        return $result;
    }

}

if (!function_exists('invoke')) {

    /**
     * 调用反射执行callable 支持参数绑定
     * @access public
     * @param  mixed $callable
     * @param  array $vars   参数
     * @return mixed
     */
    function invoke($callable, $vars = [])
    {
        if ($callable instanceof Closure) {
            return invokeFunction($callable, $vars);
        }

        return invokeMethod($callable, $vars);
    }
}

if (!function_exists('parseName')) {
        /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @access public
     * @param  string  $name 字符串
     * @param  integer $type 转换类型
     * @param  bool    $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

}

if (!function_exists('record')) {
        /**
     * 记录日志信息
     * @access public
     * @param  mixed  $msg       日志信息
     * @param  string $type      日志级别
     * @param  array  $context   替换内容
     * @return $this
     */
    function record($msg, $type = 'info', array $context = [])
    {
        $log = Log::getInstance();
        if (!$log->allowWrite) {
            return;
        }

        if (is_string($msg)) {
            $replace = [];
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }

            $msg = strtr($msg, $replace);
        }

        $log->log[$type][] = $msg;
    }
}

if (!function_exists('debug')) {
    function debug($msg, $type = 'info', array $context = []) {
        if (isDebug()) {
            record($msg, $type, $context);
        }
    }
}

if (!function_exists('token')) {
    /**
     * 生成表单令牌
     * @param string $name 令牌名称
     * @param mixed  $type 令牌生成方法
     * @return string
     */
    function token($name = '__token__', $type = 'md5')
    {
        $token = Request::token($name, $type);

        return '<input type="hidden" name="' . $name . '" value="' . $token . '" />';
    }
}

if (!function_exists('request')) {
    /**
     * 获取当前Request对象实例
     * @return Request
     */
    function request()
    {
        return Request::getInstance();
    }
}

if (!function_exists('session')) {
    /**
     * Session管理
     * @param string|array  $name session名称，如果为数组表示进行session设置
     * @param mixed         $value session值
     * @param string        $prefix 前缀
     * @return mixed
     */
    function session($name, $value = '', $prefix = null)
    {
        $session = Session::getInstance();
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $session->set($key, $value, $prefix);
            }
        } elseif (is_null($name)) {
            // 清除
            $session->clear($value);
        } elseif ('' === $value) {
            // 判断或获取
            return 0 === strpos($name, '?') ? $session->has(substr($name, 1), $prefix) : $session->get($name, $prefix);
        } elseif (is_null($value)) {
            // 删除
            return $session->delete($name, $prefix);
        } else {
            // 设置
            return $session->set($name, $value, $prefix);
        }
    }
}

if (!function_exists('user_id')) {
    /**
     * Session中取用户id
     */
    function user_id()
    {
        return session('user_id');
    }
}

if (!function_exists('user_role')) {
    /**
     * Session中取用户角色
     */
    function user_role()
    {
        return session('user_role');
    }
}

if (!function_exists('post')) {
    /**
     * 自动获取post内容，解析json or xml
     * 设置获取POST参数
     * @param  mixed         $name 变量名
     * @param  mixed         $default 默认值
     * @param  string|array  $filter 过滤方法
     * @return mixed
     * 
     */
    function post($name = '', $default = null, $filter = '')
    {
        $request = Request::getInstance();
        return $request->post($name, $default, $filter);
    }
}

if (!function_exists('I')) {
    /**
     * 自动获取请求参数，不管是get，还是post
     * @param  mixed         $name 变量名
     * @return mixed
     * 
     */
    function I($name = '')
    {
        $request = Request::getInstance();
        return $request->param($name);
    }
}
