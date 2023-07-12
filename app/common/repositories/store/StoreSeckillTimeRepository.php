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

use app\common\dao\store\StoreSeckillTimeDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\facade\Route;

class StoreSeckillTimeRepository extends BaseRepository
{
    /**
     * @var StoreSeckillDao
     */
    protected $dao;

    /**
     * StoreSeckillTimeRepository constructor.
     * @param StoreSeckillDao $dao
     */
    public function __construct(StoreSeckillTimeDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where,int $page, int$limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page,$limit)->select();

        return compact('count','list');
    }

    public function select()
    {
        $query = $this->dao->search(['status' => 1]);
        $list = $query->select();
        return $list;
    }

    public function form(?int $id = null ,array $formData = [])
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemSeckillConfigCreate')->build() : Route::buildUrl('systemSeckillConfigUpdate', ['id' => $id])->build());

        $form->setRule([
            Elm::input('title','标题')->required(),
            Elm::select('start_time','开始时间')->options($this->dao->getTime(1))->requiredNum(),
            Elm::select('end_time','结束时间')->options($this->dao->getTime(0))->requiredNum(),
            Elm::switches('status','是否启用')->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::frameImage('pic', '图片', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=pic&type=1')->width('896px')->height('480px')->spin(0)->modal(['modal' => false])->props(['footer' => false]),
        ]);
        return $form->setTitle(is_null($id) ? '添加'  : '编辑')->formData($formData);
    }


    public function updateForm($id)
    {
        return $this->form($id,$this->dao->get($id)->toArray());
    }

    /**
     * TODO 所选时间段是否重叠
     * @param $where
     * @return bool
     * @author Qinii
     * @day 2020-07-31
     */
    public function checkTime(array $where,?int $id)
    {
        if(!$this->dao->valStartTime($where['start_time'],$id) && !$this->dao->valEndTime($where['end_time'],$id) && !$this->dao->valAllTime($where,$id)) return true;
        return false;
    }

    /**
     * TODO APi秒杀时间列表
     * @return array
     * @author Qinii
     * @day 2020-08-11
     */
    public function selectTime()
    {
        $seckillTimeIndex = 0;
        $_h = date('H',time());
        $query = $this->dao->search(['status' => 1]);
        $list = $query->select();
        $seckillEndTime = time();
        $seckillTime = [];
        foreach($list as $k => $item){
            $item['stop'] = strtotime((date('Y-m-d ',time()).$item['end_time'].':00:00'));
            if($item['end_time'] <= $_h) {
                $item['pc_status'] = 0;
                $item['state'] = '已结束';
            }
            if($item['start_time'] > $_h ) {
                $item['pc_status'] = 2;
                $item['state'] = '待开始';
            }
            if($item['start_time'] <= $_h && $_h < $item['end_time']){
                $item['pc_status'] = 1;
                $item['state'] = '抢购中';
                $seckillTimeIndex = $k;
                $seckillEndTime = strtotime((date('Y-m-d ',time()).$item['end_time'].':00:00'));
                $item['stop_time'] = date('Y-m-d H:i:s', $seckillEndTime);
            }

            $seckillTime[$k] = $item;
        }
        return  compact('seckillTime','seckillTimeIndex','seckillEndTime');
    }

    /**
     * TODO 获取某个时间是否有开启秒杀活动
     * @param array $where
     * @return mixed
     * @author Qinii
     * @day 2020-08-19
     */
    public function getBginTime(array $where)
    {
        if($where['start_time'] == '' || $where['end_time'] == ''){
            $where['start_time'] = date('H',time());
            $where['end_time'] = date('H',time()) + 1;
        }
        $where['status'] = 1;
        return $this->dao->search($where)->find();
    }
}
