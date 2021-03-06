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


namespace app\common\repositories\user;


use app\common\dao\user\UserRechargeDao;
use app\common\model\store\order\StoreGroupOrder;
use app\common\model\user\User;
use app\common\model\user\UserRecharge;
use app\common\repositories\BaseRepository;
use crmeb\jobs\SendTemplateMessageJob;
use crmeb\services\AlipayService;
use crmeb\services\MiniProgramService;
use crmeb\services\WechatService;
use EasyWeChat\Support\Collection;
use Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;

/**
 * Class UserRechargeRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020/6/2
 * @mixin UserRechargeDao
 */
class UserRechargeRepository extends BaseRepository
{
    /**
     * UserRechargeRepository constructor.
     * @param UserRechargeDao $dao
     */
    public function __construct(UserRechargeDao $dao)
    {
        $this->dao = $dao;
    }

    public function create($uid, float $price, float $givePrice, string $type)
    {
        return $this->dao->create([
            'uid' => $uid,
            'price' => $price,
            'give_price' => $givePrice,
            'recharge_type' => $type,
            'paid' => 0,
            'order_id' => $this->dao->createOrderId($uid)
        ]);
    }

    public function getList($where, $page, $limit)
    {
        $query = $this->dao->searchJoinQuery($where)->order('a.pay_time DESC,a.create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }


    public function priceByGive($price)
    {
        $quota = systemGroupData('user_recharge_quota');
        $give = 0;
        foreach ($quota as $item) {
            $min = floatval($item['price']);
            $_give = floatval($item['give']);
            if ($price > $min) $give = $_give;
        }
        return $give;
    }

    /**
     * 充值js支付
     * @param $openId
     * @param UserRecharge $recharge
     * @return array|string
     * @author xaboy
     * @day 2020/6/2
     */
    public function jsPay($openId, UserRecharge $recharge)
    {
        return MiniProgramService::create()->jsPay($openId, $recharge['order_id'], $recharge['price'], 'user_recharge', '用户充值');
    }

    /**
     * 微信H5支付
     * @param UserRecharge $recharge
     * @return Collection
     * @author xaboy
     * @day 2020/6/2
     */
    public function wxH5Pay(UserRecharge $recharge)
    {
        return WechatService::create()->paymentPrepare(null, $recharge['order_id'], $recharge['price'], 'user_recharge', '用户充值', '', 'MWEB');
    }

    /**
     * 公众号支付
     * @param $openId
     * @param UserRecharge $recharge
     * @return array|string
     * @author xaboy
     * @day 2020/6/2
     */
    public function wxPay($openId, UserRecharge $recharge)
    {
        return WechatService::create()->jsPay($openId, $recharge['order_id'], $recharge['price'], 'user_recharge', '用户充值');
    }

    /**
     * @param User $user
     * @param UserRecharge $recharge
     * @param $return_url
//     * @return \think\response\Json
     * @author xaboy
     * @day 2020/10/22
     */
    public function payAlipay(User $user, UserRecharge $recharge, $return_url)
    {
        $url = AlipayService::create('user_recharge')->wapPaymentPrepare($recharge['order_id'], $recharge['price'], '用户充值', $return_url);
        $pay_key = md5($url);
        Cache::store('file')->set('pay_key' . $pay_key, $url, 3600);
        return ['mweb_url' => $url];
//        return app('json')->status('alipay', ['config' => $url, 'pay_key' => $pay_key, 'order_id' => $recharge['group_order_id']]);
    }

    /**
     * //TODO 余额充值成功
     *
     * @param $orderId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/19
     */
    public function paySuccess($orderId)
    {
        $recharge = $this->dao->getWhere(['order_id' => $orderId]);
        if ($recharge->paid == 1) return;
        $recharge->paid = 1;
        $recharge->pay_time = date('Y-m-d H:i:s');

        Db::transaction(function () use ($recharge) {
            $price = bcadd($recharge->price, $recharge->give_price, 2);
            $mark = '成功充值余额' . floatval($recharge->price) . '元' . ($recharge->give_price > 0 ? ',赠送' . $recharge->give_price . '元' : '');
            app()->make(UserBillRepository::class)->incBill($recharge->user->uid, 'now_money', 'recharge', [
                'link_id' => $recharge->recharge_id,
                'status' => 1,
                'title' => '余额充值',
                'number' => $price,
                'mark' => $mark,
                'balance' => $recharge->user->now_money
            ]);
            $recharge->user->now_money = bcadd($recharge->user->now_money, $price, 2);
            $recharge->user->save();
            $recharge->save();
        });
        Queue::push(SendTemplateMessageJob::class,[
            'tempCode' => 'ORDER_DELIVER_SUCCESS',
            'id' =>$orderId
        ]);
    }
}
