<?php

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------


namespace app\common\repositories\store;

use FormBuilder\Form;
use think\facade\Route;
use FormBuilder\Factory\Elm;
use think\db\exception\DbException;
use app\common\repositories\BaseRepository;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use FormBuilder\Exception\FormBuilderException;

use crmeb\traits\CategoresRepository;
use app\common\dao\store\StoreBrandCategoryDao as dao;

/**
 * @mixin dao
 */
class StoreBrandCategoryRepository extends BaseRepository
{

    use CategoresRepository;

    public function __construct(dao $dao)
    {
        $this->dao = $dao;

    }

    /**
     * @param int $merId
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-20
     */
    public function form(int $merId, ?int $id = null, array $formData = [])
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemStoreBrandCategoryCreate')->build() : Route::buildUrl('systemStoreBrandCategoryUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::cascader('pid', '上级分类')->options(function () use ($id, $merId) {
                $menus = $this->dao->getAllOptions(null);
                if ($id && isset($menus[$id])) unset($menus[$id]);
                $menus = formatCascaderData($menus, 'cate_name');
                array_unshift($menus, ['label' => '顶级分类', 'value' => 0]);
                return $menus;
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]]),
            Elm::input('cate_name', '分类名称')->required(),
            Elm::switches('is_show', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
        ]);

        return $form->setTitle(is_null($id) ? '添加分类' : '编辑分类')->formData($formData);
    }

    /**
     * @param int $merId
     * @param $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-20
     */
    public function updateForm(int $merId, $id)
    {
        return $this->form($merId, $id, $this->dao->get($id, $merId)->toArray());
    }

    /**
     * @return array
     * @author xaboy
     * @day 2020/7/22
     */
    public function getAncestorsChildList()
    {
        $res = $this->dao->options();
        $res = formatCascaderData($res, 'cate_name');
        foreach ($res as $k => $v) {
            if (!isset($v['children']) || !count($v['children']))
                unset($res[$k]);
        }
        return array_values($res);
    }

}
