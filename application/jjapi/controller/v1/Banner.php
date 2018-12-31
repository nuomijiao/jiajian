<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 10:46
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\WhBanner;
use app\jjapi\validate\TypeMustBePositiveInt;
use app\lib\enum\BannerLocationEnum;
use app\lib\exception\BannerException;

class Banner extends BaseController
{
    public function getBanner($type = BannerLocationEnum::Index)
    {
        (new TypeMustBePositiveInt())->goCheck();
        $banner = WhBanner::getBanner($type);
        if ($banner->isEmpty()) {
            throw new BannerException();
        }
        return $this->jjreturn($banner);
    }
}