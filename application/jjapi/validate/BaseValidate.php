<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/27
 * Time: 22:44
 */

namespace app\jjapi\validate;


use think\Validate;
use app\lib\exception\ParameterException;
use think\Request;


class BaseValidate extends Validate
{
    public function goCheck() {
        //获取http传入的参数
        //对这些参数做校验
        $request = Request::instance();
        $params = $request->param();
        $result = $this->batch()->check($params);
        if (!$result) {
            $e = new ParameterException([
                'msg' => $this->error,
            ]);
            throw $e;
        } else {
            return $request;
        }
    }

    protected function isPositiveInteger($value, $rule = '', $data = '', $field = '') {
        if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0) {
            return true;
        } else {
            return false;
//            return $field."必须是整数";
        }
    }

    protected function isInteger($value, $rule = '', $data = '', $field = '') {
        if (is_numeric($value) && is_int($value + 0) && ($value + 0) >= 0) {
            return true;
        } else {
            return false;
//            return $field."必须是整数";
        }
    }

    protected function isMobile($value) {
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    protected function isNotEmpty($value, $rule = '', $data = '', $field = '') {
        if (empty($value)) {
//            return $field. '不允许为空';
            return false;
        } else {
            return true;
        }
    }

    public function getDataByRule($arrays) {
        if (array_key_exists('user_id', $arrays) || array_key_exists('uid', $arrays)) {
            //不允许包含user_id或者uid，防止恶意覆盖user_id外键
            throw new ParameterException([
                'msg' => '参数中含有非法的参数名user_id或uid',
            ]);
        }
        $newArray = [];
        foreach ($this->rule as $key => $value) {
            $newArray[$key] = $arrays[$key];
        }
        return $newArray;
    }
}