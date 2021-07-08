<?php


namespace app\common\model\system\city;


use app\common\model\BaseModel;

class City extends BaseModel
{


    public static function tablePk(): ?string
    {
        return 'id';
    }
    public static function tableName(): string
    {
        return 'eb_system_city';
    }

}