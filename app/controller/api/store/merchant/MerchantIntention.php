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

namespace app\controller\api\store\merchant;

use app\common\repositories\system\merchant\MerchantAdminRepository;
use app\common\repositories\system\merchant\MerchantCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\merchant\MerchantTypeRepository;
use app\validate\api\MerchantIntentionValidate;
use crmeb\services\SmsService;
use crmeb\services\SwooleTaskService;
use crmeb\services\YunxinSmsService;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantIntentionRepository as repository;
use think\exception\ValidateException;

class MerchantIntention extends BaseController
{
    protected $repository;
    protected $userInfo;

    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
    }

    public function create()
    {
        $data = $this->checkParams();
        if (!systemConfig('mer_intention_open')) {
            return app('json')->fail('未开启商户入驻');
        }
        if ($this->userInfo) $data['uid'] = $this->userInfo->uid;
        $make = app()->make(MerchantRepository::class);
        if ($make->fieldExists('mer_name', $data['mer_name']))
            throw new ValidateException('商户名称已存在，不可申请');
        if ($make->fieldExists('mer_phone', $data['phone']))
            throw new ValidateException('手机号已存在，不可申请');
        $adminRepository = app()->make(MerchantAdminRepository::class);
        if ($adminRepository->fieldExists('account', $data['phone']))
            throw new ValidateException('手机号已是管理员，不可申请');
        $intention = $this->repository->create($data);
        SwooleTaskService::admin('notice', [
            'type' => 'new_intention',
            'data' => [
                'title' => '商户入驻申请',
                'message' => '您有一个新的商户入驻申请',
                'id' => $intention->mer_intention_id
            ]
        ]);
        return app('json')->success('提交成功');
    }

    public function update($id)
    {
        if (!$this->repository->getWhere(['mer_intention_id' => (int)$id, 'uid' => $this->userInfo->uid, 'is_del' => 0]))
            return app('json')->fail('数据不存在');
        $data = $this->checkParams();
        if (!systemConfig('mer_intention_open')) {
            return app('json')->fail('未开启商户入驻');
        }
        $data['create_time'] = date('Y-m-d H:i:s', time());
        $this->repository->updateIntention((int)$id, $data);
        SwooleTaskService::admin('notice', [
            'type' => 'new_intention',
            'data' => [
                'title' => '商户入驻申请',
                'message' => '您有一个新的商户入驻申请',
                'id' => $id
            ]
        ]);
        return app('json')->success('修改成功');
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $data = $this->repository->getList(['uid' => $this->userInfo->uid], $page, $limit);
        return app('json')->success($data);
    }

    function detail($id)
    {
        $data = $this->repository->detail((int)$id, $this->userInfo->uid);
        if (!$data) {
            return app('json')->fail('数据不存在');
        }
        if ($data->status == 1) {
            $data['login_url'] = rtrim(systemConfig('site_url'), '/') . '/' . config('admin.merchant_prefix');
        }
        return app('json')->success($data);
    }

    protected function checkParams()
    {
        $data = $this->request->params(['phone', 'mer_name', 'name', 'code', 'images', 'merchant_category_id', 'mer_type_id']);
        app()->make(MerchantIntentionValidate::class)->check($data);
        $check = app()->make(SmsService::class)->checkSmsCode($data['phone'], $data['code'], 'intention');
        $data['mer_type_id'] = (int)$data['mer_type_id'];
        if (!$check) throw new ValidateException('验证码不正确');
        if (!app()->make(MerchantCategoryRepository::class)->get($data['merchant_category_id'])) throw new ValidateException('商户分类不存在');
        if ($data['mer_type_id'] && !app()->make(MerchantTypeRepository::class)->exists($data['mer_type_id']))
            throw new ValidateException('店铺类型不存在');
        unset($data['code']);
        return $data;
    }

    /**
     * 商户分类
     * @Author:Qinii
     * @Date: 2020/9/15
     * @return mixed
     */
    public function cateLst()
    {
        $lst = app()->make(MerchantCategoryRepository::class)->getSelect();
        return app('json')->success($lst);
    }

    public function typeLst()
    {
        $lst = app()->make(MerchantTypeRepository::class)->getSelect();
        return app('json')->success($lst);
    }
}

