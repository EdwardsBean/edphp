<?php

namespace edphp;

use edphp\db\Connection;

/**
 * Class Db
 */
class Db
{
    /**
     * 当前数据库连接对象
     * @var Connection
     */
    protected static $connection;

    /**
     * 数据库配置
     * @var array
     */
    protected static $config = [];

    /**
     * 查询次数
     * @var integer
     */
    public static $queryTimes = 0;

    /**
     * 执行次数
     * @var integer
     */
    public static $executeTimes = 0;

    /**
     * 配置
     * @access public
     * @param  mixed $config
     * @return void
     */
    public static function init($config = [])
    {
        self::$config = $config;

        if (empty($config['query'])) {
            self::$config['query'] = '\\edphp\\db\\Query';
        }
    }

    /**
     * 获取数据库配置
     * @access public
     * @param  string $config 配置名称
     * @return mixed
     */
    public static function getConfig($name = '')
    {
        if ('' === $name) {
            return self::$config;
        }

        return isset(self::$config[$name]) ? self::$config[$name] : null;
    }

    /**
     * 切换数据库连接, $config = '数据源A'
     * @access public
     * @param  mixed         $config 连接配置
     * @param  bool|string   $name 连接标识 true 强制重新连接
     * @param  string        $query 查询对象类名
     * @return mixed 返回查询对象实例
     * @throws Exception
     */
    public static function connect($config = [], $name = false, $query = '')
    {
        // 解析配置参数
        $options = self::parseConfig($config ?: self::$config);

        //mysql or mongo
        $query = $query ?: $options['query'];

        // 创建数据库连接对象实例
        self::$connection = Connection::instance($options, $name);

        return new $query(self::$connection);
    }

    /**
     * 数据库连接参数解析
     * @access private
     * @param  mixed $config
     * @return array
     */
    private static function parseConfig($config)
    {
        if (is_string($config) && false === strpos($config, '/')) {
            // 指定了特定数据源，读取配置参数
            $config = isset(self::$config[$config]) ? self::$config[$config] : self::$config;
        }

        $result = is_string($config) ? self::parseDsnConfig($config) : $config;

        if (empty($result['query'])) {
            $result['query'] = self::$config['query'];
        }

        return $result;
    }

    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @access private
     * @param  string $dsnStr
     * @return array
     */
    private static function parseDsnConfig($dsnStr)
    {
        $info = parse_url($dsnStr);

        if (!$info) {
            return [];
        }

        $dsn = [
            'type'     => $info['scheme'],
            'username' => isset($info['user']) ? $info['user'] : '',
            'password' => isset($info['pass']) ? $info['pass'] : '',
            'hostname' => isset($info['host']) ? $info['host'] : '',
            'hostport' => isset($info['port']) ? $info['port'] : '',
            'database' => !empty($info['path']) ? ltrim($info['path'], '/') : '',
            'charset'  => isset($info['fragment']) ? $info['fragment'] : 'utf8',
        ];

        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = [];
        }

        return $dsn;
    }
}
