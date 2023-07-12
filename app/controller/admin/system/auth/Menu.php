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


namespace app\controller\admin\system\auth;

use crmeb\basic\BaseController;
use app\common\repositories\system\auth\MenuRepository;
use app\validate\admin\MenuValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class Menu
 * @package app\controller\admin\system\menu
 * @author xaboy
 * @day 2020-04-08
 */
class Menu extends BaseController
{
    /**
     * @var int
     */
    protected $merchant;

    /**
     * @var MenuRepository
     */
    protected $repository;

    /**
     * Menu constructor.
     * @param App $app
     * @param MenuRepository $repository
     */
    public function __construct(App $app, MenuRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->merchant = $this->request->param('merchant', 0) == 0 ? 0 : 1;
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-08
     */
    public function getList()
    {
        $data = $this->repository->getList([], $this->merchant);
        $data['list'] = formatCategory($data['list'], 'menu_id');
        return app('json')->success($data);
    }

    /**
     * 创建
     * @param MenuValidate $validate
     * @return mixed
     * @author 张先生
     * @date 2020-03-30
     */
    public function create(MenuValidate $validate)
    {
        $data = $this->checkParam($validate);
        if (!$data['pid']) $data['pid'] = 0;
        if ($data['pid'] && !$this->repository->merExists($data['pid'], $this->merchant))
            return app('json')->fail('父级分类不存在');
        $data['is_mer'] = $this->merchant;
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * 更新
     * @param int $id id
     * @param MenuValidate $validate
     * @return mixed
     * @throws DbException
     * @author 张先生
     * @date 2020-03-30
     */
    public function update($id, MenuValidate $validate)
    {
        $data = $this->checkParam($validate);
        if (!$data['pid']) $data['pid'] = 0;
        if (!$this->repository->merExists($id, $this->merchant))
            return app('json')->fail('数据不存在');
        if ($data['pid'] && !$this->repository->merExists($data['pid'], $this->merchant))
            return app('json')->fail('父级分类不存在');
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-08
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->menuForm($this->merchant)));
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-08
     */
    public function updateForm($id)
    {
        if (!$this->repository->merExists($id, $this->merchant))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateMenuForm($id, $this->merchant)));
    }

    /**
     *
     * @param MenuValidate $validate 验证规则
     * @return mixed
     * @author 张先生
     * @date 2020-03-30
     */
    private function checkParam(MenuValidate $validate)
    {
        $data = $this->request->params([['pid', 0], 'icon', 'menu_name', 'route', ['params', '[]'], 'sort', ['is_show', 0], ['is_menu', 0]]);
        if ($data['is_menu'] != 1) $validate->isAuth();
        $validate->check($data);
        return $data;
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-08
     */
    public function delete($id)
    {
        if ($this->repository->pidExists($id))
            return app('json')->fail('存在下级,无法删除');
        $this->repository->delete($id, $this->merchant);

        return app('json')->success('删除成功');
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-04-10
     */
    public function menus()
    {
        $pre = '/' . config('admin.' . ($this->merchant ? 'merchant' : 'admin') . '_prefix');
        $menus = $this->request->adminInfo()->level
            ? $this->repository->ruleByMenuList($this->request->adminRule(), $this->merchant)
            : $this->repository->getValidMenuList($this->merchant);
        foreach ($menus as $k => $menu) {
            $menu['route'] = $pre . $menu['route'];
            $menus[$k] = $menu;
        }
        return app('json')->success(array_values(array_filter(formatCategory($menus, 'menu_id'), function ($v) {
            return 0 == $v['pid'];
        })));
    }
    /**
     * @return mixed
     * @author xaboy
     * @day 2020-04-10
     */
    public function merchantMenus()
    {
        $pre = '/' . config('admin.merchant_prefix');
        $merchant = $this->request->merchant();
        $menus = $this->request->adminInfo()->level
            ? $this->repository->ruleByMenuList($this->request->adminRule(), $this->merchant)
            : ($merchant->type_id ? $this->repository->typesByValidMenuList($merchant->type_id) : $this->repository->getValidMenuList($this->merchant));
        foreach ($menus as $k => $menu) {
            $menu['route'] = $pre . $menu['route'];
            $menus[$k] = $menu;
        }
        return app('json')->success(array_values(array_filter(formatCategory($menus, 'menu_id'), function ($v) {
            return 0 == $v['pid'];
        })));
    }
}
