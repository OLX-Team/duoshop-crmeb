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


namespace app\controller\admin\system\attachment;

use app\common\repositories\system\attachment\AttachmentCategoryRepository;
use app\validate\admin\AttachmentCategoryValidate;
use crmeb\basic\BaseController;
use Exception;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\response\Json;

/**
 * Class AttachmentCategory
 * @package app\controller\admin\system\attachment
 * @author xaboy
 * @day 2020-04-22
 */
class AttachmentCategory extends BaseController
{
    /**
     * @var AttachmentCategoryRepository
     */
    protected $repository;

    /**
     * @var int
     */
    protected $merId;

    /**
     * AttachmentCategory constructor.
     * @param App $app
     * @param AttachmentCategoryRepository $repository
     */
    public function __construct(App $app, AttachmentCategoryRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->merId = $this->request->merId();
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-15
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form($this->merId)));
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-15
     */
    public function updateForm($id)
    {
        if (!$this->repository->merExists($this->merId, $id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($this->merId, $id)));
    }

    /**
     * 获取带级别列表
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 张先生
     * @date 2020-03-26
     */
    public function getFormatList()
    {
        $result = $this->repository->getFormatList($this->merId);
        return app('json')->success($result);
    }

    /**
     * @param AttachmentCategoryValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-04-15
     */
    public function create(AttachmentCategoryValidate $validate)
    {
        $data = $this->checkParam($validate);
        if ($data['pid'] && !$this->repository->merExists($this->merId, $data['pid']))
            return app('json')->fail('上级分类不存在');
        $data['mer_id'] = $this->merId;
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * 更新
     * @param int $id id
     * @param AttachmentCategoryValidate $validate
     * @return mixed
     * @throws DbException
     * @author 张先生
     * @date 2020-03-30
     */
    public function update($id, AttachmentCategoryValidate $validate)
    {
        $data = $this->checkParam($validate);
        if ($data['pid'] && !$this->repository->merExists($this->merId, $data['pid']))
            return app('json')->fail('上级分类不存在');
        if (!$this->repository->merExists($this->merId, $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $this->merId, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * 添加和修改参数验证
     * @param AttachmentCategoryValidate $validate 验证规则
     * @return mixed
     * @author 张先生
     * @date 2020-03-30
     */
    private function checkParam(AttachmentCategoryValidate $validate)
    {
        $data = $this->request->params(['pid', 'attachment_category_name', 'attachment_category_enname', 'sort']);
        $data['attachment_category_enname'] = trim($data['attachment_category_enname']) ?: 'def';
        $validate->check($data);
        return $data;
    }

    /**
     * 删除单个
     * @param int $id
     * @return Json
     * @throws Exception
     * @author 张先生
     * @date 2020-03-30
     */
    public function delete($id)
    {
        if ($this->repository->merFieldExists($this->merId, 'pid', $id))
            return app('json')->fail('存在子级,无法删除');
        $this->repository->delete($id, $this->merId);
        return app('json')->success();
    }
}
