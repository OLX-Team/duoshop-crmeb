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
use app\common\model\system\Relevance;
use app\common\repositories\system\RelevanceRepository;

class PriceRule extends BaseModel
{

    public static function tablePk(): string
    {
        return 'rule_id';
    }


    public static function tableName(): string
    {
        return 'price_rule';
    }

    public function cate()
    {
        return $this->hasMany(Relevance::class, 'left_id', 'rule_id')->where([
            'type' => RelevanceRepository::PRICE_RULE_CATEGORY
        ])->with(['category']);
    }
}
