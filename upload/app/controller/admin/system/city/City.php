<?php


namespace app\controller\admin\system\city;

use app\validate\admin\CityValidate;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use app\common\repositories\store\shipping\CityRepository;
use crmeb\basic\BaseController;

class City extends BaseController
{

    protected $repository;
    public function __construct(App $app ,CityRepository $cityRepository)
    {
        parent::__construct($app);
        $this->repository = $cityRepository;
    }

    /**
     * @Author : UJB
     * @Date : 2021/3/26
     * @return mixed
     */
    public function lst()
    {
        $list = $this->repository->getFormatList();
        return \app('json')->success($list);
    }

    /**
     * @Author : UJB
     * @Date : 2021/3/25
     * @param CityValidate $validate
     * @return mixed
     */
    public function create(CityValidate $validate)
    {
        $params = request()->post(['parent_id','area_code','name','is_show']);
        $validate->check($params);
        if (!$this->repository->parentExist($params['parent_id']))
            return app('json')->fail('父级地区不存在');
        if ($this->repository->nameExist($params['name']))
            return app('json')->fail('名称已存在');
        $this->repository->create($params);

        return app('json')->success('添加成功');
    }

    /**
     * @Author : UJB
     * @Date : 2021/3/27
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm()
    {
        $form = $this->repository->createForm();

        return app('json')->success(formToData($form));

    }

    /**
     * @Author : UJB
     * @Date : 2021/3/27
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function updateForm(int $id)
    {
        if (!$this->repository->idExist($id)) app('json')->fail('数据不存在');
        $form = $this->repository->updateForm($id);
        return app('json')->success(formToData($form));
        
    }

    /**
     * @Author : UJB
     * @Date : 2021/3/27
     * @param int $id
     * @return mixed
     */
    public function addChildForm(int $id)
    {
        if (!$this->repository->idExist($id)) app('json')->fail('数据不存在');
        $form = $this->repository->addChildForm($id);
        return app('json')->success(formToData($form));

    }
    /**
     * @Author : UJB
     * @Date : 2021/3/26
     * @param int          $id
     * @param CityValidate $validate
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function update(int $id, CityValidate $validate)
    {
        $params = request()->post(['parent_id','area_code','name','is_show']);

        $validate->check($params);
        if (!$this->repository->parentExist($params['parent_id']))
            return app('json')->fail('父级地区不存在');
        if (!$this->repository->idExist($id))
            return app('json')->fail('城市不存在');
        if ($this->repository->nameExist($params['name']))
            return app('json')->fail('名称已存在',$id);
        $this->repository->update($id, $params);
        return app('json')->success('修改成功');
    }



    /**
     * @Author : UJB
     * @Date : 2021/3/26
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        if (!$this->repository->idExist($id))
            return app('json')->fail('城市不存在');
        if ($this->repository->childrenExist($id)){
            return app('json')->fail('该城市有下级,不能删除');
        }

        $this->repository->del($id);
        return app('json')->success('删除成功');
    }

}