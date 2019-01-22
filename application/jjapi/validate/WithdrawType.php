<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/22
 * Time: 13:27
 */

namespace app\jjapi\validate;


class WithdrawType extends BaseValidate
{
    protected $rule = [
        'page' => 'isPositiveInteger',
        'size' => 'isPositiveInteger',
        'type' => 'require|in:1,2,3',
    ];

    protected $message = [
        'page' => '分页参数必须是正整数',
        'size' => '分页参数必须是正整数',
    ];
}