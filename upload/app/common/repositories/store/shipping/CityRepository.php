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

namespace app\common\repositories\store\shipping;

use app\common\repositories\BaseRepository;
use app\common\dao\store\shipping\CityDao as dao;
use app\common\repositories\system\config\ConfigClassifyRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;
use function GuzzleHttp\Psr7\str;

class CityRepository extends BaseRepository
{

    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return array
     */
    public function getFormatList( array $where = [])
    {
//        return $this->dao->getAll([]);
        return  formatCategory($this->dao->getAll([])->toArray(), 'city_id','parent_id');
    }

    /**
     * @Author : UJB
     * @Date : 2021/3/25
     * @param array $params
     * @return \app\common\dao\BaseDao|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(array $params){


        if ($params['parent_id'] != 0 && empty($params['merger_name'])){
            $general_data = $this->generateMergerNameNLevel($params['parent_id'],$params['name']);
            $params['merger_name'] = $general_data['merger_name'];
            $params['level'] = $general_data['level'];
        }else{
            $params['merge_name'] = $params['name'];
        }

        return $this->dao->create($params);
    }

    /**
     * @Author : UJB
     * @Date : 2021/3/27
     * @param int|null $id
     * @param array    $formData
     * @return Form
     * @throws FormBuilderException
     */
    public function form(?int $id , array $formData = [])
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('SystemCityCreate')->build() : Route::buildUrl('SystemCityUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::cascader('parent_id', '上级城市')->options(function ()  use ($id){
                return array_merge([['value' => 0, 'label' => '请选择']], $this->options($id));
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]]),
            Elm::input('name', '城市名称')->required(),

            Elm::input('merger_name', '合并名称'),
            Elm::input('area_code', '区号'),
            Elm::switches('is_show', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关闭')->activeText('开启'),
        ]);
        return $form->setTitle(is_null($id) ? '添加城市' : '编辑城市')->formData($formData);
    }

    /**
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-03-30
     */
    public function createForm()
    {
        return $this->form(null);
    }

    /**
     * @param $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-31
     */
    public function updateForm($id)
    {
        return $this->form($id, $this->dao->get($id)->toArray());
    }

    /**
     * @Author : UJB
     * @Date : 2021/3/26
     * @param int   $id
     * @param array $params
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update(int $id,  array $params)
    {
        if ($params['parent_id'] != 0 && empty($params['merger_name'])){
            $general_data = $this->generateMergerNameNLevel($params['parent_id'],$params['name']);
            $params['merger_name'] = $general_data['merger_name'];
            $params['level'] = $general_data['level'];
        }else{
            $params['merger_name'] = $params['name'];
        }
        return $this->dao->update($id,$params);
    }

    public function addChildForm(int $id)
    {
        $formData = ['parent_id' => $id];

        return $this->form(null,$formData);


    }

    /**
     * @Author : UJB
     * @Date : 2021/3/26
     * @param int $id
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function del(int $id)
    {
        return $this->dao->delete($id);

    }

    /**
     * @Author : UJB
     * @Date : 2021/3/26
     * @param int $id
     * @return bool
     */
    public function idExist(int $id)
    {
        if ($id != 0)
            return $this->dao->existsWhere(['id' => $id]);
        return true;
    }

    /**
     * 父级是否存在
     * @Author : UJB
     * @Date : 2021/3/25
     * @param int $parent_id
     * @return bool
     */
    public function parentExist( int $parent_id)
    {
        if ($parent_id != 0)
            return $this->dao->existsWhere(['id' => $parent_id]);
        return true;
    }

    public function childrenExist(int $id)
    {
        if ($id != 0)
            return $this->dao->existsWhere(['parent_id' => $id]);
        return true;
        
    }

    public function nameExist(string $name ,$id = false)
    {
        if ($id)
        return $this->dao->existsWhere(['name' => $name,'id' => ['<>',$id]]);
        return $this->dao->existsWhere(['name' => $name]);
    }


    /**
     * 递归生成 merge_name & level
     * @Author : UJB
     * @Date : 2021/3/25
     * @param int    $parent_id
     * @param string $name
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function generateMergerNameNLevel(int $parent_id, string $name):array
    {
        static $level = 1;
        $parent = $this->dao->getWhere(['id' => $parent_id],'id,parent_id,name');
        $merger_name = $parent['name'] . ",{$name}";
        if ($parent['parent_id'] != 0){
            $level++;
            $this->generateMergerNameNLevel($parent['parent_id'],$merger_name);
        }
        return compact('merger_name','level');
    }

    public function options($id,$addChild = false)
    {
        $option = $this->dao->getAllOption($id);
        return formatCascaderData($option, 'name', $baseLevel = 0, $pidName = 'parent_id');
    }

}
