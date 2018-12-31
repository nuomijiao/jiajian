<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 14:10
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\service\Picture;
use app\jjapi\service\Token;

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
        return $this->xdreturn($img);
    }
}