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


namespace app\controller\merchant\system\serve;

use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantRepository;
use think\App;
use think\facade\Cache;

class Config extends BaseController
{
    /**
     * @var ServeOrderRepository
     */
    protected $repository;

    /**
     * Merchant constructor.
     * @param App $app
     * @param ServeOrderRepository $repository
     */
    public function __construct(App $app, ServeOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function info()
    {
        $sms_info = Cache::get('serve_account');
        $mer_id = $this->request->merId();
        $ret = app()->make(MerchantRepository::class)->get($mer_id);
        $data['mer_id'] = $ret['mer_id'];
        $data = [
            'info' =>$sms_info,
            'copy_product_status' => systemConfig('copy_product_status'),
            'copy_product_num' => $ret['copy_product_num'],
            'crmeb_serve_dump' => systemConfig('crmeb_serve_dump'),
            'export_dump_num' => $ret['export_dump_num'],
        ];
        return app('json')->success($data);
    }

    public function getConfig()
    {
        $merId = $this->request->merId();
        $config = [
            'mer_from_com',
            'mer_from_name',
            'mer_from_tel',
            'mer_from_addr',
            'mer_config_siid',
            'mer_config_temp_id'
        ];
        $data = merchantConfig($merId,$config);
        return app('json')->success($data);
    }

    public function setConfig()
    {
        $config = [
            'mer_from_com',
            'mer_from_name',
            'mer_from_tel',
            'mer_from_addr',
            'mer_config_siid',
            'mer_config_temp_id'
        ];
        $data = $this->request->params($config);

        app()->make(ConfigValueRepository::class)->setFormData($data,$this->request->merId());

        return app('json')->success('保存成功');
    }

}
