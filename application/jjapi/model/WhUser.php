<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:29
 */

namespace app\jjapi\model;


class WhUser extends BaseModel
{
    public function getHeadImgAttr($value) {
        return config('setting.domain').$value;
    }

    public static function checkUserByMobile($mobile)
    {
        $user = self::where('mobile', '=', $mobile)->find();
        return $user;
    }

    public static function checkUser($mobile, $pwd)
    {
        $user = self::where(['mobile'=>$mobile, 'user_pwd'=>md5(md5($pwd))])->find();
        return $user;
    }

    public static function checkUserByIdNumber($IdNumber)
    {
        $user = self::where('id_number', '=', $IdNumber)->find();
        return $user;
    }
}