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


namespace app\common\repositories\system\diy;

use app\common\dao\system\diy\DiyDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use think\exception\ValidateException;
use think\facade\Db;

class DiyRepository extends BaseRepository
{
    const IS_DEFAULT_DIY = 'is_default_diy';

    public function __construct(DiyDao $dao)
    {
        $this->dao = $dao;
    }

    public function getThemeVar($type)
    {
        $var = [
            'purple' => [
                'type' => 'purple',
                'theme_color' => '#905EFF',
                'assist_color' => '#FDA900',
                'theme' => '--view-theme: #905EFF;--view-assist:#FDA900;--view-priceColor:#FDA900;--view-bgColor:rgba(253, 169, 0,.1);--view-minorColor:rgba(144, 94, 255,.1);--view-bntColor11:#FFC552;--view-bntColor12:#FDB000;--view-bntColor21:#905EFF;--view-bntColor22:#A764FF;'
            ],
            'orange' => [
                'type' => 'orange',
                'theme_color' => '#FF5C2D',
                'assist_color' => '#FDB000',
                'theme' => '--view-theme: #FF5C2D;--view-assist:#FDB000;--view-priceColor:#FF5C2D;--view-bgColor:rgba(253, 176, 0,.1);--view-minorColor:rgba(255, 92, 45,.1);--view-bntColor11:#FDBA00;--view-bntColor12:#FFAA00;--view-bntColor21:#FF5C2D;--view-bntColor22:#FF9445;'
            ],
            'pink' => [
                'type' => 'pink',
                'theme_color' => '#FF448F',
                'assist_color' => '#FDB000',
                'theme' => '--view-theme: #FF448F;--view-assist:#FDB000;--view-priceColor:#FF448F;--view-bgColor:rgba(254, 172, 65,.1);--view-minorColor:rgba(255, 68, 143,.1);--view-bntColor11:#FDBA00;--view-bntColor12:#FFAA00;--view-bntColor21:#FF67AD;--view-bntColor22:#FF448F;'
            ],
            'default' => [
                'type' => 'default',
                'theme_color' => '#E93323',
                'assist_color' => '#FF7612',
                'theme' => '--view-theme: #E93323;--view-assist:#FF7612;--view-priceColor:#E93323;--view-bgColor:rgba(255, 118, 18,.1);--view-minorColor:rgba(233, 51, 35,.1);--view-bntColor11:#FEA10F;--view-bntColor12:#FA8013;--view-bntColor21:#FA6514;--view-bntColor22:#E93323;'
            ],
            'green' => [
                'type' => 'green',
                'theme_color' => '#42CA4D',
                'assist_color' => '#FE960F',
                'theme' => '--view-theme: #42CA4D;--view-assist:#FE960F;--view-priceColor:#FE960F;--view-bgColor:rgba(254, 150, 15,.1);--view-minorColor:rgba(66, 202, 77,.1);--view-bntColor11:#FDBA00;--view-bntColor12:#FFAA00;--view-bntColor21:#42CA4D;--view-bntColor22:#70E038;'
            ],
            'blue' => [
                'type' => 'blue',
                'theme_color' => '#1DB0FC',
                'assist_color' => '#FFB200',
                'theme' => '--view-theme: #1DB0FC;--view-assist:#FFB200;--view-priceColor:#FFB200;--view-bgColor:rgba(255, 178, 0,.1);--view-minorColor:rgba(29, 176, 252,.1);--view-bntColor11:#FFD652;--view-bntColor12:#FEB60F;--view-bntColor21:#40D1F4;--view-bntColor22:#1DB0FC;'
            ],
        ];
        return $var[$type] ?? $var['default'];
    }

    /**
     * 获取DIY列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSysList(array $where,int $page, int $limit)
    {
        $field = 'is_diy,template_name,id,title,name,type,add_time,update_time,status,is_default';
        $where['is_del'] = 0;
        $query = $this->dao->getSearch($where)->order('is_default DESC, status DESC, update_time DESC,add_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->setOption('field',[])->field($field)->select()
            ->each(function($item) use($where){

                if ($item['is_default'])  {
                    $id = merchantConfig($where['mer_id'], self::IS_DEFAULT_DIY) ?: 0;
                    $item['status'] = ($id == $item['id']) ? 1 : 0;
                    return $item;
                }
            });
        return compact('count','list');
    }

    /**
     * 保存资源
     * @param int $id
     * @param array $data
     * @return int
     */
    public function saveData(int $id = 0, array $data)
    {
        if ($id) {
            if ($data['type'] === '') {
                unset($data['type']);
            }
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $this->dao->update($id, $data);
        } else {
            $data['add_time'] = date('Y-m-d H:i:s',time());
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $res = $this->dao->create($data);
            if (!$res) throw new ValidateException('保存失败');
            $id = $res->id;
        }
        return $id;
    }

