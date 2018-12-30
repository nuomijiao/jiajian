<?php

/**
 * 评估接口(房产、车辆)
 * Auther: wanggaoqi
 * Date: 2018/12/29
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;

class Assess extends Controller
{
    private $key = 'a1b20c2a3f10c235962408d37e0206d5';
    private $aes_key = 'slpgenquirysvcxx';
    private $url = 'http://172.16.2.1:8077/api/webapiaccess/PublicTestEnquiry/Enquiry';

    /**
     * 获取询价结果
     */
    public function get_enquiry()
    {
        $public_key = openssl_pkey_get_public(file_get_contents(ROOT_PATH . 'rsakey/rsa_public_key.pem'));
        $private_key= openssl_pkey_get_private(file_get_contents(ROOT_PATH . 'rsakey/rsa_private_key.pem'));

        $params = [
            'CaseId'        => $this->get_caseid(),
            'Key'           => $this->key,
            'TypeCode'      => '2',
            'AreaCode'      => '410202',
            'PropertyType'  => 92,
            'ThirdProperty' => 93,
            'ProjectName'   => '香樟公寓',
            'Address'       => '香樟公寓2期2栋',
            'BuildArea'     => 120,
        ];

        //原文加密
        $encrypt_content = $this->encrypt(json_encode($params), $this->aes_key);

        //密文加签
        $signature = $this->sign($encrypt_content, $private_key);

        //构造请求数据
        $postData = json_encode([
            'Data' => join(',', [$encrypt_content, $signature])
        ]);

        // v($postData);

        //发送请求
        $result = curlPost($this->url, $postData, 'json');

        //解析数据
        $res = explode(",", trim($result));

        v($result);

        if(count($res) != 2 )
        {            
            echo "返回数据不对\n";
            echo $result;
            return;
        }

        $result_encrpyt_content = isset($res[0]) ? $res[0] : '';
        $result_signature = isset($res[1]) ? $res[1] : '';

        //echo $result_encrpyt_content;
        //echo $result_signature;

        //验签
        $verify_status = $this->verify($result_encrpyt_content, $result_signature, $public_key);
        if($verify_status == 1)
        {
            echo "验签成功\n";
            //解密数据
            echo $this->decrypt($result_encrpyt_content,$this->aes_key);
        }
        else
        {
            echo "验签失败\n";
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
        return openssl_encrypt($data , 'AES-128-ECB',$key);
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
}