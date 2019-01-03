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
use app\jjapi\service\Token;
use app\jjapi\validate\AccountNew;
use app\lib\enum\AccountApplyStatusEnum;
use app\lib\enum\AccountApplyTypeEnum;
use app\lib\enum\RoleEnum;
use app\lib\exception\OpenAccountException;
use app\lib\exception\SuccessMessage;

class AccountApply extends BaseController
{
    public function addApply($type = AccountApplyTypeEnum::Company)
    {
        $validate = new AccountNew();
        $uid = Token::getCurrentUid();
        $type = $this->request->post('type');
        $apply = WhAccountApply::checkApplyExist($uid, $type);
        if ($type == AccountApplyTypeEnum::Company) {
            if ($apply->status == AccountApplyStatusEnum::Wait) {
                throw new OpenAccountException([
                    'msg' => '已申请企业版，请等待审核',
                    'errorCode' => 70003,
                ]);
            } elseif ($apply->status == AccountApplyStatusEnum::Pass) {
                throw new OpenAccountException([
                    'msg' => '已申请企业版，通过审核，请勿重复申请',
                    'errorCode' => 70004,
                ]);
            }
        } elseif ($type == AccountApplyTypeEnum::Alliance) {
            if ($apply->status == AccountApplyStatusEnum::Wait) {
                throw new OpenAccountException([
                    'msg' => '已申请加盟商版，请等待审核',
                    'errorCode' => 70005,
                ]);
            } elseif ($apply->status == AccountApplyStatusEnum::Pass) {
                throw new OpenAccountException([
                    'msg' => '已申请加盟商版，通过审核，请勿重复申请',
                    'errorCode' => 70006,
                ]);
            }
        }
        $request = $validate->goCheck();
        $dataArray = $validate->getDataByRule($request->post());
        $dataArray['user_id'] = $uid;
        if (!empty($dataArray['alliance_id_number'])) {
            $dataArray['alliance_id'] = Admin::where(['id_code' => $dataArray['alliance_id_number'], 'role_id' => RoleEnum::Alliance])->value('id');
        }
        $dataArray['status'] = AccountApplyStatusEnum::Wait;
        WhAccountApply::create($dataArray);
        throw new SuccessMessage([
            'msg' => '提交成功，请等待审核'
        ]);
    }
}