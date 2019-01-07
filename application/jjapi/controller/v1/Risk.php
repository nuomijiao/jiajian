<?php

/**
 * 风控接口(魔杖2.0开放平台)
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
    /**
     * 接口method方法列表
     */

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
        $eData = Db::name('user')
                ->where([
                    'id' => $this->uid
                ])
                ->find();

        $company_id = empty($eData) ? -1 : $eData['company_id'];

        // 写入查询记录
        Db::name('bigdata_order')->insert([
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
        $num = Db::name('bigdata_order')
            ->where([
                'uid'    => $this->uid,
                'status' => $status
            ])
            ->count();

        // 单页条数
        $data = Db::name('bigdata_order')
            ->where([
                'uid'    => $this->uid,
                'status' => $status
            ])
            ->limit($pageSize, $size)
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