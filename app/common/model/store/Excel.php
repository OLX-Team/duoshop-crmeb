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

namespace app\common\model\store;

use app\common\model\BaseModel;
use app\common\model\system\merchant\MerchantAdmin;

class Excel extends BaseModel
{
    public $typeData = [
            'order'             => '订单列表',
            'delivery'          => '待发货订单',
            'searchLog'         => '搜索记录',
            'financial'         => '流水记录',
            'refundOrder'       => '退款单',
            'integralLog'       => '积分日志',
            'importDelivery'    => '发货导入',
            'exportFinancial'   => '日/月账单',
            'financialLog'      => '转账记录',
            'bill'              => '资金记录',
            'profitsharing'     => '分账管理',
            'extract'           => '提现管理',
        ];

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-07-30
     */
    public static function tablePk(): string
    {
        return 'excel_id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-07-30
     */
    public static function tableName(): string
    {
        return 'excel';
    }

    public function merAdmin()
    {
        return $this->hasOne(MerchantAdmin::class,'merchant_admin_id','admin_id');
    }

    public function getPathAttr($value)
    {
        return $value ? systemConfig('site_url').$value : '';
    }

    public function getTypeAttr($value)
    {
        if ($value) {
            return $this->typeData[$value];
        }
        return '';
    }

}
