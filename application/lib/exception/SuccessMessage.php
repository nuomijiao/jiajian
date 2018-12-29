<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:51
 */

namespace app\lib\exception;


class SuccessMessage extends BaseException
{
    public $msg = 'OK';
    public $errorCode = 'Success';
}