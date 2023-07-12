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
namespace app\common\repositories\store\product;

use app\common\dao\store\product\ProductLabelDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class ProductLabelRepository extends BaseRepository
{
    protected $dao;

    public function __construct(ProductLabelDao $dao)
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
     * @day 8/17/21
     */
    public function getList(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        $query = $this->dao->getSearch($where)->order('sort DESC,create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    /**
     * TODO 添加form
     * @param int|null $id
     * @param string $route
     * @param array $formData
     * @return \FormBuilder\Form
     * @author Qinii
     * @day 8/17/21
     */
    public function form(?int $id, string $route, array $formData = [])
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl($route)->build() : Route::buildUrl($route, ['id' => $id])->build());
        $form->setRule([
            Elm::input('label_name', '标签名称')->required(),
            Elm::input('info', '说明'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
        ]);
        return $form->setTitle(is_null($id) ? '添加标签' : '编辑标签')->formData($formData);
    }


    /**
     * TODO 编辑form
     * @param int $id
     * @param string $route
     * @param int $merId
     * @return \FormBuilder\Form
     * @author Qinii
     * @day 8/17/21
     */
    public function updateForm(int $id, string $route, int $merId = 0)
    {
        $data = $this->dao->getWhere(['product_label_id' => $id, 'mer_id' => $merId ]);
        if (!$data) throw new ValidateException('数据不存在');
        return $this->form($id, $route, $data->toArray());
    }

    /**
     * TODO
     * @param int $merId
     * @return array
     * @author Qinii
     * @day 8/18/21
     */
    public function getOptions(int $merId)
    {
        $where = [
            'mer_id' => $merId,
            'status' => 1,
            'is_del' => 0
        ];
        return $this->dao->getSearch($where)->field('product_label_id id,label_name name')->order('sort DESC,create_time DESC')->select()->toArray();
    }

    public function checkHas($merId,$data)
    {
        if (!empty($data)){
            if (!is_array($data)) $data = explode(',', $data);
            foreach ($data as $item) {
                $data = $this->dao->getSearch(['product_label_id'  => $item,'mer_id'  =>  $merId])->find();
                if (!$data) throw new ValidateException( '标签ID：'.$item.'，不存在');
            }
        }
        return true;
    }

    /**
     * TODO 是否重名
     * @param string $name
     * @param int $merId
     * @param null $id
     * @return bool
     * @author Qinii
     * @day 9/6/21
     */
    public function check(string $name, int $merId, $id = null)
    {
        $where['label_name'] = $name;
        $where['mer_id'] = $merId;
        $data = $this->dao->getWhere($where);
        if ($data) {
            if (!$id) return false;
            if ($id != $data['product_label_id']) return false;
        }
        return true;
    }

}
