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


namespace app\common\repositories\store\order;


use app\common\dao\store\order\StoreEnvelopeRecordDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\user\UserRepository;
use think\facade\Db;
use think\model\Relation;

/**
 * Class StoreRefundStatusRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/6/12
 */
class StoreEnvelopeRecordRepository extends BaseRepository
{
    /**
     * StoreRefundStatusRepository constructor.
     * @param StoreEnvelopeRecordDao $dao
     */
    public function __construct(StoreEnvelopeRecordDao $dao)
    {
        $this->dao = $dao;
    }

    public function writeRecord($data){

        $data['use_status'] = 0;
        $data['add_time'] = time();
        $this->dao->create($data);
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->whereSearch($where);
        $count = $query->count();
        $totalMoney = number_format($query->sum("envelop_money"),2);
        $list = $query->page($page, $limit)->order('create_time DESC')->select();
        $query->removeOption();

        $usableMoney = number_format($this->dao->useAbleRedPackage($where['uid']),2);
        return compact('count', 'list','totalMoney','usableMoney');
    }

    public function transferMoney($uid){

        $where =  ['uid' => $uid];
        $query = $this->dao->whereSearch($where);
        $money = $query->sum("envelop_money");

        return Db::transaction(function () use ($money, $uid,$query) {
            $query->update(['use_status' => 1]);

            app()->make(UserRepository::class)->changeNowMoney($uid,0,1,$money);
        });
    }

}
