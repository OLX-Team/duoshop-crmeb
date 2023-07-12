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

namespace app\common\repositories\community;

use app\common\dao\community\CommunityCategoryDao;
use app\common\repositories\BaseRepository;
use app\controller\api\community\CommunityTopic;

use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class CommunityCategoryRepository extends BaseRepository
{
    /**
     * @var CommunityCategoryDao
     */
    protected $dao;

    /**
     * CommunityCategoryRepository constructor.
     * @param CommunityCategoryDao $dao
     */
    public function __construct(CommunityCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    public function form(?int $id)
    {
        $formData = [];
        if (!$id) {
            $form = Elm::createForm(Route::buildUrl('systemCommunityCategoryCreate')->build());
        } else {
            $formData = $this->dao->get($id)->toArray();
            $form = Elm::createForm(Route::buildUrl('systemCommunityCategoryUpdate', ['id' => $id])->build());
        }

        $form->setRule([
            Elm::input('cate_name', '分类名称')->required(),
            Elm::switches('is_show', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序')->precision(0)->max(99999),
        ]);
        return $form->setTitle(is_null($id) ? '添加分类' : '编辑分类')->formData($formData);
    }

    public function delete(int $id)
    {
        $make = app()->make(CommunityTopicRepository::class);
        if ( $make->getWhereCount(['category_id' => $id]) ) throw new ValidateException('该分类下存在数据');
        $this->dao->delete($id);
    }

    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->order('sort DESC,category_id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count','list');
    }

    public function options()
    {
        $data =  $this->dao->getSearch(['is_show' => 1])->order('sort DESC,category_id DESC')
            ->field('category_id as value,cate_name as label')->select();
        if ($data) $res = $data->toArray();
        return $res;
    }


    public function getApiList()
    {
        $res = $this->dao->getSearch(['is_show' => 1])->order('sort DESC')
            ->setOption('filed',[])->field('category_id,cate_name')->with(['children'])->order('sort DESC,category_id DESC')->select();
        $list = [];
        if ($res) $list = $res->toArray();
//        $hot = app()->make(CommunityTopicRepository::class)->getHotList();
//        $data[] = [
//            'category_id' => 0,
//            "cate_name" => "推荐",
//            "children"  => $hot['list']
//        ];
//        return array_merge($data,$list);
        return $list;
    }

    public function checkName($name, $id)
    {
        $this->dao->fieldExists();
        $this->dao->getSearch(['cate_name' => $name]);
        if ($id) {
            $this->dao->getSearch(['cate_name' => $name])->where('categ');
        }
    }

    public function clearCahe()
    {
        // CacheService::clearByTag(0, CacheService::TAG_TOPIC);
    }
}

