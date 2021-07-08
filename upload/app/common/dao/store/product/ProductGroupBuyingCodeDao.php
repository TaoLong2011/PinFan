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
namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\ProductGroupBuyingCode;

class ProductGroupBuyingCodeDao extends  BaseDao
{
    public function getModel(): string
    {
        return ProductGroupBuyingCode::class;
    }

    public function getOne($group_buying_id){
        return ($this->getModel())::getDB()->where("group_buying_id",$group_buying_id);
    }




}
