<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 16:43
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\Admin;
use app\jjapi\model\WhAccountApply;
use app\jjapi\model\WhEliteAccount;
use app\jjapi\model\WhUser;
use app\jjapi\service\Token;
use app\jjapi\validate\AccountNew;
use app\lib\enum\AccountApplyStatusEnum;
use app\lib\enum\AccountApplyTypeEnum;
use app\lib\enum\RoleEnum;
use app\lib\enum\UserDegreeEnum;
use app\lib\exception\OpenAccountException;
use app\lib\exception\SuccessMessage;
use think\Db;
use think\Exception;

class AccountApply extends BaseController
{
    public function addApply($type = AccountApplyTypeEnum::Company)
    {
        $validate = new AccountNew();
        $uid = Token::getCurrentUid();

        if (AccountApplyTypeEnum::Company == $type) {
            //检查有没有申请企业和精英
            $degree = WhUser::getUserDegree($uid);
            if ($degree) {
                throw new OpenAccountException([
                    'msg' => '员工账户或已申请过其他账户',
                    'errorCode' => 70000,
                ]);
            }
        }

        $request = $validate->goCheck();
        $dataArray = $validate->getDataByRule($request->post());
        $dataArray['user_id'] = $uid;
        $dataArray['status'] = AccountApplyStatusEnum::Wait;


        if (AccountApplyTypeEnum::Company == $type) {

            $userData = ['id'=>$uid, 'is_main_user' => 1, 'status' => AccountApplyStatusEnum::Wait];

            if (!empty($dataArray['alliance_id_number'])) {
                $alliance = Admin::where(['id_code' => $dataArray['alliance_id_number'], 'role_id' => RoleEnum::Alliance])->find();
                if (!$alliance) {
                    throw new OpenAccountException([
                        'msg' => '加盟商识别码有误',
                        'errorCode' => 70008,
                    ]);
                }
                $dataArray['alliance_id'] = $alliance->id;
                $dataArray['alliance_name'] = $alliance->nickname;
            }
            $userData['degree'] = UserDegreeEnum::QiYe;
            Db::startTrans();
            try {
                WhAccountApply::create($dataArray);
                WhUser::update($userData);
                Db::commit();
            } catch(Exception $ex) {
                Db::rollback();
                throw $ex;
            }

        } elseif (AccountApplyTypeEnum::Alliance == $type) {

            WhAccountApply::create($dataArray);

        }

        throw new SuccessMessage([
            'msg' => '提交成功，请等待审核'
        ]);
    }
}