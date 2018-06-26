<?php

namespace edphp\route;

/**
 * 自动加载interceptor目录下的拦截器
 */
class Middleware
{

    protected $defaultNamespace = 'app\\interceptor\\';

    protected $queue = [];

    public function import($interceptor)
    {
        $class = $this->defaultNamespace . $interceptor;
        $instance = new $class();
        array_push($this->queue, $instance);
    }

    public function preHandle()
    {
        foreach ($this->queue as $interceptor) {
           $interceptor->doPreHandle(); 
        }
    }

    public function afterCompletion()
    {
        foreach ($this->queue as $interceptor) {
            $interceptor->doAfterCompletion();
        }
    }

}
