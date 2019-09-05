<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta charset="utf-8"/>
    <title>
        船管理
        -后台管理
    </title>
    <meta name="description" content="overview &amp; stats"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="/Public/Admin/css/bootstrap.css"/>
    <link rel="stylesheet" href="/Public/Admin/static/font-awesome/css/font-awesome.css"/>
    <!-- page specific plugin styles -->
    <!-- text fonts -->
    <link rel="stylesheet" href="/Public/Admin/css/ace-fonts.css"/>
    <!-- ace styles -->
    <link rel="stylesheet" href="/Public/Admin/css/ace.css" class="ace-main-stylesheet" id="main-ace-style"/>
    <link rel="stylesheet" href="/Public/Admin/css/ace-skins.css"/>
    <link rel="stylesheet" href="/Public/Admin/css/ace-rtl.css"/>
    <script src="/Public/Admin/js/ace-extra.js"></script>
    <!-- layerjs -->
    <script src="/Public/Admin/js/jquery-1.9.1.min.js"></script>
    <script src="/Public/Admin/js/layer/layer.js"></script>


    <!-- 分页样式 -->
    <link rel="stylesheet" type="text/css" href="/Public/page.css">
    <!-- 时间插件 -->
    <!-- <link rel="stylesheet" type="text/css" href="/Public/Admin/static/laydate-v1.1/need/laydate.css"> -->
    <script src="/Public/laydate/laydate.js"></script>
    <script src="/Public/Admin/js/jquery.bigautocomplete.js"></script>
    <script src="/Public/layui/layui.js"></script>
    <link rel="stylesheet" href="/Public/layui/css/layui.css"/>


    <link rel="stylesheet" href="/Public/Admin/css/jquery.bigautocomplete.css" type="text/css"/>


    <script src="/Public/Admin/js/jquery.bigautocomplete.js"></script>
    <link rel="stylesheet" href="/Public/Admin/css/jquery.bigautocomplete.css" type="text/css"/>
    
</head>

<body class="no-skin">
<!-- #section:basics/navbar.layout -->
<div id="navbar" class="navbar navbar-default          ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <!-- #section:basics/sidebar.mobile.toggle -->
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <!-- /section:basics/sidebar.mobile.toggle -->
        <div class="navbar-header pull-left">
            <!-- #section:basics/navbar.layout.brand -->
            <a href="#" class="navbar-brand">
                <small>
                    <i class="fa fa-leaf"></i>
                    油船计量云平台后台管理系统
                </small>
            </a>
            <!-- /section:basics/navbar.layout.brand -->
            <!-- #section:basics/navbar.toggle -->
            <!-- /section:basics/navbar.toggle -->
        </div>
        <!-- #section:basics/navbar.dropdown -->
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="purple">
                    <a href="javascript:;" onclick="cleancache()">清理缓存</a>
                </li>
                <li class="light-blue">
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <div id="myclock"></div>
                    </a>
                </li>
                <!-- #section:basics/navbar.user_menu -->
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
								<span class="user-info">
									<small>欢迎光临,</small>
									<?php echo ($adminmsg['name']); ?>
								</span>

                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                        <li>
                            <a href="#" onclick="changePwd(<?php echo ($adminmsg['id']); ?>)">修改密码</a>
                        </li>
                        <li class="divider">

                        </li>
                        <li>
                            <a href="<?php echo U('Index/loginout');?>">
                                <i class="icon-off"></i> 退出
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- /section:basics/navbar.user_menu -->
            </ul>
        </div>
        <!-- /section:basics/navbar.dropdown -->
    </div>
    <!-- /.navbar-container -->
</div>

