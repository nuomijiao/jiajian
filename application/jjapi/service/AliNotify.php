<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 17:02
 */

namespace app\jjapi\service;

use app\jjapi\model\WhRechargeOrder;

use app\jjapi\service\Recharge as RechargeService;
use think\Loader;


Loader::import('AliPay.alipay_notify', EXTEND_PATH, '.class.php');

class AliNotify extends \AlipayNotify
{

    public function handle()
    {

        $sign = $this->verifyNotify();
        if ($sign) {
            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                $orderNo = $_POST['out_trade_no'];
                $order = WhRechargeOrder::getOrderByOrdersn($orderNo);
                RechargeService::dealRechargeOrder($order);
            }
            echo 'success';
        } else {
            echo 'success';
        }
    }

}