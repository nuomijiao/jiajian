<?php  
/** 
 * RSA算法类 
 * 签名及密文编码：base64字符串/十六进制字符串/二进制字符串流 
 * 填充方式: PKCS1Padding（加解密）/NOPadding（解密） 
 * 
 * Notice:Only accepts a single block. Block size is equal to the RSA key size!  
 * 如密钥长度为1024 bit，则加密时数据需小于128字节，加上PKCS1Padding本身的11字节信息，所以明文需小于117字节 
 * 
 * @author: linvo 
 * @version: 1.0.0 
 * @date: 2013/1/23 
 */  
class RSA{
    private $pubKey = null;
    private $priKey = null;
    private $noresource_pubKey = null;
    private $noresource_priKey = null;

    private $merno = null;

    private $action = null;
  
    /** 
     * 自定义错误处理 
     */  
    private function _error($msg){  
        die('RSA Error:' . $msg); //TODO  
    }  
  
    /** 
     * 构造函数 
     * 
     * @param string 公钥（验签和加密时传入） 
     * @param string 私钥（签名和解密时传入） 
     */  
    public function __construct(){

        //商户私钥(商户168893) 用于请求数据签名、加密
        $private_key = "MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAKkhOAvYkcjZPx8mw6I8dyvx5kl3
aTk5F9uCF+OVkifH2z7mCHbRPMOrk+69hQts0o0RV5KHLlZQ6lZnTay5D2c23Ga7ep9N8jKRqIOH
ascxv7sLW5OiO1NEqxCTq587x42pgRy7zB9qvzdBRedrYphy7hVnV/aT2b6wo6HXG0MnAgMBAAEC
gYAkiDRe+qyiwapMxEbFqGHlcB7aB50G6zooA/W9BvXG+fh1oaJ6Z7/EVC9kBjPSv/LK3dAYqnJr
2paDi1TP1jlpK7NYg0E98GiYZYoOWr5u4nwoQav1KPbR1JIR1oM7+m9nkgUEk3JB1aeQmm0BlLmp
+UPji57WGj+pnpeaUQ4/oQJBAOVSFfrQypobP5P4YW90kvAldE27aWK7CizTU3sjxwmwTFSV34Ue
XArGjOvEpxkdVzvaLiQ5Rk3ciFFpT96KSV0CQQC8znPu/+PdBprGFsfyjKKgKghH7gVEa552K/et
mPF7vap701Iu0K2/bj6EIg5OKTYqvy6q4VDXbTEx/sVDkcJTAkEAoQoHI27SiGxQNpJ7ojCEK56x
0RCmTk45NAdnnZcfZF9pCxGAuVP7oRmTxtH/4nQnWYG7W3bZNz6CgGFrVEVahQJAdnLjAq6gqnpJ
QTrrh7w5DlgwR8gIn+sQR7y/rrYD0Yik2vgxV9NtHWqxZ73h0aFDLUAxq9ydFfmX4nCeGwznpwJB
ANvnGKiiZsdOs76gHxlAVL7pmSwwMxNg4nEaI/Wm49ARAhykaIrlPotTGszesoZJcAGfO66OwIu5
ssNWzfrI+KI=";

        //双乾公钥(测试环境) 用于响应数据的验签、解密
        $public_key = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCTcOG7ac4vmRvTMuYSWJhb/6nklzChouVKhEUA
eFhoR98xXniJuWXpYXw707vOa78yM35R4g8n3incIDFDYH+3/20DvbGB079+rycqm5tqM4LCW4SZ
T8yMD5WrgO4JN9K+mtTN6isEbNXeBp59XyquK1b5PWWXWPdvAJdnd85M4wIDAQAB";


        //开启防抵赖
        //商户私钥(需换成生产正式)由双乾提供
        /*
        $private_key = "";
        
        //双乾公钥(生产环境)
        $public_key = "";
        */
        
        
        $pemPriKey = chunk_split($private_key, 64, "\n");
        $pemPriKey = "-----BEGIN RSA PRIVATE KEY-----\n".$pemPriKey."-----END RSA PRIVATE KEY-----\n";
        
        $pemPubKey = chunk_split($public_key, 64, "\n");
        $pemPubKey = "-----BEGIN PUBLIC KEY-----\n".$pemPubKey."-----END PUBLIC KEY-----\n";
        
        //$this->priKey = openssl_get_privatekey($pemPriKey);
        //$this->pubKey = openssl_get_publickey($pemPubKey);
        
        $this->priKey = $pemPriKey;
        $this->pubKey = $pemPubKey;
        $this->merno = '203309';
        $this->action = "https://df.95epay.cn/merchant/numberPaidSingle.action";
    }
  
  
    /** 
     * 生成签名 
     * 
     * @param string 签名材料 
     * @param string 签名编码（base64/hex/bin） 
     * @return 签名值 
     */  
    public function sign($data, $code = 'base64'){  
        $ret = false;  
        if (openssl_sign($data, $ret, $this->priKey)){  
            $ret = $this->_encode($ret, $code);  
        }  
        return $ret;
    }  
  
    /** 
     * 验证签名 
     * 
     * @param string 签名材料 
     * @param string 签名值 
     * @param string 签名编码（base64/hex/bin） 
     * @return bool  
     */  
    public function verify($data, $sign, $code = 'base64'){
        $ret = false;
        $sign = $this->_decode($sign, $code);
        
        if ($sign !== false) {
            switch (openssl_verify($data, $sign, $this->pubKey)){
                case 1: $ret = true; break;
                case 0:
                case -1:
                default: $ret = false;
            }
        }
        
        return $ret;
    }
  
