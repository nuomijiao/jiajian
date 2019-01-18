<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 14:18
 */

namespace app\jjapi\service;


use app\jjapi\model\WhRechargeOrder;
use app\jjapi\service\Recharge as RechargeService;
use think\Loader;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

class WxNotify extends \WxPayNotify
{
    public function NotifyProcess($data, $config, &$msg)
    {
        $data = $data->values;
        if ($data['result_code'] == 'SUCCESS') {
            $orderNo = $data['out_trade_no'];

            $attach = $data['attach'];

            $order = WhRechargeOrder::getOrderByOrdersn($orderNo);

            RechargeService::dealRechargeOrder($order);
            return true;
        } else {
            return true;
        }
    }
}