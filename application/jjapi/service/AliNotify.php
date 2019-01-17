<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 17:02
 */

namespace app\jjapi\service;

use app\jjapi\model\WhRechargeOrder;
use think\Loader;
use app\jjapi\service\Recharge as RechargeService;

Loader::import('AliPay.alipay_notify', EXTEND_PATH, '.class.php');

class AliNotify extends \AlipayNotify
{

    public function handle()
    {
        $sign = $this->RsaVerify($_POST, $this->alipay_config['alipay_public_key']);
        if ($sign) {
            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                $orderNo = $_POST['out_trade_no'];
                $order = WhRechargeOrder::getOrderByOrdersn($orderNo);
                file_put_contents('log.txt', $orderNo.PHP_EOL, FILE_APPEND);
                RechargeService::dealRechargeOrder($order);
            }
        } else {
            return 'success';
        }
    }
}