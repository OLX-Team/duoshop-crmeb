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


namespace app\common\repositories\store\service;


use app\common\dao\store\service\StoreServiceReplyDao;
use app\common\repositories\BaseRepository;

/**
 * Class StoreServiceRepository
 * @package app\common\repositories\store\service
 * @author xaboy
 * @day 2020/5/29
 * @mixin StoreServiceReplyDao
 */
class StoreServiceReplyRepository extends BaseRepository
{
    /**
     * StoreServiceRepository constructor.
     * @param StoreServiceReplyDao $dao
     */
    public function __construct(StoreServiceReplyDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList($where, $page, $limit)
    {
        $query = $this->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }

    public function create($data)
    {
        if (is_array($data['keyword'])) $data['keyword'] = implode(',', $data['keyword']);
        return $this->dao->create($data);
    }

}
