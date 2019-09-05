<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>修改公司信息</title>
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
		 
	<link rel="stylesheet" href="/tpl/default/Index/Public/css/firm.css">
	<div class='shang'>
        <div class='nav'>
            管理员设置>完善公司信息
        </div>
    </div>
    <div style="background: url(/tpl/default/Index/Public/image/img8.png) repeat;background-size: 60px 60px;padding: 20px 0px;">
        <div class='firm'>
            <form method="post" action="/index.php?s=/Firm/msg">
                <table>
                    <input type="hidden" name="id" value="<?php echo ($data['id']); ?>" >
                    <tr>
                        <td style="width: 120px;">公司logo</td>
                        <td>
                            <img class="normalFace" src="<?php echo ($data['logo'] == '') ? '/tpl/default/Index/Public/image/noimg.png' : $data['logo'];?>" onclick="fileSelect();" style='width: 80px;height: 70px;'>
                            <input type="file" name="photo" id="photo" value='' class="filepath" style="display:none;">
                            <input type="text" name="logo" id="logo" value='' style="display:none;">
                        </td>
                    </tr>
                    <tr>
                        <td>公司名称</td>
                        <td>
                            <input type="text" name="firmname" required maxlength="30" value="<?php echo ($data['firmname']); ?>" placeholder="请输入公司名称" />
                        </td>
                    </tr>
                    <tr>
                        <td>公司类型</td>
                        <td>
                            <select name="firmtype">
                                <option value="1" <?php echo ($data['firmtype']=='1' ? 'selected' : ''); ?>>检验公司</option>
                                <option value="2" <?php echo ($data['firmtype']=='2' ? 'selected' : ''); ?>>船舶公司</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>公司地址</td>
                        <td>
                            <input type="text" name="location" required maxlength="45"  value="<?php echo ($data['location']); ?>" placeholder="请输入公司地址"/>
                        </td>
                    </tr>
                    <tr>
                        <td>公司简介</td>
                        <td>
                            <textarea name="content" cols="52" rows="6" placeholder="请输入公司简介" maxlength="500" required><?php echo ($data['content']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>联&nbsp;系&nbsp;&nbsp;人</td>
                        <td>
                            <input type="text" name="people" required maxlength="10" value="<?php echo ($data['people']); ?>" placeholder="请输入联系人"/>
                        </td>
                    </tr>
                    <tr>
                        <td>联系电话</td>
                        <td>
                            <input type="text" name="phone" required maxlength="16" value="<?php echo ($data['phone']); ?>" placeholder="请输入联系电话"/>
                        </td>
                    </tr>
                    <tr>
                        <td>社会信用代码</td>
                        <td>
                            <input type="text" name="shehuicode" required maxlength="50" placeholder="请输入社会信用代码" value="<?php echo ($data['shehuicode']); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td>信用代码图片</td>
                        <td>
                            <img class="normalFace" src="<?php echo ($data['img'] == '') ? '/tpl/default/Index/Public/image/noimg.png' : $data['img'];?>" onclick="fileSelect1();" style='width: 150px;height: 110px;'>
                            <input type="file" name="photo1" id="photo1" value='' class="filepath1" style="display:none;">
                            <input type="text" name="img" id="img" value='' style="display:none;">
                        </td>
                    </tr>
                    <tr>
                        <td>公司图片</td>
                        <td>
                           <img class="normalFace" src="<?php echo ($data['image'] == '') ? '/tpl/default/Index/Public/image/noimg.png' : $data['image'];?>" onclick="fileSelect2();" style='width: 150px;height: 110px;'>
                            <input type="file" name="photo2" id="photo2" value='' class="filepath2" style="display:none;">
                            <input type="text" name="image" id="image" value='' style="display:none;">
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2>
                            <input type="submit" name="sub" value="提交" class="btn-primary" >
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
	
	<script src="/Public/Admin/static/ajaximg/jquery.min.js"></script>
    <script src="/Public/Admin/static/ajaximg/lrz.all.bundle.js"></script>
    <script type="text/javascript">
        function checkImg(img_id){
            // var img_id=document.getElementById('movie_img').value; //根据id得到值
            var index= img_id.indexOf("."); //得到"."在第几位
            img_id=img_id.substring(index); //截断"."之前的，得到后缀
            if(img_id!=".bmp"&&img_id!=".png"&&img_id!=".gif"&&img_id!=".jpg"&&img_id!=".jpeg"){  //根据后缀，判断是否符合图片格式
                  // alert("不是指定图片格式,重新选择"); 
                  return '2';
                 // document.getElementById('movie_img').value="";  // 不符合，就清除，重新选择
              }
         }
        //点击绑定
        function fileSelect() {
            document.getElementById("photo").click();
        }
        //图片生成并展示
        $(function() {
            $(document).on('change', '.filepath', function(e) {
                var str = $(this).attr("name");
                var str1 = $(this).val();
                var res = checkImg(str1);
                if (res == '2') {
                    alert("不是指定图片格式,重新选择"); 
                    return false;
                }
                lrz(this.files[0], { width: 640, quality: 0.92 })
                    .then(function(rst) {
                        $.ajax({
                            url: "<?php echo U('Login/upload_ajax');?>",
                            type: 'post',
                            data: { image: rst.base64, zd: str },
                            dataType: 'json',
                            enctype: 'multipart/form-data',
                            success: function(data) {
                                var obj = eval("(" + data + ")");
                                if (0 == obj.status) {
                                    return false;
                                } else {
                                    $(".normalFace").css('padding-top', '0px');
                                    var src = obj.url;
                                    $("input[name=" + str + "]").parent().children("img").attr("src", src);
                                    $("input[name='logo']").val(src);
                                    // alert(src);
                                }
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) { //上传失败 
                                alert(XMLHttpRequest.status);
                                alert(XMLHttpRequest.readyState);
                                alert(textStatus);
                            }
                        });
                    })
                    .catch(function(err) {

                    })
                    .always(function() {

                    });
            });
        });
        //点击绑定
        function fileSelect1() {
            document.getElementById("photo1").click();
        }
        //图片生成并展示
        $(function() {
            $(document).on('change', '.filepath1', function(e) {
                var str = $(this).attr("name");
                var str1 = $(this).val();
                var res = checkImg(str1);
                if (res == '2') {
                    alert("不是指定图片格式,重新选择"); 
                    return false;
                }
                lrz(this.files[0], { width: 640, quality: 0.92 })
                    .then(function(rst) {
                        $.ajax({
                            url: "<?php echo U('Login/upload_ajax');?>",
                            type: 'post',
                            data: { image: rst.base64, zd: str },
                            dataType: 'json',
                            enctype: 'multipart/form-data',
                            success: function(data) {
                                var obj = eval("(" + data + ")");
                                if (0 == obj.status) {
                                    return false;
                                } else {
                                    $(".normalFace").css('padding-top', '0px');
                                    var src = obj.url;
                                    $("input[name=" + str + "]").parent().children("img").attr("src", src);
                                    $("input[name='img']").val(src);
                                    // alert(src);
                                }
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) { //上传失败 
                                alert(XMLHttpRequest.status);
                                alert(XMLHttpRequest.readyState);
                                alert(textStatus);
                            }
                        });
                    })
                    .catch(function(err) {

                    })
                    .always(function() {

                    });
            });
        });
        //点击绑定
        function fileSelect2() {
            document.getElementById("photo2").click();
        }
        //图片生成并展示
        $(function() {
            $(document).on('change', '.filepath2', function(e) {
                var str = $(this).attr("name");
                var str1 = $(this).val();
                var res = checkImg(str1);
                if (res == '2') {
                    alert("不是指定图片格式,重新选择"); 
                    return false;
                }
                lrz(this.files[0], { width: 640, quality: 0.92 })
                    .then(function(rst) {
                        $.ajax({
                            url: "<?php echo U('Login/upload_ajax');?>",
                            type: 'post',
                            data: { image: rst.base64, zd: str },
                            dataType: 'json',
                            enctype: 'multipart/form-data',
                            success: function(data) {
                                console.log(data);
                                var obj = eval("(" + data + ")");
                                if (0 == obj.status) {
                                    return false;
                                } else {
                                    $(".normalFace").css('padding-top', '0px');
                                    var src = obj.url;
                                    $("input[name=" + str + "]").parent().children("img").attr("src", src);
                                    $("input[name='image']").val(src);
                                    // alert(src);
                                }
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) { //上传失败 
                                alert(XMLHttpRequest.status);
                                alert(XMLHttpRequest.readyState);
                                alert(textStatus);
                            }
                        });
                    })
                    .catch(function(err) {

                    })
                    .always(function() {

                    });
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