<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>计量首页</title>
	<link rel="stylesheet" href="/shipplatform2/tpl/default/Index/Public/css/base.css">
	<!-- 分野分页样式 -->
	<link rel="stylesheet" type="text/css" href="/shipplatform2/tpl/default/Index/Public/css/page.css">
	<script src="/shipplatform2/tpl/default/Index/Public/js/jquery1.8.3.min.js"></script> 
    <script src="/shipplatform2/tpl/default/Index/Public/static/layer/layer.js"></script>
	<script src="/shipplatform2/Public/laydate/laydate.js"></script>
</head>
<body >
	<!-- 头部开始 -->
    <div class="head">
    <div style="width:1200px;margin :0px auto">
        <div class="headleft">
            <a href="javascript:;" onclick="down()">APP下载 </a>
            <?php if(!empty($_SESSION['user_info']['id'])): ?>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="<?php echo U('Login/loginout');?>">退出登录</a><?php endif; ?>
        </div>
        <?php if(!empty($_SESSION['user_info']['id'])): ?><div class="headright">欢迎您：<?php echo ($_SESSION['user_info']['username']); ?></div><?php endif; ?>
        <!-- 焦点相册存放位置 -->
        <div id='layer-photos' >
            
        </div>
    </div>
     <script type="text/javascript">
        /*APP下载*/
        function down(){
            var authors= [];
            authors.push("/shipplatform2/Public/down.png");
            str = '<span style="display:none">（';
            for (var i=0;i<authors.length;i++)
            {
                str += '<a layer-href="'+authors[i]+'" class="" rel="gallery">';
                str += '</a>';
            }

            str += '）</span>';

            $("#layer-photos").html(str);
            var obj = $("#layer-photos").find('a');
            var src = "";
            obj.each(function(e){
                $layer_href = $(this).attr('layer-href');
                $alt = $(this).attr('alt');
                // $pid = $(this).attr('pid');
                src += '{"alt":"'+$alt+'","pid":"","src":"'+$layer_href+'","thumb":"'+$layer_href+'"}';
                if((e+1) != obj.length){
                    src += ',';
                }
            });
            var json = '{"title":"","id":"","start":0,"data":['+src+']}';
            json =  eval('(' + json + ')');
            layer.photos({
                photos: json
               // ,anim: 5 //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
            });
        }
    </script>
</div>
<!-- 导航栏开始 -->
<div class="navwai">
    <div class="width">
        <div class='left'>
            <?php if(!empty($_SESSION['user_info']['id'])): ?><img src="<?php echo ($_SESSION['user_info']['logo'] == '') ? '/shipplatform2/tpl/default/Index/Public/image/noimg.png' : $_SESSION['user_info']['logo'];?>" style="height: 50px;margin:auto auto;vertical-align: middle;">
                <span><?php echo ($_SESSION['user_info']['firmname']); ?></span>
            <?php else: ?>
                <img src="/shipplatform2/tpl/default/Index/Public/image/login/img.png" style="height: 50px;margin:auto auto;vertical-align: middle;">
                <span>货物计量检验平台</span><?php endif; ?>
        </div>
        <div class='right'>
            <a class="dropbtn1 dropbtnhover" href="<?php echo U('Index/index');?>">首 &nbsp; 页</a>
            <div class="dropdown">
                <a class="dropbtn ">作业系统</a>
                <div class="dropdown-content">
                    <a href="<?php echo U('Liquid/index');?>">液货系统</a>
                    <a href="#">散货系统</a>
                </div>
            </div>
            <a class="dropbtn1 " href="<?php echo U('Search/index');?>">查询系统</a>
            <div class="dropdown">
                <a class="dropbtn ">个人中心</a>
                <div class="dropdown-content">
                    <?php if(!empty($_SESSION['user_info']['id'])): ?><a href="javascript:;" title="完善信息" class="editinfo">完善信息</a>
                    <a href="javascript:;" title="修改密码" class="editPass">修改密码</a>
                    <a href="<?php echo U('Login/loginout');?>">退出登录</a>
                    <?php else: ?>
                        <a href="<?php echo U('Login/login');?>">用户登录</a><?php endif; ?>
                </div>
            </div>
            <?php if($_SESSION['user_info']['pid'] == '0'): ?><div class="dropdown">
                <a class="dropbtn ">管理员设置</a>
                <div class="dropdown-content">
                    <a href="<?php echo U('Firm/msg');?>">公司信息</a>
                    <a href="<?php echo U('Ship/index');?>">船舶管理</a>
                    <a href="<?php echo U('User/index');?>">人员管理</a>
                    <a href="<?php echo U('Recharge/index');?>">充值记录</a>
                    <a href="<?php echo U('Consumption/index');?>">消费记录</a>
                </div>
            </div><?php endif; ?>
        </div>
    </div>
