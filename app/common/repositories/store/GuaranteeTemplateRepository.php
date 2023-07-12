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

use app\common\dao\store\GuaranteeTemplateDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;

class GuaranteeTemplateRepository extends BaseRepository
{
    /**
     * @var GuaranteeTemplateDao
     */
    protected $dao;


    /**
     * GuaranteeRepository constructor.
     * @param GuaranteeTemplateDao $dao
     */
    public function __construct(GuaranteeTemplateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 平台列表
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 5/17/21
     */
    public function getList($where,$page, $limit)
    {
        $query = $this->dao->getSearch($where)->with(['template_value.value'])->order('sort DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    /**
     * TODO 创建
     * @param array $data
     * @author Qinii
     * @day 5/17/21
     */
    public function create(array $data)
    {
        Db::transaction(function() use($data){
            $template = [
                'template_name' => $data['template_name'],
                'mer_id' => $data['mer_id'],
                'status' => $data['status'],
                'sort' => $data['sort']
            ];
            $guaranteeData = $this->dao->create($template);
            $make = app()->make(GuaranteeRepository::class);

            foreach ($data['template_value'] as $datum){

                $where = [ 'status' => 1,'is_del' => 0,'guarantee_id' => $datum];
                $ret = $make->getWhere($where);
                if(!$ret) throw new ValidateException('ID['.$datum.']不存在');
                $value[] = [
                    'guarantee_id' => $datum ,
                    'guarantee_template_id' => $guaranteeData->guarantee_template_id,
                    'mer_id' => $data['mer_id']
                ];
            }
            app()->make(GuaranteeValueRepository::class)->insertAll($value);
        });
    }

    /**
     * TODO 编辑
     * @param int $id
     * @param array $data
     * @author Qinii
     * @day 5/17/21
     */
    public function edit(int $id,array $data)
    {
        Db::transaction(function() use($id,$data){
            $template = [
                'template_name' => $data['template_name'],
                'status' => $data['status'],
                'sort' => $data['sort']
            ];
            $make = app()->make(GuaranteeRepository::class);
            $makeValue = app()->make(GuaranteeValueRepository::class);
            foreach ($data['template_value'] as $datum){
                $where = [ 'status' => 1,'is_del' => 0,'guarantee_id' => $datum];
                $ret = $make->getWhere($where);
                if(!$ret) throw new ValidateException('ID['.$datum.']不存在');
                $value[] = [
                    'guarantee_id' => $datum ,
                    'guarantee_template_id' => $id,
                    'mer_id' => $data['mer_id']
                ];
            }
            $this->dao->update($id,$template);
            $makeValue->clear($id);
            $makeValue->insertAll($value);
        });
    }

    /**
     * TODO 详情
     * @param int $id
     * @param int $merId
     * @return array|\think\Model|null
     * @author Qinii
     * @day 5/17/21
     */
    public function detail(int $id,int $merId)
    {
        $where = [
            'mer_id' => $merId,
            'guarantee_template_id' => $id,
        ];
        $ret = $this->dao->getSearch($where)->find();
        $ret->append(['template_value']);
        if(!$ret) throw new ValidateException('数据不存在');
        return $ret;
    }

    public function delete($id)
    {
        $productId = app()->make(ProductRepository::class)->getSearch(['guarantee_template_id' => $id])->column('product_id');
        if($productId) throw new ValidateException('有商品正在使用此模板,商品ID：'.implode(',',$productId));
        Db::transaction(function() use($id){
            $this->dao->delete($id);
            app()->make(GuaranteeValueRepository::class)->clear($id);
        });
    }

    public function list($merId)
    {
        $where = [
            'status' => 1,
            'is_del' => 0,
            'mer_id' => $merId
        ];
        return $this->dao->getSearch($where)->order('sort DESC')->select()->toArray();
    }

}
