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
namespace app\controller\admin\system\diy;

use app\common\repositories\system\diy\PageCategoryRepository;
use app\common\repositories\system\diy\PageLinkRepository;
use crmeb\basic\BaseController;
use think\App;

class PageCategroy extends BaseController
{

    protected $repository;

    public function __construct(App $app, PageCategoryRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * 列表
     * @return mixed
     * @author Qinii
     */
    public function lst()
    {
        $where['is_mer'] = $this->request->param('type',0);
        return app('json')->success($this->repository->getFormatList($where));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @return mixed
     */
    public function createForm()
    {
        $type = $this->request->param('type',0);
        return app('json')->success(formToData($this->repository->form(0,$type)));
    }

    public function create()
    {
        $data = $this->request->params([
            'pid',
            'type',
            'name',
            'status',
            'sort',
            'is_mer',
           [ 'level',3],
        ]);
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->form($id)));
    }

    public function update($id)
    {
        $data = $this->request->params([
            'pid',
            'type',
            'name',
            'status',
            'sort',
            'is_mer'
        ]);
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    public function options()
    {
        $type = $this->request->param('type',0);
        return app('json')->success($this->repository->getSonCategoryList($type,0));
    }


    public function switchStatus($id)
    {
        $status = $this->request->param('status') == 1 ? 1: 0;
        $this->repository->update($id,['status' => $status]);
        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }
}
