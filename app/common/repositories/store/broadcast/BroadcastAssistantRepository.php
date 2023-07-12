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


namespace app\common\repositories\store\broadcast;

use app\common\dao\store\broadcast\BroadcastAssistantDao;
use app\common\repositories\BaseRepository;
use crmeb\services\MiniProgramService;
use FormBuilder\Exception\FormBuilderException;
use think\facade\Route;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;

class BroadcastAssistantRepository extends BaseRepository
{
    /**
     * @var BroadcastAssistantDao
     */
    protected $dao;

    public function __construct(BroadcastAssistantDao $dao)
    {
        $this->dao = $dao;
    }

    public function form(?int $id)
    {
        $formData = [];
        if ($id) {
            $form = Elm::createForm(Route::buildUrl('merchantBroadcastAssistantUpdate', ['id' => $id])->build());
            $data = $this->dao->get($id);
            if (!$data) throw new FormBuilderException('数据不存在');
            $formData = $data->toArray();

        } else {
            $form = Elm::createForm(Route::buildUrl('merchantBroadcastAssistantCreate')->build());
        }

        $rules = [
            Elm::input('username', '微信号')->required(),
            Elm::input('nickname', '微信昵称')->required(),
            Elm::input('mark', '备注'),
        ];
        $form->setRule($rules);
        return $form->setTitle(is_null($id) ? '添加小助手账号' : '编辑小助手账号')->formData($formData);
    }

    public function getList($where, int $page,  int $limit)
    {
        $query = $this->dao->getSearch($where);
        $count =  $query->count('*');
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    public function options(int $merId)
    {
        return $this->dao->getSearch(['mer_id' => $merId])->column('nickname','assistant_id');
    }


}
