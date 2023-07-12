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


namespace app\common\dao\system\merchant;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\merchant\MerchantCategory;
use think\db\BaseQuery;
use think\facade\Db;

/**
 * Class MerchantCategoryDao
 * @package app\common\dao\system\merchant
 * @author xaboy
 * @day 2020-05-06
 */
class MerchantCategoryDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return MerchantCategory::class;
    }


    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-05-06
     */
    public function search(array $where = [])
    {
        return MerchantCategory::getDB();
    }

    /**
     * @return array
     * @author xaboy
     * @day 2020-05-06
     */
    public function allOptions()
    {
        $data = MerchantCategory::getDB()->column('category_name', 'merchant_category_id');
        $options = [];
        foreach ($data as $value => $label) {
            $options[] = compact('value', 'label');
        }
        return $options;
    }

    public function dateMerchantPriceGroup($date, $limit = 4)
    {
        return MerchantCategory::getDB()->alias('A')->leftJoin('Merchant B', 'A.merchant_category_id = B.category_id')
            ->leftJoin('StoreOrder C', 'C.mer_id = B.mer_id')->field(Db::raw('sum(C.pay_price) as pay_price,A.category_name'))
            ->when($date, function ($query, $date) {
                getModelTime($query, $date, 'C.pay_time');
            })->group('A.merchant_category_id')->where('pay_price', '>', 0)->order('pay_price DESC')->limit($limit)->select();
    }

    public function names(array $ids)
    {
        return MerchantCategory::getDB()->whereIn('merchant_category_id', $ids)->column('category_name');
    }
}
