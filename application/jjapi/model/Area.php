<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 14:21
 */

namespace app\jjapi\model;


class Area extends BaseModel
{
    public static function getProvince()
    {
        return self::where('level', '=', 1)->select();
    }
}