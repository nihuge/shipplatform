define(function(require){
	var $ = require("jquery");
	
	function checkImpl(){
		if(api.impl){
			return true;
		}
		return false;
	}
	
	function noopImpl(){
		var dtd = $.Deferred();
		return dtd.promise();
	}
	
	var api = {
		loadPage:function(){
			if(checkImpl()){
				return api.impl.loadPage.apply(api.impl,arguments);
			}else{
				return noopImpl(); 
			}
		},
		showPage:function(){
			if(checkImpl()){
				return api.impl.showPage.apply(api.impl,arguments);
			}else{
				return noopImpl(); 
			}
		},
		closePage:function(){
			if(checkImpl()){
				return api.impl.closePage.apply(api.impl,arguments);
			}else{
				return noopImpl();
			}
		},
		setIsSinglePage:function(isSinglePage){
			if(checkImpl()){
				api.impl.isSinglePage = isSinglePage;
			}
		}
	}
	return api;
});