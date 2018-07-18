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
        $order = $instance->getOrder();
        if(!$order) {
            $order = count($this->queue) +  time();
        }
        $this->queue[$order] = $instance;
    }

    public function preHandle()
    {
        foreach ($this->queue as $order => $interceptor) {
           $interceptor->doPreHandle(); 
        }
    }

    public function afterCompletion()
    {
        foreach ($this->queue as $order => $interceptor) {
            $interceptor->doAfterCompletion();
        }
    }

}