    /** 
     * 加密 
     * 
     * @param string 明文 
     * @param string 密文编码（base64/hex/bin） 
     * @param int 填充方式（貌似php有bug，所以目前仅支持OPENSSL_PKCS1_PADDING） 
     * @return string 密文 
     */  
    public function encrypt($data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING){  
        $ret = false;      
        if (!$this->_checkPadding($padding, 'en')) $this->_error('padding error');  
        if (openssl_public_encrypt($data, $result, $this->pubKey, $padding)){  
            $ret = $this->_encode($result, $code);  
        }  
        return $ret;  
    }  
  
    /** 
     * 解密 
     * 
     * @param string 密文 
     * @param string 密文编码（base64/hex/bin） 
     * @param int 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING） 
     * @param bool 是否翻转明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block） 
     * @return string 明文 
     */  
    public function decrypt($data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false){  
        $ret = false;  
        $data = $this->_decode($data, $code);  
        if (!$this->_checkPadding($padding, 'de')) $this->_error('padding error');  
        if ($data !== false){  
            if (openssl_private_decrypt($data, $result, $this->priKey, $padding)){  
                $ret = $rev ? rtrim(strrev($result), "\0") : ''.$result;  
            }   
        }  
        return $ret;  
    }
    
    /**
     * 生成密钥
     */
    public function GenerateKey($dn=NULL, $config=NULL, $passphrase=NULL){
        if(!$dn){
            $dn = array(
                "countryName" => "CN",
                "stateOrProvinceName" => "JIANGSU",
                "localityName" => "Suzhou",
                "organizationName" => "95epay",
                "organizationalUnitName" => "Moneymoremore",
                "commonName" => "www.moneymoremore.com",
                "emailAddress" => "csreason@95epay.com"
            );
        }
        /*
        if (!$config)
        {
            $config = array(
            "digest_alg" => "sha1",
            "private_key_bits" => 1024,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            "encrypt_key" => false
            );
        }
        */
        $privkey = openssl_pkey_new();
        echo "private key:";
        echo "<br>";
        if($passphrase != NULL)
        {
            openssl_pkey_export($privkey, $privatekey, $passphrase);
        }
        else
        {
            openssl_pkey_export($privkey, $privatekey);
        }
        echo $privatekey;
        echo "<br><br>";
        
        /*
        $csr = openssl_csr_new($dn, $privkey);
        $sscert = openssl_csr_sign($csr, null, $privkey, 65535);
        echo "CSR:";
        echo "<br>";
        openssl_csr_export($csr, $csrout);
        
        echo "Certificate: public key";
        echo "<br>";
        openssl_x509_export($sscert, $publickey);
        */
        $publickey = openssl_pkey_get_details($privkey);
        $publickey = $publickey["key"];
        
        echo "public key:";
        echo "<br>";
        echo $publickey;
        
        $this->noresource_pubKey=$publickey;
        $this->noresource_priKey=$privatekey;
    }
  
  
    // 私有方法  
  
    /** 
     * 检测填充类型 
     * 加密只支持PKCS1_PADDING 
     * 解密支持PKCS1_PADDING和NO_PADDING 
     *  
     * @param int 填充模式 
     * @param string 加密en/解密de 
     * @return bool 
     */  
    private function _checkPadding($padding, $type){  
        if ($type == 'en'){  
            switch ($padding){  
                case OPENSSL_PKCS1_PADDING:  
                    $ret = true;  
                    break;  
                default:  
                    $ret = false;  
            }  
        } else {  
            switch ($padding){  
                case OPENSSL_PKCS1_PADDING:  
                case OPENSSL_NO_PADDING:  
                    $ret = true;  
                    break;  
                default:  
                    $ret = false;  
            }  
        }  
        return $ret;  
    }  
  
    private function _encode($data, $code){  
        switch (strtolower($code)){  
            case 'base64':  
                $data = base64_encode(''.$data);  
                break;  
            case 'hex':  
                $data = bin2hex($data);  
                break;  
            case 'bin':  
            default:  
        }  
        return $data;  
    }  
  
    private function _decode($data, $code){  
        switch (strtolower($code)){  
            case 'base64':  
                $data = base64_decode($data);  
                break;  
            case 'hex':  
                $data = $this->_hex2bin($data);  
                break;  
            case 'bin':  
            default:  
        }  
        return $data;  
    }  
  
    private function _getPublicKey($file){
        $key_content = $this->_readFile($file);  
        if ($key_content){  
            $this->pubKey = openssl_get_publickey($key_content);  
        }  
    }  
  
    private function _getPrivateKey($file){  
        $key_content = $this->_readFile($file);  
        if ($key_content){  
            $this->priKey = openssl_get_privatekey($key_content);  
        }  
    }  
  
    private function _readFile($file){  
        $ret = false;  
        if (!file_exists($file)){  
            $this->_error("The file {$file} is not exists");  
        } else {  
            $ret = file_get_contents($file);  
        }  
        return $ret;  
    }  
  
  
    private function _hex2bin($hex = false){  
        $ret = $hex !== false && preg_match('/^[0-9a-fA-F]+$/i', $hex) ? pack("H*", $hex) : false;
        return $ret;
    }


    public function getMerno()
    {
        return $this->merno;
    }

    public function getAction()
    {
        return $this->action;
    }
  
}