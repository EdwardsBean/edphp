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
}
