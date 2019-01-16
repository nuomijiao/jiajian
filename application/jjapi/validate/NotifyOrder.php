<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 13:23
 */

namespace app\jjapi\validate;


class NotifyOrder extends BaseValidate
{
    protected $rule = [
        'ordersn' => 'require|alphaNum',
    ];

    protected $message = [
        'ordersn' => '订单号只能为数字和字母'
    ];
}