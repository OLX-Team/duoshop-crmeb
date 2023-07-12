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

namespace app\common\repositories\user;


use app\common\dao\user\FeedbackCateoryDao as dao;
use app\common\repositories\BaseRepository;
use crmeb\traits\CategoresRepository;
use FormBuilder\Form;
use think\facade\Route;
use FormBuilder\Factory\Elm;

class FeedBackCategoryRepository extends BaseRepository
{
    use CategoresRepository;
    /**
     * @param FeedbackDao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    public function form(int $merId, $id = null, array $formData = [])
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemUserFeedBackCategoryCreate')->build() : Route::buildUrl('systemUserFeedBackCategoryUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::cascader('pid','上级分类')->options(function()use($id,$merId){
                $menus = $this->dao->getAllOptions(null);
                if ($id && isset($menus[$id])) unset($menus[$id]);
                $menus = formatCascaderData($menus, 'cate_name');
                array_unshift($menus, ['label' => '顶级分类', 'value' => 0]);
                return $menus;
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]]),
            Elm::input('cate_name', '分类名称')->required(),
            Elm::switches('is_show','是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
        ]);

        return $form->setTitle(is_null($id) ? '添加分类' : '编辑分类')->formData($formData);
    }

    public function updateForm(int $merId,int $id)
    {
        return $this->form($merId,$id,$this->dao->get($id)->toArray());
    }

    public function switchStatus(int $id,int $status)
    {
        $this->dao->update($id,['is_show' => $status]);
    }
}
