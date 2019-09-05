<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>查询系统</title>
	<link rel="stylesheet" href="/tpl/default/Index/Public/css/base.css">
	<!-- 分野分页样式 -->
	<link rel="stylesheet" type="text/css" href="/tpl/default/Index/Public/css/page.css">
	<script src="/tpl/default/Index/Public/js/jquery1.8.3.min.js"></script> 
    <script src="/tpl/default/Index/Public/static/layer/layer.js"></script>
	<script src="/Public/laydate/laydate.js"></script>
</head>
<body 
    style="background-image: url(/tpl/default/Index/Public/image/img7.png);background-position: center center;background-repeat: no-repeat;background-attachment: fixed;background-size: 100% 100%;"
>
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
            <a class="dropbtn1 dropbtnhover" href="<?php echo U('Search/index');?>">查询系统</a>
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
		 
	<style>
		.navwai {
		    width: 100%;
		    background-color: #fff;
		    min-width: 1200px;
		}
	</style>
    <link rel="stylesheet" href="/tpl/default/Index/Public/css/liquid.css">
	<link rel="stylesheet" href="/tpl/default/Index/Public/css/search.css">
	<link rel="stylesheet" type="text/css" href="/tpl/default/Index/Public/css/jquery.bigautocomplete.css">
	<script type="text/javascript" src="/tpl/default/Index/Public/js/jquery.bigautocomplete.js"></script>
	 <script src="/tpl/default/Index/Public/js/jquery.raty.js"></script>
	<div class='namess'>
		计量平台查询系统
	</div>
	<div class="box">
        <ul>
            <li>
                <input type="radio" name="check" id="active1" checked><label for="active1" style="text-align: right;">公共&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                <div class='div1'>
					<div class='div11'>
					<form action="" method="get">
						<input type="hidden" name="c" value="Search">
            			<input type="hidden" name="a" value="jian"> 
						<input type="text" name='firmname'  id='port1' class='searchinput' placeholder="请输入/选择检验公司名称">
						<a href="javascript:;" onclick="ti1()">
		                    <div class="div13">
		                    	<img src="/tpl/default/Index/Public/image/sourch.png" alt="" style="vertical-align: middle;width: 18px;">
		                         搜&nbsp;&nbsp;索
		                    </div>
		                </a>
               			<input type="submit" id='submit1' value="提&nbsp;&nbsp;交" style="display: none;"/>
					</form>
					</div> 
					<div class='div11'>
					<form action="" method="get">
						<input type="hidden" name="c" value="Search">
            			<input type="hidden" name="a" value="chuan"> 
						<input type="text" name='firmname'  id='port2' class='searchinput'  placeholder="请输入/选择船舶公司名称">
						<a href="javascript:;" onclick="ti2()">
		                    <div class='div13'>
		                    	<img src="/tpl/default/Index/Public/image/sourch.png" alt="" style="vertical-align: middle;width: 18px;">
		                         搜&nbsp;&nbsp;索
		                    </div>
		                </a>
               			<input type="submit" id='submit2' value="提&nbsp;&nbsp;交" style="display: none;"/>
					</form>
					</div>
					<div class='div12'>
					<form action="" method="get">
						<input type="hidden" name="c" value="Search">
            			<input type="hidden" name="a" value="ship"> 
						<input type="text" name='shipname'  id='port3' class='searchinput'  placeholder="请输入/选择船舶名称">
						<a href="javascript:;" onclick="ti3()">
		                    <div class='div13'>
		                    	<img src="/tpl/default/Index/Public/image/sourch.png" alt="" style="vertical-align: middle;width: 18px;">
		                         搜&nbsp;&nbsp;索
		                    </div>
		                </a>
               			<input type="submit" id='submit3' value="提&nbsp;&nbsp;交" style="display: none;"/>
					</form>
					</div>
                </div>
            </li>
            <li>
                <input type="radio" name="check" id="active2"><label for="active2" style="text-align: left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;高级</label>
                <div>
					<div class='div15_1'>
						<p style="display: inline-block;vertical-align: middle;color: #fff">船舶数据分析</p>
						<input type="text" name='firmname'  id='port4' class='searchinput'  placeholder="请输入/选择船舶名称">
						<a href="javascript:;" onclick="ti4()">
		                    <div class='div13_1'>
		                    	<img src="/tpl/default/Index/Public/image/sourch.png" alt="" style="vertical-align: middle;width: 18px;">
		                         搜&nbsp;&nbsp;索
		                    </div>
		                </a>
					</div>
					<div class='div15_2'>
						<div id='content1'>
							
						</div>
						<div id='page1' class='page'>
							
						</div>
						<div id='js' class='js'>
							
						</div>
					</div>
                </div>
            </li>
        </ul>
    </div>
    <script>
	$(function(){
		$("#port1").bigAutocomplete({
			width:295,
			data:[
				<?php
 foreach ($jian as $v) { echo '{title:"' . $v['firmname'] . '",show:"' . $v['firmname'] . '"},'; } ?>
			],
			callback:function(data){

			}
		});
		$("#port2").bigAutocomplete({
			width:295,
			data:[
				<?php
 foreach ($chuan as $v) { echo '{title:"' . $v['firmname'] . '",show:"' . $v['firmname'] . '"},'; } ?>
			],
			callback:function(data){

			}
		});
		$("#port3").bigAutocomplete({
			width:295,
			data:[
				<?php
 foreach ($shiplist as $v) { echo '{title:"' . $v['shipname'] . '",show:"' . $v['shipname'] . '"},'; } ?>
			],
			callback:function(data){

			}
		});
		$("#port4").bigAutocomplete({
			width:295,
			data:[
				<?php
 foreach ($shiplist as $v) { echo '{title:"' . $v['shipname'] . '",show:"' . $v['shipname'] . '"},'; } ?>
			],
			callback:function(data){

			}
		});
	})
	function ti1(){
        $('#submit1').click();
    }
    function ti2(){
        $('#submit2').click();
    }
    function ti3(){
        $('#submit3').click();
    }
    function ti4(){
    	$('.div15_2').css('display','block');
        var shipname = $('#port4').val();
        var strin = '';
        if (shipname!='' && shipname!=null) {
        	strin += 'shipname:'+shipname+',';
        }

        var curPage = 1; //当前页码
		var total,pageSize,totalPage; //总记录数，每页显示数，总页数
		getData(1,strin);
	    $("#page1").on('click','span a',function(){
	        var rel = $(this).attr("rel");
	        if(rel){
	            getData(rel,strin);
	        }
	    });
    }

    // 生成星星
    function shengc(xu1,pin){
    	$("#starts"+xu1).raty({ 
    		readOnly:true,
    		number:5,
    		path : "/tpl/default/Index/Public/image",
    		starOn:"st_r_f.png",
    		starOff : "st_r_n.png",
    		starHalf:"st_r_h.png",
    		target : "#title"+xu1,
    		score:pin,
    	});
    } 

    function getData(page,strin)
    {
    	$.ajax({
            url:"<?php echo U('Search/getShip');?>",
            type:'POST',
            data: {'pageNum':page-1,'strin':strin},
            beforeSend:function(){
                 
            },
            success:function(json){
                console.log(json);
                total = json.total; //总记录数
                pageSize = json.pageSize; //每页显示条数
                curPage = page; //当前页
                totalPage = json.totalPage; //总页数
                var ul=$('#content1').find('*').remove();
                string='<div class="xiaa"><div class="nav1"><img src="/tpl/default/Index/Public/image/img5.png" style="margin-right:10px;"><p>船驳列表</p></div><table id="customers"><tr><th>船舶名称</th><th>船舶吨位</th><th>船舶类型</th><th>总作业次数</th><th>总作业吨位</th><th>评价星级</th><th>操作</th></tr>';
                $.each(json.list,function(index,array){ 
                	//遍历json数据列
                	var url = '';
                	url = '<?php echo U("Search/msgship/shipid/'+array['id']+'");?>';
                    string+='<tr><td>'+array['shipname']+'</td><td>'+array['type']+'</td><td>'+array['sweight']+'</td><td>'+array['num']+'</td><td>'+array['weight']+'</td><td><div class="evaluate1"><div id="starts'+array['nn']+'" class="starts"></div><div id="title'+array['nn']+'"  class="title2"></div></div></td><td><a href="'+url+'"  class="aa4">详情</a></td></tr>';
                });
                string+='</table>';
                $('#content1').append(string);

                $.each(json.list,function(index,array){
					shengc(array['nn'],array['pin']);
                });
            },
            complete:function(){ //生成分页条
			    $("#page1").find('*').remove();
			    //页码大于最大页数
			    if(curPage>totalPage) curPage=totalPage;
			    //页码小于1
			    if(curPage<1) curPage=1;
			    pageStr = "<span>共"+total+"条</span><span>"+curPage+"/"+totalPage+"</span>";
			     
			    //如果是第一页
			    if(curPage==1){
			        pageStr += "<span>首页</span><span>上一页</span>";
			    }else{
			        pageStr += "<span><a href='javascript:void(0)' rel='1'>首页</a></span><span><a href='javascript:void(0)' rel='"+(curPage-1)+"'>上一页</a></span>";
			    }
			     
			    //如果是最后页
			    if(curPage>=totalPage){
			        pageStr += "<span>下一页</span><span>尾页</span>";
			    }else{
			        pageStr += "<span><a href='javascript:void(0)' rel='"+(parseInt(curPage)+1)+"'>下一页</a></span><span><a href='javascript:void(0)' rel='"+totalPage+"'>尾页</a></span>";
			    }  
			    $("#page1").append(pageStr);
            },
            error:function(){
                alert("数据加载失败");
            }
        });
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