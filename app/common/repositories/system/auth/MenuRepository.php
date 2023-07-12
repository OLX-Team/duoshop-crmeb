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

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\common\repositories\system\auth;


//附件
use app\common\dao\BaseDao;
use app\common\dao\system\menu\MenuDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Db;
use think\facade\Route;
use think\Model;

/**
 * Class BaseRepository
 * @package common\repositories
 * @mixin MenuDao
 */
class MenuRepository extends BaseRepository
{
    /**
     * MenuRepository constructor.
     * @param MenuDao $dao
     */
    protected $styles = array(
            'success' => "\033[0;32m%s\033[0m",
            'error' => "\033[31;31m%s\033[0m",
            'info' => "\033[33;33m%s\033[0m"
        );

    public $prompt = 'all';

    public function __construct(MenuDao $dao)
    {
        /**
         * @var MenuDao
         */
        $this->dao = $dao;
    }


    /**
     * @param array $where
     * @param int $merId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-16
     */
    public function getList(array $where, $merId = 0)
    {
        $query = $this->dao->search($where, $merId);
        $count = $query->count();
        $list = $query->hidden(['update_time', 'path'])->select()->toArray();
        return compact('count', 'list');
    }

    /**
     * @param array $data
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-04-09
     */
    public function create(array $data)
    {
        $data['path'] = '/';
        if ($data['pid']) {
            $data['path'] = $this->getPath($data['pid']) . $data['pid'] . '/';
        }
        return $this->dao->create($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-09
     */
    public function update(int $id, array $data)
    {
        $menu = $this->dao->get($id);
        if ($menu->pid != $data['pid']) {
            Db::transaction(function () use ($menu, $data) {
                $data['path'] = '/';
                if ($data['pid']) {
                    $data['path'] = $this->getPath($data['pid']) . $data['pid'] . '/';
                }
                $this->dao->updatePath($menu->path . $menu->menu_id . '/', $data['path'] . $menu->menu_id . '/');
                $menu->save($data);
            });
        } else {
            unset($data['path']);
            $this->dao->update($id, $data);
        }
    }

    /**
     * @param bool $is_mer
     * @return array
     * @author xaboy
     * @day 2020-04-18
     */
    public function getTree($merType = 0)
    {
        if (!$merType) {
            $options = $this->dao->getAllOptions();
        } else {
            $options = $this->dao->merchantTypeByOptions($merType);
        }
        return formatTree($options, 'menu_name');
    }

    /**
     * @param int $isMer
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-16
     */
    public function menuForm(int $isMer = 0, ?int $id = null, array $formData = []): Form
    {
        $action = $isMer == 0 ? (is_null($id) ? Route::buildUrl('systemMenuCreate')->build() : Route::buildUrl('systemMenuUpdate', ['id' => $id])->build())
            : (is_null($id) ? Route::buildUrl('systemMerchantMenuCreate')->build() : Route::buildUrl('systemMerchantMenuUpdate', ['id' => $id])->build());

        $form = Elm::createForm($action);
        $form->setRule([
            Elm::cascader('pid', '父级分类')->options(function () use ($id, $isMer) {
                $menus = $this->dao->getAllOptions($isMer, true);
                if ($id && isset($menus[$id])) unset($menus[$id]);
                $menus = formatCascaderData($menus, 'menu_name');
                array_unshift($menus, ['label' => '顶级分类', 'value' => 0]);
                return $menus;
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]]),
            Elm::select('is_menu', '权限类型', 1)->options([
                ['value' => 1, 'label' => '菜单'],
                ['value' => 0, 'label' => '权限'],
            ])->control([
                [
                    'value' => 0,
                    'rule' => [
                        Elm::input('menu_name', '路由名称')->required(),
                        Elm::textarea('params', '参数')->placeholder("路由参数:\r\nkey1:value1\r\nkey2:value2"),
                    ]
                ], [
                    'value' => 1,
                    'rule' => [
                        Elm::switches('is_show', '是否显示', 1)->inactiveValue(0)->activeValue(1)->inactiveText('关')->activeText('开'),
                        Elm::frameInput('icon', '菜单图标', '/' . config('admin.admin_prefix') . '/setting/icons?field=icon')->icon('el-icon-circle-plus-outline')->height('338px')->width('700px')->modal(['modal' => false]),
                        Elm::input('menu_name', '菜单名称')->required(),
                    ]
                ]
            ]),
            Elm::input('route', '路由'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999)
        ]);

        return $form->setTitle(is_null($id) ? '添加菜单' : '编辑菜单')->formData($formData);
    }


    /**
     * @param int $id
     * @param int $merId
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-16
     */
    public function updateMenuForm(int $id, $merId = 0)
    {
        return $this->menuForm($merId, $id, $this->dao->get($id)->toArray());
    }


    /**
     * @param string $params
     * @return array
     * @author xaboy
     * @day 2020-04-22
     */
    public function tidyParams(?string $params)
    {
        return $params ? array_reduce(explode('|', $params), function ($initial, $val) {
            $data = explode(':', $val, 2);
            if (count($data) != 2) return $initial;
            $initial[$data[0]] = $data[1];
            return $initial;
        }, []) : [];
    }

    /**
     * @param array $params
     * @param array $routeParams
     * @return bool
     * @author xaboy
     * @day 2020-04-23
     */
    public function checkParams(array $params, array $routeParams)
    {
        foreach ($routeParams as $k => $param) {
            if (isset($params[$k]) && $params[$k] != $param)
                return false;
        }
        return true;
    }

    public function formatPath($is_mer = 0)
    {
        $options = $this->getAll($is_mer);
        $options = formatCategory($options, 'menu_id');
        Db::transaction(function () use ($options) {
            foreach ($options as $option) {
                $this->_formatPath($option);
            }
        });
    }

    protected function _formatPath($parent, $path = '/')
    {
        $this->dao->update($parent['menu_id'], ['path' => $path]);
        foreach ($parent['children'] ?? [] as $item) {
            $itemPath = $path . $item['pid'] . '/';
            $this->_formatPath($item, $itemPath);
        }
    }


    public function commandMenu($type, $data, $prompt)
    {
        $res = [];
        if ($prompt) $this->prompt = $prompt;
        $isMer = ($type == 'sys') ? 0 : 1;

        foreach ($data as $key => $value) {
            try{
                $result = $this->dao->getMenuPid($key, $isMer, 0);
                if (!$result) {
                    $route = $key;
                    $isAppend =0;
                    if (substr($key,0,7) === 'append_') {
                        $isAppend = 1;
                        $route = substr($key,7);
                    }
                    $result = $this->dao->getMenuPid($route, $isMer, 1);
                    if (!$result && $key !== 'self') {
                        printf($this->styles['info'], '未找到菜单: '. $key);
                        echo PHP_EOL;
                        continue;
                    } else {
                        $result = $this->dao->create([
                            'pid' => $key == 'self' ? 0 : $result['menu_id'],
                            'path' => $key == 'self' ? '/' : $result['path'] . $result['menu_id'] . '/',
                            'menu_name' => $isAppend ? '附加权限' : '权限' ,
                            'route' => $key,
                            'is_mer' => $isMer,
                            'is_menu' => 0
                        ]);
                    }
                }
                $res = array_merge($res, $this->createSlit($isMer, $result['menu_id'], $result['path'], $value));
            }catch (\Exception $exception) {
                throw new Exception($key);
            }
        }
        $count = count($res);
        if (!empty($res)) $this->dao->insertAll($res);
        return $count;
    }

    /**
     * TODO 新增权限数据整理
     * @param int $isMer
     * @param int $menuId
     * @param string $path
     * @param array $data
     * @return array
     * @author Qinii
     * @day 3/18/22
     */
    public function createSlit(int $isMer,int $menuId, string  $path,array $data)
    {
        $arr = [];
        try {
            foreach ($data as $k => $v) {
                $result = $this->dao->getWhere(['route' => $v['route'], 'pid' => $menuId]);
                if (!$result) {
                    $arr[] = [
                        'pid' => $menuId,
                        'path' => $path . $menuId . '/',
                        'menu_name' => $v['menu_name'],
                        'route' => $v['route'],
                        'is_mer' => $isMer,
                        'is_menu' => 0,
                        'params' => $v['params'] ?? [],
                    ];
                    if ($this->prompt == 's') {
                        printf($this->styles['success'], '新增权限: ' . $v['menu_name'] . ' [' . $v['route'] . ']');
                        echo PHP_EOL;
                    }
                }
            }
            return $arr;
        }catch (\Exception $exception) {
            halt($isMer, $menuId, $path, $data);
        }
    }
}

