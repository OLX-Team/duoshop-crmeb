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


namespace app\common\repositories\system\admin;


//附件
use app\common\dao\system\admin\AdminDao;
use app\common\model\system\admin\Admin;
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
 * Class BaseRepository
 * @package common\repositories
 * @mixin AdminDao
 */
class AdminRepository extends BaseRepository
{
    public function __construct(AdminDao $dao)
    {
        /**
         * @var AdminDao
         */
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
     * @day 2020-04-09
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->hidden(['pwd', 'is_del', 'update_time'])->select();

        foreach ($list as $k => $role) {
            $list[$k]['rule_name'] = $role->roleNames();
        }
        return compact('list', 'count');
    }

    public function passwordEncode($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * 更新
     * @param int $id id
     * @param array $data 数组
     * @return int
     * @throws DbException
     * @author 张先生
     * @date 2020-03-26
     */
    public function update(int $id, array $data)
    {
        if (isset($data['roles']))
            $data['roles'] = implode(',', $data['roles']);
        return $this->dao->update($id, $data);
    }

    /**
     * @param int $id
     * @param $isSelf
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-20
     */
    public function passwordForm(int $id, $isSelf = false)
    {
        $form = Elm::createForm(Route::buildUrl($isSelf ? 'systemAdminEditPassword' : 'systemAdminPassword', $isSelf ? [] : compact('id'))->build(), [
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
        $form = Elm::createForm(Route::buildUrl('systemAdminEdit')->build());
        $form->setRule([
            [
                'type' => 'span',
                'title' => '管理员ID',
                'native' => false,
                'children' => [(string)$formData['admin_id']]
            ],
            Elm::input('real_name', '管理员姓名')->required(),
            Elm::input('phone', '联系电话')
        ]);

        return $form->setTitle('修改信息')->formData($formData);
    }

    /**
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-08
     */
    public function form(?int $id = null, array $formData = []): Form
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemAdminCreate')->build() : Route::buildUrl('systemAdminUpdate', ['id' => $id])->build());

        $rules = [
            Elm::select('roles', '身份', [])->options(function () {
                $data = app()->make(RoleRepository::class)->getAllOptions(0);
                $options = [];

                foreach ($data as $value => $label) {
                    $options[] = compact('value', 'label');
                }
                return $options;
            })->multiple(true)->required(),
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
     * @param int $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-09
     */
    public function updateForm(int $id)
    {
        return $this->form($id, $this->dao->get($id)->toArray());
    }


    /**
     * @param string $account
     * @param string $password
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-10
     */
    public function login(string $account, string $password)
    {
        event('admin.login.before',compact('account','password'));
        $adminInfo = $this->dao->accountByAdmin($account);
        if (!$adminInfo || !password_verify($password, $adminInfo->pwd)){
            $key = 'sys_login_failuree_'.$account;
            $numb = Cache::get($key) ?? 0;
            $numb++;
            Cache::set($key,$numb,15*60);
            throw new ValidateException('账号或密码错误');
        }

        if ($adminInfo['status'] != 1)
            throw new ValidateException('账号已关闭');

        $adminInfo->last_time = date('Y-m-d H:i:s');
        $adminInfo->last_ip = app('request')->ip();
        $adminInfo->login_count++;
        $adminInfo->save();
        event('admin.login',compact('adminInfo'));
        return $adminInfo;
    }

    /**
     * TODO 登录尝试次数限制
     * @param $account
     * @param int $number
     * @param int $n
     * @author Qinii
     * @day 7/6/21
     */
    public function loginFailure($account,$number = 5,$n = 3)
    {
        $key = 'sys_login_failuree_'.$account;
        $numb = Cache::get($key) ?? 0;
        $numb++;
        if($numb >= $number){
            $fail_key = 'sys_login_freeze_'.$account;
            Cache::tag('sys_login_freeze')->set($fail_key,1,15*60);
            throw new ValidateException('账号或密码错误次数太多，请稍后在尝试');
        }else{
            Cache::tag('sys_login_freeze')->set($key,$numb,5*60);
            $msg = '账号或密码错误';
            $_n = $number - $numb;
            if($_n <= $n){
                $msg .= ',还可尝试'.$_n.'次';
            }
            throw new ValidateException($msg);
        }
    }

    /**
     * @param string $token
     * @param int $exp
     * @author xaboy
     * @day 2020-04-10
     */
    public function cacheToken(string $token, int $exp)
    {
        Cache::set('admin_' . $token, time() + $exp, $exp);
    }

    public function checkToken(string $token)
    {
        $has = Cache::has('admin_' . $token);
        if (!$has)
            throw new AuthException('无效的token');
        $lastTime = Cache::get('admin_' . $token);
        if (($lastTime + (intval(Config::get('admin.token_valid_exp', 15))) * 60) < time())
            throw new AuthException('token 已过期');
    }

    public function updateToken(string $token)
    {
        Cache::set('admin_' . $token, time(), intval(Config::get('admin.token_valid_exp', 15)) * 60);
    }

    public function clearToken(string $token)
    {
        Cache::delete('admin_' . $token);
    }

    /**
     * @param Admin $admin
     * @return array
     * @author xaboy
     * @day 2020-04-09
     */
    public function createToken(Admin $admin)
    {
        $service = new JwtTokenService();
        $exp = intval(Config::get('admin.token_exp', 3));
        $token = $service->createToken($admin->admin_id, 'admin', strtotime("+ {$exp}hour"));
        $this->cacheToken($token['token'], $token['out']);
        return $token;
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
        if (!env('DEVELOPMENT',false)) {
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
     * @day 2020-04-09
     */
    public function createLoginKey(string $code)
    {
        $key = uniqid(microtime(true), true);
        Cache::set('am_captcha' . $key, $code, Config::get('admin.captcha_exp', 5) * 60);
        return $key;
    }
}
