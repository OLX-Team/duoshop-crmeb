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


namespace app\controller\admin\store;

use app\common\repositories\store\coupon\StoreCouponProductRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponSendRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\validate\merchant\StoreCouponSendValidate;
use app\validate\merchant\StoreCouponValidate;
use crmeb\basic\BaseController;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

/**
 * Class CouponIssue
 * @package app\controller\merchant\store\coupon
 * @author xaboy
 * @day 2020-05-13
 */
class Coupon extends BaseController
{
    /**
     * @var StoreCouponRepository
     */
    protected $repository;

    /**
     * CouponIssue constructor.
     * @param App $app
     * @param StoreCouponRepository $repository
     */
    public function __construct(App $app, StoreCouponRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-14
     */
    public function lst()
    {
        $where = $this->request->params(['is_full_give', 'status', 'is_give_subscribe', 'coupon_name', ['mer_id', null],'is_trader']);
        [$page, $limit] = $this->getPage();
        $where['is_mer'] = 1;
        return app('json')->success($this->repository->getList($where['mer_id'], $where, $page, $limit));
    }

    public function detail($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $coupon = $this->repository->get($id)->append(['used_num', 'send_num']);
        return app('json')->success($coupon->toArray());
    }

    public function  showLst($id)
    {
        [$page, $limit] = $this->getPage();
        $data = $this->repository->getProductList($id, $page, $limit);
        return app('json')->success($data);
    }

    public function product($id)
    {
        $merId = $this->request->merId();
        if ($merId) {
            $exists = app()->make(StoreCouponRepository::class)->merExists($merId, $id);
        } else {
            $exists = app()->make(StoreCouponRepository::class)->exists($id);
        }
        if (!$exists) {
            return app('json')->fail('优惠券不存在');
        }
        [$page, $limit] = $this->getPage();
        return app('json')->success(app()->make(StoreCouponProductRepository::class)->productList((int)$id, $page, $limit));
    }

    /**
     * @param StoreCouponUserRepository $repository
     * @author xaboy
     * @day 2020/6/2
     */
    public function issue(StoreCouponUserRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['username', 'coupon_id', 'coupon','coupon_type', 'status','type','mer_id',['is_mer',1]]);
        return app('json')->success($repository->getList($where, $page, $limit));
    }

    /**
     * TODO 平台领取记录
     * @param StoreCouponUserRepository $repository
     * @return \think\response\Json
     * @author Qinii
     * @day 2/26/22
     */
    public function platformIssue(StoreCouponUserRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['username', 'coupon_id', 'coupon', 'status','coupon_type','type', 'send_id']);
        $where['mer_id'] = 0;
        return app('json')->success($repository->getList($where, $page, $limit));
    }


    public function createForm()
    {
        $data = $this->repository->sysForm();
        return app('json')->success(formToData($data));
    }

    public function create()
    {
        $data = $this->checkParams();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->updateForm(0, $id)));
    }

    public function update($id)
    {
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        $data = $this->request->params(['title']);
        $this->repository->update($id, $data);

        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, compact('status'));
        return app('json')->success('修改成功');
    }

    public function platformLst()
    {
        $where = $this->request->params(['is_full_give', 'status', 'is_give_subscribe', 'coupon_name', 'send_type', 'type']);
        [$page, $limit] = $this->getPage();
        $data = $this->repository->sysLst($where, $page, $limit);
        return app('json')->success($data);
    }


    public function cloneForm($id)
    {
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->cloneSysCouponForm($id)));
    }


    /**
     * @param StoreCouponValidate $validate
     * @return array
     * @author xaboy
     * @day 2020/5/20
     */
    public function checkParams()
    {
        $data = $this->request->params([
            'use_type',
            'title',
            'coupon_price',
            'use_min_price',
            ['coupon_type',0],
            ['coupon_time',1],
            ['use_start_time', []],
            'sort',
            ['status', 0],
            'type',
            ['product_id', []],
            ['range_date', ''],
            ['send_type', 0],
            ['full_reduction', 0],
            ['is_limited', 0],
            ['is_timeout', 0],
            ['total_count', ''],
            ['status', 0],
            ['cate_ids',[]],
            'mer_type',
            'is_trader',
            'category_id',
            'type_id',
            ['mer_ids',[]],
        ]);

        app()->make(StoreCouponValidate::class)->check($data);
        if ($data['send_type'] == $this->repository::GET_COUPON_TYPE_SVIP) {
            $data['coupon_type'] = 0;
            $data['is_timeout'] = 0;
        }
        if ($data['is_timeout']) {
            [$data['start_time'], $data['end_time']] = $data['range_date'];
            if (strtotime($data['end_time']) <= time())
                throw new ValidateException('优惠券领取结束时间不能小于当前');
        }
        if (!$data['use_type']) $data['use_min_price'] = 0;
        unset($data['use_type']);
        if ($data['coupon_type']) {
            if (count(array_filter($data['use_start_time'])) != 2)
                throw new ValidateException('请选择有效期限');
            [$data['use_start_time'], $data['use_end_time']] = $data['use_start_time'];
            if ($data['use_start_time'] > $data['use_end_time']) {
                throw new ValidateException('使用开始时间小于结束时间');
            }
        } else unset($data['use_start_time']);
        unset($data['range_date']);
        if ($data['is_limited'] == 0) $data['total_count'] = 0;
        if (!in_array($data['type'], [10, 11, 12])) {
            throw new ValidateException('请选择有效的优惠券类型');
        }
        return $data;
    }

    public function send(StoreCouponSendValidate $validate, StoreCouponSendRepository $repository)
    {
        $data = $this->request->params(['coupon_id', 'mark', 'is_all', 'search', 'uid']);
        $validate->check($data);
        if (!$data['is_all'] && !count($data['uid'])) {
            return app('json')->fail('请选择发送用户');
        }
        $repository->create($data, 0);
        return app('json')->success('创建成功,正在发送中');
    }

    public function sendLst( StoreCouponSendRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date', 'coupon_type', 'coupon_name', 'status']);
        $where['mer_id'] = 0;
        return app('json')->success($repository->getList($where, $page, $limit));
    }


}
