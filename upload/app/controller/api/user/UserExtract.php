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

use crmeb\basic\BaseController;
use app\common\repositories\system\groupData\GroupDataRepository;
use think\App;
use app\validate\api\UserExtractValidate as validate;
use app\common\repositories\user\UserExtractRepository as repository;

class UserExtract extends BaseController
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
    public function __construct(App $app,repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    public function lst()
    {
        [$page,$limit] = $this->getPage();
        $where = $this->request->params(['status']);
        $where['uid'] = $this->request->uid();
        return app('json')->success($this->repository->search($where,$page,$limit));
    }


    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        //dump($data);
        if(!isset($data['type']) || $data['type'] == null) {
            return app('json')->fail('缺少参数');
        }
        $user = $this->request->userInfo();
        //1-佣金，2-余额
        if($data['type'] == 1) {
            if($user['brokerage_price'] < (systemConfig('user_extract_min')))
                return app('json')->fail('可提现金额不足');
            if($data['extract_price'] < (systemConfig('user_extract_min')))
                return app('json')->fail('提现金额不得小于最低额度');
            if($user['brokerage_price'] < $data['extract_price'])
                return app('json')->fail('提现金额不足');
        } else if($data['type'] == 2) {
            if($user['now_money'] < (systemConfig('user_extract_min')))
                return app('json')->fail('可提现金额不足');
            if($data['extract_price'] < (systemConfig('user_extract_min')))
                return app('json')->fail('提现金额不得小于最低额度');
            if($user['now_money'] < $data['extract_price'])
                return app('json')->fail('提现金额不足');
        } else {
            return app('json')->fail('参数错误');
        }
        $this->repository->create($user,$data);
        return app('json')->success('申请已提交');
    }

    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['extract_type','bank_code','bank_address','alipay_code','wechat','extract_pic','extract_price','real_name','type']);
        $validate->check($data);
        return $data;
    }

    public function bankLst()
    {
        [$page,$limit] = $this->getPage();
        $data = app()->make(GroupDataRepository::class)->groupData('bank_list',0,$page,100);
        return app('json')->success($data);
    }
}
