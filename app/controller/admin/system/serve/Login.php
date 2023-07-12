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

namespace app\controller\admin\system\serve;

use app\common\repositories\system\config\ConfigValueRepository;
use app\validate\admin\CrmebServeValidata;
use app\Request;
use crmeb\basic\BaseController;
use crmeb\services\CrmebServeServices;

use think\App;
use think\facade\Cache;

/**
 * 服务登录
 * Class Login
 * @package app\controller\admin\v1\serve
 */
class Login extends BaseController
{
    protected $services;

    public function __construct(App $app, CrmebServeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 发送验证码
     * @param string $phone
     * @return mixed
     */
    public function captcha(string $phone,CrmebServeValidata $validata)
    {
        $validata->scene('phone')->check(['phone' => $phone]);
        $this->services->user()->code($phone);
        return app('json')->success('短信发送成功');
    }

    /**
     * 验证验证码
     * @param string $phone
     * @param $code
     * @return mixed
     */
    public function checkCode(CrmebServeValidata $validata)
    {
        $phone = $this->request->param('phone');
        $verify_code = $this->request->param('verify_code');
        $validata->scene('phone')->check(['phone' => $phone]);
        $this->services->user()->checkCode($phone, $verify_code);
        return  app('json')->success('success');
    }

    /**
     * 注册服务
     * @param Request $request
     * @param SmsAdminServices $services
     * @return mixed
     */
    public function register(Request $request,CrmebServeValidata $validata)
    {
        $data = $this->request->params(['phone','account','password','verify_code']);

        $data['account'] = $data['phone'];
        $validata->check($data);
        $data['password'] = md5($data['password']);
        $res = $this->services->user()->register($data);
        if ($res) {
            $arr = [
                'serve_account' => $data['account'],
                'serve_token' => md5($data['account'] . md5($data['password'])),
            ];
            app()->make(ConfigValueRepository::class)->setFormData($arr, 0);
            return  app('json')->success('一号通注册成功');
        } else {
            return  app('json')->fail('一号通注册失败');
        }
    }

    /**
     * 平台登录
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function login(CrmebServeValidata $validata)
    {
        $account = $this->request->param('account');
        $password = $this->request->param('password');
        $validata->scene('login')->check(['account' => $account, 'password' => $password]);
        $password = md5($account . md5($password));
        $res = $this->services->user()->login($account, $password);

        if ($res) {
            Cache::set('serve_account', $account);
            $arr = [
                'serve_account' => $account,
                'serve_token' => $password,
            ];
            app()->make(ConfigValueRepository::class)->setFormData($arr, 0);
            return app('json')->success('一号通登录成功', $res);
        } else {
            return app('json')->fail('一号通登录失败');
        }
    }

}
