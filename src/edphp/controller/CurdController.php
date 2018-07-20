<?php

namespace edphp\controller;

use edphp\exception\HttpException;

/**
 * 自动生成增删改查控制器
 */
class CurdController
{
    protected $permission;

    function list() {
        $this->checkPermission();
        return db($this->getTable())->get();
    }

    public function save()
    {
        $this->checkPermission();
        //存在id则自动更新
        db($this->getTable())->save(post());
    }

    public function delete($id)
    {
        $this->checkPermission();
        return db($this->getTable())->delete($id);
    }

    public function one($id)
    {
        $this->checkPermission();
        return db($this->getTable())->getOne($id);
    }

    private function checkPermission() {
        if ($this->permission) {
            $called_method = debug_backtrace()[1]['function'];
            if (key_exists($called_method, $this->permission)) {
                if (strpos($this->permission[$called_method], ",")) {
                    $roles = explode(",", $this->permission);
                    foreach ($roles as $role) {
                        if ($role == user_role()) {
                            return;
                        }
                    }
                } elseif($this->permission[$called_method] == user_role()){
                    return;
                }
                throw HttpException(403, "you don't have the permission");
            }
        }
    }

    private function getTable()
    {
        $class = get_called_class();
        $r = explode("\\", $class);
        $len = count($r);
        $table = $r[$len - 1];
        return strtolower($table);
    }
}
