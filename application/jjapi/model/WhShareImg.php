<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 15:28
 */

namespace app\jjapi\model;


class WhShareImg extends BaseModel
{
    public function getUrlAttr($value)
    {
        return config('setting.domain').$value;
    }
}