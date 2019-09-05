<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>作业详情</title>
	<link rel="stylesheet" href="/tpl/default/Index/Public/css/base.css">
	<!-- 分野分页样式 -->
	<link rel="stylesheet" type="text/css" href="/tpl/default/Index/Public/css/page.css">
	<script src="/tpl/default/Index/Public/js/jquery1.8.3.min.js"></script> 
    <script src="/tpl/default/Index/Public/static/layer/layer.js"></script>
	<script src="/Public/laydate/laydate.js"></script>
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
            authors.push("/Public/down.png");
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
            <?php if(!empty($_SESSION['user_info']['id'])): ?><img src="<?php echo ($_SESSION['user_info']['logo'] == '') ? '/tpl/default/Index/Public/image/noimg.png' : $_SESSION['user_info']['logo'];?>" style="height: 50px;margin:auto auto;vertical-align: middle;">
                <span><?php echo ($_SESSION['user_info']['firmname']); ?></span>
            <?php else: ?>
                <img src="/tpl/default/Index/Public/image/login/img.png" style="height: 50px;margin:auto auto;vertical-align: middle;">
                <span>货物计量检验平台</span><?php endif; ?>
        </div>
        <div class='right'>
            <a class="dropbtn1 " href="<?php echo U('Index/index');?>">首 &nbsp; 页</a>
            <div class="dropdown">
                <a class="dropbtn dropbtnhover">作业系统</a>
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
		 
    <link rel="stylesheet" href="/tpl/default/Index/Public/css/liquid.css">

    <script src="/tpl/default/Index/Public/js/showphoto.js"></script>
    <script type="text/javascript">
    $(function() {
        $(".divcabin .panel:first").next().show();
        $(".panel .look").click(function() {
            var images=['<img src="/tpl/default/Index/Public/image/w-d.png">','<img src="/tpl/default/Index/Public/image/w-u.png">'];
            var display =$(this).parent().next().css('display');
            if(display == 'block'){
                // 设置显示
                $(this).html(images[0]); 
            }else{
                $(this).html(images[1]); 
            }
            $(this).parent().next().toggle(300);
        })
    })
    </script>
	<div style="background-color: #f2f2f2;padding: 6px 30px;">
        <div class='nav'>
            作业系统>液货船系统>作业数据详情
        </div>
    </div>
    <div class='rmsg'>
    	<div class='msgs'>
    		<p class='pp1'>船名</p>
    		<p class='pp2'><?php echo ($content['shipname']); ?></p>
    	</div>
    	<div class='msgs'>
    		<p class='pp1'>操作人</p>
    		<p class='pp2'><?php echo ($content['username']); ?></p>
    	</div>
    	<div class='msgs'>
    		<p class='pp1'>货重(吨)</p>
    		<p class='pp2'><?php echo ($content['weight']); ?></p>
    	</div>
    	<?php if(is_array($personality)): foreach($personality as $k=>$vo): ?><div class='msgs'>
    		<p class='pp1'><?php echo ($vo['title']); ?></p>
    		<p class='pp2'><?php echo ($vo['value']); ?></p>
    	</div><?php endforeach; endif; ?>
    </div>
    <div style='height: 10px;width: 100%;'></div>
    <div class='cabinmsg'>
    	<div class='chi'>
    		<div class='shui'>
    			<p class='pp'>装卸前数据</p>
    			<div class='chishuimsg'>
    				<p class='ppp1'>艏吃水</p>
    				<p class='pp2'><?php echo ($content['qian']['forntleft']); ?>
                    <?php
 echo '<span>'; foreach($content['firstfiles1'] as $key => $v){ echo '<a layer-href="'.$v.'" rel="gallery"><span class="showphoto">'; if ($key == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                    </p>
    			</div>
    			<div class='chishuimsg'>
    				<p class='ppp1'>艉吃水</p>
    				<p class='pp2'><?php echo ($content['qian']['afterleft']); ?>
                    <?php
 $n = count($content['tailfiles1']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$content['tailfiles1'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                    </p>
    			</div>
    			<div class='chishuimsg'>
    				<p class='ppp1'>实验室密度</p>
    				<p class='pp2'><?php echo ($content['qiandensity']); ?></p>
    			</div>
    			<div class='chishuimsg'></div>
    		</div>
    		<div class='shui'>
    			<p class='pp'>装卸后数据</p>
    			<div class='chishuimsg'>
    				<p class='ppp1'>艏吃水</p>
    				<p class='pp2'><?php echo ($content['hou']['forntleft']); ?>
                    <?php
 $n = count($content['firstfiles2']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$content['firstfiles2'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                    </p>
    			</div>
    			<div class='chishuimsg'>
    				<p class='ppp1'>艉吃水</p>
    				<p class='pp2'><?php echo ($content['hou']['afterleft']); ?>
                    <?php
 $n = count($content['tailfiles2']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$content['tailfiles2'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                    </p>
    			</div>
    			<div class='chishuimsg'>
    				<p class='ppp1'>实验室密度</p>
    				<p class='pp2'><?php echo ($content['houdensity']); ?></p>
    			</div>
    			<div class='chishuimsg'></div>
    		</div>
    	</div>
    	<div class='cabindiv'>
	    	<div class='divcabin'>
	    		<?php if(is_array($qian)): $i = 0; $__LIST__ = $qian;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i; $i=0;?>
                <div class="panel">
                    <div class="head1">
                        舱名：<?php echo ($vo['cabinname']); ?>
                        <span class='look'>
                            <img src="/tpl/default/Index/Public/image/w-d.png">
                        </span>
                    </div>
                    <div class="contentss">
                        <table border="1" cellspacing="0" cellpadding="0" class="table">
                        	<tr>
                        		<td>
                        			<p class='pp1'>空高</p>
    								<p class='pp2'><?php echo ($vo['ullage']); ?>
                                    <?php
 $n = count($vo['ullageimg']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$vo['ullageimg'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                                    </p>
                        		</td>
                        		<td>
                        			<p class='pp1'>实高</p>
    								<p class='pp2'><?php echo ($vo['sounding']); ?>
                                    <?php
 $n = count($vo['soundingimg']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$vo['soundingimg'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                                    </p>
                        		</td>
                        	</tr>
                        	<tr>
                        		<td>
                        			<p class='pp1'>温度</p>
    								<p class='pp2'><?php echo ($vo['temperature']); ?>
                                    <?php
 $n = count($vo['temperatureimg']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$vo['temperatureimg'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                                    </p>
                        		</td>
                        		<td>
                        			<p class='pp1'>纵倾修正值</p>
    								<p class='pp2'><?php echo ($vo['listcorrection']); ?></p>
                        		</td>
                        	</tr>
                        	<tr>
                        		<td>
                        			<p class='pp1'>修正后空距</p>
    								<p class='pp2'><?php echo ($vo['correntkong']); ?></p>
                        		</td>
                        		<td>
                        			<p class='pp1'>容量</p>
    								<p class='pp2'><?php echo ($vo['standardcapacity']); ?></p>
                        		</td>
                        	</tr>
                        	<tr>
                        		<td>
                        			<p class='pp1'>体积修正系数</p>
    								<p class='pp2'><?php echo ($vo['volume']); ?></p>
                        		</td>
                        		<td>
                        			<p class='pp1'>膨胀修正系数</p>
    								<p class='pp2'><?php echo ($vo['expand']); ?></p>
                        		</td>
                        	</tr>
                        	<tr>
                        		<td>
                        			<p class='pp1'>标准容量</p>
    								<p class='pp2'><?php echo ($vo['cabinweight']); ?></p>
                        		</td>
                        		<td>
                        			<p class='pp1'></p>
    								<p class='pp2'></p>
                        		</td>
                        	</tr>
                        </table>
                    </div>
                </div>
                <?php $i++; endforeach; endif; else: echo "" ;endif; ?>
	    	</div>
	    	<div class='divcabin'>
	    		<?php if(is_array($hou)): $i = 0; $__LIST__ = $hou;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i; $i=0;?>
                <div class="panel">
                    <div class="head1">
                        舱名：<?php echo ($vo['cabinname']); ?>
                        <span class='look'>
                            <img src="/tpl/default/Index/Public/image/w-d.png">
                        </span>
                    </div>
                    <div class="contentss">
                        <table border="1" cellspacing="0" cellpadding="0"  class="table">
                        	<td>
                                    <p class='pp1'>空高</p>
                                    <p class='pp2'><?php echo ($vo['ullage']); ?>
                                    <?php
 $n = count($vo['ullageimg']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$vo['ullageimg'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                                    </p>
                                </td>
                                <td>
                                    <p class='pp1'>实高</p>
                                    <p class='pp2'><?php echo ($vo['sounding']); ?>
                                    <?php
 $n = count($vo['soundingimg']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$vo['soundingimg'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class='pp1'>温度</p>
                                    <p class='pp2'><?php echo ($vo['temperature']); ?>
                                    <?php
 $n = count($vo['temperatureimg']); echo '<span>'; for($i=0;$i<$n;$i++) { echo '<a layer-href="'.$vo['temperatureimg'][$i].'" class="" rel="gallery"><span class="showphoto">'; if ($i == 0) { echo '<img src="/tpl/default/Index/Public/image/check.png" style="vertical-align: middle;margin-left:7px;">'; } echo '</span></a>'; } echo '</span>'; ?>
                                    </p>
                                </td>
                                <td>
                                    <p class='pp1'>纵倾修正值</p>
                                    <p class='pp2'><?php echo ($vo['listcorrection']); ?></p>
                                </td>
                            </tr>
                        	<tr>
                        		<td>
                        			<p class='pp1'>修正后空距</p>
    								<p class='pp2'><?php echo ($vo['correntkong']); ?></p>
                        		</td>
                        		<td>
                        			<p class='pp1'>容量</p>
    								<p class='pp2'><?php echo ($vo['standardcapacity']); ?></p>
                        		</td>
                        	</tr>
                        	<tr>
                        		<td>
                        			<p class='pp1'>体积修正系数</p>
    								<p class='pp2'><?php echo ($vo['volume']); ?></p>
                        		</td>
                        		<td>
                        			<p class='pp1'>膨胀修正系数</p>
    								<p class='pp2'><?php echo ($vo['expand']); ?></p>
                        		</td>
                        	</tr>
                        	<tr>
                        		<td>
                        			<p class='pp1'>标准容量</p>
    								<p class='pp2'><?php echo ($vo['cabinweight']); ?></p>
                        		</td>
                        		<td>
                        			<p class='pp1'></p>
    								<p class='pp2'></p>
                        		</td>
                        	</tr>
                        </table>
                    </div>
                </div>
                <?php $i++; endforeach; endif; else: echo "" ;endif; ?>
	    	</div>
	    </div>
	    <div class='remarks'>
	    	<p class='pp3'>备注</p>
	    	<p class='pp4'><?php echo ($content['remark']); ?></p>
	    </div>
	    <div class='baobiao'>	
			<a href="/index.php?s=/Liquid/baobiao/resultid/<?php echo ($content['id']); ?>" class="butt1">预览打印报表</a>
	    </div>
    </div>

    </div>
    <!-- 中间结束-->
    <!-- 底部开始 -->
    <div class="footer">
    <span>版权所有 <a href="http://www.xzitc.com/" target="_blank">南京携众信息科技有限公司</a> @2018-2018</span>
</div>
</body>
</html>