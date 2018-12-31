<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 14:44
 */

namespace app\lib\exception;


class CityException extends BaseException
{
    public $msg = '获取地区不存在';
    public $errorCode = 50000;
}