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

use app\common\dao\system\diy\PageLinkDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class PageLinkRepository extends BaseRepository
{
    public function __construct(PageLinkDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->with(['category'])->order('add_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    public function form(?int $id, $isMer)
    {
        if ($id) {
            $formData = $this->dao->get($id)->toArray();

            $form = Elm::createForm(Route::buildUrl($isMer ? 'systemDiyPageLinkMerUpdate' : 'systemDiyPageLinkUpdate', ['id' => $id])->build());
        } else {
            $form = Elm::createForm(Route::buildUrl($isMer ? 'systemDiyPageLinkMerCreate': 'systemDiyPageLinkCreate')->build());
            $formData = [];
        }

        $rule = [
            Elm::cascader('cate_id', '上级分类')->options(function () use($isMer) {
                $options = app()->make(PageCategoryRepository::class)->getSearch(['status' => 1,'is_mer' => $isMer,'type' => 'link','level' => 3])->column('id value, name label');
                return $options;

            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])
                ->filterable(true)
                ->appendValidate(Elm::validateInt()->required()->message('请选择上级分类')),
            Elm::input('name', '页面名称')->required(),
            Elm::input('url', '页面链接')->required(),
            Elm::text('param', '参数'),

            Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
        ];

        $form->setRule($rule);
        return $form->setTitle(is_null($id) ? '添加分类' : '编辑分类')->formData($formData);
    }

    /**
     * TODO 分类下的链接列表
     * @param int $pid
     * @param int $merId
     * @return mixed
     * @author Qinii
     * @day 3/24/22
     */
    public function getLinkList(int  $pid,int $merId)
    {
        $where['pid'] = $pid;
        $where['is_mer'] = $merId ? 1 : 0;
        $make = app()->make(PageCategoryRepository::class);
        $list  = $make->getSearch($where)->with([
            'pageLink',
        ])->select();
        return $list;
    }
}
