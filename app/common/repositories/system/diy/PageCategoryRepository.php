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

use app\common\dao\system\diy\PageCategoryDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class PageCategoryRepository extends BaseRepository
{
    public function __construct(PageCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    public function getFormatList($where)
    {
        return formatCategory($this->dao->getSearch($where)->order('sort DESC,add_time DESC')->select()->toArray(), $this->dao->getPk());
    }

    public function getList(array $where, int $page, int $limit)
    {

        $query = $this->dao->getSearch($where)->order('add_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    public function form(?int $id, int $isMer = 0)
    {
        if ($id) {
            $formData = $this->dao->get($id)->toArray();
            if ($formData['type'] != 'link') throw new ValidateException('此类型不能修改');
            $form = Elm::createForm(Route::buildUrl($isMer ? 'systemDiyPageMerCategroyUpdate' : 'systemDiyPageCategroyUpdate', ['id' => $id])->build());
            $isMer = $formData['is_mer'];
        } else {
            $form = Elm::createForm(Route::buildUrl($isMer ? 'systemDiyPageMerCategroyCreate' : 'systemDiyPageCategroyCreate')->build());
            $formData = [];
        }

        $form->setRule([
            Elm::cascader('pid', '上级分类')->options(function () use($isMer) {
                $options = $this->dao->getSearch(['status' => 1,'is_mer' => $isMer,'type' => 'link'])->where('level','<',3)->column('pid,name','id');
                $options = formatCascaderData($options, 'name');
                return $options;
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]])->filterable(true)->appendValidate(Elm::validateInt()->required()->message('请选择上级分类')),
            Elm::input('name', '分类名称')->required(),
            Elm::hidden('type','类型','link'),
            Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::hidden('is_mer',$isMer),
        ]);
        return $form->setTitle(is_null($id) ? '添加' .  ($isMer ? '商户分类' : '平台分类') :  '编辑' . ($isMer ? '商户分类' : '平台分类'))->formData($formData);
    }

    public function getSonCategoryList($type,$pid = 0)
    {
        $where['pid'] = $pid;
        $where['is_mer'] = $type;
        $list = $this->dao->getSearch($where)->where('level','<',3)
            ->field( 'id,pid,type,name label')->order('sort DESC,add_time DESC')->select();
        $arr = [];
        if ($list) {
            foreach ($list as $item) {
                $item['title'] = $item['label'];
                $item['expand'] = true;
                $item['children'] = $this->getSonCategoryList($type,$item['id']);
                $arr [] = $item;
            }
        }
        return $arr;
    }

}
