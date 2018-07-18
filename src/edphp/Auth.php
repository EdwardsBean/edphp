<?php

namespace edphp;

use edphp\exception\HttpException;

class Auth {


    /**
     * 返回用户id
     */
    public static function userId() {
        $userId = session('user_id');
        return $userId;
    }

    /**
     * 检查是否登录过
     */
    public static function check() {
        $userId = self::userId();
        if($userId) {
            throw new HttpException(401, "未登录");
        }
    }

    /**
     * 校验账号密码
     */
    public static function attempt() {
        $username = post('username');
        $password = post('password');
        if (!$username || !$password) {
            throw new HttpException(401, '账号或者密码字段未传');
        }
        $user = db('user')->findByUsername($username)->getOne();
        if ($user['password'] === md5($password)) {
            //登录成功
            session(['user_id' => $user['id'], 'user_role' => $user['role']]);
            return session_id();
        } else {
            throw new HttpException(401, '账号或者密码错误');
        }
    }

}