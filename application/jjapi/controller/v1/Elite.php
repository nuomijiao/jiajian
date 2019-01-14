<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 14:29
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\Admin;
use app\jjapi\model\WhAccountApply;
use app\jjapi\model\WhEliteAccount;
use app\jjapi\model\WhUser;
use app\jjapi\service\Picture;
use app\jjapi\service\Token;
use app\jjapi\validate\EliteNew;
use app\lib\enum\AccountApplyStatusEnum;
use app\lib\enum\RoleEnum;
use app\lib\enum\UserDegreeEnum;
use app\lib\exception\OpenAccountException;
use app\lib\exception\SuccessMessage;
use think\Db;
use think\Exception;

class Elite extends BaseController
{
    public function addImg()
    {
        $uid = Token::getCurrentUid();
        $img = $this->request->file('img');
        $data = Picture::uploadImg($img, 'account_img');
        //存到临时图片文件夹
        $img = [
            'img_url' => $data['url'],
            'img_name' => $data['filename'],
        ];
        return $this->jjreturn($img);
    }

    public function addElite()
    {
        $validate = new EliteNew();
        $uid = Token::getCurrentUid();
        //检查有没有申请企业和加盟商
//        $apply = WhAccountApply::checkApplyExist($uid);
//        if ($apply && $apply->status == AccountApplyStatusEnum::Pass) {
//            throw new OpenAccountException([
//                'msg' => '已申请过企业或加盟商版通过审核，不能再申请精英版',
//                'errorCode' => 70000,
//            ]);
//        }
//        $elite = WhEliteAccount::checkEliteExist($uid);
//        if ($elite && $elite->status == AccountApplyStatusEnum::Wait) {
//            throw new OpenAccountException([
//                'msg' => '已申请精英版，请等待审核',
//                'errorCode' => 70000,
//            ]);
//        } elseif ($elite && $elite->status == AccountApplyStatusEnum::Pass) {
//            throw new OpenAccountException([
//                'msg' => '已申请精英版，通过审核，请勿重复申请',
//                'errorCode' => 70000,
//            ]);
//        }

        //检查有没有申请企业和加盟商和精英
        $degree = WhUser::getUserDegree($uid);
        if ($degree) {
            throw new OpenAccountException([
                'msg' => '员工账户或已申请过其他账户',
                'errorCode' => 70000,
            ]);
        }

        $request = $validate->goCheck();
        $dataArray = $validate->getDataByRule($request->post());

        $dataArray['user_id'] = $uid;

//        if (!empty($dataArray['company_id_number'])) {
//            $dataArray['company_id'] = Admin::where(['id_code' => $dataArray['company_id_number'], 'role_id' => RoleEnum::Company])->value('id');
//            if (!$dataArray['company_id']) {
//                throw new OpenAccountException([
//                    'msg' => '企业识别码有误',
//                    'errorCode' => 70007,
//                ]);
//            }
//        }

        $dataArray['status'] = AccountApplyStatusEnum::Wait;

        Db::startTrans();
        try {
            WhEliteAccount::create($dataArray);
            WhUser::update([
                'id' => $uid,
                'degree' => UserDegreeEnum::JingYing,
                'is_main_user' => 1,
            ]);
            Db::commit();

        } catch(Exception $ex) {
            Db::rollback();
            throw $ex;
        }

        throw new SuccessMessage([
            'msg' => '提交成功，请等待审核'
        ]);
    }
}