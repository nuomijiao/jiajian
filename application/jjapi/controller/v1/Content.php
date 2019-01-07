<?php

/**
 * 发贴接口
 * Auther: wanggaoqi
 * Date: 2018/12/31
 */

namespace app\jjapi\controller\v1;

use think\Controller;
use think\Request;
use think\Config;
use think\Db;
use app\lib\exception\UserException;

// 此处应验证token等 是否登录
class Content extends Controller{

    /**
     * 查询帖子
     */
    public function list()
    {
        $data = Db::name('content')
            ->limit(50)
            ->order('id desc')
            ->select();

        return json($data);
    }

    /**
     * 发布帖子
     */
    public function create()
    {
        $postData = Request::instance()->post();

        // 帖子图片路径
        $paths = Request::instance()->post('paths', '');

        $data = [
            'title'     => @$postData['title'],
            'content'   => @$postData['content'],
            'paths'     => $paths,
            'createtime'=> time(),
        ];

        $bool = Db::name('content')->insert($data);

        if($bool)
        {
            $json = json([
                'errcode' => 200,
                'errmsg'  => '发布成功',
            ]);
        }
        else
        {
            $json = json([
                'errcode' => 204,
                'errmsg'  => '发布失败，请稍后再试',
            ]);
        }

        return $json;
    }

    /**
     * 编辑帖子
     */
    public function update()
    {
        $postData = Request::instance()->post();

        // 帖子图片路径
        $paths = Request::instance()->post('paths', '');

        $data = [
            'title'     => @$postData['title'],
            'content'   => @$postData['content'],
            'paths'     => $paths,
        ];

        $bool = Db::name('wh_content')
                ->where('id', @$postData['id'])
                ->update($data);

        if($bool)
        {
            $json = json([
                'errcode' => 200,
                'errmsg'  => '编辑成功',
            ]);
        }
        else
        {
            $json = json([
                'errcode' => 204,
                'errmsg'  => '操作失败，请稍后再试',
            ]);
        }

        return $json;
    }
}