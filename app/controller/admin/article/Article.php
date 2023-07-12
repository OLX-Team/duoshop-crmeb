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

namespace app\controller\admin\article;

use crmeb\basic\BaseController;
use app\common\repositories\article\ArticleCategoryRepository;
use app\common\repositories\article\ArticleContentRepository;
use app\common\repositories\article\ArticleRepository;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use app\validate\admin\ArticleValidate;

class Article extends BaseController
{
    /**
     * @var ArticleRepository
     */
    protected $repository;

    /**
     * Article constructor.
     * @param App $app
     * @param ArticleRepository $repository
     */
    public function __construct(App $app,ArticleRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    /**
     * @return mixed
     * @author Qinii
     */
    public function getList()
    {
        [$page, $limit] = $this->getPage();

        $where = $this->request->params(['cid','title']);

        return app('json')->success($this->repository->search($this->request->merId(),$where, $page, $limit));
    }

    /**
     * 添加
     * @param ArticleValidate $validate
     * @param ArticleCategoryRepository $repository
     * @return mixed
     * @author Qinii
     */
    public function create(ArticleValidate $validate,ArticleCategoryRepository $repository)
    {
        $data = $this->checkParams($validate);
        $data['admin_id'] = $this->request->adminId();
        $data['mer_id']   = $this->request->merId() ;
        if (!$repository->merExists(0,$data['cid']))
            return app('json')->fail('分类不存在');
        $this->repository->create($data);
        return  app('json')->success('添加成功');

    }

    public function detail($id)
    {
        if (!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');

        return app('json')->success($this->repository->get($id,$this->request->merId()));
    }

    /**
     * 更新
     * @param $id
     * @param ArticleValidate $validate
     * @param ArticleCategoryRepository $articleCategoryRepository
     * @return mixed
     * @author Qinii
     */
    public function update($id,ArticleValidate $validate,ArticleCategoryRepository $articleCategoryRepository)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        if (!$articleCategoryRepository->merExists($this->request->merId(),$data['cid']))
            return app('json')->fail('分类不存在');

        $this->repository->update($id,$data);

        return  app('json')->success('编辑成功');


    }

    /**
     * 删除
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author Qinii
     */
    public function delete($id)
    {
        if (!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');

        $this->repository->delete($id,$this->request->merId());

        return  app('json')->success('删除成功');
    }


    /**
     * @param ArticleValidate $validate
     * @return array
     * @author Qinii
     */
    public function checkParams(ArticleValidate $validate)
    {
        $data = $this->request->params([['cid', 0], 'title', 'content', 'author', 'image_input','status','sort','synopsis','is_hot','is_banner','url']);
        $validate->check($data);
        return $data;
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if(!$this->repository->get($id)) return app('json')->fail('数据不存在');
        $this->repository->switchStatus($id, ['status' => $status]);
        return app('json')->success('修改成功');
    }

}
