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


use app\common\dao\BaseDao;
use app\common\dao\store\StoreAttrTemplateDao;
use app\common\repositories\BaseRepository;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Model;

/**
 * Class StoreAttrTemplateRepository
 * @package app\common\repositories\store
 * @author xaboy
 * @day 2020-05-06
 * @mixin StoreAttrTemplateDao
 */
class StoreAttrTemplateRepository extends BaseRepository
{
    /**
     * @var StoreAttrTemplateDao
     */
    protected $dao;

    /**
     * StoreAttrTemplateRepository constructor.
     * @param StoreAttrTemplateDao $dao
     */
    public function __construct(StoreAttrTemplateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $merId
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-06
     */
    public function getList($merId, array $where, $page, $limit)
    {
        $query = $this->dao->search($merId, $where);
        $count = $query->count($this->dao->getPk());
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * @param array $data
     * @return array
     * @author xaboy
     * @day 2020-05-06
     */
    protected function checkValue(array $data)
    {
        $arr = [];
        foreach ($data['template_value'] as $k => $value) {
            if (!is_array($value)) throw new ValidateException('规则有误');
            if (!($value['value'] ?? null)) throw new ValidateException('请输入规则名称');
            if (!($value['detail'] ?? null) || !count($value['detail'])) throw new ValidateException('请添加规则值');
            if(in_array($value['value'],$arr)) throw new ValidateException('规格重复');
            $arr[] = $value['value'];
            if (count($value['detail']) != count(array_unique($value['detail']))) throw new ValidateException('属性重复') ;
            $data['template_value'][$k] = [
                'value' => $value['value'],
                'detail' => $value['detail'],
            ];
        }
        return $data;
    }

    /**
     * @param array $data
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-05-06
     */
    public function create(array $data)
    {
        $data = $this->checkValue($data);
        return $this->dao->create($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-05-06
     */
    public function update(int $id, array $data)
    {
        $data = $this->checkValue($data);
        $data['template_value'] = json_encode($data['template_value']);
        return $this->dao->update($id, $data);
    }

    public function list(int $merId)
    {
        return $this->dao->getList($merId);
    }
}
