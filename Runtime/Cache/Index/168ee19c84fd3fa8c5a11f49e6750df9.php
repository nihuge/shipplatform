<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>港口数据统计</title>
    <script src="http://libs.baidu.com/jquery/2.1.4/jquery.min.js"></script>
    <script src="https://www.17sucai.com/preview/1749733/2019-07-03/201512011606/js/autotyper.min.js"></script>
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
<body style="background: rgb(19,20,60)">
<!-- 为ECharts准备一个具备大小（宽高）的Dom -->
<div style="width: 100%">
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

<div style="width: 100%;height: 632px;">
    <div style="float:left;width: 49%;height: 632px;">
        <div id="cargocountmain" style="width: 100%;height: 632px;float: left;margin-top: 80px;overflow:hidden;">
        </div>
    </div>
    <div style="width:632px; float:left;position:relative; background-image:url('/shipplatform2/tpl/default/Index/Public/image/earth_bg_2x.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;">
        <div style="padding-top: 100%"></div>
        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:108px;left: 281px;z-index: 999;"
             icon-data="惠宁码头"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 40px;height: 40px;margin-top: -15px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/clicked.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                惠宁码头
            </div>
        </div>

        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:171px;left: 423px;z-index: 999;"
             icon-data="四公司"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                四公司
            </div>
        </div>

        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:303px;left:480px;z-index: 999;"
             icon-data="天宇码头"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                天宇码头
            </div>
        </div>

        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:452px;left: 423px;z-index: 999;"
             icon-data="西坝码头"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                西坝码头
            </div>
        </div>

        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:515px;left: 278px;z-index: 999;"
             icon-data="扬子石化"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                扬子石化
            </div>
        </div>

        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:452px;left: 133px;z-index: 999;"
             icon-data="扬巴码头"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                扬巴码头
            </div>
        </div>

        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:303px;left: 73px;z-index: 999;"
             icon-data="龙翔码头"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                龙翔码头
            </div>
        </div>

        <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:170px;left: 131px;z-index: 999;"
             icon-data="南化码头"
             icon-type='pier'>
            <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
            <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
                南化码头
            </div>
        </div>

                <div id="showPierText"
                     style="width:125px;height:30px;position: absolute; top:46.4%;left: 40.5%;font-size:30px;font-family:MicrosoftYaHei-Bold;font-weight:bold;color:rgba(255,255,255,1);text-align: center;">
                    惠宁码头
                </div>
        <div style="width:300px;height:200px;position: absolute; top:36%;left: 26.5%;">
            <div id="showPier" style="width:300px;height:300px;margin-top: -90px;">

            </div>
        </div>

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

    var g_cellBar0_y = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAoCAYAAAAhf6DEAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAA6SURBVEhLY2x8/vY/A4mg3zwcTDOBSTLBqGYSwahmEsGoZhLBqGYSwahmEsGoZhLBqGYSwZDUzMAAAJldBMF2UASmAAAAAElFTkSuQmCC';
    var g_cellBarImg0_y = new Image();
    g_cellBarImg0_y.src = g_cellBar0_y;

    var pierOption = <?php echo ($optionData); ?>;
    var pierChart = echarts.init(document.getElementById('showPier'));
    pierChart.setOption(pierOption);


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


    var setting = {
        selector: "#showPierText", // target element selector
        words: ["惠宁码头"], // words/sentences that will be auto typed
        charSpeed: 85, // letter typing animation speed
        delay: 2100, // word/sentence typing animation delay
        loop: false, // if loop is activated, autoTyper will start over
        flipflop: false // if flipflop is activated, letters which are typed animated will be removed ony by one animated
    };

    var piers;
    $(function () {
        piers = $("[icon-type='pier']");
        piers.click(function () {
            let htmltext = $(this).context.innerText;

            $("[icon-type='pier']").css({height: "70px", width: "80px"});

            $("[icon-type='pier']").find('div:eq(0)').css({
                backgroundImage: "url(/shipplatform2/tpl/default/Index/Public/image/unclick.png)",
                width: "12px",
                height: "12px",
                marginTop: "0px"
            });

            $(this).find('div:eq(0)').css({
                backgroundImage: "url(/shipplatform2/tpl/default/Index/Public/image/clicked.png)",
            });

            $(this).find('div:eq(0)').animate({
                width: "40px",
                height: "40px",
                marginTop: "-15px",
            });

            //渐变打字机
            $("#showPierText").animate({opacity: "0"}, function () {
                $("#showPierText").css({opacity: "1"});
                setting.words = [htmltext];
                let typer = new autoTyper(setting);
                typer.start();
                console.log(htmltext)
            });

            options = null;
            clears();
            let pier = htmltext;
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


        });


        piers.mouseenter(function () {
            let htmltext = $(this).context.innerText;
            // console.log(htmltext);
            pierChart.dispatchAction({
                type: 'pieSelect',
                name: htmltext
            });

            pierChart.dispatchAction({
                type: 'showTip',
                seriesIndex: '0',
                name: htmltext,
                position:[20,15]
            });
        });

        piers.mouseleave(function () {
            let htmltext = $(this).context.innerText;
            pierChart.dispatchAction({
                type: 'pieUnSelect',
                name: htmltext
            });
            pierChart.dispatchAction({
                type: 'hideTip'
            });
        });
    });
</script>
</body>
</html>