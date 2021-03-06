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

namespace app\common\model\store\shipping;

use app\common\model\BaseModel;

class ShippingTemplateUndelivery extends BaseModel
{
    /**
     * Author:Qinii
     * Date: 2020/5/6
     * Time: 14:20
     * @return string
     */
    public static function tablePk(): string
    {
        return 'shipping_template_undelivery_id';
    }


    /**
     * Author:Qinii
     * Date: 2020/5/6
     * Time: 14:20
     * @return string
     */
    public static function tableName(): string
    {
        return 'shipping_template_undelivery';
    }

    public function getCityIDsAttr($value,$data)
    {
        $city_id = explode('/',$data['city_id']);
        $result = [];
        if(is_array($city_id)){
            foreach ($city_id as $v){
                $result[] = [City::where('city_id',$v)->value('parent_id') ?? 0,intval($v) ];
            }
        }else{
            $result['city_id'] = [];
        }
        return $result;
    }

    public function setCityIdAttr($value)
    {
        return '/'.implode('/',$value).'/';
    }
}
