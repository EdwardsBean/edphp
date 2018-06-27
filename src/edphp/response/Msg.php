<?php
namespace edphp\response;

class Msg
{

    public $code = 200;

    public $success = true;

    public static function success($data)
    {
        $m = new Msg();
        $m->data = $data;
        return $m;
    }

    public static function done()
    {
        $m = new Msg();
        return $m;
    }

    public static function fail($msg, $code = 500)
    {
        $m = new Msg();
        $m->success = false;
        $m->code = $code;
        $m->msg = $msg;
        return $m;
    }
}
