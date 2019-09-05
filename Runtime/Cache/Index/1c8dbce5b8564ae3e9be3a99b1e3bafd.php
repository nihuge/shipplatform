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
		 
<!--startprint9-->
    <style>
        .table_01,.ju { font-size:14px; color:#333; font-family:"方正兰亭黑简";margin:auto 5px;font-weight: bold}
        .table_02 { font-size:14px; font-family:"方正兰亭黑简"; color:#333;margin:auto 5px;}
        .box02 { height:41px; margin:20px auto; line-height:50px; text-align:center; font-size:14px; color:#fff; font-family:"方正兰亭黑简";}
        input { background-color:#3ca0fe; border:none; margin-left:7px; height:30px; color:#fff; font-size:14px; cursor:pointer;}
        .ju03 {height:35px; width:200px;}
        tr{text-align: center;}
        .div1{display: inline-block;}
        .quiz{border:solid 1px #ccc;height:270px;}
        .quiz h3{font-size:14px;line-height:35px;height:35px;border-bottom:solid 1px #e8e8e8;padding-left:20px;background:#f8f8f8;color:#666;position:relative;}
        .quiz_content{padding-top:10px;padding-left:20px;position:relative;height:205px;}
        .quiz_content .btm{border:none;width:100px;height:33px;background:url(__HOME_IMG__/btn.gif) no-repeat;margin:10px 0 0 64px;display:inline;cursor:pointer;}
        .quiz_content li.full-comment{position:relative;z-index:99;height:41px;}
        .quiz_content li.cate_l{height:24px;line-height:24px;padding-bottom:10px;}
        .quiz_content li.cate_l dl dt{float:left;}
        .quiz_content li.cate_l dl dd{float:left;padding-right:15px;}
        .quiz_content li.cate_l dl dd label{cursor:pointer;}
        .quiz_content .l_text{height:120px;position:relative;padding-left:18px;}
        .quiz_content .l_text .m_flo{float:left;width:47px;}
        .quiz_content .l_text .text{width:634px;height:109px;border:solid 1px #ccc;}
        .quiz_content .l_text .tr{position:absolute;bottom:-18px;right:40px;}
        /*goods-comm-stars style*/
        .goods-comm{height:41px;position:relative;z-index:7;}
        .goods-comm-stars{line-height:25px;padding-left:12px;height:41px;position:absolute;top:0px;left:0;width:400px;}
        .goods-comm-stars .star_l{float:left;display:inline-block;margin-right:5px;display:inline;}
        .goods-comm-stars .star_choose{float:left;display:inline-block;}
        /* rater star */
        .rater-star{position:relative;list-style:none;margin:0;padding:0;background-repeat:repeat-x;background-position:left top;float:left;}
        .rater-star-item, .rater-star-item-current, .rater-star-item-hover{position:absolute;top:0;left:0;background-repeat:repeat-x;}
        .rater-star-item{background-position: -100% -100%;}
        .rater-star-item-hover{background-position:0 -48px;cursor:pointer;}
        .rater-star-item-current{background-position:0 -48px;cursor:pointer;}
        .rater-star-item-current.rater-star-happy{background-position:0 -25px;}
        .rater-star-item-hover.rater-star-happy{background-position:0 -25px;}
        .rater-star-item-current.rater-star-full{background-position:0 -72px;}
        /* popinfo */
        .popinfo{display:none;position:absolute;top:30px;background:url(__HOME_IMG__/comment/infobox-bg.gif) no-repeat;padding-top:8px;width:192px;margin-left:-14px;}
        .popinfo .info-box{border:1px solid #f00;border-top:0;padding:0 5px;color:#F60;background:#FFF;}
        .popinfo .info-box div{color:#333;}
        .rater-click-tips{font:12px/25px;color:#333;margin-left:10px;background:url(__HOME_IMG__/comment/infobox-bg-l.gif) no-repeat 0 0;width:125px;height:34px;padding-left:16px;overflow:hidden;}
        .rater-click-tips span{display:block;background:#FFF9DD url(__HOME_IMG__/comment/infobox-bg-l-r.gif) no-repeat 100% 0;height:34px;line-height:34px;padding-right:5px;}
        .rater-star-item-tips{background:url(__HOME_IMG__/comment/star-tips.gif) no-repeat 0 0;height:41px;overflow:hidden;}
        .cur.rater-star-item-tips{display:block;}   
        .rater-star-result{color:#FF6600;font-weight:bold;padding-left:10px;float:left;}
        @("@")page 
        {
            size:  auto;   /* auto is the initial value */
            margin: 0mm;  /* this affects the margin in the printer settings */
        } 
    </style>

    <div style="margin: 10px 61px;">
        <h2 align="center"><?php echo ($content['goodname']); ?>计重记录单</h2>
        <br>
        <div style="width:1077px;margin:auto 5px;font-weight: bold;font-size:14px;">
            <div class="div1" style="text-align: left;width:45px;height:40px;line-height: 40px;">船名：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:135px;text-align: center;"><?php echo ($content['shipname']); ?></div>
            <div class="div1" style="text-align: left;width:60px;height:40px;line-height: 40px;">航次号：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:105px;text-align: center;"><?php echo ($personality['voyage']); ?></div>
            <div class="div1" style="text-align: center;width:75px;height:40px;line-height: 40px;">作业地点：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:120px;text-align: center;"><?php echo ($personality['locationname']); ?></div>
            <div class="div1" style="text-align: left;width:60px;height:40px;line-height: 40px;">运单量：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:95px;text-align: center;"><?php echo ($personality['transport']); ?></div>
            <div class="div1" style="text-align: left;width:60px;height:40px;line-height: 40px;">货名：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:105px;text-align: center;"><?php echo ($personality['goodsname']); ?></div>
            <div class="div1" style="text-align: center;width:45px;height:40px;line-height: 40px;">编号：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:120px;text-align: center;"><?php echo ($personality['number']); ?></div>
        </div>
        <div style="width:1077px;margin:auto 5px;font-weight: bold;font-size:14px;">
            <div class="div1" style="text-align: left;width:63px;height:40px;line-height: 40px;">起运港：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:125px;text-align: center;"><?php echo ($personality['start']); ?></div>
            <div class="div1" style="text-align: left;width:63px;height:40px;line-height: 40px;">目的港：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:120px;text-align: center;"><?php echo ($personality['objective']); ?></div>     
            <div class="div1" style="text-align: left;width:115px;height:40px;line-height: 40px;">作业起止时间：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:160px;text-align: center;"><?php echo ($endtime); ?></div>
            <div class="div1" style="text-align: left;width:25px;height:40px;line-height: 40px;">到</div>
            <div class="div1" style="border-bottom:solid 1px black;width:160px;text-align: center;"><?php echo ($starttime); ?></div>
        </div>
        <!-- <div style="width:1077px;margin:auto 5px;font-weight: bold;font-size:14px;">
            <div class="div1" style="text-align: left;width:60px;height:40px;line-height: 40px;">&nbsp;</div>
            <div class="div1" style="text-align: left;width:130px;height:40px;line-height: 40px;">首次</div>
            <div class="div1" style="text-align: left;width:100px;height:40px;line-height: 40px;">吃水差(米)：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:145px;text-align: center;"><?php echo ($content['qianchi']); ?></div>
             <div class="div1" style="text-align: left;width:70px;height:40px;line-height: 40px;">&nbsp;</div>
            <div class="div1" style="text-align: center;width:130px;height:40px;line-height: 40px;">末次</div>
            <div class="div1" style="text-align: center;width:100px;height:40px;line-height: 40px;">吃水差(米)：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:145px;text-align: center;"><?php echo ($content['houchi']); ?></div>
        </div> -->
        <!-- <h3>&nbsp;</h3> -->
        <table border="1" cellspacing="0" cellpadding="0" align="center" width="1077px" class="table_02">
            <tr>
                <th colspan="9">首次检验<span style="float: right">吃水差：<?php echo ($content['qianchi']); ?>&nbsp;</span></th>
                <th width="2px"></th>
                <th colspan="8">末次检验<span style="float: right">吃水差：<?php echo ($content['houchi']); ?>&nbsp;</span></th>
            </tr>
            <tr>
                <th>油舱名</th>
                <th>温度</th>
                <th>空距
                    <br>(米)</th>
                <th>纵倾修正<br>值(米)</th>
                <th>修正后空<br>距(米)</th>
                <th>容量
                    <br>(米 <sup>3</sup> )</th>
                <th>体积修正<br>系数</th>
                <th>膨胀修正<br>系数</th>
                <th>标准容量</th>
                <th width="2px"></th>
                <th>温度</th>
                <th>空距
                    <br>(米)</th>
                <th>纵倾修正<br>值(米)</th>
                <th>修正后空<br>距(米)</th>
                <th>容量
                    <br>(米 <sup>3</sup> )</th>
                <th>体积修正<br>系数</th>
                <th>膨胀修正<br>系数</th>
                <th>标准容量</th>
            </tr>
            <?php if(is_array($result)): $i = 0; $__LIST__ = $result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
                    <td style='height:20px'><?php echo ($v[0]['cabinname']); ?></td>
                    <td><?php echo ($v[0]['temperature']); ?></td>
                    <td><?php echo ($v[0]['ullage']); ?></td>
                    <td><?php echo ($v[0]['listcorrection']); ?></td>
                    <td><?php echo ($v[0]['correntkong']); ?></td>
                    <td><?php echo ($v[0]['cabinweight']); ?></td>
                    <td><?php echo ($v[0]['volume']); ?></td>
                    <td><?php echo ($v[0]['expand']); ?></td>
                    <td><?php echo ($v[0]['standardcapacity']); ?></td>
                    <td></td>
                    <td><?php echo ($v[1]['temperature']); ?></td>
                    <td><?php echo ($v[1]['ullage']); ?></td>
                    <td><?php echo ($v[1]['listcorrection']); ?></td>
                    <td><?php echo ($v[1]['correntkong']); ?></td>
                    <td><?php echo ($v[1]['cabinweight']); ?></td>
                    <td><?php echo ($v[1]['volume']); ?></td>
                    <td><?php echo ($v[1]['expand']); ?></td>
                    <td><?php echo ($v[1]['standardcapacity']); ?></td>
                    
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            <tr>
                <td style="height:20px"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="height:20px" class="ju01">总容量</td>
                <td colspan="6"></td>
                <td colspan="2"><?php echo ($content['qianweight']); ?></td>
                <td></td>
                <td colspan="6"></td>
                <td colspan="2"><?php echo ($content['houweight']); ?></td>
            </tr>
            <tr>
                <td colspan="7" align="left" style="height:20px" class="ju01">&nbsp;实验室密度15℃(克/厘米<sup>3</sup>)</td>
                <td colspan="2"><?php echo ($content['qiandensity']); ?></td>
                <td></td>
                <td colspan="6"></td>
                <td colspan="2"><?php echo ($content['houdensity']); ?></td>
            </tr>
            <tr>
                <td colspan="7" align="left" style="height:20px" class="ju01">&nbsp;重量(吨)</td>
                <td colspan="2"><?php echo ($content['qiantotal']); ?></td>
                <td></td>
                <td colspan="6"></td>
                <td colspan="2"><?php echo ($content['houtotal']); ?></td>
            </tr>
            <tr>
                <td colspan="16" align="right" style="height:20px" class="ju01">货重(吨)&nbsp;</td>
                <td colspan="2"><?php echo ($content['weight']); ?></td>
            </tr>
        </table>
        <h3></h3>
        <div style="width:1077px;margin:auto 5px;font-weight: bold;font-size:14px;">
            <div class="div1" style="text-align: left;width:80px;height:40px;line-height: 40px;">图表编号：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:220px;"><?php echo ($content['ship_number']); ?></div>
            <div class="div1" style="text-align: left;width:90px;height:40px;line-height: 40px;margin-left: 55px;">温度计编号：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:225px;"><?php echo ($personality['thermometer']); ?></div>
            <div class="div1" style="text-align: left;width:90px;height:40px;line-height: 40px;margin-left: 55px;">量油尺编号：</div>
            <div class="div1" style="border-bottom:solid 1px black;width:220px;"><?php echo ($personality['dipstick']); ?></div>
            
        </div>
        <table border="0" cellspacing="0" cellpadding="0" width="1077px" class="ju">
           <!--  <tr>
                <th colspan=4>&nbsp;</th>
            </tr> -->
           <!--  <tr>
                <th>温度计编号：</th>
                <th style="border-bottom:solid 1px black"> <?php echo ($personality['thermometer']); ?> </th>
                <th>量油尺编号：</th>
                <th style="border-bottom:solid 1px black"> <?php echo ($personality['dipstick']); ?> </th>
            </tr> -->
            <tr>
                <th width="50px" align="left" class="ju02">备注：</th>
                <th colspan='3' style="border-bottom:solid 1px black;text-align:left">&nbsp;&nbsp;<?php echo ($content['remark']); ?></th>
            </tr>
            <tr>
                <th colspan="4" style="border-bottom:solid 1px black;height:30px;">&nbsp;</th>
            </tr>
            
            <tr>
                <th colspan=4>&nbsp;</th>
            </tr>
            <tr>
                <th width="50px" align="left" class="ju02">计量员：</th>
                <th width="185px" style="">
                    <?php if($content['ffirmtype'] == '1'): echo ($content['username']); ?>
                    <?php else: ?>
                        <?php if(!empty($content['eimg'])): ?><img src="<?php echo ($content[eimg]); ?>" style="height: 60px;width:180px"><?php endif; endif; ?>
                </th>
                <th width="285px" align="right" class="ju02">船舶签章：</th>
                <th width="175px" style="">
                    <?php if($content['ffirmtype'] == '1'): if(!empty($content['eimg'])): ?><img src="<?php echo ($content[eimg]); ?>" style="height: 60px;width:180px"><?php endif; ?>
                    <?php else: endif; ?>
                </th>
            </tr>
        </table>
        <h3>&nbsp;</h3>
        <div style="width: 1077px;text-align: right;">（本单证版权归中理检验公司所有，由南京携众提供技术支持。）</div>
        <!--endprint9-->
        <div class="box02">
            <!-- <form action="<?php echo U('');?>" method="post"> -->
                <input type="hidden" name="resultid" value="<?php echo ($content['id']); ?>">
                <input type="button" onclick="preview(9)" name="reset" value="打&nbsp;&nbsp;印" align="right" class="ju03">
            <!-- </form> -->
        </div>
    </div>
        
    <script>
    function preview(oper){
        if (oper < 10){
            //get_page_info();
            bdhtml=window.document.body.innerHTML;//获取当前页的html代码

            sprnstr="<!--startprint"+oper+"-->";//设置打印开始区域
            eprnstr="<!--endprint"+oper+"-->";//设置打印结束区域
            prnhtml=bdhtml.substring(bdhtml.indexOf(sprnstr)+18); //从开始代码向后取html
            prnhtml=prnhtml.substring(0,prnhtml.indexOf(eprnstr));//从结束代码向前取html
            window.document.body.innerHTML=prnhtml;
            if (!!window.ActiveXObject || "ActiveXObject" in window) { //是否ie
                remove_ie_header_and_footer();
            }
            window.print();
            // prnhtml.print();
            window.document.body.innerHTML=bdhtml;
        } else {
            if (!!window.ActiveXObject || "ActiveXObject" in window) { //是否ie
                remove_ie_header_and_footer();
            }
            window.print();
        }
    }
    function remove_ie_header_and_footer() {
        var hkey_path;
        hkey_path = "HKEY_CURRENT_USER\\Software\\Microsoft\\Internet Explorer\\PageSetup\\";
        try {
            var RegWsh = new ActiveXObject("WScript.Shell"); //设置上页边距（8）   
            // RegWsh.RegWrite(hkey_path + HKEY_Path + HKEY_Key, "8");
            RegWsh.RegWrite(hkey_path + "header", "");
            RegWsh.RegWrite(hkey_path + "footer", "");
        } catch (e) {}
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