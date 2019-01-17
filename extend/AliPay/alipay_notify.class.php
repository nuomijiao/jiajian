<?php
/* *
 * 类名：AlipayNotify
 * 功能：支付宝通知处理类
 * 详细：处理支付宝各接口通知返回
 * 版本：3.2
 * 日期：2011-03-25
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考

 *************************注意*************************
 * 调试通知返回时，可查看或改写log日志的写入TXT里的数据，来检查通知返回是否正常
 */

require_once("alipay_core.function.php");
require_once("alipay_rsa.function.php");
require_once("alipay_config.class.php");

class AlipayNotify {
    /**
     * HTTPS形式消息验证地址
     */
	var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	/**
     * HTTP形式消息验证地址
     */
	var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
	var $alipay_config;

	function __construct(){
		$config = new AliPayConfig();
		$this->alipay_config = $config->getConfig();
	}
    function AlipayNotify() {
    	$this->__construct();
    }
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyNotify(){

		if(empty($_POST) || !is_array($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {

			//生成签名结果
			$isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
//			$responseTxt = 'false';
//			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}
			
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_POST);
			//logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
//			if (preg_match("/true$/i",$responseTxt) && $isSign) {
//				return true;
//			} else {
//				return false;
//			}
            if ($isSign) {
                return true;
            } else {
                return false;
            }
		}
	}
	
    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyReturn(){
		if(empty($_GET)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'false';
			if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}
			
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n return_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_GET);
			//logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}

	function RsaVerify($return_data, $public_key, $ksort = true) {
        if (empty($return_data) || !is_array($return_data)) {
            return false;
        }
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
        $rsasign = str_replace(' ', '+', $rsasign);
        $rsasign = base64_decode($rsasign);
        if($sign_types == "RSA2"){
            $rsaverify = openssl_verify($strdata, $rsasign, $pkeyid, OPENSSL_ALGO_SHA256);
        }else{
            $rsaverify = openssl_verify($strdata, $rsasign, $pkeyid);
        }
        openssl_free_key($pkeyid);

        return $rsaverify;
	}


    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
	function getSignVeryfy($para_temp, $sign) {


		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);
		//对待签名参数数组排序
		$para_sort = argSort($para_filter);
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = createLinkstring($para_sort);
		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "RSA" :
				$isSgin = rsaVerify($prestr, trim($this->alipay_config['alipay_public_key']), $sign);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}

//	add By JIao

	function chackKey($key, $public = true)
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

	function strexists($string, $find)
	{
        return !(strpos($string, $find) === FALSE);
	}

//	add By Jiao

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	function getResponse($notify_id) {
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = $this->https_verify_url;
		}
		else {
			$veryfy_url = $this->http_verify_url;
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);
		
		return $responseTxt;
	}
}
?>
