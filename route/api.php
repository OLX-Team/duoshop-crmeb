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


use app\common\middleware\AllowOriginMiddleware;
use app\common\middleware\CheckSiteOpenMiddleware;
use app\common\middleware\InstallMiddleware;
use app\common\middleware\UserTokenMiddleware;
use app\common\middleware\VisitProductMiddleware;
use app\common\middleware\RequestLockMiddleware;
use think\facade\Route;

Route::group('api/', function () {
    Route::any('test', 'api.Auth/test');
    //强制登录
    Route::group(function () {
        Route::group('v2', function () {
            //新的下单接口,支持分账
            Route::group('order', function () {
                Route::post('check', '/v2CheckOrder');
                Route::post('create', '/v2CreateOrder');
            })->prefix('api.store.order.StoreOrder');
        });

        //退出登录
        Route::post('logout', 'api.Auth/logout');
        //用户信息
        Route::get('user', 'api.Auth/userInfo');

        //绑定推荐人
        Route::post('user/spread', 'api.Auth/spread');

        //优惠券
        Route::group('coupon', function () {
            Route::post('receive/:id', 'api.store.product.StoreCoupon/receiveCoupon');
        });

        //客服聊天
        Route::group('service', function () {
            Route::get('history/:id', 'api.store.service.Service/chatHistory');
            Route::get('list', 'api.store.service.Service/getList');
            Route::get('mer_history/:merId/:id', 'api.store.service.Service/serviceHistory');
            Route::get('user_list/:merId', 'api.store.service.Service/serviceUserList');
            //客服扫码登录
            Route::post('scan_login/:key', 'api.store.service.Service/scanLogin');
            Route::get('user/:merId/:uid', 'api.store.service.Service/user');
            Route::post('mark/:merId/:uid', 'api.store.service.Service/mark');
        });

        //订单
        Route::group('order', function () {
            Route::post('check', '/checkOrder');
            Route::post('create', '/createOrder');
            Route::get('group_order_list', '/groupOrderList');
            Route::get('group_order_detail/:id', '/groupOrderDetail');
            Route::post('cancel/:id', '/cancelGroupOrder');
            Route::get('list', '/lst');
            Route::get('detail/:id', '/detail');
            Route::get('number', '/number');
            Route::post('pay/:id', '/groupOrderPay');
            Route::post('points/pay/:id', '/groupOrderPay')->append(['is_points' => 1]);
            Route::post('take/:id', '/take');
            Route::post('express/:id', '/express');
            Route::post('del/:id', '/del');
            Route::get('status/:id', '/groupOrderStatus');
            Route::get('verify_code/:id', '/verifyCode');
            Route::post('receipt/:id', '/createReceipt');
            Route::get('delivery/:id', '/getOrderDelivery');
        })->prefix('api.store.order.StoreOrder');

        // 预售
        Route::group('presell', function () {
            Route::post('pay/:id', '/pay');
        })->prefix('api.store.order.PresellOrder');

        //退款单
        Route::group('refund', function () {
            Route::get('batch_product/:id', '/batchProduct');
            Route::get('express/:id', '/express');
            Route::get('product/:id', '/product');
            Route::post('apply/:id', '/refund');
            Route::get('list', '/lst');
            Route::get('detail/:id', '/detail');
            Route::post('del/:id', '/del');
            Route::post('back_goods/:id', '/back_goods');
            Route::post('cancel/:id', '/cancel');
        })->prefix('api.store.order.StoreRefundOrder');

        //评价
        Route::group('reply', function () {
            Route::get('product/:id', '/product');
            Route::post(':id', '/reply');
        })->prefix('api.store.product.StoreReply');

        //注销用户
        Route::post('user/cancel', 'api.Auth/cancel');
        //用户
        Route::group('user', function () {
            //切换账号
            Route::get('account', 'User/account');
            Route::post('switch', 'User/switchUser');
            //修改信息
            Route::post('change/phone', 'User/changePhone');
            Route::post('change/info', 'User/updateBaseInfo');
            Route::post('change/password', 'User/changePassword');
            //收藏
            Route::get('/relation/product/lst', 'UserRelation/productList');
            Route::get('/relation/merchant/lst', 'UserRelation/merchantList');
            Route::post('/relation/create', 'UserRelation/create');
            Route::post('/relation/batch/create', 'UserRelation/batchCreate');
            Route::post('/relation/delete', 'UserRelation/delete');
            Route::post('/relation/batch/delete', 'UserRelation/batchDelete');

            //反馈
            Route::post('/feedback', 'Feedback/feedback');
            Route::get('/feedback/list', 'Feedback/feedbackList');
            Route::get('/feedback/detail/:id', 'Feedback/detail');
            //充值
            Route::post('/recharge', 'UserRecharge/recharge');
            Route::post('/recharge/brokerage', 'UserRecharge/brokerage');
            //地址
            Route::get('/address/lst', 'UserAddress/lst');
            Route::post('/address/create', 'UserAddress/create');
            Route::get('/address/detail/:id', 'UserAddress/detail');
            Route::post('/address/update/:id', 'UserAddress/editDefault');
            Route::post('/address/delete/:id', 'UserAddress/delete');

            //分销海报
            Route::get('/spread_image', 'User/spread_image');
            Route::get('/v2/spread_image', 'User/spread_image_v2');
            //推广人列表
            Route::get('/spread_list', 'User/spread_list');

            //提现
            Route::get('/extract/lst', 'UserExtract/lst');
            Route::get('/extract/banklst', 'UserExtract/bankLst');
            Route::post('/extract/create', 'UserExtract/create');
            Route::get('/extract/history_bank', 'UserExtract/historyBank');

            //绑定手机号
            Route::post('binding', 'User/binding');
            //小程序获取手机号
            Route::post('mp/binding', 'User/mpPhone');

            //余额记录
            Route::get('bill', 'User/bill');
            //佣金记录
            Route::get('brokerage_list', 'User/brokerage_list');
            //推广人订单
            Route::get('spread_order', 'User/spread_order');
            //推广人排行榜
            Route::get('spread_top', 'User/spread_top');
            //佣金排行榜
            Route::get('brokerage_top', 'User/brokerage_top');
            Route::get('spread_info', 'User/spread_info');
            Route::get('spread_level', 'User/spread_info');

            Route::get('brokerage/info', 'User/brokerage_info');
            Route::get('brokerage/all', 'User/brokerage_all');
            Route::get('brokerage/notice', 'User/notice');

            //浏览记录
            Route::get('history', 'UserHistory/lst');
            Route::post('history/delete/:id', 'UserHistory/deleteHistory');
            Route::post('history/batch/delete', 'UserHistory/deleteHistoryBatch');

            //发票
            Route::post('receipt/create', 'UserReceipt/create');
            Route::get('receipt/lst', 'UserReceipt/lst');
            Route::get('receipt/order', 'UserReceipt/order');
            Route::get('receipt/order/:id', 'UserReceipt/orderDetail');
            Route::post('receipt/delete/:id', 'UserReceipt/delete');
            Route::post('receipt/update/:id', 'UserReceipt/update');
            Route::post('receipt/is_default/:id', 'UserReceipt/isDefault');
            Route::get('receipt/detail/:id', 'UserReceipt/detail');

            //签到
            Route::get('sign/lst', 'UserSign/lst');
            Route::get('sign/info', 'UserSign/info');
            Route::post('sign/create', 'UserSign/create');
            Route::get('sign/month', 'UserSign/month');

            //积分
            Route::get('integral/info', 'User/integralInfo');
            Route::get('integral/lst', 'User/integralList');

            //客服列表
            Route::get('services', 'User/services');

            Route::get('member/info', 'User/memberInfo');
            Route::get('member/log', 'Member/getMemberValue');
        })->prefix('api.user.');

        //购物车
        Route::group('user/cart', function () {
            Route::get('/lst', 'StoreCart/lst');
            Route::post('/create', 'StoreCart/create');
            Route::post('/again', 'StoreCart/again');
            Route::post('/change/:id', 'StoreCart/change');
            Route::post('/delete', 'StoreCart/batchDelete');
            Route::get('/count', 'StoreCart/cartCount');
            Route::post('/batchCreate', 'StoreCart/batchCreate');
        })->prefix('api.store.order.');

        Route::group('store/product', function () {
            Route::post('/assist/create/:id', 'StoreProductAssistSet/create');
            Route::get('/assist/detail/:id', 'StoreProductAssistSet/detail');
            Route::post('/assist/set/:id', 'StoreProductAssistSet/set');
            Route::get('/assist/user/:id', 'StoreProductAssistSet/userList');
            Route::get('/assist/share/:id', 'StoreProductAssistSet/shareNum');
            Route::get('/assist/set/lst', 'StoreProductAssistSet/lst');
            Route::post('/assist/set/delete/:id', 'StoreProductAssistSet/delete');
            Route::post('/increase_take', 'StoreProduct/setIncreaseTake');
        })->prefix('api.store.product.');

        //申请商户
        Route::get('intention/lst', 'api.store.merchant.MerchantIntention/lst');
        Route::get('intention/detail/:id', 'api.store.merchant.MerchantIntention/detail');
        Route::post('intention/update/:id', 'api.store.merchant.MerchantIntention/update');
        Route::post('store/product/group/cancel', 'api.store.product.StoreProductGroup/cancel');

        //客服商品管理
        Route::group('server/:merId', function () {
            //商品
            Route::post('product/create', 'StoreProduct/create');
            Route::post('product/update/:id', 'StoreProduct/update');
            Route::get('product/detail/:id', 'StoreProduct/detail');
            Route::post('product/delete/:id', 'StoreProduct/delete');
            Route::post('product/status/:id', 'StoreProduct/switchStatus');
            Route::get('product/lst', 'StoreProduct/lst');
            Route::get('product/title', 'StoreProduct/title');
            Route::post('product/restore/:id', 'StoreProduct/restore');
            Route::post('product/destory/:id', 'StoreProduct/destory');
            Route::post('product/good/:id', 'StoreProduct/updateGood');
            Route::get('product/config', 'StoreProduct/config');

            //商品分类
            Route::get('category/lst', 'StoreCategory/lst');
            Route::post('category/create', 'StoreCategory/create');
            Route::post('category/update/:id', 'StoreCategory/update');
            Route::get('category/detail/:id', 'StoreCategory/detail');
            Route::post('category/status/:id', 'StoreCategory/switchStatus');
            Route::post('category/delete/:id', 'StoreCategory/delete');
            Route::get('category/list', 'StoreCategory/getList');
            Route::get('category/select', 'StoreCategory/getTreeList');
            Route::get('category/brandlist', 'StoreCategory/BrandList');


            //运费模板
            Route::get('template/lst', 'ShippingTemplate/lst');
            Route::post('template/create', 'ShippingTemplate/create');
            Route::post('template/update/:id', 'ShippingTemplate/update');
            Route::get('template/select', 'ShippingTemplate/getList');
            Route::get('template/detail/:id', 'ShippingTemplate/detail');
            Route::post('template/delete', 'ShippingTemplate/batchDelete');

            //品牌管理
            Route::get('attr/lst', 'StoreProductAttrTemplate/lst');
            Route::post('attr/create', 'StoreProductAttrTemplate/create');
            Route::post('attr/update/:id', 'StoreProductAttrTemplate/update');
            Route::get('attr/detail/:id', 'StoreProductAttrTemplate/detail');
            Route::post('attr/delete', 'StoreProductAttrTemplate/batchDelete');
            Route::get('attr/detail/:id', 'StoreProductAttrTemplate/detail');
            Route::get('attr/list', 'StoreProductAttrTemplate/getlist');



        })->prefix('api.server.')->middleware(\app\common\middleware\MerchantServerMiddleware::class,1);

        //管理员订单
        Route::group('admin/:merId', function () {
            Route::get('/statistics', '/orderStatistics');
            Route::get('/order_price', '/orderDetail');
            Route::get('/order_list', '/orderList');
            Route::get('/order/:id', '/order');
            Route::post('/mark/:id', '/mark');
            Route::post('/price/:id', '/price');
            Route::post('/delivery/:id', '/delivery');
            Route::post('/verify/:id', '/verify');
            Route::get('/pay_price', '/payPrice');
            Route::get('/pay_number', '/payNumber');
            Route::get('/mer_form', '/getFormData');
            Route::get('/dump_temp', '/getFormData');
            Route::get('/delivery_config', '/getDeliveryConfig');
            Route::get('/delivery_options', '/getDeliveryOptions');

        })->prefix('api.server.StoreOrder')->middleware(\app\common\middleware\MerchantServerMiddleware::class,0);

        //管理员退款单
        Route::group('server/:merId/refund', function () {
            //退款单
            Route::get('lst', '/lst');
            Route::get('detail/:id', '/detail');
            Route::get('get/:id', '/getRefundPrice');
            Route::post('confirm/:id', '/refundPrice');
            Route::get('express/:id', '/express');
            Route::post('status/:id', '/switchStatus');
            Route::post('mark/:id', '/mark');
        })->prefix('api.server.StoreRefundOrder')->middleware(\app\common\middleware\MerchantServerMiddleware::class,0);
        //核销
        Route::group('verifier/:merId', function () {
            Route::get('order/:id', '/detail');
            Route::post(':id', '/verify');
        })->prefix('api.store.order.StoreOrderVerify')->middleware(\app\common\middleware\MerchantServerMiddleware::class,0);

        //社区
        Route::group('community', function () {

            Route::post('/create', 'Community/create');
            Route::post('/update/:id', 'Community/update');
            Route::post('/delete/:id', 'Community/delete');
            Route::get('pay_product/lst', 'Community/payList');
            Route::get('rela_product/lst', 'Community/relationList');
            Route::get('hist_product/lst', 'Community/historyList');

            Route::post('fans/:id', 'Community/setFocus');
            Route::get('fans/lst', 'Community/getUserFans');
            Route::get('focus/lst', 'Community/getUserFocus');

            Route::post('start/:id', 'Community/startCommunity');
            Route::get('start/lst', 'Community/getUserStartCommunity');

            Route::post('reply/create/:id', 'CommunityReply/create');
            Route::post('reply/start/:id', 'CommunityReply/start');

            Route::get('order/:id', 'Community/getSpuByOrder');
            Route::get('qrcode/:id', 'Community/qrcode');

        })->prefix('api.community.');

        //请求频率
        Route::group( function () {

            //付费会员购买
            Route::post('svip/pay/:id', 'api.user.Svip/createOrder');

            //订单检查
            Route::group('order/v3', function () {
                Route::post('check', 'PointsOrder/beforCheck');
                Route::post('create', 'PointsOrder/createOrder');
                Route::post('pay/:id', 'PointsOrder/orderPay');

            })->prefix('api.store.order.');

        })->middleware(\app\common\middleware\BlockerMiddleware::class);

        Route::group('points/order', function () {
            Route::get('lst', 'PointsOrder/lst');
            Route::get('detail/:id', 'PointsOrder/detail');
            Route::post('take/:id', 'PointsOrder/take');
            Route::post('deleate/:id', 'PointsOrder/del');
        })->prefix('api.store.order.');

    })->middleware(UserTokenMiddleware::class, true);

    //非强制登录
    Route::group(function () {
        // 付费会员
        Route::group('svip', function () {
            //价格列表
            Route::get('pay_lst', '/getTypeLst');
            Route::get('user_info', '/svipUserInfo');
            Route::get('coupon_lst', '/svipCoupon');
            Route::get('product_lst', '/svipProductList');
            Route::post('coupon_receive/:id', '/receiveCoupon');
        })->prefix('api.user.Svip');

        //社区
        Route::group('community', function () {
            //社区文章列表
            Route::get('/lst', 'Community/lst');
            Route::get('/video_lst', 'Community/videoShow');
            //详情
            Route::get('/show/:id', 'Community/show');
            //用户页
            Route::get('/user/info/:id', 'Community/userInfo');
            //用户的文章
            Route::get('/user/community/:id', 'Community/userCommunitylst');
            //用户的视频
            Route::get('/user/community_video/:id', 'Community/userCommunityVideolst');
            //分类&话题
            Route::get('category/lst', 'CommunityCategory/lst');
            Route::get('/:id/reply', 'CommunityReply/lst');

            Route::get('/focuslst', 'Community/focuslst');
        })->prefix('api.community.');
        //上传图片
        Route::post('upload/image/:field', 'api.Common/uploadImage');
        //获取商户基本信息
        Route::get('service/info/:id', 'api.store.service.Service/merchantInfo');
        //公共配置
        Route::get('config', 'api.Common/config');
        Route::get('has_service/:id','api.store.service.Service/hasService');
        //专题
        Route::group('activity', function () {
            Route::get('lst/:id', 'api.Common/activityLst');
            Route::get('info/:id', 'api.Common/activityInfo');
        });

        //商品
        Route::group('store/product', function () {
            Route::get('seckill/select', 'StoreProductSeckill/select');
            Route::get('seckill/lst', 'StoreProductSeckill/lst');
            Route::get('seckill/detail/:id', 'StoreProductSeckill/detail')->middleware(VisitProductMiddleware::class, 1);


            Route::get('category/lst', 'StoreCategory/lst');
            Route::get('category', 'StoreCategory/children');
            Route::get('brand/lst', 'StoreBrand/lst');
            Route::get('detail/:id', 'StoreProduct/detail')->middleware(VisitProductMiddleware::class, 0);
            Route::get('/qrcode/:id', 'StoreProduct/qrcode');
            Route::get('category/hotranking', 'StoreCategory/cateHotRanking');

            Route::get('bag/explain', 'StoreProduct/getBagExplain');
            Route::get('/reply/lst/:id', 'StoreReply/lst');
            //预售
            Route::get('/presell/lst', 'StoreProductPresell/lst');
            Route::get('/presell/detail/:id', 'StoreProductPresell/detail')->middleware(VisitProductMiddleware::class, 2);
            //预售协议
            Route::get('presell/agree', 'StoreProductPresell/getAgree');
            //助力
            Route::get('/assist/lst', 'StoreProductAssist/lst');
            //拼团
            Route::get('group/lst', 'StoreProductGroup/lst');
            Route::get('group/detail/:id', 'StoreProductGroup/detail')->middleware(VisitProductMiddleware::class, 4);
            Route::get('group/count', 'StoreProductGroup/userCount');
            Route::get('group/category', 'StoreProductGroup/category');
            Route::get('group/get/:id', 'StoreProductGroup/groupBuying');

            Route::get('/guarantee/:id', 'StoreProduct/guaranteeTemplate');
            Route::get('/preview', 'StoreProduct/preview');
            Route::get('/price_rule/:id', 'StoreProduct/priceRule');
        })->prefix('api.store.product.');

        //各种商品列表
        Route::group('product/spu', function () {
            //礼包 product/spu/bag
            Route::get('/bag', 'StoreSpu/bag');
            //商品 product/spu/lst
            Route::get('/lst', 'StoreSpu/lst');
            //热门 product/spu/hot/:type
            Route::get('/hot/:type', 'StoreSpu/hot');
            //推荐 product/spu/recommend
            Route::get('/recommend', 'StoreSpu/recommend');
            //商户商品  product/spu/merchant/:id
            Route::get('/merchant/:id', 'StoreSpu/merProductLst');
            //礼包推荐  product/spu/bag/recommend
            Route::get('/bag/recommend', 'StoreSpu/bagRecommend');
            //活动分类  product/spu/active/category/:type
            Route::get('/active/category/:type', 'StoreSpu/activeCategory');
            //标签获取数据
            Route::get('/labels', 'StoreSpu/labelsLst');
            //本地生活商品
            Route::get('/local/:id', 'StoreSpu/local');
            //复制口令
            Route::get('/copy', 'StoreSpu/copy');
            Route::get('/get/:id', 'StoreSpu/get');
            //优惠券商品列表
            Route::get('/coupon_product', 'StoreSpu/getProductByCoupon');
            //热卖排行
            Route::get('/get_hot_ranking', 'StoreSpu/getHotRanking');
        })->prefix('api.store.product.');

        //直播
        Route::group('broadcast', function () {
            Route::get('/lst', 'BroadcastRoom/lst');
            Route::get('/hot', 'BroadcastRoom/hot');
        })->prefix('api.store.broadcast.');

        //优惠券
        Route::group('coupon', function () {
            Route::get('product', 'api.store.product.StoreCoupon/coupon');
            Route::get('store/:id', 'api.store.product.StoreCoupon/merCoupon');
            Route::get('list', 'api.store.product.StoreCoupon/lst');
            Route::get('getlst', 'api.store.product.StoreCoupon/getList');
            Route::get('new_people', 'api.store.product.StoreCoupon/newPeople');
        });

        //商户
        Route::group('store/merchant/', function () {
            Route::get('/lst', 'Merchant/lst');
            Route::get('/product/lst/:id', 'Merchant/productList');
            Route::get('/category/lst/:id', 'Merchant/categoryList');
            Route::get('/detail/0', 'Merchant/systemDetail');
            Route::get('/detail/:id', 'Merchant/detail');
            Route::get('/qrcode/:id', 'Merchant/qrcode');
            Route::get('/local', 'Merchant/localLst');
        })->prefix('api.store.merchant.');
        Route::post('store/certificate/:merId', 'api.Auth/getMerCertificate');

        //文章
        Route::group('article', function () {
            Route::get('/lst/:cid', 'Article/lst');
            Route::get('/list', 'Article/list');
            Route::get('detail/:id', 'Article/detail');
            Route::get('/category/lst', 'ArticleCategory/lst');
        })->prefix('api.article.');

        Route::post('upload/video', 'merchant.Common/uploadVideo');
        Route::get('excel/download/:id', 'merchant.store.order.Order/download');
        //申请商户
        Route::post('intention/create', 'api.store.merchant.MerchantIntention/create');
        Route::get('intention/cate', 'api.store.merchant.MerchantIntention/cateLst');
        Route::get('intention/type', 'api.store.merchant.MerchantIntention/typeLst');
        //浏览
        Route::post('common/visit', 'api.Common/visit');
        Route::get('store/product/assist/count', 'api.store.product.StoreProductAssist/userCount');
        Route::get('store/expr/temps', 'admin.system.serve.Export/getExportTemp');

        //复制口令
        Route::get('command/copy', 'api.Common/getCommand');
        Route::group('discounts', function () {
            Route::get('lst', '/lst');
        })->prefix('api.store.product.Discounts');
        //test
        Route::any('store/test', 'api.Test/test');
        Route::get('subscribe', 'api.Common/subscribe');

        Route::group('points', function () {
            Route::get('home', '/home');
            Route::get('scope', '/points_mall_scope');
            Route::get('lst', '/lst');
            Route::get('detail/:id', '/detail');
        })->prefix('api.store.product.PointsProduct');

    })->middleware(UserTokenMiddleware::class, false);

    //微信支付回调
    Route::any('notice/wechat_pay', 'api.Common/wechatNotify')->name('wechatNotify');
    //微信支付回调
    Route::any('notice/wechat_combine_pay/:type', 'api.Common/wechatCombinePayNotify')->name('wechatCombinePayNotify');
    Route::any('notice/routine_combine_pay/:type', 'api.Common/routineCombinePayNotify')->name('routineCombinePayNotify');

    Route::any('notice/callback', 'api.Common/deliveryNotify');

    //小程序支付回调
    Route::any('notice/routine_pay', 'api.Common/routineNotify')->name('routineNotify');
    //支付宝支付回调
    Route::any('notice/alipay_pay/:type', 'api.Common/alipayNotify')->name('alipayNotify');
    Route::any('getVersion', 'api.Common/getVersion')->name('getVersion');

    //城市列表
    Route::get('system/city/lst', 'merchant.store.shipping.City/getlist');
    Route::get('v2/system/city/lst/:pid', 'merchant.store.shipping.City/lstV2');
    Route::get('v2/system/city', 'merchant.store.shipping.City/cityList');

    //热门搜索
    Route::get('common/hot_keyword', 'api.Common/hotKeyword')->append(['type'  => 0]);
    //社区热门搜索
    Route::get('common/commuunity/hot_keyword', 'api.Common/hotKeyword')->append(['type'  => 1]);
    //推荐页 banner
    Route::get('common/hot_banner/:type', 'api.Common/hotBanner');
    //退款原因
    Route::get('common/refund_message', 'api.Common/refundMessage');
    //充值赠送
    Route::get('common/recharge_quota', 'api.Common/userRechargeQuota');
    //快递公司
    Route::get('common/express', 'api.Common/express');
    //图片转 base64
    Route::post('common/base64', 'api.Common/get_image_base64');
    //个人中心菜单
    Route::get('common/menus', 'api.Common/menus');
    //首页数据
    Route::get('common/home', 'api.Common/home');
    //经纬度转位置信息
    Route::get('lbs/geocoder', 'api.Common/lbs_geocoder');
    //获取支付宝支付链接
    Route::get('common/pay_key/:key', 'api.Common/pay_key');
    //用户反馈类型
    Route::get('common/feedback_type', 'api.user.FeedBackCategory/lst');
    //登录
    Route::post('auth/login', 'api.Auth/login');
    //登录
    Route::post('auth', 'api.Auth/authLogin');
    //短信登录
    Route::post('auth/smslogin', 'api.Auth/smsLogin');
    //注册
    Route::post('auth/register', 'api.Auth/register');
    //小程序手机号注册
    Route::post('auth/mp_phone', 'api.Auth/mpPhone');
    //微信授权
    Route::get('auth/wechat', 'api.Auth/auth');
    //小程序授权
    Route::post('auth/mp', 'api.Auth/mpAuth');
    //app授权
    Route::post('auth/app', 'api.Auth/appAuth');
    //apple授权
    Route::post('auth/apple', 'api.Auth/appleAuth');
    //修改密码
    Route::post('user/change_pwd', 'api.Auth/changePassword');
    //验证码
    Route::post('auth/verify', 'api.Auth/verify');
    //微信配置
    Route::get('wechat/config', 'api.Wechat/jsConfig');
    //图片验证码
    Route::get('captcha', 'api.Auth/getCaptcha');
    //获取协议列表
    Route::get('agreement_lst', 'admin.system.Cache/getKeyLst')->append(['type' => 1]);
    //获取协议内容
    Route::get('agreement/:key', 'admin.system.Cache/getAgree');

    Route::get('copyright', 'api.Common/copyright');

    Route::get('script', 'api.Common/script');
    Route::get('appVersion', 'api.Common/appVersion');
    Route::get('diy', 'api.Common/diy');
    Route::get('navigation', 'api.Common/getNavigation');
    Route::get('micro', 'api.Common/micro');
    Route::get('version', 'admin.Common/version');

    //滑块验证码
    Route::get('ajcaptcha', 'api.Auth/ajcaptcha');
    Route::post('ajcheck', 'api.Auth/ajcheck');

    Route::get('open_screen', 'api.Common/open_screen');

})->middleware(AllowOriginMiddleware::class)
    ->middleware(InstallMiddleware::class)
    ->middleware(CheckSiteOpenMiddleware::class)
    ->middleware(RequestLockMiddleware::class);

Route::any('/', 'View/h5')->middleware(InstallMiddleware::class)
    ->middleware(CheckSiteOpenMiddleware::class);

Route::group('/pages', function () {
    Route::miss('View/h5');
})->middleware(InstallMiddleware::class)
    ->middleware(CheckSiteOpenMiddleware::class);

Route::group('/open-location', function () {
    Route::miss('View/h5');
})->middleware(InstallMiddleware::class)
    ->middleware(CheckSiteOpenMiddleware::class)
    ;
