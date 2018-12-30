<?php
/**
 * 公共函数文件
 * Date: 2018/12/28
 */

function v($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die;
}

/**
 * 字典序拼接
 */
function joinMapValue($sign_params)
{
    $sign_str = "";

    foreach ($sign_params as $key => $val) 
    {
        $sign_str .= sprintf("%s=%s&", $key, $val);
    }

    return substr($sign_str, 0);
}

/** 
 * returnCode
 * @param errcode
 * @param errmsg
 */
function returnCode($errcode, $errmsg)
{
    $data = [
        'errcode'   => $errcode,
        'errmsg'    => urlencode($errmsg)
    ];

    $json = urldecode(json_encode($data));

    return $json;
}

/**
 * Curl Get
 * @param url
 */
function curlGet($url)
{
    $ch = curl_init();  
    curl_setopt($ch,CURLOPT_URL,$url);  
    curl_setopt($ch,CURLOPT_HEADER,0);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );  
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $res = curl_exec($ch);
    curl_close($ch); 
    $result = trim($res, "\xEF\xBB\xBF");
    return $result; 
}

/** 
 * Curl Post请求
 * @param url
 * @param data
 * @param method
 */
function curlPost($url, $data = '', $dataType = '')
{
    $dataTypeArr = [
        'form' => ['content-type: application/x-www-form-urlencoded;charset=UTF-8'],
        'json' => ['Content-Type: application/json;charset=utf-8'],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    if(!empty($dataType))
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $dataTypeArr[$dataType]);
    }
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);
    $result = trim($result, "\xEF\xBB\xBF");
    return $result;
}

/** 
 * encrypt
 * @param string 			[Y]	解密字符串
 * @param operation 		[Y]	加密解密关键字
 * @param key				[Y]	加密解密令牌
 */
function encrypt($string, $operation, $key)
{ 
    $key = md5($key); 
    $key_length = strlen($key); 
    $string = $operation == 'D' ? base64_decode($string) : substr(md5($string.$key), 0, 8) . $string; 
    $string_length = strlen($string); 
    $rndkey = $box = array(); 
    $result = '';

    for($i = 0; $i <= 255; $i++)
    { 
        $rndkey[$i] = ord($key[$i % $key_length]); 
        $box[$i] = $i; 
    } 

    for($j = $i = 0; $i < 256; $i++)
    { 
        $j = ($j + $box[$i] + $rndkey[$i]) % 256; 
        $tmp = $box[$i]; 
        $box[$i] = $box[$j]; 
        $box[$j] = $tmp; 
    } 

    for($a = $j = $i = 0; $i < $string_length; $i++)
    { 
        $a = ($a+1) % 256; 
        $j = ($j+$box[$a]) % 256; 
        $tmp = $box[$a]; 
        $box[$a] = $box[$j]; 
        $box[$j] = $tmp; 
        $result .= chr(ord($string[$i])^($box[($box[$a] + $box[$j]) % 256])); 
    } 

    if($operation == 'D')
    { 
        if(substr($result,0,8) == substr(md5(substr($result,8).$key), 0, 8))
        { 
            return substr($result, 8); 
        }
        else
        { 
            return ''; 
        } 
    }
    else
    { 
        return str_replace('=', '', base64_encode($result)); 
    } 
}