<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/27
 * Time: 22:54
 */

namespace app\lib\exception;


class ParameterException extends BaseException
{
    public $msg = '参数错误';
    public $errorCode = 10000;
}