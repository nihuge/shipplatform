<?php if (!defined('THINK_PATH')) exit();?><!--<!DOCTYPE html>-->
<!--<html lang="en">-->
<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <title>港口数据统计</title>-->
<!--    <script src="/shipplatform2/tpl/default/Index/Public/js/echarts/echarts.js"></script>-->
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
    <script type="text/javascript" src="/shipplatform2/tpl/default/Index/Public/js/jquery1.8.3.min.js"></script>
    <script src="/shipplatform2/tpl/default/Index/Public/js/echarts/echarts.js"></script>
    <!--    <script type="text/javascript" src="js/test.js"></script>-->
    <!--    <link rel="stylesheet" type="text/css" href="css/test.css" />-->
</head>
<body style="">
<div id="main" style="width:900px;height:534px;"></div>
<script>
    var q=[];
    var r;
    var b;
    var clear;

    var myChart = echarts.init(document.getElementById('main'));
    option = <?php echo ($option); ?>;
    myChart.setOption(option)

</script>
</body>
</html>