</div>
    <!-- 完善个人信息 -->
    <div class="editMask4">
        <div class="editBox">
            <div class="bar">完善个人信息</div>
            <ul class="pass">
                <li>
                    <label>姓名：</label>
                    <p><input type="text" name="username" placeholder="请输入姓名" class="i-box" required id="username" data-msg-required="请输入姓名" value="<?php echo ($_SESSION['user_info']['username']); ?>" maxlength="15"></p>
                </li>
                <li>
                    <label>电话：</label>
                    <p><input type="text" name="phone" placeholder="请输入电话" class="i-box" id="phone" required data-msg-required="请输入电话" value="<?php echo ($_SESSION['user_info']['phone']); ?>"  maxlength="16"></p>
                </li>
            </ul>
            <div class="bar">
                <input type="submit" value="取&nbsp;消" class="mmqx passbtn">
                <input type="submit" onclick="editi()"  value="确&nbsp;定" class="mmqd passbtn"> 
            </div>
        </div>
        <script>
            $(document).on("click",".editinfo",function(){
                event.preventDefault();
                $('.editMask4').addClass('is-visible3');
                $('.editMask4').find(".tip_info i").remove()
            })
            $('.editMask4').on('click', function(event){
                if($(event.target).is('.mmqx') || $(event.target).is('.editMask4') ) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function editi() {
                var username = $('#username').val();
                var phone = $('#phone').val();
                $.ajax({
                    url: "<?php echo U('User/editinfo');?>",
                    type: "POST",
                    data: {
                        "username": username,
                        "phone": phone
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.state == 1) {
                            layer.msg(data.message, {icon: 1});
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            layer.msg(data.message, {icon: 5});
                        }
                    }
                });
            }
        </script>
    </div>
    <!--修改密码-->
    <div class="editMask">
        <div class="editBox">
            <div class="bar">修改密码</div>
            <ul class="pass">
                <li>
                    <label>原密码：</label>
                    <p><input type="text" name="oldpass" placeholder="请输入原密码" class="i-box" required id="oldpass" data-msg-required="请输入原密码"></p>
                </li>
                <li>
                    <label>新密码：</label>
                    <p><input type="text" name="newpass" placeholder="请输入新密码" class="i-box" id="newpass" required data-msg-required="请输入新密码"></p>
                </li>
                <li>
                    <label>确认密码：</label>
                    <p><input type="text" name="newpass2" placeholder="请确认新密码" class="i-box" id="newpass2" required equalTo="#newpass" data-msg-required="请确认新密码"></p>
                </li>  
            </ul>
            <div class="bar">
                <input type="submit" value="取&nbsp;消" class="mmqx passbtn">
                <input type="submit" onclick="changepwd()"  value="确&nbsp;定" class="mmqd passbtn"> 
            </div>
        </div>
        <script>
            //修改密码
            $(document).on("click",".editPass",function(){
                event.preventDefault();
                $('.editMask').addClass('is-visible3');
                $('.editMask').find(".pass input").val("");
                $('.editMask').find(".tip_info i").remove()
            })
            $('.editMask').on('click', function(event){
                if($(event.target).is('.mmqx') || $(event.target).is('.editMask') ) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function changepwd() {
                var oldpwd = $('#oldpass').val();
                var newpwd = $('#newpass').val();
                var repeatpwd = $('#newpass2').val();

                $.ajax({
                    url: "<?php echo U('User/changepwd');?>",
                    type: "POST",
                    data: {
                        "oldpwd": oldpwd,
                        "newpwd": newpwd,
                        "repeatpwd": repeatpwd,
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.state == 1) {
                            layer.msg(data.message, {icon: 1});
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            layer.msg(data.message, {icon: 5});
                        }
                    }
                });
            }
        </script>
    </div>
        <!-- 底部结束 -->
    <script>
        //点击弹窗取消按钮和除弹窗外其它地方关闭弹窗
        $('.mask').on('click', function(event){
            if($(event.target).is('.quxiao') || $(event.target).is('.mask') ) {
                event.preventDefault();
                $(this).removeClass('is-visible');
                $(this).removeClass('editPass');
            }
        });
        
        //按键盘上 ESC 键关闭弹窗
        $(document).keyup(function(event){
            if(event.which=='27'){
                $('.mask').removeClass('is-visible');
                $('.mask2').removeClass('is-visible2');
                $('.editMask').removeClass('is-visible3');
                $(this).removeClass('editPass');
            }
        });
    </script>
    <!-- 头部结束 -->
    <!-- 导航栏结束 -->
    <!-- 中间开始-->
    <div class="center">
		 
	<link rel="stylesheet" href="/shipplatform2/tpl/default/Index/Public/css/index.css">
    <div class='article'>
        <div class='article1'>
            <img src="/shipplatform2/tpl/default/Index/Public/image/article.png">
		</div>
        <div class='article2'>
            <div id="marquee1">
                <ul>
                	<?php if(is_array($data["data"])): $i = 0; $__LIST__ = $data["data"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><li>
                    	<a href="<?php echo U('Article/msg',array('aid'=>$v['aid']));?>">
                        	<div class='kongbai'>
                        		<img src="<?php echo ($v['pic_path']); ?>" / >
                        		<span><?php echo ($v['title']); ?></span>
                        	</div>
                    	</a>
                    </li><?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
            <script src="/shipplatform2/tpl/default/Index/Public/js/Marquee.js"></script>
            <script>
            // 一次横向滚动一个
            $('#marquee1').kxbdSuperMarquee({
                distance: 380,
                time: 3,
                btnGo: { left: '#goL', right: '#goR' },
                direction: 'left'
            });
            </script>
        </div>
    </div>
    <div class='result'>
    	<div class='article1'>
            <img src="/shipplatform2/tpl/default/Index/Public/image/result.png">
		</div>
		<div class='result1'>
			<div class='result1_1'>
				<a href="<?php echo U('Liquid/index');?>">
					<img class='imager' src="/shipplatform2/tpl/default/Index/Public/image/Liquid-carg-ship.png">
				</a>
			</div>
			<div class='result1_2'>
				<a href="">
					<img class='imager' src="/shipplatform2/tpl/default/Index/Public/image/Bulk-carrier.png">
				</a>
			</div>			
		</div>
    </div>
    <div class='search'>
    	<div class='article1'>
            <img src="/shipplatform2/tpl/default/Index/Public/image/search.png">
		</div>
		<div id="search1">
            <ul>
                <li>
                	<a href="<?php echo U('Search/jian');?>" class='Quality'>
                		<img src="/shipplatform2/tpl/default/Index/Public/image/Quality_inspection.png"/ >
                		<p>检验公司</p>
                	</a>
                </li>
                <li>
                	<a href="<?php echo U('Search/chuan');?>" class='Shipping'>
                		<img src="/shipplatform2/tpl/default/Index/Public/image/Shipping_company.png"/>
                		<p>船舶公司</p>
                	</a>
                </li>
                <li>
                	<a href="<?php echo U('Search/ship');?>" class='ship'>
                		<img src="/shipplatform2/tpl/default/Index/Public/image/ship.png"/ >
                		<p>船舶</p>
                	</a>
                </li>
            </ul>
            <script>
            	$(document).ready(function() {
					$(".Quality").hover(function() {
					    $(this).find('img').attr('src', '/shipplatform2/tpl/default/Index/Public/image/Quality_inspection_hover.png');
					}, function() {
					    $(this).find('img').attr('src', '/shipplatform2/tpl/default/Index/Public/image/Quality_inspection.png');
					});

					$(".Shipping").hover(function() {
					    $(this).find('img').attr('src', '/shipplatform2/tpl/default/Index/Public/image/Shipping_company_hover.png');
					}, function() {
					    $(this).find('img').attr('src', '/shipplatform2/tpl/default/Index/Public/image/Shipping_company.png');
					});

					$(".ship").hover(function() {
					    $(this).find('img').attr('src', '/shipplatform2/tpl/default/Index/Public/image/ship_hover.png');
					}, function() {
					    $(this).find('img').attr('src', '/shipplatform2/tpl/default/Index/Public/image/ship.png');
					});
				});
            </script>
        </div>
    </div>
    <div style="height: 50px;"></div>

    </div>
    <!-- 中间结束-->
    <!-- 底部开始 -->
    <div class="footer">
    <span>版权所有 <a href="http://www.xzitc.com/" target="_blank">南京携众信息科技有限公司</a> @2018-2018</span>
</div>
</body>
</html>