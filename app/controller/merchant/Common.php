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


namespace app\controller\merchant;


use app\common\repositories\user\UserRepository;
use crmeb\basic\BaseController;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\user\UserRelationRepository;
use app\common\repositories\user\UserVisitRepository;
use crmeb\services\ImageWaterMarkService;
use crmeb\services\UploadService;
use Joypack\Tencent\Map\Bundle\Address;
use Joypack\Tencent\Map\Bundle\AddressOption;
use think\App;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class Common
 * @package app\controller\merchant
 * @author xaboy
 * @day 2020/6/25
 */
class Common extends BaseController
{
    /**
     * @var int|null
     */
    protected $merId;

    /**
     * Common constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->merId = $this->request->merId() ?: null;
    }

    /**
     * @param null $merId
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function main($merId = null)
    {
        $today = $this->mainGroup('today', $merId ?? $this->merId);
        $yesterday = $this->mainGroup('yesterday', $merId ?? $this->merId);
        $lastWeek = $this->mainGroup(date('Y-m-d', strtotime('- 7day')), $merId ?? $this->merId);
        $lastWeekRate = [];
        foreach ($lastWeek as $k => $item) {
            if ($item == $today[$k])
                $lastWeekRate[$k] = 0;
            else if ($item == 0)
                $lastWeekRate[$k] = $today[$k];
            else if ($today[$k] == 0)
                $lastWeekRate[$k] = -$item;
            else
                $lastWeekRate[$k] = (float)bcdiv(bcsub($today[$k], $item, 4), $item, 4);
        }
        $day = date('Y-m-d');
        return $merId ? compact('today', 'yesterday', 'lastWeekRate', 'day') : app('json')->success(compact('today', 'yesterday', 'lastWeekRate', 'day'));
    }

    /**
     * @param $date
     * @param $merId
     * @return array
     * @author xaboy
     * @day 2020/6/25
     */
    public function mainGroup($date, $merId)
    {
        $userVisitRepository = app()->make(UserVisitRepository::class);
        $repository = app()->make(StoreOrderRepository::class);
        $relationRepository = app()->make(UserRelationRepository::class);
        $orderNum = (float)$repository->dayOrderNum($date, $merId);
        $payPrice = (float)$repository->dayOrderPrice($date, $merId);
        $payUser = (float)$repository->dayOrderUserNum($date, $merId);
        $visitNum = (float)$userVisitRepository->dateVisitUserNum($date, $merId);
        $likeStore = (float)$relationRepository->dayLikeStore($date, $merId);
        return compact('orderNum', 'payPrice', 'payUser', 'visitNum', 'likeStore');
    }

    /**
     * @param StoreOrderRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function order(StoreOrderRepository $repository)
    {
        $date = $this->request->param('date') ?: 'lately7';
        $res = Cache::remember(self::class . '@order' . $this->merId . $date, function () use ($repository, $date) {
            if ($date == 'year') {
                $m = date('m',time());
                $time[] = $m;
                do{
                    $time[] = '0'. ($m - 1);
                    $m--;
                }while($m > 1);
                $time = array_reverse($time);
            } else {
                $time = getDatesBetweenTwoDays(getStartModelTime($date), date('Y-m-d'));
            }
            $list = $repository->orderGroupNum($date, $this->merId)->toArray();
            $list = array_combine(array_column($list, 'day'), $list);
            $data = [];
            foreach ($time as $item) {
                $data[] = [
                    'day' => $item,
                    'total' => $list[$item]['total'] ?? 0,
                    'user' => $list[$item]['user'] ?? 0,
                    'pay_price' => $list[$item]['pay_price'] ?? 0
                ];
            }
            return $data;
        }, 2000 + random_int(600, 1200));
        return app('json')->success($res);
    }

    /**
     * @param UserRelationRepository $repository
     * @param StoreOrderRepository $orderRepository
     * @param UserVisitRepository $userVisitRepository
     * @return \think\response\Json
     * @author xaboy
     * @day 2020/9/24
     */
    public function user(StoreOrderRepository $orderRepository, UserVisitRepository $userVisitRepository)
    {
        $date = $this->request->param('date', 'today') ?: 'today';
        $res = Cache::store('file')->remember(self::class . '@user' . $this->merId . $date, function () use ($orderRepository, $userVisitRepository, $date) {
            $visitUser = $userVisitRepository->dateVisitUserNum($date, $this->merId);
            $orderUser = $orderRepository->orderUserNum($date, null, $this->merId);
            $orderPrice = $orderRepository->orderPrice($date, null, $this->merId);
            $payOrderUser = $orderRepository->orderUserNum($date, 1, $this->merId);
            $payOrderPrice = $orderRepository->orderPrice($date, 1, $this->merId);
            $userRate = $payOrderUser ? bcdiv($payOrderPrice, $payOrderUser, 2) : 0;
            $orderRate = $visitUser ? bcdiv($orderUser, $visitUser, 2) : 0;
            $payOrderRate = $orderUser ? bcdiv($payOrderUser, $orderUser, 2) : 0;

            return compact('visitUser', 'orderUser', 'orderPrice', 'payOrderUser', 'payOrderPrice', 'payOrderRate', 'userRate', 'orderRate');
        }, 2000 + random_int(600, 1200));

        return app('json')->success($res);
    }

