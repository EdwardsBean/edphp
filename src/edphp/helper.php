<?php

use edphp\Db;
use edphp\exception\HttpException;
use edphp\exception\HttpResponseException;
use edphp\Log;
use edphp\Request;
use edphp\Response;
use edphp\response\Msg;
use edphp\Session;

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
    function controller($name, $layer = 'controller', $empty = '')
    {
        $class = parseClass($layer, $name);
        $system_class = parseClass($layer, $name, "edphp") . "Controller";

        if (class_exists($class)) {
            return invokeClass($class);
        } elseif (class_exists($system_class)) {
            return invokeClass($system_class);
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
     * @param  string $name   namespace
     * @return string
     */
    function parseClass($layer, $name, $namespace = 'app')
    {
        $name = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);

        return $namespace . '\\' . (!empty($module) ? $module . '\\' : '') . $layer . '\\' . $name;
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
        $type = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();

        foreach ($params as $param) {
            $name = $param->getName();
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

if (!function_exists('write')) {
    /**
     * 记录日志信息
     * @access public
     * @param  mixed  $msg       日志信息
     * @param  string $type      日志级别
     * @param  array  $context   替换内容
     * @return $this
     */
    function write($msg, $type = 'info', array $context = [])
    {
        $log = Log::getInstance();
        $log->record($msg, $type);
    }
}

if (!function_exists('debug')) {
    function debug($msg, $type = 'info', array $context = [])
    {
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

if (!function_exists('array_remove')) {

    /**
     * 删除数组中的一个元素，并返回值
     */
    function array_remove(array &$arr, $key)
    {
        if (array_key_exists($key, $arr)) {
            $val = $arr[$key];
            unset($arr[$key]);
            return $val;
        }
        return null;
    }
}

if (!function_exists('array_select')) {

    /**
     * 适用于 [ 0=>[], 1=>[]]的复合数组，选择value中的keys
     */
    function array_select(array &$arr, $value_keys)
    {
        return array_map($arr, function ($row) use ($value_keys) {
            $v = [];
            foreach ($value_keys as $key) {
                $v[] = $row[$key];
            }
            return $v;
        });
    }
}

if (!function_exists('array_mmerge')) {

    /**
     * 适用于 [ 0=>[], 1=>[]]的复合数组，选择使用keys作为主键合并
     */
    function array_mmerge(array $a, array $b, array $keys)
    {
        foreach ($a as $a_line) {
            foreach ($b as $b_line) {
                $match = true;
                foreach ($keys as $key) {
                    if ($a_line[$key] !== $b_line[$key]) {
                        $match = false;
                    }
                }
                if ($match) {
                    $a_line = $b_line;
                }
            }
            $result[] = $a_line;
        }
        return isset($result) ? $result : [];
    }
}

if (!function_exists('array_msort')) {

    /**
     * 适用于 [ 0=>[], 1=>[]]的复合数组，选择使用key的值做排序。
     */
    function array_msort(array $arr = [], $key, $desc = true)
    {
        if ($desc) {
            usort($arr, function ($a, $b) {
                return $a['val'] < $b['val'];
            });
        } else {
            usort($arr, function ($a, $b) {
                return $a['val'] > $b['val'];
            });
        }
        return $arr;
    }
}

if (!function_exists('csv_select')) {

    /**
     * 提取csv文件中的特定列
     */
    function csv_select($filepath, $select_columns)
    {
        //字段位置
        $columnIndex = [];

        $file = fopen($filepath, "r");
        //第一行为字段名
        $first = fgetcsv($file);
        //字段位置计算
        foreach ($first as $i => $v) {
            // 获得列名称的格式
            $encode = mb_detect_encoding($first[$i], 'UTF-8, GB2312, GBK, UTF-7, UTF-16,ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
            // 判断列名称的格式是否正确
            if ($encode != "UTF-8") {
                // 列名格式错误，将其转换成 utf-8 格式
                $v = trim(iconv($encode, "UTF-8", $v));
            }

            $name = array_search($v, $select_columns);
            if (!empty($name)) {
                $columnIndex[$name] = $i;
            }
        }

        $result = [];
        while ($row = fgetcsv($file)) { //每次读取CSV里面的一行内容
            foreach ($columnIndex as $field => $fieldIndex) {
                if (empty($row)) continue;
                $encode = mb_detect_encoding($row[$fieldIndex], 'UTF-8, GB2312, GBK, UTF-7, UTF-16, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
                // 判断格式，不正确进行转换
                $v = $row[$fieldIndex];
                if ($encode != "UTF-8") {
                    //GBK神奇效果，不知道为什么GB2312会有部分乱码
                    $v = trim(mb_convert_encoding($v, "UTF-8", "GBK"));
                }
                if (strpos($v, "=") === 0) {
                    //处理 csv的函数 ="188476566409178005"
                    $v = substr($v, 2, -1);
                } elseif (strpos($v, "'") === 0) {
                    $v = substr($v, 1);
                }
                if ($v == "") {
                    $v = null;
                }
                $express[$field] = $v;
            }
            $result[] = $express;
        }
        fclose($file);
        return $result;
    }
}

/**
 * @param string $id
 * @param array  $config
 * @return \edphp\Response
 */
function captcha($id = '', $config = [])
{
    $captcha = new \edphp\Captcha($config);
    return $captcha->entry($id);
}

/**
 * @param $id
 * @return string
 */
function captcha_src($id = '')
{
    return Url::build('/captcha' . ($id ? "/{$id}" : ''));
}

/**
 * @param $id
 * @return mixed
 */
function captcha_img($id = '')
{
    return '<img src="' . captcha_src($id) . '" alt="captcha" />';
}

/**
 * @param        $value
 * @param string $id
 * @param array  $config
 * @return bool
 */
function captcha_check($value, $id = '')
{
    $captcha = new \edphp\Captcha((array) config('captcha.'));
    return $captcha->check($value, $id);
}

if (!function_exists('response')) {
    /**
     * 创建普通 Response 对象实例
     * @param mixed      $data   输出数据
     * @param int|string $code   状态码
     * @param array      $header 头信息
     * @param string     $type
     * @return Response
     */
    function response($data = [], $code = 200, $header = [], $type = 'html')
    {
        return Response::create($data, $type, $code, $header);
    }
}

function success($msg = '')
{
    return Msg::success([], $msg);
}

function fail($msg = '', $code = 50000)
{
    return Msg::fail($msg, $code);
}

function http_get($url)
{
    record("url:$url");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);           //设置超时   
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36");   //用户访问代理 User-Agent   
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);      //跟踪301   
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果   
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        //返回结果   
    $r = curl_exec($ch);
    $errorno = curl_errno($ch);
    curl_close($ch);
    if ($errorno) {
        record("http get error with code: $errorno");
        return false;
    }
    return $r;
}

function http_get_json($url)
{
    $r = http_get($url);
    if ($r !== false) {
        $res = json_decode($r, true);
        return $res;
    }
    return $r;
}