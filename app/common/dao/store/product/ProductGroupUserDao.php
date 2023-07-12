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
use app\common\model\store\product\ProductGroupUser;

class ProductGroupUserDao extends  BaseDao
{
    public function getModel(): string
    {
        return ProductGroupUser::class;
    }

    public function successUser($id)
    {
        $query = ProductGroupUser::hasWhere('groupBuying',function($query){
            $query->where('status',10);
        });
        $query->where('ProductGroupUser.product_group_id',$id);
        return $query->setOption('field',[])->field('nickname,avatar')->select();
    }

    public function updateStatus(int $groupId)
    {
        return $this->getModel()::getDb()->where('group_buying_id',$groupId)->update(['status' => 10]);
    }

    public function groupOrderIds($productGroupId)
    {
        return ProductGroupUser::getDB()->where('group_buying_id', $productGroupId)->where('order_id', '>', 0)->select();
    }
}
