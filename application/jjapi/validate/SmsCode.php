<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:17
 */

namespace app\jjapi\validate;



class SmsCode extends BaseValidate
{
    protected $rule = [
        'mobile' => 'require|isMobile',
        'type' => "require|in:1,2"
    ];

    protected $message = [
        'mobile' => '请输入正确手机号',
        'type' => '请选择验证码类型'
    ];
}