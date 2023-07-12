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


namespace app\common\repositories\article;


use app\common\dao\article\ArticleCategoryDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;

/**
 * Class ArticleCategoryRepository
 * @package app\common\repositories\article
 * @author xaboy
 * @day 2020-04-20
 * @mixin ArticleCategoryDao
 */
class ArticleCategoryRepository extends BaseRepository
{
    /**
     * ArticleCategoryRepository constructor.
     * @param ArticleCategoryDao $dao
     */
    public function __construct(ArticleCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param int $merId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-20
     */
    public function getFormatList($merId = 0,$status = null)
    {
        return formatCategory($this->dao->getAll($merId,$status)->toArray(), 'article_category_id');
    }


    /**
     * @return \think\Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/9/18
     */
    public function apiGetArticleCategory()
    {
        return $this->dao->search(['status' => 1, 'pid' => 0])->select();
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
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemArticleCategoryCreate')->build() : Route::buildUrl('systemArticleCategoryUpdate', ['id' => $id])->build());
        $form->setRule([
//            Elm::cascader('pid', '上级分类')->options(function () use ($id, $merId) {
//                $menus = $this->dao->getAllOptions($merId);
//                if ($id && isset($menus[$id])) unset($menus[$id]);
//                $menus = formatCascaderData($menus, 'title');
//                array_unshift($menus, ['label' => '顶级分类', 'value' => 0]);
//                return $menus;
//            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]]),
            Elm::input('title', '分类名称')->required(),
            Elm::input('info', '分类简介'),
            Elm::frameImage('image', '分类图片', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=image&type=1')->width('896px')->height('480px')->props(['footer' => false])->modal(['modal' => false, 'custom-class' => 'suibian-modal']),
            Elm::switches('status', '状态', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
        ]);

        return $form->setTitle(is_null($id) ? '添加文章配置' : '编辑文章分类')->formData($formData);
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
}
