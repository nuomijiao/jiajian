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

class Base extends Controller
{
    // 基础过滤器
    public function _initialize()
    {
        if($_SERVER['SERVER_NAME'] === 'jj.888.com')    // 测试环境 
        {   
            $this->uid = 1;
        }
        else                                            // 生产环境
        {
            if(Request::instance()->isPost())
            {
                $this->uid = Token::getCurrentUid();
            }
            else
            {
                echo json_encode([
                    'errcode' => 201,
                    'errmsg'  => 'illegal request',
                ]);
    
                die();
            }
        }
    }
}