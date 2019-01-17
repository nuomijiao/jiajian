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


class AliNotify
{

    protected $alipay_config;

    public function __construct()
    {
        $this->alipay_config = [
            'alipay_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCszzcP86t02+2k3IkpCgM9KE2QVKCXX0AeYYxlLyEQ7rWeCjRPH1bDJm+sNnyJfEOfYWxkrS0ICfl52o5nkWdOIC90CgSaTTX5durNRluOy4zyIJFtklt/ZD9DTnau7mBr3Tnb/uzMk3+pqzW+F/iVriFhlzpqSMX7U8I+pcdJMwIDAQAB',
            'sign_type' => 'RSA',
            'transport' => 'https',
            'partner' => '2088431169714534',
        ];
    }

    public function handle()
    {
        $sign = $this->RsaVerify($_POST, $this->alipay_config['alipay_public_key']);
        if ($sign) {
            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                $orderNo = $_POST['out_trade_no'];
                $order = WhRechargeOrder::getOrderByOrdersn($orderNo);
                RechargeService::dealRechargeOrder($order);
            }
        } else {
            return 'success';
        }
    }


    public function RSAVerify($return_data, $public_key, $ksort = true)
    {
        if (empty($return_data) || !is_array($return_data)) {
            return false;
        }
        file_put_contents('log.txt', var_export($return_data, true).PHP_EOL, FILE_APPEND);
        $public_key = $this->chackKey($public_key);
        $pkeyid = openssl_pkey_get_public($public_key);
        if (empty($pkeyid)) {
            return false;
        }

        $sign_types = $return_data['sign_type'];

        $rsasign = $return_data['sign'];
        unset($return_data['sign']);
        unset($return_data['sign_type']);

        if ($ksort) {
            ksort($return_data);
        }

        if (is_array($return_data) && !empty($return_data)) {
            $strdata = '';

            foreach ($return_data as $k => $v) {
                if (empty($v)) {
                    continue;
                }

                if (is_array($v)) {
                    $strdata .= $k . '=' . json_encode($v) . '&';
                }
                else {
                    $strdata .= $k . '=' . $v . '&';
                }
            }
        }
        $strdata = trim($strdata, '&');
        file_put_contents('log2.txt', $strdata.PHP_EOL, FILE_APPEND);
        $rsasign = str_replace(' ', '+', $rsasign);
        $rsasign = base64_decode($rsasign);
        file_put_contents('log3.txt', $rsasign.PHP_EOL, FILE_APPEND);
        if($sign_types == "RSA2"){
            $rsaverify = openssl_verify($strdata, $rsasign, $pkeyid, OPENSSL_ALGO_SHA256);
        }else{
            $rsaverify = openssl_verify($strdata, $rsasign, $pkeyid);
        }
        openssl_free_key($pkeyid);

        return $rsaverify;
    }



    public function chackKey($key, $public = true)
    {
        if (empty($key)) {
            return $key;
        }

        if ($public) {
            if ($this->strexists($key, '-----BEGIN PUBLIC KEY-----')) {
                $key = str_replace(array('-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'), '', $key);
            }

            $head_end = "-----BEGIN PUBLIC KEY-----\n{key}\n-----END PUBLIC KEY-----";
        }
        else {
            if ($this->strexists($key, '-----BEGIN RSA PRIVATE KEY-----')) {
                $key = str_replace(array('-----BEGIN RSA PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----'), '', $key);
            }

            $head_end = "-----BEGIN RSA PRIVATE KEY-----\n{key}\n-----END RSA PRIVATE KEY-----";
        }

        $key = str_replace(array("\r\n", "\r", "\n"), '', trim($key));
        $key = wordwrap($key, 64, "\n", true);
        return str_replace('{key}', $key, $head_end);
    }



    public function strexists($string, $find) {
        return !(strpos($string, $find) === FALSE);
    }


}