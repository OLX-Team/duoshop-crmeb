<?php

namespace crmeb\jobs;

use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use crmeb\interfaces\JobInterface;
use crmeb\services\RedisCacheService;
use think\facade\Cache;
use think\facade\Log;
use function app;

class SyncProductTopJob implements JobInterface
{

    public function fire($job, $data)
    {
        try{
            $SpuRepository = app()->make(SpuRepository::class);
            $RedisCacheService = app()->make(RedisCacheService::class);
            $prefix = env('queue_name','merchant').'_hot_ranking_';
            $oldKeys1 = $RedisCacheService->keys($prefix.'top_*') ?: [];
            $oldKeys1 = array_combine($oldKeys1, $oldKeys1);
            $mset = [];
            $hot = systemConfig(['hot_ranking_switch','hot_ranking_lv']);
            if (!$hot['hot_ranking_switch']) return $job->delete();
            $where['product_type'] = 0;
            $where['spu_status'] = 1;
            $where['mer_status'] = 1;
            $where['order'] = 'sales';
            $ids = $SpuRepository->search($where)->limit(15)->column('spu_id');
            $mset[$prefix.'top_0'] = implode(',', $ids);
            unset($oldKeys1[$prefix.'top_0']);

            $make = app()->make(StoreCategoryRepository::class);
            foreach ([1,2,3] as $level) {
                $cateList = $make->getSearch(['status' => 1])->where('level','<',$level)->column('store_category_id,cate_name,pic');
                foreach ($cateList as $item) {
                    $id = $item['store_category_id'];
                    $ids = $make->findChildrenId($id);
                    $ids[] = $id;
                    $where['cate_id'] = $ids;
                    $spuList = $SpuRepository->search($where)->limit(15)->select();
                    if (count($spuList)) {
                        foreach ($spuList as $i => $spu) {
                            $key = $prefix.'top_item_' . $id . '_' . $spu['spu_id'];
                            $mset[$key] = json_encode([$item['cate_name'], $i + 1, $id], JSON_UNESCAPED_UNICODE);
                            unset($oldKeys1[$key]);
                        }
                        $_key = $prefix.'top_' . $id;
                        $mset[$_key] = implode(',', $spuList->column('spu_id'));
                        unset($oldKeys1[$_key]);
                    }
                }
                Cache::set($prefix.'topCate', implode(',', array_column($cateList, 'store_category_id')));
            }
            if (count($mset)) {
                $RedisCacheService->mSet($mset);
            }
            if (count($oldKeys1)) {
                $RedisCacheService->handler()->del(...array_values($oldKeys1));
            }
        }catch (\Exception $e){
            Log::info('热卖排行统计:' . $e->getMessage());
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }

    public function work()
    {

    }
}
