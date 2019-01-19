<?php

/**
 * 开户接口
 * Auther: wanggaoqi
 * Date: 2018/12/31
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use think\Db;
use app\lib\exception\UserException;

class Account extends Base{

    /**
     * 电子合同查询
     */
    public function contract_list()
    {
        // 状态(1.成功 2.异常)
        $status = Request::instance()->param('status', 1);

        // 显示记录数
        $size = Request::instance()->param('size', 10);

        // 页码
        $pageSize = Request::instance()->param('pageSize', 1);
        $pageSize = (int)$pageSize - 1;

        // 总页数
        $num = Db::name('wh_contract')
            ->where([
                'uid'    => $this->uid,
                'status' => $status
            ])
            ->count();

        // 单页条数
        $data = Db::name('wh_contract')
            ->where([
                'uid'    => $this->uid,
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

    /**
     * 电子合同签约手机验证码
     */
    public function send_smscode()
    {
        $phone = Request::instance()->param('phone');
        $code  = mt_rand(1000, 9999);
        $time = time();

        // 发送短信验证码
        $bool = Base::sendSmsCode($phone, '[加减数据]验证码：' . $code . '，5分钟内有效。');

        // 缓存验证码
        $pool = Db::name('wh_smscode')->insert([
            'mobile_number' => $phone,
            'validate_code' => $code,
            'create_time'   => $time,
            'expire_time'   => $time + 300, // 验证码有效期5分钟(300秒)
            'type'          => 3,
        ]);

        if($bool && $pool)
        {
            $json = json([
                'errcode' => 200,
                'errmsg'  => '操作成功',
            ]);
        }
        else
        {
            $json = json([
                'errcode' => 204,
                'errmsg'  => '操作失败，请稍后再试',
            ]);
        }

        return $json;
    }

    /**
     * 电子合同签约
     */
    public function contract()
    {
        // $_POST = [
        //     'idno'    => '343221199103201230',
        //     'phone'   => '15666589065',
        //     'name'  => '黄斌',
        //     'company_name' => 'xxxx科技有限公司',
        //     'company_account' => 'xxxxxxxxxxxxxxxxxxx',
        //     'loan_lower'    => '一百',
        //     'service_charge'    => '20',
        //     'pay_money' => '五十元',
        //     'code' => 3343,
        // ];

        $postData = Request::instance()->post();

        // 手机验证码 验证
        $late = Db::name('wh_smscode')
                ->where([
                    'mobile_number' => $postData['phone'],
                    'validate_code' => $postData['code'],
                    'type'          => 3,
                ])
                ->order('id desc')
                ->find();

        if(empty($late) || $late['expire_time'] < time())
        {
            return json([
                'errcode' => 204,
                'errmsg'  => '手机验证码已经过期',
            ]); 
        }

        // 所属企业ID
        $eData = Db::name('wh_user')
                ->where([
                    'id' => $this->uid
                ])
                ->find();

        $company_id = empty($eData) ? -1 : $eData['company_id'];

        // 上上签流水号
        $sid = mt_rand(10000, 99999) . time();
        
        // 合同信息入库
        $data = [
            'uid'   => $this->uid,                                  // UID
            'name'  => $postData['name'],                           // 客户姓名
            'idno'  => $postData['idno'],                           // 客户身份证
            'phone' => $postData['phone'],                          // 客户手机
            'company_name' => $postData['company_name'],            // 公司名称
            'company_account' => $postData['company_account'],      // 公司帐户
            'loan_lower' => $postData['loan_lower'],                // 预计贷款金额
            'service_charge' => $postData['service_charge'],        // 服务费（百分比）
            'pay_money' => $postData['pay_money'],                  // 意向金
            'sid'   => $sid,                                        // 上上签 流水号
            'company_id' => $company_id,                            // 所属企业ID
        ];

        $bool = Db::name('wh_contract')->insert($data);


        /**
         * 上上签 签约
         */

        // ============================ 上上签 ===============================
        $elements = [
            [
                'pageNum'=> '1',
                'x'      => '0.18',
                'y'      => '0.115',
                'type'   => 'text',
                'value'  => $postData['name'],      // 甲方客户姓名
            ],
            [
                'pageNum'=> '1',
                'x'      => '0.60',
                'y'      => '0.115',
                'type'   => 'text',
                'value'  => $postData['company_name'],  // 乙方公司
            ],
            [
                'pageNum'=> '1',
                'x'      => '0.65',
                'y'      => '0.210',
                'type'   => 'text',
                'value'  => $postData['loan_lower'],   // 贷款金额
            ],
            [
                'pageNum'=> '1',
                'x'      => '0.428',
                'y'      => '0.250',
                'type'   => 'text',
                'value'  => $postData['service_charge'], // 服务费
            ],
            [
                'pageNum'=> '1',
                'x'      => '0.61',
                'y'      => '0.303',
                'type'   => 'text',
                'value'  => $postData['pay_money'],   // 意向金
            ],
            [
                'pageNum'=> '2',
                'x'      => '0.20',
                'y'      => '0.74',
                'type'   => 'text',
                'value'  => $postData['name'], // 甲方客户姓名
            ],
            [
                'pageNum'=> '2',
                'x'      => '0.20',
                'y'      => '0.82',
                'type'   => 'text',
                'value'  => date('Y-m-d'),
            ],
            [
                'pageNum'=> '2',
                'x'      => '0.71',
                'y'      => '0.74',
                'type'   => 'text',
                'value'  => $postData['company_name'],  // 乙方公司
            ],
            [
                'pageNum'=> '2',
                'x'      => '0.71',
                'y'      => '0.82',
                'type'   => 'text',
                'value'  => date('Y-m-d'),
            ],
        ];

        $result = $this->shangShangQian([
            'custName' => $postData['name'],
            'phoneNo'  => $postData['phone'],
            'idNo'     => $postData['idno'],
            'sid'      => $sid,
        ], $elements);

        // v($result);
        // ============================ end =================================


        if($bool)
        {
            $json = json([
                'errcode' => 200,
                'errmsg'  => '操作成功',
            ]);
        }
        else
        {
            $json = json([
                'errcode' => 204,
                'errmsg'  => '操作失败，请稍后再试',
            ]);
        }

        return $json;
    }


    /**
     * 上上签 签约协议
     */
    private function shangShangQian($data, $elements)
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
        $jjdocBase64 = base64_encode(file_get_contents(ROOT_PATH . 'jjdoc/enter.pdf'));
        $response = $bestSign->apiPost('/storage/upload/', [
            'account'       => $data['phoneNo'],
            'fmd5'          => md5_file(ROOT_PATH . 'jjdoc/enter.pdf'),
            'ftype'         => 'pdf',
            'fname'         => 'enter.pdf',
            'fpages'        => '2',
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
                    'pageNum' => '2',
                    'x'       => '0.2',
                    'y'       => '0.7',
                ],
            ],
            'pushUrl' => 'http://jj.5d1.top/jjapi/v1/account/notify',
            // 'returnUrl'     => 'https://www.baidu.com',
        ]);

        $result = json_decode($response, true);
        // v($result);
        if($result['errno'] != 0)
        {
            return $result;
        }

        

        // 发送短信
        $content = '[加减数据]尊敬的客户，您有一份待签署的外包服务协议，地址如下：' . $result['data']['url'];

        $res = Base::sendSmsCode($data['phoneNo'], $content);

        if($res === 'ok')
        {
            return [
                'errno' => 0,
                'errmsg'=> '成功',
            ];
        }
        else
        {
            return [
                'errno' => 204,
                'errmsg'=> $res,
            ];
        }
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
            Db::name('wh_contract')
                ->where([
                    'sid' => $data['params']['sid'],
                ])
                ->update([
                    'status' => 1   // 更新为1,签约成功
                ]);
        }
    }
}
