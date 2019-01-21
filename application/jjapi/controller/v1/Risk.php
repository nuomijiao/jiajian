<?php

/**
 * 风控接口(魔杖2.0和顶象技术)
 * Auther: wanggaoqi
 * Date: 2019.1.3
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use think\Db;
use app\jjapi\model\Auth;
use app\lib\exception\UserException;

class Risk extends Base
{

    // ==================================== 顶象技术 ==================================

    /**
     * 顶象接口
     */
    public function dingxiang()
    {
        $name   = Request::instance()->param('name');
        $idcard = Request::instance()->param('idcard');
        $mobile = Request::instance()->param('mobile');

        // 验证当前用户所在信息是否存在
        $data = Db::name('wh_user')
                ->where([
                    'id' => $this->uid
                ])
                ->find();

        if(empty($data))
        {
            return json([
                'errcode' => 202,
                'errmsg'  => '没有查询到当前用户相关信息'
            ]);
        }

        // 验证精英or企业
        if($data['degree'] == 1)    
        {
            // =================================== 精英 =====================================

            $table = 'wh_user';

            // 精英帐户 ID
            $account_id = $data['id'];
        }
        else    
        {
            // ==================================== 企业 ====================================

            $table = 'admin';

            // 企业帐户 ID
            $account_id = $data['company_id'];

            // 验证企业
            $data = Db::name('admin')
                ->where([
                    'id' => $account_id
                ])
                ->find(); 

            if(empty($data))
            {
                return json([
                    'errcode' => 202,
                    'errmsg'  => '没有查询到您所在的企业'
                ]);
            }
        }

        // 验证 企业帐户或精英帐户 余额
        if((float)$data['surplus'] < 9.9)
        {
            return json([
                'errcode' => 203,
                'errmsg'  => '余额不足'
            ]);
        }

        // ================================= 更新余额、查询记录入库 ============================

        // 事务启动
        Db::startTrans();

        try{
            // 更新余额
            Db::name($table)
                ->where('id', $account_id)
                ->setDec('surplus', 9.9);

            // 查询记录入库
            Db::name('wh_bigdata_order')->insert([
                'uid'    => $this->uid,
                'name'   => $name,
                'idcard' => $idcard,
                'mobile' => $mobile,
                'createtime' => time(),
                'company_id' => $account_id,    // 注：此ID 可能为企业ID 或 精英ID
            ]);

            // 事务提交
            Db::commit(); 
        } 
        catch (\Exception $e) 
        {
            // 事务回滚
            Db::rollback();

            return json([
                'errcode' => 204,
                'errmsg'  => '事务提交失败, 余额和订单异常'
            ]);
        }


        // ================================== 查询请求（顶象接口） ===================================

        $json = [
            'name'   => $name,
            'idcard' => $idcard,
            'mobile' => $mobile
        ];

        // 接口参数
        $timeStamp  = time();
        $customerId = 'd79ecff65925311772ede42d6f8fb294';
        $appsecert  = 'f3bdd4303c61754190afc5d9f24efbed';

        $sign = md5($appsecert . $customerId . $timeStamp . $appsecert);
        $headers = [
            'customerId:' . $customerId,
            'timeStamp:' . $timeStamp,
            'sign:' . $sign,
            'Content-type: application/json; charset=utf-8',
        ];

        // 接口地址
        $url = 'https://sec2.dingxiang-inc.com/api/dataplatform/dxantifraud';

        $result = $this->curlPost($url, $headers, $json);
        $data = substr($result, 192);
        $data = json_decode($data, true);

        return json($data);
    }

    /**
     * 接口请求curPost
     */
    public function curlPost($url, $headers, $json)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.81 Safari/537.36");
        //curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode(json_encode($json)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, (json_encode($json)));
        $data = curl_exec($ch);
        //var_dump(curl_getinfo($ch));
        // var_dump(curl_error($ch));
        curl_close($ch);
        return $data;
    }



















    // ==================================== 魔杖2.0 ==================================

    /*
    moxie.api.risk.magicwand2.anti-fraud',          // 反欺诈报告
    moxie.api.risk.magicwand2.application',         // 申请准入报告
    moxie.api.risk.magicwand2.credit.evaluation',   // 额度评估【账户】报告
    moxie.api.risk.magicwand2.credit.qualification',// 额度评估【电商】报告
    moxie.api.risk.magicwand2.post-load',           // 贷后行为报告
    */

    public function report()
    {
        $postData = Request::instance()->param();
        $postData = array_filter($postData);

        if(count($postData) !== 4)
        {
            return json([
                'code' => 202,
                'msg'  => '请填写正确的参数',
            ]);
        }

        $json = $this->getMoZhangContent($postData['name'], $postData['idcard'], $postData['mobile'], $postData['method']);

        // 所属企业ID
        $eData = Db::name('wh_user')
                ->where([
                    'id' => $this->uid
                ])
                ->find();

        $company_id = empty($eData) ? -1 : $eData['company_id'];

        // 写入查询记录
        Db::name('wh_bigdata_order')->insert([
            'uid'    => $this->uid,
            'name'   => $postData['name'],
            'idcard' => $postData['idcard'],
            'mobile' => $postData['mobile'],
            'method' => $postData['method'],
            'createtime' => time(),
            'company_id' => $company_id,
        ]);

        // 同步扣款业务逻辑...


        $data = json_decode($json, true);

        return json($data);
    }

    /**
     * 大数据查询订单
     */
    public function report_list()
    {
        // 状态(1.成功 2.异常)
        $status = Request::instance()->param('status', 1);

        // 显示记录数
        $size = Request::instance()->param('size', 10);

        // 页码
        $pageSize = Request::instance()->param('pageSize', 1);
        $pageSize = (int)$pageSize - 1;

        // 总记录数
        $num = Db::name('wh_bigdata_order')
            ->where([
                'uid'    => $this->uid,
                'status' => $status
            ])
            ->count();

        // 单页条数
        $data = Db::name('wh_bigdata_order')
            ->where([
                'uid'    => $this->uid,
                'status' => $status
            ])
            ->limit($pageSize * $size, $size)
            ->select();

        return json([
            'errcode' => 200,
            'errmsg'  => 'ok',
            'num'     => $num,
            'data'    => $data,
        ]);
    }

    /**
     * 通用接口方法
     */
    private function getMoZhangContent($name, $idcard, $mobile, $method)
    {
        $app_id = "dde8ddcf0fed432e8c079d710b31cb01";
        $format = "JSON";
        $sign_type = "RSA";
        $version = "1.0";
        $timestamp = $this->getMillionSeconds();
        $biz_content = '{"name":"'.$name.'","idcard":"'.$idcard.'","mobile":"'.$mobile.'"}';
        $paramsStr = "app_id={$app_id}&biz_content={$biz_content}&format={$format}&method={$method}&sign_type={$sign_type}&timestamp={$timestamp}&version={$version}";
        
        //rsa私钥字符串
        $secret = openssl_pkey_get_private(file_get_contents(ROOT_PATH . 'rsakey/mozhang_private_key.pem'));

        //获取签名
        $sign = $this->getSign($paramsStr,$secret);

        // v($sign);
        
        $url = 'https://api.51datakey.com/risk-gateway/api/gateway?'.$paramsStr."&sign=".$sign;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        $content = curl_exec($curl);
        
        if(curl_errno($curl))
        {
            $content =  curl_error($curl);
        }
        
        curl_close($curl);
        
        return $content;
    }
    
    /**
     * 获取SHA1签名
     */
    private function getSign($paramsStr, $secret)
    {
        $signature = "";
        // $str = chunk_split( $secret, 64, "\n");
        // $key = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
        openssl_sign($paramsStr, $signature, $secret);
        
        return base64_encode($signature);
    }
    
    /**
     * 获取毫秒级时间戳
     * @return number
     */
    private function getMillionSeconds()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        
        return $msectime ;
    }
}