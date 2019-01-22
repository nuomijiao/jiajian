<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/22
 * Time: 13:36
 */

namespace app\jjapi\model;


use app\lib\enum\UserDegreeEnum;

class WhWithdraw extends BaseModel
{
    protected $autoWriteTimestamp = true;

    public static function getWithdrawListByStatus($uid, $page, $size, $type)
    {
        return self::where(['user_id'=>$uid, 'type'=> UserDegreeEnum::JingYing, 'status' => $type])->order('create_time', 'desc')->paginate($size, true, ['page' => $page]);
    }
}