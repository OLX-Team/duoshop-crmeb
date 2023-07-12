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


namespace app\controller;


use crmeb\basic\BaseController;
use crmeb\services\WechatService;
use EasyWeChat\Core\Exceptions\InvalidArgumentException;
use EasyWeChat\Server\BadRequestException;
use think\Response;

/**
 * Class WechatNotice
 * @package app\controller
 * @author xaboy
 * @day 2020-04-26
 */
class WechatNotice extends BaseController
{
    /**
     * @return Response
     * @throws InvalidArgumentException
     * @throws BadRequestException
     * @author xaboy
     * @day 2020-04-26
     */
    public function serve()
    {
        ob_clean();
        return WechatService::create()->serve($this->request);
    }
}
