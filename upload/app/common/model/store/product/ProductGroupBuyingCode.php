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
namespace app\common\model\store\product;

use app\common\model\BaseModel;
use app\common\model\system\merchant\Merchant;

class ProductGroupBuyingCode extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tablePk(): string
    {
        return 'id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tableName(): string
    {
        return 'store_product_group_buying_code';
    }

}
