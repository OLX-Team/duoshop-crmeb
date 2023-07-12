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


use app\common\repositories\article\ArticleRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\diy\DiyRepository;
use app\common\repositories\system\diy\PageLinkRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\controller\admin\AuthController;
use app\services\diy\DiyServices;
use app\common\repositories\system\diy\PageCategoryRepository;
use app\services\diy\PageLinkServices;
use app\services\product\category\StoreCategoryServices;
use crmeb\basic\BaseController;
use think\App;

/**
 * Class PageLink
 * @package app\controller\admin\v1\diy
 */
class PageLink extends BaseController
{

    protected $repository;
    public function __construct(App $app, PageLinkRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params([['status',1]]);
        $where['is_mer'] = $this->request->param('type',0);
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function createForm()
    {
        $isMer  = $this->request->param('type',0);
        return app('json')->success(formToData($this->repository->form(0, $isMer)));
    }

    public function create()
    {
        $data = $this->request->params([
            'cate_id',
            'name',
            'url',
            'param',
            'example',
            'status',
            'sort',
        ]);
        $data['is_mer']  = $this->request->param('type',0);
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        $isMer  = $this->request->param('type',0);
        return app('json')->success(formToData($this->repository->form($id, $isMer)));
    }

    public function update($id)
    {
        if ( !$this->repository->existsWhere(['id' => $id]))
            return app('json')->fail('数据不存在');
        $data = $this->request->params([
            'cate_id',
            'name',
            'url',
            'param',
            'example',
            'status',
            'sort',
        ]);
        $this->repository->update($id,$data);
        return app('json')->success('编辑成功');
    }


    /**
     * 获取页面链接
     * @param $cate_id
     * @return mixed
     */
    public function getLinks($id, PageCategoryRepository $pageCategoryServices)
    {
        if (!$id) return app('json')->fail('缺少参数');
        $category = $pageCategoryServices->get((int)$id);
        if (!$category) {
            return app('json')->fail('页面分类不存在');
        }
        [$page, $limit] = $this->getPage();
        switch ($category['type']) {
            case 'special':
                $diyServices = app()->make(ArticleRepository::class);
                $data = $diyServices->search(0,['status' => 1], $page, $limit);
                break;
            case 'product_category':
                $storeCategoryServices = app()->make(StoreCategoryRepository::class);
                $data = $storeCategoryServices->getApiFormatList($this->request->merId(),1);
                break;
            case 'merchant':
                $data = app()->make(MerchantRepository::class)->lst(['mer_state' => 1, 'status' => 1],$page,$limit);
                break;
            case 'active':
                $groupid = $this->request->merId() ? 95 : 94;
                $query = app()->make(GroupDataRepository::class)
                ->getGroupDataWhere($this->request->merId(),$groupid)
                ->where('status',1);
                $data['count'] = $query->count();
                $data['list'] = $query->page($page, $limit)->select()->toArray();
                break;
            case 'points_cate':
                $storeCategoryServices = app()->make(StoreCategoryRepository::class);
                $data = $storeCategoryServices->getApiFormatList($this->request->merId(),1,1);
                break;
            default:
                $data = $this->repository->getLinkList($id,$this->request->merId());
                break;
        }
        return app('json')->success($data);
    }

    /**
     * 删除链接
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) return app('json')->fail('参数错误');
        if ( !$this->repository->existsWhere(['id' => $id]))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功!');
    }

}
