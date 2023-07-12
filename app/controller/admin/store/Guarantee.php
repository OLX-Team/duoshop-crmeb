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

use app\common\repositories\store\GuaranteeRepository;
use app\validate\admin\GuaranteeValidate;
use think\App;
use crmeb\basic\BaseController;

class Guarantee extends BaseController
{
    /**
     * @var GuaranteeRepository
     */
    protected $repository;

    /**
     * City constructor.
     * @param App $app
     * @param GuaranteeRepository $repository
     */
    public function __construct(App $app, GuaranteeRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date','keyword']);
        $where['is_del'] = 0;
        $data = $this->repository->getList($where,$page, $limit);
        return app('json')->success($data);
    }

    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form(null)));
    }

    public function create(GuaranteeValidate $validate)
    {
        $data = $this->checkParams($validate);
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    public function update($id,GuaranteeValidate $validate)
    {
        $ret = $this->repository->get($id);
        if(!$ret) return app('json')->fail('数据不存在');
        $data = $this->checkParams($validate);
        $this->repository->update($id,$data);
        return app('json')->success('编辑成功');
    }

    public function sort($id)
    {
        $ret = $this->repository->get($id);
        if(!$ret) return app('json')->fail('数据不存在');
        $data = [
            'sort' => $this->request->param('sort'),
        ];
        $this->repository->update($id,$data);

        return app('json')->success('修改成功');
    }

    public function switchStatus($id)
    {
        $ret = $this->repository->get($id);
        if(!$ret) return app('json')->fail('数据不存在');
        $data = [
            'status' => $this->request->param('status') == 1 ?: 0,
        ];
        $this->repository->update($id,$data);

        return app('json')->success('修改成功');
    }

    public function detail($id)
    {
        $ret = $this->repository->get($id);
        if(!$ret) return app('json')->fail('数据不存在');
        return app('json')->success($ret);
    }

    public function delete($id)
    {
        $ret = $this->repository->get($id);
        if(!$ret) return app('json')->fail('数据不存在');
        $this->repository->update($id,['is_del' => 1]);
        return app('json')->success('删除成功');
    }

    public function checkParams(GuaranteeValidate $validate)
    {
        $params = [
             "guarantee_name", "guarantee_info", "image", "status",  "sort",
        ];
        $data = $this->request->params($params);
        $validate->check($data);
        return $data;
    }
}
