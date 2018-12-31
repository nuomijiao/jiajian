<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 14:58
 */

namespace app\jjapi\validate;


class ShareNew extends BaseValidate
{
    protected $rule = [
        'title' => 'require',
        'content' => 'require',
        'ids' => 'require|isIds',

    ];

    protected $message = [
        'title' => '标题不能为空',
        'content' => '内容不能为空',
        'ids' => 'ids格式有误',

    ];

    public function isIds($value)
    {
        $rule = '/^\d+(,\d+)*$/';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}