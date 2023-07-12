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


namespace app\controller\admin\system\admin;


use crmeb\basic\BaseController;
use app\common\repositories\system\admin\AdminRepository;
use app\validate\admin\LoginValidate;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;

class Login extends BaseController
{
    protected $repository;

    public function __construct(App $app, AdminRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * TODO 判断是否需要滑块验证码
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/10/11
     */
    public function ajCaptchaStatus()
    {
        $data = $this->request->params(['account']);
        $key = 'sys_login_failuree_'.$data['account'];
        $numb = (Cache::get($key) ?? 0);
        return app('json')->success(['status' => $numb > 2 ]);
    }


    /**
     * @param LoginValidate $validate
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-10
     */
    public function login(LoginValidate $validate)
    {
        $data = $this->request->params(['account', 'password', 'code', 'key',['captchaType', ''], ['captchaVerification', ''],'token']);
        $validate->check($data);
        //图形验证码废弃
        #$this->repository->checkCode($data['key'], $data['code']);

        $key = 'sys_login_failuree_'.$data['account'];
        $numb = Cache::get($key) ?? 0;
        if($numb > 2){
            if (!$data['captchaType'] || !$data['captchaVerification']) {
                return app('json')->fail('请滑动滑块验证');
            }
            try {
                aj_captcha_check_two($data['captchaType'], $data['captchaVerification']);
            } catch (\Throwable $e) {
                return app('json')->fail($e->getMessage());
            }
        }
        $adminInfo = $this->repository->login($data['account'], $data['password']);
        $tokenInfo = $this->repository->createToken($adminInfo);
        $admin = $adminInfo->toArray();
        unset($admin['pwd']);
        $data = [
            'token' => $tokenInfo['token'],
            'exp' => $tokenInfo['out'],
            'admin' => $admin
        ];
        Cache::delete($key);
        return app('json')->success($data);
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-04-10
     */
    public function logout()
    {
        if ($this->request->isLogin())
            $this->repository->clearToken($this->request->token());
        return app('json')->success('退出登录');
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-04-09
     */
    public function getCaptcha()
    {
        $codeBuilder = new CaptchaBuilder(null, new PhraseBuilder(4));
        $key = $this->repository->createLoginKey($codeBuilder->getPhrase());
        $captcha = $codeBuilder->build()->inline();
        return app('json')->success(compact('key', 'captcha'));
    }
}
