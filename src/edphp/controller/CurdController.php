<?php

namespace edphp\controller;

use edphp\exception\HttpException;

/**
 * 自动生成增删改查控制器
 */
class CurdController extends AuthController
{
    protected $limit_user;

    public function list() {
        $where = [];
        if (isset($limit_user)) {
            $where['user_id'] = user_id();
        }
        return db($this->getTable())->where($where)->get();
    }

    public function save()
    {
        $where = [];
        if (isset($limit_user)) {
            $where['user_id'] = user_id();
        }
        //存在id则自动更新
        $p = post();
        db($this->getTable())->where($where)->save($p);
    }

    public function delete($id)
    {
        $where = [];
        if (isset($limit_user)) {
            $where['user_id'] = user_id();
        }
        return db($this->getTable())->where($where)->delete($id);
    }

    public function one($id)
    {
        $where = [];
        if (isset($limit_user)) {
            $where['user_id'] = user_id();
        }
        return db($this->getTable())->where($where)->getOne($id);
    }

    protected function getTable()
    {
        $class = get_called_class();
        $r = explode("\\", $class);
        $len = count($r);
        $table = $r[$len - 1];
        $table = parseName($table);
        return $table;
    }
}
