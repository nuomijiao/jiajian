<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 17:24
 */

namespace app\jjapi\validate;


class UserInfo extends BaseValidate
{
    protected $rule = [
        'fullname' => 'require|chs',
        'email' => 'require|email',
    ];

    protected $message = [

    ];
}