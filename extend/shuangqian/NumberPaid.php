<?php
	include("RSA.php");

    $merno = "168893"; //商户号
    $time = "20170212154230"; //时间
    $totalAmount = "11.0"; //金额
    $num = "2"; //笔数
    $batchNo = "92017030111211789514967"; //批次号
    $content = "开户名1|ABC|6226131223214231|1|5.0|20170210000001|000|数字字母汉字".
                "#".
                "开户名2|ICBC|6226131223214541|2|5.0|20170210000002|000"; //内容

    $remark = "备注"; //备注

    $post_data  = array(
        "merno" => $merno,
        "time" => $time,        
        "totalAmount" => $totalAmount,
        "num" => $num,
        "batchNo" => $batchNo, 
        "content" => $content
    );
    

    $beforeSignedData = joinMapValue($post_data);
    print "beforeSignedData:".$beforeSignedData;
    print "";
    $rsa = new RSA();
    $signature = $rsa->sign($beforeSignedData);
   
    $post_data["remark"] = $remark;
    $post_data["signature"] = $signature;

    echo "signature：";
    print_r($signature);
    $action = "http://218.4.234.150:9600/merchant/numberPaid.action";
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
    //print_r($responseData);
    $response_joinMap = array(
    	"merno"=>$responseData->merno,
        "time"=>$responseData->time,
        "totalAmount"=>$responseData->totalAmount,
        "num"=>$responseData->num,
    	"batchNo"=>$responseData->batchNo,
        "content"=>$responseData->content,
    	"status"=>$responseData->status
    );

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


