<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +---------------------------------------------------------------------
namespace app\common\repositories\store;

use app\common\dao\store\StoreActivityDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\RelevanceRepository;
use think\exception\ValidateException;
use think\facade\Db;

class StoreActivityRepository extends BaseRepository
{
    //活动边框
    const ACTIVITY_TYPE_ATMOSPHERE = 1;
    //氛围图
    const ACTIVITY_TYPE_BORDER = 2;

    //指定范围类型
    //0全部商品
    const TYPE_ALL = 0;
    //指定商品
    const TYPE_MUST_PRODUCT = 1;
    //指定分类
    const TYPE_MUST_CATEGORY = 2;
    //指定商户
    const TYPE_MUST_STORE = 3;

    /**
     * @var StoreActivityDao
     */
    protected $dao;

    /**
     * StoreActivityDao constructor.
     * @param StoreActivityDao $dao
     */
    public function __construct(StoreActivityDao $dao)
    {
        $this->dao = $dao;
    }

    public function createActivity(array $data,$extend = null, $func = null)
    {
        $paramsData = $this->getParams($data,$extend);
        Db::transaction(function() use($data, $extend, $func,$paramsData){
            $createData = $this->dao->create($data);
            if (isset($paramsData['ids']) && !empty($paramsData['ids']))
                app()->make(RelevanceRepository::class)->createMany($createData->activity_id, $paramsData['ids'], $paramsData['type']);
            if ($func && function_exists($func)) $this->$func($createData,$extend);
        });
    }

    public function getParams($data,$extend)
    {
        if (!$extend) return [];
        $res = [];
        $type = '';
        switch ($data['scope_type']) {
            case self::TYPE_ALL;
                break;
            case self::TYPE_MUST_PRODUCT:
                if (!isset($extend['spu_ids']) || empty($extend['spu_ids'])) throw new ValidateException('请选择指定商品');
                $res = app()->make(SpuRepository::class)->getSearch(['spu_ids' => $extend['spu_ids'],'status' => 1])->column('spu_id');
                $type = RelevanceRepository::SCOPE_TYPE_PRODUCT;
                break;
            case self::TYPE_MUST_CATEGORY:
                if (!isset($extend['cate_ids']) || empty($extend['cate_ids'])) throw new ValidateException('请选择指定商品分类');
                $res = app()->make(StoreCategoryRepository::class)->getSearch(['ids' => $extend['cate_ids'],'status' => 1])->column('store_category_id');
                $type = RelevanceRepository::SCOPE_TYPE_CATEGORY;
                break;
            case self::TYPE_MUST_STORE:
                if (!isset($extend['mer_ids']) || empty($extend['mer_ids'])) throw new ValidateException('请选择指定商户');
                $res = app()->make(MerchantRepository::class)->getSearch(['mer_ids' => $extend['mer_ids']])->column('mer_id');
                $type = RelevanceRepository::SCOPE_TYPE_STORE;
                break;
        }
        $ids = array_unique($res);
        return compact('ids','type');
    }

    public function updateActivity(int $id,array $data,$extend = null, $func = null)
    {
        $paramsData = $this->getParams($data, $extend);
        Db::transaction(function() use($id,$data, $extend, $func,$paramsData){
            $createData = $this->dao->update($id,$data);
            app()->make(RelevanceRepository::class)->clear($id, [RelevanceRepository::SCOPE_TYPE_PRODUCT,RelevanceRepository::SCOPE_TYPE_STORE,RelevanceRepository::SCOPE_TYPE_CATEGORY],'left_id');
            if (isset($paramsData['ids']) && !empty($paramsData['ids']))
                app()->make(RelevanceRepository::class)->createMany($id, $paramsData['ids'], $paramsData['type']);
            if ($func && function_exists($func)) $this->$func($createData,$extend);
        });
    }

    /**
     * TODO 详情
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2022/9/17
     */
    public function getAdminList($where, $page, $limit)
    {
        $query = $this->dao->search($where)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    /**
     * TODO 详情
     * @param $id
     * @return array
     * @author Qinii
     * @day 2022/9/16
     */
    public function detail($id)
    {
        $data = $this->dao->getSearch([$this->dao->getPk() => $id])->with(['socpeData',])->find()->toArray();
        try{
            $arr = array_column($data['socpeData'],'right_id');
            if ($data['scope_type'] == self::TYPE_MUST_CATEGORY) {
                $data['cate_ids'] = $arr;
            } else if ($data['scope_type'] == self::TYPE_MUST_STORE) {
                $data['mer_ids'] = $arr;
            } else {
                $data['spu_ids'] = $arr;
            }
        }catch (\Exception $e) {
        }
        unset($data['socpeData']);
        return $data;
    }

    /**
     * TODO 删除活动
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2022/9/17
     */
    public function deleteActivity($id)
    {
        return Db::transaction(function() use($id){
            $this->dao->delete($id);
            app()->make(RelevanceRepository::class)->clear($id,[RelevanceRepository::SCOPE_TYPE_PRODUCT,RelevanceRepository::SCOPE_TYPE_STORE,RelevanceRepository::SCOPE_TYPE_CATEGORY],'left_id');
        });
    }

    public function getActivityBySpu(int $type, $spuId, $cateId, $merId)
    {
        $make = app()->make(RelevanceRepository::class);
        $list = $this->dao->getSearch(['activity_type' => $type,'status' => 1,'is_show' => 1,'gt_end_time' => date('Y-m-d H:i:s',time())])->setOption('field',[])->field('activity_id,scope_type,activity_type,pic')->order('create_time DESC')->select()->toArray();
        foreach ($list as $item) {
            switch ($item['scope_type']) {
                case self::TYPE_ALL:
                    return $item;
                    break;
                case self::TYPE_MUST_PRODUCT:
                    $_type = RelevanceRepository::SCOPE_TYPE_PRODUCT;
                    $right_id = $spuId ?:0;
                    break;
                case self::TYPE_MUST_CATEGORY:
                    $_type = RelevanceRepository::SCOPE_TYPE_CATEGORY;
                    $right_id = $cateId ?:0;
                    break;
                case self::TYPE_MUST_STORE:
                    $_type = RelevanceRepository::SCOPE_TYPE_STORE;
                    $right_id = $merId ?:0;
                    break;
            }
            if (isset($_type)) {
                $res = $make->checkHas($item['activity_id'], $right_id, $_type);
                if ($res) return $item;
            }
        }
        return [];
    }
}
