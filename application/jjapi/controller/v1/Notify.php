<?php
/**
 * 异步通知接口
 * Auther: wanggaoqi
 * Date: 2018/1/21
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use think\Db;

class Notify extends Controller{

    // ========================================= 双乾支付 ======================================

    /**
     * 双乾支付协议异步通知接口 (此处应做签名加密验证，后续处理)
     */
    public function epay()
    {
        $postData = Request::instance()->post();

        if(isset($postData['BillNo']))
        {
            // 查询订单
            $result = Db::name('wh_pay_order')
                    ->where([
                        'order_no' => $postData['BillNo'],
                    ])
                    ->find();
            
            if(empty($result))
            {
                // 订单不存在 日志业务逻辑...

                return false;
            }

            // 查询企业识别码
            $userinfo = Db::name('wh_user')
                    ->where('id', $result['uid'])
                    ->find();

            if(empty($userinfo))
            {
                // 企业不存在 异常日志业务逻辑...
                return false;
            }

            // 启动事务
            Db::startTrans();

            try{
                // 更新支付订单状态 1为成功
                Db::name('wh_pay_order')
                    ->where([
                        'order_no' => $postData['BillNo'],
                    ])
                    ->update([
                        'status' => 1
                    ]);

                // 余额同步加
                Db::name('admin')
                    ->where([
                        'id' => $userinfo['company_id'],
                        'id_code' => $userinfo['company_code']
                    ])
                    ->setInc('pay_surplus', (int)$result['price'] / 100);

                // 事务提交
                Db::commit(); 
            } 
            catch (\Exception $e) 
            {
                // 事务回滚
                Db::rollback();

                // 更新失败 日志处理...
            }
        }
    }


    /**
     * 双乾支付代扣异步通知接口
     */
    public function agent()
    {
        $postData = Request::instance()->post();

        if(isset($postData['BillNo']))
        {
            // 查询订单
            $result = Db::name('wh_pay_order')
                    ->where([
                        'order_no' => $postData['BillNo'],
                    ])
                    ->find();
            
            if(empty($result))
            {
                // 订单不存在 日志业务逻辑...

                return false;
            }

            // 签名校验
            $authMsgData = [
                'Amount' => $postData['Amount'],
                'BillNo' => $postData['BillNo'],
                'MerNo'  => $result['mer_no'],
                'Succeed'=> $postData['Succeed'],
            ];

            $joinMapValue = joinMapValue($authMsgData);
            $strBeforeMd5 = $joinMapValue . strtoupper(md5(12345678));
            $MD5Info = strtoupper(md5($strBeforeMd5));

            if($postData['MD5info'] != $MD5Info)
            {
                // 签名校验失败 此处应做日志处理...
                return false;
            }

            // 查询企业识别码
            $userinfo = Db::name('wh_user')
                    ->where('id', $result['uid'])
                    ->find();

            if(empty($userinfo))
            {
                // 企业不存在 异常日志业务逻辑...
                return false;
            }

            // 启动事务
            Db::startTrans();

            try{
                // 更新支付订单状态 1为成功
                Db::name('wh_pay_order')
                    ->where([
                        'order_no' => $postData['BillNo'],
                    ])
                    ->update([
                        'status' => 1
                    ]);

                // 余额同步加
                Db::name('admin')
                    ->where([
                        'id' => $userinfo['company_id'],
                        'id_code' => $userinfo['company_code']
                    ])
                    ->setInc('pay_surplus', (int)$result['price'] / 100);

                // 事务提交
                Db::commit();
                
                return 'SUCCESS';
            } 
            catch (\Exception $e) 
            {
                // 事务回滚
                Db::rollback();

                // 更新失败 日志处理...
            }
        }
    }


    // ========================================= 上上签协议签约 ======================================

    /**
     * 上上签协议签约通知接口 (此处应做签名加密验证，后续处理)
     */
    public function protocol()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // 写入测试表
        Db::name('wh_ceshi')->insert([
            'data' => $json
        ]);

        if(isset($data['params']['sid']))
        {
            $bool = Db::name('wh_auth')
                ->where([
                    'sid' => $data['params']['sid'],
                ])
                ->update([
                    'status' => 1   // 更新为1,签约成功
                ]);
            
            if(!$bool)
            {
                // 日志处理...
            }
        }
    }


    // ========================================= 上上签电子合同 ======================================

    /**
     * 上上签电子合同通知接口 (此处应做签名加密验证，后续处理)
     */
    public function contract()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if(isset($data['params']['sid']))
        {
            Db::name('wh_contract')
                ->where([
                    'sid' => $data['params']['sid'],
                ])
                ->update([
                    'status' => 1   // 更新为1,签约成功
                ]);
        }
    }
}