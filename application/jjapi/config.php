<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/27
 * Time: 22:49
 */

//配置文件
$conf = [
    'exception_handle'    => 'app\lib\exception\ExceptionHandler',
    'default_return_type' => 'json',
];


// 支付、签约统一接口配置
$payConfig = [
    'dev_auth_url'  => 'http://218.4.234.150:9600/pay/identifyAuthAndSign.action',  // 认证/签约 测试地址
    'auth_url'      => 'https://fastpay.95epay.cn/pay/identifyAuthAndSign.action',  // 认证/签约 生产地址

    'dev_syn_url'   =>  'http://218.4.234.150:9600 /realnameAuth.action',           // 同步支付 测试地址
    'syn_url'       =>  'https://fastpay.95epay.cn/pay/realnameAuth.action',        // 同步支付 生产地址
    'notify_url'    => 'http://www.skeep.cc/jjapi/v1/pay/trans_notify',             // 异步支付通知地址

    'dev_trans_url' => 'http://218.4.234.150:9600/hfDaikouTrade.action',            // 交易接口 测试地址
    'trans_url'     => 'https://fastpay.95epay.cn/pay/hfDaikouTrade.action',        // 交易接口 生产地址

    'break_bind_pay_url' => 'https://fastpay.95epay.cn/pay/breakBindCard.action',       // 解约接口 测试地址
    'dev_break_bind_pay_url' => 'https://fastpay.95epay.cn/pay/breakBindCard.action',   // 解约接口 生产地址
];

return array_merge($conf, $payConfig);