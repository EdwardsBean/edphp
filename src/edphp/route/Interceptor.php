<?php

namespace edphp\route;

abstract class Interceptor
{

    //不需要拦截的路由
    protected $exclude = [];

    protected $letgo;

    //拦截器的顺序，值越小排越前
    protected $order =  100;

    public function doPreHandle()
    {
        if ($this->check()) {
            return;
        }

        return $this->preHandle();
    }

    public function doAfterCompletion()
    {
        if ($this->check()) {
            return;
        }

        return $this->afterCompletion();
    }

    protected function check()
    {
        if (!empty($this->letgo)) {
            return $this->letgo;
        }
        $path = $_SERVER['PATH_INFO'];
        foreach ($this->exclude as $route) {
            //泛匹配处理
            if (strpos($route, '*')) {
                //处理成真的正则表达式
                $regx = str_replace('/', '\/', $route);
                $regx = '/' . str_replace('*', '.*', $regx) . '/';
                if (preg_match($regx, $path)) {
                    $this->letgo = true;
                    return true;
                }
            } else if ($route === $path) {
                $this->letgo = true;
                return true;
            } else {
                return false;
            }
        }
    }

    public function getOrder() {
        return $this->order;
    }

    /**
     * @return 不为null则直接当做页面输出值
     */
    abstract public function preHandle();

    /**
     * @return 不为null则直接当做页面输出值
     */
    abstract public function afterCompletion();

}