    /**
     * @param StoreOrderRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function userRate(StoreOrderRepository $repository, UserRepository $userRepository)
    {
        $date = $this->request->param('date') ?: 'today';

        $res = Cache::store('file')->remember(self::class . '@userRate' . $this->merId . $date, function () use ($userRepository, $repository, $date) {
            $uids = $repository->orderUserGroup($date, 1, $this->merId)->toArray();
            $userPayCount = $userRepository->idsByPayCount(array_column($uids, 'uid'));
            $user = count($uids);
            $oldUser = 0;
            $totalPrice = 0;
            $oldTotalPrice = 0;
            foreach ($uids as $uid) {
                $totalPrice = bcadd($uid['pay_price'], $totalPrice, 2);
                if (($userPayCount[$uid['uid']] ?? 0) > $uid['total']) {
                    $oldUser++;
                    $oldTotalPrice = bcadd($uid['pay_price'], $oldTotalPrice, 2);
                }
            }
            $newTotalPrice = bcsub($totalPrice, $oldTotalPrice, 2);
            $newUser = $user - $oldUser;
            return compact('newTotalPrice', 'newUser', 'oldTotalPrice', 'oldUser', 'totalPrice', 'user');
        }, 2000 + random_int(600, 1200));

        return app('json')->success($res);
    }

    /**
     * @param StoreOrderProductRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function product(StoreOrderProductRepository $repository)
    {
        $date = $this->request->param('date', 'today') ?: 'today';

        $res = Cache::store('file')->remember(self::class . '@product' . $this->merId . $date, function () use ($repository, $date) {
            return $repository->orderProductGroup($date, $this->merId)->toArray();
        }, 2000 + random_int(600, 1200));
        return app('json')->success($res);
    }

    public function productVisit(UserVisitRepository $repository)
    {
        $date = $this->request->param('date', 'today') ?: 'today';

        $res = Cache::store('file')->remember(self::class . '@productVisit' . $this->merId . $date, function () use ($repository, $date) {
            return $repository->dateVisitProductNum($date, $this->merId);
        }, 2000 + random_int(600, 1200));
        return app('json')->success($res);
    }

    /**
     * @param ProductRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function productCart(ProductRepository $repository)
    {
        $date = $this->request->param('date', 'today') ?: 'today';

        $res = Cache::store('file')->remember(self::class . '@productCart' . $this->merId . $date, function () use ($repository, $date) {
            return $repository->cartProductGroup($date, $this->merId);
        }, 2000 + random_int(600, 1200));
        return app('json')->success($res);
    }

    public function uploadCertificate()
    {
        $file = $this->request->file('file');
        if (!$file)
            return app('json')->fail('请上传证书');
        validate(["file|图片" => [
            'fileSize' => config('upload.filesize'),
            'fileExt' => 'jpg,jpeg,png,bmp',
            'fileMime' => 'image/jpeg,image/png',
        ]])->check(['file' => $file]);
        $upload = UploadService::create(1);
        $data = $upload->to('attach')->move('file');
        if ($data === false) {
            return app('json')->fail($upload->getError());
        }
        app()->make(ImageWaterMarkService::class)->run(public_path() . $upload->getFileInfo()->filePath);
        return app('json')->success(['src' => tidy_url($upload->getFileInfo()->filePath)]);
    }

    public function uploadVideo()
    {
        $file = $this->request->file('file');
        if (!$file)
            return app('json')->fail('请上传视频');
        validate(["file|视频" => [
            'fileSize' => config('upload.filesize'),
            'fileExt' => 'mp4,mov',
            'fileMime' => 'video/mp4,video/quicktime',
        ]])->check(['file' => $file]);
        $upload = UploadService::create();
        $data = $upload->to('media')->validate([])->move('file');
        if ($data === false) {
            return app('json')->fail($upload->getError());
        }
        return app('json')->success(['src' => tidy_url($upload->getFileInfo()->filePath)]);
    }

    public function config()
    {
        $data = systemConfig(['tx_map_key','delivery_status','delivery_type','rmargin_emind_switch']);
        $data['mer_id'] = $this->request->merId();
        return app('json')->success($data);
    }
}
