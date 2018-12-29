<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/29
 * Time: 9:05
 */

namespace app\jjapi\service;


use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Core\Config;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Profile\DefaultProfile;

class SendSms
{
    protected $accessKeyId;
    protected $accessKeyScret;
    protected $signName;
    protected $templateCode;
    protected $phone_number;
    protected $code;
    protected $acsClient = null;

    public function __construct($phone_number, $code, $templateCode = null)
    {
        Config::load();
        $this->accessKeyId = config('aliyun.sms_AKID');
        $this->accessKeyScret = config('aliyun.sms_AKS');
        $this->signName = config('aliyun.sms_SN');
        $this->code = $code;
        $this->templateCode = $templateCode;
        $this->phone_number = $phone_number;
    }

    private function getAcsClient()
    {
        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";
        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        $accessKeyId = $this->accessKeyId; // AccessKeyId

        $accessKeySecret = $this->accessKeyScret; // AccessKeySecret

        // 暂时不支持多Region
        $region = "cn-hangzhou";

        // 服务结点
        $endPointName = "cn-hangzhou";

        if($this->acsClient == null) {

            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

            // 增加服务结点
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);

            // 初始化AcsClient用于发起请求
            $this->acsClient = new DefaultAcsClient($profile);
        }
        return $this->acsClient;
    }

    public function sendSms() {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填，设置短信接收号码
        $request->setPhoneNumbers($this->phone_number);

        // 必填，设置签名名称，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $request->setSignName($this->signName);

        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $request->setTemplateCode($this->templateCode);

        // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
        $request->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "code" => $this->code,
        ), JSON_UNESCAPED_UNICODE));

        // 可选，设置流水号
        //$request->setOutId("yourOutId");

        // 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
        //$request->setSmsUpExtendCode("1234567");

        // 发起访问请求, 返回的是stdclass，对象。。。。。。不是数组啊。。。。。
        $acsResponse = $this->getAcsClient()->getAcsResponse($request);

        return $acsResponse;
    }


}