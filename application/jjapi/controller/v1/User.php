<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 17:18
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\WhUser;
use app\jjapi\service\Picture;
use app\jjapi\service\Token;
use app\jjapi\validate\UserInfo;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;

class User extends BaseController
{
    public function getUserInfo()
    {
        $uid = Token::getCurrentUid();
        $userInfo = WhUser::get($uid);
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

}