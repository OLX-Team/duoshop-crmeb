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


namespace crmeb\services\easywechat\certficates;


use crmeb\exceptions\WechatException;
use crmeb\services\easywechat\BaseClient;
use EasyWeChat\Core\AbstractAPI;
use think\exception\InvalidArgumentException;
use think\facade\Cache;

class Client extends BaseClient
{
    public function get()
    {
        $driver = Cache::store('file');
        $cacheKey = '_wx_v3' . ($this->isService ? $this->app['config']['service_payment']['serial_no'] : $this->app['config']['payment']['serial_no']);
        if ($driver->has($cacheKey)) {
            return $driver->get($cacheKey);
        }
        $certficates = $this->getCertficates();
        $driver->set($cacheKey, $certficates, 3600 * 24 * 30);
        return $certficates;
    }

    /**
     * get certficates.
     *
     * @return array
     */
    public function getCertficates()
    {
        $response = $this->request('/v3/certificates', 'GET', [], false);
        if (isset($response['code']))  throw new WechatException($response['message']);
        $certificates = $response['data'][0];
        $certificates['certificates'] = $this->decrypt($certificates['encrypt_certificate']);
        unset($certificates['encrypt_certificate']);
        return $certificates;
    }
}
