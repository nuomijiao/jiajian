<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:15
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\WhSmscode;
use app\jjapi\model\WhUser;
use app\jjapi\service\Token;
use app\jjapi\service\UserToken;
use app\jjapi\validate\LoginTokenGet;
use app\jjapi\validate\RegisterOrReset;
use app\lib\enum\SmsCodeTypeEnum;
use app\lib\enum\UserDegreeEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;
use think\Cache;
use think\Request;

class LogAndReg extends BaseController
{
    public function register($mobile = '', $pwd = '', $pwd1 = '', $code = '')
    {
        //注册流程
        //1. 判断手机号码是否已被注册
        //2.判断验证码是否正确
        //3.账号密码加密新增数据库
        //4.注册成功，即生成token返回给客户端，保存登陆状态
        (new RegisterOrReset())->goCheck();
        //检查两次密码是否一致
        if ($pwd !== $pwd1) {
            throw new UserException([
                'msg' => '两次密码不一致',
                'errorCode' => 30004,
            ]);
        }
        //检查手机号码是否被注册
        $user = WhUser::checkUserByMobile($mobile);
        if ($user) {
            throw new UserException([
                'msg' => '手机号码已注册，请直接登录',
                'errorCode' => 30001,
            ]);
        }
        //检查验证码是否正确
        $codeInfo = WhSmscode::checkCode($mobile, $code, SmsCodeTypeEnum::ToRegister);
        if (!$codeInfo || $codeInfo['validate_code'] != $code || $codeInfo['expire_time'] < time() || $codeInfo['using_time'] > 0) {
            throw new UserException([
                'msg' => '验证码不匹配或已过期',
                'errorCode' => 30005,
            ]);
        } else {
            $timenow = time();
            //修改验证码使用状态
            WhSmscode::changeStatus($mobile, $code, SmsCodeTypeEnum::ToRegister, $timenow);
            //新增用户数据库
            $dataArray = [
                'mobile' => $mobile, 'pwd' => md5(md5($pwd)),
                'id_number' => self::randIdNumber(), 'head_img' => '/assets/img/user_head.png', 'degree' => UserDegreeEnum::YouKe
            ];
            $user = WhUser::create($dataArray);
            if ($user) {
                throw new SuccessMessage([
                    'msg' => '注册成功',
                ]);
            }
        }
    }

    public function login($mobile = '', $pwd = '')
    {
        (new LoginTokenGet())->goCheck();
        //检查手机是否注册
        $user = WhUser::checkUserByMobile($mobile);
        if (!$user) {
            throw new UserException([
                'msg' => '手机号还未注册',
                'errorCode' => 30002,
            ]);
        }
        //检查手机号密码是否正确
        $user = WhUser::checkUser($mobile, $pwd);
        if (!$user) {
            throw new UserException([
                'msg' => '手机号或密码不正确',
                'errorCode' => 30006
            ]);
        } else {
            $log = new UserToken();
            $token = $log->getToken($user->id);
            return $this->jjreturn(['token'=>$token]);
        }
    }


    public function resetPwd($mobile = '', $pwd = '', $pwd1 = '', $code = '')
    {
        (new RegisterOrReset())->goCheck();
        //检查两次密码是否一致
        if ($pwd !== $pwd1) {
            throw new UserException([
                'msg' => '两次密码不一致',
                'errorCode' => 30004,
            ]);
        }
        //检查手机号码是否被注册
        $user = WhUser::checkUserByMobile($mobile);
        if (!$user) {
            throw new UserException([
                'msg' => '手机号码还未注册',
                'errorCode' => 30002,
            ]);
        }
        //检查验证码是否正确
        $codeInfo = WhSmscode::checkCode($mobile, $code, SmsCodeTypeEnum::ToResetPwd);
        if (!$codeInfo || $codeInfo['validate_code'] != $code || $codeInfo['expire_time'] < time() || $codeInfo['using_time'] > 0) {
            throw new UserException([
                'msg' => '验证码不匹配或已过期',
                'errorCode' => 30005,
            ]);
        } else {
            $timenow = time();
            //修改验证码使用状态
            WhSmscode::changeStatus($mobile, $code, SmsCodeTypeEnum::ToResetPwd, $timenow);
            $dataArray = [
                'id' => $user->id,
                'pwd' => md5(md5($pwd)),
            ];
            $user = WhUser::update($dataArray);
            if ($user) {
                throw new SuccessMessage([
                    'msg' => '修改成功',
                ]);
            }
        }
    }

    public function logout()
    {
        $token = Request::instance()->header('token');
        $vars = Cache::get($token);
        if ($vars) {
            cache($token, NULL);
        }
        throw new SuccessMessage([
            'msg' => '退出成功',
        ]);
    }

    //判断是否登录
    public function isLogin()
    {
        $uid = Token::getCurrentUid();
        throw new SuccessMessage([
            'msg' => '已登录',
        ]);
    }


    private static function randIdNumber()
    {
        $IdNumber = "jj_".self::getRandChar(6);
        $user = WhUser::checkUserByIdNumber($IdNumber);
        if ($user) {
            self::randIdNumber();
        } else {
            return $IdNumber;
        }

    }

    private static function getRandChar($length)
    {
        $str = null;
        $strPol = "0123456789";
        $max = strlen($strPol) - 1;

        for ($i = 0;
             $i < $length;
             $i++) {
            $str .= $strPol[rand(0, $max)];
        }

        return $str;
    }
}