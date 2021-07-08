<?php

namespace crmeb\services\minpay\cert;


class RSA
{
    private $config;

    CONST PRI_KEY = __DIR__ ."/private_key.key";

    function __construct()
    {
        $this->config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            'config' => __DIR__ . '/openssl.cnf'
        );
    }

    //创建公钥密钥
    public function createKey($bits = 2048, $timeout = false, $partial = array())
    {
        $res = openssl_pkey_new($this->config);

        openssl_pkey_export($res, $private_key, null, $this->config);

        $details = openssl_pkey_get_details($res);
        $public_key = $details["key"];
        return array('public_key' => $public_key, 'private_key' => $private_key);
    }


    //公钥加密
    public function encrypt_by_public_base64($data, $public_key)
    {
        if (!$data || !$public_key) {
            return FALSE;
        }
        $crypted = '';
        $public_key = openssl_get_publickey($public_key);
        for ($i = 0; $i < strlen($data); $i += 117) {
            $src = substr($data, $i, 117);
            $ret = openssl_public_encrypt($src, $out, $public_key);
            $crypted .= $out;
        }
        return base64_encode($crypted);
    }

    //私钥解密
    public function decrypt_by_private($crypted, $private_key)
    {
        if (!$crypted || !$private_key) {
            return FALSE;
        }
        $out_plain = '';
        $private_key = openssl_get_privatekey($private_key);
        for ($i = 0; $i < strlen($crypted); $i += 256) {
            $src = substr($crypted, $i, 256);
            $ret = openssl_private_decrypt($src, $out, $private_key);
            //var_dump($private_key);
            $out_plain .= $out;
        }

        return $out_plain;
    }

    //私钥加签
    public function sign_by_private_key_base64_en($data, $private_key)
    {
        if (!$data || !$private_key) {
            return FALSE;
        }
        $private_key = openssl_get_privatekey($private_key);
        openssl_sign($data, $sign, $private_key);
        openssl_free_key($private_key);
        $sign = base64_encode($sign);//最终的签名
        return $sign;
    }

    //公钥验签
    public function verify_by_public_key($original_str, $sign, $public_key)
    {
        if (!$original_str || !$sign || !$public_key) {
            return FALSE;
        }
        $public_key = openssl_get_publickey($public_key);
        $sign = base64_decode($sign);//得到的签名
        $result = (bool)openssl_verify($original_str, $sign, $public_key);
        openssl_free_key($public_key);
        return $result;

    }

    public function curl_post($url, $post_data = '', $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, 1);

        if ($post_data) {
            // if ($files = $_FILES) {
            //     $index = 1;
            //     foreach ($files as $v) {
            //         $post_data['file'.$index] = new \CURLFile($v['tmp_name'],'file',$v['name']);
            //         $index++;
            //     }
            // }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: multipart/form-data; charset=utf-8'
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        // print_r($file_contents);
        return json_decode($file_contents, true);
    }

    /*  加密请求数据
     *  RSA加密和加签
     *  $dataStr                要加密的json字符串
     *  $server_public_key      服务器公钥
     *
     * */

    public function enRSA($dataStr, $server_public_key, $private = self::PRI_KEY )
    {

        $rsa = new RSA();
        //加密
        $en = $rsa->encrypt_by_public_base64($dataStr, $server_public_key);
        $return['rsa'] = $en;

        //获取用户密钥加密，不能泄露
        $user_private_key = file_get_contents($private);
        //加签
        $sign = $rsa->sign_by_private_key_base64_en($en, $user_private_key);
        $return['sign'] = $sign;
        return $return;
    }

    /*  解密返回数据
     *  RSA验签和解密
     *  $rsa                    要解密的字符串
     *  $sign                   验签字符串
     *  $server_public_key      服务器公钥
     *
     * */
    public function deRSA($rsa, $sign, $server_public_key, $private = self::PRI_KEY)
    {
        $rsaObj = new RSA();
        $flag = $rsaObj->verify_by_public_key($rsa, $sign, $server_public_key);
        if ($flag) {
            //获取用户密钥加密，不能泄露
            $user_private_key = file_get_contents($private);
            //解密
            $plaintext = $rsaObj->decrypt_by_private(base64_decode($rsa), $user_private_key);
            if (!$plaintext) {
                return "数据解密失败";
            }
            return json_decode($plaintext, true);
        } else {
            return "数据验签失败";
        }
    }
}