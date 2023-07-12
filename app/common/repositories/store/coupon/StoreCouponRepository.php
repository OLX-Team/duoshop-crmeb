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


namespace app\common\repositories\store\coupon;


use app\common\dao\store\coupon\StoreCouponDao;
use app\common\dao\store\coupon\StoreCouponProductDao;
use app\common\model\store\coupon\StoreCoupon;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\merchant\MerchantCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\merchant\MerchantTypeRepository;
use app\common\repositories\user\UserRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;

/**
 * Class StoreCouponIssueRepository
 * @package app\common\repositories\store\coupon
 * @author xaboy
 * @day 2020-05-13
 * @mixin StoreCouponDao
 */
class StoreCouponRepository extends BaseRepository
{

    //店铺券
    const TYPE_STORE_ALL = 0;
    //店铺商品券
    const TYPE_STORE_PRODUCT = 1;
    //平台券
    const TYPE_PLATFORM_ALL = 10;
    //平台分类券
    const TYPE_PLATFORM_CATE = 11;
    //平台跨店券
    const TYPE_PLATFORM_STORE = 12;

    //获取方式
    const GET_COUPON_TYPE_RECEIVE = 0;
    //消费满赠
    const GET_COUPON_TYPE_PAY_MEET = 1;
    //新人券
    const GET_COUPON_TYPE_NEW = 2;
    //买赠
    const GET_COUPON_TYPE_PAY = 3;
    //首单赠送
    const GET_COUPON_TYPE_FIRST = 4;
    //会员券
    const GET_COUPON_TYPE_SVIP = 5;

    /**
     * @var StoreCouponDao
     */
    protected $dao;

