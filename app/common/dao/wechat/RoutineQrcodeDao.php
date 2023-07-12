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
use app\common\model\wechat\RoutineQrcode;

class RoutineQrcodeDao extends BaseDao
{

    protected function getModel(): string
    {
        return RoutineQrcode::class;
    }

    /**
     * TODO 添加二维码  存在直接获取
     * @param int $thirdId
     * @param string $thirdType
     * @param string $page
     * @param string $qrCodeLink
     * @return array|false|object|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function routineQrCodeForever($thirdId = 0, $thirdType = 'spread', $page = '', $qrCodeLink = '')
    {
        $count = RoutineQrcode::where('third_id', $thirdId)->where('third_type', $thirdType)->count();
        if ($count) return RoutineQrcode::where('third_id', $thirdId)->where('third_type', $thirdType)->field('routine_qrcode_id')->find();
        return $this->setRoutineQrcodeForever($thirdId, $thirdType, $page, $qrCodeLink);
    }

    /**
     * 添加二维码记录
     * @param int $thirdId
     * @param string $thirdType
     * @param string $page
     * @param string $qrCodeLink
     * @return object
     */
    public static function setRoutineQrcodeForever($thirdId = 0, $thirdType = 'spread', $page = '', $qrCodeLink = '')
    {
        $data['third_type'] = $thirdType;
        $data['third_id'] = $thirdId;
        $data['status'] = 1;
        $data['add_time'] = time();
        $data['page'] = $page;
        $data['qrcode_url'] = $qrCodeLink;
        return RoutineQrcode::create($data);
    }

    /**
     * 修改二维码地址
     * @param int $id
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function setRoutineQrcodeFind($id = 0, $data = array())
    {
        if (!$id) return false;
        $count = $this->getRoutineQrcodeFind($id);
        if (!$count) return false;
        return $this->update($id, $data);
    }

    /**
     * 获取二维码是否存在
     * @param int $id
     * @return int|string
     */
    public function getRoutineQrcodeFind($id = 0)
    {
        if (!$id) return 0;
        return RoutineQrcode::where('routine_qrcode_id', $id)->count();
    }

    /**
     * 获取小程序二维码信息
     * @param int $id
     * @param string $field
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoutineQrcodeFindType($id = 0, $field = 'third_type,third_id,page')
    {
        if (!$id) return false;
        $count = $this->getRoutineQrcodeFind($id);
        if (!$count) return false;
        return RoutineQrcode::where('routine_qrcode_id', $id)->where('status', 1)->field($field)->find();
    }
}
