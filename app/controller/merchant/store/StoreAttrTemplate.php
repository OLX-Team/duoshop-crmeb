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


namespace app\controller\merchant\store;


use crmeb\basic\BaseController;
use app\common\repositories\store\StoreAttrTemplateRepository;
use app\validate\merchant\StoreAttrTemplateValidate;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class StoreAttrTemplate
 * @package app\controller\merchant\store
 * @author xaboy
 * @day 2020-05-06
 */
class StoreAttrTemplate extends BaseController
{
    /**
     * @var StoreAttrTemplateRepository
     */
    protected $repository;

    /**
     * StoreAttrTemplate constructor.
     * @param App $app
     * @param StoreAttrTemplateRepository $repository
     */
    public function __construct(App $app, StoreAttrTemplateRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-06
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $data = $this->repository->getList($this->request->merId(), [], $page, $limit);

        return app('json')->success($data);
    }

    public function getlist()
    {
        return app('json')->success($this->repository->list($this->request->merId()));
    }
    /**
     * @param StoreAttrTemplateValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-05-06
     */
    public function create(StoreAttrTemplateValidate $validate)
    {
        $data = $this->checkParams($validate);
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);

        return app('json')->success('添加成功');
    }

    /**
     * @param $id
     * @param StoreAttrTemplateValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-06
     */
    public function update($id, StoreAttrTemplateValidate $validate)
    {
        $merId = $this->request->merId();

        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');
        $data = $this->checkParams($validate);
        $data['mer_id'] = $merId;
        $this->repository->update($id, $data);

        return app('json')->success('编辑成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-06
     */
    public function delete($id)
    {
        $merId = $this->request->merId();
        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id, $merId);

        return app('json')->success('删除成功');
    }

    /**
     * @param StoreAttrTemplateValidate $validate
     * @return array
     * @author xaboy
     * @day 2020-05-06
     */
    public function checkParams(StoreAttrTemplateValidate $validate)
    {
        $data = $this->request->params(['template_name', ['template_value', []]]);
        $validate->check($data);
        return $data;
    }
}
