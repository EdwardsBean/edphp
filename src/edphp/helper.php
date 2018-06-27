<?php

use edphp\Response;

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     * @param string|array  $name 参数名
     * @param mixed         $value 参数值不为空，则为设置值
     * @return mixed
     */
    function config($name = '', $value = null)
    {
        $config = edphp\Config::getInstance();
        if (is_null($value) && is_string($name)) {
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

if (!function_exists('json')) {
    /**
     * 获取\think\response\Json对象实例
     * @param mixed   $data 返回的数据
     * @param integer $code 状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return \think\response\Json
     */
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'json', $code, $header, $options);
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

            $args = $constructor ? $this->bindParams($constructor, $vars) : [];

            return $reflect->newInstanceArgs($args);

        } catch (ReflectionException $e) {
            throw new edphp\exception\ClassNotFoundException('class not exists: ' . $class, $class);
        }
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

        throw new ClassNotFoundException('class not exists:' . $class, $class);
    }
}

if (!function_exists('passClass')) {
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
