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


namespace app\common\repositories\system\config;


use app\common\dao\system\config\SystemConfigClassifyDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;

/**
 * Class ConfigClassifyRepository
 * @package crmeb\repositories\system\config
 * @mixin SystemConfigClassifyDao
 */
class ConfigClassifyRepository extends BaseRepository
{

    /**
     * ConfigClassifyRepository constructor.
     * @param SystemConfigClassifyDao $dao
     */
    public function __construct(SystemConfigClassifyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return array
     * @author xaboy
     * @day 2020-03-27
     */
    public function options(): array
    {
        $options = $this->dao->getOptions();
        return formatCascaderData($options, 'classify_name');
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-31
     */
    public function lst($where)
    {
        $query = $this->dao->search($where);
        $list = $query->select();
        $count = $query->count();

        return compact('list', 'count');
    }

    /**
     * @param int $id
     * @param int $status
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-03-31
     */
    public function switchStatus(int $id, int $status)
    {
        return $this->dao->update($id, compact('status'));
    }

    /**
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-03-31
     */
    public function form(?int $id = null, array $formData = []): Form
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('configClassifyCreate')->build() : Route::buildUrl('configClassifyUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::select('pid', '上级分类', 0)->options(function () {
                $data = $this->dao->getTopOptions();
                $options = [['value' => 0, 'label' => '顶级分类']];
                foreach ($data as $value => $label) {
                    $options[] = compact('value', 'label');
                }
                return $options;
            }),
            Elm::input('classify_name', '配置分类名称')->required(),
            Elm::input('classify_key', '配置分类key')->required(),
            Elm::input('info', '配置分类说明'),
            Elm::frameInput('icon', '配置分类图标', '/' . config('admin.admin_prefix') . '/setting/icons?field=icon')->icon('el-icon-circle-plus-outline')->height('338px')->width('700px')->modal(['modal' => false]),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
        ]);

        return $form->setTitle(is_null($id) ? '添加配置分类' : '编辑配置分类')->formData($formData);
    }

    /**
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-03-30
     */
    public function createForm()
    {
        return $this->form();
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
}
