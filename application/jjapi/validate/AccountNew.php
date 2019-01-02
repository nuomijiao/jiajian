<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 16:45
 */

namespace app\jjapi\validate;


class AccountNew extends BaseValidate
{
    protected $rule = [
        'fullname' => 'require|chs',
        'mobile' => 'require|isMobile',
        'province_id' => 'require|isPositiveInteger',
        'city_id' => 'require|isPositiveInteger',
        'district_id' => 'require|isPositiveInteger',
        'address' => 'require',
        'alliance_id_number' => 'alphaNum',
        'type' => 'require|in:1,2',
    ];

    protected $message = [
        'fullname' => '姓名必须为汉字',
        'mobile' => '手机号格式不正确',
        'province_id' => '省id必须为正整数',
        'city_id' => '市id必须为正整数',
        'district_id' => '区id必须为正整数',
        'address' => '详细地址不能为空',
        'alliance_id_number' => '加盟商识别码必须为字母和数字',
        'type' => 'require|in:1,2',
    ];
}