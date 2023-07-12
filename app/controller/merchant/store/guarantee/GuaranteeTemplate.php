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
namespace app\controller\merchant\store\guarantee;

use app\common\repositories\store\GuaranteeRepository;
use app\common\repositories\store\GuaranteeTemplateRepository;
use app\validate\admin\GuaranteeTemplateValidate;
use think\App;
use crmeb\basic\BaseController;

class GuaranteeTemplate extends BaseController
{
    /**
     * @var GuaranteeTemplateRepository
     */
    protected $repository;

    /**
     * Product constructor.
     * @param App $app
     * @param GuaranteeTemplateRepository $repository
     */
    public function __construct(App $app, GuaranteeTemplateRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date','keyword']);
        $where['is_del'] = 0;
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->getList($where,$page, $limit);
        return app('json')->success($data);
    }

    public function create(GuaranteeTemplateValidate $validate)
    {
        $data = $this->request->params(['template_name','template_value',['status',1],'sort']);
        $validate->check($data);
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function detail($id)
    {
        $ret = $this->repository->detail($id,$this->request->merId());
        return app('json')->success($ret);
    }

    public function update($id,GuaranteeTemplateValidate $validate)
    {
        $data = $this->request->params(['template_name','template_value',['status',1],'sort']);
        $validate->check($data);
        $this->repository->detail($id,$this->request->merId());

        $data['mer_id'] = $this->request->merId();
        $this->repository->edit($id,$data);

        return app('json')->success('编辑成功');
    }

    public function delete($id)
    {
        $this->repository->detail($id,$this->request->merId());

        $this->repository->delete($id);

        return app('json')->success('删除成功');
    }

    /**
     * TODO 添加模板筛选的条款数据
     * @return \think\response\Json
     * @author Qinii
     * @day 5/25/21
     */
    public function select()
    {
        $where['keyword'] = $this->request->param('keyword');
        $where['is_del'] = 0;
        $where['status'] = 1;
        $data = app()->make(GuaranteeRepository::class)->select($where);

        return app('json')->success($data);
    }

    public function sort($id)
    {
        $ret = $this->repository->detail($id,$this->request->merId());
        if(!$ret) return app('json')->fail('数据不存在');
        $data = [
            'sort' => $this->request->param('sort'),
        ];
        $this->repository->update($id,$data);

        return app('json')->success('修改成功');
    }

    /**
     * TODO 商品选择模板的下拉数据
     * @return \think\response\Json
     * @author Qinii
     * @day 5/25/21
     */
    public function list()
    {
        $data = $this->repository->list($this->request->merId());
        return app('json')->success($data);
    }

    public function switchStatus($id)
    {
        $ret = $this->repository->detail($id,$this->request->merId());
        if(!$ret) return app('json')->fail('数据不存在');
        $data = [
            'status' => $this->request->param('status') == 1 ?: 0,
        ];
        $this->repository->update($id,$data);

        return app('json')->success('修改成功');
    }
}
