<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>港口数据统计</title>
    <script type="text/javascript" src="/shipplatform2/tpl/default/Index/Public/js/jquery1.8.3.min.js"></script>
    <script type="text/javascript" src="/shipplatform2/tpl/default/Index/Public/js/echarts/echarts.js"></script>
    <script type="text/javascript" src="/shipplatform2/tpl/default/Index/Public/js/jquery.luara.0.0.1.min.js"></script>
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
            top: -90px;
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
</head>
<body style="background: rgb(18,28,75)">
<!-- 为ECharts准备一个具备大小（宽高）的Dom -->
<!--transform:rotate(90deg);-->
<div>
    <div id="mapmain" style="width: 50%;height:600px;float: left"></div>
    <div id="cargocountmain" style="width: 50%;height: 600px;float: left">

    </div>
</div>
<script type="text/javascript">
    var q = [];
    var r;
    var b;
    var clear;
    // 基于准备好的dom，初始化echarts实例
    // var myChart = echarts.init(document.getElementById('main'));
    // var uploadedDataURL = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/nanjin.json";
    var nanjin = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/nanjin.json";
    var cargoCharts = [];
    // var pukou = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/pukou.json";
    // var liuhe = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/liuhe.json";
    // var xixia = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/xixia.json";

    var g_cellBar0_y = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAoCAYAAAAhf6DEAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAA6SURBVEhLY2x8/vY/A4mg3zwcTDOBSTLBqGYSwahmEsGoZhLBqGYSwahmEsGoZhLBqGYSwZDUzMAAAJldBMF2UASmAAAAAElFTkSuQmCC';
    var g_cellBarImg0_y = new Image();
    g_cellBarImg0_y.src = g_cellBar0_y;

    $.get(nanjin, function (nanjinJson) {
        echarts.registerMap('南京', nanjinJson);
        var mapChart = echarts.init(document.getElementById('mapmain'));

        // var geoCoordMap = {
        //     "天宇码头": [119.0831932327,32.2136299597],
        //     "惠宁码头": [118.8776496481,32.1713841944],
        //     "西坝码头": [118.9021274516,32.1995042101],
        // };
        var geoCoordMap = <?php echo ($GobalData); ?>;


        var convertData = function (data) {
            var res = [];
            for (var i = 0; i < data.length; i++) {
                var geoCoord = geoCoordMap[data[i].name];
                if (geoCoord) {
                    res.push({
                        name: data[i].name,
                        value: geoCoord.concat(data[i].value)
                    });
                }
            }
            return res;
        };
        /*visualMap: [{
                        // type: "piecewise",
                        calculable: true,
                        min:1991,
                        max:1400000,
                        inRange: {
                            color: ['#50a3ba', '#eac736', '#d94e5d']
                        },
                        textStyle: {
                            color: '#fff'
                        }
                    }],*/
        /*grid: {
            right: '5%',
                bottom: '3%',
                width: '25%',
                height: '80%'
        },*/


        /*orient: 'vertical',
            y: 'bottom',
            x: 'right',
            data: ['港口'],
            textStyle: {
            color: '#fff'
        }*/

        /*tooltip: {
            trigger: 'item',
                position: [500, 100],
                formatter: function (params) {
                return params.name + ' : ' + params.value[2] + "吨";
            }
        },*/
        var option = {
            backgroundColor: "rgb(18,28,75)",
            xAxis: {
                gridIndex: 0,
                axisTick: {
                    show: false
                },
                axisLabel: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                axisLine: {
                    show: false
                }
            },
            yAxis: {
                gridIndex: 0,
                interval: 0,
                axisTick: {
                    show: false
                },
                axisLabel: {
                    show: true
                },
                splitLine: {
                    show: false
                },
                axisLine: {
                    show: false,
                }
            },
            geo: {
                map: '南京',
                label: {
                    emphasis: {
                        show: false
                    }
                },
                itemStyle: {
                    normal: {
                        areaColor: '#1737A8',
                        borderColor: '#00D2FF'
                    },
                    emphasis: {
                        areaColor: '#1507A8'
                    }
                }
            },
            series: [
                {
                    name: '港口',
                    type: 'scatter',
                    coordinateSystem: 'geo',
                    data: convertData(<?php echo ($optionData); ?>),
                    symbolSize: 15,
                    label: {
                        show: false,
                        formatter: function (params) {
                            return params.name + ' : ' + params.value[2] + "吨";
                        },
                        offset: [0, 0],
                        rotate: -90,
                        fontSize: 12,
                        normal: {
                            show: true,
                            formatter: '{b}',
                            offset: [18, 0],
                            rotate: -90,
                            fontSize: 15,
                        },
                        emphasis: {
                            show: true,
                            formatter: function (params) {
                                return params.name + ' : ' + params.value[2] + "吨";
                            },
                            offset: [18, 0],
                            rotate: -90,
                            fontSize: 17,
                        },

                    },

                    itemStyle: {
                        emphasis: {
                            color: '#00D2FF',
                            borderColor: '#fff',
                            borderWidth: 1
                        },
                        normal: {
                            color: '#00D2FF',
                            borderColor: '#fff',
                            borderWidth: 1
                        }
                    }
                }
            ]

        };
        mapChart.setOption(option);
        $('canvas[data-zr-dom-id="zr_0"]').css({transform: "rotate(-90deg)", float: "left",});
        mapChart.on('click', function (params) {
            console.log(params);
            if (params.componentType == "series") {
                let pier = params.name;
                console.log(pier);
                console.log(cargoCharts);
                //销毁货物汇总的所有图标
                for (let ChartsIndex in cargoCharts) {
                    cargoCharts[ChartsIndex].dispose();
                }
                cargoCharts = [];
                $.ajax({
                    url: "<?php echo U('Index/newGKdCargoCount');?>/pier/" + pier,
                    type: 'GET',
                    dataType: "json",
                    success: function (data) {
                        console.log(data);
                        let option = data.option;
                        let top = data.top;
                        let htmlText = '<div class="example">';
                        let ulText = '<ul>';
                        let olText = '<ol>';
                        for (let i = 0; i < option.length; i++) {
                            ulText += '<li><div id="main' + i + '" style="width:100%;height:534px;"></div></li>';
                            olText += '<li></li>';
                        }
                        ulText += '</ul>';
                        olText += '</ol>';
                        htmlText += ulText + olText + '</div>';
                        $('#cargocountmain').html(htmlText);
                        <!--调用Luara示例-->
                        clear = 'y';
                        r = '';
                        b = '';
                        clear = '';
                        for (let iit in q) {
                            clearTimeout(q[iit]);
                        }
                        for (let i = 0; i < option.length; i++) {
                            cargoCharts.push(echarts.init(document.getElementById('main' + i)));
                            option[i].series[0].itemStyle.color.image = g_cellBarImg0_y;
                            console.log(option[i]);
                            cargoCharts[i].setOption(option[i]);
                        }

                        // clearInterval(q);
                        if (option.length > 1) {
                            $(".example").luara({width: "100%", height: "634", interval: 4000, selected: "seleted"});
                        } else {
                            $(".example ol").html("");
                        }

                    }
                })
            }

        });

        // chart.setOption({
        //     series: [{
        //         type: 'map',
        //         map: 'nanjin'
        //     }]
        // });
    });


</script>
</body>
</html>