<?php

namespace edphp\controller;

use edphp\exception\HttpException;

/**
 */
class AuthController
{
    // 权限表 ["save" => "admin"]
    protected $permission;

    // 设定后，该控制器所有方法，只允许该角色访问，优先级大于$permission
    protected $role;

    //该方法将在Dispatch处调用，检查用户是否有调用该控制器的权限
    public function checkPermission() {
        if ($this->role && $this->role !== user_role()) {
            throw new HttpException(403, "you don't have the permission");
        } elseif ($this->permission) {
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
                throw new HttpException(403, "you don't have the permission");
            }
        }
    }

}
