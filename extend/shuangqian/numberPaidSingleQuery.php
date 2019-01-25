<?php

/**
 * @Author: 卢盛杰
 * @Date:   2018-11-19 15:58:54
 * @Last Modified by:   卢盛杰
 * @Last Modified time: 2018-11-21 15:48:35
 */

	include("RSA.php");
	
    $merno = "168893"; //商户号
   
    $orderNo = "w1811201307011810180024";  //订单号

    $post_data = array(
        'merno' => $merno,
      
        'orderNo' => $orderNo,          
    );

    $beforeSignedData = joinMapValue($post_data);
    //echo "beforeSignedData:".$beforeSignedData;

    $rsa = new RSA();
    $signature = $rsa->sign($beforeSignedData);
    $post_data["signature"] = $signature;

    $action = "http://218.4.234.150:9600/merchant/numberPaidSingleQuery.action";
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

    $responseData = json_decode($ret);

    $response_joinMap = array(
    	"merno"=>$responseData->merno,
    	
    	"orderNo"=>$responseData->orderNo,
    	
		
		"searchResult"=>$responseData->searchResult
    );

    $responseBeforeSignedData = joinMapValue($response_joinMap);
    $verifySignature = $rsa->verify($responseBeforeSignedData,$responseData->signature);

    print_r($responseData);
	echo "verify:".$verifySignature;

    function joinMapValue($sign_params){
        $sign_str = "";
        //ksort($sign_params);
        foreach ($sign_params as $key => $val) {
            $sign_str .= sprintf("%s=%s&", $key, $val);                
        }
        return substr($sign_str, 0, -1);
    }

