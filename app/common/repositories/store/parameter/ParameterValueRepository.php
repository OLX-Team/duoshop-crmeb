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

namespace app\common\repositories\store\parameter;

use app\common\dao\store\parameter\ParameterValueDao;
use app\common\repositories\BaseRepository;

class ParameterValueRepository extends BaseRepository
{
    /**
     * @var ParameterValueDao
     */
    protected $dao;


    /**
     * ParameterRepository constructor.
     * @param ParameterValueDao $dao
     */
    public function __construct(ParameterValueDao  $dao)
    {
        $this->dao = $dao;
    }

    public function create($id, $data,$merId)
    {
        if (empty($data)) return ;
        foreach ($data as $datum) {
            if ($datum['name'] && $datum['value']) {
                $create[] = [
                    'product_id' => $id,
                    'name' => $datum['name'] ,
                    'value' => $datum['value'],
                    'sort' => $datum['sort'],
                    'parameter_id' => $datum['parameter_id'] ?? 0,
                    'mer_id' => $datum['mer_id'] ?? $merId,
                    'create_time' => date('Y-m-d H:i:s',time())
                ];
            }
        }
        if ($create) $this->dao->insertAll($create);
    }


}

