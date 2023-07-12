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


namespace crmeb\services;

use app\common\repositories\system\config\ConfigClassifyRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\groupData\GroupRepository;
use crmeb\interfaces\RouteParserInterface;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Log;

class UpdateAuthInit implements RouteParserInterface
{
    public function create($route, $method = 'config')
    {
        return $this->{$method}($route);
    }

    public function config($route)
    {
        $resp[] = $route;
        $append = $route['option']['_append'] ?? [];
        try {
            $data = app()->make(ConfigClassifyRepository::class)->getSearch(['status' => 1])
                ->field('classify_name _alias,classify_key _params')
                ->select()->toArray();

            foreach ($data as $k => $v) {
                if ($v['_params'] == 'distribution_tabs') continue;
                $v['_path'] = '/systemForm/Basics/'.$v['_params'];
                $v['_name'] = $route['name'];
                $v['_alias'] = $route['option']['_alias'];
                $v['_repeat'] = true;
                $v['_append'] = $append;
                $resp[]['option'] = $v;
            }

        }catch (Exception $e) {
            throw new ValidateException('配置路由执行失败：' .$e->getMessage());
        }
        return $resp;
    }

    public function groupData($route)
    {
        $resp[] = $route;
        $append = $route['option']['_append'] ?? [];
        try {
            $data = app()->make(GroupRepository::class)->getSearch([])
                ->field('group_name _alias,group_id _params')
                ->select()->toArray();
            foreach ($data as $k => $v) {
                $v['_path'] = '/group/config/'.$v['_params'];
                $v['_name'] = $route['name'];
                $v['_alias'] = $route['option']['_alias'];
                $v['_repeat'] = true;
                $v['_append'] = $append;
                $resp[]['option'] = $v;
                $v['_path'] = '/group/topic/'.$v['_params'];
                $resp[]['option'] = $v;
            }
        }catch (Exception $e) {
           throw new ValidateException('组合数据路由执行失败：' .$e->getMessage());
        }
        return $resp;
    }

    public function agreement($route)
    {
        $resp[] = $route;
        try {
            $resp = [
                [
                    'option' => [
                        '_name'  =>'systemAgreeSave',
                        '_path'  =>'/marketing/presell/agreement',
                        '_alias' => '预售协议',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' => [
                        '_name'  =>'systemAgreeSave',
                        '_path'  =>'/promoter/commission',
                        '_alias' => '佣金说明',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' =>  [
                        '_name'  =>'systemAgreeSave',
                        '_path'  =>'/promoter/distribution',
                        '_alias' => '等级规则',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' =>  [
                        '_name'  =>'systemAgreeSave',
                        '_path'  =>'/marketing/Platform_coupon/instructions',
                        '_alias' => '使用说明',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' => [
                        '_name'  =>'systemAgreeSave',
                        '_path'  =>'/user/agreement',
                        '_alias' => '用户协议',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' => [
                        '_name'  =>'systemAgreeSave',
                        '_path'  =>'/user/member/description',
                        '_alias' => '会员等级规则',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' => [
                        '_name'  => 'systemAgreeSave',
                        '_path'  => '/setting/agreements',
                        '_alias' => '商户入住申请协议',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' => [
                        '_name'  => 'systemAgreeSave',
                        '_path'  => '/merchant/type/description',
                        '_alias' => '店铺类型说明 ',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
                [
                    'option' => [
                        '_name'  => 'systemAgreeSave',
                        '_path'  => '/accounts/invoiceDesc',
                        '_alias' => '发票说明 ',
                        '_repeat'=> true,
                        '_auth'  => true,
                    ],
                ],
            ];
        }catch (Exception $e) {
            throw new ValidateException('协议路由执行失败：' .$e->getMessage());
        }
        return $resp;
    }

}
