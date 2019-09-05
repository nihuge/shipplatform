<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>登陆</title>
	<link rel="stylesheet" href="/tpl/default/Index/Public/css/login.css">
	<script src="/tpl/default/Index/Public/js/jquery1.8.3.min.js" type="text/javascript"></script>
	<script src="/tpl/default/Index/Public/static/layer/layer.js" type="text/javascript"></script>
</head>
<body>
	<div class="outer">
		<div class="topBox">
			<div class="box">
				<div class="boxleft">
					<div class="imgs">
						<img src="/tpl/default/Index/Public/image/login/img.png">
					</div>
					<div class="height1"></div>
					<div class="kong1"></div>
					<div class="logofont">货物计量检验平台</div>
				</div><div class="boxright">
					<img src="/tpl/default/Index/Public/image/login/s-date.png">
					<div class='times'><?php echo ($h >= 12) ? '下午好' : '上午好';?></div>
					<div class="height2"></div>
					<div class="kong2"></div>
					<div class='times'>今天是<span><?php echo ($year); ?></span>年<span><?php echo ($mouth); ?></span>月<span><?php echo ($d); ?></span>日</div>
					
				</div>
			</div>
		</div>
		<div class="centerBox">
			<div class="box">
				<div class='login_box1'></div>
				<div class='login_box_1'>
					<form  method="post" action="<?php echo U('Login/login');?>">
				        <ul class="form">
				        	<li class="li1">账&nbsp;&nbsp;号</li>
				            <li class="li">
				                <input type="text" name="title" placeholder="用户名" class="i-box" required autocomplete='tel'/>
				            </li>
				            <li class="li1">密&nbsp;&nbsp;码</li>
				            <li class="li2">
				                <input type="password" name="pwd" placeholder="密码" class="i-box" required autocomplete='tel'/>
				            </li>
				            <li class="li3">
				            	<input class="submitBtn" type="submit" value='登 陆'>
				            </li>
				            <li class='li4'>没有账号？<a href="<?php echo U('Login/regist');?>">点击注册</a></li>
				        </ul>
				    </form>
				</div>
			</div>
		</div>
	</div>
</body>
</html>