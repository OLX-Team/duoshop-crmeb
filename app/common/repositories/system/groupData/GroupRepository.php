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


namespace app\common\repositories\system\groupData;


use app\common\dao\system\groupData\GroupDao;
use app\common\model\system\groupData\SystemGroup;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;
use think\Model;

/**
 * Class GroupRepository
 * @package app\common\repositories\system\groupData
 * @mixin GroupDao
 * @author xaboy
 * @day 2020-03-27
 */
class GroupRepository extends BaseRepository
{

    /**
     *
     */
    const TYPES = ['input' => '文本框', 'number' => '数字框', 'textarea' => '多行文本框', 'radio' => '单选框', 'checkbox' => '多选框', 'select' => '下拉框', 'file' => '文件上传', 'image' => '图片上传', 'images' => '多图上传', 'color' => '颜色选择框', 'label' => '标签', 'cate' => '平台分类'];


    /**
     * GroupRepository constructor.
     * @param GroupDao $dao
     */
    public function __construct(GroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $data
     * @return SystemGroup|Model
     * @author xaboy
     * @day 2020-03-27
     */
    public function create(array $data)
    {
        $data['fields'] = $this->tidyFields($data['fields']);
        return $this->dao->create($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-03-27
     */
    public function update(int $id, array $data)
    {

        $data['fields'] = json_encode($this->tidyFields($data['fields']));
        return $this->dao->update($id, $data);
    }

    /**
     * @param array $fields
     * @return array
     * @author xaboy
     * @day 2020-03-27
     */
    public function tidyFields(array $fields): array
    {
        if (!count($fields))
            throw new ValidateException('字段最少设置一个');

        $data = [];
        $fieldKey = [];
        foreach ($fields as $field) {
            if (!isset($field['type']))
                throw new ValidateException('字段类型不能为空');
            if (!isset($field['field']))
                throw new ValidateException('字段key不能为空');
            if (!isset($field['name']))
                throw new ValidateException('字段名称不能为空');
            if (in_array($field['field'], $fieldKey))
                throw new ValidateException('字段key不能重复');
            $fieldKey[] = $field['field'];
            $data[] = [
                'name' => $field['name'],
                'field' => $field['field'],
                'type' => $field['type'],
                'param' => $field['param'] ?? '',
                'props' => $field['props'] ?? ''
            ];
        }
        return $data;
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
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('groupCreate')->build() : Route::buildUrl('groupUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::select('user_type', '后台类型', 0)->options([
                ['label' => '总后台配置', 'value' => 0],
                ['label' => '商户后台配置', 'value' => 1],
            ])->requiredNum(),
            Elm::input('group_name', '组合数据名称')->required(),
            Elm::input('group_key', '组合数据key')->required(),
            Elm::input('group_info', '组合数据说明'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::group('fields', '字段')->rules([
                Elm::select('type', '类型')->required()->options(function () {
                    $options = [];
                    foreach (self::TYPES as $value => $label) {
                        $options[] = compact('value', 'label');
                    }
                    return $options;
                }),
                Elm::input('name', '字段名称'),
                Elm::input('field', '字段key'),
                Elm::textarea('param', '选择项'),
                Elm::textarea('props', '配置'),
            ]),
        ]);

        return $form->setTitle(is_null($id) ? '添加组合数据' : '编辑组合数据')->formData($formData);
    }

    /**
     * @param int $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-02
     */
    public function updateForm(int $id)
    {
        return $this->form($id, $this->dao->get($id)->toArray());
    }

    /**
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-01
     */
    public function page(int $page, int $limit)
    {
        $list = $this->dao->page($page, $limit)->hidden(['fields', 'sort'])->order('create_time DESC')->select();
        $count = $this->dao->count();
        return compact('count', 'list');
    }

    /**
     * @param int $id
     * @return array
     * @author xaboy
     * @day 2020-04-02
     */
    public function keys(int $id): array
    {
        return array_column($this->fields($id), 'field');
    }

    /**
     * @param $id
     * @author xaboy
     * @day 2020-05-16
     */
    public function delete($id)
    {
        Db::transaction(function () use ($id) {
            $this->delete($id);

            /** @var GroupDataRepository $make */
            $make = app()->make(GroupDataRepository::class);
            $make->clearGroup($id);
        });
    }

}
