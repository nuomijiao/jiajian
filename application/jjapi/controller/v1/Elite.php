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
use app\jjapi\model\WhEliteAccount;
use app\jjapi\service\Picture;
use app\jjapi\service\Token;
use app\jjapi\validate\EliteNew;
use app\lib\enum\AccountApplyStatusEnum;
use app\lib\enum\RoleEnum;
use app\lib\exception\SuccessMessage;

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
        $request = $validate->goCheck();
        $dataArray = $validate->getDataByRule($request->post());
        $uid = Token::getCurrentUid();
        $dataArray['user_id'] = $uid;
        if (!empty($dataArray['company_id_number'])) {
            $dataArray['company_id'] = Admin::where(['id_code' => $dataArray['company_id_number'], 'role_id' => RoleEnum::Company])->value('id');
        }
        $dataArray['status'] = AccountApplyStatusEnum::Wait;
        WhEliteAccount::create($dataArray);
        throw new SuccessMessage([
            'msg' => '提交成功，请等待审核'
        ]);
    }
}