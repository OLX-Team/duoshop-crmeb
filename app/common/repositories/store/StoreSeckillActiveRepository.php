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

namespace app\common\repositories\store;

use app\common\dao\store\StoreSeckillActiveDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\SpuRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class StoreSeckillActiveRepository extends BaseRepository
{

    /**
     * @var StoreSeckillActiveDao
     */
    protected $dao;

    /**
     * StoreSeckillTimeRepository constructor.
     * @param StoreSeckillActiveDao $dao
     */
    public function __construct(StoreSeckillActiveDao $dao)
    {
        $this->dao = $dao;
    }

    public function updateSort(int $id,?int $merId,array $data)
    {
        $where[$this->dao->getPk()] = $id;
        if($merId) $where['mer_id'] = $merId;
        $ret = $this->dao->getWhere($where);
        if(!$ret) throw new  ValidateException('数据不存在');
        app()->make(ProductRepository::class)->update($ret['product_id'],$data);
        $make = app()->make(SpuRepository::class);
        return $make->updateSort($ret['product_id'],$ret[$this->dao->getPk()],1,$data);
    }
}
