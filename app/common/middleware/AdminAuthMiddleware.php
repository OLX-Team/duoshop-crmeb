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


namespace app\common\middleware;

use app\common\repositories\system\auth\MenuRepository;
use app\common\repositories\system\auth\RoleRepository;
use app\Request;
use think\exception\ValidateException;
use think\Response;

class AdminAuthMiddleware extends BaseMiddleware
{

    public function before(Request $request)
    {
        $admin = $request->adminInfo();

        /** @var RoleRepository $role */
        $role = app()->make(RoleRepository::class);

        /** @var MenuRepository $menu */
        $menu = app()->make(MenuRepository::class);

        if ($admin->level) {
            $rules = $role->idsByRules(0, $admin->roles);
            $menus = $menu->idsByRoutes($rules);
        } else {
            $rules = [];
            $menus = [];
        }

        $request->macro('adminAuth', function () use (&$menus) {
            return $menus;
        });

        $request->macro('adminRule', function () use (&$rules) {
            return $rules;
        });

        $request->macro('checkAuth', function ($name, $vars) use (&$admin, &$menus, &$menu) {
            if (!$name || !$admin->level) return true;
            $isset = false;
            foreach ($menus as $_menu) {
                $keys = $menu->tidyParams($_menu['params']);
                if ($_menu['route'] != $name) continue;
                $isset = true;
                if (!count($keys)) return true;
                if ($menu->checkParams($keys, $vars))
                    return true;
            }
            if ($isset || $menu->routeExists($name))
                return false;
            return true;
        });

        $rule = $request->rule();
        if (!$rule) {
            return true;
        }
        $options = $rule->getOption();
        if (!($options['_auth'] ?? true) && !isset($options['_form'])) {
            return true;
        }
        if (isset($options['_form'])) {
            $name = $options['_form'];
            $var = $options['_form_val'] ?? [];
        } else {
            $name = $rule->getName();
            $var = $rule->getVars();
        }
        if (!$request->checkAuth($name, $var))
            throw new ValidateException('没有权限访问');
    }

    public function after(Response $response)
    {
        // TODO: Implement after() method.
    }
}
