<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 16:10
 */

namespace app\jjapi\model;


use app\lib\enum\RoleEnum;

class Admin extends BaseModel
{
    public function getCompanyByCode($code)
    {
        return self::where(['id_code'=>$code, 'role_id'=>RoleEnum::Company])->find();
    }
}