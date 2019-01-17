<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/17
 * Time: 14:09
 */

namespace app\jjapi\model;


class WhBalanceDetail extends BaseModel
{
    protected $autoWriteTimestamp = true;

    public static function getDetailByUser($uid, $page, $size)
    {
        return self::where('user_id', '=', $uid)->order('create_time', 'desc')->paginate($size, true, ['page' => $page]);
    }

    public static function getDetailByCompany($cid, $page, $size)
    {
        return self::where('company_id', '=', $cid)->order('create_time', 'desc')->paginate($size, true, ['page' => $page]);
    }
}