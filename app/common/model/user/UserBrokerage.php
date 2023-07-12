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

class UserBrokerage extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'user_brokerage_id';
    }

    public static function tableName(): string
    {
        return 'user_brokerage';
    }

    public function getBrokerageRuleAttr($val)
    {
        return json_decode($val, true);
    }

    public function setBrokerageRuleAttr($val)
    {
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    }

    public function getExtensionOneRateAttr()
    {
        return bcdiv((int)$this->extension_one, 100, 4);
    }

    public function getExtensionTwoRateAttr()
    {
        return bcdiv((int)$this->extension_two, 100, 4);
    }

}
