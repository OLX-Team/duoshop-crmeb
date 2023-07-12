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


namespace app\common\model\user;


use app\common\model\BaseModel;

/**
 * Class UserMerchant
 * @package app\common\model\user
 * @author xaboy
 * @day 2020/10/20
 */
class UserMerchant extends BaseModel
{

    /**
     * @return string|null
     * @author xaboy
     * @day 2020/10/20
     */
    public static function tablePk(): ?string
    {
        return 'user_merchant_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/10/20
     */
    public static function tableName(): string
    {
        return 'user_merchant';
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    /**
     * @param $value
     * @return array
     * @author xaboy
     * @day 2020-05-09
     */
    public function getLabelIdAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }

    /**
     * @param $value
     * @return string
     * @author xaboy
     * @day 2020-05-09
     */
    public function setLabelIdAttr($value)
    {
        return implode(',', $value);
    }

    public function getAuthLabelAttr()
    {
        return app()->make(UserLabel::class)->whereIn('label_id', $this->label_id)->where('mer_id', $this->mer_id)->where('type', 1)->column('label_id');
    }
}