<script>
    function changePwd(id) {
        layer.open({
            id: 1,
            type: 1,
            title: '修改密码',
            skin: 'layui-layer-rim',
            area: ['450px', 'auto'],

            content: ' <div class="row" style="width: 420px;  margin-left:7px; margin-top:10px;">'
                + '<div class="col-sm-12">'
                + '<div class="input-group">'
                + '<span class="input-group-addon" style="margin-top: 10px"> 原 密 码   :</span>'
                + '<input id="oldpwd" type="password" class="form-control" placeholder="请输入密码">'
                + '</div>'
                + '</div>'
                + '<div class="col-sm-12" style="margin-top: 10px">'
                + '<div class="input-group">'
                + '<span class="input-group-addon" > 新 密 码   :</span>'
                + '<input id="newpwd" type="password" class="form-control" placeholder="请输入密码">'
                + '</div>'
                + '</div>'
                + '<div class="col-sm-12" style="margin-top: 10px">'
                + '<div class="input-group">'
                + '<span class="input-group-addon">确认密码:</span>'
                + '<input id="newpwd2" type="password" class="form-control" placeholder="请再输入一次密码">'
                + '</div>'
                + '</div>'
                + '</div>'
            ,
            btn: ['保存', '取消'],
            btn1: function (index, layero) {
                var old_pwd = top.$('#oldpwd').val();
                var new_pwd = top.$('#newpwd').val();
                var new_pwd2 = top.$('#newpwd2').val();

                if (new_pwd !== new_pwd2) {
                    //提示层
                    layer.alert("新密码和确认密码不一致！", {
                        icon: 5
                    });
                } else {
                    $.ajax({
                        url: "<?php echo U('Admin/changepwd');?>",
                        type: "POST",
                        data: {
                            "id": id,
                            'old_pwd': old_pwd,
                            'new_pwd': new_pwd,
                            'new_pwd2': new_pwd2
                        },
                        dataType: "json",
                        success: function (data) {
                            if (data.state == 1) {
                                layer.alert('修改成功', {icon: 6}, function () {
                                    //刷新
                                    location.reload();
                                });
                            } else {
                                layer.alert(data.msg, {
                                    icon: 5
                                });
                            }
                        }
                    });
                }
            },
            btn2: function (index, layero) {
                layer.close(index);
            }

        });
    }
</script>

