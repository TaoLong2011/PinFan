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


namespace app\controller\merchant\store\broadcast;


use app\common\repositories\store\broadcast\BroadcastRoomGoodsRepository;
use app\common\repositories\store\broadcast\BroadcastRoomRepository;
use app\validate\merchant\BroadcastRoomValidate;
use crmeb\basic\BaseController;
use think\App;

class BroadcastRoom extends BaseController
{
    protected $repository;

    public function __construct(App $app, BroadcastRoomRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'status_tag']);
        return app('json')->success($this->repository->getList($this->request->merId(), $where, $page, $limit));
    }

    /**
     * @param BroadcastRoomGoodsRepository $repository
     * @param $id
     * @return \think\response\Json
     * @author xaboy
     * @day 2020/8/31
     */
    public function goodsList(BroadcastRoomGoodsRepository $repository, $id)
    {
        [$page, $limit] = $this->getPage();
        if (!$this->repository->merExists((int)$id, $this->request->merId()))
            return app('json')->fail('直播间不存在');
        return app('json')->success($repository->getGoodsList($id, $page, $limit));
    }

    public function detail($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id)->toArray());
    }

    public function createForm()
    {
        return app('json')->success(formToData($this->repository->createForm()));
    }

    public function updateForm($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        if (!$this->repository->existsWhere(['broadcast_room_id' => $id, 'status' => [-1, 0]]))
            return app('json')->fail('当前直播间不能修改');
        return app('json')->success(formToData($this->repository->updateForm(intval($id))));
    }

    public function create()
    {
        $this->repository->create($this->request->merId(), $this->checkParams());
        return app('json')->success('创建成功');
    }

    public function update($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        if (!$this->repository->existsWhere(['broadcast_room_id' => $id, 'status' => [-1, 0]]))
            return app('json')->fail('当前直播间不能修改');
        $this->repository->updateRoom($this->request->merId(), $id, $this->checkParams());
        return app('json')->success('修改成功');
    }

    public function checkParams()
    {
        $validate = app()->make(BroadcastRoomValidate::class);
        $data = $this->request->params(['name', 'cover_img', 'share_img', 'anchor_name', 'anchor_wechat', 'phone', 'start_time', 'type', 'screen_type', 'close_like', 'close_goods', 'close_comment', 'replay_status', 'close_share', 'close_kf']);
        $validate->check($data);
        [$data['start_time'], $data['end_time']] = $data['start_time'];
        return $data;
    }


    public function changeStatus($id)
    {
        $isShow = $this->request->param('is_show') == 1 ? 1 : 0;
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->isShow($id, $isShow);
        return app('json')->success('修改成功');
    }

    public function mark($id)
    {
        $mark = (string)$this->request->param('mark');
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->mark($id, $mark);
        return app('json')->success('修改成功');
    }

    public function exportGoods()
    {
        [$ids, $roomId] = $this->request->params(['ids', 'room_id'], true);
        if (!count($ids)) return app('json')->fail('请选择直播商品');
        $this->repository->exportGoods($this->request->merId(), (array)$ids, $roomId);
        return app('json')->success('导入成功');
    }

    public function rmExportGoods()
    {
        [$id, $roomId] = $this->request->params(['id', 'room_id'], true);
        $this->repository->rmExportGoods($this->request->merId(), intval($roomId), intval($id));
        return app('json')->success('删除成功');
    }

    public function delete($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->merDelete((int)$id);
        return app('json')->success('删除成功');
    }
}