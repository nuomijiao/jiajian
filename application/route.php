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
//退出登录
Route::post('api/:version/logout', 'jjapi/:version.LogAndReg/logout');
//判断是否登录
Route::get('api/:version/islogin', 'jjapi/:version.LogAndReg/isLogin');

//获取banner
Route::get('api/:version/getbanner/:type', 'jjapi/:version.Banner/getBanner');

//共享上传图片
Route::post('api/:version/addshareimg', 'jjapi/:version.Share/addShareImg');
//共享上传内容
Route::post('api/:version/addshare', 'jjapi/:version.Share/addShare');
//获取共享列表
Route::get('api/:version/getsharelist/:type', 'jjapi/:version.Share/getShareList');
//获取共享详情
Route::get('api/:version/getsharedetail/:id', 'jjapi/:version.Share/getShareDetail');


//获取用户信息
Route::get('api/:version/getuserinfo', 'jjapi/:version.User/getUserInfo');
//修改头像
Route::post('api/:version/modifyheadimg', 'jjapi/:version.User/modifyHeadImg');
//修改信息
Route::post('api/:version/saveinfo', 'jjapi/:version.User/saveInfo');

//申请精英上传图片
Route::post('api/:version/addeliteimg', 'jjapi/:version.Elite/addImg');
//申请精英上传数据
Route::post('api/:version/addelite', 'jjapi/:version.Elite/addElite');
//申请加盟商
Route::post('api/:version/addapply', 'jjapi/:version.AccountApply/addApply');

//充值
Route::post('api/:version/recharge', 'jjapi/:version.Recharge/rechargeOrder');
//支付宝回调
Route::post('api/:version/alipaynotify', 'jjapi/:version.Recharge/alipayNotify');
//微信回调
Route::post('api/:version/wxpaynotify', 'jjapi/:verison.Recharge/wxpayNotify');

//获取省市区
Route::get('api/:version/getprovince', 'jjapi/:version.City/getProvince');
Route::get('api/:version/getcitybyprovince/:id', 'jjapi/:version.City/getCityByProvince');
Route::get('api/:version/getdistrictbycity/:id', 'jjapi/:version.City/getDistrictByCity');
