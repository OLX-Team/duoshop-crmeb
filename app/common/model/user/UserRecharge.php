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
 * Class UserRecharge
 * @package app\common\model\user
 * @author xaboy
 * @day 2020/6/2
 */
class UserRecharge extends BaseModel
{

    /**
     * @return string|null
     * @author xaboy
     * @day 2020/6/2
     */
    public static function tablePk(): ?string
    {
        return 'recharge_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/6/2
     */
    public static function tableName(): string
    {
        return 'user_recharge';
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function searchDateAttr($query,$value)
    {
        getModelTime($query, $value);
    }

    public function getPayParams($return_url = '')
    {
        $params = [
            'order_sn' => $this->order_id,
            'pay_price' => $this->price,
            'attach' => 'user_recharge',
            'body' => '用户充值'
        ];
        if ($return_url) {
            $params['return_url'] = $return_url;
        }
        return $params;
    }
}
