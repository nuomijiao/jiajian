<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 16:13
 */

namespace app\lib\exception;


class ShareException extends BaseException
{
    public $msg = '共享信息不存在';
    public $errorCode = 60000;
}