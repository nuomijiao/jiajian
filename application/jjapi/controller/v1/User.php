<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 17:18
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\Admin;
use app\jjapi\model\WhBalanceDetail;
use app\jjapi\model\WhUser;
use app\jjapi\model\WhWithdraw;
use app\jjapi\service\Picture;
use app\jjapi\service\Token;
use app\jjapi\validate\MoneyMustBePositiveInt;
use app\jjapi\validate\PagingParameter;
use app\jjapi\validate\UserInfo;
use app\jjapi\validate\WithdrawType;
use app\lib\enum\AccountApplyStatusEnum;
use app\lib\enum\UserDegreeEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;
use think\Config;
use think\Db;
use think\Exception;
use think\Loader;

Loader::import('shuangqian.NumberPaidSingleJiao', EXTEND_PATH, '.php');

class User extends BaseController
{
    public function getUserInfo()
    {
        $uid = Token::getCurrentUid();
        $userInfo = WhUser::get($uid)->hidden(['pwd']);
        if (!$userInfo) {
            throw new UserException();
        }
        return $this->jjreturn($userInfo);
    }


    public function modifyHeadImg()
    {
        $uid = Token::getCurrentUid();
        $head_img = $this->request->file('head_img');
        $origion_img = WhUser::where('id', '=', $uid)->value('head_img');

        $data = Picture::uploadImg($head_img, 'head_img');

        $user = WhUser::update(['id'=>$uid, 'head_img'=>$data['url']]);
        if ($user) {
            if ($origion_img != '/assets/img/user_head.png' && $origion_img != $data['url']) {
                unlink(ROOT_PATH.'public'.DS.$origion_img);
            }
            return $this->jjreturn(['head_img' => $user->head_img]);
        }
    }

    public function saveInfo()
    {
        $validate = new UserInfo();
        $request = $validate->goCheck();
        $uid = Token::getCurrentUid();
        $dataArray = $validate->getDataByRule($request->post());
        WhUser::where('id', '=', $uid)->update($dataArray);
        throw new SuccessMessage([
            'msg' => '修改成功'
        ]);
    }


    public function getBalanceDetail($page = 1, $size = 10)
    {
        (new PagingParameter())->goCheck();
        $uid = Token::getCurrentUid();
        $userInfo = WhUser::get($uid);
        //精英账户有四个余额，企业主账号有加减账户余额和明细， 企业员工账号只有加减账户余额和明细
        if (UserDegreeEnum::JingYing == $userInfo->degree && AccountApplyStatusEnum::Pass == $userInfo->status) {
            $pagingDetailList = WhBalanceDetail::getDetailByUser($uid, $page, $size);
            $surplus = $userInfo->surplus;
            $pay_surplus = $userInfo->pay_surplus;
            $econtract_surplus = $userInfo->econtract_surplus;
            $deposit_surplus = $userInfo->deposit_surplus;

        } elseif (UserDegreeEnum::QiYe == $userInfo->degree && AccountApplyStatusEnum::Pass == $userInfo->status && 1 == $userInfo->is_main_user) {
            $adminInfo = Admin::getCompanyByUserId($uid);
            $pagingDetailList = WhBalanceDetail::getDetailByCompany($adminInfo->id, $page, $size);
            $surplus = $adminInfo->surplus;

        } elseif (UserDegreeEnum::QiYe == $userInfo->degree && AccountApplyStatusEnum::Pass == $userInfo->status && 0 == $userInfo->is_main_user) {
            $adminInfo = Admin::getCompanyById($userInfo->company_id);
            $surplus = $adminInfo->surplus;
        } else {
            throw new UserException([
                'msg' => '该账号没有权限',
                'errorCode' => 30008,
            ]);
        }
        if (isset($pagingDetailList)) {
            if ($pagingDetailList->isEmpty()) {
                return json([
                    'msg' => '明细已见底',
                    'error_code' => 30009,
                    'surplus' => $surplus,
                    'pay_surplus' => isset($pay_surplus) ? $pay_surplus : '',
                    'econtract_surplus' => isset($econtract_surplus) ? $econtract_surplus : '',
                    'deposit_surplus' => isset($deposit_surplus) ? $deposit_surplus : '',
                    'degree' => $userInfo->degree,
                    'status' => $userInfo->status,
                    'is_main_user' => $userInfo->is_main_user,
                ]);
            }
            $data = $pagingDetailList->toArray();
        }

        return json([
            'error_code' => 'Success',
            'data' => isset($data) ? $data : '',
            'current_page' => $pagingDetailList->getCurrentPage(),
            'surplus' => $surplus,
            'pay_surplus' => isset($pay_surplus) ? $pay_surplus : '',
            'econtract_surplus' => isset($econtract_surplus) ? $econtract_surplus : '',
            'deposit_surplus' => isset($deposit_surplus) ? $deposit_surplus : '',
            'degree' => $userInfo->degree,
            'status' => $userInfo->status,
            'is_main_user' => $userInfo->is_main_user,
        ]);

    }


