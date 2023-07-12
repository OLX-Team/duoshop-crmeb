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


namespace app\controller\admin;


use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\system\CacheRepository;
use app\common\repositories\system\config\ConfigRepository;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\merchant\MerchantCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\user\UserVisitRepository;
use crmeb\basic\BaseController;
use crmeb\services\HttpService;
use crmeb\services\UploadService;
use think\facade\Cache;

/**
 * Class Common
 * @package app\controller\admin
 * @author xaboy
 * @day 2020/6/25
 */
class Common extends BaseController
{

    /**
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function main()
    {
        $res = Cache::store('file')->remember(self::class . '@main', function () {
            $today = $this->mainGroup('today');
            $yesterday = $this->mainGroup('yesterday');
            $lastWeek = $this->mainGroup(date('Y-m-d', strtotime('- 7day')));
            $lastWeekRate = [];
            foreach ($lastWeek as $k => $item) {
                $lastWeekRate[$k] = $this->getRate($item, $today[$k], 4);
            }
            return compact('today', 'yesterday', 'lastWeekRate');
        }, 2000 + random_int(600, 1200));
        return app('json')->success($res);
    }

    /**
     * TODO 上传视频key
     * @return \think\response\Json
     * @author Qinii
     * @day 3/11/22
     */
    public function temp_key()
    {
        $upload = UploadService::create();
        $re = $upload->getTempKeys();
        return app('json')->success($re);
    }

    /**
     * @param $date
     * @return array
     * @author xaboy
     * @day 2020/6/25
     */
    protected function mainGroup($date)
    {
        $userRepository = app()->make(UserRepository::class);
        $storeOrderRepository = app()->make(StoreOrderRepository::class);
        $merchantRepository = app()->make(MerchantRepository::class);
        $userVisitRepository = app()->make(UserVisitRepository::class);
        $payPrice = (float)$storeOrderRepository->dayOrderPrice($date);
        $userNum = (float)$userRepository->newUserNum($date);
        $storeNum = (float)$merchantRepository->dateMerchantNum($date);
        $visitUserNum = (float)$userVisitRepository->dateVisitUserNum($date);
        $visitNum = (float)$userVisitRepository->dateVisitNum($date);

        return compact('payPrice', 'userNum', 'storeNum', 'visitUserNum', 'visitNum');
    }

