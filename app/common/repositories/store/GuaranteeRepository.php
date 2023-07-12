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

use app\common\dao\store\GuaranteeDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductRepository;
use FormBuilder\Factory\Elm;
use think\facade\Route;

class GuaranteeRepository extends BaseRepository
{
    /**
     * @var GuaranteeDao
     */
    protected $dao;


    /**
     * GuaranteeRepository constructor.
     * @param GuaranteeDao $dao
     */
    public function __construct(GuaranteeDao $dao)
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
        $query = $this->dao->getSearch($where)->order('sort DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    public function select(array $where)
    {
        $list = $this->dao->getSearch($where)->field('guarantee_id,guarantee_name,guarantee_info,image')->order('sort DESC')->select();
        return $list;
    }
    /**
     * TODO 添加form
     * @param int|null $id
     * @param array $formData
     * @return \FormBuilder\Form
     * @author Qinii
     * @day 5/17/21
     */
    public function form(?int $id,array $formData = [])
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemGuaranteeCreate')->build() : Route::buildUrl('systemGuaranteeUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::input('guarantee_name', '服务条款')->required(),
            Elm::textarea('guarantee_info', '服务内容描述')->autosize([
                'minRows'=>1000,
            ])->required(),
            Elm::frameImage('image', '服务条款图标(100*100px)', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=image&type=1')->value($formData['image']??'')->modal(['modal' => false])->width('896px')->height('480px')->required(),
            Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
        ]);
        return $form->setTitle(is_null($id) ? '添加服务条款' : '编辑服务条款')->formData($formData);
    }

    /**
     * TODO 编辑form
     * @param $id
     * @return \FormBuilder\Form
     * @author Qinii
     * @day 5/17/21
     */
    public function updateForm($id)
    {
        $ret = $this->dao->get($id);
        return $this->form($id,$ret->toArray());
    }

    /**
     * TODO 获取详情
     * @param $id
     * @return array|\think\Model|null
     * @author Qinii
     * @day 5/17/21
     */
    public function get($id)
    {
        $where = [
            $this->dao->getPk() => $id,
            'is_del' => 0,
        ];
        $ret = $this->dao->getWhere($where);
        return $ret;
    }

    public function countGuarantee()
    {
        /**
         * 获取所有条款
         * 计算商户数量
         * 计算商品数量
         */
        $ret = $this->dao->getSearch(['status' => 1,'is_del' => 0])->column($this->dao->getPk());
        $make = app()->make(GuaranteeValueRepository::class);
        $makeProduct = app()->make(ProductRepository::class);
        if($ret){
            foreach ($ret as $k => $v){
                $item = [];
                $item['mer_count'] = $make->getSearch(['guarantee_id' => $v])->group('mer_id')->count('*');
                $template = $make->getSearch(['guarantee_id' => $v])->group('guarantee_template_id')->column('guarantee_template_id');
                $item['product_cout'] = $makeProduct->getSearch(['guarantee_template_id' => $template])->count('*');
                $item['update_time'] = date('Y-m-d H:i:s',time());
                $this->dao->update($v,$item);
            }
        }
        return ;
    }

}
