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


namespace app\common\repositories\store\service;


use app\common\dao\store\service\StoreServiceDao;
use app\common\model\store\service\StoreService;
use app\common\repositories\BaseRepository;
use crmeb\exceptions\AuthException;
use crmeb\services\JwtTokenService;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Route;

/**
 * Class StoreServiceRepository
 * @package app\common\repositories\store\service
 * @author xaboy
 * @day 2020/5/29
 * @mixin StoreServiceDao
 */
class StoreServiceRepository extends BaseRepository
{
    /**
     * StoreServiceRepository constructor.
     * @param StoreServiceDao $dao
     */
    public function __construct(StoreServiceDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/5/29
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->with(['user' => function ($query) {
            $query->field('nickname,avatar,uid,cancel_time');
        }])->order('sort DESC,create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020/5/29
     */
    public function form($merId, $isUpdate = false)
    {
        $pwd = Elm::password('pwd', '客服密码');
        $confirm_pwd = Elm::password('confirm_pwd', '确认密码');
        if (!$isUpdate) {
            $pwd->required();
            $confirm_pwd->required();
        }
        $adminRule = $filed = [];
        if($merId){
            $adminRule = [
                Elm::switches('customer', '订单管理', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开')->col(12),
                Elm::switches('is_goods', '商品管理', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开')->col(12),
                Elm::switches('is_verify', '开启核销', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
                Elm::switches('notify', '订单通知', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开')->control([
                    [
                        'value' => 1,
                        'rule' => [
                            Elm::input('phone', '通知电话')
                        ]
                    ]
                ])
            ];

        }
        $filed = [
            "value" => 1,
            "rule"  => [
                "customer","is_goods","is_verify","notify"
            ]
        ];
        $adminRule[] = Elm::number('sort', '排序', 0)->precision(0)->max(99999);
        $prefix = $merId ? config('admin.merchant_prefix') : config('admin.admin_prefix');
        return Elm::createForm(Route::buildUrl('merchantServiceCreate')->build(), array_merge([
            Elm::frameImage('uid', '用户', '/' . $prefix . '/setting/userList?field=uid&type=1')->prop('srcKey', 'src')->width('675px')->height('500px')->modal(['modal' => false]),
            Elm::frameImage('avatar', '客服头像', '/' . $prefix . '/setting/uploadPicture?field=avatar&type=1')->width('896px')->height('480px')->props(['footer' => false])->modal(['modal' => false]),
            Elm::input('nickname', '客服昵称')->required(),
            Elm::input('account', '客服账号')->required(),
            $pwd, $confirm_pwd,
            Elm::switches('is_open', '账号状态', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开')->col(12)->control([$filed]),
            Elm::switches('status', '客服状态', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开')->col(12),
        ], $adminRule))->setTitle('添加客服');
    }

    /**
     * @param $id
     * @return Form
     * @throws FormBuilderException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/5/29
     */
    public function updateForm($id)
    {
        $service = $this->dao->getWith($id, ['user' => function ($query) {
            $query->field('avatar,uid');
        }])->toArray();
        if($service['user'] ?? null){
            $service['uid'] = ['id' => $service['uid'], 'src' => $service['user']['avatar'] ?: $service['avatar']];
        }else{
            unset($service['uid']);
        }
        unset($service['user'], $service['pwd']);
        return $this->form($service['mer_id'], true)->formData($service)->setTitle('编辑表单')->setAction(Route::buildUrl('merchantServiceUpdate', compact('id'))->build());
    }

    /**
     * @param $merId
     * @param $uid
     * @return array|mixed|\think\Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/5/29
     */
    public function getChatService($merId, $uid = 0)
    {
        $service = null;
        if ($uid) {
            $logRepository = app()->make(StoreServiceLogRepository::class);
            $lastServiceId = $logRepository->getLastServiceId($merId, $uid);
        }

        if (isset($lastServiceId) && $lastServiceId)
            $service = $this->getValidServiceInfo($lastServiceId);
        if ($service) return $service;
        $service = $this->dao->getRandService($merId);
        if ($service) return $service;
    }

    public function getServices($uid, array $where = [],$is_sys = 1)
    {
        $order = $is_sys ? 'ASC' : 'DESC';
        $where['uid'] = $uid;
        $list = $this->search($where)->with(['merchant' => function ($query) {
            $query->field('mer_id,mer_avatar,mer_name');
        }])->order('mer_id '.$order)->select()->hidden(['pwd'])->toArray();
        $config = systemConfig(['site_logo', 'site_name']);
        foreach ($list as &$item){
            if ($item['mer_id'] == 0) {
                $item['merchant'] = [
                    'mer_avatar' => $config['site_logo'],
                    'mer_name' => $config['site_name'],
                    'mer_id' => 0,
                ];
            }
        }
        unset($item);
        return $list;
    }

    public function createToken(StoreService $admin)
    {
        $service = new JwtTokenService();
        $exp = intval(Config::get('admin.token_exp', 3));
        $token = $service->createToken($admin->service_id, 'service', strtotime("+ {$exp}hour"));
        $this->cacheToken($token['token'], $token['out']);
        return $token;
    }

    /**
     * @param string $token
     * @param int $exp
     * @author xaboy
     * @day 2020-04-10
     */
    public function cacheToken(string $token, int $exp)
    {
        Cache::set('service_' . $token, time() + $exp, $exp);
    }

    public function checkToken(string $token)
    {
        $has = Cache::has('service_' . $token);
        if (!$has)
            throw new AuthException('无效的token');
        $lastTime = Cache::get('service_' . $token);
        if (($lastTime + (intval(Config::get('admin.token_valid_exp', 15))) * 60) < time())
            throw new AuthException('token 已过期');
    }

    public function updateToken(string $token)
    {
        Cache::set('service_' . $token, time(), intval(Config::get('admin.token_valid_exp', 15)) * 60);
    }

    public function clearToken(string $token)
    {
        Cache::delete('service_' . $token);
    }


    /**
     * 检测验证码
     * @param string $key key
     * @param string $code 验证码
     * @author 张先生
     * @date 2020-03-26
     */
    public function checkCode(string $key, string $code)
    {
        $_code = Cache::get('ser_captcha' . $key);
        if (!$_code) {
            throw new ValidateException('验证码过期');
        }

        if (strtolower($_code) != strtolower($code)) {
            throw new ValidateException('验证码错误');
        }

        //删除code
        Cache::delete('ser_captcha' . $key);
    }


    /**
     * @param string $code
     * @return string
     * @author xaboy
     * @day 2020-04-09
     */
    public function createLoginKey(string $code)
    {
        $key = uniqid(microtime(true), true);
        Cache::set('ser_captcha' . $key, $code, Config::get('admin.captcha_exp', 5) * 60);
        return $key;
    }
}
