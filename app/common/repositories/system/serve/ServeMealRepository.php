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

namespace app\common\repositories\system\serve;

use app\common\dao\system\serve\ServeMealDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class ServeMealRepository extends BaseRepository
{
    protected $dao;

    public function __construct(ServeMealDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        $query = $this->dao->getSearch($where)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    public function updateForm($id)
    {
        $data = $this->dao->get($id);
        if(!$data)  throw new ValidateException('数据不存在');
        $data = $data->toArray();
        return $this->form($id,$data);
    }

    public function form($id = null, array $formData = [])
    {
        $isCreate = is_null($id);
        $action = Route::buildUrl($isCreate ? 'systemServeMealCreate' : 'systemServeMealUpdate', $isCreate ? [] : compact('id'))->build();
        return Elm::createForm($action, [
            Elm::input('name', '套餐名称')->required(),
            Elm::radio('type', '套餐类型 ',1)->options([
                ['value' => 1, 'label' => '一号通商品采集'],
                ['value' => 2, 'label' => '一号通电子面单'],
            ]),
            Elm::number('price', '价格')->required(),
            Elm::number('num', '数量')->required(),
            Elm::radio('status', '状态', 1)->options([
                ['label' => '开启', 'value' => 1],
                ['label' => '关闭', 'value' => 0]
            ]),
            Elm::number('sort', '排序')->required()->precision(0)->max(99999),

        ])->setTitle($isCreate ? '添加套餐' : '编辑套餐')->formData($formData);
    }

    public function delete($id)
    {
        $data = $this->dao->get($id);
        if(!$data) throw new ValidateException('数据不存在');
        $data->is_del = 1;
        $data->save();
    }

    public function QrCode(int $merId, array $data)
    {
        $ret = $this->dao->get($data['meal_id']);
        if(!$data)  throw new ValidateException('数据不存在');
        $param = [
            'status' => 0,
            'is_del' => 0,
            'mer_id' => $merId,
            'type'   => $data['type'],
            'meal_id'=> $ret['meal_id'],
        ];
        $this->dao->getSearch($param)->finid();

    }
}
