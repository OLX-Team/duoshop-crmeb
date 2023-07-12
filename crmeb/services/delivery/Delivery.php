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


namespace crmeb\services\delivery;

use crmeb\basic\BaseManager;
use think\facade\Config;

class Delivery extends BaseManager
{
    /**
     * 空间名
     * @var string
     */
    protected $namespace = '\\crmeb\\services\\delivery\\storage\\';

    /**
     * 设置默认
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return Config::get('delivery.default', 'dada');
    }
}
