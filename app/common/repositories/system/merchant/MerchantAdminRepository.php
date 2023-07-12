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


namespace app\common\repositories\system\merchant;


use app\common\dao\BaseDao;
use app\common\dao\system\merchant\MerchantAdminDao;
use app\common\model\system\merchant\Merchant;
use app\common\model\system\merchant\MerchantAdmin;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\auth\RoleRepository;
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
use think\Model;

/**
 * Class MerchantRepository
 * @package app\common\repositories\system\merchant
 * @mixin MerchantAdminDao
 * @author xaboy
 * @day 2020-04-16
 */
class MerchantAdminRepository extends BaseRepository
{

    const PASSWORD_TYPE_ADMIN = 1;
    const PASSWORD_TYPE_MERCHANT = 2;
    const PASSWORD_TYPE_SELF = 3;

    /**
     * MerchantAdminRepository constructor.
     * @param MerchantAdminDao $dao
     */
    public function __construct(MerchantAdminDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $merId
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-18
     */
    public function getList($merId, array $where, $page, $limit)
    {
        $query = $this->dao->search($merId, $where, 1);
        $count = $query->count();
        $list = $query->page($page, $limit)->hidden(['pwd', 'is_del'])->select();
        $topAccount = $this->dao->merIdByAccount($merId);
        foreach ($list as $k => $role) {
            if ($topAccount)
                $list[$k]['account'] = $topAccount . '@' . $role['account'];
            $list[$k]['rule_name'] = $role->roleNames();
        }
        return compact('list', 'count');
    }

    /**
     * @param int $merId
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-18
     */
    public function form(int $merId, ?int $id = null, array $formData = []): Form
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('merchantAdminCreate')->build() : Route::buildUrl('merchantAdminUpdate', ['id' => $id])->build());

