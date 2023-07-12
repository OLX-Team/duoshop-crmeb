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


namespace app\common\repositories\store;


use app\common\repositories\system\config\ConfigValueRepository;

class MerchantTakeRepository
{
    public function get($merId)
    {
        return merchantConfig($merId, [
            'mer_take_status', 'mer_take_name', 'mer_take_phone', 'mer_take_address', 'mer_take_location', 'mer_take_day', 'mer_take_time'
        ]);
    }

    public function set($merId, array $data)
    {
        $configValueRepository = app()->make(ConfigValueRepository::class);
        $configValueRepository->setFormData($data, $merId);
    }

    public function has($merId)
    {
        return merchantConfig($merId, 'mer_take_status') == '1';
    }
}
