<?php
namespace edphp\response;

class Msg
{

    public $code = 200;

    public $success = true;

    public static function success($data = [], $msg = '')
    {
        $m = new Msg();
        $m->data = $data;
        $m->message = $msg;
        return $m;
    }

    public static function fail($msg, $code = 500, $data = [])
    {
        $m = new Msg();
        $m->success = false;
        $m->code = $code;
        $m->message = $msg;
        $m->data = $data;
        return $m;
    }
}
