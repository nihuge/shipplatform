<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>跳转提示</title>
</head>

<body>
<div width:"100%" style="height:77px; background:url(/shipplatform2/tpl/default/Index/Public/image/tishi/bg_r_top.png) no-repeat center bottom;" ></div>
<div width:"100%" style="height:100px; background:url(/shipplatform2/tpl/default/Index/Public/image/tishi/ditu.png) no-repeat center;text-align: center;"><?php echo($error); ?></div>
<div width:"100%" style="height:200px; background:url(/shipplatform2/tpl/default/Index/Public/image/tishi/bg_r.png) no-repeat center;" ></div>
<div align="center"; style="font-size:18px; color:#7F7F7F; font-family:'微软雅黑'"><p>页面自动<a id="href" href="<?php echo($jumpUrl); ?>">跳转</a>&nbsp;&nbsp;&nbsp;等待时间：<b id="wait"><?php echo($waitSecond); ?></b></p></div>

</body>
<script type="text/javascript">
(function(){
	var wait = document.getElementById('wait'),href = document.getElementById('href').href;
	var interval = setInterval(function(){
	var time = --wait.innerHTML;
	if(time == 0) {
	location.href = href;
	clearInterval(interval);
	};
	}, 1000);
})();
</script>
</html>