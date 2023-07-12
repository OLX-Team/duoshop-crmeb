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


namespace app\common\repositories\system\merchant;


use app\common\dao\system\merchant\MerchantTypeDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\auth\MenuRepository;
use app\common\repositories\system\RelevanceRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;

/**
 * @mixin MerchantTypeDao
 */
class MerchantTypeRepository extends BaseRepository
{
    public function __construct(MerchantTypeDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList($page, $limit)
    {
        $query = $this->dao->search()->with(['auth']);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('mer_type_id DESC')->select();
        foreach ($list as $item){
            $item['auth_ids'] = array_column($item['auth']->toArray(), 'right_id');
            unset($item['auth']);
        }
        return compact('count', 'list');
    }

    public function getSelect()
    {
        $query = $this->search([])->field('mer_type_id,type_name');
        return $query->select()->toArray();
    }

    public function delete(int $id)
    {
        return Db::transaction(function () use ($id) {
            $this->dao->delete($id);
            app()->make(MerchantRepository::class)->clearTypeId($id);
            app()->make(RelevanceRepository::class)->batchDelete($id, RelevanceRepository::TYPE_MERCHANT_AUTH);
        });
    }

    public function create(array $data)
    {
        return Db::transaction(function () use ($data) {
            $auth = array_filter(array_unique($data['auth']));
            unset($data['auth']);
            $type = $this->dao->create($data);
            $inserts = [];
            foreach ($auth as $id) {
                $inserts[] = [
                    'left_id' => $type->mer_type_id,
                    'right_id' => (int)$id,
                    'type' => RelevanceRepository::TYPE_MERCHANT_AUTH
                ];
            }
            app()->make(RelevanceRepository::class)->insertAll($inserts);
            return $type;
        });
    }

    public function update(int $id, array $data)
    {
        return Db::transaction(function () use ($id, $data) {
            $auth = array_filter(array_unique($data['auth']));

            unset($data['auth']);
            $inserts = [];
            foreach ($auth as $aid) {
                $inserts[] = [
                    'left_id' => $id,
                    'right_id' => (int)$aid,
                    'type' => RelevanceRepository::TYPE_MERCHANT_AUTH
                ];
            }
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $this->dao->update($id, $data);
            $make = app()->make(RelevanceRepository::class);
            $make->batchDelete($id, RelevanceRepository::TYPE_MERCHANT_AUTH);
            $make->insertAll($inserts);
            //更新未交保证金的商户
            app()->make(MerchantRepository::class)->updateMargin($id, $data['margin'], $data['is_margin']);
        });
    }

    public function markForm($id)
    {
        $data = $this->dao->get($id);
        if (!$data)  throw new ValidateException('数据不存在');
        $form = Elm::createForm(Route::buildUrl('systemMerchantTypeMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('mark', '备注', $data['mark'])->required(),
        ]);
        return $form->setTitle('修改备注');
    }

    public function mark($id, $data)
    {
        if (!$this->dao->getWhereCount([$this->dao->getPk() => $id]))
            throw new ValidateException('数据不存在');
        $this->dao->update($id, $data);
    }

    public function detail($id)
    {
        $find = $this->dao->search(['mer_type_id' => $id])->with(['auth'])->find();
        if (!$find)    throw new ValidateException('数据不存在');
        $ids = array_column($find['auth']->toArray(), 'right_id');
        unset($find['auth']);
        $options = [];
        if ($ids) {
            $paths = app()->make(MenuRepository::class)->getAllOptions(1, true,compact('ids'),'path');
            foreach ($paths as $id => $path) {
                $ids = array_merge($ids, explode('/', trim($path, '/')));
                array_push($ids, $id);
            }
            $auth = app()->make(MenuRepository::class)->getAllOptions(1, true, compact('ids'));
            $options = formatTree($auth, 'menu_name');
        }

        $find['options'] = $options;
        return $find;
    }
}
