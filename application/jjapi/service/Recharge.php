<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 11:22
 */

namespace app\jjapi\service;


use app\jjapi\model\Admin;
use app\jjapi\model\WhBalanceDetail;
use app\jjapi\model\WhRechargeOrder;
use app\jjapi\model\WhUser;
use app\lib\enum\BalanceChangeTypeEnum;
use app\lib\enum\OrderStatusEnum;
use app\lib\enum\RoleEnum;
use app\lib\enum\UserDegreeEnum;
use app\lib\exception\OrderException;
use app\lib\exception\ParameterException;
use think\Db;
use think\Exception;

class Recharge
{
    public static function createOrder($money, $type, $uid)
    {
        $data = [
            'user_id' => $uid,
            'money' => $money,
            'status' => OrderStatusEnum::Unpaid,
            'pay_way' => $type,
            'order_sn' => self::makeOrderNo(),
        ];
        $order = WhRechargeOrder::create($data);
        return $order;
    }

    public static function dealRechargeOrder($order)
    {
        Db::startTrans();
        try {
            //更新订单状态
            WhRechargeOrder::update([
                'id' => $order->id,
                'pay_time' => time(),
                'status' => OrderStatusEnum::Paid,
            ]);

            //增加余额（企业账户后台账号和精英账户前台账号）
            //检查账号身份
            //添加明细
            $userInfo = WhUser::get($order->user_id);
            if ($userInfo['degree'] == UserDegreeEnum::JingYing && $userInfo['is_main_user'] == 1) {
                WhUser::update([
                    'id' => $userInfo->id,
                    'surplus' => $userInfo->surplus + $order->money,
                ]);
                WhBalanceDetail::create([
                    'user_id' => $userInfo->id,
                    'change_money' => $order->money,
                    'change_type' => BalanceChangeTypeEnum::Recharge,
                    'order_sn' => $order->order_sn,
                    'type' => UserDegreeEnum::JingYing,
                ]);
            } elseif ($userInfo['degree'] == UserDegreeEnum::QiYe && $userInfo['is_main_user'] == 1) {
                $adminInfo = Admin::getCompanyByUserId($order->user_id);
                Admin::update([
                    'id' => $adminInfo->id,
                    'surplus' => $adminInfo->surplus + $order->money,
                ]);
                WhBalanceDetail::create([
                    'user_id' => $userInfo->id,
                    'change_money' => $order->money,
                    'change_type' => BalanceChangeTypeEnum::Recharge,
                    'order_sn' => $order->order_sn,
                    'type' => UserDegreeEnum::QiYe,
                    'company_id' => $adminInfo->id,
                ]);
            }
            Db::commit();

        } catch(Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public static function checkOperate($ordersn = '')
    {
        $order = WhRechargeOrder::getOrderByOrdersn($ordersn);
        if (!$order) {
            throw new OrderException();
        }
        $userId = Token::isValidOperate($order->user_id);
        if (!$userId) {
            throw new ParameterException([
                'msg' => '不能操作他人订单',
                'errorCode' => 10003,
            ]);
        }
        return $order;
    }



    private static function makeOrderNo()
    {
        $yCode = array('A', 'B', 'C', 'E', 'F','H', 'K', 'M', 'N', 'R');

        $orderSn = $yCode[intval(date('Y')) - 2018] . strtoupper(dechex(date('m'))) . date(
                'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
                '%02d', rand(0, 99));
        return $orderSn;
    }
}