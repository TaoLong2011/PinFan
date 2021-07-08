<?php

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: Rostrong
// +----------------------------------------------------------------------

namespace app\common\repositories\store\product;


use app\common\dao\store\product\ProductGroupBuyingDao;
use app\common\dao\store\product\ProductGroupUserDao;
use app\common\repositories\BaseRepository;
use app\common\dao\store\product\ProductGroupBuyingCodeDao as dao;
use app\common\repositories\store\order\StoreEnvelopeRecordRepository;
use app\common\repositories\store\order\StoreRefundOrderRepository;

class ProductGroupBuyingCodeRepository extends BaseRepository
{

    protected $dao;

    protected $base_code = 100000;

    /**
     * ProductRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 创建拼购码
     * @param $groupBuyingID int 活动ID
     * @param $num           int 成团人数
     * @return mixed
     */
    public function writeCode($groupBuyingID,$num){
        $codeList = range(1,$num);
        $codeList = array_map(function($val){
            return $this->base_code + $val;
        },$codeList);
        shuffle($codeList);
        shuffle($codeList);
        $codeStr = implode(",",$codeList);
        $data = [
            'group_buying_id' => $groupBuyingID,
            'all_code'        => $codeStr,
            'all_code_num'    => $num,
            'remain_code'     => $codeStr,
            'remain_code_num' => $num,
        ];
       // var_dump($data);
        $res = $this->dao->create($data);
       // var_dump($res);
        return $res->id;
    }

    public function getCode($groupBuyingID,$num){
        $codeStr = $this->dao->getOne($groupBuyingID)->find();
        $codeArr = explode(",",$codeStr['remain_code']);
        $code = '';
        for($i=0; $i<$num; $i++){
            $code = ($code == '') ? array_shift($codeArr) : ',' . array_shift($codeArr);
        }
        $remain_num = $codeStr['remain_code_num'] - $num;
        if($remain_num <= 0) $remain_num = 0;
        $this->dao->update($codeStr['id'],['remain_code' => implode(",",$codeArr), 'remain_code_num' => $remain_num]);
        return ['codeStr' => $code,'remainNum' => $remain_num];
    }

    public function getRemainNum($groupBuyingID){


    }

    /**
     * 计算拼购幸运码
     * @param $groupBuyingID
     * @return array
     */
    public function calcLuckCode($groupBuyingID){
        $group_buying = app()->make(ProductGroupBuyingDao::class);
        $active = $group_buying->getWhere(['group_buying_id' => $groupBuyingID]);

        $group_user = app()->make(ProductGroupUserDao::class);
        $data = $group_user->getOrderInfo($groupBuyingID);

        $number = 0;
        foreach ($data as $row){
            $number +=  intval(str_replace(['-',':',' '],'',$row['create_time']));
        }

        $code =  $number % $active['buying_count_num'];
        if($code == 0) $code = $active['buying_count_num'];
        $codeArr = [];
        for($i=0; $i<$active['buying_luck_num']; $i++){
            $code_n = $code + $i;
            if($code_n > $active['buying_count_num']){
                $code_n = $code_n - $active['buying_count_num'];
            }
            array_push($codeArr,"" . (100000 + $code_n));
        }

        $group_buying->update( $groupBuyingID, ['buying_luck_code' => implode(",",$codeArr)]);
        $ids = [];

        $refund_make = app()->make(StoreRefundOrderRepository::class);
        $envelope_make = app()->make(StoreEnvelopeRecordRepository::class);
        foreach ($data as $row){
            if(in_array($row['group_buying_code'],$codeArr)){
                array_push($ids,$row['id']);
            }else{
                //array_push($orderIds,$row['order_id']);
                if($active["buying_count_num"]==10){
                    $envelopMoney=bcmul($row['orderInfo']['total_price'],0.1,2);
                }elseif ($active["buying_count_num"]==3){
                    $envelopMoney=bcmul($row['orderInfo']['total_price'],0.05,2);
                }
                if($row['order_id'] > 0){
                    $refund_make->autoRefundOrder($row['order_id'],1,"拼团未中退款");
                    $envelop_data['group_id']      = $row['group_buying_id'];
                    $envelop_data['shop_id']       = $row['product_group_id'];
                    $envelop_data['order_id']      = $row['order_id'];
                    $envelop_data['uid']           = $row['uid'];
                    $envelop_data['envelop_money'] = $envelopMoney;
                    $envelope_make->writeRecord($envelop_data);
                }
            }
        }
        $group_user->updates($ids,['is_luck' => 1]);

        return $ids;
    }



}