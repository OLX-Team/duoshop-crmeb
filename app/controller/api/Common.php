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


namespace app\controller\api;


use app\common\repositories\delivery\DeliveryOrderRepository;
use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductGroupBuyingRepository;
use app\common\repositories\store\product\ProductGroupRepository;
use app\common\repositories\store\product\ProductPresellRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\shipping\ExpressRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\CacheRepository;
use app\common\repositories\system\diy\DiyRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\groupData\GroupRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\notice\SystemNoticeConfigRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\user\UserSignRepository;
use app\common\repositories\user\UserVisitRepository;
use app\common\repositories\wechat\TemplateMessageRepository;
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\basic\BaseController;
use crmeb\services\AlipayService;
use crmeb\services\CopyCommand;
use crmeb\services\MiniProgramService;
use crmeb\services\UploadService;
use crmeb\services\WechatService;
use Exception;
use Joypack\Tencent\Map\Bundle\Location;
use Joypack\Tencent\Map\Bundle\LocationOption;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Log;
use think\Response;

/**
 * Class Common
 * @package app\controller\api
 * @author xaboy
 * @day 2020/5/28
 */
class Common extends BaseController
{
    /**
     * @return mixed
     * @author xaboy
     * @day 2020/5/28
     */
    public function hotKeyword()
    {
        $type = $this->request->param('type');
        switch ($type) {
            case 0:
                $keyword = systemGroupData('hot_keyword');
                break;
            case 1:
                $keyword = systemGroupData('community_hot_keyword');
                break;
        }
        return app('json')->success($keyword);
    }

    public function express(ExpressRepository $repository)
    {
        return app('json')->success($repository->options());
    }

    public function menus()
    {
        return app('json')->success([
            'global_theme' => $this->getThemeVar(systemConfig('global_theme')),
            'banner' => systemGroupData('my_banner'),
            'menu' => systemGroupData('my_menus')
        ]);
    }

    public function refundMessage()
    {
        return app('json')->success(explode("\n", systemConfig('refund_message')));
    }

    private function getThemeVar($type)
    {
        return app()->make(DiyRepository::class)->getThemeVar($type);
    }

    public function config()
    {
        $config = systemConfig(['open_update_info', 'store_street_theme', 'is_open_service', 'is_phone_login', 'global_theme', 'integral_status', 'mer_location', 'alipay_open', 'hide_mer_status', 'mer_intention_open', 'share_info', 'share_title', 'share_pic', 'store_user_min_recharge', 'recharge_switch', 'balance_func_status', 'yue_pay_status', 'site_logo', 'routine_logo', 'site_name', 'login_logo', 'procudt_increase_status', 'sys_extension_type', 'member_status', 'copy_command_status', 'community_status','community_reply_status','community_app_switch', 'withdraw_type', 'recommend_switch', 'member_interests_status', 'beian_sn', 'community_reply_auth','hot_ranking_switch','svip_switch_status','margin_ico','margin_ico_switch']);
        $make = app()->make(TemplateMessageRepository::class);

        $cache = app()->make(CacheRepository::class)->search(['copyright_status', 'copyright_context', 'copyright_image', 'sys_intention_agree']);

        if (!isset($cache['sys_intention_agree'])) {
            $cache['sys_intention_agree'] = systemConfig('sys_intention_agree');
        }

        $title = app()->make(UserSignRepository::class)->signConfig();
        if (!$title) {
            $config['member_status'] = 0;
        }
        if (!is_array($config['withdraw_type'])) {
            $config['withdraw_type'] = ['1', '2', '3'];
        }

        $config['tempid'] = app()->make(SystemNoticeConfigRepository::class)->getSubscribe();
        $config['global_theme'] = $this->getThemeVar($config['global_theme']);
        $config['navigation'] = app()->make(DiyRepository::class)->getNavigation();
        $config = array_merge($config, $cache);
        return app('json')->success($config);
    }

    /**
     * @param GroupDataRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/3
     */
    public function userRechargeQuota(GroupDataRepository $repository)
    {
        $recharge_quota = $repository->groupDataId('user_recharge_quota', 0);
        $recharge_attention = explode("\n", systemConfig('recharge_attention'));
        return app('json')->success(compact('recharge_quota', 'recharge_attention'));
    }

    /**
     * @param $field
     * @return mixed
     * @author xaboy
     * @day 2020/5/28
     */
    public function uploadImage($field)
    {
        $name = $this->request->param('name');
        $file = $this->request->file($field);
        if (!$file)
            return app('json')->fail('请上传图片');
        if ($name) {
            $f = $this->request->getOriginFile($field);
            if ($f) {
                $f['name'] = $name;
            }
            $this->request->setOriginFile($field, $f);
            $file = $this->request->file($field);
        }
        $file = is_array($file) ? $file[0] : $file;
        validate(["$field|图片" => [
            'fileSize' => config('upload.filesize'),
            'fileExt' => 'jpg,jpeg,png,bmp,gif',
            'fileMime' => 'image/jpeg,image/png,image/gif,application/octet-stream'
        ]])->check([$field => $file]);
        $upload = UploadService::create();
        $info = $upload->to('def')->move($field);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        $res['dir'] = tidy_url($res['dir']);
        return app('json')->success('上传成功', ['path' => $res['dir']]);
    }

