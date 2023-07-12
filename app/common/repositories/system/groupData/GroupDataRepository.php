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


use app\common\dao\BaseDao;
use app\common\dao\system\groupData\GroupDataDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductLabelRepository;
use app\common\repositories\store\StoreCategoryRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Route;
use think\Model;

/**
 * Class GroupDataRepository
 * @package app\common\repositories\system\groupData
 * @mixin GroupDataDao
 * @author xaboy
 * @day 2020-03-30
 */
class GroupDataRepository extends BaseRepository
{

    /**
     * GroupDataRepository constructor.
     * @param GroupDataDao $dao
     */
    public function __construct(GroupDataDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param int $merId
     * @param array $data
     * @param array $fieldRule
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-03-30
     */
    public function create(int $merId, array $data, array $fieldRule)
    {
        $this->checkData($data['value'], $fieldRule);
        $data['mer_id'] = $merId;
        return $this->dao->create($data);
    }

    /**
     * @param $merId
     * @param $id
     * @param $data
     * @param $fieldRule
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020/9/23
     */
    public function merUpdate($merId, $id, $data, $fieldRule)
    {
        $this->checkData($data['value'], $fieldRule);
        return $this->dao->merUpdate($merId, $id, $data);
    }

    /**
     * @param array $data
     * @param array $fieldRule
     * @author xaboy
     * @day 2020/9/23
     */
    public function checkData(array $data, array $fieldRule)
    {
        foreach ($fieldRule as $rule) {
//            if (!isset($data[$rule['field']]) || $data[$rule['field']] === '') {
//                throw new ValidateException($rule['name'] . '不能为空');
//            }
            if ($rule['type'] === 'number' && $data[$rule['field']] < 0)
                throw new ValidateException($rule['name'] . '不能小于0');
        }
    }

    /**
     * @param int $merId
     * @param int $groupId
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-30
     */
    public function getGroupDataLst(int $merId, int $groupId, int $page, int $limit): array
    {
        $query = $this->dao->getGroupDataWhere($merId, $groupId)->order('sort DESC,group_data_id ASC');
        $count = $query->count();
        $list = $query->field('group_data_id,value,sort,status,create_time')->page($page, $limit)->select()->toArray();
        foreach ($list as $k => $data) {
            $value = $data['value'];
            unset($data['value']);
            $data += $value;
            $list[$k] = $data;
        }
        return compact('count', 'list');
    }

    /**
     * @param int $groupId
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-02
     */
    public function form(int $groupId, ?int $id = null, ?int $merId = null, array $formData = []): Form
    {
        $fields = app()->make(GroupRepository::class)->fields($groupId);
        if (is_null($merId)) {
            $url = is_null($id)
                ? Route::buildUrl('groupDataCreate', compact('groupId'))->build()
                : Route::buildUrl('groupDataUpdate', compact('groupId', 'id'))->build();
        } else {
            $url = is_null($id)
                ? Route::buildUrl('merchantGroupDataCreate', compact('groupId'))->build()
                : Route::buildUrl('merchantGroupDataUpdate', compact('groupId', 'id'))->build();
        }

        $form = Elm::createForm($url);
        $rules = [];
        foreach ($fields as $field) {
            $rule = null;
            if ($field['type'] == 'image') {
                $rule = Elm::frameImage($field['field'], $field['name'], '/' . config('admin.' . ($merId ? 'merchant' : 'admin') . '_prefix') . '/setting/uploadPicture?field=' . $field['field'] . '&type=1')->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false]);
            } else if ($field['type'] == 'images') {
                $rule = Elm::frameImage($field['field'], $field['name'], '/' . config('admin.' . ($merId ? 'merchant' : 'admin') . '_prefix') . '/setting/uploadPicture?field=' . $field['field'] . '&type=2')->maxLength(5)->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false]);
            } else if ($field['type'] == 'cate') {
                $rule = Elm::cascader($field['field'], $field['name'])->options(function () use ($id) {
                    $storeCategoryRepository = app()->make(StoreCategoryRepository::class);
                    $menus = $storeCategoryRepository->getAllOptions(0, 1, null,0);
                    if ($id && isset($menus[$id])) unset($menus[$id]);
                    $menus = formatCascaderData($menus, 'cate_name');
                    return $menus;
                })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])->filterable(true)->appendValidate(Elm::validateInt()->required()->message('请选择分类'));
            } else if ($field['type'] == 'label') {
                $rule = Elm::select($field['field'], $field['name'])->options(function () {
                    return app()->make(ProductLabelRepository::class)->getSearch(['mer_id' => request()->merId(), 'status' => 1])->column('label_name as label,product_label_id as value');
                })->appendValidate(Elm::validateNum()->required()->message('请选择标签'));
            } else if (in_array($field['type'], ['select', 'checkbox', 'radio'])) {
                $options = array_map(function ($val) {
                    [$value, $label] = explode(':', $val, 2);
                    return compact('value', 'label');
                }, explode("\n", $field['param']));
                $rule = Elm::{$field['type']}($field['field'], $field['name'])->options($options);
                if ($field['type'] == 'select') {
                    $rule->filterable(true)->prop('allow-create', true);
                }
            } else if ($field['type'] == 'file') {
                $rule = Elm::uploadFile($field['field'], $field['name'], rtrim(systemConfig('site_url'), '/') . Route::buildUrl('configUpload', ['field' => 'file'])->build())->headers(['X-Token' => request()->token()]);
            } else {
                $rule = Elm::{$field['type']}($field['field'], $field['name'], '');
            }
            if ($field['props'] ?? '') {
                $props = @parse_ini_string($field['props'], false, INI_SCANNER_TYPED);
                if (is_array($props)) {
                    $rule->props($props);
                    if(isset($props['required']) && $props['required']){
                        $rule->required();
                    }
                    if (isset($props['defaultValue'])) {
                        $rule->value($props['defaultValue']);
                    }
                }
            }
            $rules[] = $rule;
        }
        $rules[] = Elm::number('sort', '排序', 0)->precision(0)->max(99999);
        $rules[] = Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开');

        $form->setRule($rules);

        return $form->setTitle(is_null($id) ? '添加数据' : '编辑数据')->formData(array_filter($formData, function ($item) {
            return $item !== '' && !is_null($item);
        }));
    }

    /**
     * @param int $groupId
     * @param int $merId
     * @param int $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-02
     */
    public function updateForm(int $groupId, int $merId, int $id)
    {
        $data = $this->dao->getGroupDataWhere($merId, $groupId)->where('group_data_id', $id)->find()->toArray();
        $value = $data['value'];
        unset($data['value']);
        $data += $value;
        return $this->form($groupId, $id, $merId, $data);
    }

    /**
     * @param string $key
     * @param int $merId
     * @param int|null $page
     * @param int|null $limit
     * @return array
     * @author xaboy
     * @day 2020/5/27
     */
    public function groupData(string $key, int $merId, ?int $page = null, ?int $limit = 10)
    {
        $make = app()->make(GroupRepository::class);
        $groupId = $make->keyById($key);
        if (!$groupId) return [];
        return $this->dao->getGroupData($merId, $groupId, $page, $limit);
    }

    /**
     * @param string $key
     * @param int $merId
     * @param int|null $page
     * @param int|null $limit
     * @return int
     * @author xaboy
     * @day 2020/5/27
     */
    public function getGroupDataCount(string $key, int $merId)
    {
        /** @var GroupRepository $make */
        $make = app()->make(GroupRepository::class);
        $groupId = $make->keyById($key);
        if (!$groupId) 0;
        return $this->dao->groupDataCount($merId, $groupId);
    }

    /**
     * @param int $id
     * @param int $merId
     * @return mixed|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/2
     */
    public function idByData(int $id, int $merId)
    {
        $data = $this->dao->merGet($id, $merId);
        if (!$data) return;
        return json_decode($data['value']);
    }

    /**
     * @param string $key
     * @param int $merId
     * @param int|null $page
     * @param int|null $limit
     * @return array
     * @author xaboy
     * @day 2020/6/3
     */
    public function groupDataId(string $key, int $merId, ?int $page = null, ?int $limit = 10)
    {
        $make = app()->make(GroupRepository::class);
        $groupId = $make->keyById($key);
        if (!$groupId) return [];
        return $this->dao->getGroupDataId($merId, $groupId, $page, $limit);
    }

    public function setGroupData(string $key, $merId, array $data)
    {
        $groupRepository = app()->make(GroupRepository::class);
        $group = $groupRepository->getWhere(['group_key' => $key]);
        $fields = array_column($groupRepository->fields($group->group_id), 'field');
        $insert = [];
        foreach ($data as $k => $item) {
            unset($item['group_data_id'], $item['group_mer_id']);
            $value = [];
            foreach ($fields as $field) {
                if (isset($item[$field])) {
                    $value[$field] = $item[$field];
                }
            }
            $insert[$k] = [
                'value' => json_encode($value,JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'sort' => 0,
                'group_id' => $group->group_id,
                'mer_id' => $merId,
            ];
        }
        $this->dao->selectWhere(['group_id' => $group->group_id])->delete();
        if (count($insert)) {
            $this->dao->insertAll($insert);
        }
    }

    public function clearGroup(string $key, $merId)
    {
        $groupRepository = app()->make(GroupRepository::class);
        $group = $groupRepository->getWhere(['group_key' => $key]);
        $this->dao->selectWhere(['group_id' => $group->group_id,'mer_id' => $merId])->delete();
    }

    public function reSetDataForm(int $groupId, ?int $id, ?int $merId)
    {
        $formData = [];
        if (is_null($id)) {
            $url = is_null($merId)
                ? Route::buildUrl('groupDataCreate', compact('groupId'))->build()
                : Route::buildUrl('merchantGroupDataCreate', compact('groupId'))->build();

        } else {
            $data = $this->dao->getSearch([])->find($id);
            if (!$data) throw new ValidateException('数据不存在');
            $formData = $data->value;
            $formData['status'] = $data->status;
            $formData['sort'] = $data->sort;
            $url = is_null($merId)
                ? Route::buildUrl('systemUserSvipTypeUpdate', compact('groupId','id'))->build()
                : Route::buildUrl('merchantGroupDataUpdate', compact('groupId','id'))->build();
        }
        $form = Elm::createForm($url);
        $rules = [
            Elm::input('svip_name', '会员名')->required(),
            Elm::radio('svip_type', '会员类别', '2')
                ->setOptions([
                    ['value' => '1', 'label' => '试用期',],
                    ['value' => '2', 'label' => '有限期',],
                    ['value' => '3', 'label' => '永久期',],
                ])->control([
                    [
                        'value' => '1',
                        'rule' => [
                            Elm::number('svip_number', '有效期（天）')->required()->min(0),
                        ]
                    ],
                    [
                        'value' =>'2',
                        'rule' => [
                            Elm::number('svip_number', '有效期（天）')->required()->min(0),
                        ]
                    ],
                    [
                        'value' => '3',
                        'rule' => [
                            Elm::input('svip_number1', '有效期（天）','永久期')->disabled(true),
                            Elm::input('svip_number', '有效期（天）','永久期')->hiddenStatus(true),
                        ]
                    ],
                ])->appendRule('suffix', [
                    'type' => 'div',
                    'style' => ['color' => '#999999'],
                    'domProps' => [
                        'innerHTML' =>'试用期每个用户只能购买一次，购买过付费会员之后将不在展示，不可购买',
                    ]
                ]),
            Elm::number('cost_price', '原价')->required(),
            Elm::number('price', '优惠价')->required(),
            Elm::number('sort', '排序'),
            Elm::switches('status', '是否显示')->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
        ];
        $form->setRule($rules);
        if ($formData && $formData['svip_type'] == 3) $formData['svip_number'] = '永久期';
        return $form->setTitle(is_null($id) ? '添加' : '编辑')->formData($formData);
    }
}
