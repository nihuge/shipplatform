<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>港口数据统计</title>
    <script src="/tpl/default/Index/Public/js/echarts/echarts.min.js"></script>
</head>
<body style="background: rgb(20, 58, 110)">
<!-- 为ECharts准备一个具备大小（宽高）的Dom -->
<div id="main" style="width: 90%;height:2400px;margin: 0 auto;"></div>
<script type="text/javascript">
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('main'));

    // 指定图表的配置项和数据
    // var option = {
    //     title: {
    //         text: 'ECharts 入门示例'
    //     },
    //     tooltip: {},
    //     legend: {
    //         data:['销量']
    //     },
    //     xAxis: {
    //         data: ["衬衫","羊毛衫","雪纺衫","裤子","高跟鞋","袜子"]
    //     },
    //     yAxis: {},
    //     series: [{
    //         name: '销量',
    //         type: 'bar',
    //         data: [5, 20, 36, 10, 10, 20]
    //     }]
    // };

    var option = <?php echo ($option); ?>;

    var g_cellBar0_y = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAoCAYAAAAhf6DEAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAA6SURBVEhLY2x8/vY/A4mg3zwcTDOBSTLBqGYSwahmEsGoZhLBqGYSwahmEsGoZhLBqGYSwZDUzMAAAJldBMF2UASmAAAAAElFTkSuQmCC';
    var g_cellBarImg0_y = new Image();
    g_cellBarImg0_y.src = g_cellBar0_y;
    console.log(option);
    option.series[0].itemStyle.color.image = g_cellBarImg0_y;



    // var option = {
    //     "title": {
    //         "text": "港口数据统计",
    //         "textStyle": {
    //             "color": "#3cefff"
    //         },
    //         "textAlign": "center"
    //     },
    //     "backgroundColor": "rgb(20, 58, 110)",
    //     "color": ["#3cefff"],
    //     "tooltip": {},
    //     "xAxis": {},
    //     "yAxis": {
    //         "data": ["铜精矿", "其他", "硫酸铵", "硫酸二钠", "氯化钾"],
    //         "nameTextStyle": {"color": "#82b0ec"},
    //         "axisLine": {"lineStyle": {"color": "#82b0ec"}},
    //         "axisLabel": {"textStyle": {"color": "#82b0ec"}}
    //     },
    //     "series": [{
    //         "name": "重量",
    //         "type": "bar",
    //         "data": ["897545293", "892726790", "353696507", "276497765"]
    //     }],
    //     "dataZoom": [
    //     {
    //     "orient": "vertical",
    //     "type": "inside"
    //     },
    //     {
    //         "orient": "vertical",
    //         "type": "slider",
    //         "dataBackground": {
    //             "lineStyle": {
    //                 "color": "#3cefff",
    //                 "width": 2
    //             }
    //         }
    //     }]
    // };

    // var option = {
    //     title: {
    //         text: 'ECharts 入门示例',
    //         textStyle:{
    //             color:"#3cefff"
    //         },
    //         textAlign:"center"
    //     },
    //     "backgroundColor": "rgb(20, 58, 110)",
    //     "color": ["#3cefff"],
    //     tooltip: {},
    //     // legend: {
    //     //     // data:['重量'],
    //     //     // textStyle:{
    //     //     //     "color": ["#3cefff"],
    //     //     // }
    //     // },
    //     xAxis: {
    //
    //     },
    //     yAxis: {
    //         data: ["衬衫","羊毛衫","雪纺衫","裤子","高跟鞋","袜子"],
    //         "nameTextStyle": {
    //             "color": "#82b0ec"
    //         },
    //         "axisLine": {
    //             "lineStyle": {
    //                 "color": "#82b0ec"
    //             }
    //         },
    //         "axisLabel": {
    //             "textStyle": {
    //                 "color": "#82b0ec"
    //             }
    //         }
    //
    //     },
    //
    //     series: [{
    //         name: '重量',
    //         type: 'bar',
    //         data: [5, 20, 36, 10, 10, 20]
    //     }],
    //     dataZoom:[{
    //         orient:'vertical',
    //         type:'inside'
    //     },
    //         {
    //             orient:'vertical',
    //             type:'slider',
    //             dataBackground:{
    //                 'lineStyle':{
    //                     color:'#3cefff',
    //                     width:2
    //                 }
    //             }
    //         }]
    // };
    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
</script>
</body>
</html>