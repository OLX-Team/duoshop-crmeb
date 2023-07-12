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

use app\common\dao\store\parameter\ParameterDao;
use app\common\repositories\BaseRepository;

class ParameterRepository extends BaseRepository
{
    /**
     * @var ParameterDao
     */
    protected $dao;


    /**
     * ParameterRepository constructor.
     * @param ParameterDao $dao
     */
    public function __construct(ParameterDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 更新或者添加参数
     * @param $id
     * @param $merId
     * @param $data
     * @author Qinii
     * @day 2022/11/22
     */
    public function createOrUpdate($id, $merId, $data)
    {
        foreach ($data as $datum) {
            if (isset($datum['parameter_id']) && $datum['parameter_id']) {
                $update = [
                    'name' => $datum['name'],
                    'value' => $datum['value'],
                    'sort' => $datum['sort'],
                ];
                $this->dao->update($datum['parameter_id'], $update);
                $changeKey[] = $datum['parameter_id'];
            } else {
                $create[] = [
                    'template_id' => $id,
                    'name' => $datum['name'],
                    'value' => $datum['value'],
                    'sort' => $datum['sort'],
                    'mer_id' => $merId
                ];
            }
        }
        if (!empty($create)) $this->dao->insertAll($create);
    }

    /**
     * TODO 更新差异的删除操作
     * @param int $id
     * @param array $params
     * @author Qinii
     * @day 2022/11/22
     */
    public function diffDelete(int $id, array $params)
    {
        $paramsKey = array_unique(array_column($params,'parameter_id'));
        $this->dao->getSearch([])->where('template_id',$id)->whereNotIn('parameter_id',$paramsKey)->delete();
    }
}

