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


namespace app\common\middleware;


use app\Request;
use think\exception\ValidateException;
use think\facade\Cache;
use think\Response;

class MerchantCheckBaseInfoMiddleware extends BaseMiddleware
{

    protected $rules = [
        'merchantAttachmentCategoryCreate',
        'merchantUpdate',
        'merchantUploadImage',
        'merchant.Common/uploadCertificate',
        'merchantAttachmentCategoryUpdate',
        'merchantAttachmentCategoryDelete',
        'merchantAttachmentUpdate',
        'merchantAttachmentDelete'
    ];

    public function before(Request $request)
    {
        $name = $this->request->rule()->getName();
        if ($this->request->method() == 'GET' || in_array($name, $this->rules)) return;
        $cache = Cache::store('file');
        $merchant = $request->merchant();

        $key = 'mer_valid_' . $merchant->mer_id;
        if ($cache->has($key)) return;


        if (!$merchant->mer_avatar || !$merchant->mer_banner || !$merchant->mer_info ||  !$merchant->mer_address) {
            throw new ValidateException('您好，请前往左侧菜单【设置】-【商户基本信息】完善商户基本信息。');
        }
        Cache::store('file')->set('mer_valid_' . $merchant->mer_id, 1, 3600 * 8);
    }

    public function after(Response $response)
    {
        // TODO: Implement after() method.
    }
}
