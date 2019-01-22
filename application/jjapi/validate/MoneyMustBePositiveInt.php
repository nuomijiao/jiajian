<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/22
 * Time: 13:51
 */

namespace app\jjapi\validate;


class MoneyMustBePositiveInt extends BaseValidate
{
    protected $rule = [
        'money' => 'require|isPositiveInteger',
    ];

    protected $message = [
        'money' => '提现金额必须为正整数',
    ];
}