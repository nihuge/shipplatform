define(function(require){
	var $ = require('jquery');
	var Browser = require('$UI/system/lib/base/browser');
	if(Browser.isAndroid){
		var viewportHeight = $(window).height();
	    function isInViewport (el) {
	        var rect = el.getBoundingClientRect();
	        return (
	                rect.top >= 0 &&
	                rect.left >= 0 &&
	                rect.bottom <= $(window).height() &&
	                rect.right <= $(window).width()
	        );
	    }
	    $('<style type="text/css">.android_soft_input_cover_bug{z-index:10000;position:fixed !important;width:100% !important;left:0 !important;bottom:0 !important;margin:0 !important;}</style>').appendTo('body');
	    $(window).on('resize',function(){
	        var newviewportHeight = $(window).height();
	        var newviewportWidth = $(window).width();
	        // only fix Portrait condition
	        if(viewportHeight - newviewportHeight > 150 && viewportHeight - newviewportWidth > 0){
	            var activeElement = document.activeElement;
	            if(activeElement && /^(INPUT|TEXTAREA)$/.test(activeElement.nodeName)){
	                if(!isInViewport(activeElement)){
	                    $(document.activeElement).addClass('android_soft_input_cover_bug');
	                }
	            }
	        }else if(Math.abs(viewportHeight - newviewportHeight) < 50){
	            $('.android_soft_input_cover_bug').removeClass('android_soft_input_cover_bug');
	        }
	    });
	}
	
	var isAndroid5 = (navigator.userAgent.indexOf("Crosswalk") >= 0) || (navigator.userAgent.indexOf("Android 5.") >= 0);
	if(isAndroid5){
		if(($(window).height() >= $('body').height()) && $('body').css('overflowY') === "visible"){
			$('body').css('overflowY','hidden');
		}
	}
	
	return true;
});