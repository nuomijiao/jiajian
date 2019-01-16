<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 10:27
 */

namespace app\jjapi\validate;


class MoneyRechagre extends BaseValidate
{
    protected $rule = [
        'money' => 'require|gt:0',
        'pay_type' => 'require|in:1,2'
    ];

    protected $message = [
        'money' => '输入的正确的金额',
    ];

//    public function isMoney($value)
//    {
//        $rule = '/^([1-9]\d*(\.\d*[1-9])?)|(0\.\d*[1-9])$/';
//        $result = preg_match($rule, $value);
//        if ($result) {
//            return true;
//        } else {
//            return false;
//        }
//    }
}