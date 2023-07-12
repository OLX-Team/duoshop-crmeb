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

use app\common\dao\community\CommunityTopicDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class CommunityTopicRepository extends BaseRepository
{
    /**
     * @var CommunityTopicDao
     */
    protected $dao;

    /**
     * CommunityTopicRepository constructor.
     * @param CommunityTopicDao $dao
     */
    public function __construct(CommunityTopicDao $dao)
    {
        $this->dao = $dao;
    }

    public function form(?int $id)
    {
        $formData = [];
        if (!$id) {
            $form = Elm::createForm(Route::buildUrl('systemCommunityTopicCreate')->build());
        } else {
            $formData = $this->dao->get($id)->toArray();
            $form = Elm::createForm(Route::buildUrl('systemCommunityTopicUpdate', ['id' => $id])->build());
        }

        $form->setRule([
            Elm::select('category_id', '社区分类')->options(function () {
                return app()->make(CommunityCategoryRepository::class)->options();
            })->requiredNum(),

            Elm::frameImage('pic', '图标', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=pic&type=1')
                ->modal(['modal' => false])
                ->width('896px')
                ->height('480px'),
            Elm::input('topic_name', '社区话题')->required(),
            Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::switches('is_hot', '是否推荐', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序')->precision(0)->max(99999),
        ]);
        return $form->setTitle(is_null($id) ? '添加话题' : '编辑话题')->formData($formData);
    }

    public function delete(int $id)
    {
        $make = app()->make(CommunityRepository::class);
        if ( $make->getWhereCount(CommunityRepository::IS_SHOW_WHERE) ) throw new ValidateException('该话题下存在数据');
        $this->dao->delete($id);
    }

    public function getList(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        $query = $this->dao->getSearch($where)->with(['category'])
            ->order('sort DESC,create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count','list');
    }

    /**
     * TODO 获取推荐的话题
     * @return array
     * @author Qinii
     * @day 10/27/21
     */
    public function getHotList()
    {
        $list = $this->dao->getSearch([
            'is_hot' => 1,
            'status' => 1,
            'is_del' => 0
        ])
            ->setOption('field',[])->field('category_id,topic_name,topic_id,pic,count_view,count_use')
            ->order('create_time DESC')->select();

        return compact('list');
    }

    /**
     * TODO
     * @param int|null $id
     * @author Qinii
     * @day 11/3/21
     */
    public function sumCountUse(?int $id)
    {
        if (!$id) {
            $id = $this->dao->getSearch(['status' => 1,'is_del' =>0])->column('topic_id');
        } else {
            $id = [$id];
        }
        foreach ($id as $item) {
            $count = app()->make(CommunityRepository::class)
                ->getSearch(CommunityRepository::IS_SHOW_WHERE)->where('topic_id',$item)->count();
            $this->dao->update($item, ['count_use' => $count]);
        }
    }

    public function options()
    {
        $data =  $this->dao->getSearch(['status' => 1,'is_del' =>0])->order('sort DESC,create_time DESC')
            ->field('topic_id as value,topic_name as label')->select();
        if ($data) $res = $data->toArray();
        return $res;
    }
}

