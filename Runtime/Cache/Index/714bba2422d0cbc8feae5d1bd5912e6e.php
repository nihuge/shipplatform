<?php if (!defined('THINK_PATH')) exit();?><!--<!DOCTYPE html>-->
<!--<html lang="en">-->
<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <title>港口数据统计</title>-->
<!--    <script src="/tpl/default/Index/Public/js/echarts/echarts.js"></script>-->
<!--</head>-->
<!--<body style="background: rgb(20, 58, 110)">-->
<!--&lt;!&ndash; 为ECharts准备一个具备大小（宽高）的Dom &ndash;&gt;-->
<!--<div id="main" style="width: 1200px;height:800px;margin: 0 auto;"></div>-->
<!--&lt;!&ndash;<div ></div>&ndash;&gt;-->


<!--<script type="text/javascript">-->


<!--</script>-->
<!--</body>-->
<!--</html>-->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
    <script type="text/javascript" src="/tpl/default/Index/Public/js/jquery1.8.3.min.js"></script>
    <script src="/tpl/default/Index/Public/js/echarts/echarts.js"></script>
    <script type="text/javascript" src="/tpl/default/Index/Public/js/jquery.luara.0.0.1.min.js"></script>
    <!--    <script type="text/javascript" src="js/test.js"></script>-->
    <style>
        .luara- {
            position: relative;
            padding: 0;
            overflow: hidden
        }

        .luara- ul {
            padding: inherit;
            margin: 0
        }

        .luara- ul li {
            display: none;
            padding: inherit;
            margin: inherit;
            list-style: none
        }

        .luara- ul li:first-child {
            display: block
        }

        .luara- ul li img {
            width: inherit;
            height: inherit
        }


        body, ul, li, ol, img {
            margin: 0;
            padding: 0
        }

        li {
            list-style: none
        }

        body > h5 {
            margin-left: 20px
        }

        body > div {
            margin-left: 20px
        }

        .example {
        }

        .example ol {
            position: relative;
            width: 80px;
            height: 20px;
            top: -<?php echo ($top); ?>px;
            left: 60px
        }

        .example ol li {
            float: left;
            width: 10px;
            height: 10px;
            margin: 5px;
            background: #fff
        }

        .example ol li.seleted {
            background: #1aa4ca
        }
    </style>
    <!--    <link rel="stylesheet" type="text/css" href="css/test.css" />-->
</head>
<body style="background: rgb(20, 58, 110)">
<div class="example">
    <ul>
        <?php if(is_array($option)): $d_key = 0; $__LIST__ = $option;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($d_key % 2 );++$d_key;?><li>
            <div id="main<?php echo ($d_key); ?>" style="width:900px;height:534px;"></div>
        </li><?php endforeach; endif; else: echo "" ;endif; ?>
<!--        <li>-->
<!--            <div id="main2" style="width:1200px;height:534px;"></div>-->
<!--        </li>-->
<!--        <li>-->
<!--            <div id="main3" style="width:1200px;height:534px;"></div>-->
<!--        </li>-->
<!--        <li>-->
<!--            <div id="main4" style="width:1200px;height:534px;"></div>-->
<!--        </li>-->
    </ul>
    <ol>
        <?php if(is_array($option)): $d_key = 0; $__LIST__ = $option;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($d_key % 2 );++$d_key;?><li></li><?php endforeach; endif; else: echo "" ;endif; ?>
<!--        <li></li>-->
<!--        <li></li>-->
<!--        <li></li>-->
<!--        <li></li>-->
    </ol>
</div>


<script>
    var q=[];
    var r;
    var b;
    var clear;

    $(function () {
        <!--调用Luara示例-->
        $(".example").luara({width: "900", height: "634", interval: 4000, selected: "seleted"});

    });


    <?php if(is_array($option)): $d_key = 0; $__LIST__ = $option;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($d_key % 2 );++$d_key;?>var myChart<?php echo ($d_key); ?> = echarts.init(document.getElementById('main<?php echo ($d_key); ?>'));
    option<?php echo ($d_key); ?> = <?php echo ($v); ?>;
    myChart<?php echo ($d_key); ?>.setOption(option<?php echo ($d_key); ?>);<?php endforeach; endif; else: echo "" ;endif; ?>







    /*<?php
 foreach($option as $key=>$value){ echo "var myChart2 = echarts.init(document.getElementById('main"+$key+"'));\n" + "    option"+$key+" = "+$value+";\n" + "    myChart2.setOption(option"+$key+");"; } ?>*/


    // //基于准备好的dom，初始化echarts实例
    // var myChart1 = echarts.init(document.getElementById('main1'));
    // option1 = <?php echo ($option1); ?>;
    // myChart1.setOption(option1);
    // //基于准备好的dom，初始化echarts实例
    // var myChart2 = echarts.init(document.getElementById('main2'));
    // option2 = <?php echo ($option2); ?>;
    // myChart2.setOption(option2);
    //
    // //基于准备好的dom，初始化echarts实例
    // var myChart3 = echarts.init(document.getElementById('main3'));
    // option3 = <?php echo ($option3); ?>;
    // myChart3.setOption(option3);
    //
    // //基于准备好的dom，初始化echarts实例
    // var myChart4 = echarts.init(document.getElementById('main4'));
    // option4 = <?php echo ($option4); ?>;
    // myChart4.setOption(option4);
</script>
</body>
</html>