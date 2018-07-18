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
        return Msg::success(['token' => $token]);
    }

    public function info()
    {
        $user = db('user')->findById(userid())->exclude('password')->getOne();
        return Msg::success($user);
    }

    public function logout()
    {
        Session::destroy();
    }

}
