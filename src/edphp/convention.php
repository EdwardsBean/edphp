<?php

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    'app'      => [
        // 应用名称
        'app_name'               => '',
        // 应用地址
        'app_host'               => '',
        // 应用调试模式
        'app_debug'              => false,
        // 应用Trace
        'app_trace'              => false,
        // 应用模式状态
        'app_status'             => '',
        // 入口自动绑定模块
        'auto_bind_module'       => false,
        // 注册的根命名空间
        'root_namespace'         => [],
        // 默认输出类型
        'default_return_type'    => 'json',
        //是否默认使用Msg对象包裹控制器输出
        'wrap_return_object'     => true,
        // 默认AJAX 数据返回格式,可选json xml ...
        'default_ajax_return'    => 'json',
        // 默认JSONP格式返回的处理方法
        'default_jsonp_handler'  => 'jsonpReturn',
        // 默认JSONP处理方法
        'var_jsonp_handler'      => 'callback',
        // 默认时区
        'default_timezone'       => 'Asia/Shanghai',
        // 默认验证器
        'default_validate'       => '',

        // +----------------------------------------------------------------------
        // | 模块设置
        // +----------------------------------------------------------------------

        // 默认的空控制器名
        'empty_controller'       => 'Error',
        // 默认的空模块名
        'empty_module'           => '',
        // 是否支持多模块
        'app_multi_module'       => true,

        // +----------------------------------------------------------------------
        // | URL请求设置
        // +----------------------------------------------------------------------

        // IP代理获取标识
        'http_agent_ip'          => 'X-REAL-IP',
        // URL伪静态后缀
        'url_html_suffix'        => 'html',
        // 表单请求类型伪装变量
        'var_method'             => '_method',
        // 表单ajax伪装变量
        'var_ajax'               => '_ajax',
        // 表单pjax伪装变量
        'var_pjax'               => '_pjax',
        // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
        'request_cache'          => false,
        // 请求缓存有效期
        'request_cache_expire'   => null,
        // 全局请求缓存排除规则
        'request_cache_except'   => [],

        // +----------------------------------------------------------------------
        // | 异常及错误设置
        // +----------------------------------------------------------------------

        // 默认跳转页面对应的模板文件
        'dispatch_success_tmpl'  => __DIR__ . '/tpl/dispatch_jump.tpl',
        'dispatch_error_tmpl'    => __DIR__ . '/tpl/dispatch_jump.tpl',
        // 异常页面的模板文件
        'exception_tmpl'         => __DIR__ . '/tpl/exception.tpl',
        // 错误显示信息,非调试模式有效
        'error_message'          => '页面错误！请稍后再试～',
        // 显示错误信息
        'show_error_msg'         => false,
        // 异常处理handle类 留空使用 \edphp\exception\Handle
        'exception_handle'       => '',
    ],

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'      => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'         => 'File',
        // 日志保存目录
        //'path'  => LOG_PATH,
        // 日志记录级别
        'level'        => [],
        // 是否记录trace信息到日志
        'record_trace' => true,
        // 是否JSON格式记录
        'json'         => false,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'  => [
        //PHPSESSID或token字段名称
        'name'           => 'X-Token',
        //设置sessionid
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'edphp',
        //token或者默认cookie方式，token方式将会从Header中取name
        'type'           => '',
        // 驱动方式，默认使用php自带的session存储。支持redis
        'driver'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
        'httponly'       => true,
        'secure'         => false,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------

    'cookie'   => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    // +----------------------------------------------------------------------
    // | 数据库设置
    // +----------------------------------------------------------------------

    'database' => [
        // 数据库类型
        'type'            => 'mysql',
        // 数据库连接DSN配置
        'dsn'             => '',
        // 服务器地址
        'hostname'        => '127.0.0.1',
        // 数据库名
        'database'        => '',
        // 数据库用户名
        'username'        => 'root',
        // 数据库密码
        'password'        => '',
        // 数据库连接端口
        'hostport'        => '',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => '',
        // 数据库调试模式
        'debug'           => false,
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 数据集返回类型
        'resultset_type'  => 'array',
        // 自动写入时间戳字段
        'auto_timestamp'  => false,
        // 时间字段取出后的默认时间格式
        'datetime_format' => 'Y-m-d H:i:s',
        // 是否需要进行SQL性能分析
        'sql_explain'     => false,
        // 查询对象
        'query'           => '\\edphp\\db\\Query',
    ],

    //分页配置
    'paginate' => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],

    'trace' => [
        'type'       =>  'Console',
        'trace_tabs' =>  [
             'base'=>'基本',
             'file'=>'文件',
             'error|notice|warning'=>'错误',
             'sql'=>'SQL',
             'debug|info'=>'调试',
         ]
    ]
];
