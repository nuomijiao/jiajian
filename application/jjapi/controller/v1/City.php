<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 14:17
 */

namespace app\jjapi\controller\v1;


use app\jjapi\controller\BaseController;
use app\jjapi\model\Area;

class City extends BaseController
{
    public function getProvince()
    {
        $provinceList = Area::getProvince();
        return $this->jjreturn($provinceList);
    }

    public function getCityByProvince($id = '')
    {

    }

    public function getDistrictByCity($id = '')
    {

    }
}