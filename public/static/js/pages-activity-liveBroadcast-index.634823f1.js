(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-activity-liveBroadcast-index"],{"0457":function(t,e,a){"use strict";a.r(e);var i=a("5c27"),n=a("2be8");for(var o in n)["default"].indexOf(o)<0&&function(t){a.d(e,t,(function(){return n[t]}))}(o);a("ef5c");var r=a("f0c5"),u=Object(r["a"])(n["default"],i["b"],i["c"],!1,null,"0d3566c7",null,!1,i["a"],void 0);e["default"]=u.exports},"14a5":function(t,e,a){var i=a("a97a");i.__esModule&&(i=i.default),"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=a("4f06").default;n("55f1195e",i,!0,{sourceMap:!1,shadowMode:!1})},"2be8":function(t,e,a){"use strict";a.r(e);var i=a("7e4a"),n=a.n(i);for(var o in i)["default"].indexOf(o)<0&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e["default"]=n.a},"4cc1":function(t,e,a){"use strict";a.r(e);var i=a("cc8a"),n=a("4dc2");for(var o in n)["default"].indexOf(o)<0&&function(t){a.d(e,t,(function(){return n[t]}))}(o);a("b1a8");var r=a("f0c5"),u=Object(r["a"])(n["default"],i["b"],i["c"],!1,null,"6d33dd08",null,!1,i["a"],void 0);e["default"]=u.exports},"4dc2":function(t,e,a){"use strict";a.r(e);var i=a("fa4c"),n=a.n(i);for(var o in i)["default"].indexOf(o)<0&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e["default"]=n.a},"5c27":function(t,e,a){"use strict";a.d(e,"b",(function(){return i})),a.d(e,"c",(function(){return n})),a.d(e,"a",(function(){}));var i=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"main"},[a("v-uni-view",{staticClass:"flash-sale"},[a("v-uni-view",{staticClass:"list"},[t._l(t.broadcastList,(function(e,i){return a("v-uni-view",{key:i},[a("v-uni-navigator",{attrs:{"hover-class":"none",url:103==e.live_status&&e.replay_status||101===e.live_status||102===e.live_status?"plugin-private://wx2b03c6e691cd7370/pages/live-player-plugin?room_id="+e.room_id:""}},[a("v-uni-view",{staticClass:"item acea-row row-between-wrapper"},[a("v-uni-view",{staticClass:"live-image"},[a("img",{staticClass:"image",attrs:{src:e.share_img}}),a("v-uni-view",{staticClass:"live-top",class:102==e.live_status?"playRadius":"notPlayRadius",style:"background:"+(101==e.live_status?t.playBg:101!=e.live_status&&102!=e.live_status?t.endBg:t.notBg)+";"},[101==e.live_status?[a("v-uni-image",{attrs:{src:t.domain+"/static/images/live-01.png",mode:""}}),a("v-uni-text",[t._v("直播中")])]:t._e(),103==e.live_status&&1===e.replay_status?[a("v-uni-image",{attrs:{src:t.domain+"/static/images/live-02.png",mode:""}}),a("v-uni-text",[t._v("回放")])]:t._e(),101!=e.live_status&&102!=e.live_status&&103!=e.live_status||103==e.live_status&&0==e.replay_status?[a("v-uni-image",{attrs:{src:t.domain+"/static/images/live-02.png",mode:""}}),a("v-uni-text",[t._v("已结束")])]:t._e(),102==e.live_status?[a("v-uni-image",{attrs:{src:t.domain+"/static/images/live-03.png",mode:""}}),a("v-uni-text",[t._v("预告")])]:t._e()],2),101==e.live_status||102==e.live_status?a("v-uni-view",{staticClass:"broadcast-time"},[t._v(t._s(e.show_time))]):t._e()],1),a("v-uni-view",{staticClass:"live-wrapper"},[a("v-uni-view",{staticClass:"live-title"},[t._v(t._s(e.name))]),a("v-uni-view",{staticClass:"live-store"},[t._v(t._s(e.anchor_name))]),e.broadcast.length>0?a("v-uni-view",{staticClass:"pro-count",staticStyle:{"white-space":"nowrap",display:"flex"}},t._l(e.broadcast,(function(i,n){return a("v-uni-navigator",{key:n,staticClass:"item",attrs:{"hover-class":"none"}},[n<3?a("v-uni-view",{staticClass:"pro-img"},[a("v-uni-image",{attrs:{src:i.goods.cover_img}}),n<2?a("v-uni-view",{staticClass:"price"},[t._v("¥"+t._s(i.goods.price))]):a("v-uni-view",{staticClass:"more"},[t._v("+"+t._s(e.broadcast.length-2))])],1):t._e()],1)})),1):t._e()],1)],1)],1)],1)})),a("v-uni-view",{staticClass:"loadingicon acea-row row-center-wrapper"},[a("v-uni-text",{staticClass:"loading iconfont icon-jiazai",attrs:{hidden:0==t.loading}}),t._v(t._s(t.loadTitle))],1)],2)],1),a("home")],1)},n=[]},"736f":function(t,e,a){"use strict";a("7a82");var i=a("4ea4").default;Object.defineProperty(e,"__esModule",{value:!0}),e.arrivalNoticeApi=function(t){return o.default.post("store/product/increase_take",t)},e.bagExplain=function(){return o.default.get("store/product/bag/explain")},e.bagRecommend=function(){return o.default.get("product/spu/bag/recommend")},e.collectAdd=function(t){return o.default.post("user/relation/create",t)},e.collectAll=function(t){return o.default.post("user/relation/batch/create",t)},e.collectDel=function(t){return o.default.post("user/relation/delete",t)},e.copyPasswordApi=function(t){return o.default.get("product/spu/copy",t,{noAuth:!0})},e.copyPasswordSearch=function(t){return o.default.get("command/copy",t,{noAuth:!0})},e.create=function(t){return o.default.post("intention/create",t)},e.discountsCartAdd=function(t){return o.default.post("user/cart/batchCreate",t)},e.express=function(t){return o.default.post("ordero/express/"+t,{noAuth:!0})},e.followStore=function(t){return o.default.post("user/relation/create",{type:10,type_id:t})},e.getApplicationRecordList=function(t){return o.default.get("intention/lst",t)},e.getBrandlist=function(t){return o.default.get("store/product/brand/lst",t,{noAuth:!0})},e.getBroadcastListApi=function(t){return o.default.get("broadcast/lst",t,{noAuth:!0})},e.getCaptcha=function(){return o.default.get("captcha")},e.getCategoryList=function(){return o.default.get("store/product/category/lst",{},{noAuth:!0})},e.getCollectUserList=function(t){return o.default.get("user/relation/product/lst",t)},e.getCouponProductlist=function(t){t.brand_id&&Array.isArray(t.brand_id)&&(t=(0,n.default)({},t),t.brand_id=t.brand_id.toString());return o.default.get("product/spu/coupon_product",t,{noAuth:!0})},e.getDiscountsLst=function(t){return o.default.get("discounts/lst",t,{noAuth:!0})},e.getGeocoder=function(t){return o.default.get("lbs/geocoder?location=".concat(t.lat,",").concat(t.long),{},{noAuth:!0})},e.getGoodsDetails=function(t){return o.default.get("intention/detail/"+t,{})},e.getGroomList=function(t,e){return o.default.get("product/spu/hot/"+t,e,{noAuth:!0})},e.getHotBanner=function(t){return o.default.get("common/hot_banner/"+t,{},{noAuth:!0})},e.getLiveList=function(t){return o.default.get("broadcast/hot",t,{noAuth:!0})},e.getMerProductHot=function(t,e){return o.default.get("product/spu/recommend",{page:void 0===e.page?1:e.page,limit:void 0===e.limit?10:e.limit,mer_id:t||""},{noAuth:!0})},e.getMerchantLst=function(t){return o.default.get("user/relation/merchant/lst",t,{noAuth:!0})},e.getPresellProductDetail=function(t){return o.default.get("store/product/presell/detail/"+t,{},{noAuth:!0})},e.getPreviewProDetail=function(t){return o.default.get("store/product/preview",t,{noAuth:!0})},e.getProductCode=function(t,e){return o.default.get("store/product/qrcode/"+t,e)},e.getProductDetail=function(t){return o.default.get("store/product/detail/"+t,{},{noAuth:!0})},e.getProductHot=function(t,e){return o.default.get("product/spu/recommend",{page:void 0===t?1:t,limit:void 0===e?10:e},{noAuth:!0})},e.getProductslist=function(t){t.brand_id&&Array.isArray(t.brand_id)&&(t=(0,n.default)({},t),t.brand_id=t.brand_id.toString());return o.default.get("product/spu/lst",t,{noAuth:!0})},e.getReplyConfig=function(t){return o.default.get("reply/config/"+t)},e.getReplyList=function(t,e){return o.default.get("store/product/reply/lst/"+t,e,{noAuth:!0})},e.getSearchKeyword=function(){return o.default.get("common/hot_keyword",{},{noAuth:!0})},e.getSeckillProductDetail=function(t){return o.default.get("store/product/seckill/detail/"+t,{},{noAuth:!0})},e.getStoreCategory=function(t,e){return o.default.get("store/merchant/category/lst/"+t,e,{noAuth:!0})},e.getStoreCoupon=function(t){return o.default.get("coupon/store/"+t,{noAuth:!0})},e.getStoreDetail=function(t,e){return o.default.get("store/merchant/detail/"+t,e,{noAuth:!0})},e.getStoreGoods=function(t,e){return o.default.get("product/spu/merchant/"+t,e,{noAuth:!0})},e.getStoreTypeApi=function(){return o.default.get("intention/type",{},{noAuth:!0})},e.merClassifly=function(){return o.default.get("intention/cate",{},{noAuth:!0})},e.merchantProduct=function(t,e){e.brand_id&&Array.isArray(e.brand_id)&&(e=(0,n.default)({},e),e.brand_id=e.brand_id.toString());return o.default.get("product/spu/merchant/"+t,e,{noAuth:!0})},e.merchantQrcode=function(t,e){return o.default.get("store/merchant/qrcode/"+t,e,{noAuth:!0})},e.postCartAdd=function(t){return o.default.post("user/cart/create",t)},e.priceRuleApi=function(t){return o.default.get("store/product/price_rule/".concat(t),{},{noAuth:!0})},e.productBag=function(t){return o.default.get("product/spu/bag",t,{noAuth:!0})},e.storeCategory=function(t){return o.default.get("store/product/category",t,{noAuth:!0})},e.storeCertificate=function(t){return o.default.post("store/certificate/".concat(t.merId),t)},e.storeListApi=function(t){return o.default.get("store_list",t,{noAuth:!0})},e.storeMerchantList=function(t){return o.default.get("store/merchant/lst",t,{noAuth:!0})},e.storeServiceList=function(t,e){return o.default.get("product/spu/local/".concat(t),e,{noAuth:!0})},e.unfollowStore=function(t){return o.default.post("user/relation/delete",{type:10,type_id:t})},e.updateGoodsRecord=function(t,e){return o.default.post("intention/update/"+t,e)},e.userCollectDel=function(t){return o.default.post("user/relation/batch/delete",t)},e.verify=function(t){return o.default.post("auth/verify",t)},a("d401"),a("d3b7"),a("25f0"),a("99af");var n=i(a("5530")),o=i(a("3314"))},7799:function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,".pictrueBox[data-v-6d33dd08]{width:%?130?%;height:%?120?%}\n/*返回主页按钮*/.home[data-v-6d33dd08]{position:fixed;color:#fff;text-align:center;z-index:9999;right:%?15?%;display:flex}.home .homeCon[data-v-6d33dd08]{border-radius:%?50?%;opacity:0;height:0;color:#e93323;width:0}.home .homeCon.on[data-v-6d33dd08]{opacity:1;-webkit-animation:bounceInRight .5s cubic-bezier(.215,.61,.355,1);animation:bounceInRight .5s cubic-bezier(.215,.61,.355,1);width:%?300?%;height:%?86?%;margin-bottom:%?20?%;display:flex;justify-content:center;align-items:center;background:var(--view-theme)}.home .homeCon .iconfont[data-v-6d33dd08]{font-size:%?48?%;color:#fff;display:inline-block;margin:0 auto}.home .pictrue[data-v-6d33dd08]{width:%?86?%;height:%?86?%;border-radius:50%;margin:0 auto;background-color:var(--view-theme);box-shadow:0 %?5?% %?12?% rgba(0,0,0,.5)}.home .pictrue .image[data-v-6d33dd08]{width:100%;height:100%}.pictruea[data-v-6d33dd08]{width:100%;height:100%;display:block;object-fit:cover;vertical-align:middle}",""]),t.exports=e},"7e4a":function(t,e,a){"use strict";a("7a82");var i=a("4ea4").default;Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0,a("99af");var n=a("736f"),o=i(a("4cc1")),r=a("f26a"),u={components:{home:o.default},data:function(){return{topImage:"",broadcastList:[],loadTitle:"加载更多",scrollLeft:0,interval:0,status:1,page:1,limit:5,loading:!1,loadend:!1,pageloading:!1,endBg:"linear-gradient(#666666, #999999)",notBg:"rgb(26, 163, 246)",playBg:"linear-gradient(#FF0000, #FF5400)",domain:r.HTTP_REQUEST_URL}},onLoad:function(){this.getBroadcastList()},methods:{getBroadcastList:function(){var t=this,e={page:t.page,limit:t.limit};t.loadend||t.pageloading||(this.pageloading=!0,(0,n.getBroadcastListApi)(e).then((function(e){var a=e.data.list,i=a.length<t.limit;t.page++,t.broadcastList=t.broadcastList.concat(a),t.page=t.page,t.pageloading=!1,t.loadend=i,t.loadTitle=i?"我也是有底线的":"加载更多"})).catch((function(e){t.pageloading=!1,t.loadTitle="我也是有底线的"})))}},onReachBottom:function(){this.getBroadcastList()}};e.default=u},a97a:function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,'@charset "UTF-8";\n/**\n * 这里是uni-app内置的常用样式变量\n *\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\n *\n */\n/**\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\n *\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */.main[data-v-0d3566c7]{padding:0 %?20?%;margin-top:%?20?%}.main .row-between-wrapper[data-v-0d3566c7]{margin-bottom:%?20?%;display:flex;flex-direction:row;flex-wrap:wrap;justify-content:space-between;border-radius:%?18?%}.main .row-between-wrapper .live-image[data-v-0d3566c7]{position:relative;width:%?355?%;height:%?272?%;border-radius:%?18?% 0 0 %?18?%}.main .row-between-wrapper .live-image .image[data-v-0d3566c7]{width:100%;height:100%;border-radius:%?18?% 0 0 %?18?%}.main .row-between-wrapper .live-wrapper[data-v-0d3566c7]{width:50%;height:%?272?%;padding:%?20?%;background:#fff;border-radius:0 %?18?% %?18?% 0;position:relative}.main .row-between-wrapper .live-wrapper .live-title[data-v-0d3566c7]{font-size:%?30?%;color:#282828;font-weight:700}.main .row-between-wrapper .live-wrapper .live-store[data-v-0d3566c7]{font-size:%?24?%;color:#666}.main .row-between-wrapper .live-wrapper .pro-count[data-v-0d3566c7]{width:%?330?%;height:%?100?%;white-space:nowrap;position:absolute;bottom:%?20?%}.main .row-between-wrapper .live-wrapper .item[data-v-0d3566c7]{width:%?100?%;height:%?100?%;margin-right:%?15?%;border-radius:%?8?%;position:relative}.main .row-between-wrapper .live-wrapper .item .pro-img[data-v-0d3566c7]{width:%?100?%;height:%?100?%}.main .row-between-wrapper .live-wrapper .item uni-image[data-v-0d3566c7]{width:%?100?%;height:%?100?%;max-width:100%;border-radius:%?8?%}.main .row-between-wrapper .live-wrapper .item .price[data-v-0d3566c7]{text-align:center;color:#fefefe;position:absolute;bottom:%?4?%;left:0;width:100%;font-size:%?22?%;background:rgba(0,0,0,.5);border-radius:0 0 %?8?% %?8?%}.main .row-between-wrapper .live-wrapper .item .more[data-v-0d3566c7]{width:%?100?%;height:%?100?%;line-height:%?100?%;text-align:center;font-size:%?28?%;color:#fefefe;font-weight:700;position:absolute;top:0;left:0;background-color:rgba(0,0,0,.2);border-radius:%?8?%}.live-top[data-v-0d3566c7]{z-index:20;position:absolute;left:0;top:0;display:flex;align-items:center;justify-content:center;color:#fff;min-width:%?130?%;max-width:%?140?%;height:%?50?%;font-size:%?22?%}.live-top.playRadius[data-v-0d3566c7]{border-radius:%?18?% 0 0 0}.live-top.notPlayRadius[data-v-0d3566c7]{border-radius:%?18?% 0 %?18?% 0}.live-top uni-image[data-v-0d3566c7]{width:%?30?%;height:%?30?%;margin-right:%?10?%;display:block}.broadcast-time[data-v-0d3566c7]{z-index:20;position:absolute;left:%?130?%;top:0;display:flex;align-items:center;justify-content:center;color:#fff;width:%?160?%;height:%?50?%;background:rgba(0,0,0,.4);font-size:%?22?%;border-radius:0 0 %?18?% 0}',""]),t.exports=e},b1a8:function(t,e,a){"use strict";var i=a("d56c"),n=a.n(i);n.a},cc8a:function(t,e,a){"use strict";a.d(e,"b",(function(){return i})),a.d(e,"c",(function(){return n})),a.d(e,"a",(function(){}));var i=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",{staticStyle:{"touch-action":"none"},style:t.viewColor},[a("v-uni-view",{staticClass:"home",staticStyle:{position:"fixed"},style:{top:t.top+"px",bottom:t.bottom},attrs:{id:"right-nav"},on:{touchmove:function(e){e.stopPropagation(),e.preventDefault(),arguments[0]=e=t.$handleEvent(e),t.setTouchMove.apply(void 0,arguments)}}},[t.homeActive?a("v-uni-view",{staticClass:"homeCon",class:!0===t.homeActive?"on":""},[a("v-uni-navigator",{staticClass:"iconfont icon-shouye-xianxing",attrs:{"hover-class":"none",url:"/pages/index/index","open-type":"switchTab"}}),a("v-uni-navigator",{staticClass:"iconfont icon-caigou-xianxing",attrs:{"hover-class":"none",url:"/pages/order_addcart/order_addcart","open-type":"switchTab"}}),a("v-uni-navigator",{staticClass:"iconfont icon-yonghu1",attrs:{"hover-class":"none",url:"/pages/user/index","open-type":"switchTab"}})],1):t._e(),a("v-uni-view",{staticClass:"pictrueBox",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.open.apply(void 0,arguments)}}},[a("v-uni-view",{staticClass:"pictrue"},[a("v-uni-image",{staticClass:"image pictruea",attrs:{src:!0===t.homeActive?"/static/images/navbtn_open.gif":"/static/images/navbtn_close.gif"}})],1)],1)],1)],1)},n=[]},d56c:function(t,e,a){var i=a("7799");i.__esModule&&(i=i.default),"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=a("4f06").default;n("6cbd5adf",i,!0,{sourceMap:!1,shadowMode:!1})},ef5c:function(t,e,a){"use strict";var i=a("14a5"),n=a.n(i);n.a},fa4c:function(t,e,a){"use strict";a("7a82"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i=a("26cb"),n=a("f26a"),o={name:"Home",props:{},data:function(){return{domain:n.HTTP_REQUEST_URL,top:"",bottom:""}},computed:(0,i.mapGetters)(["homeActive","viewColor","keyColor"]),methods:{setTouchMove:function(t){t.touches[0].clientY<545&&t.touches[0].clientY>66&&(this.top=t.touches[0].clientY,this.bottom="auto")},open:function(){this.homeActive?this.$store.commit("CLOSE_HOME"):this.$store.commit("OPEN_HOME")}},created:function(){this.bottom="50px"}};e.default=o}}]);