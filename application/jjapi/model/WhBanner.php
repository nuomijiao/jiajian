<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 11:03
 */

namespace app\jjapi\model;


class WhBanner extends BaseModel
{
    public static function getBanner($type)
    {
        return self::where('type', '=', $type)->order('sort_order', 'asc')->select();
    }
}