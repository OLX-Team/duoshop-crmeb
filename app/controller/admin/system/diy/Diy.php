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

namespace app\controller\admin\system\diy;

use app\common\repositories\article\ArticleRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\diy\DiyRepository;
use crmeb\basic\BaseController;
use think\App;
use think\exception\ValidateException;

class Diy extends BaseController
{

    protected $repository;
    public function __construct(App $app, DiyRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * DIY列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lst()
    {
        $where = $this->request->params([
            ['status', ''],
            ['type', ''],
            ['name', ''],
            ['version', ''],
            ['is_diy',1]
        ]);
        $where['mer_id'] = $this->request->merId();
        [$page, $limit] = $this->getPage();
        $data = $this->repository->getSysList($where,$page, $limit);
        return app('json')->success($data);
    }

    /**
     * 保存资源
     * @param int $id
     * @return mixed
     */
    public function saveData(int $id = 0)
    {
        $data = $this->request->params([
            ['name', ''],
            ['title', ''],
            ['value', ''],
            ['type', '1'],
            ['cover_image', ''],
            ['is_show', 0],
            ['is_bg_color', 0],
            ['is_bg_pic', 0],
            ['bg_tab_val', 0],
            ['color_picker', ''],
            ['bg_pic', ''],
            ['is_diy',1],
        ]);
        $data['mer_id'] = $this->request->merId();
        $value = is_string($data['value']) ? json_decode($data['value'], true) : $data['value'];
        $infoDiy = $id ? $this->repository->getWhere(['id' => $id, 'mer_id' => $data['mer_id']]) : [];
        if ($infoDiy && $infoDiy['is_default'])
            return app('json')->fail('默认模板不能修改');
        if ($infoDiy) {
            foreach ($value as $k => $item) {
                if ($item['name'] === 'goodList') {
                    if (isset($item['selectConfig']['list'])) {
                        unset($item['selectConfig']['list']);
                    }
                    if (isset($item['goodsList']['list']) && is_array($item['goodsList']['list'])) {
                        $item['goodsList']['ids'] = array_column($item['goodsList']['list'], 'product_id');
                        unset($item['goodsList']['list']);
                    }
                } elseif ($item['name'] === 'articleList') {
                    if (isset($item['selectList']['list']) && is_array($item['selectList']['list'])) {
                        unset($item['selectList']['list']);
                    }
                } elseif ($item['name'] === 'promotionList') {
                    unset($item['productList']['list']);
                }
                $value[$k] = $item;
            }
            $data['value'] = json_encode($value);
        } else {
            if (isset($value['d_goodList']['selectConfig']['list'])) {
                unset($value['d_goodList']['selectConfig']['list']);
            } elseif (isset($value['d_goodList']['goodsList']['list'])) {
                $limitMax = config('database.page.limitMax', 50);
                if (isset($value['d_goodList']['numConfig']['val']) && isset($value['d_goodList']['tabConfig']['tabVal']) && $value['d_goodList']['tabConfig']['tabVal'] == 0 && $value['d_goodList']['numConfig']['val'] > $limitMax) {
                    return app('json')->fail('您设置得商品个数超出系统限制,最大限制' . $limitMax . '个商品');
                }
                $value['d_goodList']['goodsList']['ids'] = array_column($value['d_goodList']['goodsList']['list'], 'product_id');
                unset($value['d_goodList']['goodsList']['list']);
            } elseif (isset($value['k_newProduct']['goodsList']['list'])) {
                $list = [];
                foreach ($value['k_newProduct']['goodsList']['list'] as $item) {
                    $list[] = [
                        'image' => $item['image'],
                        'store_info' => $item['store_info'],
                        'store_name' => $item['store_name'],
                        'id' => $item['id'],
                        'price' => $item['price'],
                        'ot_price' => $item['ot_price'],
                    ];
                }
                $value['k_newProduct']['goodsList']['list'] = $list;
            } elseif (isset($value['selectList']['list']) && is_array($value['selectList']['list'])) {
                unset($value['goodsList']['list']);
            }
            $data['value'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $data['version'] = '1.0';
        return app('json')->success($id ? '修改成功' : '保存成功',
            ['id' => $this->repository->saveData($id, $data)]
        );
    }

    public function select()
    {
        $where = ['is_diy' => 0, 'is_del' => 0];
        $data = $this->repository->getOptions($where);
        return app('json')->success($data);
    }

    /**
     * 删除模板
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        $this->repository->del($id,$this->request->merId());
        return app('json')->success('删除成功');
    }

    public function getDiyInfo()
    {
        return app('json')->success($this->repository->getDiyInfo(0,$this->request->merId()));
    }

    /**
     * 使用模板
     * @param $id
     * @return mixed
     */
    public function setStatus($id)
    {
        $this->repository->setUsed($id,$this->request->merId());
        return app('json')->success('修改成功');
    }

    /**
     * 获取一条数据
     * @param int $id
     * @return mixed
     */
    public function getInfo(int $id)
    {
        if (!$id) throw new ValidateException('参数错误');
        $info = $this->repository->getWhere([$this->repository->getPk() => $id, 'mer_id' => $this->request->merId()]);
        if ($info) {
            $info = $info->toArray();
        } else {
            throw new ValidateException('模板不存在');
        }
        $info['value'] = json_decode($info['value'], true);
        if ($info['value']) {
            $articleServices = app()->make(ArticleRepository::class);
            if ($info['is_diy']) {
                foreach ($info['value'] as &$item) {
                    if ($item['name'] === 'goodList' && isset($item['goodsList']['ids']) && count($item['goodsList']['ids'])) {
                        $item['goodsList']['list'] = app()->make(SpuRepository::class)->search(['product_ids' => $item['goodsList']['ids']])->select();
                    } elseif ($item['name'] === 'articleList') {//文章
                        $data = [];
                        if ($item['selectConfig']['activeValue'] ?? 0) {
                            $data = $articleServices->search(0,['cid' => $item['selectConfig']['activeValue'] ?? 0], 0, $item['numConfig']['val'] ?? 10);
                            $data = $data['list'];
                        }
                        $item['selectList']['list'] = $data['list'] ?? [];
                    } elseif ($item['name'] === 'promotionList') {//活动模仿
                        $data = [];
                        if (isset($item['tabConfig']['tabCur']) && $typeArr = $item['tabConfig']['list'][$item['tabConfig']['tabCur']] ?? []) {
                            $val = $typeArr['link']['activeVal'] ?? 0;
                            if ($val) {
                                $data = $this->get_groom_list($val, (int)($item['numConfig']['val'] ?? 0));
                            }
                        }
                        $item['productList']['list'] = $data;
                    }
                }
            } else {
                if ($info['value']) {
                    if (isset($info['value']['d_goodList']['goodsList'])) {
                        $info['value']['d_goodList']['goodsList']['list'] = [];
                    }
                    if (isset($info['value']['d_goodList']['goodsList']['ids']) && count($info['value']['d_goodList']['goodsList']['ids'])) {
                        $info['value']['d_goodList']['goodsList']['list'] = app()->make(SpuRepository::class)->getApiSearch(['product_ids' => $info['value']['d_goodList']['goodsList']['ids']],1,10);
                    }
                }
            }
        }
        return app('json')->success(compact('info'));
    }


    /**
     * 设置模版默认数据
     * @param $id
     * @return mixed
     */
    public function setDefaultData($id)
    {
        if (!$id) return app('json')->fail('参数错误');

        $info = $this->repository->getWhere([$this->repository->getPk() => $id, 'mer_id' => $this->request->merId()]);
        if ($info) {
            if ($info->is_default)  return app('json')->fail('默认模板不能修改');
            $info->default_value = $info->value;
            $info->update_time = time();
            $info->save();
            return app('json')->success('设置成功');
        } else {
            return app('json')->fail('模板不存在');
        }
    }

    /**
     * 还原模板数据
     * @param $id
     * @return mixed
     */
    public function Recovery($id)
    {
        if (!$id)  return app('json')->fail('参数错误');
        $info = $this->repository->getWhere([$this->repository->getPk() => $id, 'mer_id' => $this->request->merId()]);
        if ($info) {
            if ($info->is_default)  return app('json')->fail('默认模板不能修改');
            $info->value = $info->default_value;
            $info->update_time = time();
            $info->save();
            return app('json')->success('还原成功');
        } else {
            return app('json')->fail('模板不存在');
        }
    }

    /**
     * 实际获取方法
     * @param $type
     * @return array
     */
    protected function get_groom_list($type, int $num = 0)
    {
        $services = app()->make(SpuRepository::class);
        $info = [];
        [$page, $limit] = $this->getPage();

        $where['is_gift_bag'] = 0;
        $where['order'] = 'star';
        $where['product_type'] = 0;
        if ($type == 1) {//TODO 精品推荐
            $where['hot_type'] = 'best';
            $info = $services->getApiSearch($where,  $page, $limit, null);//TODO 精品推荐个数
        } else if ($type == 2) {//TODO  热门榜单
            $where['hot_type'] = 'hot';
            $info = $services->getApiSearch($where,  $page, $limit, null);//TODO 热门榜单 猜你喜欢
        } else if ($type == 3) {//TODO 首发新品
            $where['hot_type'] = 'new';
            $info = $services->getApiSearch($where,  $page, $limit, null);//TODO 首发新品
        } else if ($type == 4) {//TODO 促销单品
            $where['hot_type'] = 'good';
            $info = $services->getApiSearch($where,  $page, $limit, null);//TODO 促销单品
        }
        return $info;
    }

    public function productLst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params([
            ['store_name',''],
            ['order', 'star'],
            ['cate_pid',0],
            ['star',''],
            'product_type',
            'mer_cate_id'
        ]);
        $where['is_gift_bag'] = 0;
        $where['keyword'] = $where['store_name'];
        if ($this->request->merId()) $where['mer_id'] = $this->request->merId();
        $data = app()->make(SpuRepository::class)->getApiSearch($where, $page, $limit, null);
        return app('json')->success($data);
    }

    public function copy($id)
    {
        $data = $this->repository->copy($id,$this->request->merId());
        return app('json')->success($data);
    }
}