    /**
     * @return Response
     * @author xaboy
     * @day 2020/6/3
     */
    public function wechatNotify()
    {
        try {
            if($this->request->header('content-type') === 'application/json'){
                return response(WechatService::create()->handleNotifyV3()->getContent());
            }
            return response(WechatService::create()->handleNotify()->getContent());
        } catch (Exception $e) {
            Log::info('支付回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
    }

    /**
     * 电商收付通合并支付回调
     */
    public function wechatCombinePayNotify($type)
    {
        if (!in_array($type, ['order', 'presell'], true))
            throw new ValidateException('参数错误');
        try {
            return WechatService::create()->handleCombinePayNotify($type);
        } catch (Exception $e) {
            Log::info('电商收付通支付回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
    }

    /**
     * 电商收付通合并支付回调
     */
    public function routineCombinePayNotify($type)
    {
        if (!in_array($type, ['order', 'presell'], true))
            throw new ValidateException('参数错误');
        try {
            return WechatService::create()->handleCombinePayNotify($type);
        } catch (Exception $e) {
            Log::info('小程序电商收付通支付回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
    }

    public function routineNotify()
    {
        try {
            if($this->request->header('content-type') === 'application/json'){
                return response(MiniProgramService::create()->handleNotifyV3()->getContent());
            }
            return response(MiniProgramService::create()->handleNotify()->getContent());
        } catch (Exception $e) {
            Log::info('支付回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine(),$this->request->header()], true));
        }
    }

    public function alipayNotify($type)
    {
        if (!in_array($type, ['order', 'user_recharge', 'presell', 'user_order'], true))
            throw new ValidateException('参数错误');
        $post = $_POST;
        $get = $_GET;
        $_POST = $this->request->post();
        $_GET = $this->request->get();
        try {
            AlipayService::create()->notify($type);
        } catch (Exception $e) {
            Log::info('支付宝回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        } finally {
            $_POST = $post;
            $_GET = $get;
        }
    }

    public function getVersion()
    {
        return app('json')->success(['version' => get_crmeb_version(), 'host' => request()->host(), 'system' => PHP_OS, 'php' => @phpversion()]);
    }

    /**
     * 获取图片base64
     * @return mixed
     */
    public function get_image_base64()
    {
        list($imageUrl, $codeUrl) = $this->request->params([
            ['image', ''],
            ['code', ''],
        ], true);
        checkSuffix([$imageUrl, $codeUrl]);
        try {
            $codeTmp = $code = $codeUrl ? image_to_base64($codeUrl) : '';
            if (!$codeTmp) {
                $putCodeUrl = put_image($codeUrl);
                $code = $putCodeUrl ? image_to_base64('./runtime/temp' . $putCodeUrl) : '';
                $code && unlink('./runtime/temp' . $putCodeUrl);
            }

            $imageTmp = $image = $imageUrl ? image_to_base64($imageUrl) : '';
            if (!$imageTmp) {
                $putImageUrl = put_image($imageUrl);
                $image = $putImageUrl ? image_to_base64('./runtime/temp' . $putImageUrl) : '';
                $image && unlink('./runtime/temp' . $putImageUrl);
            }
            return app('json')->success(compact('code', 'image'));
        } catch (Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    public function home()
    {
        $banner = systemGroupData('home_banner', 1, 10);
        $menu = systemGroupData('home_menu');
        $hot = systemGroupData('home_hot', 1, 4);
        $activity = systemGroupData('sys_activity', 1, 1)[0] ?? null;
        $activity_lst = systemGroupData('sys_activity', 1, 3);
        $ad = systemConfig(['home_ad_pic', 'home_ad_url']);
        $category = app()->make(StoreCategoryRepository::class)->getTwoLevel();
        return app('json')->success(compact('banner', 'menu', 'hot', 'ad', 'category', 'activity', 'activity_lst'));
    }

    public function activityLst($id)
    {
        $merId = (int)$id;
        [$page, $limit] = $this->getPage();
        return app('json')->success($merId ? merchantGroupData($merId, 'mer_activity', $page, $limit) : systemGroupData('sys_activity', $page, $limit));
    }

    public function activityInfo($id)
    {
        $activity = app()->make(GroupDataRepository::class)->getData((int)$id,1);
        if (!$activity) {
            return app('json')->fail('活动不存在');
        }
        $activity['merchant'] = $activity['group_mer_id'] ? app()->make(MerchantRepository::class)->search(['mer_id' => $activity['group_mer_id']])->field('mer_name,mer_avatar')->find() : null;
        return app('json')->success($activity);
    }

    public function visit()
    {
        if (!$this->request->isLogin()) return app('json')->success();
        [$page, $type] = $this->request->params(['page', 'type'], true);
        $uid = $this->request->uid();
        if (!$page || !$uid) return app('json')->fail();
        $userVisitRepository = app()->make(UserVisitRepository::class);
        $type == 'routine' ? $userVisitRepository->visitSmallProgram($uid, $page) : $userVisitRepository->visitPage($uid, $page);
        return app('json')->success();
    }

    public function hotBanner($type)
    {
        if (!in_array($type, ['new', 'hot', 'best', 'good']))
            $data = [];
        else
            $data = systemGroupData($type . '_home_banner');
        return app('json')->success($data);
    }

    public function pay_key($key)
    {
        $cache = Cache::store('file');
        if (!$cache->has('pay_key' . $key)) {
            return app('json')->fail('支付链接不存在');
        }
        return app('json')->success($cache->get('pay_key' . $key));
    }

    public function lbs_geocoder()
    {
        $data = explode(',', $this->request->param('location', ''));
        $locationOption = new LocationOption(systemConfig('tx_map_key'));
        $locationOption->setLocation($data[0] ?? '', $data[1] ?? '');
        $location = new Location($locationOption);
        $res = $location->request();
        if ($res->error) {
            return app('json')->fail($res->error);
        }
        if ($res->status) {
            return app('json')->fail($res->message);
        }
        if (!$res->result) {
            return app('json')->fail('获取失败');
        }
        return app('json')->success($res->result);
    }


    public function getCommand()
    {
        $key = $this->request->param('key');
        if (!preg_match('/^(\/@[1-9]{1}).*\*\//', $key)) {
            return app('json')->fail('无效口令');
        }
        $userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
        $command = app()->make(CopyCommand::class)->getMassage($key);
        if (empty($command)) return app('json')->fail('无效口令');
        $info = [];
        if ($command['uid']) {
            $user = app()->make(UserRepository::class)->get($command['uid']);
            $info = [
                'uid' => $user['uid'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
            ];
        }
        switch ($command['type']) {
            case 0:
                $data = app()->make(ProductRepository::class)->detail($command['id'], $userInfo);
                $ret['product_id'] = $command['id'];
                break;
            case 1:
                $data = app()->make(ProductRepository::class)->detail($command['id'], $userInfo);
                $ret['product_id'] = $command['id'];
                break;
            case 2:
                $data = app()->make(ProductPresellRepository::class)->apiDetail((int)$command['id'], $userInfo);
                $ret['activity_id'] = $command['id'];
                break;
            case 4:
                $data = app()->make(ProductGroupRepository::class)->apiDetail($command['id'], $userInfo);
                $ret['activity_id'] = $command['id'];
                break;
            case 30:
                $data = app()->make(ProductAssistSetRepository::class)->detail($command['id'], $userInfo);
                $ret['activity_id'] = $command['id'];
                break;
            case 40:
                $data = app()->make(ProductGroupBuyingRepository::class)->detail($command['id'], $userInfo);
                $ret['activity_id'] = $command['id'];
                break;
        }
        if ($userInfo && $command['uid']) app()->make(UserRepository::class)->bindSpread($userInfo, $command['uid']);
        $ret['product_type'] = $command['type'];
        $ret['user'] = $info;
        $ret['com'] = $command['com'];
        $ret['data'] = $data;
        return app('json')->success($ret);
    }

    public function script()
    {
        return \response(systemConfig('static_script'));
    }

    public function appVersion()
    {
        return app('json')->success(systemConfig([
            'appVersion',
            'iosAddress',
            'androidAddress',
            'openUpgrade'
        ]));
    }

    public function deliveryNotify()
    {
        try {
            $params = $this->request->param();
            app()->make(DeliveryOrderRepository::class)->notify($params);
        } catch (Exception $e) {
            Log::info('同城配送订单回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
    }

    public function diy()
    {
        $merid = $this->request->param('id', 0);
        return app('json')->success(app()->make(DiyRepository::class)->getDiyInfo(0, $merid));
    }

    public function getNavigation()
    {
        return app('json')->success(app()->make(DiyRepository::class)->getNavigation());
    }

    public function micro()
    {
        $id = $this->request->param('id', 0);
        return app('json')->success(app()->make(DiyRepository::class)->getDiyInfo($id, 0, 0));
    }

    /**
     * 是否关注
     * @return mixed
     */
    public function subscribe()
    {
        if ($this->request->isLogin()) {
            $user = $this->request->userInfo();
            if ($user && $user['wechat_user_id']) {
                $wechatUserService = app()->make(WechatUserRepository::class);
                $subscribe = $wechatUserService->getWhereCount([
                        'wechat_user_id' => $user['wechat_user_id'],
                        'subscribe' => 1
                    ]) > 0;
                return app('json')->success(['subscribe' => $subscribe]);
            }
        }
        return app('json')->success(['subscribe' => false, 'qrcode' => systemConfig('wechat_qrcode')]);
    }

    public function open_screen()
    {
        $key = ['open_screen_advertising'];
        $group = app()->make(GroupRepository::class)->getSearch([])->where('group_key','in',$key)->find();
        $data= app()->make(GroupDataRepository::class)->getGroupDataLst($this->request->merId(),$group->group_id,1, 10);
        $data['config'] = systemConfig(['open_screen_switch','open_screen_time','open_screen_space']);
        return app('json')->success($data);
    }
}
