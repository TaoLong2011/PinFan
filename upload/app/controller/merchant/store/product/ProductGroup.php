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
namespace app\controller\merchant\store\product;

use crmeb\jobs\ChangeSpuStatusJob;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductGroupRepository;
use app\validate\merchant\StoreProductGroupValidate;
use think\exception\ValidateException;

class ProductGroup extends BaseController
{
    protected $repository;
    /**
     * @var int 云购码 起始数值
     */
    protected $_code;

    /**
     * ProductGroup constructor.
     * @param App $app
     * @param ProductGroupRepository $repository
     */
    public function __construct(App $app, ProductGroupRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->_code = 1000;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['product_status', 'keyword', 'active_type', 'status']);
        $where['is_show'] = $where['status'];
        unset($where['status']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getMerchantList($where, $page, $limit));
    }

    public function create(StoreProductGroupValidate $validate)
    {
        $data = $this->checkParams($validate);
        // 云购码起始值
        $code_start = $this->_code + 1;
        // 云购码结束值
        $code_end = $this->_code + $data['buying_count_num'];
        // 云购码
        $data['code_section'] = "{$code_start},{$code_end}";
        $data['once_pay_count'] = 1;
        $data['pay_count'] = 1;

        //判断补贴百分比不为空
        if (!empty($data['subsidy'])) {
            $sub_price = ($data['attrValue'][0]['active_price']*$data['subsidy'])/100;
            $data['sub_price'] = $sub_price;
        }else{
            $data['sub_price'] = 0.00;
        }

        $this->repository->create($this->request->merId(), $data);
        return app('json')->success('添加成功');
    }

    public function detail($id)
    {
        $where = [
            $this->repository->getPk() => $id,
            'mer_id' => $this->request->merId()
        ];
        if (!$this->repository->getWhere($where))
            return app('json')->fail('数据不存在');
        $data = $this->repository->detail($this->request->merId(), $id);
        return app('json')->success($data);
    }

    public function delete($id)
    {
        $where = [
            $this->repository->getPk() => $id,
            'mer_id' => $this->request->merId()
        ];
        if (!$this->repository->getWhere($where))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_del' => 1]);
        Queue(ChangeSpuStatusJob::class, ['product_type' => 4, 'id' => $id]);
        return app('json')->success('删除成功');
    }

    public function update($id, StoreProductGroupValidate $validate)
    {
        $data = $this->checkParams($validate);

        $where = [
            $this->repository->getPk() => $id,
            'mer_id' => $this->request->merId()
        ];
        if (!$this->repository->getWhere($where))
            return app('json')->fail('数据不存在');

        //判断补贴百分比不为空
        if (!empty($data['subsidy'])) {
            $sub_price = ($data['attrValue'][0]['active_price']*$data['subsidy'])/100;
            $data['sub_price'] = $sub_price;
        }else{
            $data['sub_price'] = '0.00';
        }

        $this->repository->edit($id, $data);
        return app('json')->success('编辑成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->detail($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_show' => $status]);
        Queue(ChangeSpuStatusJob::class, ['product_type' => 4, 'id' => $id]);
        return app('json')->success('修改成功');
    }

    public function checkParams(StoreProductGroupValidate $validate)
    {
        $params = [
            "image", "slider_image", "store_name", "store_info", "product_id", "is_show", "temp_id", "once_pay_count",
            "start_time", "end_time", "buying_num", "buying_count_num", "buying_luck_num", "status", "pay_count", "time", "ficti_status", "ficti_num", "attrValue", 'unit_name', 'content', 'sort', 'subsidy'
        ];
        $data = $this->request->params($params);
        if ($data['buying_count_num'] < 2) throw new ValidateException('开团人数不得少于2人');
        if ($data['end_time'] < $data['start_time']) throw new ValidateException('活动开始时间必须大于结束时间');
        $validate->check($data);
        return $data;
    }

    public function updateSort($id)
    {
        $sort = $this->request->param('sort');
        $this->repository->updateSort($id, $this->request->merId(), ['sort' => $sort]);
        return app('json')->success('修改成功');
    }
}
