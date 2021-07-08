<?php

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------


namespace app\controller\api\user;


use crmeb\basic\BaseController;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\user\UserRechargeRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\services\MinPayService;
use crmeb\services\WechatService;
use think\App;
use think\facade\View;
use function GuzzleHttp\Psr7\str;

class UserRecharge extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserRechargeRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function brokerage(UserRepository $userRepository)
    {
        $brokerage = (float)$this->request->param('brokerage');
        if ($brokerage <= 0)
            return app('json')->fail('请输入正确的充值金额!');
        $user = $this->request->userInfo();
        if ($user->brokerage_price < $brokerage)
            return app('json')->fail('剩余可用佣金不足' . $brokerage);
        $config = systemConfig(['recharge_switch', 'balance_func_status']);
        if (!$config['recharge_switch'] || !$config['balance_func_status'])
            return app('json')->fail('余额充值功能已关闭');
        $userRepository->switchBrokerage($user, $brokerage);
        return app('json')->success('转换成功');
    }

    public function recharge(GroupDataRepository $groupDataRepository)
    {
        [$type, $price, $rechargeId] = $this->request->params(['type', 'price', 'recharge_id'], true);
        if (!in_array($type, ['wechat', 'routine', 'h5','alipay']))
            return app('json')->fail('请选择正确的支付方式!');
        $wechatUserId = $this->request->userInfo()['wechat_user_id'];
        if (!$wechatUserId && in_array($type, ['wechat', 'routine']))
            return app('json')->fail('请关联微信' . ($type == 'wechat' ? '公众号' : '小程序') . '!');
        $config = systemConfig(['store_user_min_recharge', 'recharge_switch', 'balance_func_status']);
        if (!$config['recharge_switch'] || !$config['balance_func_status'])
            return app('json')->fail('余额充值功能已关闭');
        if ($rechargeId) {
            if (!intval($rechargeId))
                return app('json')->fail('请选择充值金额!');
            $rule = $groupDataRepository->merGet(intval($rechargeId), 0);
            if (!$rule || !isset($rule['price']) || !isset($rule['give']))
                return app('json')->fail('您选择的充值方式已下架!');
            $give = floatval($rule['give']);
            $price = floatval($rule['price']);
            if ($price <= 0)
                return app('json')->fail('请选择正确的充值金额!');
        } else {
            $price = floatval($price);
            if ($price <= 0)
                return app('json')->fail('请输入正确的充值金额!');
            if ($price < $config['store_user_min_recharge'])
                return app('json')->fail('最低充值' . floatval($config['store_user_min_recharge']));
            $give = 0;
        }
        $recharge = $this->repository->create($this->request->uid(), $price, $give, $type);
        $userRepository = app()->make(WechatUserRepository::class);
        if ($type == 'wechat') {
            $openId = $userRepository->idByOpenId($wechatUserId);
            if (!$openId)
                return app('json')->fail('请关联微信公众号!');
            $data = $this->repository->wxPay($openId, $recharge);
        } else if ($type == 'h5') {
            $data = $this->repository->wxH5Pay($recharge);
        } else if ($type == 'alipay'){
            $data = $this->repository->payAlipay($this->request->userInfo(),$recharge,request()->domain() .'/pages/users/user_payment/index');
        } else {
            $openId = $userRepository->idByRoutineId($wechatUserId);
            if (!$openId)
                return app('json')->fail('请关联微信小程序!');
            $data = $this->repository->jsPay($openId, $recharge);
        }

        return app('json')->success(compact('type', 'data'));
//            return app('json')->success($data);

    }

    /**
     * 使用越南奇葩支付
     * @Author : UJB
     * @Date : 2021/3/24
     * @param GroupDataRepository $groupDataRepository
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function minPay(GroupDataRepository $groupDataRepository)
    {
        // 获取参数 并验证数据
        [$price, $rechargeId,$bank_code] = $this->request->params(['price', 'recharge_id','bank_code'], true);
        $type = 'minpay';
        if (!is_numeric($price))
            return app('json')->fail('请选择正确的充值金额');

        $config = systemConfig(['store_user_min_recharge', 'recharge_switch', 'balance_func_status']);
        if (!$config['recharge_switch'] || !$config['balance_func_status'])
            return app('json')->fail('余额充值功能已关闭');

        if ($rechargeId) {
            if (!intval($rechargeId))
                return app('json')->fail('请选择充值金额!');
            $rule = $groupDataRepository->merGet(intval($rechargeId), 0);
            if (!$rule || !isset($rule['price']) || !isset($rule['give']))
                return app('json')->fail('您选择的充值方式已下架!');
            $give = intval($rule['give']);
            $price = intval($rule['price']);
            if ($price <= 0)
                return app('json')->fail('请选择正确的充值金额!');
        } else {
            $price = intval($price);
            if ($price <= 0)
                return app('json')->fail('请输入正确的充值金额!');
            if ($price < $config['store_user_min_recharge'])
                return app('json')->fail('最低充值' . intval($config['store_user_min_recharge']));
            $give = 0;
        }
        // 生成余额记录
        $recharge = $this->repository->create($this->request->uid(), $price, $give, $type);
        // 支付参数
        $pay_type = MinPayService::$config['pay_type'];
        $service = MinPayService::$config['service'];
        $amount = $recharge['price'];
        $agentPhone = MinPayService::$config['agentPhone'];
        $callback = MinPayService::$config['callback'];
        $out_order_number = $recharge['order_id'];
        $front = "https://app.kaolabinfen.com";
        // 支付参数
        $params = [
            'pay_type'      => $pay_type,  // 收款方式
            'service'       => $service,   // 接口类型
            'bank_code'     => $bank_code, // 银行代码
            'amount'        => $amount,    // 交易金額
            'agentPhone'    => $agentPhone,// 商家手机
            'callback'      => $callback,  // 回调地址
            'out_order_number' => $out_order_number, // 外部订单号
            'front'         => $front,
            'manual_remark' => '手机号：1000000,用户名称：test',
        ];
        // 加解密后 获得
        $res = (new MinPayService())->doPay($params);
        array_push($res,compact('out_order_number'));

        return app('json')->success($res);

    }


    /**
     * 查询订单是否充值成功
     * @Author : UJB
     * @Date : 2021/3/24
     * @param string $out_order_number
     * @return mixed
     */
    public function checkOrder( string$out_order_number)
    {
        $res = MinPayService::create()->checkOrder($out_order_number);
        if ($res['pay_status'] = 'S')
            return app('json')->success('支付成功');
        return app('json')->fail('支付失败');

    }
}
