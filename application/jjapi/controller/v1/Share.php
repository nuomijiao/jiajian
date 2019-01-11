<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 14:10
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\WhShare;
use app\jjapi\model\WhTempImgs;
use app\jjapi\service\Picture;
use app\jjapi\service\Token;
use app\jjapi\validate\IDMustBePositiveInt;
use app\jjapi\validate\ShareNew;
use app\jjapi\validate\TypeMustBePositiveInt;
use app\lib\enum\ShareTypeEnum;
use app\jjapi\service\Share as ShareService;
use app\lib\exception\ShareException;

class Share extends BaseController
{

    //分享图片上传
    public function addShareImg()
    {
        $uid = Token::getCurrentUid();
        $share_img = $this->request->file('share_img');
        $data = Picture::uploadImg($share_img, 'share_tmp_img');
        //存到临时图片文件夹
        $img = WhTempImgs::create([
            'img_url' => $data['url'],
            'img_name' => $data['filename'],
            'user_id' => $uid,
        ]);
        return $this->jjreturn($img);
    }

    //上传分享内容。客户或产品
    public function addShare($type = ShareTypeEnum::Product)
    {
        (new TypeMustBePositiveInt())->goCheck();
        $request = (new ShareNew())->goCheck();
        $title = $request->param('title');
        $content = $request->param('content');
        $ids = $request->param('ids');
        $mobile = $request->param('mobile');
        $uid = Token::getCurrentUid();
        $data = ShareService::releaseShare($uid,$title,$content,$ids, $mobile, $type);
        return $this->jjreturn($data);
    }

    public function getShareList($type = ShareTypeEnum::Product, $page = 1, $size = 10)
    {
        (new TypeMustBePositiveInt())->goCheck();
        $pagingShareList = WhShare::getShareList($type, $page, $size);
        if ($pagingShareList->isEmpty()) {
            throw new ShareException([
                'msg' => '共享信息已见底线',
                'errorCode' => 60001,
            ]);
        }
        $data = $pagingShareList->toArray();
        return json([
            'error_code' => 'Success',
            'data' => $data,
            'current_page' => $pagingShareList->getCurrentPage(),
        ]);

    }

    public function getShareDetail($id)
    {
        (new IDMustBePositiveInt())->goCheck();
        $shareDetail = WhShare::getShareDetail($id);
        if (!$shareDetail) {
            throw new ShareException();
        }
        return $this->jjreturn($shareDetail);
    }
}