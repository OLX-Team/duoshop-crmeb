<?php

namespace app\controller\admin\system\diy;

use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\diy\DiyRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\groupData\GroupRepository;
use crmeb\basic\BaseController;

class VisualConfig extends BaseController
{
    public function storeStreet()
    {
        return app('json')->success(systemConfig(['mer_location', 'store_street_theme']) + ['mer_location' => 0, 'store_street_theme' => 0]);
    }

    public function setStoreStreet()
    {
        $data = $this->request->params(['mer_location', 'store_street_theme']);
        app()->make(ConfigValueRepository::class)->setFormData($data, 0);
        return app('json')->success('编辑成功');
    }

    public function userIndex()
    {
        $my_banner = systemGroupData('my_banner');
        $my_menus = systemGroupData('my_menus');
        $theme = app()->make(DiyRepository::class)->getThemeVar(systemConfig('global_theme'));
        return app('json')->success(compact('my_banner', 'my_menus', 'theme'));
    }

    public function setUserIndex()
    {
        $data = $this->request->params(['my_banner', 'my_menus']);
        $make = app()->make(GroupDataRepository::class);
        $make->setGroupData('my_banner', 0, $data['my_banner']);
        $make->setGroupData('my_menus', 0, $data['my_menus']);
        return app('json')->success('编辑成功');
    }

    /**
     * TODO 可视化配置里显示的组合数据
     * @return \think\response\Json
     * @author Qinii
     * @day 2023/4/11
     */
    public function getThemeKey()
    {
        $key = ['new_home_banner','hot_home_banner','best_home_banner','good_home_banner','sign_day_config','points_mall_banner','points_mall_district','points_mall_scope','open_screen_advertising'];
        $data['menu'] = app()->make(GroupRepository::class)->getSearch([])->where('group_key','in',$key)->field('group_id,group_name,group_key')->select();

        return app('json')->success($data);
    }


    /**
     * TODO 根据每个key
     * @param $key
     * @return \think\response\Json
     * @author Qinii
     * @day 2023/4/12
     */
    public function getTheme($key)
    {
        [$page, $limit] = $this->getPage();
        $groupRepository = app()->make(GroupRepository::class);
        $group = $groupRepository->getWhere(['group_key' => $key]);
        $data = app()->make(GroupDataRepository::class)->getGroupDataLst($this->request->merId(),$group->group_id,$page, $limit);
        if ($key == 'open_screen_advertising') {
            $data['config'] = systemConfig(['open_screen_switch','open_screen_time','open_screen_space']);
        }
        return app('json')->success($data);
    }

    /**
     * TODO 写入可视化数据
     * @param $key
     * @author Qinii
     * @day 2023/4/11
     */
    public function setTheme($key)
    {
        $grouop = ['sign_day_config','points_mall_scope'];
        $config = $this->request->param('config',[]);
        $list = $this->request->param('data',[]);
        if ($config) {
            app()->make(ConfigValueRepository::class)->setFormData($config, 0);
        }
        $make = app()->make(GroupDataRepository::class);
        if (in_array($key,$grouop))  return app('json')->success('编辑成功');
        if ($list) {
            $make->setGroupData($key, 0, $list);
        } else {
            $make->clearGroup($key, 0);
        }
        return app('json')->success('编辑成功');
    }
}
