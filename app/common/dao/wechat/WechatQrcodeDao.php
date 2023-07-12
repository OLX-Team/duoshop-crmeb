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


namespace app\common\dao\wechat;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\wechat\WechatQrcode;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * Class WechatQrcodeDao
 * @package app\common\dao\wechat
 * @author xaboy
 * @day 2020-04-28
 */
class WechatQrcodeDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return WechatQrcode::class;
    }

    /**
     * @param $ticket
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function ticketByQrcode($ticket)
    {
        return WechatQrcode::getDB()->where('ticket', $ticket)->find();
    }

    /**
     * @param $type
     * @param $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function getForeverQrcode($type, $id)
    {
        return WechatQrcode::getDB()->where('third_id', $id)->where('third_type', $type)->where('expire_seconds', 0)->find();
    }

    /**
     * @param $type
     * @param $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function getTemporaryQrcode($type, $id)
    {
        return WechatQrcode::getDB()->where('third_id', $id)->where('third_type', $type)->where('expire_seconds', '>', 0)->find();
    }
}