    /**
     * 删除DIY模板
     * @param int $id
     */
    public function del(int $id, $merId)
    {
        $count = $this->dao->getWhere(['id' => $id,'mer_id' => $merId]);
        if ($count['is_default']) throw new ValidateException('默认模板无法删除');
        if ($count['status']) throw new ValidateException('该模板使用中，无法删除');
        $res = $this->dao->update($id, ['is_del' => 1]);
        if (!$res) throw new ValidateException('删除失败，请稍后再试');
    }


    /**
     * 获取diy详细数据
     * @param int $id
     * @return array|object
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiyInfo($id, $merId,$is_diy = 1)
    {
        if ($merId) {
            $merchant = app()->make(MerchantRepository::class)->get($merId);
            if (!$merchant) throw new ValidateException('店铺已关闭');
            if (!$merchant['mer_state']) throw new ValidateException('店铺已打烊');
        }
        $field = 'title,value';
        if ($id) {
            $diyInfo = $this->dao->getWhere([$this->dao->getPk() => $id,'is_del' => 0], $field);
            if (!$diyInfo && !$is_diy) throw new ValidateException('页面不存在');
        } else {
            $id = merchantConfig($merId, self::IS_DEFAULT_DIY) ?: 0;
            $diyInfo = $this->dao->getWhere(['id' => $id,'is_del' => 0]);
            if (!$diyInfo) {
                $where = ['is_default' => $merId ? 2 : 1,];
                $diyInfo = $this->dao->getWhere($where,$field);
            }
        }
        if ($diyInfo) {
            $diyInfo = $diyInfo->toArray();
            $diyInfo['value'] = json_decode($diyInfo['value'], true);
            return $diyInfo;
        } else {
            return [];
        }
    }

    /**
     * 获取底部导航
     * @param string $template_name
     * @return array|mixed
     */
    public function getNavigation()
    {
        $id = merchantConfig(0, self::IS_DEFAULT_DIY);
        $diyInfo = $this->dao->getWhere(['id' => $id,'is_del' => 0],'value');
        if (!$diyInfo) {
            $where = ['is_default' =>  1,];
            $diyInfo = $this->dao->getWhere($where,'value');
        }
        $navigation = [];
        if ($diyInfo) {
            $value = json_decode($diyInfo['value'], true);
            foreach ($value as $item) {
                if (isset($item['name']) && strtolower($item['name']) === 'pagefoot') {
                    $navigation = $item;
                    break;
                }
            }
        }
        return $navigation;
    }

    public function copy($id, $merId)
    {
        $data = $this->dao->getWhere([$this->dao->getPk() => $id]);

        if (!$data) throw new ValidateException('数据不存在');
        if ($merId){
            if ($data['mer_id'] != $merId && $data['is_default'] != 2) {
                throw new ValidateException('模板不属于你');
            }
        } else {
            if ($data['mer_id'] != $merId && $data['is_default'] != 1) {
                throw new ValidateException('模板不属于你');
            }
        }
        $data = $data->toArray();
        $data['name'] = $data['name'].'-copy';
        $data['add_time'] = date('Y-m-d H:i:s',time());
        $data['update_time'] = date('Y-m-d H:i:s',time());
        $data['status'] = 0;
        $data['is_default'] = 0;
        $data['mer_id'] = $merId;
        unset($data[$this->dao->getPk()]);
        $res = $this->dao->create($data);
        $id = $res[$this->dao->getPk()];
        return compact('id');
    }

    public function setUsed(int $id, int $merId)
    {
        $diyInfo = $this->dao->getWhere(['id' => $id,'is_del' => 0]);
        if (!$diyInfo) throw new ValidateException('模板不存在');

        if ($merId){
            if ($diyInfo['mer_id'] != $merId && $diyInfo['is_default'] != 2) {
                throw new ValidateException('模板不属于你');
            }
        } else {
            if ($diyInfo['mer_id'] != $merId && $diyInfo['is_default'] != 1) {
                throw new ValidateException('模板不属于你');
            }
        }
        return Db::transaction(function () use($id, $merId){
            $make = app()->make(ConfigValueRepository::class);
            $this->dao->setUsed($id, $merId);
            $make->setFormData([self::IS_DEFAULT_DIY => $id ], $merId);
            return ;
        });
    }
    public function getOptions(array $where)
    {
        return $this->dao->getSearch($where)->field('name label, id value')->select();
    }
}
