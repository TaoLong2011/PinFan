<?php
/**
 * User: Ujb
 * Date: 2021/3/25
 * 想不通就不想,得不到就不要
 */

namespace app\validate\admin;


use think\Validate;

class CityValidate extends Validate
{
    protected $failException = true;
    protected $rule = [
//        'city_id|城市' => 'require|number',
        'parent_id|上级城市' => 'require|number',
        'area_code|区号'  => 'max:30|unique:system_city',
        'name|名称' => 'require|max:100',
        'is_show|是否显示' => 'require|in:0,1'
    ];

    protected $message = [];
}