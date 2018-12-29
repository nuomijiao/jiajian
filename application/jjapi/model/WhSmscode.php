<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:42
 */

namespace app\jjapi\model;

class WhSmscode extends BaseModel
{
    public function setExpireTimeAttr($value, $data)
    {
        return ($data['create_time'] + config('aliyun.sms_code_expire'));
    }

    public static function checkByMobile($mobile,$type)
    {
        $mobile_count = self::whereTime('create_time', 'today')->where(['mobile_number' => $mobile, 'type' => $type])->count();
        return $mobile_count;
    }

    public static function checkCode($mobile, $code, $type)
    {
        $codeInfo = self::where(['mobile_number' => $mobile, 'validate_code' => $code, 'type' => $type])->order('id', 'desc')->limit(1)->find();
        return $codeInfo;
    }

    public static function changeStatus($mobile, $code, $type, $time = '')
    {
        self::where(['mobile_number'=>$mobile, 'validate_code'=>$code, 'type'=>$type])->order('id', 'desc')->limit(1)->update(['using_time'=>$time]);
    }
}