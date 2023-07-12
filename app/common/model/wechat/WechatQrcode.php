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


namespace app\common\model\wechat;


use app\common\model\BaseModel;

/**
 * Class WechatQrcode
 * @package app\common\model\wechat
 * @author xaboy
 * @day 2020-04-28
 */
class WechatQrcode extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'wechat_qrcode_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'wechat_qrcode';
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-04-28
     */
    public function incTicket()
    {
        return self::getDB()->where('wechat_qrcode_id', $this->wechat_qrcode_id)->inc('scan')->update();
    }
}
