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
        $user = db('user')->findbyId($user_id)->getOne();
        if ($user['password'] == md5($params['old_password'])) {
            return db('user')->save(['id' => $user_id, 'password' => md5($params['password'])]);
        } else {
            throw new BizException(50000, "旧密码错误");
        }
    }

    // 对于需要验证码的前后端分离，需要一开始获取一个token，用于存放验证码，以及后续登录成功也使用该token
    public function token()
    {
        session(['create_time' => time()]);
        return ['token' => session_id()];
    }
}
