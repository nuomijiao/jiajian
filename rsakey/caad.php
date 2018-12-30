<?php     

    get_enquiry();   
    /**
     * 获取询价结果
     * @param $TypeCode  查询类型  1:系统查询  2:人工查询 (必传)
     * @param $AreaCode  区划代码(国标码)(必传)
     * @param $PropertyType  物业类型ID(必传)
     * @param $ProjectId  小区ID
     * @param $FloorId 楼栋ID
     * @param $BuildArea 建筑面积(必传)
     * @param string $Address  地址
     * @param string $CurrentFloor 所在楼层
     * @param string $TotalFloor 总楼层
     * @return mixed
     *
            ["code"]=>"000"
            ["msg"]=>"请求成功"
            ["data"]=>
                ["PropertyType"]=>""
                ["DataList"]=>""
                ["TotalPrice"]=>"229000"  //总价
                ["UnitPrice"]=>"1908" //单价
                ["FileCode"]=>""
     */
    function get_enquiry(){
        $key="a1b20c2a3f10c235962408d37e0206d5";
        $url="http://172.16.2.1:8077/api/webapiaccess/PublicTestEnquiry/Enquiry";
        $aes_key="slpgenquirysvcxx";
        $public_key=openssl_pkey_get_public(file_get_contents('rsa_public_key.pem'));
        $private_key= openssl_pkey_get_private(file_get_contents('rsa_private_key.pem'));
        $params['CaseId'] =get_caseid();
        $params['Key'] = $key;
        $params['TypeCode'] = "4";
        $params['AreaCode'] ="410202";
        $params['PropertyType'] = "92";
        $params['ThirdProperty'] = "93";
        $params['ProjectName'] ="香樟公寓";
        $params['Address'] ="香樟公寓2期2栋";
        $params['BuildArea'] ="120";
        //原文加密
        $encrypt_content = encrypt(json_encode($params),$aes_key);
        //密文加签
        $signature = sign($encrypt_content,$private_key);
        //构造请求数据
        $post_params = json_encode(["Data" => join(",",[$encrypt_content,$signature])]);
        //发送请求
        $result = post($url,$post_params);       
        //解析数据
        $res = explode(",", trim($result));
        if(count($res)!=2 )
        {            
            echo "返回数据不对\n";
            echo $result;
            return ;
        }
        $result_encrpyt_content = isset($res[0]) ? $res[0] : '';
        $result_signature = isset($res[1]) ? $res[1] : '';
        //echo $result_encrpyt_content;
        //echo $result_signature;
        //验签
        $verify_status = verify($result_encrpyt_content, $result_signature,$public_key);
        if($verify_status==1){
            echo "验签成功\n";
            //解密数据
            echo decrypt($result_encrpyt_content,$aes_key);
        }
        else{
            echo "验签失败\n";
        }
    }

    /**
     * 加密
     * @param string $content 原文
     * @param string $key 密钥的字符串
     * @return string  加密后的字符串
     */
    function encrypt($data,$key){
        return openssl_encrypt($data , 'AES-128-ECB',$key);
    }
    /**
     * 解密
     * @param string $data 密文
     * @param string $key 密钥的字符串
     * @return string  解密后的字符串
     */
    function decrypt($data,$key){
        return openssl_decrypt($data, 'AES-128-ECB', $key);
    }

    /**
     * 加签
     * @param string $content 原文
     * @param string $private_key 私钥的字符串
     * @return string 签文的字符串
     */
    function sign($content,$private_key){
        $signature = '';
        openssl_sign($content,$signature,$private_key);
        return base64_encode($signature);
    }

    /**
     * 验签
     * @param string $content 原文
     * @param string $signature 签文
     * @param string $public_key 公钥的字符串
     * @return int 1 表示成功，其他表示失败
     */
    function verify($content,$signature,$public_key){
        return openssl_verify(trim($content,'"'),base64_decode(trim($signature,'"')),$public_key);
    }

     /**
     * 发送请求
     * @param string $url 请求地址
     * @param string $post_data 请求数据
     * @return mixed  返回的数据
     */
    function post($url, $post_data)
    {
        $useragent = 'PHP5 Client 1.0 (curl) ' . phpversion();
        $ch = curl_init();
        $this_header = array("content-type: application/json; charset=UTF-8");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    /**
     * 生成唯一单据id
     * todo 这里需要自己按照规则定义
     * @return string 
     */
    function get_caseid(){
       return date("YmdHis").str_replace(".","",uniqid("",true));;
    }

?> 