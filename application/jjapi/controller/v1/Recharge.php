<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/15
 * Time: 19:44
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\WhUser;
use app\jjapi\service\Recharge as RechargeService;
use app\jjapi\service\Token;
use app\jjapi\service\WxNotify;
use app\jjapi\validate\MoneyRechagre;
use app\jjapi\validate\NotifyOrder;
use app\lib\exception\UserException;
use think\Loader;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

class Recharge extends BaseController
{
    public function rechargeOrder($money = '', $type = '')
    {
        $request = (new MoneyRechagre())->goCheck();
        $uid = Token::getCurrentUid();
        //检查有没有充值资格,
        $isMainUser = WhUser::where('id', '=', $uid)->value('is_main_user');
        if (!$isMainUser) {
            throw new UserException([
                'msg' => '该账号没有充值资格',
                'errorCode' => 30008
            ]);
        }
        //生成订单
        $order = RechargeService::createOrder($money, $type, $uid);

        return $this->jjreturn(['total' => $order->money]);

    }

    public function rechargeNotify($ordersn = '')
    {
        (new NotifyOrder())->goCheck();

        $order = RechargeService::checkOperate($ordersn);

        RechargeService::dealRechargeOrder($order);
    }


    public function wxpayNotify()
    {
        $config = new \WxPayConfig();
        $notify = new WxNotify();
        $notify->Handle($config);

    }
}