define(function(require){
	var $ = require("jquery");
	var Component = require("$UI/system/lib/base/component");
	var justep = require("$UI/system/lib/justep");
	var CommonUtils = require("$UI/system/components/justep/common/utils"),
		RouteUtil = require('$UI/system/lib/route/routeUtil'),
		WindowContainer = require("$UI/system/components/justep/windowContainer/windowContainer"),
		shell = require("./shell"),
		Object = require("$UI/system/lib/base/object"),
		Observable = require("$UI/system/lib/base/observable");
	
	var ShellImpl = Object.extend({
		mixins:Observable,
		constructor: function(model,options){
			this.callParent();
			Observable.prototype.constructor.call(this);
			if(shell.impl){
				throw new Error('shell不支持嵌套');
			}else{
				shell.impl = this;
			}
			this.ownerModel = model;
			this.openedPageHistory = [];
			this.isSinglePage = false;
			this.useDefaultExitHandler = true;
			this.mainPageXid = null;
			this.uninterceptXids = [];
			this.pageMappings = options.pageMappings || {};
			this.errorHandler = options.errorHandler;
			this.contentsXid = options.contentsXid || 'contents';
			this.wingLeftXid = options.wingLeftXid || 'left';
			this.wingRightXid = options.wingRightXid || 'right';
			this.wingXid = options.wingXid || 'wing';
			this.$routeState = this.ownerModel.$routeState;
			this.initRoute();
			this.init();
		}
	});
	
	ShellImpl.prototype.init = function(params){
		this.ownerModel.on('onModelConstructDone',function(){
			this.mainPageLoadDtd = this.ownerModel.getLoadedDeferred();
			this.pagesComp = this.ownerModel.comp(this.contentsXid);
			this.wingComp = this.ownerModel.comp(this.wingXid);
			this.wingLeftComp = this.ownerModel.comp(this.wingLeftXid);
			this.wingRightComp = this.ownerModel.comp(this.wingRightXid);
			if(this.useDefaultExitHandler){
				this.initDoubleClickExitApp();
			}
		},this);
	};
	
	ShellImpl.prototype.initDoubleClickExitApp = function(){
		var self = this;
		CommonUtils.attachDoubleClickExitApp(function(){
			if(self.pagesComp.getActiveIndex() === 0){
				return true;
			}
			return false;
		});
	};
	
	ShellImpl.prototype.addPageMappings = function(pageMappings){
		this.pageMappings = $.extend(pageMappings,this.pageMappings);
	};
	
	ShellImpl.prototype.showMainPage = function(){
		if(this.mainPageXid){
			return this.showPage(this.mainPageXid);
		}
	};
	
	ShellImpl.prototype.refineParam = function(params){
		if(typeof params === "object"){
			$.each(params,function(key,value){
				if(typeof value === "undefined" || value === "undefined"){
					delete params[key];
				}
			});
		}
		return params;
	};
	
	ShellImpl.prototype.formatParam = function(params){
		var urlParams = [];
		var url = "";
		/**
		 * 兼容参数 类型 
		 * 1. json对象
		 *     有可能是功能树上点击进来 先根据参数看是否能匹配到pagesMapping 
		 * 2. string  先假设是xid根据pagesMappings查询，查询不到假设为url
		 */
		if(typeof params === "string"){
			if(this.pageMappings[params] === undefined){
				url = params;
				var urlParsed = new justep.URL(url);
				var _params = $.extend(urlParsed.params,{url:urlParsed.pathname});
				params = _params;
			}else{
				var pageParams = this.pageMappings[params];
				pageParams.xid = params;
				params = pageParams;
			}
		}
		
		if(typeof params === "object"){
			
			if(!params.xid){
				params.xid = params.url; 
			}
			$.each(this.pageMappings,function(key,value){
				if(params.xid === key){
					value.xid = key;
					params = value;
					return;
				}
				var match = true;
				$.each(value,function(_key,_value){
					if(params[_key] !== _value && _key !== "extra" && _key !== "xid"){
						match = false;
					}
				});
				
				if(match){
					params.xid = key;
					return;
				}
			});
		}
		
		
		
		
		$.each(params,function(key,value){
			if(key !== "url" && key !== "xid" && key !== "extra"){
				if(value !== "undefined" && value !== "" && value !== undefined){
					urlParams.push(key + "=" + encodeURIComponent(value));
				}
			}else if(key === "url"){
				url = value;
			}
		});
		var urlParamStr = urlParams.join("&");
		if(urlParams.length > 0){
			if(url.indexOf("?") != -1){
				params.url = url + "&" + urlParamStr;
			}else{
				params.url = url + "?" + urlParamStr;
			}
		}
		params.xid = params.xid || params.url;
		return params;
	};
	
	ShellImpl.prototype.loadPage = function(params,viewLoadCallback){
		params = this.formatParam(params);
		var url = params.url;
		//var title = params.title;
		var xid = params.xid;
		
		var eventData = {
			data:params,
			cancel:false
		};
		this.fireEvent("onBeforePageLoad",eventData);
		if(eventData.cancel){
			return $.Deferred().promise();
		}
		
		var self = this;
		var container,pageLoaded = false,parentNode;
		if(this.wingComp && xid === this.wingLeftXid){
			container = this.wingLeftComp;
			 if(container.innerContainer){
				 pageLoaded = true;
			 }else{
				 parentNode = container.$domNode.get(0);
			 }
		}else if(this.wingComp && xid === this.wingRightXid){
			container = this.wingRightComp;
			 if(container.innerContainer){
				pageLoaded = true;
			 }else{
				parentNode = container.$domNode.get(0);
			 }
		}else{
			 container = this.pagesComp.getContent(xid);
			 if(container){
				 pageLoaded = true;
			 }else{
				 container = this.pagesComp.add({xid: xid});
				 parentNode = container.$domNode.get(0);
			 }
			 if(this.wingComp){
				 this.wingComp.hideLeft();
				 this.wingComp.hideRight();
			 }
		}
		var dtd = $.Deferred();
		if(!pageLoaded){
			var compose = new WindowContainer({
				parentNode: parentNode,
				src: url,
				onViewLoad: function(event){
					var pageViewLoadEventData = {
						data:params
					};
					self.fireEvent("onPageViewLoad",pageViewLoadEventData);
					
					viewLoadCallback && viewLoadCallback(event);
				},
				onLoad: function(){
					self._bindActiveEvent(container,params,xid,compose);
					self._bindInactiveEvent(container,params,xid,compose);
					
					var pageLoadEventData = {
						data:params,
						cancel:false
					};
					self.fireEvent("onAfterPageLoad",pageLoadEventData);
					
					dtd.resolve({
						container:compose
					});
				},
				onLoadError: function(event){
					self.errorHandler && self.errorHandler(event); 
					dtd.reject(event);
				}
			});
			container.innerContainer = compose;
		}else{
			if(container && container.innerContainer){
				dtd.resolve({
					container:container.innerContainer
				});
			}else{
				dtd.resolve({
					container:container
				});
			}
		}
		return dtd.promise();
	};
	
	ShellImpl.prototype._bindActiveEvent = function(container,params,xid,compose){
		var self = this;
		var isFirst = true;
		var activeCallback = function(){
			var eventParams = params;
			if(self.pageMappings[xid]){
				eventParams = self.pageMappings[xid]; 
			}
			var activeEventData = {
				source:self,
				data:{
					xid:xid,
					params:eventParams,
					container:compose,
					isFirst:isFirst
				}
			};
			
			self.fireEvent('onPageActive',activeEventData);
			if(compose.getInnerModel()){
				compose.getInnerModel().fireEvent('onActive',activeEventData);
			}
		};
		activeCallback();
		isFirst = false;
		container.on("onActive", activeCallback);
	};
	
	ShellImpl.prototype._bindInactiveEvent = function(container,params,xid,compose){
		var self = this;
		var activeCallback = function(){
			var eventParams = params;
			if(self.pageMappings[xid]){
				eventParams = self.pageMappings[xid]; 
			}
			var activeEventData = {
				source:self,
				data:{
					xid:xid,
					params:eventParams,
					container:compose
				}
			};
			
			self.fireEvent('onPageInactive',activeEventData);
			if(compose.getInnerModel()){
				compose.getInnerModel().fireEvent('onInactive',activeEventData);
			}
		};
		activeCallback();
		container.on("onInactive", activeCallback);
	};
	
	
	ShellImpl.prototype.closeAllOpendedPages = function(){
		var self = this;
		var closeAllDtd = $.Deferred();
		var _openedPages = self.getOpenedPages();
		if(_openedPages.length === 0){
			closeAllDtd.resolve();
		}else{
			
			this.showMainPage().done(function(){
				self.openedPageHistory = [self.mainPageXid];
				
				$.each(_openedPages,function(index,url){
					if(url !== self.mainPageXid){
						self.closePage();
					}
				});
				closeAllDtd.resolve();
			}).fail(function(){
				closeAllDtd.reject();
			});
		}
		
		
		return closeAllDtd.promise();
	};
	
	ShellImpl.prototype.closePage = function(url){
		//TODO 不支持wing left right 的删除
		var self = this;
		var closePageDtd = $.Deferred();
		if(!url){
			url = this.openedPageHistory[this.openedPageHistory.length-1];
		}
		$.each(this.openedPageHistory,function(index,data){
            if(url === data){
            	self.openedPageHistory.splice(index,1);
            }
        });
		var removePageXid = url;
		var toPageXid = this.openedPageHistory[this.openedPageHistory.length-1];
		
		var pageCloseEventData = {
			data:{
				closePageXid:removePageXid,
				toPageXid:toPageXid,
				closePageUrl:url
			}
		};
		self.fireEvent("onBeforePageClose",pageCloseEventData);
		
		if(toPageXid !== undefined){
			var loadPromise = this.loadPage({url:toPageXid});
			loadPromise.then(function(){
				self.pagesComp.remove(removePageXid, toPageXid);
				
				var pageCloseEventData = {
					data:{
						closePageXid:removePageXid,
						toPageXid:toPageXid,
						closePageUrl:url
					}
				};
				self.fireEvent("onAfterPageClose",pageCloseEventData);
				
				closePageDtd.resolve();
			});
		}else{
			var pageCloseEventData = {
				data:{
					closePageXid:removePageXid,
					toPageXid:toPageXid,
					closePageUrl:url
				}
			};
			self.fireEvent("onAfterPageClose",pageCloseEventData);
			
			this.pagesComp.remove(removePageXid, toPageXid);
			closePageDtd.resolve();
		}
		
		return closePageDtd.promise();
	};
	
	ShellImpl.prototype._afterShowPage = function(xid){
		var dtd = $.Deferred();
		if(xid === this.wingLeftXid && this.wingLeftComp && this.wingLeftComp.innerContainer){
			dtd.resolve({
				container:this.wingLeftComp.innerContainer
			});
		}else if(xid === this.wingRightXid && this.wingRightComp && this.wingRightComp.innerContainer){
			dtd.resolve({
				container:this.wingRightComp.innerContainer
			});
		}else{
			var _content = this.pagesComp.getContent(xid);
			if(_content && _content.innerContainer){
				dtd.resolve({
					container:_content.innerContainer
				});
			}else{
				dtd.resolve();
			}
		}
		return dtd.promise();
	};
	
	ShellImpl.prototype.showLeft = function(){
		this.showPage(this.wingLeftXid);
	};
	
	ShellImpl.prototype.showRight = function(){
		this.showPage(this.wingRightXid);
	};
	
	ShellImpl.prototype.showPage = function(params){
		params = this.formatParam(params);
		var xid = params.xid || params.url;
		var self = this;
		var showPageDtd;
		if(xid === this.wingLeftXid || xid === this.wingRightXid || this.pagesComp.has(xid)){
			showPageDtd = this._showPage(params).then(function(xid){
				return self._afterShowPage(xid);
			});
		}else{
			var loadPromise = this.loadPage(params,function(event){
				event.async = true;
				var viewLoadDtd = event.dtd;
				/**
				 * 可以在这里订阅的原因是
				 * 在contents中先调用回调函数
				 * 然后才触发自己的onActive事件
				 * 收到content的onActive事件才会出发windowContainer的onActive事件，这个时候内部routestate才会publish 
				 * 
				 */
				self.subscribeChange(xid);
				self.pagesComp.to(xid,function(){
					viewLoadDtd.resolve();
				});
			});
			showPageDtd = loadPromise.then(function(data){
				return self._showPage(params).then(function(xid){
					return self._afterShowPage(xid);
				});
			});
		}
		showPageDtd.then(function(data){
			if(xid === self.mainPageXid){
				self.mainPageLoadDtd.resolve();
			}
		});
		return showPageDtd; 
	};
	
	ShellImpl.prototype._showPage = function(params){
		var dtd = $.Deferred();
		var self = this;
		var xid = params.xid || params.url;
		if(xid === this.wingLeftXid){
			if(this.wingComp){
				this.wingComp.showLeft(function(){
					dtd.resolve(xid);
				});
			}else{
				dtd.reject(xid);
			}
		}else if(xid === this.wingLeftXid){
			if(this.wingComp){
				this.wingComp.showRight(function(){
					dtd.resolve(xid);
				});
			}else{
				dtd.reject(xid);
			}
		}else{
			var currentXid =  this.pagesComp.getActiveXid();
			self.subscribeChange(xid);
			if(xid === currentXid){
				this._showCenterPageAfter(xid,dtd);
			}else{
				this.pagesComp.to(xid,function(){
					/**
					 *  如果为首页情况，
					 *  在addContent时候 在contents的回调用获取currentActiveXid 是空的 
					 *  所以需要放到 to之后。 
					 */
					self.subscribeChange(xid);
					self._showCenterPageAfter(xid,dtd);
				});
			}
		}
		return dtd.promise();
	};
	
	ShellImpl.prototype._showCenterPageAfter = function(xid,dtd){
		var self = this;
		self.openedPageHistory.push(xid);
		if(!self.mainPageXid){
			self.mainPageXid = xid;
		}
		if(self.isSinglePage){
			self._removeOtherPages();
		}
		dtd.resolve(xid);
	};
	
	
	
	ShellImpl.prototype.getOpenedPages = function(){
		var pages = [];
		var contents = this.pagesComp.contents;
		for(var i = 0; i< contents.length; i++){
			var content = contents[i];
			pages.push(content.xid);
		}
		return pages;
	};
	
	
	ShellImpl.prototype._removeOtherPages = function(){
		var contents = [];
		var activeIndex = this.pagesComp.active;
		for(var i = 0; i< this.pagesComp.contents.length; i++){
			var content = this.pagesComp.contents[i];
			if(i === activeIndex) {
				contents.push(content);
			}else{
				if(content.xid === this.mainPageXid){
					contents.push(content);
				}else{
					Component.removeComponent(content);
				}
			}
		}
		this.pagesComp.contents = contents;
		if(contents.length === 2){
			this.pagesComp.active = 1;
		}else{
			this.pagesComp.active = 0;
		}
	},
	
	ShellImpl.prototype.getActivePage = function(params){
		var activeXid = this.pagesComp.getActiveXid();
		var $active = this.pagesComp.getContent(activeXid);
		return $active;
	};
	
	ShellImpl.prototype.dispatchChange = function(innerStateValue){
		var $active = this.getActivePage();
		if ($active && $active.innerContainer && $active.innerContainer.getInnerModel()) {
			$active.innerContainer.getInnerModel().postMessage({
				type : "routeState",
				newUrl : innerStateValue
			});
		}
	};

	ShellImpl.prototype.subscribeChange = function(){
		for ( var i in this.pagesComp.contents) {
			var content = this.pagesComp.contents[i];
			if (content.innerContainer) {
				content.innerContainer.off('onRouteStatePublish');
			}
		}
		var $active = this.getActivePage();
		if ($active && $active.innerContainer) {
			$active.innerContainer.on('onRouteStatePublish', function(event) {
				
				this.addRouteInnerItem($active.xid, event.hashbang, event.isReplace);
			}, this);
		}
	};
	
	ShellImpl.prototype.addRouteInnerItem = function(xid, hashbang, isReplace){
		this.$routeState.addInnerState(xid, '', hashbang);
		this.$routeState.publishState(isReplace);
	};
	
	ShellImpl.prototype.clearRouteItem = function() {
		this.$routeState.clearState();
	};
	
	ShellImpl.prototype.removeRouteItem = function(xid, name) {
		this.$routeState.removeState(xid, name);
	};

	ShellImpl.prototype.initRoute = function() {
		var self = this;
		this.$routeState.on('onRoute', function(event) {
			self.doRoute(event);
		}, this);
	};


	ShellImpl.prototype.doPagesRoute = function(event) {
		var self = this;
		var dtd = event.dtd;
		var innerStateValue;
		event.cancel = true;
		if (event.routeState == 'leave') {
			var leaveXid = event.xid;
			var $leave = this.pagesComp.getContent(leaveXid);
			if ($leave) {
				$leave.off('onRouteStatePublish');
			}
			dtd.resolve();
		} else if (event.routeState == 'enter') {
			event.async = true;
			//var paramValue = RouteUtil.getParamValue(event.param);
			innerStateValue = RouteUtil.getInnerStateValue(event.param);
			var xid = event.xid;
			var activeXid = this.pagesComp.getActiveXid();
			if (activeXid == xid) {
				this.dispatchChange(innerStateValue);
			} else {
				var showPromise = this.showPage(xid);
				showPromise.then(function() {
					dtd.resolve();
					self.dispatchChange(innerStateValue);
				});
			}
		} else if (event.routeState == 'update') {
			innerStateValue = RouteUtil.getInnerStateValue(event.param);
			this.dispatchChange(innerStateValue);
			dtd.resolve();
		}
	};
	
	/**
	 * 后续支持wing的路由能力
	ShellImpl.prototype.doWingRoute = function(event) {
		var self = this;
		var dtd = event.dtd;
		event.cancel = false;
		if (event.routeState == 'leave') {
			
		} else if (event.routeState == 'enter') {
			event.async = true;
			var paramValue = RouteUtil.getParamValue(event.param);
			var innerStateValue = RouteUtil.getInnerStateValue(event.param);
			var xid = event.xid;
			var activeXid = this.pagesComp.getActiveXid();
			if (activeXid == xid) {
				this.dispatchChange(innerStateValue);
			} else {
				var showPromise = this.showPage({url:xid});
				showPromise.then(function() {
					dtd.resolve();
					self.dispatchChange(innerStateValue);
				});
			}
		} else if (event.routeState == 'update') {
			var innerStateValue = RouteUtil.getInnerStateValue(event.param);
			this.dispatchChange(innerStateValue);
			dtd.resolve();
		}
	};
	*/

	ShellImpl.prototype.doRoute = function(event) {
		if(event.xid !== this.wingXid && this.uninterceptXids.indexOf(event.xid) === -1){
			var fakeEvent = {
				xid : event.xid,
				name : event.name,
				param : event.param,
				routeState : event.routeState,
				dtd : event.dtd
			};
			event.cancel = true;
			event.async = true;
			this.doPagesRoute(fakeEvent);
		}
	};
	
	return ShellImpl;
});