define(function(require) {
	var wx = require('http://res.wx.qq.com/open/js/jweixin-1.0.0.js');
	var MD5 = require('$UI/system/lib/base/md5');
	var Sha1 = require('./sha1');
	var $ = require('jquery');
	var weixinJSApiUrl = "/baas/weixin/jsapi";
	//var router = __re__quire__("$UI/system/lib/route/router");
	
	function WxApi(appId) {
		this.jsapiTicket = "";
		this.ready = false;
		this.appId = appId;
	}
	;

	WxApi.prototype.exec = function(cb) {
		var configDtd = $.Deferred();
		var self = this;
		if (this.ready === false) {
			//router.originUrl
			var url = location.href.split('#')[0];
			this.sign(url, function(ret) {
				var config = {
					debug : false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
					appId : self.appId, // 必填，公众号的唯一标识
					timestamp : ret.timestamp, // 必填，生成签名的时间戳
					nonceStr : ret.nonceStr, // 必填，生成签名的随机串
					signature : ret.signature,// 必填，签名，见附录1
					jsApiList : [ 'checkJsApi', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'hideMenuItems', 'showMenuItems', 'hideAllNonBaseMenuItem',
							'showAllNonBaseMenuItem', 'translateVoice', 'startRecord', 'stopRecord', 'onRecordEnd', 'playVoice', 'pauseVoice', 'stopVoice', 'uploadVoice', 'downloadVoice',
							'chooseImage', 'previewImage', 'uploadImage', 'downloadImage', 'getNetworkType', 'openLocation', 'getLocation', 'hideOptionMenu', 'showOptionMenu', 'closeWindow',
							'scanQRCode', 'chooseWXPay', 'openProductSpecificView', 'addCard', 'chooseCard', 'openCard' ]
				// 必填，需要使用的JS接口列表，所有JS接口列表见附录2
				};
				wx.config(config);
			});
		}
		wx.ready(function() {
			self.ready = true;
			configDtd.resolve(wx);
		});
		wx.error(function() {
			configDtd.reject();
		});
		return configDtd.promise();
	};

	/**
	 {
					action: "getPrepayOrder",
					body:params.body,
					mchId
					notifyUrl
					outTradeNo
					totalFee
				}
	 */
	WxApi.prototype.chooseWXPay = function(params) {
		var self = this;
		params.action = "chooseWXPay";
		var wxJsApiPayDtd = $.Deferred();
		this.exec().done(function(wx){
			var chooseWXPayDtd = $.Deferred();
			$.ajax({
				method: "POST",
				url:weixinJSApiUrl,
				cache:false,
				data:params
			}).done(function(wxJsPayReq) {
				wxJsPayReq = JSON.parse(wxJsPayReq);
				chooseWXPayDtd.resolve(wxJsPayReq);
			}).fail(function() {
				prepayOrderDtd.reject("chooseWXPayError");
				
			});
			
			chooseWXPayDtd.promise().done(function(wxJsPayReq){
				var payParams = {
				      timestamp: wxJsPayReq.timeStamp,
				      nonceStr: wxJsPayReq.nonceStr,
				      package: wxJsPayReq['package'],
				      signType: 'MD5', // 注意：新版支付接口使用 MD5 加密
				      paySign:wxJsPayReq.paySign,
				      success: function (res) {
				    	  wxJsApiPayDtd.resolve(res);
				      }
				};
				wx.chooseWXPay(payParams);
			}).fail(function(msg){
				wxJsApiPayDtd.reject(msg);
			});
		}).fail(function(error){
			wxJsApiPayDtd.reject(error);
		});
		return wxJsApiPayDtd.promise();
	};

	WxApi.prototype.sign = function(url, cb) {
		var self = this;
		this.getTicket(function(jsapiTicket) {
			var ret = {
				jsapi_ticket : jsapiTicket,
				nonceStr : createNonceStr(),
				timestamp : createTimestamp(),
				url : url
			};
			var result = raw(ret);
			ret.signature = Sha1.hash(result);
			ret.appId = self.appId;
			delete ret.jsapi_ticket;
			delete ret.url;
			cb(ret);
		});

	};

	WxApi.prototype.getTicket = function(cb) {
		var self = this;
		if (this.jsapiTicket){
			return cb(this.jsapiTicket);
		}
		$.ajax({
			method: "GET",
			url:weixinJSApiUrl,
			cache:false,
			data:{action: "getTicket"}
		}).done(function(ticket) {
			self.jsapiTicket = ticket;
			cb(self.jsapiTicket);
		}).fail(function() {
			console.log("getTicket wrong");
		});
	};

	WxApi.prototype.createTimestamp = function() {
		return createTimestamp();
	};

	function createNonceStr() {
		return Math.random().toString(36).substr(2, 15);
	}

	function createTimestamp() {
		return parseInt(new Date().getTime() / 1000) + '';
	}

	function raw(args) {
		var keys = Object.keys(args);
		keys = keys.sort();
		var newArgs = {};
		keys.forEach(function(key) {
			newArgs[key.toLowerCase()] = args[key];
		});
		var result = '';
		for ( var k in newArgs) {
			if (newArgs.hasOwnProperty(k)) {
				result += '&' + k + '=' + newArgs[k];
			}
		}
		result = result.substr(1);
		return result;
	}

	navigator.WxApi = WxApi;
	return navigator.WxApi;
});