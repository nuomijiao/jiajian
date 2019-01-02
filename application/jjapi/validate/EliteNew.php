<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2019/1/2
 * Time: 14:39
 */

namespace app\jjapi\validate;


class EliteNew extends BaseValidate
{
    protected $rule = [
        'fullname' => 'require|chs',
        'card_number' => 'require|isCardNumber',
        'mobile' => 'require|isMobile',
        'province_id' => 'require|isPositiveInteger',
        'city_id' => 'require|isPositiveInteger',
        'district_id' => 'require|isPositiveInteger',
        'address' => 'require',
        'card_img_pos' => 'require',
        'card_img_neg' => 'require',
        'bank_card_number' => 'require',
        'bank_card_user_name' => 'require|chs',
        'bank_card_mobile' => 'require|isMobile',
        'bank_card_area' => 'require',
        'bank_business_hall' => 'require',
        'hand_card_img' => 'require',
        'zizhi_img' => 'require',
        'bank_card_img' => 'require',
        'company_id_number' => 'alphaNum',
    ];

    protected $message = [
        'fullname' => '姓名必须为汉字',
        'card_number' => '身份证号格式不正确',
        'mobile' => '手机号格式不正确',
        'province_id' => '省id必须为正整数',
        'city_id' => '市id必须为正整数',
        'district_id' => '区id必须为正整数',
        'address' => '详细地址不能为空',
        'card_img_pos' => '请上传身份证正面照片',
        'card_img_neg' => '请上传身份证反面照片',
        'bank_card_number' => '银行卡号不能为空',
        'bank_card_user_name' => '银行卡户主姓名必须为汉字',
        'bank_card_mobile' => '银行卡预留手机号格式不正确',
        'bank_card_area' => '银行开户行地区不能为空',
        'bank_business_hall' => '开户行名称不能为空',
        'hand_card_img' => '请上传手持身份证照片',
        'zizhi_img' => '请上传资质照片',
        'bank_card_img' => '请上传银行卡正面照片',
        'company_id_number' => '企业识别码为字母和汉字',
    ];


    public function isCardNumber($value) {
        // 只能是18位
        if(strlen($value)!=18){
            return false;
        }
        // 取出本体码
        $idCard_base = substr($value, 0, 17);
        // 取出校验码
        $verify_code = substr($value, 17, 1);
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        // 校验码对应值
        $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        // 根据前17位计算校验码
        $total = 0;
        for($i=0; $i<17; $i++){
            $total += substr($idCard_base, $i, 1)*$factor[$i];
        }
        // 取模
        $mod = $total % 11;
        // 比较校验码
        if($verify_code == $verify_code_list[$mod]){
            return true;
        }else{
            return false;
        }
    }
}