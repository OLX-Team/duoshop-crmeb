(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-users-user_money-index"],{"02f6":function(t,e,i){"use strict";i.r(e);var a=i("3668"),n=i("1029");for(var A in n)["default"].indexOf(A)<0&&function(t){i.d(e,t,(function(){return n[t]}))}(A);i("fa4e");var o=i("f0c5"),r=Object(o["a"])(n["default"],a["b"],a["c"],!1,null,"2f7fb421",null,!1,a["a"],void 0);e["default"]=r.exports},"0abe":function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,'@charset "UTF-8";\n/**\n * 这里是uni-app内置的常用样式变量\n *\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\n *\n */\n/**\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\n *\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */.Popup[data-v-2f7fb421]{flex:1;align-items:center;justify-content:center;width:%?500?%;background-color:#fff;position:fixed;top:%?500?%;left:%?125?%;z-index:1000}.Popup .logo-auth[data-v-2f7fb421]{z-index:-1;position:absolute;left:50%;top:0;-webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);width:%?150?%;height:%?150?%;display:flex;align-items:center;justify-content:center;border:%?8?% solid #fff;border-radius:50%;background:#fff}.Popup .image[data-v-2f7fb421]{height:%?42?%;margin-top:%?-54?%}.Popup .title[data-v-2f7fb421]{font-size:%?28?%;color:#000;text-align:center;margin-top:%?30?%;align-items:center;justify-content:center;width:%?500?%;display:flex}.Popup .tip[data-v-2f7fb421]{font-size:%?22?%;color:#555;padding:0 %?24?%;margin-top:%?25?%;display:flex;align-items:center;justify-content:center}.Popup .bottom .item[data-v-2f7fb421]{width:%?250?%;height:%?80?%;background-color:#eee;text-align:center;line-height:%?80?%;margin-top:%?54?%;font-size:%?24?%;color:#666}.Popup .bottom .item .text[data-v-2f7fb421]{font-size:%?24?%;color:#666}.Popup .bottom .item.on[data-v-2f7fb421]{width:%?500?%}.flex[data-v-2f7fb421]{display:flex;flex-direction:row}.Popup .bottom .item.grant[data-v-2f7fb421]{font-weight:700;background-color:#e93323;\n  /* background-color: var(--view-theme); */border-radius:0;padding:0}.Popup .bottom .item.grant .text[data-v-2f7fb421]{font-size:%?28?%;color:#fff}.mask[data-v-2f7fb421]{position:fixed;top:0;right:0;left:0;bottom:0;background-color:rgba(0,0,0,.65);z-index:99}',""]),t.exports=e},"0f96":function(t,e,i){"use strict";i("7a82");var a=i("4ea4").default;Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=a(i("5530")),A=a(i("a50e")),o=i("cd6d"),r=i("937f"),s=i("26cb"),c=a(i("823f")),u=i("3255"),d=(a(i("42cb")),i("ddb3")),l=getApp(),h={name:"Authorize",props:{isAuto:{type:Boolean,default:!0},isGoIndex:{type:Boolean,default:!0},isShowAuth:{type:Boolean,default:!1}},components:{},data:function(){return{title:"用户登录",info:"请登录，将为您提供更好的服务！",isWeixin:this.$wechat.isWeixin(),canUseGetUserProfile:!1,code:null,top:0,mp_is_new:this.$Cache.get("MP_VERSION_ISNEW")||!1,editModal:!1}},computed:(0,n.default)((0,n.default)({},(0,s.mapGetters)(["isLogin","userInfo","viewColor"])),(0,u.configMap)(["routine_logo"])),watch:{isLogin:function(t){!0===t&&this.$emit("onLoadFun",this.userInfo)},isShowAuth:function(t){this.getCode(this.isShowAuth)}},created:function(){this.top=uni.getSystemInfoSync().windowHeight/2-70,wx.getUserProfile&&(this.canUseGetUserProfile=!0),this.setAuthStatus(),this.getCode(this.isShowAuth)},methods:{setAuthStatus:function(){},getCode:function(t){t&&(this.code=1)},toWecahtAuth:function(){(0,d.toLogin)(!0)},getUserProfile:function(){var t=this,e=this;c.default.getUserProfile().then((function(i){var a=i.userInfo;a.code=t.code,a.spread=l.globalData.spid,a.spread_code=l.globalData.code,(0,o.commonAuth)({auth:{type:"routine",auth:a}}).then((function(i){if(200!=i.data.status)return uni.setStorageSync("auth_token",i.data.result.key),uni.navigateTo({url:"/pages/users/login/index"});var a=i.data.result.expires_time-A.default.time();e.$store.commit("UPDATE_USERINFO",i.data.result.user),e.$store.commit("LOGIN",{token:i.data.result.token,time:a}),e.$store.commit("SETUID",i.data.result.user.uid),A.default.set(r.EXPIRES_TIME,i.data.result.expires_time,a),A.default.set(r.USER_INFO,i.data.result.user,a),t.$emit("onLoadFun",i.data.result.user),i.data.result.user.isNew&&t.mp_is_new&&(t.editModal=!0)})).catch((function(t){uni.hideLoading(),uni.showToast({title:t.message,icon:"none",duration:2e3})}))})).catch((function(t){uni.hideLoading()}))},close:function(){var t=getCurrentPages();t[t.length-1];this.$emit("authColse",!1)}}};e.default=h},1029:function(t,e,i){"use strict";i.r(e);var a=i("0f96"),n=i.n(a);for(var A in a)["default"].indexOf(A)<0&&function(t){i.d(e,t,(function(){return a[t]}))}(A);e["default"]=n.a},"178d":function(t,e,i){var a=i("0abe");a.__esModule&&(a=a.default),"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("a1548d5e",a,!0,{sourceMap:!1,shadowMode:!1})},"18a8":function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,'@charset "UTF-8";\n/**\n * 这里是uni-app内置的常用样式变量\n *\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\n *\n */\n/**\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\n *\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */.my-account .wrapper[data-v-b485292e]{background-color:#fff;padding:%?32?% 0 %?34?% 0;margin-bottom:%?14?%}.my-account .wrapper .header[data-v-b485292e]{width:%?690?%;height:%?330?%;background-image:linear-gradient(90deg,var(--view-bntColor21) 0,var(--view-bntColor22));border-radius:%?16?%;margin:0 auto;box-sizing:border-box;color:hsla(0,0%,100%,.6);font-size:%?24?%}.t-color[data-v-b485292e]{color:var(--view-theme)}.my-account .wrapper .header .headerCon[data-v-b485292e]{background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAArIAAAFKCAYAAADhULxpAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQyIDc5LjE2MDkyNCwgMjAxNy8wNy8xMy0wMTowNjozOSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTggKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkEzMUM4RDlEM0YxNTExRTk4OUJFQ0Q4Qjg0RDBCMzQ1IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkEzMUM4RDlFM0YxNTExRTk4OUJFQ0Q4Qjg0RDBCMzQ1Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6QTMxQzhEOUIzRjE1MTFFOTg5QkVDRDhCODREMEIzNDUiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6QTMxQzhEOUMzRjE1MTFFOTg5QkVDRDhCODREMEIzNDUiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6ymvxvAAAIhklEQVR42uzd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");background-repeat:no-repeat;background-size:100%;height:100%;width:100%;padding:%?36?% 0 %?29?% 0;box-sizing:border-box}.my-account .wrapper .header .headerCon .account[data-v-b485292e]{padding:0 %?35?%}.my-account .wrapper .header .headerCon .account .assets .money[data-v-b485292e]{font-size:%?72?%;color:#fff}.my-account .wrapper .header .headerCon .account .recharge[data-v-b485292e]{font-size:%?28?%;width:%?150?%;height:%?54?%;border-radius:%?27?%;background-color:#fff9f8;text-align:center;line-height:%?54?%}.my-account .wrapper .header .headerCon .cumulative[data-v-b485292e]{margin-top:%?46?%}.my-account .wrapper .header .headerCon .cumulative .item[data-v-b485292e]{flex:1;padding-left:%?35?%}.my-account .wrapper .header .headerCon .cumulative .item .money[data-v-b485292e]{font-size:%?48?%;color:#fff;margin-top:%?6?%}.my-account .wrapper .nav[data-v-b485292e]{height:%?155?%;border-bottom:1px solid #f5f5f5}.my-account .wrapper .nav .item[data-v-b485292e]{flex:1;text-align:center;font-size:%?26?%;color:#999}.my-account .wrapper .nav .item .pictrue[data-v-b485292e]{width:%?44?%;height:%?44?%;margin:0 auto;margin-bottom:%?20?%}.my-account .wrapper .nav .item .pictrue uni-image[data-v-b485292e]{width:100%;height:100%}.my-account .wrapper .advert[data-v-b485292e]{padding:0 %?30?%;margin-top:%?30?%}.my-account .wrapper .advert .item[data-v-b485292e]{background-color:#fff6d1;width:%?332?%;height:%?118?%;border-radius:%?10?%;padding:0 %?27?% 0 %?25?%;box-sizing:border-box;font-size:%?24?%;color:#e44609}.my-account .wrapper .advert .item.on[data-v-b485292e]{background-color:#fff3f3;color:#e96868}.my-account .wrapper .advert .item .pictrue[data-v-b485292e]{width:%?78?%;height:%?78?%}.my-account .wrapper .advert .item .pictrue uni-image[data-v-b485292e]{width:100%;height:100%}.my-account .wrapper .advert .item .text .name[data-v-b485292e]{font-size:%?30?%;font-weight:700;color:#f33c2b;margin-bottom:%?7?%}.my-account .wrapper .advert .item.on .text .name[data-v-b485292e]{color:#f64051}.my-account .wrapper .list[data-v-b485292e]{padding:0 %?30?%}.my-account .wrapper .list .item[data-v-b485292e]{margin-top:%?44?%}.my-account .wrapper .list .item .picTxt .iconfont[data-v-b485292e]{width:%?82?%;height:%?82?%;border-radius:50%;background-image:linear-gradient(90deg,#ff9389 0,#f9776b);text-align:center;line-height:%?82?%;color:#fff;font-size:%?40?%}.my-account .wrapper .list .item .picTxt .iconfont.yellow[data-v-b485292e]{background-image:linear-gradient(90deg,#fca 0,#fea060)}.my-account .wrapper .list .item .picTxt .iconfont.green[data-v-b485292e]{background-image:linear-gradient(90deg,#a1d67c 0,#9dd074)}.my-account .wrapper .list .item .picTxt[data-v-b485292e]{width:%?428?%;font-size:%?30?%;color:#282828}.my-account .wrapper .list .item .picTxt .text[data-v-b485292e]{width:%?317?%}.my-account .wrapper .list .item .picTxt .text .infor[data-v-b485292e]{font-size:%?24?%;color:#999;margin-top:%?5?%}.my-account .wrapper .list .item .bnt[data-v-b485292e]{font-size:%?26?%;color:#282828;width:%?156?%;height:%?52?%;border:%?1?% solid #ddd;border-radius:%?26?%;text-align:center;line-height:%?52?%}.my-account .wrapper .list .item .bnt.end[data-v-b485292e]{font-size:%?26?%;color:#aaa;background-color:#f2f2f2;border-color:#f2f2f2}',""]),t.exports=e},3622:function(t,e,i){var a=i("18a8");a.__esModule&&(a=a.default),"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("47d9504e",a,!0,{sourceMap:!1,shadowMode:!1})},3668:function(t,e,i){"use strict";i.d(e,"b",(function(){return a})),i.d(e,"c",(function(){return n})),i.d(e,"a",(function(){}));var a=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("v-uni-view",[t.isShowAuth&&t.code?i("v-uni-view",{staticClass:"mask",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.close.apply(void 0,arguments)}}}):t._e(),t.isShowAuth&&t.code?i("v-uni-view",{staticClass:"Popup",style:"top:"+t.top+"px;"},[i("v-uni-view",{staticClass:"logo-auth"},[i("v-uni-image",{staticClass:"image",attrs:{src:t.routine_logo,mode:"aspectFit"}})],1),t.isWeixin?i("v-uni-text",{staticClass:"title"},[t._v("授权提醒")]):i("v-uni-text",{staticClass:"title"},[t._v(t._s(t.title))]),t.isWeixin?i("v-uni-text",{staticClass:"tip"},[t._v("请授权头像等信息，以便为您提供更好的服务！")]):i("v-uni-text",{staticClass:"tip"},[t._v(t._s(t.info))]),i("v-uni-view",{staticClass:"bottom flex"},[i("v-uni-text",{staticClass:"item",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.close.apply(void 0,arguments)}}},[t._v("随便逛逛")]),i("v-uni-button",{staticClass:"item grant",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.toWecahtAuth.apply(void 0,arguments)}}},[t.isWeixin?i("v-uni-text",{staticClass:"text"},[t._v("去授权")]):i("v-uni-text",{staticClass:"text"},[t._v("去登录")])],1)],1)],1):t._e()],1)},n=[]},"4b65":function(t,e,i){"use strict";i("7a82");var a=i("4ea4").default;Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0,i("99af");var n=i("736f"),A=(i("4d59"),i("6859")),o=i("26cb"),r=a(i("1857")),s=a(i("02f6")),c=i("3255"),u=i("f26a"),d=(getApp(),{components:{recommend:r.default,authorize:s.default},data:function(){return{domain:u.HTTP_REQUEST_URL,userInfo:{},hostProduct:[],isClose:!1,activity:{},isAuto:!1,isShowAuth:!1,hotScroll:!1,hotPage:1,hotLimit:10}},computed:(0,c.configMap)(["recharge_switch","balance_func_status","recommend_switch"],(0,o.mapGetters)(["isLogin","viewColor","keyColor"])),onLoad:function(){this.isLogin?(this.getUserInfo(),this.get_host_product()):(this.isAuto=!0,this.isShowAuth=!0)},onReady:function(){},methods:{getUserInfo:function(){var t=this;(0,A.getUserInfo)().then((function(e){t.userInfo=e.data}))},onLoadFun:function(){this.isShowAuth=!1,this.getUserInfo(),this.get_host_product()},authColse:function(t){this.isShowAuth=t},get_host_product:function(){var t=this;t.hotScroll||(0,n.getProductHot)(t.hotPage,t.hotLimit).then((function(e){t.hotPage++,t.hotScroll=e.data.list.length<t.hotLimit,t.hostProduct=t.hostProduct.concat(e.data.list)}))}},onReachBottom:function(){this.get_host_product()},onPageScroll:function(t){uni.$emit("scroll")}});e.default=d},"4d59":function(t,e,i){"use strict";i("7a82"),Object.defineProperty(e,"__esModule",{value:!0}),e.arrivalSubscribe=function(){var t=n();return A([t.PRODUCT_INCREASE])},e.auth=n,e.openEextractSubscribe=function(){var t=n();return A([t.EXTRACT_NOTICE])},e.openExtrctSubscribe=function(){var t=n();return A([t.EXTRACT_NOTICE])},e.openOrderRefundSubscribe=function(){var t=n();return A([t.REFUND_CONFORM_CODE])},e.openOrderSubscribe=function(){var t=n();return A([t.ORDER_DELIVER_SUCCESS,t.DELIVER_GOODS_CODE])},e.openPaySubscribe=function(){var t=n();return A([t.DELIVER_GOODS_CODE,t.ORDER_DELIVER_SUCCESS,t.ORDER_PAY_SUCCESS])},e.openRechargeSubscribe=function(){var t=n();return A([t.USER_BALANCE_CHANGE])},e.subscribe=A,i("d3b7");var a=i("937f");function n(){var t,e=uni.getStorageSync(a.SUBSCRIBE_MESSAGE);return t=e||{},t}function A(t){wx;return new Promise((function(e,i){uni.requestSubscribeMessage({tmplIds:t,success:function(t){return e(t)},fail:function(t){return e(t)},complete:function(t){}})}))}},"8cf2":function(t,e,i){"use strict";i.r(e);var a=i("4b65"),n=i.n(a);for(var A in a)["default"].indexOf(A)<0&&function(t){i.d(e,t,(function(){return a[t]}))}(A);e["default"]=n.a},"915c":function(t,e,i){"use strict";i.d(e,"b",(function(){return a})),i.d(e,"c",(function(){return n})),i.d(e,"a",(function(){}));var a=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("v-uni-view",{style:t.viewColor},[i("v-uni-view",{staticClass:"my-account"},[i("v-uni-view",{staticClass:"wrapper"},[i("v-uni-view",{staticClass:"header"},[i("v-uni-view",{staticClass:"headerCon"},[i("v-uni-view",{staticClass:"account acea-row row-top row-between"},[i("v-uni-view",{staticClass:"assets"},[i("v-uni-view",[t._v("总资产(元)")]),i("v-uni-view",{staticClass:"money"},[t._v(t._s(t.userInfo.now_money||0))])],1),1==t.recharge_switch?i("v-uni-navigator",{staticClass:"recharge t-color",attrs:{url:"/pages/users/user_payment/index","hover-class":"none"}},[t._v("充值")]):t._e()],1),i("v-uni-view",{staticClass:"cumulative acea-row row-top"},[i("v-uni-view",{staticClass:"item"},[i("v-uni-view",[t._v("累计充值(元)")]),i("v-uni-view",{staticClass:"money"},[t._v(t._s(t.userInfo.total_recharge||0))])],1),i("v-uni-view",{staticClass:"item"},[i("v-uni-view",[t._v("累计消费(元)")]),i("v-uni-view",{staticClass:"money"},[t._v(t._s(t.userInfo.total_consume||0))])],1)],1)],1)],1),i("v-uni-view",{staticClass:"nav acea-row row-middle"},[i("v-uni-navigator",{staticClass:"item",attrs:{"hover-class":"none",url:"/pages/users/user_bill/index"}},[i("v-uni-view",{staticClass:"pictrue"},[i("v-uni-image",{attrs:{src:t.domain+"/static/diy/record1"+t.keyColor+".png"}})],1),i("v-uni-view",[t._v("账单记录")])],1),i("v-uni-navigator",{staticClass:"item",attrs:{"hover-class":"none",url:"/pages/users/user_bill/index?type=1"}},[i("v-uni-view",{staticClass:"pictrue"},[i("v-uni-image",{attrs:{src:t.domain+"/static/diy/record2"+t.keyColor+".png"}})],1),i("v-uni-view",[t._v("消费记录")])],1),i("v-uni-navigator",{staticClass:"item",attrs:{"hover-class":"none",url:"/pages/users/user_bill/index?type=2"}},[i("v-uni-view",{staticClass:"pictrue"},[i("v-uni-image",{attrs:{src:t.domain+"/static/diy/record3"+t.keyColor+".png"}})],1),i("v-uni-view",[t._v("充值记录")])],1)],1)],1),1==t.recommend_switch?i("recommend",{attrs:{hostProduct:t.hostProduct,isLogin:t.isLogin}}):t._e()],1),i("authorize",{attrs:{isAuto:t.isAuto,isShowAuth:t.isShowAuth},on:{onLoadFun:function(e){arguments[0]=e=t.$handleEvent(e),t.onLoadFun.apply(void 0,arguments)},authColse:function(e){arguments[0]=e=t.$handleEvent(e),t.authColse.apply(void 0,arguments)}}})],1)},n=[]},e62c:function(t,e,i){"use strict";var a=i("3622"),n=i.n(a);n.a},eaaa:function(t,e,i){"use strict";i.r(e);var a=i("915c"),n=i("8cf2");for(var A in n)["default"].indexOf(A)<0&&function(t){i.d(e,t,(function(){return n[t]}))}(A);i("e62c");var o=i("f0c5"),r=Object(o["a"])(n["default"],a["b"],a["c"],!1,null,"b485292e",null,!1,a["a"],void 0);e["default"]=r.exports},fa4e:function(t,e,i){"use strict";var a=i("178d"),n=i.n(a);n.a}}]);