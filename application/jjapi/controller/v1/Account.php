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
     * 电子合同签约
     */
    public function contract()
    {
        $postData = Request::instance()->post();

        if(count($postData) !== 7)
        {
            return json([
                'errcode' => 204,
                'errmsg'  => '请正确填写',
            ]);   
        }

        // 所属企业ID
        $eData = Db::name('wh_user')
                ->where([
                    'id' => $this->uid
                ])
                ->find();

        $company_id = empty($eData) ? -1 : $eData['company_id'];
        
        // 写入数据
        $postData['uid'] = $this->uid;
        $postData['company_id'] = $company_id;

        $bool = Db::name('wh_contract')->insert($postData);

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
     * 电子合同查询
     */
    public function contract_list()
    {
        // 状态(1.成功 2.异常)
        $status = Request::instance()->param('status', 1);

        // 显示记录数
        $size = Request::instance()->post('size', 10);

        // 页码
        $pageSize = Request::instance()->post('pageSize', 1);
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
            ->limit($pageSize, $size)
            ->select();

        return json([
            'errcode' => 200,
            'errmsg'  => 'ok',
            'num'     => $num,
            'data'    => $data,
        ]);
    }

    // /**
    //  * 开户 企业版 异常处理 {"msg":"服务器内部错误，不想告诉你","error_code":999,"request_url":"\/jjapi\/v1\/Account\/create_enterprise"}
    //  */
    // public function create_enterprise()
    // {
    //     $postData = Request::instance()->post();

    //     $data = [
    //         'type'      => @$postData['type'],
    //         'name'      => @$postData['name'],
    //         'phone'     => @$postData['phone'],
    //         'city'      => @$postData['city'],
    //         'address'   => @$postData['address'],
    //         'createtime'=> time(),
    //     ];

    //     $bool = Db::name('wh_enterprise')->insert($data);

    //     if($bool)
    //     {
    //         $json = json([
    //             'errcode' => 200,
    //             'errmsg'  => '操作成功',
    //         ]);
    //     }
    //     else
    //     {
    //         $json = json([
    //             'errcode' => 204,
    //             'errmsg'  => '操作失败，请稍后再试',
    //         ]);
    //     }

    //     return $json;
    // }

    // /**
    //  * 开户 精英版
    //  */
    // public function create_fine()
    // {
    //     $postData = Request::instance()->post();

    //     // 推荐人
    //     $recommend = Request::instance()->post('recommend', '');

    //     $data = [
    //         'name'      => $postData['name'],       // 姓名
    //         'idno'      => $postData['idno'],       // 身份证
    //         'phone'     => $postData['phone'],      // 手机
    //         'city'      => $postData['city'],       // 城市
    //         'address'   => $postData['address'],    // 地址
    //         'recommend' => $recommend,              // 推荐人
    //         'idno_photo'=> $postData['idno_photo'], // 身份证正反面

    //         'cardno'    => $postData['cardno'],     // 卡号
    //         'cardname'  => $postData['cardname'],   // 卡姓名
    //         'reserve_mp'=> $postData['reserve_mp'],    // 预留手机
    //         'cardaddr'  => $postData['cardaddr'],   // 开户行

    //         'prove'     => $postData['prove'],      // 手持身份证、资质证明、提现银行卡 等图片 

    //     ];

    //     $bool = Db::name('wh_fine')->insert($data);

    //     if($bool)
    //     {
    //         $json = json([
    //             'errcode' => 200,
    //             'errmsg'  => '操作成功',
    //         ]);
    //     }
    //     else
    //     {
    //         $json = json([
    //             'errcode' => 204,
    //             'errmsg'  => '操作失败，请稍后再试',
    //         ]);
    //     }

    //     return $json;
    // }
}
