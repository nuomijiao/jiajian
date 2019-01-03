<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/3
 * Time: 14:14
 */

namespace app\lib\exception;


class OpenAccountException extends BaseException
{
    public $msg = '开户信息不存在';
    public $errorCode = 70000;
}