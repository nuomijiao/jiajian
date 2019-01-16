<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 13:35
 */

namespace app\lib\exception;


class OrderException extends BaseException
{
    public $msg = '订单不存在';
    public $errorCode = 40000;
}