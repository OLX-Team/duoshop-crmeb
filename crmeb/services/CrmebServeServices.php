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

namespace crmeb\services;


use app\common\repositories\system\config\ConfigValueRepository;
use crmeb\services\express\Express;
use crmeb\services\product\Product;
use crmeb\services\serve\Serve;
use crmeb\services\sms\Sms;
use think\facade\Cache;

/**
 * 平台服务入口
 * Class ServeServices
 * @package crmeb\services
 */
class CrmebServeServices
{

    /**
     * SmsTemplateApplyServices constructor.
     * @param FormBuilder $builder
     */
    public function __construct()
    {

    }

    /**
     * 获取配置
     * @return array
     */
    public function getConfig(array $config = [])
    {
        return array_merge([
            'account' => systemConfig('serve_account'),
            'secret' => systemConfig('serve_token')
        ], $config);
    }

    /**
     * 短信
     * @return Sms
     */
    public function sms(array $config = [])
    {
        $account = systemConfig('sms_account');
        if ($account){
            $password = md5($account.systemConfig('sms_token'));
            $res = $this->user()->login($account, $password);

            if($res){
                Cache::set('serve_account', $account);
                $arr = [
                    'serve_account' => $account,
                    'serve_token' => $password,
                ];
                app()->make(ConfigValueRepository::class)->setFormData($arr, 0);

                Cache::delete('sms_account');

                request()->clearCache();
            }
        }
        return app()->make(Sms::class, [$this->getConfig($config)]);
    }

    /**
     * 复制商品
     * @return Product
     */
    public function copy(array $config = [])
    {
        return app()->make(Product::class, [$this->getConfig($config)]);
    }

    /**
     * 电子面单
     * @return Express
     */
    public function express(array $config = [])
    {
        return app()->make(Express::class, [$this->getConfig($config)]);
    }

    /**
     * 用户
     * @return Serve
     */
    public function user(array $config = [])
    {
        return app()->make(Serve::class, [$this->getConfig($config)]);
    }

    /**
     * 获取短信模板
     * @param int $page
     * @param int $limit
     * @param int $type
     * @return array
     */
    public function getSmsTempsList(int $page, int $limit, int $type)
    {
        $list = $this->sms()->temps($page, $limit, $type);
        foreach ($list['data'] as &$item) {
            $item['templateid'] = $item['temp_id'];
            switch ((int)$item['temp_type']) {
                case 1:
                    $item['type'] = '验证码';
                    break;
                case 2:
                    $item['type'] = '通知';
                    break;
                case 30:
                    $item['type'] = '营销短信';
                    break;
            }
        }
        return $list;
    }

    /**
     * TODO 退出
     * @author Qinii
     * @day 9/11/21
     */
    public function logout()
    {
        Cache::delete('sms_account');
        Cache::delete('serve_account');
        app()->make(ConfigValueRepository::class)->clearBykey(['serve_account','serve_token'], 0);
    }
}
