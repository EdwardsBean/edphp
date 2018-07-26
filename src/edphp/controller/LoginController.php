<?php

namespace edphp\controller;

use edphp\Auth;
use edphp\Session;
use edphp\exception\BizException;

class LoginController
{

    //登录接口
    public function signin()
    {
        $token = Auth::attempt();
        return ['token' => $token];
    }

    public function info()
    {
        return db('user')->findById(user_id())->exclude('password')->getOne();
    }

    public function logout()
    {
        Session::getInstance()->destroy();
    }

    public function password()
    {
        $params = post();
        $user_id = user_id();
        $user = db('user')->findbyId($user_id)->get();
        if ($user['password'] == $params['old_password']) {
            return db('user')->save(['id' => $user_id, 'password' => $params['password']]);
        } else {
            throw new BizException(50000, "旧密码错误");
        }
    }
}
