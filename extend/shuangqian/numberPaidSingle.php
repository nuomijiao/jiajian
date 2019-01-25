<?php
	include("RSA.php");

    $merno = "202697"; //商户号
    $time = "20181119170118"; //时间
    $content = "开户名1|ABC|6226131223214231|1|5.0|20170210000001|000|数字字母汉字"; //内容
    $remark = "备注"; //备注

    $post_data  = array(
        "merno" => $merno,
        "time" => $time,         
        "content" => $content
    );
    

    $beforeSignedData = joinMapValue($post_data);
    print "beforeSignedData:".$beforeSignedData;
    
    $rsa = new RSA();
    $signature = $rsa->sign($beforeSignedData);
   
    $post_data["remark"] = $remark;
    $post_data["signature"] = $signature;
    print_r($beforeSignedData);
    print_r($signature);

    $action = "https://df.95epay.cn/merchant/numberPaidSingle.action";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    curl_setopt($ch, CURLOPT_POST, TRUE); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
    curl_setopt($ch, CURLOPT_URL, $action);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);

    $ret = curl_exec($ch);
    curl_close($ch);

    print_r(json_decode($ret));
    $responseData = json_decode($ret);
    print_r($responseData);

    $response_joinMap = array(
    	"merno"=>$responseData->merno,
        "time"=>$responseData->time,
        "content"=>$responseData->content,
        "status"=>$responseData->status,
        "remark"=>$responseData->remark,
     
    
    );

    print_r($response_joinMap);

    $responseBeforeSignedData = joinMapValue($response_joinMap);
    $verifySignature = $rsa->verify($responseBeforeSignedData,$responseData->signature);

    echo "verify:".$verifySignature;


    function joinMapValue($sign_params){
        $sign_str = "";
        //ksort($sign_params);
        foreach ($sign_params as $key => $val) {
            $sign_str .= sprintf("%s=%s&", $key, $val);                
        }
        return substr($sign_str, 0, -1);
    }
