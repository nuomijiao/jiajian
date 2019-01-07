<?php

/**
 * 评估接口(房产)
 * Auther: wanggaoqi
 * Date: 2018/12/29
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;

class Assess extends Controller
{
    private $key = '58bcbead1920c664f67a08d66a4b242f';
    private $aes_key = 'FtZ4ELwzXuGA0euc';
    private $url = 'http://api.dispatchertest.visscaa.com/api/webapiaccess/jiajiandata/enquiry';

    /**
     * 获取询价结果
     */
    public function get_enquiry()
    {
        $postData = array_filter(Request::instance()->post());
        $baseData = [
            'CaseId'        => $this->get_caseid(),
            'Key'           => $this->key,
            'TypeCode'      => 5,                           // 精确查价
            'PropertyType'  => 92,                          // 二级物业 默认92
            'ThirdProperty' => 93,                          // 三级物业 默认93
            // 'AreaCode'      => '320506',                    // 国际码
            // 'ProjectName'   => '阳光城丽景湾',                   // 小区名称
            // 'Address'       => urlencode('启发广场'),     // 产证地址
            // 'BuildArea'     => 56,                          // 住宅面积
            // 'CurrentFloor'  => 12,                          // 所在楼层
            // 'TotalFloor'    => 31,                          // 总楼层
        ];

        $data = array_merge($postData, $baseData);
        
        $json = $this->apiDataPost($data);

        return json(json_decode($json, true));
    }

    /**
     * 行政区域
     */
    public function get_regions()
    {
        $postData = [
            'CaseId'   => $this->get_caseid(),
            'Key'      => $this->key,
            'TypeCode' => 14,
        ];

        $data = $this->apiDataPost($postData);
        $data = json_decode($data, true);
        $data = tree($data['DataList'], 'Code', 'ParentCode');

        return json($data);
    }

    /**
     * 评估通用接口方法
     */
    private function apiDataPost($params)
    {
        $public_key = openssl_pkey_get_public(file_get_contents(ROOT_PATH . 'rsakey/rsa_public_key.pem'));
        $private_key= openssl_pkey_get_private(file_get_contents(ROOT_PATH . 'rsakey/rsa_private_key.pem'));

        // 原文加密
        $encrypt_content = $this->encrypt(urldecode(json_encode($params)), $this->aes_key);
        // echo $encrypt_content;
        // 测试解密
        // echo $this->decrypt($encrypt_content, $this->aes_key);exit;

        // 密文加签
        $signature = $this->sign($encrypt_content, $private_key);
        // 测试签名
        // $cpublic_key = openssl_pkey_get_public(file_get_contents(ROOT_PATH . 'rsakey/c_public.pem'));
        // $verify_status = $this->verify($encrypt_content, $signature, $cpublic_key);
        // v($verify_status);

        //构造请求数据
        $postData = json_encode([
            'Data' => join(',', [$encrypt_content, $signature])
        ]);
        // var_dump($postData);

        //发送请求
        // $result = $this->post($this->url, $postData, 'json');
        $result = curlPost($this->url, $postData, 'json');
        // v($result);
        // echo $result;exit;

        //解析数据
        $res = explode(',', trim($result));
        // v($res);

        if(count($res) != 2)
        {       
            return json([
                'errcode' => 202,
                'errmsg'  => '接口返回数据有误'
            ]);
        }

        $result_encrpyt_content = $res[0];
        $result_signature = $res[1];

        //echo $result_encrpyt_content;
        //echo $result_signature;

        //验签
        $verify_status = $this->verify($result_encrpyt_content, $result_signature, $public_key);

        if($verify_status == 1)
        {
            // var_dump("验签成功");
            // 解密数据
            return $this->decrypt($result_encrpyt_content, $this->aes_key);
        }
        else
        {
            return returnCode(204, '验签失败');
        }
    }

    /**
     * 生成唯一单据id
     * todo 这里需要自己按照规则定义
     * @return string 
     */
    private function get_caseid()
    {
        return date('YmdHis') . str_replace('.', '', uniqid('', true));
    }

    /**
     * 加密
     * @param string $content 原文
     * @param string $key 密钥的字符串
     * @return string  加密后的字符串
     */
    private function encrypt($data, $key)
    {
        return openssl_encrypt($data, 'AES-128-ECB', $key);
    }
    
    /**
     * 解密
     * @param string $data 密文
     * @param string $key 密钥的字符串
     * @return string  解密后的字符串
     */
    private function decrypt($data, $key)
    {
        return openssl_decrypt($data, 'AES-128-ECB', $key);
    }

    /**
     * 加签
     * @param string $content 原文
     * @param string $private_key 私钥的字符串
     * @return string 签文的字符串
     */
    private function sign($content, $private_key)
    {
        $signature = '';
        openssl_sign($content, $signature, $private_key);
        return base64_encode($signature);
    }

    /**
     * 验签
     * @param string $content 原文
     * @param string $signature 签文
     * @param string $public_key 公钥的字符串
     * @return int 1 表示成功，其他表示失败
     */
    private function verify($content, $signature, $public_key)
    {
        return openssl_verify(trim($content, '"'), base64_decode(trim($signature,'"')), $public_key);
    }

     /**
     * 发送请求
     * @param string $url 请求地址
     * @param string $post_data 请求数据
     * @return mixed  返回的数据
     */
    private function post($url, $post_data)
    {
        $useragent = 'PHP5 Client 1.0 (curl) ' . phpversion();
        $ch = curl_init();
        $this_header = array("content-type: application/json; charset=UTF-8");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}