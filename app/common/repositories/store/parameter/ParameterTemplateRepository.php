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

namespace app\common\repositories\store\parameter;

use app\common\dao\store\parameter\ParameterTemplateDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\RelevanceRepository;
use think\exception\ValidateException;
use think\facade\Db;

class ParameterTemplateRepository extends BaseRepository
{
    /**
     * @var ParameterTemplateDao
     */
    protected $dao;


    /**
     * ParameterRepository constructor.
     * @param ParameterTemplateDao $dao
     */
    public function __construct(ParameterTemplateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2022/11/22
     */
    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->with([
            'cateId' => function($query){
                $query->with(['category' =>function($query) {
                    $query->field('store_category_id,cate_name');
                }]);
            },
            'merchant' => function($query) {
                $query->field('mer_id,mer_name');
            }
//            'parameter' =>function($query){
//                $query->field('parameter_id,template_id,name,value,sort')->order('sort DESC');
//            }
        ])->order('sort DESC,create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function getSelect(array $where)
    {
        return$this->dao->getSearch($where)->field('template_name label,template_id value')->select();
    }

    /**
     * TODO 详情
     * @param $id
     * @param $merId
     * @return array|\think\Model
     * @author Qinii
     * @day 2022/11/22
     */
    public function detail($id,$merId)
    {
        $where['template_id'] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $data = $this->dao->getSearch($where)->with([
            'cateId' => function($query){
                $query->with(['category' =>function($query) {
                    $query->field('store_category_id,cate_name');
                }]);
            },
            'parameter' =>function($query){
                $query->field('parameter_id,template_id,name,value,sort')->order('sort DESC');
            },
            'merchant' => function($query){
                $query->field('mer_name,mer_id');
            }
        ])->find();
        if (!$data) throw new ValidateException('数据不存在');
        return $data;
    }

    public function show($where)
    {
        $data = $this->dao->getSearch($where)->with([
            'parameter' =>function($query){
                $query->field('parameter_id,template_id,name,value,mer_id,sort')->order('sort DESC');
            }
        ])->order('mer_id ASC,create_time DESC')->select();
        $list = [];
        foreach ($data as $datum) {
            if ($datum['parameter']) {
                foreach ($datum['parameter'] as $item) {
                    $list[]  =  $item;
                }
            }
        }
        return $list;
    }
    /**
     * TODO 添加模板
     * @param $merId
     * @param $data
     * @author Qinii
     * @day 2022/11/22
     */
    public function create($merId, $data)
    {
        $params = $data['params'];
        $cate = array_unique($data['cate_ids']);
        $tem = [
            'template_name' => $data['template_name'],
            'sort' => $data['sort'],
            'mer_id' => $merId
        ];
        $paramMake = app()->make(ParameterRepository::class);
        $releMake = app()->make(RelevanceRepository::class);
        Db::transaction(function() use($params, $tem, $cate,$merId,$paramMake,$releMake) {
            $temp = $this->dao->create($tem);
            $paramMake->createOrUpdate($temp->template_id, $merId, $params);
            if (!empty($cate)) $releMake->createMany($temp->template_id, $cate, RelevanceRepository::PRODUCT_PARAMES_CATE);
        });
    }

    public function update($id, $data, $merId = 0)
    {
        $params = $data['params'];
        $cate = array_unique($data['cate_ids']);
        $tem = [
            'template_name' => $data['template_name'],
            'sort' => $data['sort'],
        ];

        $paramMake = app()->make(ParameterRepository::class);
        $releMake = app()->make(RelevanceRepository::class);
        Db::transaction(function() use($id, $params, $tem, $cate,$paramMake,$releMake,$merId) {
            $this->dao->update($id,$tem);
            $paramMake->diffDelete($id, $params);
            $paramMake->createOrUpdate($id, $merId, $params);
            $releMake->batchDelete($id,RelevanceRepository::PRODUCT_PARAMES_CATE);
            if (!empty($cate)) $releMake->createMany($id, $cate, RelevanceRepository::PRODUCT_PARAMES_CATE);;
        });
    }

    public function delete($id)
    {
        $paramMake = app()->make(ParameterRepository::class);
        $releMake = app()->make(RelevanceRepository::class);
        Db::transaction(function() use($id, $paramMake,$releMake) {
            $this->dao->delete($id);
            $paramMake->getSearch(['template_id' => $id])->delete();
            $releMake->batchDelete($id,RelevanceRepository::PRODUCT_PARAMES_CATE);
        });
    }

}

