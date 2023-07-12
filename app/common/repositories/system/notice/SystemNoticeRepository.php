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


namespace app\common\repositories\system\notice;


use app\common\dao\system\notice\SystemNoticeDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\merchant\MerchantCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use think\exception\ValidateException;
use think\facade\Db;

class SystemNoticeRepository extends BaseRepository
{
    public function __construct(SystemNoticeDao $dao)
    {
        $this->dao = $dao;
    }

    public function create(array $data, $admin_id)
    {
        $data['admin_id'] = $admin_id;

        $merchantRepository = app()->make(MerchantRepository::class);
        if ($data['type'] == 1) {
            $ids = (array)$data['mer_id'];
            $type_str = implode('/', $merchantRepository->names($ids));
        } else if ($data['type'] == 2) {
            $ids = $merchantRepository->search(['is_trader' => (int)$data['is_trader']])->column('mer_id');
            $type_str = $data['is_trader'] ? '自营' : '非自营';
        } else if ($data['type'] == 3) {
            $ids = $merchantRepository->search(['category_id' => (array)$data['category_id']])->column('mer_id');
            $type_str = implode('/', app()->make(MerchantCategoryRepository::class)->names((array)$data['category_id']));
        } else if ($data['type'] == 4) {
            $ids = $merchantRepository->search([])->column('mer_id');
            $type_str = '全部';
        } else {
            throw new ValidateException('商户类型有误');
        }

        if (!count($ids)) throw new ValidateException('没有有效的商户信息');
        $data['type_str'] = $type_str;
        unset($data['is_trader'], $data['category_id'], $data['mer_id']);

        return Db::transaction(function () use ($data, $ids) {
            $notice = $this->dao->create($data);
            $systemNoticeLogRepository = app()->make(SystemNoticeLogRepository::class);
            $inserts = [];
            foreach ($ids as $id) {
                if (!$id) continue;
                $inserts[] = [
                    'mer_id' => (int)$id,
                    'notice_id' => $notice->notice_id
                ];
            }
            $systemNoticeLogRepository->insertAll($inserts);
            return $notice;
        });
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('notice_id DESC')->select();
        return compact('count', 'list');
    }
}