    //精英账户提现信息
    public function withdraw($page = 1, $size = 10, $type = 2)
    {
        $request = (new WithdrawType())->goCheck();
        $uid = Token::getCurrentUid();
        $userInfo = WhUser::get($uid);
        if (!(UserDegreeEnum::JingYing == $userInfo->degree && AccountApplyStatusEnum::Pass == $userInfo->status)) {
            throw new UserException([
                'msg' => '该账号没有权限',
                'errorCode' => 30008,
            ]);
        }
        $site = Config::get('site');
        $weekMoney = Db::name('wh_withdraw')->whereTime('create_time', 'week')->where('status', ['=', 1], ['=', 2], 'or')->where(['user_id'=>$uid, 'type'=> UserDegreeEnum::JingYing])->sum('money');
        $weekSurplus = $site['elite_weekly_withdraw_amount'] - $weekMoney;
        $withdrawList = WhWithdraw::getWithdrawListByStatus($uid, $page, $size, $type);
        if ($withdrawList->isEmpty()) {
            return json([
                'msg' => '明细已见底',
                'error_code' => 30009,
                'elite_single_withdraw_max' => $site['elite_single_withdraw_max'],
                'elite_weekly_withdraw_amount' => $site['elite_weekly_withdraw_amount'],
                'elite_weekly_withdraw_surplus' => $weekSurplus,
                'pay_surplus' => $userInfo->pay_surplus,
                'wait_pay_surplus' => $userInfo->wait_pay_surplus,
            ]);
        }
        $data = $withdrawList->toArray();
        return json([
            'error_code' => 'Success',
            'data' => $data,
            'current_page' => $withdrawList->getCurrentPage(),
            'elite_single_withdraw_max' => $site['elite_single_withdraw_max'],
            'elite_weekly_withdraw_amount' => $site['elite_weekly_withdraw_amount'],
            'elite_weekly_withdraw_surplus' => $weekSurplus,
            'pay_surplus' => $userInfo->pay_surplus,
            'wait_pay_surplus' => $userInfo->wait_pay_surplus,
        ]);
    }


    public function applyWithdraw($money = '')
    {
        $request = (new MoneyMustBePositiveInt())->goCheck();
        $uid = Token::getCurrentUid();
        $userInfo = WhUser::get($uid);
        $site = Config::get('site');
        if (!(UserDegreeEnum::JingYing == $userInfo->degree && AccountApplyStatusEnum::Pass == $userInfo->status)) {
            throw new UserException([
                'msg' => '该账号没有权限',
                'errorCode' => 30008,
            ]);
        }
        if ($money > $userInfo->pay_surplus) {
            throw new UserException([
                'msg' => '余额不足',
                'errorCode' => 30010,
            ]);
        }
        if ($money > $site['elite_single_withdraw_max']) {
            throw new UserException([
                'msg' => '超过单笔提现金额最大值',
                'errorCode' => 30011,
            ]);
        }
        $weekMoney = Db::name('wh_withdraw')->whereTime('create_time', 'week')->where('status', ['=', 1], ['=', 2], 'or')->where(['user_id'=>$uid, 'type'=> UserDegreeEnum::JingYing])->sum('money');
        $weekSurplus = $site['elite_weekly_withdraw_amount'] - $weekMoney;
        if ($money > $weekSurplus) {
            throw new UserException([
                'msg' => '超过本周剩余提现额度',
                'errorCode' => 30012,
            ]);
        }
        Db::startTrans();
        try {

            $order = WhWithdraw::create([
                'user_id' => $uid,
                'money' => $money,
                'type' => UserDegreeEnum::JingYing,
                'order_sn' => self::makeOrderNo(),
                'open_bank_name' => $userInfo->open_bank_name,
                'shuangqian_bank_code' => $userInfo->shuangqian_bank_code,
                'bank_card_code' => $userInfo->bank_card_code,
                'bank_card_type' => 1,
            ]);
            $singPay = new \Paid($order);
            $result = $singPay->singlePaid();
            //提交双乾代付接口
            if ($result == 'success') {
                Db::name('wh_user')->where(['id' => $uid, 'degree' => UserDegreeEnum::JingYing])->dec('pay_surplus', $money)->inc('wait_pay_surplus', $money)->update();
                WhWithdraw::update([
                    'id' => $order->id,
                    'shuangqian_status' => 1,
                ]);
                $msg = '提现申请成功';

            } else {
                WhWithdraw::update([
                    'id' => $order->id,
                    'shuangqian_status' => 2,
                    'status' => 3,
                    'fail_reason' => '异常',
                ]);
                $msg = '提现申请异常';
            }
            Db::commit();
        } catch(Exception $ex) {
            Db::rollback();
            throw $ex;
        }

        throw new SuccessMessage([
            'msg' => $msg,
        ]);


    }

    private static function makeOrderNo()
    {
        $yCode = array('A', 'B', 'C', 'E', 'F','H', 'K', 'M', 'N', 'R');

        $orderSn = $yCode[intval(date('Y')) - 2018] . strtoupper(dechex(date('m'))) . date(
                'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
                '%02d', rand(0, 99));
        $order = WhWithdraw::checkOrderByOrderSn($orderSn);
        if ($order) {
            return self::makeOrderNo();
        } else {
            return $orderSn;
        }
    }



}