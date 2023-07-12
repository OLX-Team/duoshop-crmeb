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


namespace app\common\model\system\merchant;


use app\common\model\BaseModel;
use app\common\model\system\auth\Menu;
use app\common\model\system\Relevance;
use app\common\repositories\system\RelevanceRepository;

class MerchantType extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'mer_type_id';
    }

    public static function tableName(): string
    {
        return 'merchant_type';
    }

    public function auth()
    {
        return $this->hasMany(Relevance::class, 'left_id', 'mer_type_id')->where('type', RelevanceRepository::TYPE_MERCHANT_AUTH);
    }
}
