<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/17
 * Time: 9:47
 */

class AliPayConfig
{
    protected $config = [
        'alipay_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCszzcP86t02+2k3IkpCgM9KE2QVKCXX0AeYYxlLyEQ7rWeCjRPH1bDJm+sNnyJfEOfYWxkrS0ICfl52o5nkWdOIC90CgSaTTX5durNRluOy4zyIJFtklt/ZD9DTnau7mBr3Tnb/uzMk3+pqzW+F/iVriFhlzpqSMX7U8I+pcdJMwIDAQAB',
        'sign_type' => 'RSA',
        'transport' => 'https',
        'partner' => '2088431169714534',
    ];

    public function getConfig()
    {
        return $this->config;
    }
}