<?php

namespace app\controller\service;

use app\common\repositories\system\merchant\MerchantRepository;
use crmeb\basic\BaseController;

class Common extends BaseController
{
    public function info()
    {
        $merId = $this->request->merId();
        if ($merId) {
            $merchant = app()->make(MerchantRepository::class)->get($merId);
            $data = [
                'mer_id' => $merchant['mer_id'],
                'avatar' => $merchant['mer_avatar'],
                'name'  => $merchant['mer_name'],
            ];
        } else {
            $config = systemConfig(['site_logo', 'site_name','login_logo']);
            $data = [
                'mer_id' => 0,
                'avatar' => $config['login_logo'],
                'name' => $config['site_name'],
            ];
        }
        return app('json')->success($data);
    }

    public function user()
    {
        $admin = $this->request->adminInfo();
        return app('json')->success($admin->hidden(['pwd', 'merchant'])->toArray());
    }

    public function config()
    {
        return app('json')->success(systemConfig(['site_name', 'site_logo', 'beian_sn']));
    }
}
