<?php

/**
 * auth模型
 * Auther: wanggaoqi
 * Date: 2018/12/31
 */

namespace app\jjapi\model;

use think\Model;

class Auth extends Model
{
    /**
     * 签约信息存储
     */
    public static function insert($data)
    {
        $auth = new Auth($data);
        
        return $auth->save();
    }
}