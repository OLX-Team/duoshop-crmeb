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


namespace app\common\repositories\system\merchant;


use app\common\dao\system\merchant\MerchantCategoryDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;

/**
 * Class MerchantCategoryRepository
 * @package app\common\repositories\system\merchant
 * @author xaboy
 * @day 2020-05-06
 * @mixin MerchantCategoryDao
 */
class MerchantCategoryRepository extends BaseRepository
{
    /**
     * @var MerchantCategoryDao
     */
    protected $dao;

    /**
     * MerchantCategoryRepository constructor.
     * @param MerchantCategoryDao $dao
     */
    public function __construct(MerchantCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-06
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->search($where);
        $count = $query->count($this->dao->getPk());
        $list = $query->page($page, $limit)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['commission_rate'] = ($v['commission_rate'] > 0 ? bcmul($v['commission_rate'], 100, 2) : 0) . '%';
        }
        return compact('count', 'list');
    }

    /**
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-06
     */
    public function form(?int $id = null, array $formData = [])
    {
        $action = Route::buildUrl(is_null($id) ? 'systemMerchantCategoryCreate' : 'systemMerchantCategoryUpdate', is_null($id) ? [] : compact('id'))->build();

        $form = Elm::createForm($action, [
            Elm::input('category_name', '分类名称')->required(),
            Elm::number('commission_rate', '手续费(%)', 0)->required()->max(100)->precision(2)
        ]);

        return $form->formData($formData)->setTitle(is_null($id) ? '添加商户分类' : '编辑商户分类');
    }

    /**
     * @param $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-06
     */
    public function updateForm($id)
    {
        $res = $this->dao->get($id)->toArray();
        $res['commission_rate'] = $res['commission_rate'] > 0 ? bcmul($res['commission_rate'], 100, 2) : 0;
        return $this->form($id, $res);
    }

    /**
     * 筛选分类
     * @Author:Qinii
     * @Date: 2020/9/15
     * @return array
     */
    public function getSelect()
    {
        $query = $this->search([])->field('merchant_category_id,category_name');
        $list = $query->select()->toArray();
        return $list;
    }
}
