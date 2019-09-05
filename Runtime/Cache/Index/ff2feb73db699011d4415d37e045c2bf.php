<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>船舶管理</title>
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
    <!-- <link rel="stylesheet" href="/tpl/default/Index/Public/css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="/tpl/default/Index/Public/css/checkbox.css">

    <div style="background-color: #f2f2f2;padding: 6px 30px;">
        <div class='nav'>
            管理员设置>船舶管理
        </div>
    </div>
    <div class='xia'>
        <hr style="width: 710px">
        <button class="addbut" id='addbut1'>新建船舶</button>
        <button class="addbut" id='addbut2'>新建船舱</button>
        <hr style="width: 50px">
        <table id="customers">
            <tr>
                <th>船名</th>
                <th>膨胀倍数</th>
                <th>舱总数</th>
                <th>管线容量</th>
                <th>底量测量孔</th>
                <th>纵横倾修正表</th>
                <th>操作</th>
            </tr>
            <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($v['shipname']); ?></td>
                    <td><?php echo ($v['coefficient']); ?></td>
                    <td><?php echo ($v['cabinnum']); ?></td>
                    <td>
                        <?php if($v['is_guanxian'] == '1'): ?>包含
                            <?php elseif($v['is_guanxian'] == '2'): ?>
                            未包含<?php endif; ?>
                    </td>
                    <td>
                        <?php if($v['is_diliang'] == '1'): ?>有
                            <?php elseif($v['is_diliang'] == '2'): ?>
                            无<?php endif; ?>
                    </td>
                    <td>
                        <?php if($v['suanfa'] == 'a'): ?>无
                            <?php elseif($v['suanfa'] == 'b'): ?>
                            有
                            <?php elseif($v['suanfa'] == 'c'): ?>
                            有<?php endif; ?>
                    </td>
                    <td>
                        <a href="javascript:;" onclick="edit(<?php echo ($v['id']); ?>)" class='aa1'>修改</a>
                        &nbsp;&nbsp;
                        <a href="<?php echo U('Cabin/index',array('shipid'=>$v['id']));?>" class='aa2'>船舱管理</a>
                    </td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            <tr style="background-color: #fff">
                <td colspan="7" style="border-right: 0px;"><?php echo ($page); ?></td>
            </tr>
        </table>
    </div>

    <!-- 新建船舶 -->
    <div class="editMask5">
        <div class="editBox1">
            <div class="bar">新建船舶</div>
            <div class="bar1">船舶信息</div>
            <ul class="pass">
                <li>
                    <label>船舶公司</label>
                    <p>
                        <select name="firmid" id='firmid' class=''>
                            <option value="">请选择公司</option>
                            <?php if(is_array($firmlist)): $i = 0; $__LIST__ = $firmlist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><option value="<?php echo ($v['id']); ?>"><?php echo ($v['firmname']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
                        </select>
                    </p>
                </li>
                <li>
                    <label>船&nbsp;名</label>
                    <p><input type="text" name="shipname" placeholder="请输入船名" class="i-box" id="shipname"
                              maxlength="12"></p>
                </li>
                <li>
                    <label>膨胀倍数</label>
                    <p>
                        <input type="text" name="coefficient" placeholder="请输入膨胀倍数" class="i-box" id="coefficient"
                               maxlength="3">
                        <img src="/tpl/default/Index/Public/image/question.png" class="wenimg" onclick="b()">
                    </p>
                </li>
                <li>
                    <label>舱&nbsp;总&nbsp;数</label>
                    <p>
                        <input type="text" name="cabinnum" placeholder="请输入舱总数" class="i-box" id="cabinnum"
                               maxlength="2">
                        <img src="/tpl/default/Index/Public/image/question.png" class="wenimg" onclick="a('船舶总共有多少舱')">
                    </p>
                </li>
            </ul>
            <div class="bar1">舱容表信息</div>
            <ul class="pass">
                <li>
                    <label>管线容量</label>
                    <p>
                    <div class='radios'>
                        <label><input type="radio" name="is_guanxian" value='1' checked class="regular-checkbox">&nbsp;&nbsp;包含</label>
                        <label><input type="radio" name="is_guanxian" value='2' class="regular-checkbox">&nbsp;&nbsp;未包含</label>
                    </div>
                    <img src="/tpl/default/Index/Public/image/question.png" onclick="a('舱容表所列容积值是否包含管线容量')">
                    </p>
                </li>
                <li>
                    <label>底量测量孔</label>
                    <p>
                    <div class='radios'>
                        <label><input type="radio" name="is_diliang" value='1'
                                      class="regular-checkbox">&nbsp;&nbsp;有</label>
                        <label><input type="radio" name="is_diliang" value='2' checked class="regular-checkbox">&nbsp;&nbsp;无</label>
                    </div>
                    <img src="/tpl/default/Index/Public/image/question.png" class="wenimg"
                         onclick="a('部分船舶每个舱有底量和装货容量两个测量孔，相应地有两本舱容表')">
                    </p>
                </li>
                <li>
                    <label>纵横倾修正表</label>
                    <p>
                        <select name="suanfa" id='suanfa' class=''>
                            <option value="a">无</option>
                            <option value="b">有</option>
                        </select>
                        <img src="/tpl/default/Index/Public/image/question.png" class="wenimg" onclick="a('请查阅检定证书目录确认是否有纵倾、横倾修正表')">
                    </p>
                </li>
                <li><label>舱容表有效期</label>
                    <p><input type='text' class='i-box' id='dateinput' value=''
                              name='expire_time'>
                        <img src='./tpl/default/Index/Public/image/question.png' class='wenimg'
                             onclick="a( '查看有效文案底部有效期')"></p></li>
            </ul>
            <div class="bar">
                <input type="submit" value="取&nbsp;消" class="mmqx passbtn">
                <input type="submit" onclick="addr()" value="提&nbsp;交" class="mmqd passbtn">
            </div>
        </div>
        <script>
            laydate.render({
                elem: '#dateinput' //指定元素
                , theme: 'grid' //主题
                , format: 'yyyy-MM-dd' //自定义格式
                , min: 0
            });

            $(document).on("click", "#addbut1", function () {
                event.preventDefault();
                $('.editMask5').addClass('is-visible3');
                $('.editMask5').find(".tip_info i").remove()
            })
            $('.editMask5').on('click', function (event) {
                if ($(event.target).is('.mmqx') || $(event.target).is('.editMask5')) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function addr() {
                var firmid = $('#firmid').val();
                var shipname = $('#shipname').val();
                var coefficient = $('#coefficient').val();
                var cabinnum = $('#cabinnum').val();
                var suanfa = $('#suanfa').val();
                var is_guanxian = $('input[name="is_guanxian"]:checked').val();
                var is_diliang = $('input[name="is_diliang"]:checked').val();
                var expir_time = $('#dateinput').val();
                $.ajax({
                    url: "<?php echo U('Ship/addship');?>",
                    type: "POST",
                    data: {
                        'firmid': firmid,
                        'shipname': shipname,
                        'coefficient': coefficient,
                        'cabinnum': cabinnum,
                        'suanfa': suanfa,
                        'is_guanxian': is_guanxian,
                        'is_diliang': is_diliang,
                        'expire_time': expir_time,
                    },
                    dataType: "json",
                    success: function (data) {
                        if (data.state == 1) {
                            layer.msg(data.message, {icon: 1});
                            setTimeout(function () {
                                location.reload();
                            }, 2000);
                        } else {
                            layer.msg(data.message, {icon: 5});
                        }
                    }
                });
            }
        </script>
    </div>

    <!-- 修改船舶 -->
    <div class="editMask6">
        <div class="editBox1" id='editBox1'>

        </div>
        <script>
            $('.editMask6').on('click', function (event) {
                if ($(event.target).is('.mmqx') || $(event.target).is('.editMask6')) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function edit(id) {
                // 判断作业是否已开始
                $.ajax({
                    url: "<?php echo U('Ship/shipmsg');?>",
                    type: "POST",
                    data: {
                        'id': id,
                    },
                    dataType: "json",
                    success: function (data) {
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
                var id = $('#shipid').val();
                var firmid = $('#firmid1').val();
                var shipname = $('#shipname1').val();
                var coefficient = $('#coefficient1').val();
                var cabinnum = $('#cabinnum1').val();
                var suanfa = $('#suanfa1').val();
                var is_guanxian = $('input[name="is_guanxian1"]:checked').val();
                var is_diliang = $('input[name="is_diliang1"]:checked').val();
                var expir_time = $('#dateinput1').val();
                $.ajax({
                    url: "<?php echo U('Ship/editship');?>",
                    type: "POST",
                    data: {
                        'id': id,
                        'firmid': firmid,
                        'shipname': shipname,
                        'coefficient': coefficient,
                        'cabinnum': cabinnum,
                        'suanfa': suanfa,
                        'is_guanxian': is_guanxian,
                        'is_diliang': is_diliang,
                        'expire_time': expir_time,
                    },
                    dataType: "json",
                    success: function (data) {
                        if (data.state == 1) {
                            layer.msg(data.message, {icon: 1});
                            setTimeout(function () {
                                location.reload();
                            }, 2000);
                        } else {
                            layer.msg(data.message, {icon: 5});
                        }
                    }
                });
            }
        </script>
    </div>

    <!-- 新建船舱 -->
    <div class="editMask7">
        <div class="editBox1">
            <div class="bar">新建船舱</div>
            <div class="bar1">基本信息</div>
            <ul class="pass">
                <li>
                    <label>所属船舶</label>
                    <p>
                        <select name="shipidd" id='shipidd' class=''>
                            <option value="">请选择所属船舶</option>
                            <?php if(is_array($shiplist)): $i = 0; $__LIST__ = $shiplist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><option value="<?php echo ($v['id']); ?>"><?php echo ($v['shipname']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
                        </select>
                    </p>
                </li>
                <li>
                    <label>舱&nbsp;名</label>
                    <p><input type="text" name="cabinname" placeholder="请输入舱名" class="i-box" id="cabinname"
                              maxlength="12"></p>
                </li>
                <li>
                    <label>管线容量</label>
                    <p>
                        <input type="text" name="pipe_line" placeholder="请输入管线容量" class="i-box" id="pipe_line"
                               maxlength="5">
                    </p>
                </li>
            </ul>
            <div class="bar1">容量表信息</div>
            <ul class="pass">
                <li>
                    <label>基准高度</label>
                    <p>
                        <input type="text" name="altitudeheight" placeholder="请输入基准高度" class="i-box" id="altitudeheight"
                               maxlength="5">
                    </p>
                </li>
                <li>
                    <label>底&nbsp;量</label>
                    <p>
                        <input type="text" name="bottom_volume" placeholder="请输入底量" class="i-box" id="bottom_volume"
                               maxlength="5">
                    </p>
                </li>
            </ul>
            <div style="display: none;" id="hiden">
                <div class="bar1">底量表信息</div>
                <ul class="pass">
                    <li>
                        <label>基准高度</label>
                        <p>
                            <input type="text" name="dialtitudeheight" placeholder="请输入基准高度" class="i-box"
                                   id="dialtitudeheight" maxlength="5">
                        </p>
                    </li>
                    <li>
                        <label>底&nbsp;量</label>
                        <p>
                            <input type="text" name="bottom_volume_di" placeholder="请输入底量" class="i-box"
                                   id="bottom_volume_di" maxlength="5">
                        </p>
                    </li>
                </ul>
            </div>
            <div class="bar">
                <input type="submit" value="取&nbsp;消" class="mmqx passbtn">
                <input type="submit" onclick="addc()" value="提&nbsp;交" class="mmqd passbtn">
            </div>
        </div>
        <script>
            $(document).on("click", "#addbut2", function () {
                event.preventDefault();
                $('.editMask7').addClass('is-visible3');
                $('.editMask7').find(".tip_info i").remove()
            })
            $('.editMask7').on('click', function (event) {
                if ($(event.target).is('.mmqx') || $(event.target).is('.editMask7')) {
                    event.preventDefault();
                    $(this).removeClass('is-visible3');
                }
            });

            function addc() {
                var shipid = $('#shipidd').val();
                var cabinname = $('#cabinname').val();
                var pipe_line = $('#pipe_line').val();
                var altitudeheight = $('#altitudeheight').val();
                var bottom_volume = $('#bottom_volume').val();
                var dialtitudeheight = $('#dialtitudeheight').val();
                var bottom_volume_di = $('#bottom_volume_di').val();

                $.ajax({
                    url: "<?php echo U('Cabin/add');?>",
                    type: "POST",
                    data: {
                        'shipid': shipid,
                        'cabinname': cabinname,
                        'pipe_line': pipe_line,
                        'altitudeheight': altitudeheight,
                        'bottom_volume': bottom_volume,
                        'dialtitudeheight': dialtitudeheight,
                        'bottom_volume_di': bottom_volume_di,
                    },
                    dataType: "json",
                    success: function (data) {
                        if (data.state == 1) {
                            layer.msg(data.message, {icon: 1});
                            setTimeout(function () {
                                location.reload();
                            }, 2000);
                        } else {
                            layer.msg(data.message, {icon: 5});
                        }
                    }
                });
            }
        </script>
    </div>
    <div id="tong1" hidden>
        <img src="/tpl/default/Index/Public/image/tong.png" style="width: 450px;height:290px;">
    </div>
    <script type="text/javascript">
        function a(msgs) {
            layer.msg(msgs);
        }

        function b() {
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                area: '450px 300px',
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content: $('#tong1')
            });
        }

        function ti() {
            $('#submit').click();
        }

        // 下拉选择判断是否有底量表
        $("select[name=shipidd]").change(function () {
            var h = $("#hiden");
            $.ajax({
                url: "<?php echo U('Cabin/ajax_diliang');?>",
                data: 'shipid=' + $(this).val(),
                type: 'post',
                async: false,
                dataType: 'json',
                success: function (res) {
                    if (res == '1') {
                        h.show();
                    } else {
                        h.hide();
                    }
                }
            });
        });
    </script>

    </div>
    <!-- 中间结束-->
    <!-- 底部开始 -->
    <div class="footer">
    <span>版权所有 <a href="http://www.xzitc.com/" target="_blank">南京携众信息科技有限公司</a> @2018-2018</span>
</div>
</body>
</html>