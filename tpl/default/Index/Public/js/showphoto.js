$(function(){
	$('.showphoto').css('color','blue');
	$('.showphoto').mouseover(function(){
		$(this).css("cursor","pointer");
	});
	$('.showphoto').click(function(){
		var obj = $(this).parent().parent().find('a');
		var src = "";
		obj.each(function(e){
			$layer_href = $(this).attr('layer-href');
			$pid = $(this).attr('pid');
			src += '{"alt":"","pid":'+$pid+',"src":"'+$layer_href+'","thumb":"'+$layer_href+'"}';
			if((e+1) != obj.length){
				src += ',';
			}
		});
		var json = '{"title":"","id":"","start":0,"data":['+src+']}';
		json =  eval('(' + json + ')');
		layer.photos({
			photos: json
			,anim: 5 //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
		});
	})
})