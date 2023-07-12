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


namespace app\common\repositories\wechat;


use app\common\dao\BaseDao;
use app\common\dao\wechat\WechatQrcodeDao;
use app\common\repositories\BaseRepository;
use crmeb\services\WechatService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Model;

/**
 * Class WechatQrcodeRepository
 * @package app\common\repositories\wechat
 * @author xaboy
 * @day 2020-04-28
 * @mixin WechatQrcodeDao
 */
class WechatQrcodeRepository extends BaseRepository
{
    /**
     * WechatQrcodeRepository constructor.
     * @param WechatQrcodeDao $dao
     */
    public function __construct(WechatQrcodeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $id
     * @param $type
     * @param null $qtcode_id
     * @return BaseDao|int|Model
     * @throws DbException
     * @author xaboy
     * @day 2020-04-28
     */
    public function createTemporaryQrcode($id, $type, $qtcode_id = null)
    {
        $qrcode = WechatService::create()->getApplication()->qrcode;
        $data = $qrcode->temporary($id, 30 * 24 * 3600)->toArray();
        $data['qrcode_url'] = $data['url'];
        $data['expire_seconds'] = $data['expire_seconds'] + time();
        $data['url'] = $qrcode->url($data['ticket']);
        $data['status'] = 1;
        $data['third_id'] = $id;
        $data['third_type'] = $type;
        if ($qtcode_id) {
            return $this->dao->update($qtcode_id, $data);
        } else {
            return $this->dao->create($data);
        }
    }

    /**
     * @param $id
     * @param $type
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-04-28
     */
    public function createForeverQrcode($id, $type)
    {
        $qrcode = WechatService::create()->getApplication()->qrcode;
        $data = $qrcode->forever($id)->toArray();
        $data['qrcode_url'] = $data['url'];
        $data['url'] = $qrcode->url($data['ticket']);
        $data['expire_seconds'] = 0;
        $data['status'] = 1;
        $data['third_id'] = $id;
        $data['third_type'] = $type;
        return self::create($data);
    }

    /**
     * @param $type
     * @param $id
     * @return BaseDao|array|int|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function getTemporaryQrcode($type, $id)
    {
        $qrInfo = $this->dao->getTemporaryQrcode($type, $id);
        if (!$qrInfo || (!$qrInfo['expire_seconds'] || $qrInfo['expire_seconds'] < time())) {
            $qrInfo = $this->createTemporaryQrcode($type, $id);
        }
        if (!isset($qrInfo['ticket']) || !$qrInfo['ticket']) throw new ValidateException('临时二维码获取错误');
        return $qrInfo;
    }

    /**
     * @param $type
     * @param $id
     * @return BaseDao|array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function getForeverQrcode($type, $id)
    {
        $qrInfo = $this->dao->getForeverQrcode($type, $id);
        if (!$qrInfo) {
            $qrInfo = $this->createForeverQrcode($id, $type);
        }
        if (!isset($qrInfo['ticket']) || !$qrInfo['ticket']) throw new ValidateException('二维码获取错误');
        return $qrInfo;
    }

}
