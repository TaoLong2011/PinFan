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

namespace app\common\dao\store\shipping;

use app\common\dao\BaseDao;
use app\common\model\store\shipping\City as model;

class CityDao  extends BaseDao
{
    protected function getModel(): string
    {
        return model::class;
    }

    public function getAll(array $where)
    {
//        return ($this->getModel()::getDB())->where($where)
//            ->order('city_id ASC')->field('city_id,name,merger_name,parent_id,level')->select();
        return ($this->getModel()::getDB())->where($where)
            ->order('id ASC')->field('id,city_id,name,is_show,merger_name,parent_id,level')->select();
    }

    public function getAllOption($id)
    {
        return ($this->getModel()::getDB())
            ->when($id,function ($query) use ($id){
                $query->where('id','<>',$id);
            })
            ->field('name,parent_id')
            ->order('id DESC')->column('parent_id,name','id');
    }

}
