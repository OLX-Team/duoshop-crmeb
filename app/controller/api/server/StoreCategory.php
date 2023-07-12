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

use app\common\repositories\store\StoreCategoryRepository;
use think\App;
use crmeb\basic\BaseController;
use think\exception\HttpResponseException;
use app\validate\admin\StoreCategoryValidate;
use app\common\repositories\store\service\StoreServiceRepository;

class StoreCategory extends BaseController
{
    protected $merId;
    protected $repository;

    public function __construct(App $app, StoreCategoryRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->merId = $this->request->route('merId');
    }

    public function lst($merId)
    {
        return app('json')->success($this->repository->getFormatList($merId));
    }

    public function create($merId, StoreCategoryValidate $validate)
    {
        $data = $this->checkParams($validate);
        $data['cate_name'] = trim($data['cate_name']);
        if($data['cate_name'] == '')  return app('json')->fail('分类名不可为空');

        if ($data['pid'] && !$this->repository->merExists($merId, $data['pid']))
            return app('json')->fail('上级分类不存在');
        if ($data['pid'] && !$this->repository->checkLevel($data['pid'],0,$merId))
            return app('json')->fail('不可添加更低阶分类');
        $data['mer_id'] = $merId;
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function update($merId, $id, StoreCategoryValidate $validate)
    {
        $data = $this->checkParams($validate);

        if(!$this->repository->checkUpdate($id,$data['pid'])){
            if (!$this->repository->merExists($merId, $id))
                return app('json')->fail('数据不存在');
            if ($data['pid'] && !$this->repository->merExists($merId, $data['pid']))
                return app('json')->fail('上级分类不存在');
            if ($data['pid'] && !$this->repository->checkLevel($data['pid'],0, $merId))
                return app('json')->fail('不可添加更低阶分类');
            if (!$this->repository->checkChangeToChild($id,$data['pid']))
                return app('json')->fail('无法修改到当前分类到子集，请先修改子类');
            if (!$this->repository->checkChildLevel($id,$data['pid'],$merId))
                return app('json')->fail('子类超过最低限制，请先修改子类');
        }
        $this->repository->update($id,$data);

        return app('json')->success('编辑成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists($this->merId, $id))
            return app('json')->fail('数据不存在');

        $this->repository->switchStatus($id, $status);
        return app('json')->success('修改成功');
    }

    public function detail($id)
    {
        if (!$this->repository->merExists($this->merId, $id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id));
    }

    public function delete($id)
    {
        if (!$this->repository->merExists($this->merId, $id))
            return app('json')->fail('数据不存在');
        if ($this->repository->hasChild($id))
            return app('json')->fail('该分类存在子集，请先处理子集');

        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function getTreeList()
    {
        $data = $this->repository->getTreeList($this->merId,1);
        $ret = [];
        foreach ($data as $datum) {
            if (isset($datum['children'])) {
                $ret[] = $datum;
            }
        }
        return app('json')->success($ret);
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function getList()
    {
        $data = $this->repository->getList(1);
        $ret = [];
        foreach ($data as $key => $value) {
            if (isset($value['children'])) {
                $level = [];
                foreach ($value['children'] as $child) {
                    if (isset($child['children'])) {
                        $level[] = $child;
                    }
                }
                if (isset($level) && !empty($level)) {
                    $value['children'] = $level;
                    $ret[] = $value;
                }
            }
        }
        return app('json')->success($ret);
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function BrandList()
    {
        return app('json')->success($this->repository->getBrandList());
    }

    /**
     * TODO
     * @param StoreCategoryValidate $validate
     * @return array
     * @author Qinii
     * @day 8/24/21
     */
    public function checkParams(StoreCategoryValidate $validate)
    {
        $data = $this->request->params(['pid','cate_name','is_show','pic','sort']);
        $validate->check($data);
        return $data;
    }
}