    /**
     * StoreCouponIssueRepository constructor.
     * @param StoreCouponDao $dao
     */
    public function __construct(StoreCouponDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param int $merId
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-14
     */
    public function getList(?int $merId, array $where, $page, $limit)
    {
        $baseQuery = $this->dao->search($merId, $where)->with(['merchant' => function ($query) {
            $query->field('mer_id,mer_name,is_trader');
        }]);
        $count = $baseQuery->count($this->dao->getPk());
        $list = $baseQuery->page($page, $limit)->select();
        foreach ($list as $item) {
            $item->append(['used_num', 'send_num']);
        }
        return compact('count', 'list');
    }

    public function sysLst(array $where, int $page, int $limit)
    {
        $baseQuery = $this->dao->search(0, $where);
        $count = $baseQuery->count($this->dao->getPk());
        $list = $baseQuery->page($page, $limit)->select();
        foreach ($list as $item) {
            $item->append(['used_num', 'send_num']);
        }
        return compact('count', 'list');
    }
    public function sviplist(array $where,$uid)
    {
        $with = [];
        if ($uid) $with['svipIssue'] = function ($query) use ($uid) {
            $query->where('uid', $uid);
        };
        $list  = $this->validCouponQueryWithMerchant($where, $uid)->with($with)->setOption('field',[])->field('C.*')->select();
        return $list;
    }

    public function apiList(array $where, int $page, int $limit, $uid)
    {
        $with = [
            'merchant' => function ($query) {
                $query->field('mer_id,mer_name,is_trader');
            },
        ];
        if ($uid) $with['issue'] = function ($query) use ($uid) {
            $query->where('uid', $uid);
        };
        $baseQuery = $this->validCouponQueryWithMerchant($where, $uid)
            ->with($with);
        $count = $baseQuery->count($this->dao->getPk());
        $list = $baseQuery->setOption('field',[])->field('C.*')->page($page, $limit)->select()->append(['ProductLst']);
        $arr = [];
        if ($where['is_pc']) {
            foreach ($list as $item) {
                if ($item['ProductLst']) {
                    $arr[] = $item;
                }
                if (count($arr) >= 3) break;
            }
            $list = $arr ?? $list ;
        }

        return compact('count', 'list');
    }
    /**
     * @param array $data
     * @author xaboy
     * @day 2020/5/26
     */
    public function create1(array $data)
    {
        if (isset($data['total_count'])) $data['remain_count'] = $data['total_count'];
        Db::transaction(function () use ($data) {
            $products = array_column((array)$data['product_id'], 'id');
            unset($data['product_id']);
            if ($data['type'] == 1 && !count($products))
                throw new ValidateException('请选择产品');
            $coupon = $this->dao->create($data);
            if (!count($products)) return $coupon;
            $lst = [];
            foreach ($products as $product) {
                $lst[] = [
                    'product_id' => (int)$product,
                    'coupon_id' => $coupon->coupon_id
                ];
            }
            app()->make(StoreCouponProductDao::class)->insertAll($lst);
        });
    }

    /**
     * @param $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/5/26
     */
    public function cloneCouponForm($id)
    {
        $couponInfo = $this->dao->getWith($id, ['product'])->toArray();
        if ($couponInfo['is_timeout']) {
            $couponInfo['range_date'] = [$couponInfo['start_time'], $couponInfo['end_time']];
        }
        if ($couponInfo['coupon_type']) {
            $couponInfo['use_start_time'] = [$couponInfo['use_start_time'], $couponInfo['use_end_time']];
        }
        $couponInfo['product_id'] = [];
        if (count($couponInfo['product'])) {
            $productIds = array_column($couponInfo['product'], 'product_id');
            /** @var ProductRepository $make */
            $make = app()->make(ProductRepository::class);
            $products = $make->productIdByImage($couponInfo['mer_id'], $productIds);
            foreach ($products as $product) {
                $couponInfo['product_id'][] = ['id' => $product['product_id'], 'src' => $product['image']];
            }
        }
        $couponInfo['use_type'] = $couponInfo['use_min_price'] > 0 ? 1 : 0;
        return $this->form()->formData($couponInfo)->setTitle('复制优惠券');
    }

    /**
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020/5/20
     */
    public function form()
    {
        return Elm::createForm(Route::buildUrl('merchantCouponCreate')->build(), [
            Elm::input('title', '优惠券名称')->required(),
            Elm::radio('type', '优惠券类型', 0)
                ->setOptions([
                    ['value' => 0, 'label' => '店铺券'],
                    ['value' => 1, 'label' => '商品券'],
                ])->control([
                    [
                        'value' => 1,
                        'rule' => [
                            Elm::frameImages('product_id', '商品', '/' . config('admin.merchant_prefix') . '/setting/storeProduct?field=product_id')
                                ->width('680px')->height('480px')->modal(['modal' => false])->prop('srcKey', 'src')->required(),
                        ]
                    ],
                ]),
            Elm::number('coupon_price', '优惠券面值')->min(0)->precision(1)->required(),
            Elm::radio('use_type', ' 使用门槛', 0)
                ->setOptions([
                    ['value' => 0, 'label' => '无门槛'],
                    ['value' => 1, 'label' => '有门槛'],
                ])->appendControl(0, [
                    Elm::hidden('use_min_price', 0)
                ])->appendControl(1, [
                    Elm::number('use_min_price', '优惠券最低消费')->min(0)->required(),
                ]),
            Elm::radio('coupon_type', '使用有效期', 0)
                ->setOptions([
                    ['value' => 0, 'label' => '天数'],
                    ['value' => 1, 'label' => '时间段'],
                ])->control([
                    [
                        'value' => 0,
                        'rule' => [
                            Elm::number('coupon_time', ' ', 0)->min(0)->required(),
                        ]
                    ],
                    [
                        'value' => 1,
                        'rule' => [
                            Elm::dateTimeRange('use_start_time', ' ')->required(),
                        ]
                    ],
                ]),
            Elm::radio('is_timeout', '领取时间', 0)->options([['label' => '限时', 'value' => 1], ['label' => '不限时', 'value' => 0]])
                ->appendControl(1, [Elm::dateTimeRange('range_date', ' ')->placeholder('不填为永久有效')]),
            Elm::radio('send_type', '获取方式', 0)->setOptions([
                ['value' => 0, 'label' => '领取'],
//                ['value' => 1, 'label' => '消费满赠'],
                ['value' => 2, 'label' => '新人券'],
                ['value' => 3, 'label' => '赠送券']
            ])->appendControl(1, [Elm::number('full_reduction', '满赠金额', 0)->min(0)->placeholder('赠送优惠券的最低消费金额')]),
            Elm::radio('is_limited', '是否限量', 0)->options([['label' => '限量', 'value' => 1], ['label' => '不限量', 'value' => 0]])
                ->appendControl(1, [Elm::number('total_count', '发布数量', 0)->min(0)]),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::radio('status', '状态', 1)->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]]),
        ])->setTitle('发布优惠券');
    }

    public function receiveCoupon($id, $uid)
    {
        $coupon = $this->dao->validCoupon($id, $uid);
        if (!$coupon)
            throw new ValidateException('优惠券失效');

        if (!is_null($coupon['issue']))
            throw new ValidateException('优惠券已领取');
        $this->sendCoupon($coupon, $uid,StoreCouponUserRepository::SEND_TYPE_RECEIVE);
    }

    public function receiveSvipCounpon($id,$uid)
    {
        $coupon = $this->dao->validSvipCoupon($id, $uid);
        if (!$coupon)
            throw new ValidateException('优惠券失效');

        if (!is_null($coupon['svipIssue']))
            throw new ValidateException('优惠券已领取');
        $this->sendCoupon($coupon, $uid,StoreCouponUserRepository::SEND_TYPE_RECEIVE);
    }

    public function sendCoupon(StoreCoupon $coupon, $uid, $type)
    {
        event('user.coupon.send.before', compact('coupon', 'uid', 'type'));
        Db::transaction(function () use ($uid, $type, $coupon) {
            $this->preSendCoupon($coupon, $uid, $type);
            app()->make(StoreCouponIssueUserRepository::class)->issue($coupon['coupon_id'], $uid);
            if ($coupon->is_limited) {
                $coupon->remain_count--;
                $coupon->save();
            }
        });
        event('user.coupon.send', compact('coupon', 'uid', 'type'));
        event('user.coupon.send.' . $type, compact('coupon', 'uid', 'type'));
    }

    public function preSendCoupon(StoreCoupon $coupon, $uid, $type = 'send')
    {
        $data = $this->createData($coupon, $uid, $type);
        return app()->make(StoreCouponUserRepository::class)->create($data);
    }

    public function createData(StoreCoupon $coupon, $uid, $type = 'send')
    {
        $data = [
            'uid' => $uid,
            'coupon_title' => $coupon['title'],
            'coupon_price' => $coupon['coupon_price'],
            'use_min_price' => $coupon['use_min_price'],
            'type' => $type,
            'coupon_id' => $coupon['coupon_id'],
            'mer_id' => $coupon['mer_id']
        ];
        if ($coupon['send_type'] == self::GET_COUPON_TYPE_SVIP) {
            $data['start_time'] = date('Y-m-d 00:00:00',time());
            $firstday = date('Y-m-01', time());
            $data['end_time'] = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));
        } else {
            if ($coupon['coupon_type'] == 1) {
                $data['start_time'] = $coupon['use_start_time'];
                $data['end_time'] = $coupon['use_end_time'];
            } else {
                $data['start_time'] = date('Y-m-d H:i:s');
                $data['end_time'] = date('Y-m-d H:i:s', strtotime("+ {$coupon['coupon_time']}day"));
            }
        }
        return $data;
    }

    /**
     * TODO 优惠券发送费多用户
     * @param $uid
     * @param $id
     * @author Qinii
     * @day 2020-06-19
     */
    public function sendCouponByUser($uid, $id)
    {
        foreach ($uid as $item) {
            $coupon = $this->dao->validCoupon($id, $item);
            if (!$coupon || !is_null($coupon['issue']))
                continue;
            if ($coupon->is_limited && 0 == $coupon->remain_count)
                continue;
            $this->sendCoupon($coupon, $item,StoreCouponUserRepository::SEND_TYPE_RECEIVE);
        }
    }

    public function updateForm(int $merId, int $id)
    {
        $data = $this->dao->getWhere(['mer_id' => $merId, 'coupon_id' => $id]);
        if (!$data) throw new ValidateException('数据不存在');

        $form = Elm::createForm(Route::buildUrl('systemCouponUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::input('title', '优惠券名称', $data['title'])->required(),
        ]);
        return $form->setTitle('编辑优惠券名称');
    }


    public function sysForm()
    {
        return Elm::createForm(Route::buildUrl('systemCouponCreate')->build(), [
            Elm::input('title', '优惠券名称')->required(),
            Elm::radio('type', '优惠券类型', 10)
                ->setOptions([
                    ['value' => self::TYPE_PLATFORM_ALL, 'label' => '通用券'],
                    ['value' => self::TYPE_PLATFORM_CATE, 'label' => '品类券'],
                    ['value' => self::TYPE_PLATFORM_STORE, 'label' => '跨店券'],
                ])->control([
                    [
                        'value' => self::TYPE_PLATFORM_CATE,
                        'rule' => [
                            Elm::cascader('cate_ids', '选择品类')->options(function (){
                                return app()->make(StoreCategoryRepository::class)->getTreeList(0, 1);
                            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false, 'multiple' => true]])
                        ]
                    ],
                    [
                        'value' => self::TYPE_PLATFORM_STORE,
                        'rule' => [
                            Elm::radio('mer_type', '选择商户',2)
                                ->setOptions([
                                    ['value' => 1, 'label' => '分类筛选'],
                                    ['value' => 2, 'label' => '指定店铺'],
                                ])->control([
                                    [
                                        'value' => 1,
                                        'rule' => [
                                            Elm::select('is_trader', '商户类别')->options([
                                                ['value' => '', 'label' => '全部'],
                                                ['value' => 1, 'label' => '自营'],
                                                ['value' => 0, 'label' => '非自营'],
                                            ]),
                                            Elm::select('category_id', '商户分类')->options(function (){
                                                $options = app()->make(MerchantCategoryRepository::class)->allOptions();
                                                $options = array_merge([['value' => '', 'label' => '全部']],$options);
                                                return $options;
                                            }),
                                            Elm::select('type_id', '店铺类型')->options(function (){
                                                $options = app()->make(MerchantTypeRepository::class)->getOptions();
                                                return array_merge([['value' => '', 'label' => '全部']],$options);
                                            })
                                        ]
                                    ],
                                    [
                                        'value' => 2,
                                        'rule' => [
                                            Elm::frameImages('mer_ids', '选择商户', '/' . config('admin.admin_prefix') . '/setting/crossStore?field=mer_ids')
                                                ->width('680px')
                                                ->height('480px')
                                                ->modal(['modal' => false])
                                                ->prop('srcKey', 'src')
                                                ->required(),
                                        ]
                                    ],
                                ]),


                        ]
                    ],
                ]),
            Elm::number('coupon_price', '优惠券面值')->min(0)->precision(1)->required(),
            Elm::radio('use_type', ' 使用门槛', 0)
                ->setOptions([
                    ['value' => 0, 'label' => '无门槛'],
                    ['value' => 1, 'label' => '有门槛'],
                ])->appendControl(0, [
                    Elm::hidden('use_min_price', 0)
                ])->appendControl(1, [
                    Elm::number('use_min_price', '优惠券最低消费')->min(0)->required(),
                ]),

            Elm::radio('send_type', '获取方式', 0)->setOptions([
                ['value' => self::GET_COUPON_TYPE_RECEIVE, 'label' => '领取'],
                ['value' => self::GET_COUPON_TYPE_NEW, 'label' => '新人券'],
                ['value' => self::GET_COUPON_TYPE_SVIP, 'label' => '付费会员券'],
            ])->control([
                [
                    'value' => self::GET_COUPON_TYPE_RECEIVE,
                    'rule' => [
                        Elm::radio('coupon_type', '使用有效期', 0)
                            ->setOptions([
                                ['value' => 0, 'label' => '天数'],
                                ['value' => 1, 'label' => '时间段'],
                            ])->control([
                                [
                                    'value' => 0,
                                    'rule' => [
                                        Elm::number('coupon_time', ' ', 0)->min(0)->required(),
                                    ]
                                ],
                                [
                                    'value' => 1,
                                    'rule' => [
                                        Elm::dateTimeRange('use_start_time', ' ')->required(),
                                    ]
                                ],
                            ]),
                        Elm::radio('is_timeout', '领取时间', 0)->options([['label' => '限时', 'value' => 1], ['label' => '不限时', 'value' => 0]])
                            ->appendControl(1, [Elm::dateTimeRange('range_date', ' ')->placeholder('不填为永久有效')]),
                    ]
                ],
                [
                    'value' => self::GET_COUPON_TYPE_NEW,
                    'rule' => [
                        Elm::radio('coupon_type', '使用有效期', 0)
                            ->setOptions([
                                ['value' => 0, 'label' => '天数'],
                                ['value' => 1, 'label' => '时间段'],
                            ])->control([
                                [
                                    'value' => 0,
                                    'rule' => [
                                        Elm::number('coupon_time', ' ', 0)->min(0)->required(),
                                    ]
                                ],
                                [
                                    'value' => 1,
                                    'rule' => [
                                        Elm::dateTimeRange('use_start_time', ' ')->required(),
                                    ]
                                ],
                            ]),
                        Elm::radio('is_timeout', '领取时间', 0)->options([['label' => '限时', 'value' => 1], ['label' => '不限时', 'value' => 0]])
                            ->appendControl(1, [Elm::dateTimeRange('range_date', ' ')->placeholder('不填为永久有效')]),
                    ]
                ],
            ])->appendControl(1, [
                Elm::number('full_reduction', '满赠金额', 0)->min(0)->placeholder('赠送优惠券的最低消费金额')
            ])->appendRule('suffix', [
                'type' => 'div',
                'style' => ['color' => '#999999'],
                'domProps' => [
                    'innerHTML' =>'会员优惠券创建成功后会自动发送给创建时间之后的新付费会员；之后每月1日零点自动发送给所有付费会员；在创建优惠券之前已成为付费会员的用户可在会员中心手动领取优惠券',
                ]
            ]),
            Elm::radio('is_limited', '是否限量', 0)->options([['label' => '限量', 'value' => 1], ['label' => '不限量', 'value' => 0]])
                ->appendControl(1, [Elm::number('total_count', '发布数量', 0)->min(0)]),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::radio('status', '状态', 1)->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]]),
        ])->setTitle('发布优惠券');
    }


    public function create($data)
    {
        if (isset($data['total_count'])) $data['remain_count'] = $data['total_count'];
        $productType = 0;
        $products = [];
        switch ($data['type']) {
            case 1: //商品
                $products = array_column((array)$data['product_id'], 'id');
                unset($data['product_id']);
                if (!count($products)) throw new ValidateException('请选择产品');
                break;
            case 11: //商品分类
                $products = $data['cate_ids'];
                unset($data['cate_ids']);
                if (!count($products)) throw new ValidateException('请选择产品分类');
                $productType = 1;
                break;
            case 12: //商户
                if ($data['mer_type'] == 1) {
                    $where = [
                        'type_id' => $data['type_id'],
                        'is_trader' => $data['is_trader'],
                        'category_id' => $data['category_id'],
                    ];
                    $products = app()->make(MerchantRepository::class)->search($where)->column('mer_id');
                    if (!count($products)) throw new ValidateException('选择条件下无商户');
                } else {
                    $products = array_column((array)$data['mer_ids'], 'id');
                    if (!count($products)) throw new ValidateException('请选择商户');
                }
                $productType = 2;
                break;
        }
        unset(
            $data['product_id'],
            $data['cate_ids'],
            $data['is_trader'],
            $data['mer_type'],
            $data['category_id'],
            $data['mer_ids']
        );
        Db::transaction(function () use ($data, $products, $productType) {
            $coupon = $this->dao->create($data);
            if (!count($products)) return $coupon;
            $lst = [];
            foreach ($products as $product) {
                $lst[] = [
                    'product_id'=> (int)$product,
                    'coupon_id' => $coupon->coupon_id,
                ];
            }
            app()->make(StoreCouponProductDao::class)->insertAll($lst);
        });
    }


    public function cloneSysCouponForm($id)
    {
        $couponInfo = $this->dao->getWith($id, ['product'])->toArray();
        if ($couponInfo['is_timeout']) {
            $couponInfo['range_date'] = [$couponInfo['start_time'], $couponInfo['end_time']];
        }
        if ($couponInfo['coupon_type']) {
            $couponInfo['use_start_time'] = [$couponInfo['use_start_time'], $couponInfo['use_end_time']];
        }
        $couponInfo['product_id'] = [];
        if (count($couponInfo['product'])) {
            if ($couponInfo['type'] == 11) {
                foreach ($couponInfo['product'] as $product) {
                    $couponInfo['cate_ids'][] = $product['product_id'];
                }
            } else {
                $productIds = array_column($couponInfo['product'], 'product_id');
                $make = app()->make(MerchantRepository::class);
                $products = $make->merIdByImage($productIds);
                foreach ($products as $product) {
                    $couponInfo['mer_ids'][] = ['id' => $product['mer_id'], 'src' => $product['mer_avatar']];
                }
            }
        }
        $couponInfo['use_type'] = $couponInfo['use_min_price'] > 0 ? 1 : 0;
        unset($couponInfo['product']);
        return $this->sysForm()->formData($couponInfo)->setTitle('复制优惠券');
    }

    public function getProductList(int $id,int $page,int $limit)
    {
        $res = $this->dao->get($id);
        $productRepository = app()->make(StoreCouponProductRepository::class);
        $ids = $productRepository->search(['coupon_id' => $id])->column('product_id');
        $count = 0;
        $list = [];
        switch ($res['type']) {
            case 11: //品类
                if (empty($ids)) throw new ValidateException('品类信息不存在');
                $storeCategoryRepository = app()->make(StoreCategoryRepository::class);
                $cateId = $storeCategoryRepository->selectChildrenId($ids);
                $cateId = array_merge($cateId, $ids);
                $query = app()->make(ProductRepository::class)->getSearch([])->whereIn('cate_id', $cateId);
                $field = 'product_id,store_name,image,stock,price,sales,cate_id';
                $count = $query->count();
                $list = $query->page($page, $limit)->setOption('field',[])->field($field)->select();
                break;
            case 12: //商户
                if (empty($ids)) throw new ValidateException('商户信息不存在');
                $make = app()->make(MerchantRepository::class);
                $field = 'mer_id,category_id,type_id,mer_name,mer_phone,is_trader';
                $with = ['merchantType','merchantCategory'];
                $query = $make->search([])->whereIn($make->getPk(),$ids)->with($with);
                $count = $query->count();
                $list = $query->page($page, $limit)->setOption('field',[])->field($field)->select();
                break;
            default :
                break;
        }
        return compact('count', 'list');
    }

    public function sendSvipCoupon()
    {
        $data = ['mark' => [], 'is_all'=> '', 'search' => '',];
        $uids = app()->make(UserRepository::class)->search(['is_svip' => 1])->column('uid');
        $isMake = app()->make(StoreCouponIssueUserRepository::class);
        $senMake = app()->make(StoreCouponSendRepository::class);
        $couponIds = $this->dao->validCouponQuery(null,StoreCouponRepository::GET_COUPON_TYPE_SVIP)->column('coupon_id');
        if ($couponIds && $uids) {
            foreach ($couponIds as $item) {
                $issUids = $isMake->getSearch([])->whereMonth('create_time')->whereIn('uid',$uids)->column('uid');
                $uids_ = array_values(array_diff($uids,$issUids));
                $data['coupon_id'] = $item;
                $data['uid'] = $uids_;
                if (!empty($data['uid'])) return $senMake->create($data,0);
            }
        }

    }
}
