<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 11:09
 */

namespace app\jjapi\validate;


class TypeMustBePositiveInt extends BaseValidate
{
    protected $rule = [
        'type' => 'require|isPositiveInteger',
    ];

    protected $message = [
        'type' => 'type类型必须是正整数',
    ];
}