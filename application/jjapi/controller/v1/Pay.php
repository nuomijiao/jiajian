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

class Pay extends Base
{
    private $merno  = '203163';     // 商户号
    private $MD5key = "]BLw}Bbk";   // MD5key

    /**
     * 签约列表
     */
    public function auth_list()
    {
        // 显示记录数
        $size = Request::instance()->param('size', 10);

        // 类型
        $type = Request::instance()->param('type', 1);

        // 状态(1.成功 2.异常)
        $status = Request::instance()->param('status', 10);

        // 页码
        $pageSize = Request::instance()->param('pageSize', 1);
        $pageSize = (int)$pageSize - 1;

        // 总记录数
        $num = Db::name('wh_auth')
            ->where([
                'type'=> $type,
                'uid' => $this->uid,
                'status' => $status
            ])
            ->count();

        // 单页条数
        $data = Db::name('wh_auth')
            ->where([
                'type'=> $type,
                'uid' => $this->uid,
                'status' => $status
            ])
            ->limit($pageSize * $size, $size)
            ->select();

        return json([
            'errcode' => 200,
            'errmsg'  => 'ok',
            'num'     => $num,
            'data'    => $data,
        ]);
    }








    // ========================================= 双乾快捷支付 =========================================


    /**
     * 认证/签约接口
     */
    public function auth()
    {  
        $postData   = Request::instance()->post();
        $type       = @$postData['type'];
        $stages     = @$postData['stages'];                                     // 分期数
        $stages     = (int)$stages;
        $payMoney   = @$postData['payMoney'];                                   // 预计扣款金
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

        // v($result);

        // 协议签约
        if($result['respCode'] === 'success' && $postData['custType'] === '02')
        {
            // 预计扣款金 单位分
            $total = (float)$payMoney * 100;

            // 签约信息入库
            $authData = [
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
                'pay_money' => $total,
                'paths'     => $paths,
                'sid'       => $reqMsgId,   // 上上签流水号和商户请求流水号设相同
                'createtime'=> time(),
            ];

            // 分期数据
            $data = [
                'idNo'    => $postData['idNo'],
                'phoneNo' => $postData['phoneNo'],
                'custName'=> $postData['custName'],
                'cardNo'  => $postData['cardNo'],
                'sid'     => $reqMsgId,
            ];

            // 单期模板
            if($stages == 1)  
            {
                $elements = [  
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.62',
                        'type'   => 'text',
                        'value'  => $postData['custName'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.65',
                        'type'   => 'text',
                        'value'  => $postData['idNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.69',
                        'type'   => 'text',
                        'value'  => $postData['cardNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.73',
                        'type'   => 'text',
                        'value'  => $postData['phoneNo'],
                    ],
                    [
                        'pageNum'=> '2',
                        'x'      => '0.30',
                        'y'      => '0.10',
                        'type'   => 'text',
                        'value'  => $payMoney,
                    ],
                    [
                        'pageNum'=> '3',
                        'x'      => '0.23',
                        'y'      => '0.57',
                        'type'   => 'text',
                        'value'  => date('Y-m-d'),
                    ],
                ];
            }
            else    // 多期模板
            {
                $elements = [  
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.62',
                        'type'   => 'text',
                        'value'  => $postData['custName'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.65',
                        'type'   => 'text',
                        'value'  => $postData['idNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.69',
                        'type'   => 'text',
                        'value'  => $postData['cardNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.73',
                        'type'   => 'text',
                        'value'  => $postData['phoneNo'],
                    ],
                    [
                        'pageNum'=> '3',
                        'x'      => '0.23',
                        'y'      => '0.57',
                        'type'   => 'text',
                        'value'  => date('Y-m-d'),
                    ],
                    [
                        'pageNum'=> '2',
                        'x'      => '0.18',
                        'y'      => '0.14',
                        'type'   => 'text',
                        'value'  => $stages,
                    ],
                    [
                        'pageNum'=> '2',
                        'x'      => '0.34',
                        'y'      => '0.14',
                        'type'   => 'text',
                        'value'  => date('d'),
                    ],
                ];

                // 总期数
                $num = 36;

                // 坐标间隔表格数
                $exp = 6;

                // 初始XY坐标
                $x = ['0.78', '0.28', '0.38', '0.48', '0.58', '0.68'];
                $y = [0, '0.18', '0.22', '0.255', '0.295', '0.335', '0.37'];

                // 金额取模
                $remain = $total % $stages;

                // 单期金额, 公式：总金额-(总金额%分期数)/分期数
                $price = ($total - $remain) / $stages;

                // 分期模板合成
                for ($i = 1; $i <= $stages; $i++)
                {
                    array_unshift($elements, [
                        'pageNum'=> '2',
                        'x'      => $x[$i % $exp],
                        'y'      => $y[ceil($i / $exp)],
                        'type'   => 'text',
                        'value'  => $price / 100,
                    ]);
                }

                // 最后一期加余数
                $elements[0]['value'] = $elements[0]['value'] + $remain / 100;
            }

            // 客户签约记录
            $bool = Db::name('wh_auth')->insert($authData);

            // 上上签电子合同签约
            $result = $this->shangShangQian($data, $elements);

            if($result['errno'] == 0)
            {
                return json([
                    'errcode' => 200,
                    'errmsg'  => $result['errmsg']
                ]);
            }
            else
            {
                return json([
                    'errcode' => 204,
                    'errmsg'  => $result['errmsg']
                ]);
            }
        }
        else    // 认证时
        {
            if($result['respCode'] === 'success')
            {
                return json([
                    'errcode' => 200,
                    'errmsg'  => $result['respMessage']
                ]);
            }
            else
            {
                return json([
                    'errcode' => 204,
                    'errmsg'  => $result['respMessage']
                ]);
            }
        }
    }

    /**
     * 上上签 签约协议
     */
    private function shangShangQian($data, $elements)
    {
        $developerId = '1546398307015021788';
        $pem = file_get_contents(ROOT_PATH . 'rsakey/shangshangqian_rsa_private_key.pem');
        $server_host = 'https://openapi.bestsign.info/openapi/v2';

        $bestSign = new \shangshangqian\BestSign($developerId, $pem, $server_host);

        // 注册
        $response = $bestSign->apiPost('/user/reg/', [
            'account'       => $data['phoneNo'],
            'name'          => $data['custName'],
            'userType'      => '1',
            'mobile'        => $data['phoneNo'],
            'credential'    => [
                'identity'  => $data['idNo']
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
            'account'       => $data['phoneNo'],
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
            'account'   => $data['phoneNo'],
            'elements'  => $elements,
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
            'account'       => $data['phoneNo'],
            'fid'           => $result['data']['fid'],
            'expireTime'    => "{$time}",
            'title'         => '协议合同',
            'description'   => '签署的协议合同',
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
            'signer'       => $data['phoneNo'],
            'expireTime'   => "{$time}",
            'dpi'          => '120',
            'isAllowChangeSignaturePosition' => '1',            // 允许拖动
            'sid'          => $data['sid'],                     // 流水号
            'signatureImageName'    => 'default',               // 签名/印章
            'isDrawSignatureImage'  => '1',                     // 是否手绘签名
            'signaturePositions' => [
                [
                    'pageNum' => '3',
                    'x'       => '0.2',
                    'y'       => '0.6',
                ],
            ],
            'pushUrl' => 'http://jj.5d1.top/jjapi/v1/notify/protocol',
            // 'returnUrl'     => 'https://www.baidu.com',
        ]);

        $result = json_decode($response, true);

        if($result['errno'] != 0)
        {
            return $result;
        }

        // v($result);

        // 发送短信(1
        $response = $bestSign->apiPost('/notice/send/', [
            'bizType' => 'sign',
            'channel' => 'sms',
            'target'  => $data['phoneNo'],
            'content' => [
                'shortUrl' => $result['data']['url']
            ],
        ]);
        $result = json_decode($response, true);

        if($result['errno'] == 0)
        {
            return [
                'errno' => 0,
                'errmsg'=> '发送成功，请尽快签署合同。'
            ];
        }
        else
        {
            return [
                'errno' => 204,
                'errmsg'=> $result['errmsg'],
            ];
        }

        // 发送短信(2
        // $content = '[加减数据]尊敬的客户，您有一份待签署的代扣合同，地址如下：' . $result['data']['url'];

        // $res = Base::sendSmsCode($data['phoneNo'], $content);

        // if($res === 'ok')
        // {
        //     return [
        //         'errno' => 0,
        //         'errmsg'=> '短信发送成功，合同签署地址：'.$result['data']['url'],
        //     ];
        // }
        // else
        // {
        //     return [
        //         'errno' => 204,
        //         'errmsg'=> $res,
        //     ];
        // }
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
     * 扣款交易接口 (含协议交易和代扣交易）
     */
    public function consume()
    {
        $id    = Request::instance()->param('id');
        $phone = Request::instance()->param('phone');
        $price = (float)Request::instance()->param('price');

        // 验证是否签约
        $result = Db::name('wh_auth')
                ->where([
                    'id'    => $id,
                    'phone' => $phone,
                    'status'=> 1        // 1为签约成功 2为失败
                ])
                ->order('id desc')
                ->find();

        if(empty($result))
        {
            return json([
                'resFlag' => 'false',
                'resMess' => '未认证或签约',
            ]);
        }
        
        // 所属企业ID
        $eData = Db::name('wh_user')
                ->where([
                    'id' => $this->uid
                ])
                ->find();
        $company_id = empty($eData) ? -1 : $eData['company_id'];

        // 订单号
        $merOrderNo = 'JJZf' . time() . mt_rand(10000, 99999);

        // 验证 扣款总金额 不能大于 预计扣款金
        if( $price > $result['pay_money'] / 100 )
        {
            return json([
                'resFlag' => 'false',
                'resMess' => '扣款总金额不能大于预计扣款金',
            ]);
        }

        $type = $result['type'];

        // 1.协议交易业务逻辑
        if($type == 1)  
        {
            $postData = [   
                'merNo'     => $result['mer_no'],                                   // 商户号
                'custName'  => $result['cust_name'],                                // 姓名
                'cardNo'    => $result['card_no'],                                  // 银行卡号
                'phone'     => $result['phone'],                                    // 手机号
                'idNo'      => $result['id_no'],                                    // 身份证
                'idType'    => 0,                                                   // 证件类型（0.身份证）
                'payAmount' => $price,                                              // 交易金额（单位元，小数保留2位）
                'merOrderNo'=> $merOrderNo,                                         // 商户订单号
                'bankCode'  => $result['bank_code'],                                // 银行代码
                'payType'   => $result['pay_type'],                                 // 校验渠道 固定：XYPAY
                'cardType'  => 1,                                                   // 卡类型（1.借记卡 2.贷记卡）
                'NotifyURL' => 'http://jj.5d1.top/jjapi/v1/notify/epay',            // 异步通知地址(选填)
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

            // 请求数据和地址
            $data = $postData;
            $url = Config::get('syn_url');
        }
        else    
        {
            // 2.代扣交易业务逻辑

            $msgData = [
                'merNo'     => $result['mer_no'],                                   // 商户号
                'merOrderNo'=> $merOrderNo,                                         // 请求订单号
                'payAmount' => $price,                                              // 交易金额（单位元，小数保留2位）
                'cardNo'    => $result['card_no'],                                  // 银行卡号
                'customerId'=> $result['pay_type'],                               // 特殊字段（协议交易表示校验渠道，代扣交易表示客户号）
                'NotifyURL' => 'http://jj.5d1.top/jjapi/v1/notify/agent',           // 异步通知地址
                'purpose'   => '代扣支付',                                           // 交易说明
                'isSubMerPay' => 0,                                                 // 是否是会员交易 0：否;1:是
                // 'subMerNo'  => '',                                                  // 会员商户号（选填）
            ];

            $authMsgData = [
                'NotifyURL' => 'http://jj.5d1.top/jjapi/v1/notify/agent',           // 异步通知地址
                'cardNo'    => $result['card_no'],                                  // 银行卡号
                'customerId'=> $result['pay_type'],                                 // 特殊字段（协议交易表示校验渠道，代扣交易表示客户号）
                'merNo'     => $result['mer_no'],                                   // 商户号
                'merOrderNo'=> $merOrderNo,                                         // 请求订单号
                'payAmount' => $price,                                              // 交易金额（单位元，小数保留2位）
            ];

            $joinMapValue = joinMapValue($authMsgData);
            $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
            $msgData['MD5Info'] = strtoupper(md5($strBeforeMd5));

            // 入库参数
            $postData = [
                'merNo'     => $result['mer_no'],
                'custName'  => $result['cust_name'],
                'phone'     => $result['phone'],
                'cardNo'    => $result['card_no'],
                'bankCode'  => $result['bank_code'],
                'idNo'      => $result['id_no'],
            ];

            // 请求数据和地址
            $data = $msgData;
            $url = 'https://fastpay.95epay.cn/pay/collectTrade';
        }

        // 订单入库
        $params = [
            'uid'       => $this->uid,
            'type'      => $type,
            'mer_no'    => $postData['merNo'],
            'cust_name' => $postData['custName'],
            'phone'     => $postData['phone'],
            'card_no'   => $postData['cardNo'],
            'bank_code' => $postData['bankCode'],
            'id_no'     => $postData['idNo'],
            'price'     => $price * 100,
            'order_no'  => $merOrderNo,
            'createtime'=> time(),
            'day'       => date('Ymd'),
            'status'    => 2,       // 1.成功 2.异常 3.分期
            'company_id'=> $company_id,
        ];

        $bool = Db::name('wh_pay_order')->insert($params);

        if($bool)
        {
            // 请求支付
            $result = curlPost($url, $data);

            return json(json_decode($result, true));
        }
        else
        {
            // 此处应加日志处理...
            return json([
                'resFlag' => 'false',
                'resMess' => '订单入库失败',
            ]);
        }
    }

    /**
     * 双乾快捷支付 交易接口(短信通道)
     */
    // public function transaction()
    // {
    //     $merOrderNo = Request::instance()->param('merOrderNo');
    //     $txnTime    = Request::instance()->param('txnTime');
    //     $smsCode    = Request::instance()->param('smsCode');

    //     $result = Db::name('wh_pay_order')
    //             ->where([
    //                 'order_no' => $merOrderNo,
    //             ])
    //             ->find();

    //     if(empty($result))
    //     {
    //         return json([
    //             'resFlag' => 'false',
    //             'resMess' => '交易异常，订单不存在',
    //         ]);
    //     }
    //     else
    //     {
    //         // 支付请求数据
    //         $postData = [   
    //             'merNo'     => $this->merno,                                        // 商户号
    //             'merOrderNo'=> $merOrderNo,                                         // 商户订单号
    //             'cardNo'    => $result['card_no'],                                  // 银行卡号
    //             'custName'  => $result['cust_name'],                                // 姓名
    //             'idType'    => 0,                                                   // 证件类型（0.身份证）
    //             'idNo'      => $result['id_no'],                                    // 身份证
    //             'phone'     => $result['phone'],                                    // 手机号
    //             'purpose'   => '交易支付',                                            // 交易备注信息
    //             'payAmount' => (int)$result['price'] / 100,                         // 交易金额（单位元，小数保留2位）
    //             'bankCode'  => $result['bank_code'],                                // 银行代码
    //             'payType'   => 'XYPAY',                                             // 校验渠道 固定：XYPAY
    //             'NotifyURL' => 'http://jj.5d1.top/jjapi/v1/notify/epay',            // 异步通知地址(选填)
    //             'txnTime'   => $txnTime,                                            // 交易时间
    //             'smsCode'   => $smsCode,                                            // 短信验证码
    //         ];

    //         // 加密参
    //         $md5InfoData = [   
    //             'merNo'     => $this->merno,                                        // 商户号
    //             'merOrderNo'=> $merOrderNo,                                         // 商户订单号
    //             'custName'  => $postData['custName'],                                // 姓名
    //             'phone'     => $postData['phone'],                                    // 手机号
    //             'cardNo'    => $postData['cardNo'],                                  // 银行卡号
    //             'idType'    => 0,                                                   // 证件类型（0.身份证）
    //             'idNo'      => $postData['idNo'],                                    // 身份证
    //             'payAmount' => $postData['payAmount'],                             // 交易金额（单位元，小数保留2位）
    //             'bankCode'  => $postData['bankCode'],                                // 银行代码
    //             'NotifyURL' => $postData['NotifyURL'],                           // 异步通知地址(选填)
    //         ];

    //         $joinMapValue = joinMapValue($md5InfoData);
    //         $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
    //         $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

    //         // v($postData);
            
    //         // 支付请求
    //         $result = curlPost(Config::get('trans_url'), $postData);

    //         return json(json_decode($result, true));
    //     }

    // }











    // ========================================= 双乾代扣支付 绑卡 =========================================


    /**
     * 鉴权绑卡接口(无需签约可直接交易)
     */
    public function agentpay()
    {  
        $postData   = Request::instance()->post();

        $stages     = (int)$postData['stages'];                                 // 分期数
        $payMoney   = @$postData['payMoney'];                                   // 预计扣款金
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

        // 商户请求订单号(随机)
        $merOrderNo = mt_rand(10000, 99999) . time();

        // 鉴权信息
        $postData = [
            'merNo'     => $this->merno,
            'custName'  => @$postData['custName'],
            'cardNo'    => @$postData['cardNo'],
            'phoneNo'   => @$postData['phoneNo'],
            'idNo'      => @$postData['idNo'],
            'idType'    => 0,
            'merOrderNo'=> $merOrderNo,
            'bankCode'  => $bankCode,
            'cardType'  => 1,
            'cvn2'      => '',
            'month'     => '',
            'year'      => '',
        ];

        ksort($postData);

        // v($postData);

        // 签名拼接加密
        $joinMapValue = joinMapValue($postData);
        $strBeforeMd5 = $joinMapValue . strtoupper(md5($this->MD5key));
        $postData['MD5Info'] = strtoupper(md5($strBeforeMd5));

        // 请求
        $url = 'https://fastpay.95epay.cn/pay/collectAuthentication';
        $result = curlPost($url, $postData);
        $result = json_decode($result, true);

        // v($result);

        // 代扣签约
        if($result['respCode'] === '00000') // 成功
        {
            // 预计扣款金 单位分
            $total = (float)$payMoney * 100;

            // 签约信息入库
            $authData = [
                'uid'       => $this->uid,
                'type'      => 2,
                'mer_no'    => $this->merno,
                'cust_name' => $postData['custName'],
                'phone'     => $postData['phoneNo'],
                'card_no'   => $postData['cardNo'],
                'bank_code' => $postData['bankCode'],
                'id_no'     => $postData['idNo'],
                'req_msg_id'=> $merOrderNo,
                'pay_type'  => $result['customerId'], // 注：此字段在此接口中表示客户号，用于代扣交易接口使用，但在协议交易中此字段表示校验渠道
                'stages'    => $stages,
                'pay_money' => $total,
                'paths'     => $paths,
                'sid'       => $merOrderNo,   // 上上签流水号和商户鉴权订单号设相同
                'createtime'=> time(),
            ];

            // 分期数据
            $data = [
                'idNo'    => $postData['idNo'],
                'phoneNo' => $postData['phoneNo'],
                'custName'=> $postData['custName'],
                'cardNo'  => $postData['cardNo'],
                'sid'     => $merOrderNo,
            ];

            // 单期模板
            if($stages == 1)  
            {
                $elements = [  
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.62',
                        'type'   => 'text',
                        'value'  => $postData['custName'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.65',
                        'type'   => 'text',
                        'value'  => $postData['idNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.69',
                        'type'   => 'text',
                        'value'  => $postData['cardNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.73',
                        'type'   => 'text',
                        'value'  => $postData['phoneNo'],
                    ],
                    [
                        'pageNum'=> '2',
                        'x'      => '0.30',
                        'y'      => '0.10',
                        'type'   => 'text',
                        'value'  => $payMoney,
                    ],
                    [
                        'pageNum'=> '3',
                        'x'      => '0.23',
                        'y'      => '0.57',
                        'type'   => 'text',
                        'value'  => date('Y-m-d'),
                    ],
                ];
            }
            else    // 多期模板
            {
                $elements = [  
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.62',
                        'type'   => 'text',
                        'value'  => $postData['custName'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.65',
                        'type'   => 'text',
                        'value'  => $postData['idNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.69',
                        'type'   => 'text',
                        'value'  => $postData['cardNo'],
                    ],
                    [
                        'pageNum'=> '1',
                        'x'      => '0.23',
                        'y'      => '0.73',
                        'type'   => 'text',
                        'value'  => $postData['phoneNo'],
                    ],
                    [
                        'pageNum'=> '3',
                        'x'      => '0.23',
                        'y'      => '0.57',
                        'type'   => 'text',
                        'value'  => date('Y-m-d'),
                    ],
                    [
                        'pageNum'=> '2',
                        'x'      => '0.18',
                        'y'      => '0.14',
                        'type'   => 'text',
                        'value'  => $stages,
                    ],
                    [
                        'pageNum'=> '2',
                        'x'      => '0.34',
                        'y'      => '0.14',
                        'type'   => 'text',
                        'value'  => date('d'),
                    ],
                ];

                // 总期数
                $num = 36;

                // 坐标间隔表格数
                $exp = 6;

                // 初始XY坐标
                $x = ['0.78', '0.28', '0.38', '0.48', '0.58', '0.68'];
                $y = [0, '0.18', '0.22', '0.255', '0.295', '0.335', '0.37'];

                // 金额取模
                $remain = $total % $stages;

                // 单期金额, 公式：总金额-(总金额%分期数)/分期数
                $price = ($total - $remain) / $stages;

                // 分期模板合成
                for ($i = 1; $i <= $stages; $i++)
                {
                    array_unshift($elements, [
                        'pageNum'=> '2',
                        'x'      => $x[$i % $exp],
                        'y'      => $y[ceil($i / $exp)],
                        'type'   => 'text',
                        'value'  => $price / 100,
                    ]);
                }

                // 最后一期加余数
                $elements[0]['value'] = $elements[0]['value'] + $remain / 100;
            }

            // 客户签约记录
            $bool = Db::name('wh_auth')->insert($authData);

            // 上上签电子合同签约
            $result = $this->shangShangQian($data, $elements);

            if($result['errno'] == 0)
            {
                return json([
                    'errcode' => 200,
                    'errmsg'  => $result['errmsg']
                ]);
            }
            else
            {
                return json([
                    'errcode' => 204,
                    'errmsg'  => $result['errmsg']
                ]);
            }
        }
        else    // 失败
        {
            return json([
                'errcode' => 204,
                'errmsg'  => $result['respMessage']
            ]);
        }
    }












    // ========================================= 订单查询接口 =========================================
    


    /**
     * 订单查询接口，含协议和代扣
     */
    public function order_list()
    {
        // 类型(1.协议 2.代扣)
        $type = Request::instance()->param('type', 1);

        // 状态(1.成功 2.异常)
        $status = Request::instance()->param('status', 1);

        // 显示记录数
        $size = Request::instance()->param('size', 10);

        // 页码
        $pageSize = Request::instance()->param('pageSize', 1);
        $pageSize = (int)$pageSize - 1;

        // 总记录数
        $num = Db::name('wh_pay_order')
            ->where([
                'uid'    => $this->uid,
                'type'   => $type,
                'status' => $status,
            ])
            ->count();

        // 单页条数
        $data = Db::name('wh_pay_order')
            ->where([
                'uid'    => $this->uid,
                'type'   => $type,
                'status' => $status,
            ])
            ->limit($pageSize * $size, $size)
            ->select();

        return json([
            'errcode' => 200,
            'errmsg'  => 'ok',
            'num'     => $num,
            'data'    => $data,
        ]);
    }
















    // ========================================= 解约接口 =========================================

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