        $rules = [
            Elm::select('roles', '身份', [])->options(function () use ($merId) {
                $data = app()->make(RoleRepository::class)->getAllOptions($merId);
                $options = [];

                foreach ($data as $value => $label) {
                    $options[] = compact('value', 'label');
                }
                return $options;
            })->multiple(true),
            Elm::input('real_name', '管理员姓名'),
            Elm::input('account', '账号')->required(),
            Elm::input('phone', ' 联系电话'),
        ];
        if (!$id) {
            $rules[] = Elm::password('pwd', '密码')->required();
            $rules[] = Elm::password('againPassword', '确认密码')->required();
        }
        $rules[] = Elm::switches('status', '是否开启', 1)->inactiveValue(0)->activeValue(1)->inactiveText('关')->activeText('开');
        $form->setRule($rules);
        return $form->setTitle(is_null($id) ? '添加管理员' : '编辑管理员')->formData($formData);
    }

    /**
     * @param int $merId
     * @param int $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-18
     */
    public function updateForm(int $merId, int $id)
    {
        return $this->form($merId, $id, $this->dao->get($id)->toArray());
    }

    /**
     * @param Merchant $merchant
     * @param $account
     * @param $pwd
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-04-17
     */
    public function createMerchantAccount(Merchant $merchant, $account, $pwd)
    {
        $pwd = $this->passwordEncode($pwd);
        $data = compact('pwd', 'account') + [
                'mer_id' => $merchant->mer_id,
                'real_name' => $merchant->real_name,
                'phone' => $merchant->mer_phone,
                'level' => 0
            ];
        return $this->create($data);
    }


    /**
     * @param $password
     * @return bool|string
     * @author xaboy
     * @day 2020-04-17
     */
    public function passwordEncode($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * @param string $account
     * @param string $password
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-17
     */
    public function login(string $account, string $password)
    {
        event('admin.merLogin.before',compact('account', 'password'));
        $accountInfo = explode('@', $account, 2);
        if (count($accountInfo) === 1) {
            $adminInfo = $this->dao->accountByTopAdmin($accountInfo[0]);
        } else {
            $merId = $this->dao->accountByMerchantId($accountInfo[0]);
            if (!$merId){
                $key = 'mer_login_failuree_'.$account;
                $numb = Cache::get($key) ?? 0;
                $numb++;
                Cache::set($key,$numb,15*60);
                throw new ValidateException('账号或密码错误');
            }
            $adminInfo = $this->dao->accountByAdmin($accountInfo[1], $merId);
        }

        if (!$adminInfo || !password_verify($password, $adminInfo->pwd)){
            $key = 'mer_login_failuree_'.$account;
            $numb = Cache::get($key) ?? 0;
            $numb++;
            Cache::set($key,$numb,15*60);
            throw new ValidateException('账号或密码错误');
        }

        if ($adminInfo['status'] != 1)
            throw new ValidateException('账号已关闭');


        /**
         * @var MerchantRepository $merchantRepository
         */
        $merchantRepository = app()->make(MerchantRepository::class);
        $merchant = $merchantRepository->get($adminInfo->mer_id);
        if (!$merchant)
            throw new ValidateException('商户不存在');
        if (!$merchant['status'])
            throw new ValidateException('商户已被锁定');

        $adminInfo->last_time = date('Y-m-d H:i:s');
        $adminInfo->last_ip = app('request')->ip();
        $adminInfo->login_count++;
        $adminInfo->save();
        event('admin.merLogin',compact('adminInfo'));
        return $adminInfo;
    }

    /**
     * @param string $token
     * @param int $exp
     * @author xaboy
     * @day 2020-04-10
     */
    public function cacheToken(string $token, int $exp)
    {
        Cache::set('mer_' . $token, time() + $exp, $exp);
    }

    /**
     * @param string $token
     * @author xaboy
     * @day 2020-04-17
     */
    public function checkToken(string $token)
    {
        $has = Cache::has('mer_' . $token);
        if (!$has)
            throw new AuthException('无效的token');
        $lastTime = Cache::get('mer_' . $token);
        if (($lastTime + (intval(Config::get('admin.token_valid_exp', 15))) * 60) < time())
            throw new AuthException('token 已过期');
    }

    /**
     * @param string $token
     * @author xaboy
     * @day 2020-04-17
     */
    public function updateToken(string $token)
    {
        Cache::set('mer_' . $token, time(), intval(Config::get('admin.token_valid_exp', 15)) * 60);
    }

    /**
     * @param string $token
     * @author xaboy
     * @day 2020-04-17
     */
    public function clearToken(string $token)
    {
        Cache::delete('mer_' . $token);
    }

    /**
     * @param MerchantAdmin $admin
     * @return array
     * @author xaboy
     * @day 2020-04-17
     */
    public function createToken(MerchantAdmin $admin)
    {
        $service = new JwtTokenService();
        $exp = intval(Config::get('admin.token_exp', 3));
        $token = $service->createToken($admin->merchant_admin_id, 'mer', strtotime("+ {$exp}hour"));
        $this->cacheToken($token['token'], $token['out']);
        return $token;
    }

    /**
     * @param string $key
     * @param string $code
     * @author xaboy
     * @day 2020-04-17
     */
    public function checkCode(string $key, string $code)
    {
        if (!env('DEVELOPMENT',false)){
            $_code = Cache::get('am_captcha' . $key);
            if (!$_code) {
                throw new ValidateException('验证码过期');
            }

            if (strtolower($_code) != strtolower($code)) {
                throw new ValidateException('验证码错误');
            }

            //删除code
            Cache::delete('am_captcha' . $key);
        }
    }


    /**
     * @param string $code
     * @return string
     * @author xaboy
     * @day 2020-04-17
     */
    public function createLoginKey(string $code)
    {
        $key = uniqid(microtime(true), true);
        Cache::set('am_captcha' . $key, $code, Config::get('admin.captcha_exp', 5) * 60);
        return $key;
    }

    /**
     * @param int $id
     * @param int $userType
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-20
     */
    public function passwordForm(int $id, $userType = 2)
    {
        $action = 'merchantAdminPassword';
        if ($userType == self::PASSWORD_TYPE_ADMIN)
            $action = 'systemMerchantAdminPassword';
        else if ($userType == self::PASSWORD_TYPE_SELF)
            $action = 'merchantAdminEditPassword';

        $form = Elm::createForm(Route::buildUrl($action, $userType == self::PASSWORD_TYPE_SELF ? [] : compact('id'))->build(), [
            $rules[] = Elm::password('pwd', '密码')->required(),
            $rules[] = Elm::password('againPassword', '确认密码')->required(),
        ]);
        return $form->setTitle('修改密码');
    }

    /**
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-20
     */
    public function editForm(array $formData)
    {
        $form = Elm::createForm(Route::buildUrl('merchantAdminEdit')->build());
        $form->setRule([
            Elm::input('real_name', '管理员姓名')->required(),
            Elm::input('phone', '联系电话')
        ]);

        return $form->setTitle('修改信息')->formData($formData);
    }

    /**
     * @param int $id
     * @param array $data
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-18
     */
    public function update(int $id, array $data)
    {
        if (isset($data['roles']))
            $data['roles'] = implode(',', $data['roles']);
        return $this->dao->update($id, $data);
    }

}
