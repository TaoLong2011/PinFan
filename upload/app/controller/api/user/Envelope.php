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

namespace app\controller\api\user;

use app\common\repositories\store\order\StoreEnvelopeRecordRepository;
use crmeb\basic\BaseController;
use app\common\repositories\system\groupData\GroupDataRepository;
use think\App;
use app\validate\api\UserExtractValidate as validate;


class Envelope extends BaseController
{
    /**
     * @var repository
     */
    public $repository;

    /**
     * UserExtract constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app,StoreEnvelopeRecordRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    public function lst()
    {
        [$page,$limit] = $this->getPage();
		// 1- 已使用  2-未使用  0 或 不传 表示所有
        $status = $this->request->params(['status']);
        if($status == 1){
            $where['use_status'] = 1;
        }else if($status == 2){
            $where['use_status'] = 0;
        }
        $where['uid'] = $this->request->uid();
        return app('json')->success($this->repository->getList($where,$page,$limit));
    }




    public function transfer()
    {
        $uid = $this->request->uid();
        $data = app()->make(StoreEnvelopeRecordRepository::class)->transferMoney($uid);
        if($data == null) {
            $res['msg'] = "succeed";
            $res['code'] = 1;
        } else{
            $res['msg'] = "failed";
            $res['code'] = 0;
        }
        return app('json')->success($res);
    }
}
