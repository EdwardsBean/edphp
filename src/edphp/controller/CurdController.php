<?php

namespace edphp\controller;

use edphp\exception\HttpException;

/**
 * 自动生成增删改查控制器
 */
class CurdController extends AuthController
{

    function list() {
        return db($this->getTable())->get();
    }

    public function save()
    {
        //存在id则自动更新
        db($this->getTable())->save(post());
    }

    public function delete($id)
    {
        return db($this->getTable())->delete($id);
    }

    public function one($id)
    {
        return db($this->getTable())->getOne($id);
    }

    protected function getTable()
    {
        $class = get_called_class();
        $r = explode("\\", $class);
        $len = count($r);
        $table = $r[$len - 1];
        $table = parseName($table);
        return $table;
    }
}
