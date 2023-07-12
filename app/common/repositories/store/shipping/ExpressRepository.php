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

namespace app\common\repositories\store\shipping;

use app\common\repositories\BaseRepository;
use app\common\dao\store\shipping\ExpressDao as dao;
use crmeb\services\CrmebServeServices;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;

/**
 * Class ExpressRepository
 * @package app\common\repositories\store\shipping
 * @day 2020/6/13
 * @mixin dao
 */
class ExpressRepository extends BaseRepository
{

    /**
     * ExpressRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @param string $value
     * @param string $name
     * @return bool
     */
    public function nameExists(string $value,?int $id)
    {
        return $this->dao->merFieldExists('name',$value,null,$id);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @param string $value
     * @param $code
     * @return bool
     */
    public function codeExists(string $value,?int $id)
    {
        return $this->dao->merFieldExists('code',$value,null,$id);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @param int $value
     * @return bool
     */
    public function fieldExists(int $value)
    {
        return $this->dao->merFieldExists($this->dao->getPk(),$value);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function search(array $where,int $page, int $limit)
    {
        $query = $this->dao->search($where)->order('is_show DESC,sort DESC,id ASC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/22
     * @param int $merId
     * @param int|null $id
     * @param array $formData
     * @return \FormBuilder\Form
     */
    public function form(int $merId, ?int $id = null, array $formData = [])
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemExpressCreate')->build() : Route::buildUrl('systemExpressUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::input('name', '快递公司名称')->required(),
            Elm::input('code', '快递公司编码')->required(),
            Elm::switches('is_show', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
        ]);

        return $form->setTitle(is_null($id) ? '添加快递公司' : '编辑快递公司')->formData($formData);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/22
     * @param int|null $merId
     * @param $id
     * @return \FormBuilder\Form
     */
    public function updateForm(?int$merId,$id)
    {
        return $this->form($merId, $id, $this->dao->get($id, $merId)->toArray());
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/22
     * @param $id
     * @param $data
     * @return int
     */
    public function switchStatus($id,$data)
    {
        return $this->dao->update($id,$data);
    }

    public function options()
    {
        return $this->dao->selectWhere(['is_show' => 1], 'name label,code value')->toArray();
    }


    /**
     * TODO 从一号通同步数据
     * @author Qinii
     * @day 7/23/21
     */
    public function syncExportAll()
    {
        $services = app()->make(CrmebServeServices::class);
        $result = $services->express()->express(1);
        $has = $this->dao->search([])->find();
        if (!is_null($has)) {
            $arr = [];
            foreach ($result['data'] as $datum) {
                $res = $this->dao->getWhere(['code' => $datum['code']]);
                if ($res) {
                    $res->name = $datum['name'];
                    $res->mark = $datum['mark'];
                    $res->partner_id = $datum['partner_id'];
                    $res->partner_key = $datum['partner_key'];
                    $res->partner_name = $datum['partner_name'];
                    $res->check_man = $datum['check_man'];
                    $res->is_code = $datum['is_code'];
                    $res->net = $datum['net'];
                    $res->save();
                } else {
                    $arr[] = [
                        'code' => $datum['code'],
                        'name' => $datum['name'],
                        'mark' => $datum['mark'],
                        'partner_id' => $datum['partner_id'],
                        'partner_key' => $datum['partner_key'],
                        'partner_name' => $datum['partner_name'],
                        'check_man' => $datum['check_man'],
                        'is_code' => $datum['is_code'],
                        'net' => $datum['net'],
                    ];
                }
            }
        } else {
            $arr = $result['data'];
        }
        $this->dao->insertAll($arr);
    }


    /**
     * TODO
     * @param $id
     * @param $merId
     * @return \FormBuilder\Form
     * @author Qinii
     * @day 7/23/21
     */
    public function partnerForm($id,$merId)
    {
        $where = ['mer_id' => $merId,'express_id' => $id];
        $formData = $this->dao->get($id);

        if(!$formData['partner_id'] && !$formData['partner_key'] && !$formData['check_man'] && !$formData['partner_name'] && !$formData['is_code']&& !$formData['net'])
            throw new ValidateException('无需月结账号');

        $res = app()->make(ExpressPartnerRepository::class)->getSearch($where)->find();

        $form = Elm::createForm(Route::buildUrl('merchantExpressPratnerUpdate', ['id' => $id])->build());

        if ($formData['partner_id'] == 1)
            $field[] = Elm::input('account', '月结账号', $res['account'] ?? '');
        if ($formData['partner_key'] == 1)
            $field[] = Elm::input('key', '月结密码', $res['key'] ?? '');
        if ($formData['net'] == 1)
            $field[] = Elm::input('net_name', '取件网点', $res['net_name'] ?? '');
        if ($formData['check_man'] == 1)
            $field[] = Elm::input('check_man', '承载快递员名称', $res['check_man'] ?? '');
        if ($formData['partner_name'] == 1)
            $field[] = Elm::input('partner_name', '客户账户名称', $res['partner_name'] ?? '');
        if ($formData['is_code'] == 1)
            $field[] = Elm::input('code', '承载编号', $res['code'] ?? '');

        $field[] = Elm::radio('status', '是否启用', $res['status'] ?? 1)->options([['value' => 0, 'label' => '隐藏'], ['value' => 1, 'label' => '启用']]);

        $form->setRule($field);

        return $form->setTitle('编辑月结账号')->formData($formData->toArray());
    }

    /**
     * TODO 添加月结账号
     * @param array $data
     * @author Qinii
     * @day 7/23/21
     */
    public function updatePartne(array $data)
    {
        Db::transaction(function ()use($data){
            $make  = app()->make(ExpressPartnerRepository::class);
            $where = [
                'express_id' => $data['express_id'],
                'mer_id' => $data['mer_id']
            ];
            $getData = $make->getSearch($where)->find();
            if($getData){
                $make->update($getData['id'],$data);
            }else{
                $make->create($data);
            }
        });
    }
}
