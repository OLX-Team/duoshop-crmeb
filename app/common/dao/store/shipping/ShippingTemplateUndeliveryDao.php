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

namespace app\common\dao\store\shipping;

use app\common\dao\BaseDao;
use app\common\model\store\shipping\ShippingTemplateUndelivery as model;

class ShippingTemplateUndeliveryDao  extends BaseDao
{
    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    protected function getModel(): string
    {
        return model::class;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @param $field
     * @param $value
     * @param null $except
     * @return bool
     */
    public function merFieldExists($field, $value, $except = null)
    {
        return ($this->getModel())::getDB()->when($except, function ($query, $except) use ($field) {
                $query->where($field, '<>', $except);
            })->where($field, $value)->count() > 0;
    }


    /**
     * 批量删除
     * @Author:Qinii
     * @Date: 2020/5/8
     * @param array $id
     * @param array $temp_id
     */
    public function batchRemove(array $id,array $temp_id)
    {
        if($id)
            ($this->getModel())::getDB()->where($this->getPk(),'in',$id)->delete();
        if($temp_id)
            ($this->getModel())::getDB()->where('temp_id','in',$temp_id)->delete();
    }
}
