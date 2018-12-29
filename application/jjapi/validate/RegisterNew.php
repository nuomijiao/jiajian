<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 10:09
 */

namespace app\jjapi\validate;


class RegisterNew extends BaseValidate
{
    protected $rule = [
        'mobile' => 'require|isMobile',
        'pwd' => 'require|isNotEmpty',
        'pwd1' => 'require|isNotEmpty',
        'code' => 'require|isCode',
    ];

    protected $message = [
        'mobile' => '请输入正确的手机号',
        'pwd' => '密码不能为空',
        'pwd1' => '请确认密码',
        'code' => '验证码为6位数字',
    ];

    public function isCode($value)
    {
        $rule = '/^\d{'.config('aliyun.sms_KL').'}$/';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}