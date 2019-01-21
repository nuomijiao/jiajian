<?php

/**
 * 基础过滤器
 * Auther: wanggaoqi
 * Date: 2018/1/4
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use think\Db;
use app\jjapi\service\Token;
use app\jjapi\model\Auth;
use app\lib\exception\UserException;

// 生产环境下 此处应验证token等 是否登录
class Base extends Controller
{
    // 基础过滤器
    public function _initialize()
    {
        if($_SERVER['SERVER_NAME'] === 'jj.888.com')    // 测试环境 
        {   
            $this->uid = 1;
        }
        else                                            // 生产环境
        {
            if(Request::instance()->isPost())
            {
                $this->uid = Token::getCurrentUid();
            }
            else
            {
                echo json_encode([
                    'errcode' => 201,
                    'errmsg'  => 'illegal request',
                ]);
    
                die();
            }
        }
    }

    /**
     * sendSmsCode
     * 公共验证码方法
     */
    protected static function sendSmsCode($phone, $content)
    {
        // 代码标题
        $statusStr = [
            "0" => "短信发送成功[短信宝]",
            "-1" => "参数不全[短信宝]",
            "-2" => "短信宝服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！[短信宝]",
            "30" => "密码错误[短信宝]",
            "40" => "账号不存在[短信宝]",
            "41" => "余额不足[短信宝]",
            "42" => "帐户已过期[短信宝]",
            "43" => "IP地址限制[短信宝]",
            "50" => "内容含有敏感词[短信宝]"
        ];

        $smsData = [
            'u' => 'jiajian',               // 帐户
            'p' => md5('jiajian2019'),       // 密码
            'm' => $phone,                  // 手机号
            'c' => $content,                // 内容
        ];

        $param  = http_build_query($smsData);
        $smsApi = 'http://api.smsbao.com/sms?' . $param;
        $result = file_get_contents($smsApi);

        return $result == 0 ? 'ok' : $statusStr[$result];
    }
}