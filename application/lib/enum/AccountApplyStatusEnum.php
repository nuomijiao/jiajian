<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 16:19
 */

namespace app\lib\enum;


class AccountApplyStatusEnum
{
    //待审核
    const Wait = 1;

    //审核通过
    const Pass = 2;

    //审核不通过
    const NoPass = 3;
}