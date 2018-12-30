<?php

/**
 * 支付、签约、回调统一接口
 * Auther: wanggaoqi
 * Date: 2018/12/28
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use app\lib\exception\UserException;

class Pay extends Controller
{
    // 商户号
    private $merno  = '168885';

    // MD5key
    private $MD5key = "12345678";

    /**
     * 双乾快捷支付 认证/签约接口
     */
    public function auth()
    {
        return json([
            'a' => 2
        ]);
        // v($_POST);
        // var_dump($_FILES);exit;
        $postData = Request::instance()->post();
        $authMsg = Request::instance()->post('authMsg', '123456');

        $reqMsgId = mt_rand(1000, 9999) . time();

        // 签约/认证数据
        $postData = [   
            'merNo'     => $this->merno,                                        // 商户号
            'custName'  => @$postData['custName'],                               // 姓名
            'phoneNo'   => @$postData['phoneNo'],                                // 手机号
            'cardNo'    => @$postData['cardNo'],                                 // 银行卡号
            'bankCode'  => @$postData['bankCode'],                               // 银行代码
            'idNo'      => @$postData['idNo'],                                   // 身份证
            'idType'    => 0,                                                   // 证件类型（0.身份证）
            'reqMsgId'  => $reqMsgId,                                           // 商户请求流水号
            'cardType'  => 1,                                                    // 卡类型（1.借记卡 2.贷记卡）
            'authMsg'   => @$authMsg,                                            // 授权信息:签约交易必填，认证成功返回
            'custType'  => @$postData['custType'],                               // 01：认证，02：签约
            'payType'   => 'XYPAY',                                             // 校验渠道 固定：XYPAY
        ];

        // 字典序
        ksort($postData);

        // 签名拼接加密
        $joinMapValue = joinMapValue($postData);
        $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
        $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

        // 请求
        $result = curlPost(Config::get('auth_url'), $postData);
        
        return $result;
    }

    /**
     * 双乾快捷支付 支付接口(须先签约)(此接口若启用短信通道，须调用交易接口，非启用则直接完成支付，异步通知更新业务逻辑)
     */
    public function consume()
    {
        $merOrderNo = 'JJZf' . time() . mt_rand(10000, 99999);

        // 支付请求数据
        $postData = [   
            'merNo'     => $this->merno,                                        // 商户号
            'custName'  => '汪高启',                                             // 姓名
            'cardNo'    => '6227002006600280380',                               // 银行卡号
            'phone'     => '13285177013',                                       // 手机号
            'idNo'      => '342221199110018230',                                // 身份证
            'idType'    => 0,                                                   // 证件类型（0.身份证）
            'payAmount' => 0.01,                                                // 交易金额（单位元，小数保留2位）
            'merOrderNo'=> $merOrderNo,                                         // 商户订单号
            'bankCode'  => 'CCB',                                               // 银行代码
            'payType'   => 'XYPAY',                                             // 校验渠道 固定：XYPAY
            'cardType'  => 1,                                                   // 卡类型（1.借记卡 2.贷记卡）
            'NotifyURL' => Config::get('notify_url'),                           // 异步通知地址(选填)
            'transDate' => date('Ymd'),                                         // 交易日期
            'transTime' => date('His'),                                         // 交易时间
            'purpose'   => '测试',                                               // 商户备注(选填)
        ];

        // 字典序加密
        $md5InfoData = [
            'merNo'     => $postData['merNo'],
            'custName'  => $postData['custName'],
            'phone'     => $postData['phone'],
            'cardNo'    => $postData['cardNo'],
            'idNo'      => $postData['idNo'],
            'idType'    => $postData['idType'],
            'payAmount' => $postData['payAmount'],
            'merOrderNo'=> $postData['merOrderNo'],
            'cardType'  => $postData['cardType'],
            'year'      => '',
            'month'     => '',
            'CVV2'      => '',
            'transDate' => $postData['transDate'],
            'transTime' => $postData['transTime'],
        ];

        $joinMapValue = joinMapValue($md5InfoData);
        $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
        $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

        // 请求
        $result = curlPost(Config::get('syn_url'), $postData);

        v(json_decode($result));
    }


    /**
     * 双乾快捷支付 交易接口(短信通道) 暂不使用
     */
    public function transaction()
    {
        $merOrderNo = Request::instance()->post('merOrderNo');
        $txnTime    = Request::instance()->post('txnTime');
        $smsCode    = Request::instance()->post('smsCode');

        // 支付请求数据
        $postData = [   
            'merNo'     => $this->merno,                                        // 商户号
            'merOrderNo'=> $merOrderNo,                                         // 商户订单号
            'cardNo'    => '6227002006600280380',                               // 银行卡号
            'custName'  => '汪高启',                                             // 姓名
            'idType'    => 0,                                                   // 证件类型（0.身份证）
            'idNo'      => '342221199110018230',                                // 身份证
            'phone'     => '13285177013',                                       // 手机号
            'purpose'   => '',                                                  // 交易信息
            'payAmount' => 0.01,                                                // 交易金额（单位元，小数保留2位）
            'bankCode'  => 'CCB',                                               // 银行代码
            'payType'   => 'XYPAY',                                             // 校验渠道 固定：XYPAY
            'NotifyURL' => Config::get('notify_url'),                           // 异步通知地址(选填)
            'txnTime'   => $txnTime,                                            // 交易时间
            'smsCode'   => $smsCode,                                            // 短信验证码
        ];

        // 此处为短信通道 商户业务逻辑...

    }

    /**
     * 双乾快捷支付 解约接口
     */
    public function break_bind_pay()
    {
        $reqMsgId = mt_rand(1000, 9999) . time();

        // 支付请求数据
        $postData = [   
            'merNo'     => $this->merno,                                        // 商户号
            'custName'  => '汪高启',                                             // 姓名
            'phoneNo'   => '13285177013',                                       // 手机号
            'cardNo'    => '6227002006600280380',                               // 银行卡号
            'idNo'      => '342221199110018230',                                // 身份证
            'reqMsgId'  => '834124411',                                         // 商户订单号
            'payType'   => 'NUCCPAY',                                           // 网联：NUCCPAY；银联：UNIONPAY
        ];   

        // 字典序
        ksort($postData);

        // 签名拼接加密
        $joinMapValue = joinMapValue($postData);
        $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
        $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

        // 请求
        $result = curlPost(Config::get('break_bind_pay_url'), $postData);
        
        v(json_decode($result));
    }
}