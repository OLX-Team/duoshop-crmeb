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
namespace app\controller\api\server;

use crmeb\basic\BaseController;
use app\common\repositories\store\service\StoreServiceRepository;
use app\validate\merchant\StoreAttrTemplateValidate;
use think\App;
use app\common\repositories\store\StoreAttrTemplateRepository;
use think\exception\HttpResponseException;

class StoreProductAttrTemplate extends BaseController
{
    protected $merId;
    protected $repository;

    public function __construct(App $app, StoreAttrTemplateRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->merId = $this->request->route('merId');
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword']);
        $data = $this->repository->getList($this->merId, $where, $page, $limit);

        return app('json')->success($data);
    }

    public function getlist()
    {
        return app('json')->success($this->repository->list($this->merId));
    }

    public function create(StoreAttrTemplateValidate $validate)
    {
        $data = $this->checkParams($validate);
        $data['mer_id'] = $this->merId;
        $this->repository->create($data);

        return app('json')->success('添加成功');
    }

    public function update($id, StoreAttrTemplateValidate $validate)
    {
        $merId = $this->merId;
        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');
        $data = $this->checkParams($validate);
        $data['mer_id'] = $merId;
        $this->repository->update($id, $data);

        return app('json')->success('编辑成功');
    }

    public function detail($id)
    {
        if (!$this->repository->merExists($this->merId, $id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id,$this->merId));
    }

    public function batchDelete()
    {
        $ids = $this->request->param('ids');
        $merId = $this->merId;
        foreach ($ids as $id){
            if (!$this->repository->merExists($merId, $id))
                return app('json')->fail('ID:'.$id.' 不存在');
        }
        $this->repository->delete($ids, $merId);

        return app('json')->success('删除成功');
    }

    public function checkParams(StoreAttrTemplateValidate $validate)
    {
        $data = $this->request->params(['template_name', ['template_value', []]]);
        $validate->check($data);
        return $data;
    }


}
