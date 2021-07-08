<?php


namespace crmeb\services;

use crmeb\services\minpay\cert\RSA;
use FormBuilder\Response;
use think\Exception;
use think\facade\Log;

class MinPayService
{
    //存储公钥路径                    用于请求参数
    const PUB_KEY = __DIR__ . '/minpay/cert/public_key.key';
    //存储密钥路径
    const PRI_KEY = __DIR__ . '/minpay/cert/private_key.key';
    const SER_KEY = __DIR__ . '/minpay/cert/server_public.key';
    // 存储服务器公钥路径     用于加密和验签
    const SER_KEY_API = 'https://5minpay.com/api/v1/getPublicKey';
    // 支付api地址
    const PAY_API = 'https://5minpay.com/api/v1/pay';
    // 查询接口
    const CHECK_API = 'https://5minpay.com/api/v1/checkOrder';
    // 支付配置
    public static $config;

    protected $rsa;

    public function __construct()
    {
        $this->rsa = new RSA();
        self::$config = [
            'pay_type'      => 9,
            'service'       => 'pay_bank_card',
            'agentPhone'    => '092222222222',
//            'callback'      => request()->domain() . '/api/notice/minpay',
            'callback'      => 'https://app.kaolabinfen.com/api/notice/minpay',
        ];
    }

    public static function create()
    {
        return new self;
    }

    /**
     * 5minpay银行支付
     * @Author : UJB
     * @Date : 2021/3/23
     * @param array $params
     * @return array
     */
    public function doPay(array $params)
    {
        $params = json_encode($params);
        $server = file_get_contents(self::SER_KEY);

        $data = $this->rsa->enRSA($params,$server);
        $data['user_public'] = base64_encode(file_get_contents(self::PUB_KEY));//用户公钥
        $data['type'] = "form";//返回方式
        $data['is_enctypt'] = "2";//是否加密
        $data['version'] = "v1";//
        $data['url'] = self::PAY_API;
        return $data;

    }

    /**
     * minpay支付回调
     * @Author : UJB
     * @Date : 2021/3/24
     * @param array $post
     * @return bool
     */
    public function minpayNotify(array $post):bool
    {
        if ($post['encryption_type'] == 2) {

            $server_public = file_get_contents(self::SER_KEY);
            $post = $this->rsa->deRSA($post['rsa'], $post['sign'],$server_public );
        }
        if ($post['pay_status'] == 'S') {
            event('pay_success_user_recharge', ['order_sn' => $post['out_order_number'], ['data' => $post]]);
            Log::info('用户充值 订单号:' . $post['out_order_number']);
            return true;
        }
        return false;
    }

    public function checkOrder (string $order_number)
    {
        $arr = [
            'agentPhone' => self::$config['agentPhone'],
            'out_order_number' => $order_number
        ];

        $server = file_get_contents(self::SER_KEY);
        $data = $this->rsa->enRSA(json_encode($arr),$server);//加密数据
        $data['user_public'] = base64_encode(file_get_contents(self::PUB_KEY));//用户公钥
        $data['is_enctypt'] = "2";//是否加密
        $res = $this->rsa->curl_post(self::CHECK_API,$data);

        if ($res['encryption_type'] == 2)
            $res = $this->rsa->deRSA($res['rsa'],$res['sign'],$server);

        return $res;

    }


}