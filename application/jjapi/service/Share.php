<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/31
 * Time: 15:11
 */

namespace app\jjapi\service;


use app\jjapi\model\WhShare;
use app\jjapi\model\WhShareImg;
use app\jjapi\model\WhTempImgs;
use think\Db;
use think\Exception;

class Share
{
    public static function releaseShare($uid, $title, $content, $ids, $type) {
        //获取临时图片文件夹相关图片
        //更新到正式数据表
        //删除临时图片数据表数据
        //移动图片到正式文件夹
        $share_imgs = WhTempImgs::whereIn('id',$ids)->select()->toArray();
        $new_share_imgs = [];
        $new_imgs = [];
        $new_ids = '';
        Db::startTrans();
        try {
            $share = WhShare::create([
                'title' => $title,
                'content' => $content,
                'type' => $type,
                'user_id' => $uid,
            ]);
            $share_id = $share->id;
            foreach ($share_imgs as $key => $value) {
                if ($value['user_id'] == $uid) {
                    array_push($new_share_imgs, $value);
                    $new_ids .= $value['id'].",";
                    $data = [
                        'share_id' => $share_id,
                        'url' => DS.'images'.DS.$value['img_name'],
                    ];
                    WhShareImg::create($data);
                }
            }
            $new_ids = rtrim($new_ids, ',');
            WhTempImgs::destroy($new_ids);
            Db::commit();
            foreach ($new_share_imgs as $key => $value) {
                if (!in_array(DS."images".DS.$value['img_name'], $new_imgs)) {
                    if (file_exists(ROOT_PATH.'public'.$value['img_url'])) {
                        rename(ROOT_PATH.'public'.$value['img_url'], ROOT_PATH.'public'.DS.'images'.DS.$value['img_name']);
                    }
                }
                array_push($new_imgs, DS."images".DS.$value['img_name']);
            }
            $data = [
                'title' => $title,
                'content' => $content,
                'img' => $new_imgs,
            ];
            return $data;
        } catch(Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }
}