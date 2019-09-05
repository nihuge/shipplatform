<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>人员管理</title>
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
                <a class="dropbtn dropbtnhover">管理员设置</a>
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
	<link rel="stylesheet" href="/tpl/default/Index/Public/css/ship.css">
	<link rel="stylesheet" href="/tpl/default/Index/Public/css/checkbox.css">

	<div style="background-color: #f2f2f2;padding: 6px 30px;">
        <div class='nav'>
            管理员设置>人员管理
        </div>
    </div>
    <div class='xia'>
        <hr style="width: 870px" class='hr'>
        <button class="addbut1" id='addbut1'>新建人员</button>
        <hr style="width: 60px" class='hr'>
        <table id="customers">
            <tr>
                <th>账号</th>
                <th>用户名</th>
                <th>联系电话</th>
                <th>所属公司</th>
                <th>重置密码</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
                <td><?php echo ($v['title']); ?></td>
                <td><?php echo ($v['username']); ?></td>
                <td><?php echo ($v['phone']); ?></td>
                <td><?php echo ($v['firmname']); ?></td>
                <td><a href="javascript:;" onclick="resetpwd('<?php echo ($v['id']); ?>')" style="color:red;">重置密码</a></td>
                <?php if($v['status'] == 2): ?><td><a href="javascript:;" onclick="changestatus('<?php echo ($v['id']); ?>','1')" style="color:red">解除冻结</a></td>
                    <?php else: ?>
                    <td><a href="javascript:;" onclick="changestatus('<?php echo ($v['id']); ?>','2')" style="color:black">冻结用户</a></td><?php endif; ?>
                <td>
                    <a href="javascript:;" class='aa1' onclick="edit('<?php echo ($v['id']); ?>','<?php echo ($v['firmid']); ?>')">修改</a>&nbsp;&nbsp;
                    <a href="javascript:;" class='aa2' onclick="configSearch('<?php echo ($v['id']); ?>')">分配查询权限</a>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            <tr style="background-color: #fff">
            	<td colspan="7" style="border-right: 0px;"><?php echo ($page); ?></td>
            </tr>
        </table>
    </div>

    <!-- 新建人员 -->
    <div class='editMask5'>
        <div class='editBox1'>
            <div class='bar'>新建人员</div>
            <div class='bar1'>基本信息</div>
            <ul class='pass'>
                <input type="hidden" name='pid' id='pid' value="<?php echo ($_SESSION['user_info']['id']); ?>">
                <li>
                    <label>账号</label>
                    <p><input type='text' name='title' placeholder='请输入账号' class='i-box' id='title' maxlength='15'></p>
                </li>
                <li>
                    <label>用&nbsp;户&nbsp;名</label>
                    <p>
                    	<input type='text' name='username' placeholder='请输入用户名' class='i-box' id='nameuser' maxlength='15'>
                    </p>
                </li> 
                <li>
                    <label>联系电话</label>
                    <p><input type='text' name='phone' placeholder='请输入联系电话' class='i-box' id='phones' maxlength='15'></p>
                </li>
            </ul>
            <div class='bar1'>操作权限</div>
            <ul class='pass1'>
                <?php if(is_array($shiplist)): $i = 0; $__LIST__ = $shiplist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><li>
                    <p>
                    	<label><input type='checkbox' name='operation_jur' value="<?php echo ($v['id']); ?>" class='regular-checkbox'>&nbsp;&nbsp;<?php echo ($v['shipname']); ?></label>
                    </p>
                </li><?php endforeach; endif; else: echo "" ;endif; ?>
            </ul>
            <div class='bar'>
                <input type='submit' value='取&nbsp;消' class='mmqx passbtn'>
                <input type='submit' onclick='addr()'  value='提&nbsp;交' class='mmqd passbtn'> 
            </div>
        </div>
        <script>
            $(document).on("click","#addbut1",function(){
                event.preventDefault();
                $('.editMask5').addClass('is-visible3');
                $('.editMask5').find(".tip_info i").remove()
            })
            $('.editMask5').on('click', function(event){
                if($(event.target).is('.mmqx') || $(event.target).is('.editMask5') ) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function addr() {
                var pid = $('#pid').val();
                var title = $('#title').val();
                var username = $('#nameuser').val();
                var phone = $('#phones').val(); 
                var operation_jur = [];
              
                var chk_value =[]; 
                $('input[name="operation_jur"]:checked').each(function(){ 
                    chk_value.push($(this).val()); 
                }); 

                $.ajax({
                    url: "<?php echo U('User/add');?>",
                    type: "POST",
                    data: {
                        'pid': pid,
                        'title': title,
                        'username': username,
                        'phone': phone,
                        'operation_jur': chk_value,
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

    <!-- 修改人员 -->
    <div class="editMask6">
        <div class="editBox1" id='editBox1'>
            
        </div>
        <script>
            $('.editMask6').on('click', function(event){
                if($(event.target).is('.mmqx') || $(event.target).is('.editMask6') ) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function edit(id){
                $.ajax({
                    url: "<?php echo U('User/usermsg');?>",
                    type: "POST",
                    data: {
                        'id': id,
                    },
                    dataType: "json",
                    success: function(data) {
                    	console.log(data);
                        if (data.state == 1) {
                            $("#editBox1").html(data.content);
                            event.preventDefault();
                            $('.editMask6').addClass('is-visible3');
                            $('.editMask6').find(".tip_info i").remove()                  
                        } else {
                            layer.msg(data.message, {icon: 5});
                        }
                    }
                });

            }
            function editr() {
                var id = $('#userid').val();
                var username = $('#nameuser1').val();
                var phone = $('#phones1').val(); 
                var operation_jur = [];
              
                var chk_value =[]; 
                $('input[name="operation_jur1"]:checked').each(function(){ 
                    chk_value.push($(this).val()); 
                }); 
                $.ajax({
                    url: "<?php echo U('User/edit');?>",
                    type: "POST",
                    data: {
                        'id': id,
                        'username': username,
                        'phone': phone,
                        'operation_jur': chk_value,
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

    <!-- 分配查询权限 -->
    <div class="editMask7">
        <div class="editBox1">
            <div class="bar">分配查询权限</div>
            <div class='bar2' id='bar2'>
                                
            </div>
            <div class="bar">
                <input type="submit" value="取&nbsp;消" class="mmqx passbtn">
                <input type="submit" onclick="adds()"  value="提&nbsp;交" class="mmqd passbtn"> 
            </div>
        </div>
        <script>
            $('.editMask7').on('click', function(event){
                if($(event.target).is('.mmqx') || $(event.target).is('.editMask7') ) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function configSearch(id){
                $.ajax({
                    url: "<?php echo U('User/configSearch');?>",
                    type: "POST",
                    data: {
                        'id': id,
                    },
                    dataType: "json",
                    success: function(data) {
                        console.log(data);
                        if (data.state == 1) {
                            $("#bar2").html(data.content);
                            event.preventDefault();
                            $('.editMask7').addClass('is-visible3');
                            $('.editMask7').find(".tip_info i").remove()                  
                        } else {
                            layer.msg(data.message, {icon: 5});
                        }
                    }
                });
            }

            function adds() {
                var id = $('#iduser').val();
              
                var search_jur =[]; 
                $('input[name="search_jur"]:checked').each(function(){ 
                    search_jur.push($(this).val()); 
                }); 
                $.ajax({
                    url: "<?php echo U('User/searchconfig');?>",
                    type: "POST",
                    data: {
                        'id': id,
                        'search_jur': search_jur,
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
    <script type="text/javascript">
    	//重置密码
        function resetpwd(id) {
            layer.confirm('您确定要重置密码吗？', {
                btn: ['确定', '取消'] //按钮
            }, function() {
                $.ajax({
                    url: "/index.php?s=/User/resetpwd",
                    type: "POST",
                    data: {
                        "id": id
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.state == 1) {
                            layer.msg(data.msg, {icon: 1});
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                        }
                    }
                });
            })
        }

        //修改状态
        function changestatus(id, status) {
            layer.confirm('您确定要修改状态吗？', {
                btn: ['确定', '取消'] //按钮
            }, function() {
                $.ajax({
                    url: "/index.php?s=/User/changestatus",
                    type: "POST",
                    data: {
                        "id": id,
                        "status": status
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.state == 1) {
                            layer.msg(data.msg, {icon: 1});
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                        }
                    }
                });
            })
        }
	</script>

    </div>
    <!-- 中间结束-->
    <!-- 底部开始 -->
    <div class="footer">
    <span>版权所有 <a href="http://www.xzitc.com/" target="_blank">南京携众信息科技有限公司</a> @2018-2018</span>
</div>
</body>
</html>