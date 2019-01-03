<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 17:00
 */

namespace app\jjapi\model;


class WhAccountApply extends BaseModel
{
    protected $autoWriteTimestamp = true;

    public static function checkApplyExist($uid, $type)
    {
        return self::where(['user_id'=>$uid, 'type'=>$type])->order('id', 'desc')->limit(1)->find();
    }
}