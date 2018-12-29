<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:22
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\WhSmsCode;
use app\jjapi\model\WhUser;
use app\jjapi\validate\SmsCode;
use app\lib\enum\SmsCodeTypeEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;
use app\jjapi\service\SendSms;
use think\Exception;

class Sms extends BaseController
{
    public function sendSms($type = SmsCodeTypeEnum::ToRegister) {
        $request = (new SmsCode())->goCheck();
        $mobile = $request->param('mobile');
        $user = WhUser::checkUserByMobile($mobile);
        if (SmsCodeTypeEnum::ToRegister == $type) {
            if ($user) {
                throw new UserException([
                    'msg' => '手机号码已注册，请直接登录',
                    'errorCode' => 30001,
                ]);
            }
        } elseif (SmsCodeTypeEnum::ToResetPwd == $type){
            if (!$user) {
                throw new UserException([
                    'msg' => '手机号码未注册',
                    'errorCoce' => 30002,
                ]);
            }
        }

        $mobile_count = WhSmsCode::checkByMobile($mobile, $type);
        if ($mobile_count > config('aliyun.sms_mobile_limit')) {
            throw new UserException([
                'msg' => '发送次数过多',
                'errorCode' => 30003,
            ]);
        } else {
            $code = $this->randomKeys(config('aliyun.sms_KL'));
            $sendSms = new SendSms($mobile, $code, config("aliyun.sms_TC".$type));
            //返回stdClass
            $acsResponse = $sendSms->sendSms();
            if ('OK' == $acsResponse->Code) {
                $dataArray = [
                    'mobile_number' => $mobile, 'validate_code' => $code, 'type' => $type, 'create_time' => time(),
                    'expire_time' => '',
                ];
                WhSmscode::create($dataArray);
                throw new SuccessMessage([
                    'msg' => '验证码发送成功',
                ]);
            } else {
                throw new Exception($acsResponse->Message);
            }
        }
    }

    private function randomKeys($length)
    {
        $key='';
        $pattern='1234567890';
        for($i=0;$i<$length;++$i)
        {
            $key .= $pattern{mt_rand(0,9)}; // 生成php随机数
        }
        return $key;
    }
}