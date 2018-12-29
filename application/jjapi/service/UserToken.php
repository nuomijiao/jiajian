<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 10:02
 */

namespace app\jjapi\service;


use app\lib\exception\ParameterException;

class UserToken extends Token
{
    public function getToken($uid = null)
    {
        $values = [
            'uid' => $uid,
        ];
        $token = $this->saveToCache($values);
        return $token;
    }

    private function saveToCache($values){
        $token = self::generateToken();
        $expire_in = config('secure.token_expire_in');
        $result = cache($token, json_encode($values), $expire_in);
        if(!$result){
            throw new ParameterException([
                'msg' => '服务器缓存异常',
                'errorCode' => 10002
            ]);
        }
        return $token;
    }

}