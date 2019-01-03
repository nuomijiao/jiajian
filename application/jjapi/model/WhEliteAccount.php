<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 16:21
 */

namespace app\jjapi\model;


use app\lib\enum\AccountApplyStatusEnum;

class WhEliteAccount extends BaseModel
{
    protected $autoWriteTimestamp = true;

    public static function checkEliteExist($uid)
    {
        return self::where('user_id', '=', $uid)->order('id', 'desc')->limit(1)->find();
    }

}