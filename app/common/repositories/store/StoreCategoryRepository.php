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

use app\common\dao\store\StoreCategoryDao as dao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;
use crmeb\traits\CategoresRepository;

/**
 * @mixin dao
 */
class StoreCategoryRepository extends BaseRepository
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
        if ($merId) {
            $form = Elm::createForm(is_null($id) ? Route::buildUrl('merchantStoreCategoryCreate')->build() : Route::buildUrl('merchantStoreCategoryUpdate', ['id' => $id])->build());
        } else {
            $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemStoreCategoryCreate')->build() : Route::buildUrl('systemStoreCategoryUpdate', ['id' => $id])->build());
        }
        $form->setRule([
            Elm::cascader('pid', '上级分类')->options(function () use ($id, $merId) {
                $menus = $this->dao->getAllOptions($merId,1,$this->dao->getMaxLevel($merId)-1,0);
                if ($id && isset($menus[$id])) unset($menus[$id]);
                $menus = formatCascaderData($menus, 'cate_name');
                array_unshift($menus, ['label' => '顶级分类', 'value' => 0]);
                return $menus;
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])->filterable(true)->appendValidate(Elm::validateInt()->required()->message('请选择上级分类')),
            Elm::input('cate_name', '分类名称')->required(),
            Elm::frameImage('pic', '分类图片(110*110px)', '/' . config('admin.' . ($merId ? 'merchant' : 'admin') . '_prefix') . '/setting/uploadPicture?field=pic&type=1')->width('896px')->height('480px')->props(['footer' => false])->modal(['modal' => false, 'custom-class' => 'suibian-modal']),
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
     * @Author:Qinii
     * @Date: 2020/5/16
     * @return mixed
     */
    public function getList($status = null,$lv = null)
    {
        $menus = $this->dao->getAllOptions(0,$status,$lv,0);
        $menus = formatCascaderData($menus, 'cate_name', $lv ?: 3);
        return $menus;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function getBrandList()
    {
        return app()->make(StoreBrandRepository::class)->getAll();
    }

    /**
     * 检测是否超过最低等级限制
     * @param int $id
     * @param int $level
     * @return bool
     * @author Qinii
     */
    public function checkLevel(int $id,int $level = 0, $merId = null)
    {
        $check = $this->getLevelById($id);
        if($level)
            $check = $level;
        return ($check < $this->dao->getMaxLevel($merId)) ? true : false;
    }

    public function updateStatus($id, $data)
    {
        return $this->dao->update($id, $data);
    }

    public function getHot($merId)
    {
        $hot = $this->dao->getSearch(['is_hot' => 1,'mer_id' => $merId])->hidden(['path','level','mer_id','create_time'])->select();
        if ($hot) $hot->toArray();
        return compact('hot');
    }


    public function pointsForm(?int $id)
    {
        $formData = [];
        if ($id) {
            $formData = $this->dao->get($id)->toArray();
            $form = Elm::createForm(Route::buildUrl('pointsCateUpdate', ['id' => $id])->build());
        } else {
            $form = Elm::createForm(Route::buildUrl('pointsCateCreate')->build());
        }
        $form->setRule([
            Elm::input('cate_name', '分类名称')->required(),
            Elm::switches('is_show', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::number('type', '类型', 1)->hiddenStatus(true),
        ]);
        return $form->setTitle(is_null($id) ? '添加分类' : '编辑分类')->formData($formData);
    }
}
