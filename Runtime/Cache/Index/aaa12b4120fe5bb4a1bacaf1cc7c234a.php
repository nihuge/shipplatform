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


        .example {
        }

        .example ol {
            position: relative;
            width: 100%;
            height: 20px;
            top: -40px;
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

    <div style="left: 0;position: fixed;top: 0;width: 100%;z-index: 100;">
        <div style="margin: 0 auto;width: 30%; height: 40px; pointer-events: auto; display: flex; align-items: center; justify-content: center; color: rgb(255, 255, 255); font-weight: lighter; font-family: 'Microsoft Yahei', Arial, sans-serif; font-size: 25px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
            码头数据实时统计
        </div>
        <div style="width: 80%; height: 30px; pointer-events: auto;margin: 0 auto;">
            <div style="background-image: url('/shipplatform2/tpl/default/Index/Public/image/down.gif');width: 100%; height: 100%;background-size: 100%; background-repeat: no-repeat; background-position: center center; "></div>
        </div>
        <div style='width: 90%; height: 8px; z-index: 0; transform: rotate(0deg); opacity: 1; pointer-events: none; margin: 0 auto;'>
            <div style='width: 100%; height: 8px; pointer-events: auto; background-image: url("/shipplatform2/tpl/default/Index/Public/image/fenge.png"); background-repeat: no-repeat; background-size: 100% 100%;margin: 0 auto; image-rendering: -webkit-optimize-contrast;'>
            </div>
        </div>

    </div>


<div style="width: 100%;height: 600px;overflow:hidden;">
    <div id="mapmain" style="width: 50%;height:600px;float: left;margin-top: 0px;"></div>
    <div id="cargocountmain" style="width: 50%;height: 420px;float: left;margin-top: 80px;overflow:hidden;">
    </div>
</div>


<div style="width: 100%;height: 400px; margin-top: 20px;">
    <div id="cargoPstmain" style="width: 30%;height: 400px;float: left;"></div>
</div>
<script type="text/javascript">
    //货物汇总轮播变量
    var q = [];
    var r;
    var b;
    var clear;
    var cargoCharts;
    var cargoCount = 0;
    var nowoption = 0;
    var e;
    var options;


    //货物占比轮播变量
    var cargoPstCharts;
    var PstE;
    var PstOptions;


    // 基于准备好的dom，初始化echarts实例
    // var myChart = echarts.init(document.getElementById('main'));
    // var uploadedDataURL = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/nanjin.json";
    var nanjin = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/nanjin.json";

    // var pukou = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/pukou.json";
    // var liuhe = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/liuhe.json";
    // var xixia = "/shipplatform2/tpl/default/Index/Public/js/echarts/map/xixia.json";

    var g_cellBar0_y = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAoCAYAAAAhf6DEAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAA6SURBVEhLY2x8/vY/A4mg3zwcTDOBSTLBqGYSwahmEsGoZhLBqGYSwahmEsGoZhLBqGYSwZDUzMAAAJldBMF2UASmAAAAAElFTkSuQmCC';
    var g_cellBarImg0_y = new Image();
    g_cellBarImg0_y.src = g_cellBar0_y;

    function v() {
        q = setTimeout(
            function () {
                nowoption = nowoption >= cargoCount ? 0 : nowoption + 1;
                cargoCharts.setOption(options[nowoption]);

                cargoPstCharts.setOption(PstOptions[nowoption]);

                PstE.removeClass('seleted');
                PstE.eq(nowoption).addClass('seleted');

                e.removeClass('seleted');
                e.eq(nowoption).addClass('seleted');
                v();
            }, 2000
        );
    }

    function t() {
        cargoCharts.setOption(options[nowoption]);
        cargoPstCharts.setOption(PstOptions[nowoption]);

    }

    function clears() {
        clearTimeout(q);
        q = 0;
    }


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
                options = null;
                clears();
                let pier = params.name;
                console.log(pier);
                console.log(cargoCharts);
                //销毁货物汇总图表
                if (cargoCharts != null) {
                    cargoCharts.dispose();
                    cargoCharts = null;
                }

                $.ajax({
                    url: "<?php echo U('Index/newGKdCargoCount');?>/pier/" + pier,
                    type: 'GET',
                    dataType: "json",
                    success: function (data) {
                        // console.log(data);
                        options = data.option;
                        let top = data.top;
                        let htmlText = '<div class="example">';
                        let ulText = '<ul><li><div id="cargomain" style="width:100%;height:500px;"></div></li></ul>';
                        let olText = '<ol>';
                        cargoCount = options.length - 1;

                        for (let i = 0; i < options.length; i++) {
                            olText += '<li></li>';
                        }

                        olText += '</ol>';
                        htmlText += ulText + olText + '</div>';
                        $('#cargocountmain').html(htmlText);
                        <!--调用Luara示例-->
                        clear = 'y';
                        r = '';
                        b = '';
                        clear = '';
                        nowoption = 0;


                        cargoCharts = echarts.init(document.getElementById('cargomain'));

                        for (let i = 0; i < options.length; i++) {
                            options[i].series[0].itemStyle.color.image = g_cellBarImg0_y;
                        }

                        cargoCharts.setOption(options[0]);
                        // console.log(option);
                        if (options.length > 1) {
                            e = $(".example").find("ol").eq(0).find("li");
                            e.eq(0).addClass('seleted');
                            console.log(options);

                            v();
                            e.mouseover(function () {
                                e.removeClass('seleted');
                                nowoption = e.index($(this));
                                $(this).addClass('seleted');

                                PstE.removeClass('seleted');
                                PstE.eq(nowoption).addClass('seleted');

                                t();

                                clears();
                            });

                            $(".example").mouseleave(function () {
                                v();
                            });
                            $(".example").mouseover(function () {
                                clears();
                            });


                        } else {
                            $(".example ol").html("");
                        }
                    }
                });

                //异步获取
                $.ajax({
                    url: "<?php echo U('Index/GKCargoPst');?>/pier/" + pier,
                    type: 'GET',
                    dataType: "json",
                    success: function (data) {
                        // console.log(data);
                        PstOptions = data.option;
                        let top = data.top;
                        let htmlText = '<div id="examplePst" class="example">';
                        let ulText = '<ul><li><div id="cargoPstCharts" style="width:100%;height:400px;"></div></li></ul>';
                        let olText = '<ol>';

                        for (let i = 0; i < PstOptions.length; i++) {
                            olText += '<li></li>';
                        }

                        olText += '</ol>';
                        htmlText += ulText + olText + '</div>';
                        $('#cargoPstmain').html(htmlText);

                        cargoPstCharts = echarts.init(document.getElementById('cargoPstCharts'));

                        for (let i = 0; i < PstOptions.length; i++) {
                            PstOptions[i].series[0].animationDelay = function (idx) {
                                return Math.random() * 200;
                            };
                        }

                        cargoPstCharts.setOption(PstOptions[0]);
                        // console.log(option);
                        if (PstOptions.length > 1) {
                            PstE = $("#examplePst").find("ol").eq(0).find("li");
                            PstE.eq(0).addClass('seleted');
                            console.log(options);

                            // v();
                            PstE.mouseover(function () {
                                PstE.removeClass('seleted');
                                nowoption = PstE.index($(this));
                                $(this).addClass('seleted');

                                e.removeClass('seleted');
                                e.eq(nowoption).addClass('seleted');

                                t();
                                clears();
                            });

                            $("#examplePst").mouseleave(function () {
                                v();
                            });
                            $("#examplePst").mouseover(function () {
                                clears();
                            });

                        } else {
                            $("#examplePst ol").html("");
                        }
                    }
                })
            }

        });
    });


</script>
</body>
</html>