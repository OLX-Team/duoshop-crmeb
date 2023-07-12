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

use app\common\repositories\store\coupon\StoreCouponRepository;
use app\validate\admin\StoreCategoryValidate as validate;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\StoreCategoryRepository as repository;

class StoreCategory extends BaseController
{

    /**
     * @var repository|ArticleCategoryRepository
     */
    protected $repository;

    /**
     * ArticleCategory constructor.
     * @param App $app
     * @param ArticleCategoryRepository $repository
     */
    public function __construct(App $app, repository $repository)
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
        return app('json')->success($this->repository->getFormatList($this->request->merId()));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @return mixed
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form($this->request->merId())));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param $id
     * @return mixed
     */
    public function updateForm($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($this->request->merId(),$id)));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param validate $validate
     * @return mixed
     */
    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        $data['cate_name'] = trim($data['cate_name']);
        if($data['cate_name'] == '')  return app('json')->fail('分类名不可为空');

        if ($data['pid'] && !$this->repository->merExists($this->request->merId(), $data['pid']))
            return app('json')->fail('上级分类不存在');
        if ($data['pid'] && !$this->repository->checkLevel($data['pid'],0,$this->request->merId()))
            return app('json')->fail('不可添加更低阶分类');
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param $id
     * @param validate $validate
     * @return mixed
     */
    public function update($id,validate $validate)
    {
        $data = $this->checkParams($validate);

        if(!$this->repository->checkUpdate($id,$data['pid'])){
            if (!$this->repository->merExists($this->request->merId(), $id))
                return app('json')->fail('数据不存在');
            if ($data['pid'] && !$this->repository->merExists($this->request->merId(), $data['pid']))
                return app('json')->fail('上级分类不存在');
            if ($data['pid'] && !$this->repository->checkLevel($data['pid'],0,$this->request->merId()))
                return app('json')->fail('不可添加更低阶分类');
            if (!$this->repository->checkChangeToChild($id,$data['pid']))
                return app('json')->fail('无法修改到当前分类到子集，请先修改子类');
            if (!$this->repository->checkChildLevel($id,$data['pid'], $this->request->merId()))
                return app('json')->fail('子类超过最低限制，请先修改子类');
        }
        $this->repository->update($id,$data);
        return app('json')->success('编辑成功');
    }

    /**
     * 修改状态
     * @param int $id
     * @return mixed
     * @author Qinii
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->switchStatus($id, $status);
        return app('json')->success('修改成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        if ($this->repository->hasChild($id))
            return app('json')->fail('该分类存在子集，请先处理子集');

        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id));
    }
    /**
     * 验证
     * @param WechatNewsValidate $validate
     * @param bool $isCreate
     * @return array
     * @author Qinii
     */
    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['pid','cate_name','is_show','pic','sort']);
        $validate->check($data);
        return $data;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/16
     * @return mixed
     */
    public function getTreeList()
    {
        $data = $this->repository->getTreeList($this->request->merId(),1);
        $ret = [];
        foreach ($data as $datum) {
            if (isset($datum['children'])) {
                $ret[] = $datum;
            }
        }
        return app('json')->success($ret);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function getList()
    {
        $type = $this->request->param('type',null);
        $lv = $this->request->param('lv',null);
        if (!is_null($lv)) $lv = $lv + 1;
        $data = $this->repository->getList($type,$lv);
        if ($lv) {
            $ret = $data;
        } else {
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
        }
        return app('json')->success($ret);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function BrandList()
    {
        return app('json')->success($this->repository->getBrandList());
    }


    public function switchIsHot($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');

        $this->repository->updateStatus($id, ['is_hot' => $status]);
        return app('json')->success('修改成功');
    }
}
