<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

//return [
//    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
//    '__alias__'   => [
//    ],
//    //变量规则
//    '__pattern__' => [
//    ],
////        域名绑定到模块
////        '__domain__'  => [
////            'admin' => 'admin',
////            'api'   => 'api',
////        ],
//];

use think\Route;


//发送验证码
Route::post('api/:version/sendsms', 'jjapi/:version.Sms/sendSms');

//注册
Route::post('api/:version/register', 'jjapi/:version.LogAndReg/register');
//登录
Route::post('api/:version/login', 'jjapi/:version.LogAndReg/login');
//忘记密码
Route::post('api/:version/resetpwd', 'jjapi/:version.LogAndReg/resetPwd');

//获取banner
Route::get('api/:version/getbanner/:type', 'jjapi/:version.Banner/getBanner');

//获取省市区
Route::get('api/:version/getprovince', 'jjapi/:version.City/getProvince');
Route::get('api/:version/getcitybyprovince', 'jjapi/:version.Character/getCityByProvince');
Route::get('api/:version/getdistrictbycity', 'jjapi/:version.Character/getDistrictByCity');


// 签约/认证
Route::post('api/:version/auth', 'jjapi/:version.Pay/auth');
