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


namespace app\common\dao\store\order;


use app\common\dao\BaseDao;
use app\common\model\store\order\StoreEnvelopeRecord;
use app\common\model\store\order\StoreGroupOrder;

class StoreEnvelopeRecordDao extends BaseDao
{

    protected function getModel(): string
    {
        return StoreEnvelopeRecord::class;
    }

    public function search($id)
    {
        return $query = StoreEnvelopeRecord::getDB()->where('id', $id);
    }

    public function useAbleRedPackage($uid)
    {
        return $query = StoreEnvelopeRecord::getDB()
            ->where([
                'uid' => $uid,
                'use_status' => 0
        ])->sum('envelop_money');
    }

    public function whereSearch(array $where)
    {
        return StoreEnvelopeRecord::getDB()->when(isset($where['group_id']) && $where['group_id'] !== '', function ($query) use ($where) {
            $query->where('group_id', $where['group_id']);
        })->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->order('id DESC')->when(isset($where['use_status']) && $where['use_status'] !== '', function ($query) use ($where) {
            $query->where('use_status', $where['use_status']);
        });
    }
}
