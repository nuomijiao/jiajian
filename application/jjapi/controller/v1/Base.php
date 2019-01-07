<?php

/**
 * 基础过滤器
 * Auther: wanggaoqi
 * Date: 2018/1/4
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use think\Db;
use app\jjapi\service\Token;
use app\jjapi\model\Auth;
use app\lib\exception\UserException;

// 生产环境下 此处应验证token等 是否登录
class Base extends Controller
{
    /**
     * 过滤器
     */
    public function _initialize()
    {
        if (Request::instance()->isPost())
        {
            // $this->uid = 1;
            $this->uid = Token::getCurrentUid();
        }
        else
        {
            return json([
                'errcode' => 201,
                'errmsg'  => 'illegal request',
            ]);
        }
    }

}