    /**
     * @param StoreOrderRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function order(StoreOrderRepository $repository)
    {
        $today = $repository->dayOrderPriceGroup('today')->toArray();
        $yesterday = $repository->dayOrderPriceGroup('yesterday')->toArray();
        $today = array_combine(array_column($today, 'time'), array_column($today, 'price'));
        $yesterday = array_combine(array_column($yesterday, 'time'), array_column($yesterday, 'price'));
        $time = getTimes();
        $order = [];
        foreach ($time as $item) {
            $order[] = [
                'time' => $item,
                'today' => $today[$item] ?? 0,
                'yesterday' => $yesterday[$item] ?? 0,
            ];
        }
        $todayPrice = $repository->dayOrderPrice('today');
        $yesterdayPrice = $repository->dayOrderPrice('yesterday');
        return app('json')->success(compact('order', 'todayPrice', 'yesterdayPrice'));
    }

    /**
     * @param StoreOrderRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function orderNum(StoreOrderRepository $repository)
    {
        $orderNum = $repository->dayOrderNum('today');

        $yesterdayNum = $repository->dayOrderNum('yesterday');
        $today = $repository->dayOrderNumGroup('today')->toArray();
        $today = array_combine(array_column($today, 'time'), array_column($today, 'total'));
        $monthOrderNum = $repository->dayOrderNum(date('Y/m/d', strtotime('first day of')) . ' 00:00:00' . '-' . date('Y/m/d H:i:s'));

        $date = date('Y/m/01 00:00:00', strtotime('last Month')) . '-' . date('Y/m/d 00:00:00', strtotime('-1 day', strtotime('first day of')));
        $beforeOrderNum = $repository->dayOrderNum($date);

        $monthRate = $this->getRate($beforeOrderNum, $monthOrderNum);
        $orderRate = $this->getRate($yesterdayNum, $orderNum);
        $time = getTimes();
        $data = [];
        foreach ($time as $item) {
            $data[] = [
                'total' => $today[$item] ?? 0,
                'time' => $item
            ];
        }
        $today = $data;
        return app('json')->success(compact('orderNum', 'today', 'monthOrderNum', 'monthRate', 'orderRate'));
    }

    /**
     * @param StoreOrderRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function orderUser(StoreOrderRepository $repository)
    {
        $orderNum = $repository->dayOrderUserNum('today');
        $yesterdayNum = $repository->dayOrderUserNum('yesterday');
        $today = $repository->dayOrderUserGroup('today')->toArray();
        $today = array_combine(array_column($today, 'time'), array_column($today, 'total'));
        $monthOrderNum = $repository->dayOrderUserNum(date('Y/m/d', strtotime('first day of')) . ' 00:00:00' . '-' . date('Y/m/d H:i:s'));

        $date = gmdate('Y/m/01 00:00:00', strtotime('last Month')) . '-' . date('Y/m/d 00:00:00', strtotime('-1 day', strtotime('first day of')));
        $beforeOrderNum = $repository->dayOrderUserNum($date);

        $monthRate = $this->getRate($beforeOrderNum, $monthOrderNum);
        $orderRate = $this->getRate($yesterdayNum, $orderNum);
        $time = getTimes();
        $data = [];
        foreach ($time as $item) {
            $data[] = [
                'total' => $today[$item] ?? 0,
                'time' => $item
            ];
        }
        $today = $data;
        return app('json')->success(compact('orderNum', 'today', 'monthOrderNum', 'monthRate', 'orderRate'));
    }

    /**
     * @param StoreOrderProductRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function merchantStock(StoreOrderProductRepository $repository)
    {
        $date = $this->request->param('date') ?: 'lately7';
        $res = Cache::store('file')->remember(self::class . '@merchantStock' . $date, function () use ($date, $repository) {
            $total = $repository->dateProductNum($date);
            $list = $repository->orderProductGroup($date)->toArray();
            foreach ($list as &$item) {
                $item['rate'] = bcdiv($item['total'], $total, 2);
            }

            return compact('list', 'total');
        }, 2000 + random_int(600, 1200));
        return app('json')->success($res);
    }

    /**
     * @param UserVisitRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function merchantVisit(UserVisitRepository $repository)
    {
        $date = $this->request->param('date') ?: 'lately7';
        $res = Cache::store('file')->remember(self::class . '@merchantVisit' . $date, function () use ($date, $repository) {
            $total = $repository->dateVisitMerchantTotal($date);
            $list = $repository->dateVisitMerchantNum($date)->toArray();
            foreach ($list as &$item) {
                $item['rate'] = bcdiv($item['total'], $total, 2);
            }
            return compact('list', 'total');
        }, 2000 + random_int(600, 1200));
        return app('json')->success($res);
    }

    /**
     * @param StoreOrderRepository $repository
     * @param MerchantCategoryRepository $merchantCategoryRepository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function merchantRate(StoreOrderRepository $repository, MerchantCategoryRepository $merchantCategoryRepository)
    {
        $date = $this->request->param('date') ?: 'lately7';
        $res = Cache::store('file')->remember(self::class . '@merchantRate' . $date, function () use ($repository, $merchantCategoryRepository, $date) {
            $total = $repository->dateOrderPrice($date);
            $list = $merchantCategoryRepository->dateMerchantPriceGroup($date)->toArray();
            $rate = 1;
            $pay_price = $total;
            foreach ($list as &$item) {
                $item['rate'] = bcdiv($item['pay_price'], $total, 2);
                $rate = bcsub($rate, $item['rate'], 2);
                $pay_price = bcsub($pay_price, $item['pay_price'], 2);
            }
            if ($rate > 0 && count($list)) {
                $list[] = [
                    'pay_price' => $pay_price,
                    'category_name' => '其他类',
                    'rate' => $rate
                ];
            }
            return compact('list', 'total');
        }, 2000 + random_int(600, 1200));

        return app('json')->success($res);
    }

    public function userData(UserRepository $repository, UserVisitRepository $visitRepository)
    {
        $date = $this->request->param('date') ?: 'lately7';
        $res = Cache::store('file')->remember(self::class . '@userData' . $date, function () use ($visitRepository, $repository, $date) {
            $newUserList = $repository->userNumGroup($date)->toArray();
            $newUserList = array_combine(array_column($newUserList, 'time'), array_column($newUserList, 'new'));
            $visitList = $visitRepository->dateVisitNumGroup($date)->toArray();
            $visitList = array_combine(array_column($visitList, 'time'), array_column($visitList, 'total'));
            $base = $repository->beforeUserNum(getStartModelTime($date));
            $time = getDatesBetweenTwoDays(getStartModelTime($date), date('Y-m-d'));
            $userList = [];
            $before = $base;
            foreach ($time as $item) {
                $new = $newUserList[$item] ?? 0;
                $before += $new;
                $userList[] = [
                    'total' => $before,
                    'new' => $new,
                    'visit' => $visitList[$item] ?? 0,
                    'day' => $item
                ];
            }
            return $userList;
        }, 2000 + random_int(600, 1200));

        return app('json')->success($res);
    }

    /**
     * @param $last
     * @param $today
     * @param int $scale
     * @return int|string|null
     * @author xaboy
     * @day 2020/6/25
     */
    protected function getRate($last, $today, $scale = 2)
    {
        if ($last == $today)
            return 0;
        else if ($last == 0)
            return $today;
        else if ($today == 0)
            return -$last;
        else
            return (float)bcdiv(bcsub($today, $last, $scale), $last, $scale);
    }

