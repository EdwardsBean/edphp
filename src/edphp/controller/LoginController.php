<?php

namespace edphp\controller;

use edphp\Auth;
use edphp\Session;
use edphp\response\Msg;

class User
{

    //登录接口
    public function signin()
    {
        $token = Auth::atempt();
        return Msg::done(['token' => $token]);
    }

    public function logout()
    {
        Session::destroy();
    }

}
