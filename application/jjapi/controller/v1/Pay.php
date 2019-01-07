<?php

/**
 * 双乾支付 支付、签约、回调统一接口
 * Auther: wanggaoqi
 * Date: 2018/12/28
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use think\Db;
use app\jjapi\model\Auth;
use app\lib\exception\UserException;

// 生产环境下 此处应验证token等 是否登录
class Pay extends Base
{
    private $merno  = '168885';     // 商户号
    private $MD5key = "12345678";   // MD5key

    /**
     * 签约列表
     */
    public function auth_list()
    {
        // 显示记录数
        $size = Request::instance()->param('size', 10);

        // 页码
        $pageSize = Request::instance()->param('pageSize', 1);
        $pageSize = (int)$pageSize - 1;

        // 总记录数
        $num = Db::name('auth')
            ->where([
                'status' => 1
            ])
            ->count();

        // 单页条数
        $data = Db::name('auth')
            ->where([
                'status' => 1
            ])
            ->limit($pageSize, $size)
            ->select();

        return json([
            'errcode' => 200,
            'errmsg'  => 'ok',
            'num'     => $num,
            'data'    => $data,
        ]);
    }

    /**
     * 双乾快捷支付 认证/签约接口
     */
    public function auth()
    {  
        // $_POST = [
        //     'custName' => '汪高启',
        //     'phoneNo'  => '13285177013',
        //     'cardNo'   => '6227002006600280380',
        //     'bankCode' => '建设银行',
        //     'idNo'     => '342221199110018230',
        //     'authMsg'  => '761469',
        //     'custType' => '02',

        //     'stages'   => 1,
        //     'payMoney' => 100,
        //     'paths'    => '1.jpg',
        // ];

        $postData   = Request::instance()->post();
        $type       = @$postData['type'];
        $stages     = @$postData['stages'];                                     // 分期数
        $payMoney   = @$postData['payMoney'];                                   // 预计捐款金
        $paths      = @$postData['paths'];                                      // 身份证等图片路径

        // 银行代码 (此处业务逻辑应为前端处理，闹心)
        $bankCodes = [
            'ICBC'  => '工商银行',
            'ABC'   => '农业银行',
            'BOC'   => '中国银行',
            'CCB'   => '建设银行',
            'BOCOM' => '交通银行',
            'PSBC'  => '邮储银行',
            'CEB'   => '光大银行',
            'CITIC' => '中信银行',
            'HXB'   => '华夏银行',
            'CMBC'  => '民生银行',
            'GDB'   => '广发银行',
            'CMB'   => '招商银行',
            'CIB'   => '兴业银行',
            'SPDB'  => '浦发银行',
            'PAB'   => '平安银行',
            'EBCL'  => '恒丰银行',
            'CZBANK'=> '浙商银行',
            'BCCB'  => '北京银行',
            'BOS'   => '上海银行',
        ];

        // 银行代码
        $bankCode = array_search(@$postData['bankCode'], $bankCodes);
        
        // 认证时为短信验证码
        $authMsg = Request::instance()->post('authMsg', '123456');

        // 商户请求流水号(随机)
        $reqMsgId = mt_rand(10000, 99999) . time();

        // 签约/认证数据
        $postData = [
            'merNo'     => $this->merno,                                        // 商户号
            'custName'  => @$postData['custName'],                               // 姓名
            'phoneNo'   => @$postData['phoneNo'],                                // 手机号
            'cardNo'    => @$postData['cardNo'],                                 // 银行卡号
            'bankCode'  => @$bankCode,                                          // 银行代码
            'idNo'      => @$postData['idNo'],                                   // 身份证
            'idType'    => 0,                                                   // 证件类型（0.身份证）
            'reqMsgId'  => $reqMsgId,                                           // 商户请求流水号
            'cardType'  => 1,                                                    // 卡类型（1.借记卡 2.贷记卡）
            'authMsg'   => @$authMsg,                                            // 授权信息:签约交易必填，认证成功返回
            'custType'  => @$postData['custType'],                               // 01：认证，02：签约
            'payType'   => 'XYPAY',                                             // 校验渠道 固定：XYPAY
        ];

        // 字典序
        ksort($postData);

        // 签名拼接加密
        $joinMapValue = joinMapValue($postData);
        $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
        $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

        // 请求
        $result = curlPost(Config::get('auth_url'), $postData);
        $result = json_decode($result, true);

        // 如果为签约则写入数据库
        if($result['respCode'] === 'success' && $postData['custType'] === '02')
        {
            // 无需处理成功或失败
            // 赋上上签流水号
            $sid = $reqMsgId;

            Auth::insert([
                'uid'       => $this->uid,
                'type'      => $type,
                'mer_no'    => $this->merno,
                'cust_name' => $postData['custName'],
                'phone'     => $postData['phoneNo'],
                'card_no'   => $postData['cardNo'],
                'bank_code' => $postData['bankCode'],
                'id_no'     => $postData['idNo'],
                'req_msg_id'=> $reqMsgId,
                'pay_type'  => $postData['payType'],
                'stages'    => $stages,
                'pay_money' => $payMoney,
                'paths'     => $paths,
                'sid'       => $sid,
                'createtime'=> time(),
            ]);

            // 上上签电子合同签约
            $result = $this->shangShangQian($postData['idNo'], $postData['phoneNo'], $postData['custName'], $sid);

            if($result['errno'] != 0)
            {
                return json([
                    'respMess' => $result['errmsg'],
                    'respCode' => 'false',
                ]);
            }
        }
        
        return json($result);
    }

    /**
     * 上上签 签约协议
     */
    private function shangShangQian($idNo = '', $account = '', $name = '', $sid = '')
    {
        // $idNo = '343221199103201230';
        // $account = '15666589065';
        // $name    = '黄斌';
        // $sid = mt_rand(10000, 99999) . time();

        $developerId = '1546398307015021788';
        $pem = file_get_contents(ROOT_PATH . 'rsakey/shangshangqian_rsa_private_key.pem');
        $server_host = 'https://openapi.bestsign.info/openapi/v2';

        $bestSign = new \shangshangqian\BestSign($developerId, $pem, $server_host);

        // 注册
        $response = $bestSign->apiPost('/user/reg/', [
            'account'       => $account,
            'name'          => $name,
            'userType'      => '1',
            'mobile'        => $account,
            'credential'    => [
                'identity'  => $idNo
            ],
            'applyCert'     => 1,
        ]);

        $result = json_decode($response, true);
        // v($result);

        if($result['errno'] != 0)
        {
            return $result;
        }

        // 注册时证书申请状态查询(暂时不做处理)
        // $response = $bestSign->apiPost('/user/async/applyCert/status/', [
        //     'account' => $account,
        //     'taskId'  => $result['data']['taskId']
        // ]);
        // $result = json_decode($response, true);


        // 上传合同
        $jjdocBase64 = base64_encode(file_get_contents(ROOT_PATH . 'jjdoc/jj.pdf'));
        $response = $bestSign->apiPost('/storage/upload/', [
            'account'       => $account,
            'fmd5'          => md5_file(ROOT_PATH . 'jjdoc/jj.pdf'),
            'ftype'         => 'pdf',
            'fname'         => 'jj.pdf',
            'fpages'        => '3',
            'fdata'         => $jjdocBase64,
            'isCleanup'     => 1,
        ]);

        $result = json_decode($response, true);
        // v($result);
        if($result['errno'] != 0)
        {
            return $result;
        }

        // 为合同添加元素
        $response = $bestSign->apiPost('/storage/addPdfElements/', [
            'fid'       => $result['data']['fid'],
            'account'   => $account,
            'elements'  => [
                [
                    'pageNum'=> '1',
                    'x'      => '0.23',
                    'y'      => '0.62',
                    'type'   => 'text',
                    'value'  => $name,
                ],
                [
                    'pageNum'=> '1',
                    'x'      => '0.23',
                    'y'      => '0.65',
                    'type'   => 'text',
                    'value'  => '3424564465465465465',
                ],
                [
                    'pageNum'=> '1',
                    'x'      => '0.23',
                    'y'      => '0.69',
                    'type'   => 'text',
                    'value'  => '476579854156645',
                ],
                [
                    'pageNum'=> '1',
                    'x'      => '0.23',
                    'y'      => '0.73',
                    'type'   => 'text',
                    'value'  => '13285177015',
                ],
                [
                    'pageNum'=> '3',
                    'x'      => '0.23',
                    'y'      => '0.57',
                    'type'   => 'text',
                    'value'  => date('Y-m-d'),
                ],
            ]
        ]);

        $result = json_decode($response, true);
        // v($result);
        if($result['errno'] != 0)
        {
            return $result;
        }

        // 创建合同
        $time = time() + 31536000;
        $response = $bestSign->apiPost('/contract/create/', [
            'account'       => $account,
            'fid'           => $result['data']['fid'],
            'expireTime'    => "$time",
            'title'         => '测试',
            'description'   => '测试121212',
        ]);

        $result = json_decode($response, true);
        // v($result);
        if($result['errno'] != 0)
        {
            return $result;
        }

        // 发送合同
        $time = time() + 3600;
        $response = $bestSign->apiPost('/contract/send/', [
            'contractId'   => $result['data']['contractId'],
            'signer'       => $account,
            'expireTime'   => "$time",
            'dpi'          => '120',
            'isAllowChangeSignaturePosition' => '1',            // 允许拖动
            'sid'          => $sid,                             // 业务流水号
            'signatureImageName'    => 'default',               // 签名/印章
            'isDrawSignatureImage'  => '1',                     // 是否手绘签名
            'signaturePositions' => [
                [
                    'pageNum' => '3',
                    'x'       => '0.2',
                    'y'       => '0.6',
                ],
            ],
            'pushUrl' => 'http://www.skeep.cc/jjapi/v1/pay/notify',
            // 'returnUrl'     => 'https://www.baidu.com',
        ]);

        $result = json_decode($response, true);
        
        // 此处为短信业务逻辑  $result['data']['url']
        if($result['errno'] == 0)
        {
            
        }

        // 返回结果数组
        return $result;
    }

    /**
     * 上上签协议异步回调业务逻辑
     */
    public function notify()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if(isset($data['params']['sid']))
        {
            Db::name('auth')
                ->where([
                    'sid' => $data['params']['sid'],
                ])
                ->update([
                    'status' => 1   // 更新为1,签约成功
                ]);
        }
    }



    /**
     * 身份证等图片上传接口( 此处需要验证图片真实性 )
     */
    public function upload()
    {
        // 获取图片资源
        $file = request()->file('image');
        
        // 图片上传，移动到 /public/uploads/ 目录下
        if($file)
        {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');

            if($info)
            {
                // 图片后缀
                // echo $info->getExtension(); // jpg

                // 输出 保存路径 如20160820/42a79759f284b767dfcb2a0197904287.jpg
                return json([
                    'errcode' => 200,
                    'errmsg'  => 'ok',
                    'path'    => '//' . $_SERVER['SERVER_NAME'] . '/uploads/' . $info->getSaveName(),
                ]);

                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                // echo $info->getFilename(); 
            }
            else
            {
                return json([
                    'errcode' => 204,
                    'errmsg'  => '图片上传失败',
                ]);
            }
        }
    }

    /**
     * 双乾快捷支付 捐款接口(须先签约)(若启用短信通道，须调用交易接口，非启用则直接完成支付，异步通知更新业务逻辑)
     */
    public function consume()
    {
        $phone = Request::instance()->param('phone');
        $price = Request::instance()->param('price');

        // 验证是否签约
        $result = Db::name('auth')
                ->where([
                    'phone' => $phone,
                    'status'=> 1        // 1为签约成功 2为失败
                ])
                ->find();

        if(empty($result))
        {
            return json([
                'resFlag' => 'false',
                'resMess' => '未认证或签约',
            ]);
        }

        // 交易类型（1.协议 2.大额）
        $type = $result['type'];

        // 订单号
        $merOrderNo = 'JJZf' . time() . mt_rand(10000, 99999);

        // 支付请求数据
        $postData = [   
            'merNo'     => $result['mer_no'],                                        // 商户号
            'custName'  => $result['cust_name'],                                             // 姓名
            'cardNo'    => $result['card_no'],                               // 银行卡号
            'phone'     => $result['phone'],                                       // 手机号
            'idNo'      => $result['id_no'],                                // 身份证
            'idType'    => 0,                                                   // 证件类型（0.身份证）
            'payAmount' => $price,                                                // 交易金额（单位元，小数保留2位）
            'merOrderNo'=> $merOrderNo,                                         // 商户订单号
            'bankCode'  => $result['bank_code'],                                               // 银行代码
            'payType'   => $result['pay_type'],                                             // 校验渠道 固定：XYPAY
            'cardType'  => 1,                                                   // 卡类型（1.借记卡 2.贷记卡）
            'NotifyURL' => Config::get('notify_url'),                           // 异步通知地址(选填)
            'transDate' => date('Ymd'),                                         // 交易日期
            'transTime' => date('His'),                                         // 交易时间
            // 'purpose'   => '测试',                                               // 商户备注(选填)
        ];

        // 字典序加密
        $md5InfoData = [
            'merNo'     => $postData['merNo'],
            'custName'  => $postData['custName'],
            'phone'     => $postData['phone'],
            'cardNo'    => $postData['cardNo'],
            'idNo'      => $postData['idNo'],
            'idType'    => $postData['idType'],
            'payAmount' => $postData['payAmount'],
            'merOrderNo'=> $postData['merOrderNo'],
            'cardType'  => $postData['cardType'],
            'year'      => '',
            'month'     => '',
            'CVV2'      => '',
            'transDate' => $postData['transDate'],
            'transTime' => $postData['transTime'],
        ];

        $joinMapValue = joinMapValue($md5InfoData);
        $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
        $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));


        // 所属企业ID
        $eData = Db::name('user')
                ->where([
                    'id' => $this->uid
                ])
                ->find();

        $company_id = empty($eData) ? -1 : $eData['company_id'];

        // 交易订单入库
        $bool = Db::name('pay_order')->insert([
            'uid'       => $this->uid,
            'type'      => $type,
            'cust_name' => $postData['custName'],
            'phone'     => $postData['phone'],
            'card_no'   => $postData['cardNo'],
            'bank_code' => $postData['bankCode'],
            'id_no'     => $postData['idNo'],
            'price'     => $postData['payAmount'] * 100,
            'order_no'  => $postData['merOrderNo'],
            'createtime'=> time(),
            'company_id'=> $company_id,
        ]);

        if($bool)
        {
            // 请求支付
            $result = curlPost(Config::get('syn_url'), $postData);

            return json(json_decode($result, true));
        }
        else
        {
            return json([
                'resFlag' => 'false',
                'resMess' => '订单入库失败',
            ]);
        }
    }

    /**
     * 双乾快捷支付 交易接口(短信通道) 暂不使用
     */
    public function transaction()
    {
        $merOrderNo = Request::instance()->param('merOrderNo');
        $txnTime    = Request::instance()->param('txnTime');
        $smsCode    = Request::instance()->param('smsCode');

        $result = Db::name('pay_order')
                ->where([
                    'order_no' => $merOrderNo,
                ])
                ->find();

        if(empty($result))
        {
            return json([
                'resFlag' => 'false',
                'resMess' => '交易异常，订单不存在',
            ]);
        }
        else
        {
            // 支付请求数据
            $postData = [   
                'merNo'     => $this->merno,                                        // 商户号
                'merOrderNo'=> $merOrderNo,                                         // 商户订单号
                'cardNo'    => $result['card_no'],                                  // 银行卡号
                'custName'  => $result['cust_name'],                                // 姓名
                'idType'    => 0,                                                   // 证件类型（0.身份证）
                'idNo'      => $result['id_no'],                                    // 身份证
                'phone'     => $result['phone'],                                    // 手机号
                'purpose'   => '交易支付',                                            // 交易备注信息
                'payAmount' => (int)$result['price'] / 100,                         // 交易金额（单位元，小数保留2位）
                'bankCode'  => $result['bank_code'],                                // 银行代码
                'payType'   => 'XYPAY',                                             // 校验渠道 固定：XYPAY
                'NotifyURL' => Config::get('notify_url'),                           // 异步通知地址(选填)
                'txnTime'   => $txnTime,                                            // 交易时间
                'smsCode'   => $smsCode,                                            // 短信验证码
            ];

            // 加密参
            $md5InfoData = [   
                'merNo'     => $this->merno,                                        // 商户号
                'merOrderNo'=> $merOrderNo,                                         // 商户订单号
                'custName'  => $postData['custName'],                                // 姓名
                'phone'     => $postData['phone'],                                    // 手机号
                'cardNo'    => $postData['cardNo'],                                  // 银行卡号
                'idType'    => 0,                                                   // 证件类型（0.身份证）
                'idNo'      => $postData['idNo'],                                    // 身份证
                'payAmount' => $postData['payAmount'],                             // 交易金额（单位元，小数保留2位）
                'bankCode'  => $postData['bankCode'],                                // 银行代码
                'NotifyURL' => $postData['NotifyURL'],                           // 异步通知地址(选填)
            ];

            $joinMapValue = joinMapValue($md5InfoData);
            $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
            $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

            // v($postData);
            
            // 支付请求
            $result = curlPost(Config::get('trans_url'), $postData);

            return json(json_decode($result, true));
        }

    }

    /**
     * 双乾支付异步回调通知（更新订单）
     */
    public function trans_notify()
    {
        $postData = Request::instance()->post();

        if(isset($postData['BillNo']))
        {
            $bool = Db::name('pay_order')
                ->where([
                    'order_no' => $postData['BillNo'],
                ])
                ->update([
                    'status' => 1 // 更新支付订单状态 1为成功
                ]);
        }
    }

    /**
     * 订单查询接口，含协议和大额
     */
    public function order_list()
    {
        // 类型(1.协议 2.大额)
        $type = Request::instance()->param('type', 1);

        // 状态(1.成功 2.异常)
        $status = Request::instance()->param('status', 1);

        // 显示记录数
        $size = Request::instance()->param('size', 10);

        // 页码
        $pageSize = Request::instance()->param('pageSize', 1);
        $pageSize = (int)$pageSize - 1;

        // 总记录数
        $num = Db::name('pay_order')
            ->where([
                'uid'    => $this->uid,
                'type'   => $type,
                'status' => $status,
            ])
            ->count();

        // 单页条数
        $data = Db::name('pay_order')
            ->where([
                'uid'    => $this->uid,
                'type'   => $type,
                'status' => $status,
            ])
            ->limit($pageSize, $size)
            ->select();

        return json([
            'errcode' => 200,
            'errmsg'  => 'ok',
            'num'     => $num,
            'data'    => $data,
        ]);
    }


















    /**
     * 双乾快捷支付 解约接口
     */
    public function break_bind_pay()
    {
        $reqMsgId = mt_rand(1000, 9999) . time();

        // 支付请求数据
        $postData = [   
            'merNo'     => $this->merno,                                        // 商户号
            'custName'  => '汪高启',                                             // 姓名
            'phoneNo'   => '13285177013',                                       // 手机号
            'cardNo'    => '6227002006600280380',                               // 银行卡号
            'idNo'      => '342221199110018230',                                // 身份证
            'reqMsgId'  => '834124411',                                         // 商户订单号
            'payType'   => 'NUCCPAY',                                           // 网联：NUCCPAY；银联：UNIONPAY
        ];   

        // 字典序
        ksort($postData);

        // 签名拼接加密
        $joinMapValue = joinMapValue($postData);
        $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
        $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

        // 请求
        $result = curlPost(Config::get('break_bind_pay_url'), $postData);
        
        v(json_decode($result));
    }
}