    /**
     * 申请授权
     * @return mixed
     */
    public function auth_apply()
    {
        $data = $this->request->params([
            ['company_name', ''],
            ['domain_name', ''],
            ['order_id', ''],
            ['phone', ''],
            ['label', 10],
            ['captcha', ''],
        ]);
        if (!$data['company_name']) {
            return app('json')->fail('请填写公司名称');
        }
        if (!$data['domain_name']) {
            return app('json')->fail('请填写授权域名');
        }
        if (!$data['phone']) {
            return app('json')->fail('请填写手机号码');
        }
        if (!$data['order_id']) {
            return app('json')->fail('请填写订单id');
        }
        if (!$data['captcha']) {
            return app('json')->fail('请填写验证码');
        }
        $res = HttpService::postRequest('http://authorize.crmeb.net/api/auth_apply', $data);
        if ($res === false) {
            return app('json')->fail('申请失败,服务器没有响应!');
        }
        $res = json_decode($res, true);
        if (isset($res['status'])) {
            if ($res['status'] == 400) {
                return app('json')->fail($res['msg'] ?? "申请失败");
            } else {
                return app('json')->success($res['msg'] ?? '申请成功', $res);
            }
        }
        return app('json')->fail("申请授权失败!");
    }

    public function uploadConfig(ConfigRepository $repository)
    {
        return app('json')->success(formToData($repository->uploadForm()));
    }

    public function saveUploadConfig(ConfigRepository $repository)
    {
        $formData = $this->request->post();
        if (!count($formData)) return app('json')->fail('保存失败');
        $repository->saveUpload($formData);

        return app('json')->success('保存成功');
    }

    public function loginConfig()
    {
        $login_logo = systemConfig('sys_login_logo');
        $menu_logo = systemConfig('sys_menu_logo');
        $menu_slogo = systemConfig('sys_menu_slogo');
        $login_title = systemConfig('sys_login_title');
        $sys_login_banner = systemConfig('sys_login_banner');
        $beian_sn = systemConfig('beian_sn');
        $login_banner = [];
        foreach ($sys_login_banner as $item) {
            $login_banner[] = [
                'pic' => $item,
                'name' => $item
            ];
        }

        return app('json')->success(compact('login_banner', 'login_logo', 'login_title', 'menu_slogo', 'menu_logo', 'beian_sn'));
    }

    public function version()
    {
        $sys_open_version = systemConfig('sys_open_version');
        $data = [
            'version' => get_crmeb_version('未知'),
            'year' => '© 2014-' . date('Y', time()),
            'beian_sn' => systemConfig('beian_sn'),
            'url' => 'www.crmeb.com',
            'Copyright' => 'Copyright',
            'sys_open_version' => $sys_open_version === '' ? '1' : $sys_open_version,
        ];

        $copyright = app()->make(CacheRepository::class)->getResultByKey('copyright_status');
        if (!$copyright) {
            $data['status'] = -1;
        } else {
            $copyright = app()->make(CacheRepository::class)->search(['copyright_status', 'copyright_context', 'copyright_image']);
            $data['status'] = 1;
            $data['Copyright'] = $copyright['copyright_context'] ?? '';
            $data['image'] = $copyright['copyright_image'] ?? '';
        }
        return app('json')->success($data);
    }

    public function config()
    {
        $config = systemConfig(['delivery_type', 'delivery_status', 'sms_use_type', 'hot_ranking_lv', 'hot_ranking_switch']);
        return app('json')->success($config);
    }

    public function getChangeColor()
    {
        return app('json')->success(systemConfig(['global_theme']));
    }

    public function setChangeColor()
    {
        $data = $this->request->params(['global_theme']);
        $make = app()->make(ConfigValueRepository::class);
        $make->setFormData($data, 0);
        return app('json')->success('修改成功');
    }


    public function svaeCopyright()
    {
        $data = $this->request->params(['copyright_context', 'copyright_image']);
        $copyright = app()->make(CacheRepository::class)->getResultByKey('copyright_status');
        if (!$copyright)
            return app('json')->fail('请先获取版权授权');

        app()->make(CacheRepository::class)->saveAll($data);
        return app('json')->success('修改成功');
    }

    public function payAuth()
    {
        $host = 'https://shop.crmeb.net/html/index.html';
        $version = get_crmeb_version_code();
        $url = rtrim($this->request->host(), '/');
        $data['url'] = $host . '?url=' . $url . '&product=mer&label=10&venrsion=' . $version;
        return app('json')->success($data);
    }

}

