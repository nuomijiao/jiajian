<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 15:15
 */

namespace app\jjapi\model;


class WhShare extends BaseModel
{
    protected $autoWriteTimestamp = true;

    public function user()
    {
        return $this->belongsTo('WhUser', 'user_id', 'id');
    }

    public function allImg()
    {
        return $this->hasMany('WhShareImg', 'share_id', 'id');
    }

    public static function getShareList($type, $page, $size)
    {
        return self::with(['allImg'])->with([
            'user' => function($query) {
                $query->field(['id', 'fullname', 'head_img']);
            }
        ])->where('type', '=', $type)->where('is_show', '=', 1)->order('create_time', 'desc')->paginate($size, true, ['page' => $page]);
    }

    public static function getShareDetail($id)
    {
        return self::with(['allImg'])->where('id', '=', $id)->where('is_show', '=', 1)->find();
    }

}