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


namespace app\controller\admin\system\merchant;


use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\validate\admin\MerchantCategoryValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class MerchantCategory
 * @package app\controller\admin\system\merchant
 * @author xaboy
 * @day 2020-05-06
 */
class MerchantCategory extends BaseController
{
    /**
     * @var MerchantCategoryRepository
     */
    protected $repository;

    /**
     * MerchantCategory constructor.
     * @param App $app
     * @param MerchantCategoryRepository $repository
     */
    public function __construct(App $app, MerchantCategoryRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-06
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList([], $page, $limit));
    }

    public function getOptions()
    {
        return app('json')->success($this->repository->allOptions());
    }

    /**
     * @param MerchantCategoryValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-05-06
     */
    public function create(MerchantCategoryValidate $validate)
    {
        $data = $this->checkParams($validate);
        $data['commission_rate'] = bcdiv($data['commission_rate'], 100, 4);
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-06
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form()));
    }


    /**
     * @param $id
     * @param MerchantCategoryValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-06
     */
    public function update($id, MerchantCategoryValidate $validate)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $data['commission_rate'] = bcdiv($data['commission_rate'], 100, 4);
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-06
     */
    public function updateForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    /**
     * @param $id
     * @param MerchantRepository $merchantRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-06
     */
    public function delete($id, MerchantRepository $merchantRepository)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        if ($merchantRepository->fieldExists('category_id', $id))
            return app('json')->fail('存在商户,无法删除');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    /**
     * @param MerchantCategoryValidate $validate
     * @return array
     * @author xaboy
     * @day 2020-05-06
     */
    public function checkParams(MerchantCategoryValidate $validate)
    {
        $data = $this->request->params(['category_name', ['commission_rate', 0]]);
        $validate->check($data);
        return $data;
    }
}
