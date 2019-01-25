<?php
	include("RSA.php");
	
    $merno = "168893"; //商户号
    $batchNo = "92017030111211789514967";  //批次号
    $orderNo = "";  //订单号

    $post_data = array(
        'merno' => $merno,
        'batchNo' => $batchNo, 
        'orderNo' => $orderNo,          
    );

    $beforeSignedData = joinMapValue($post_data);
    //echo "beforeSignedData:".$beforeSignedData;

    $rsa = new RSA();
    $signature = $rsa->sign($beforeSignedData);
    $post_data["signature"] = $signature;

    $action = "http://218.4.234.150:9600/tradesystem/merchant/numberPaidQuery.action";
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
    	"batchNo"=>$responseData->batchNo,
    	"orderNo"=>$responseData->orderNo,
    	"state"=>$responseData->state,
		"num"=>$responseData->num,
		"totalAmount"=>$responseData->totalAmount,
		"poundage"=>$responseData->poundage,
		"contents"=>json_encode($responseData->contents,JSON_UNESCAPED_UNICODE),
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


