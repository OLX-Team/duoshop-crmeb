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


namespace app\common\repositories\system\attachment;


//附件
use app\common\dao\BaseDao;
use app\common\dao\system\attachment\AttachmentCategoryDao;
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
 * Class BaseRepository
 * @package common\repositories
 * @mixin AttachmentCategoryDao
 */
class AttachmentCategoryRepository extends BaseRepository
{
    /**
     * AttachmentCategoryRepository constructor.
     * @param AttachmentCategoryDao $dao
     */
    public function __construct(AttachmentCategoryDao $dao)
    {
        /**
         * @var AttachmentCategoryDao
         */
        $this->dao = $dao;
    }

    /**
     * @param int $merId
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-22
     */
    public function form(int $merId, ?int $id = null, array $formData = []): Form
    {
        if ($merId) {
            $action = is_null($id) ? 'merchantAttachmentCategoryCreate' : 'merchantAttachmentCategoryUpdate';
        } else {
            $action = is_null($id) ? 'systemAttachmentCategoryCreate' : 'systemAttachmentCategoryUpdate';
        }

        $form = Elm::createForm(Route::buildUrl($action, is_null($id) ? [] : ['id' => $id])->build());
        $form->setRule([
            Elm::cascader('pid', '上级分类')->options(function () use ($id, $merId) {
                $menus = $this->dao->getAllOptions($merId);
                if ($id && isset($menus[$id])) unset($menus[$id]);
                $menus = formatCascaderData($menus, 'attachment_category_name');
                array_unshift($menus, ['label' => '顶级分类', 'value' => 0]);
                return $menus;
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])->appendValidate(Elm::validateInt()->required()->message('请选择上级分类')),
            Elm::input('attachment_category_name', '分类名称')->required(),
            Elm::input('attachment_category_enname', '分类目录', 'def')->required(),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
        ]);

        return $form->setTitle(is_null($id) ? '添加配置' : '编辑配置')->formData($formData);
    }

    /**
     * @param int $merId
     * @param int $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-22
     */
    public function updateForm(int $merId, int $id)
    {
        return $this->form($merId, $id, $this->dao->get($id, $merId)->toArray());
    }

    /**
     * @param int $merId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-22
     */
    public function getFormatList(int $merId)
    {
        return formatCategory($this->dao->getAll($merId)->toArray(), 'attachment_category_id');
    }

    /**
     * @param int $id
     * @param $merId
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-30
     */
    public function update(int $id, $merId, array $data)
    {
        $model = $this->dao->get($id, $merId);
        if ($model->pid != $data['pid']) {
            Db::transaction(function () use ($data, $model) {
                $data['path'] = $this->getPathById($data['pid']);
                if (substr_count(trim($data['path'], '/'), '/') > 1) throw new ValidateException('素材分类最多添加三级');
                $this->dao->updatePath($model->path . $model->attachment_category_id, $data['path'] . $model->attachment_category_id);
                $model->save($data);
            });
        } else {
            unset($data['path']);
            $this->dao->update($id, $data);
        }
    }

    /**
     * 添加
     * @param array $data 添加的数据
     * @return BaseDao|int|Model
     * @author 张先生
     * @date 2020-03-30
     */
    public function create(array $data)
    {
        $data['path'] = $this->getPathById($data['pid']);
        if (substr_count(trim($data['path'], '/'), '/') > 1) throw new ValidateException('素材分类最多添加三级');
        return $this->dao->create($data);
    }


    /**
     * 获取path
     * @param int $id 主键id
     * @return mixed
     * @author 张先生
     * @date 2020-03-30
     */
    private function getPathById(int $id = 0)
    {
        $result = '/';
        if ($id) {
            $result = $this->dao->getPathById($id) . $id . '/';
        }
        return $result;
    }

}
