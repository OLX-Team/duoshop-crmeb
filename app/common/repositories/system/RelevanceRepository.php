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


namespace app\common\repositories\system;


use app\common\dao\system\RelevanceDao;
use app\common\repositories\BaseRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * @mixin RelevanceDao
 */
class RelevanceRepository extends BaseRepository
{

    //文章关联商品
    const TYPE_COMMUNITY_PRODUCT  =  'community_product';
    //社区关注
    const TYPE_COMMUNITY_FANS  =  'fans';
    //社区文章点赞
    const TYPE_COMMUNITY_START  =  'community_start';
    //社区评论点赞
    const TYPE_COMMUNITY_REPLY_START  =  'community_reply_start';
    //商户权限
    const TYPE_MERCHANT_AUTH = 'mer_auth';

    //指定范围类型
    //0全部商品
    const TYPE_ALL = 'scope_type';
    //指定商品
    const SCOPE_TYPE_PRODUCT = 'scope_type_product';
    //指定分类
    const SCOPE_TYPE_CATEGORY = 'scope_type_category';
    //指定商户
    const SCOPE_TYPE_STORE = 'scope_type_store';
    //价格说明关联分类
    const PRICE_RULE_CATEGORY = 'price_rule_category';

    //商品参数关联
    const PRODUCT_PARAMES_CATE = 'product_params_cate';

    protected $dao;
    /**
     * RelevanceRepository constructor.
     * @param RelevanceDao $dao
     */
    public function __construct(RelevanceDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 添加
     * @param int $leftId
     * @param int $rightId
     * @param string $type
     * @param $check
     * @return bool
     * @author Qinii
     * @day 10/28/21
     */
    public function create(int $leftId, int $rightId, string $type, bool $check = false)
    {
        if ($check && $this->checkHas($leftId, $rightId, $type))  {
            return false;
        }

        $data = [
            'left_id' => $leftId,
            'right_id'=> $rightId,
            'type'    => $type,
        ];

        try{
            $this->dao->create($data);
            return true;
        } catch (\Exception  $exception) {
            throw new ValidateException('创建失败');
        }
    }

    /**
     * TODO 删除
     * @param int $leftId
     * @param string $type
     * @param int $rightId
     * @return bool
     * @author Qinii
     * @day 10/28/21
     */
    public function destory(int $leftId, int $rightId, string $type)
    {
        return $this->dao->getSearch([
            'left_id' => $leftId,
            'right_id'=> $rightId,
            'type'    => $type,
        ])->delete();
    }

    /**
     * TODO 检测是否存在
     * @param int $leftId
     * @param int $rightId
     * @param string $type
     * @return int
     * @author Qinii
     * @day 10/28/21
     */
    public function checkHas(int $leftId, int $rightId, string $type)
    {
        return $this->dao->getSearch([
            'left_id' => $leftId,
            'right_id'=> $rightId,
            'type'    => $type,
        ])->count();
    }

    /**
     * TODO 根据左键批量删除
     * @param int $leftId
     * @param $type
     * @return bool
     * @author Qinii
     * @day 10/28/21
     */
    public function batchDelete(int $leftId, $type)
    {
        return $this->dao->getSearch([
            'left_id' => $leftId,
            'type'    => $type,
        ])->delete();
    }

    /**
     * TODO 关注我的人
     * @param int $uid
     * @return \think\Collection
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserFans(int $uid, int $page, int $limit)
    {
        $query = $this->dao->getSearch([
            'right_id' => $uid,
            'type'    => self::TYPE_COMMUNITY_FANS,
        ])->with([
            'fans' => function($query) {
                $query->field('uid,avatar,nickname,count_fans');
            }
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->append(['is_start']);
        return compact('count','list');
    }

    /**
     * TODO 我关注的人
     * @param $uid
     * @return \think\Collection
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserFocus(int $uid, int $page, int $limit)
    {
        $query = $this->dao->getSearch([
            'left_id' => $uid,
            'type'    => self::TYPE_COMMUNITY_FANS,
        ])->with([
            'focus' => function($query) {
                $query->field('uid,avatar,nickname,count_fans');
            }
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->append(['is_fans']);
        return compact('count','list');
    }


    /**
     * TODO 我点赞过的文章
     * @param int $uid
     * @return \think\Collection
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserStartCommunity(array $where, int $page, int $limit)
    {
        $query = $this->dao->joinUser($where)->with([
            'community'=> function($query) use($where){
                $query->with([
                    'author' => function($query){
                        $query->field('uid,real_name,status,avatar,nickname,count_start');
                    },
                    'is_start' => function($query) use ($where) {
                        $query->where('left_id',$where['uid']);
                    },
                    'topic' => function($query) {
                        $query->where('status', 1)->where('is_del',0);
                        $query->field('topic_id,topic_name,status,category_id,pic,is_del');
                    },
                    'relevance'  => [
                        'spu' => function($query) {
                            $query->field('spu_id,store_name,image,price,product_type,activity_id,product_id');
                        }
                    ],
                    'is_fans' => function($query) use($where){
                        $query->where('left_id',$where['uid']);
                    }]);
            },
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->each(function ($item){
            $item['time'] = date('m月d日', strtotime($item['create_time']));
            return $item;
        });

        return compact('count','list');
    }

    /**
     * TODO 我点赞过的文章
     * @param int $uid
     * @return \think\Collection
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserStartCommunityByVideos(array $where, int $page, int $limit)
    {
        $query = $this->dao->joinUser($where)->with([
            'community'=> function($query) {
                $query->with(['author'=> function($query) {
                    $query->field('uid,avatar,nickname');
                }]);
            },
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->each(function ($item){
            $item['time'] = date('m月d日', strtotime($item['create_time']));
            return $item;
        });

        return compact('count','list');
    }

    public function getFieldCount(string $field, int $value, string $type)
    {
        return $this->dao->getSearch([$field => $value, 'type'    => $type,])->count();
    }

    public function createMany(int $leftId, array $rightId, string $type)
    {
        if (!empty($rightId)) {
            foreach ($rightId as $value) {
                $res[] = [
                    'left_id' => $leftId,
                    'right_id' => $value,
                    'type' => $type,
                ];
            }
            $this->dao->insertAll($res);
        }
    }

}
