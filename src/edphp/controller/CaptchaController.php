<?php

namespace edphp\controller;

use edphp\Captcha;

class CaptchaController
{
    public function index($id = "")
    {
        $captcha = new Captcha((array) config('captcha.'));
        return $captcha->entry($id);
    }

    //可用于主动检测验证码是否输入正确
    public function check($id = "", $code) {
        if(captcha_check($code, $id)) {
            return success("verify ok");
        } else {
            return fail("code incorrect");
        }
    }
}
