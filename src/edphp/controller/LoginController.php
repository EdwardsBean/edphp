<?php

namespace edphp\controller;

use edphp\Auth;
use edphp\Session;
use edphp\response\Msg;

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

}
