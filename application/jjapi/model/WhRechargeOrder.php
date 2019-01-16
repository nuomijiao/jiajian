<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/16
 * Time: 10:48
 */

namespace app\jjapi\model;


class WhRechargeOrder extends BaseModel
{
    protected $autoWriteTimestamp = true;

    public static function getOrderByOrdersn($ordersn)
    {
        return self::where('order_sn','=', $ordersn)->find();
    }
}