<!-- /section:basics/navbar.layout -->
<div class="main-container ace-save-state" id="main-container">
    <script type="text/javascript">
        try {
            ace.settings.loadState('main-container')
        } catch (e) {
        }
    </script>
    <!-- #section:basics/sidebar -->
    <div id="sidebar" class="sidebar                  responsive                    ace-save-state">
        <script type="text/javascript">
            try {
                ace.settings.loadState('sidebar')
            } catch (e) {
            }
        </script>
        <div class="sidebar-shortcuts" id="sidebar-shortcuts">
            <div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
                <button class="btn btn-success">
                    <i class="ace-icon fa fa-signal"></i>
                </button>
                <button class="btn btn-info">
                    <i class="ace-icon fa fa-pencil"></i>
                </button>
                <!-- #section:basics/sidebar.layout.shortcuts -->
                <button class="btn btn-warning">
                    <i class="ace-icon fa fa-users"></i>
                </button>
                <button class="btn btn-danger">
                    <i class="ace-icon fa fa-cogs"></i>
                </button>
                <!-- /section:basics/sidebar.layout.shortcuts -->
            </div>
            <div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
                <span class="btn btn-success"></span>
                <span class="btn btn-info"></span>
                <span class="btn btn-warning"></span>
                <span class="btn btn-danger"></span>
            </div>
        </div>
        <!-- /.sidebar-shortcuts -->
        <ul class="nav nav-list">
            <li
            
            >
            <a href="<?php echo U('Index/index');?>">
                <i class="menu-icon fa fa-tachometer"></i>
                <span class="menu-text"> 控制台 </span>
            </a>
            <b class="arrow"></b>
            </li>
            <li
            
            >
            <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa  fa-key"></i>
                <span class="menu-text">
                                系统设置
                            </span>

                <b class="arrow fa fa-angle-down"></b>
            </a>
            <b class="arrow"></b>
            <ul class="submenu">
                <li
                
                >
                <a href="<?php echo U('MySQLReBack/index');?>">
                    <i class="menu-icon fa fa-caret-right"></i>
                    数据备份与还原
                </a>
                <b class="arrow"></b>
                </li>
                <li
                
                >
                <a href="<?php echo U('AuthRule/index');?>">
                    <i class="menu-icon fa fa-caret-right"></i>
                    权限管理
                </a>
                <b class="arrow"></b>
                </li>
                <li
                
                >
                <a href="<?php echo U('AuthGroup/index');?>">
                    <i class="menu-icon fa fa-caret-right"></i>
                    用户组管理
                </a>
                <b class="arrow"></b>
                </li>
            </ul>
            </li>


            <li
            class="active open"
            >
            <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa fa-gavel"></i>
                <span class="menu-text">
                                审核管理
                            </span>
                <?php if($review_count_arr['all_review_count'] > 0): ?><span class="layui-badge"><?php echo ($review_count_arr['all_review_count']); ?></span><?php endif; ?>
                <b class="arrow fa fa-angle-down"></b>
            </a>
            <b class="arrow"></b>
            <ul class="submenu">
                <li
                
                >
                <a href="<?php echo U('Review/create_ship_index');?>">
                    <i class="menu-icon fa fa-caret-right"></i>
                    新建油船审核
                    <?php if($review_count_arr['ship_count'] > 0): ?><span class="layui-badge"><?php echo ($review_count_arr['ship_count']); ?></span><?php endif; ?>
                </a>
                <b class="arrow"></b>
                </li>
                <li
                class="active"
                >
                <a href="<?php echo U('Review/create_sh_index');?>">
                    <i class="menu-icon fa fa-caret-right"></i>
                    新建散货船审核
                    <?php if($review_count_arr['sh_ship_count'] > 0): ?><span class="layui-badge"><?php echo ($review_count_arr['sh_ship_count']); ?></span><?php endif; ?>
                </a>
                <b class="arrow"></b>
                </li>
                <li
                
                >
                <a href="<?php echo U('Review/review_ship_index');?>">
                    <i class="menu-icon fa fa-caret-right"></i>
                    修改油船审核
                    <?php if($review_count_arr['ship_review_count'] > 0): ?><span class="layui-badge"><?php echo ($review_count_arr['ship_review_count']); ?></span><?php endif; ?>
                </a>
                <b class="arrow"></b>
                </li>

                <li
                
                >
                <a href="<?php echo U('Review/review_sh_index');?>">
                    <i class="menu-icon fa fa-caret-right"></i>
                    修改散货船船审核
                    <?php if($review_count_arr['sh_ship_review_count'] > 0): ?><span class="layui-badge"><?php echo ($review_count_arr['sh_ship_review_count']); ?></span><?php endif; ?>
                </a>
                <b class="arrow"></b>
                </li>

            </ul>
            </li>



            <li
            
            >
            <a href="<?php echo U('Admin/index');?>">
                <i class="menu-icon fa fa-users"></i>
                <span class="menu-text"> 管理员管理 </span>
            </a>
            </li>

            <li
            
            >
            <a href="<?php echo U('User/index');?>">
                <i class="menu-icon fa fa-user"></i>
                <span class="menu-text"> 用户管理 </span>
            </a>
            </li>

            <li
            
            >
            <a href="<?php echo U('Firm/index');?>">
                <i class="menu-icon fa fa-desktop"></i>
                <span class="menu-text"> 公司管理 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('Ship/index');?>">
                <i class="menu-icon fa fa-ship"></i>
                <span class="menu-text"> 船舶管理 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('Cabin/index');?>">
                <i class="menu-icon fa fa-text-width"></i>
                <span class="menu-text"> 船舱管理 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('Upload/index');?>">
                <i class="menu-icon fa fa-cloud-upload"></i>
                <span class="menu-text"> 数据导入 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('Recharge/index');?>">
                <i class="menu-icon fa fa-money"></i>
                <span class="menu-text"> 充值管理 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('Consumption/index');?>">
                <i class="menu-icon fa fa-pencil-square-o"></i>
                <span class="menu-text"> 消费管理 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('Article/index');?>">
                <i class="menu-icon fa fa-comment-o"></i>
                <span class="menu-text"> 资讯管理 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('personality/index');?>">
                <i class="menu-icon fa  fa-bookmark"></i>
                <span class="menu-text"> 个性化管理 </span>
            </a>
            </li>
            <li
            
            >
            <a href="<?php echo U('Search/index');?>">
                <i class="menu-icon fa  fa-search"></i>
                <span class="menu-text"> 作业查询 </span>
            </a>
            </li>
        </ul>
        <!-- /.nav-list -->
        <!-- #section:basics/sidebar.layout.minimize -->
        <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"
               data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
        <!-- /section:basics/sidebar.layout.minimize -->
    </div>
    <!-- /section:basics/sidebar -->
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li>
                        <i class="ace-icon fa fa-home home-icon"></i>
                        <a href="{Index/index}">首页</a>
                    </li>
                    
    <li class="active">
        船管理
    </li>
    <li class="active">
        船列表
    </li>

                </ul>
            </div>
            <div class="page-content">
                <div class="col-xs-12">
                    
    <div class="page-container">
        <div>
            <form action="/admin.php?s=/Review/create_sh_index" method="get">
                <input type="hidden" name="c" value="Review">
                <input type="hidden" name="a" value="create_ship_index">
                <!--                <select class=" col-xs-10 col-sm-2" id="form-field-select-1" name="firmid">-->
                <!--                    <option value="">选择所属公司</option>-->
                <!--                    <?php if(is_array($firmlist)): $i = 0; $__LIST__ = $firmlist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>-->
                <!--                        <option value="<?php echo ($v['id']); ?>"><?php echo ($v['firmname']); ?></option>-->
                <!--<?php endforeach; endif; else: echo "" ;endif; ?>-->
                <!--                </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
                <label style="color: #858585;">船名：<input style="border:1px solid #D5D5D5;height: 30px;"
                                                         id="form-field-input-1" name="shipname"></label>&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;审核状态：<select name="del_sign"
                                                                       style='width:130px;text-align: center'>
                <option value="">--选择审核状态--</option>
                <option value="1">未审核</option>
                <option value="2">已审核</option>
            </select>
                <button class="btn btn-sm btn-primary">查询</button>
            </form>
        </div>
        <br/>

        <h4></h4>
        <table id="sample-table-1" class="table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>船名</th>
                <th>所属公司</th>
                <th>编号</th>
                <th>舱总数</th>
                <th>操作</th>
            </tr>
            </thead>
            <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr align='center'>
                    <td><?php echo ($v['id']); ?></td>
                    <td><?php echo ($v['shipname']); ?></td>
                    <td><?php echo ($v['firmname']); ?></td>
                    <td><?php echo ($v['number']); ?></td>
                    <td><?php echo ($v['cabinnum']); ?></td>
                    <td>
                        <a href="<?php echo U('Review/edit',array('id'=>$v['id']));?>">查看</a>
                    </td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            <tr>
                <td colspan=6 class="pages"><?php echo ($page); ?></td>
            </tr>
        </table>
        <script type="text/javascript">
            function delShip(shipid) {
                layer.confirm('您确定要删除船舶吗？', {
                    btn: ['确定', '取消'] //按钮
                }, function () {
                    $.ajax({
                        url: "<?php echo U('Ship/del_ship');?>",
                        type: "POST",
                        data: {
                            shipid: shipid
                        },
                        success: function (data) {
                            if (data.code == 1) {
                                //刷新
                                location.reload()
                            } else {
                                layer.msg(data.msg, {
                                    icon: 5
                                });
                            }

                        }

                    });
                })
            }

            function recoverShip(shipid) {
                layer.confirm('您确定要恢复船舶吗？', {
                    btn: ['确定', '取消'] //按钮
                }, function () {
                    $.ajax({
                        url: "<?php echo U('Ship/recoverShip');?>",
                        type: "POST",
                        data: {
                            shipid: shipid
                        },
                        success: function (data) {
                            if (data.code == 1) {
                                //刷新
                                location.reload()
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
        <?php if($adminmsg['group_id'] == 1): ?><script type="text/javascript">
                function relDelShip(shipid) {
                    layer.confirm('危险操作，此操作无法撤回数据。您确定要彻底删除船舶吗？', {
                        btn: ['确定', '取消'] //按钮
                    }, function () {
                        $.ajax({
                            url: "<?php echo U('Ship/relDelShip');?>",
                            type: "POST",
                            data: {
                                shipid: shipid
                            },
                            success: function (data) {
                                if (data.code == 1) {
                                    //刷新
                                    location.reload()
                                } else {
                                    layer.msg(data.msg, {
                                        icon: 5
                                    });
                                }
                            }
                        });
                    })
                }
            </script><?php endif; ?>
    </div>

                </div>
            </div>
            <!-- /.page-content -->
        </div>
    </div>
    <!-- /.main-content -->
    <div class="footer">
        <div class="footer-inner">
            <!-- #section:basics/footer -->
            <div class="footer-content">
                    <span class="bigger-100">
							技术支持@<a href="http://new.xzitc.com/" target="_blank"><span
                            class="blue bolder">南京携众</span></a> 2018-2019
                    </span>
            </div>
            <!-- /section:basics/footer -->
        </div>
    </div>
    <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
        <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
    </a>
</div>
<!-- /.main-container -->

<script src="/Public/Admin/js/jquery.js"></script>

<script type="text/javascript">
    if ('ontouchstart' in document.documentElement) document.write("<script src='../components/_mod/jquery.mobile.custom/jquery.mobile.custom.js'>" + "<" + "/script>");
</script>
<script src="/Public/Admin/js/bootstrap.js"></script>
<script src="/Public/Admin/js/jquery-ui.custom.js"></script>
<script src="/Public/Admin/js/jquery.ui.touch-punch.js"></script>
<script src="/Public/Admin/js/jquery.easypiechart.js"></script>
<script src="/Public/Admin/static/Flot/jquery.flot.js"></script>
<script src="/Public/Admin/static/Flot/jquery.flot.pie.js"></script>
<script src="/Public/Admin/static/Flot/jquery.flot.resize.js"></script>
<!-- ace scripts -->
<script src="/Public/Admin/js/src/elements.scroller.js"></script>
<script src="/Public/Admin/js/src/elements.colorpicker.js"></script>
<script src="/Public/Admin/js/src/elements.fileinput.js"></script>
<script src="/Public/Admin/js/src/elements.typeahead.js"></script>
<script src="/Public/Admin/js/src/elements.wysiwyg.js"></script>
<script src="/Public/Admin/js/src/elements.spinner.js"></script>
<script src="/Public/Admin/js/src/elements.treeview.js"></script>
<script src="/Public/Admin/js/src/elements.wizard.js"></script>
<script src="/Public/Admin/js/src/elements.aside.js"></script>
<script src="/Public/Admin/js/src/ace.js"></script>
<script src="/Public/Admin/js/src/ace.basics.js"></script>
<script src="/Public/Admin/js/src/ace.scrolltop.js"></script>
<script src="/Public/Admin/js/src/ace.ajax-content.js"></script>
<script src="/Public/Admin/js/src/ace.touch-drag.js"></script>
<script src="/Public/Admin/js/src/ace.sidebar.js"></script>
<script src="/Public/Admin/js/src/ace.sidebar-scroll-1.js"></script>
<script src="/Public/Admin/js/src/ace.submenu-hover.js"></script>
<script src="/Public/Admin/js/src/ace.widget-box.js"></script>
<script src="/Public/Admin/js/src/ace.settings.js"></script>
<script src="/Public/Admin/js/src/ace.settings-rtl.js"></script>
<script src="/Public/Admin/js/src/ace.settings-skin.js"></script>
<script src="/Public/Admin/js/src/ace.widget-on-reload.js"></script>
<script src="/Public/Admin/js/src/ace.searchbox-autocomplete.js"></script>
<link rel="stylesheet" href="/Public/Admin/css/ace.onpage-help.css"/>
<script src="/Public/Admin/js/src/elements.onpage-help.js"></script>
<script src="/Public/Admin/js/src/ace.onpage-help.js"></script>
<script type="text/javascript">
    function clock_12h() {
        var today = new Date(); //获得当前时间
        //获得年、月、日，Date()函数中的月份是从0－11计算
        var year = today.getFullYear();
        var month = today.getMonth() + 1;
        var date = today.getDate();
        var hour = today.getHours(); //获得小时、分钟、秒
        var minute = today.getMinutes();
        var second = today.getSeconds();

        var apm = "上午"; //默认显示上午: AM
        if (hour > 12) //按12小时制显示
        {
            hour = hour - 12;
            apm = "下午";
        }
        var weekday = 0;
        switch (today.getDay()) {
            case 0:
                weekday = "星期日";
                break;
            case 1:
                weekday = "星期一";
                break;
            case 2:
                weekday = "星期二";
                break;
            case 3:
                weekday = "星期三";
                break;
            case 4:
                weekday = "星期四";
                break;
            case 5:
                weekday = "星期五";
                break;
            case 6:
                weekday = "星期六";
                break;
        }

        /*设置div的内容为当前时间*/
        document.getElementById("myclock").innerHTML = year + "年" + month + "月" + date + "日&nbsp;<span>" + hour + ":" + minute + ":" + second + "</span>&nbsp;" + weekday + "&nbsp;" + apm;
    }

    /*使用setInterval()每间隔指定毫秒后调用clock_12h()*/
    var myTime = setInterval("clock_12h()", 1000);

    //清理缓存
    function cleancache() {
        if (confirm('确定要清理缓存吗？')) {
            flag = true;
        } else {
            flag = false;
        }
        if (flag == true) {
            $.ajax({
                type: "POST",
                url: "<?php echo U('Index/cleancache');?>",
                dataType: "html",
                // data:"id="+id+'&status='+status,
                success: function (msg) {
                    if (msg == 1) {
                        alert('清理成功！');
                    } else {
                        alert('操作失败！');
                    }
                    location.reload();
                }
            });
        }
    }
</script>
</body>

</html>