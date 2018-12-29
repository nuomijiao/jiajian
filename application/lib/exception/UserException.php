<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:26
 */

namespace app\lib\exception;


class UserException extends BaseException
{
    public $msg = '用户不存在';
    public $errorCode = 30000;
}