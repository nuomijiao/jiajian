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
 * 无限级节点归类(替代递归)
 * @param list 		    [Y]	归类数组
 * @param id 			[N]	子级key，默认id
 * @param pid 			[N]	父级key，默认pid
 * @param child 		[N]	子级归类后的key，默认child
 * @param root 		    [N]	顶级
 */
function tree($list, $pk = 'id', $pid = 'pid', $child = 'child', $root = 0)
{  
    $tree = [];

    if(is_array($list)) 
    {  
        $refer = [];

        //基于数组的指针(引用) 并 同步改变数组
        foreach ($list as $key => $val) 
        {  
            $refer[$val[$pk]] = &$list[$key];
        }

        foreach ($list as $key => $val)
        {  
            //判断是否存在parent  
            $parentId = $val[$pid];

            if ($root == $parentId) 
            {  
                $tree[$val[$pk]] = &$list[$key]; 
            }
            else
            {  
                if (isset($refer[$parentId]))
                {  
                    $refer[$parentId][$child][] = &$list[$key];  
                }  
            }
        } 
    }

    return $tree;  
}

/**
 * 驼峰命名转下划线命名
 * @param $str  字符串
 */
function toUnderScore($str)
{
    $dstr = preg_replace_callback('/([A-Z]+)/',function($matchs)
    {
        return '_'.strtolower($matchs[0]);

    },$str);

    return trim(preg_replace('/_{2,}/','_', $dstr), '_');
}

/**
 * sendSmsCode 验证码
 * @param $phone    手机号
 * @param $content  内容
 */
function sendSmsCode($phone, $content)
{
    // 代码标题
    $statusStr = [
        "0" => "短信发送成功[短信宝]",
        "-1" => "参数不全[短信宝]",
        "-2" => "短信宝服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！[短信宝]",
        "30" => "密码错误[短信宝]",
        "40" => "账号不存在[短信宝]",
        "41" => "余额不足[短信宝]",
        "42" => "帐户已过期[短信宝]",
        "43" => "IP地址限制[短信宝]",
        "50" => "内容含有敏感词[短信宝]"
    ];

    $smsData = [
        'u' => 'jiajian',               // 帐户
        'p' => md5('jiajian2019'),       // 密码
        'm' => $phone,                  // 手机号
        'c' => $content,                // 内容
    ];

    $param  = http_build_query($smsData);
    $smsApi = 'http://api.smsbao.com/sms?' . $param;
    $result = file_get_contents($smsApi);

    return $result == 0 ? 'ok' : $statusStr[$result];
}