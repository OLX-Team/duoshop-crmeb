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


namespace app\controller\admin\system\sms;


use crmeb\basic\BaseController;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\sms\SmsRecordRepository;
use app\validate\admin\SmsRegisterValidate;
use crmeb\services\YunxinSmsService;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;

/**
 * Class Sms
 * @package app\controller\admin\system\sms
 * @author xaboy
 * @day 2020-05-18
 */
class Sms extends BaseController
{
    /**
     * @var YunxinSmsService
     */
    protected $service;

    /**
     * Sms constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = YunxinSmsService::create();
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function captcha()
    {
        $phone = request()->param('phone');
        if (!$phone)
            return app('json')->fail('请输入手机号');
        if (!preg_match('/^1[3456789]{1}\d{9}$/', $phone))
            return app('json')->fail('请输入正确的手机号');
        $res = $this->service->captcha($phone);

        if (!isset($res['status']) && $res['status'] !== 200)
            return app('json')->fail($res['data']['message'] ?? $res['msg'] ?? '发送失败');

        return app('json')->success($res['data']['message'] ?? $res['msg'] ?? '发送成功');
    }

    /**
     * @param SmsRegisterValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function save(SmsRegisterValidate $validate)
    {
        $data = $this->request->params(['account', 'password', 'phone', 'code', 'url', 'sign']);
        $validate->check($data);
        $data['password'] = md5($data['password']);
        $res = $this->service->registerData($data);
        if ($res['status'] == 400) return app('json')->fail('短信平台：' . $res['msg']);
        $this->service->setConfig($data['account'], $data['password']);
        return app('json')->success('短信平台：' . $res['msg']);
    }

    /**
     * @param SmsRegisterValidate $validate
     * @param ConfigValueRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function save_basics(SmsRegisterValidate $validate, ConfigValueRepository $repository)
    {
        $data = $this->request->params([
            'account', 'password'
        ]);

        $validate->isLogin()->check($data);
        $this->service->setConfig($data['account'], md5($data['password']));

        //添加公共短信模板
        $templateList = $this->service->publictemp([]);
        if ($templateList['status'] != 400) {
            $repository->setFormData(['sms_account' => $data['account'], 'sms_token' => md5($data['password'])], 0);
            return app('json')->success('登录成功');
        } else {
            return app('json')->fail('账号或密码错误');
        }
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function is_login()
    {
        if ($sms_info = $this->service->account()) {
            return app('json')->success(['status' => true, 'info' => $sms_info]);
        } else {
            return app('json')->success(['status' => false]);
        }
    }

    /**
     * @param SmsRecordRepository $repository
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-18
     */
    public function record(SmsRecordRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['type',0]);
        return app('json')->success($repository->getList($where, $page, $limit));
    }

    /**
     * @param SmsRecordRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function data(SmsRecordRepository $repository)
    {
        $countInfo = $this->service->count();
        if ($countInfo['status'] == 400) {
            $info['number'] = 0;
            $info['total_number'] = 0;
        } else {
            $info['number'] = $countInfo['data']['number'];
            $info['total_number'] = $countInfo['data']['send_total'];
        }
        $info['record_number'] = $repository->count();
        $info['sms_account'] = $this->service->account();
        return app('json')->success($info);
    }

    /**
     * @param ConfigValueRepository $repository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-18
     */
    public function logout(ConfigValueRepository $repository)
    {
        Cache::delete('sms_account');
        Cache::delete('serve_account');
        $repository->clearBykey(['sms_account', 'sms_token','serve_account','serve_token'], 0);
        return app('json')->success('退出成功');
    }

    /**
     *  修改密码
     * @Author:Qinii
     * @Date: 2020/9/2
     * @return mixed
     */
    public function changePassword()
    {
        $data = $this->request->params(['password','phone','code']);
        if(empty($data['password']))
            return app('json')->fail('密码不能为空');
        $data['password'] = md5($data['password']);
        $res = $this->service->smsChange($data);
        if ($res['status'] == 400) return app('json')->fail('短信平台：' . $res['msg']);
        $this->service->setConfig($this->service->account(), $data['password']);
        return app('json')->success('修改成功');
    }

    /**
     *  修改签名
     * @Author:Qinii
     * @Date: 2020/9/2
     * @return mixed
     */
    public function changeSign()
    {
        $data = $this->request->params(['sign','phone','code']);
        if(empty($data['sign'])) return app('json')->fail('签名不能为空');
        $res = $this->service->smsChange($data);
        if ($res['status'] == 400) return app('json')->fail('短信平台：' . $res['msg']);
        return app('json')->success('修改已提交,审核通过后自动更改');
    }
}
