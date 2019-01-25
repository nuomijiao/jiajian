<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/24
 * Time: 17:42
 */

require_once "RSA.php";

class Paid
{
    //发起时间
    protected $time = null;
    //开户名
    protected $open_bank_name = null;
    //开户行编号
    protected $shuangqian_bank_code = null;
    //卡号
    protected $bank_card_code = null;
    //卡类型
    protected $bank_card_type = null;
    //备注
    protected $remark = null;
    //签名对象
    protected $rsa = null;
    //订单
    protected $order = null;

    public function __construct($order)
    {
        $this->rsa = new RSA();
        $this->time = date('YmdHis');
        $this->open_bank_name = $order->open_bank_name;
        $this->shuangqian_bank_code = $order->shuangqian_bank_code;
        $this->bank_card_code = $order->bank_card_code;
        $this->bank_card_type = $order->bank_card_type;
        $this->remark = '';
        $this->order = $order;
    }

    public function singlePaid()
    {
        $postData = [
            "merno" => $this->rsa->getMerno(),
            "time" => $this->time,
            "content" => $this->open_bank_name."|".$this->shuangqian_bank_code."|".$this->bank_card_code."|".$this->bank_card_type."|".$this->order->money."|000|".$this->remark,
        ];

        $beforeSignedData = $this->joinMapValue($postData);
        $signature = $this->rsa->sign($beforeSignedData);
        $postData["remark"] = $this->remark;
        $postData["signature"] = $signature;

        $ret = $this->httpRequest($this->rsa->getAction(), $postData);

        $responseData = json_decode($ret);

        $response_joinMap = array(
            "merno"=>$responseData->merno,
            "time"=>$responseData->time,
            "content"=>$responseData->content,
            "status"=>$responseData->status,
            "remark"=>$responseData->remark,
        );

        $responseBeforeSignedData = $this->joinMapValue($response_joinMap);
        $verifySignature = $this->rsa->verify($responseBeforeSignedData,$responseData->signature);
        if ($verifySignature) {
            return $responseData->status;
        } else {
            return false;
        }
    }


    private function joinMapValue($sign_params){
        $sign_str = "";
        //ksort($sign_params);
        foreach ($sign_params as $key => $val) {
            $sign_str .= sprintf("%s=%s&", $key, $val);
        }
        return substr($sign_str, 0, -1);
    }

    private function httpRequest($url, $data = NULL)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}

