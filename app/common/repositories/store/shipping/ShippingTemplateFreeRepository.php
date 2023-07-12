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

namespace app\common\repositories\store\shipping;

use app\common\repositories\BaseRepository;
use app\common\dao\store\shipping\ShippingTemplateFreeDao as dao;

class ShippingTemplateFreeRepository extends BaseRepository
{

    /**
     * ShippingTemplateFreeRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @param $merId
     * @param $id
     * @return bool
     */
    public function merExists($merId , $id)
    {
        $result = $this->dao->get($id);
        $make = app()->make(ShippingTemplateRepository::class);
        if ($result)
            return $make->merExists($merId,$result['temp_id']);
        return false;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @param $id
     * @param $data
     */
    public function update($id,$data)
    {
        foreach ($data as $item){
            if(isset($item['shipping_template_free_id']) && $item['shipping_template_free_id']){
                $item['city_id'] = implode('/',$item['city_id']);
                $this->dao->update($item['shipping_template_free_id'],$item);
            }else{
                $item['temp_id'] = $id;
                $this->dao->create($item);
            }
        }
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @param array $data
     * @return mixed
     */
    public function insertAll(array $data)
    {
        return $this->dao->insertAll($data);
    }
}
