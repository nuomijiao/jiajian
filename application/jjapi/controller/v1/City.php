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
use app\jjapi\validate\IDMustBePositiveInt;

class City extends BaseController
{
    public function getProvince()
    {
        $provinceList = Area::getProvince();
        return $this->jjreturn($provinceList);
    }

    public function getCityByProvince($id = '')
    {
        (new IDMustBePositiveInt())->goCheck();
        $cityList = Area::getCityByProvince($id);
        return $this->jjreturn($cityList);
    }

    public function getDistrictByCity($id = '')
    {
        (new IDMustBePositiveInt())->goCheck();
        $districtList = Area::getDistrictByCity($id);
        return $districtList;
    }
}