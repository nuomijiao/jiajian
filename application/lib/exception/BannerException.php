<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 11:07
 */

namespace app\lib\exception;


class BannerException extends BaseException
{
    public $msg = '请求Banner不存在';
    public $errorCode = 20000;
}