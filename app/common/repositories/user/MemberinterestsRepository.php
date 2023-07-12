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


namespace app\common\repositories\user;


use app\common\dao\user\MemberInterestsDao;
use app\common\dao\user\UserBrokerageDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

/**
 * @mixin UserBrokerageDao
 */
class MemberinterestsRepository extends BaseRepository
{

    const TYPE_FREE = 1;
    //付费会员
    const TYPE_SVIP = 2;

    const HAS_TYPE_PRICE = 1;

    const HAS_TYPE_SIGN = 2;

    const HAS_TYPE_PAY = 3;

    const HAS_TYPE_SERVICE = 4;

    const HAS_TYPE_MEMBER = 5;

    const HAS_TYPE_COUPON = 6;

    //签到收益
    const INTERESTS_TYPE = [
        1 => ['label'=> '会员特价', 'msg' => ''],
        2 => ['label'=> '签到返利' , 'msg' => '积分倍数' ],
        3 => ['label'=> '消费返利' , 'msg' => '积分倍数' ],
        4 => ['label'=> '专属客服' , 'msg' => '' ],
        5 => ['label'=> '经验翻倍' , 'msg' => '经验翻倍' ],
        6 => ['label'=> '会员优惠券', 'msg' => ''],
    ];

    public function __construct(MemberInterestsDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    public function getSvipInterestVal($has_type)
    {
        return max(((float)$this->dao->query(['status' => 1])->where('has_type', $has_type)->where('type', 2)->value('value')) ?: 0, 0);
    }

    public function form(?int $id = null, $type = self::TYPE_FREE)
    {
        $formData = [];
        if ($id) {
            $data = $this->dao->get($id);
            if (!$data) throw new ValidateException('数据不存在');
            $form = Elm::createForm(Route::buildUrl('systemUserMemberInterestsUpdate', ['id' => $id])->build());
            $formData = $data->toArray();
        } else {
            $form = Elm::createForm(Route::buildUrl('systemUserMemberInterestsCreate')->build());
        }
        $rules = [
            Elm::input('name', '权益名称')->required(),
            Elm::input('info', '权益简介')->required(),
            Elm::frameImage('pic', '图标', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=pic&type=1')
                ->value($formData['pic'] ?? '')
                ->modal(['modal' => false])
                ->width('896px')
                ->height('480px'),
            Elm::select('brokerage_level', '会员级别')->options(function () use($type){
                $options = app()->make(UserBrokerageRepository::class)->options(['type' => $type])->toArray();
                return $options;
            }),
        ];
        $form->setRule($rules);
        return $form->setTitle(is_null($id) ? '添加权益' : '编辑权益')->formData($formData);
    }

    public function getInterestsByLevel(int $type, $level = 0)
    {
        if ($type == self::TYPE_FREE) {
            $list = $this->dao->getSearch(['type' => $type])->select();
            foreach ($list as $item) {
                $item['status'] = 0;
                if ($item['brokerage_level'] <= $level) {
                    $item['status'] = 1;
                }
            }
        } else {
            $list = $this->dao->getSearch(['type' => $type,'status' => 1])->select();
        }
        return $list;
    }

    public function svipForm(int $id)
    {
        $data = $this->dao->get($id);
        if (!$data) throw new ValidateException('数据不存在');
        $form = Elm::createForm(Route::buildUrl('systemUserSvipInterestsUpdate', ['id' => $id])->build());
        $formData = $data->toArray();
        $rules = [
            Elm::select('has_type', '权益名称')->options(function(){
                foreach (self::INTERESTS_TYPE as $k => $v) {
                    $res[] = ['value' => $k, 'label' => $v['label']];
                }
                return $res;
            })->disabled(true),
            Elm::input('name', '展示名称')->required(),
            Elm::input('info', '权益简介')->required(),
            Elm::frameImage('pic', '未开通图标', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=pic&type=1')
                ->value($formData['pic'] ?? '')->required()
                ->modal(['modal' => false])
                ->width('896px')
                ->height('480px'),
            Elm::frameImage('on_pic', '已开通图标', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=on_pic&type=1')
                ->value($formData['on_pic'] ?? '')->required()
                ->modal(['modal' => false])
                ->width('896px')
                ->height('480px'),
            Elm::input('link', '跳转内部链接'),
        ];
        $msg = self::INTERESTS_TYPE[$formData['has_type']]['msg'];
        if ($msg) $rules[] = Elm::number('value',$msg,0);
        $form->setRule($rules);
        return $form->setTitle('编辑会员权益')->formData($formData);
    }
}
