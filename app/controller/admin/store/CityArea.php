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

namespace app\controller\admin\store;

use app\common\repositories\store\CityAreaRepository;
use think\App;
use crmeb\basic\BaseController;
use think\exception\ValidateException;

class CityArea extends BaseController
{
    protected $repository;

    /**
     * City constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, CityAreaRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @Time: 14:40
     * @return mixed
     */
    public function lst($id)
    {
        $where['parent_id'] = $id;
        return app('json')->success($this->repository->getList($where));
    }


    public function createForm($id)
    {
        return  app('json')->success(formToData($this->repository->form(0, $id)));
    }

    public function create()
    {
        $data = $this->checkParams();
        $this->repository->create($data);
        return  app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        return  app('json')->success(formToData($this->repository->form($id,null)));
    }

    public function update($id)
    {
        $data = $this->checkParams();
        if (!$res = $this->repository->get($id)){
            return  app('json')->fail('数据不存在');
        }
        $this->repository->update($id, $data);
        return  app('json')->success('编辑成功');
    }

    public function checkParams()
    {
        $type = [
            1 => 'province',
            2 => 'city',
            3 => 'area',
            4 => 'street',
        ];
        $data = $this->request->params(['parent_id','level','name',['path','/']]);
        if ($data['parent_id']) {
            $parent = $this->repository->get($data['parent_id']);
            if (!$parent) throw new ValidateException('上级数据不存在');
            $data['path'] = $parent['path'] . $parent['id'].'/';
        }
        $data['type'] = $type[$data['level']];
        if (!$data['name']) throw new ValidateException('请填写城市名称');
        return $data;
    }

    public function delete($id)
    {
        $res = $this->repository->getWhere(['parent_id' => $id]);
        if ($res) {
            return  app('json')->fail('数据存在子集不能删除');
        }
        $this->repository->delete($id);
        return  app('json')->success('删除成功');
    }
}
