<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD html 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<!-- saved from url=(0064)http://www.17sucai.com/preview/137615/2015-01-15/demo/index.html -->
<!DOCTYPE html PUBLIC "-//W3C//DTD Xhtml 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta content="IE=11.0000" http-equiv="X-UA-Compatible">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>登录页面</title>
    <script src="/Public/Admin/js/jquery-1.9.1.min.js" type="text/javascript"></script>
    <link rel="stylesheet" href="/Public/Admin/css/adminlogin.css" />
    <script type="text/javascript">
    $(function() {
        //得到焦点
        $("#password").focus(function() {
            $("#left_hand").animate({
                left: "150",
                top: " -38"
            }, {
                step: function() {
                    if (parseInt($("#left_hand").css("left")) > 140) {
                        $("#left_hand").attr("class", "left_hand");
                    }
                }
            }, 2000);
            $("#right_hand").animate({
                right: "-64",
                top: "-38px"
            }, {
                step: function() {
                    if (parseInt($("#right_hand").css("right")) > -70) {
                        $("#right_hand").attr("class", "right_hand");
                    }
                }
            }, 2000);
        });
        //失去焦点
        $("#password").blur(function() {
            $("#left_hand").attr("class", "initial_left_hand");
            $("#left_hand").attr("style", "left:100px;top:-12px;");
            $("#right_hand").attr("class", "initial_right_hand");
            $("#right_hand").attr("style", "right:-112px;top:-12px");
        });
    });
    </script>
    <!-- <meta name="GENERATOR" content="MShtml 11.00.9600.17496"> -->
</head>

<body>
    <div class="top_div"></div>
    <div style="background: rgb(255, 255, 255); margin: -80px auto auto; border: 1px solid rgb(231, 231, 231); border-image: none; width: 400px; height: 250px; text-align: center;">
        <div style="width: 165px; height: 96px; position: absolute;">
            <div class="tou"></div>
            <div class="initial_left_hand" id="left_hand"></div>
            <div class="initial_right_hand" id="right_hand"></div>
        </div>
        <form action="<?php echo U('Login/login');?>" id="login_form" method="post">
            <P style="padding: 30px 0px 10px; position: relative;">
                <span class="u_logo"></span>
                <input class="ipt" type="text" placeholder="请输入账号" name="title">
            </P>
            <P style="position: relative;padding: 0px 0px 10px; ">
                <span class="p_logo"></span>
                <input class="ipt" id="password" type="password" placeholder="请输入密码" name="pwd">
            </P>
            <P style="position: relative;padding: 0px 0px 10px;height:50px ">
                <input class="ipt" type="text" placeholder="请输入验证码" name="verify" style="width:160px;vertical-align:middle">
                <img class="verify" src="<?php echo U('Login/show_verify');?>" title="点击更换" onclick="this.src='/admin.php?s=/Login/show_verify/'+Math.random()" style="vertical-align:middle">
            </P>
            <div style="height: 50px; line-height: 50px; margin-top: 20px; border-top-color: rgb(231, 231, 231); border-top-width: 1px; border-top-style: solid;">
                <P style="margin: 0px 35px 20px 45px;">
                    <span style="float: center;">
                  <input type="submit" name="sub" value="登陆"  style="background: rgb(0, 142, 173); padding: 7px 10px; border-radius: 4px; border: 1px solid rgb(26, 117, 152); border-image: none; color: rgb(255, 255, 255); font-weight: bold;">
               </span> </P>
            </div>
        </form>
    </div>
    <div style="text-align:center;margin:100px auto 50px">
        <p>技术支持@<a href="http://new.xzitc.com/" target="_blank">携众信息</a></p>
    </div>
</body>

</html>