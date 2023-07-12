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


namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\ProductPresellSku;
use think\facade\Db;

class ProductPresellSkuDao extends BaseDao
{
    protected function getModel(): string
    {
        return ProductPresellSku::class;
    }

    public function clear($id)
    {
        $this->getModel()::getDB()->where('product_presell_id', $id)->delete();
    }

    public function descStock(int $product_presell_id, string $unique, int $desc)
    {
        return $this->getModel()::getDB()->where('product_presell_id', $product_presell_id)->where('unique', $unique)->update([
            'stock' => Db::raw('stock-' . $desc),
            'seles' => Db::raw('seles+' . $desc),
        ]);
    }

    public function incStock(int $product_presell_id, string $unique, int $desc)
    {
        return $this->getModel()::getDB()->where('product_presell_id', $product_presell_id)->where('unique', $unique)->update([
            'stock' => Db::raw('stock+' . $desc),
            'seles' => Db::raw('seles-' . $desc),
        ]);
    }

    /**
     * TODO 增加 参与或支付成功 人数
     * @param int $product_presell_id
     * @param string $unique
     * @param string $field
     * @return mixed
     * @author Qinii
     * @day 2020-11-27
     */
    public function incCount(int $product_presell_id,string $unique,string $field,$inc = 1)
    {
        return $this->getModel()::getDB()->where('product_presell_id', $product_presell_id)->where('unique', $unique)
            ->update([
                $field => Db::raw($field.'+' . $inc)
            ]);
    }

    /**
     * TODO 减少 参与或支付成功 人数
     * @param int $product_presell_id
     * @param string $unique
     * @param string $field
     * @return mixed
     * @author Qinii
     * @day 2020-11-27
     */
    public function desCount(int $product_presell_id,string $unique,$inc = 1)
    {
        $res = $this->getModel()::getDB()->where('product_presell_id', $product_presell_id)->where('unique',$unique)->find();
        if($res->presell->presell_type == 1 ){
            $res->one_pay = ($res->one_pay > 0) ? $res->one_pay - $inc : 0;
        }else{
            $res->two_pay = ($res->two_pay > 0) ? $res->two_pay - $inc : 0;
        }
        return $res->save();
    }
}

