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


namespace app\controller\merchant\system;


use app\common\repositories\store\MerchantTakeRepository;
use app\validate\merchant\MerchantTakeValidate;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantRepository;
use app\validate\merchant\MerchantUpdateValidate;
use crmeb\jobs\ChangeMerchantStatusJob;
use think\App;
use think\facade\Queue;

/**
 * Class Merchant
 * @package app\controller\merchant\system
 * @author xaboy
 * @day 2020/6/25
 */
class Merchant extends BaseController
{
    /**
     * @var MerchantRepository
     */
    protected $repository;

    /**
     * Merchant constructor.
     * @param App $app
     * @param MerchantRepository $repository
     */
    public function __construct(App $app, MerchantRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @author xaboy
     * @day 2020/6/25
     */
    public function updateForm()
    {
        return app('json')->success(formToData($this->repository->merchantForm($this->request->merchant()->toArray())));
    }

    /**
     * @param MerchantUpdateValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function update(MerchantUpdateValidate $validate)
    {
        $data = $this->request->params(['mer_info', 'service_phone', 'mer_avatar', 'mer_banner', 'mer_state', 'mini_banner', 'mer_keyword', 'mer_address', 'long', 'lat']);
        $validate->check($data);
        $this->request->merchant()->save($data);
        Queue::push(ChangeMerchantStatusJob::class, $this->request->merId());
        return app('json')->success('修改成功');
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020/7/21
     */
    public function info()
    {
        $merchant = $this->request->merchant();
        return app('json')->success($merchant->append(['merchantCategory'])->hidden(['mark', 'reg_admin_id', 'sort'])->toArray());
    }

    /**
     * @param MerchantTakeRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/8/1
     */
    public function takeInfo(MerchantTakeRepository $repository)
    {
        $merId = $this->request->merId();
        return app('json')->success($repository->get($merId) + systemConfig(['tx_map_key']));
    }

    /**
     * @param MerchantTakeValidate $validate
     * @param MerchantTakeRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/8/1
     */
    public function take(MerchantTakeValidate $validate, MerchantTakeRepository $repository)
    {
        $data = $this->request->params(['mer_take_status', 'mer_take_name', 'mer_take_phone', 'mer_take_address', 'mer_take_location', 'mer_take_day', 'mer_take_time']);
        $validate->check($data);
        $repository->set($this->request->merId(), $data);
        return app('json')->success('设置成功');
    }
}