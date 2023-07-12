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

use think\App;
use crmeb\basic\BaseController;
use app\validate\admin\StoreBrandValidate as validate;
use app\common\repositories\store\StoreBrandRepository;

class StoreBrand extends BaseController
{

    protected $repository;

    /**
     * ArticleCategory constructor.
     * @param App $app
     * @param StoreBrandRepository $repository
     */
    public function __construct(App $app, StoreBrandRepository $repository)
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
        [$page , $limit] = $this->getPage();
        $where = $this->request->params(['brand_category_id','brand_name']);

        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->parentExists($data['brand_category_id']))
            return app('json')->fail('上级分类不存在');
        if ($this->repository->merExistsBrand($data['brand_name']))
            return app('json')->fail('该品牌已存在');
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function update($id,validate $validate)
    {
        $data = $this->checkParams($validate);
        if(!$this->repository->meExists($id))
            return app('json')->fail('数据不存在');
        if (!$this->repository->parentExists($data['brand_category_id']))
            return app('json')->fail('上级分类不存在');
        $this->repository->update($id,$data);
        return app('json')->success('编辑成功');
    }


    public function delete($id)
    {
        if(!$this->repository->meExists($id))
            return app('json')->fail('数据不存在');
        if($this->repository->getBrandHasProduct($id))
            return app('json')->fail('该品牌下存在商品');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    public function detail($id)
    {
        if (!$this->repository->meExists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id));
    }
    /**
     * 验证
     * @param  validate $validate
     * @param bool $isCreate
     * @return array
     * @author Qinii
     */
    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['brand_category_id','brand_name','is_show','sort','pic']);
        $validate->check($data);
        return $data;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/27
     * @return mixed
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form()));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/27
     * @param $id
     * @return mixed
     */
    public function updateForm($id)
    {
        if (!$this->repository->meExists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/27
     * @param int $id
     * @return mixed
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->meExists($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id, ['is_show' => $status]);
        return app('json')->success('修改成功');
    }
}
