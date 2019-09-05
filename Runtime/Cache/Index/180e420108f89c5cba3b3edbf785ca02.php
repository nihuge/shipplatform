<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>港口数据统计</title>
    <script src="http://libs.baidu.com/jquery/2.1.4/jquery.min.js"></script>
    <script src="https://www.17sucai.com/preview/1749733/2019-07-03/201512011606/js/autotyper.min.js"></script>

</head>
<body style="background: rgb(19,20,60)">
<!-- 为ECharts准备一个具备大小（宽高）的Dom -->
<div style="width:632px; position:relative; background-image:url('/shipplatform2/tpl/default/Index/Public/image/earth_bg_2x.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;">
    <div style="padding-top: 100%"></div>
    <div style="cursor:pointer;height:70px; width:100px; position: absolute; top:15.5%;left: 43.5%;" icon-data="惠宁码头"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 30px;height: 30px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/clicked.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:100px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            惠宁码头
        </div>
    </div>

    <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:27%;left: 67%;" icon-data="四公司"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            四公司
        </div>
    </div>

    <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:48%;left: 76%;" icon-data="天宇码头"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            天宇码头
        </div>
    </div>

    <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:71.5%;left: 67%;" icon-data="西坝码头"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            西坝码头
        </div>
    </div>

    <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:81.5%;left: 44%;" icon-data="扬子石化"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            扬子石化
        </div>
    </div>

    <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:71.5%;left: 21%;" icon-data="扬巴码头"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            扬巴码头
        </div>
    </div>

    <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:48%;left: 11.5%;" icon-data="龙翔码头"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            龙翔码头
        </div>
    </div>

    <div style="cursor:pointer;height:70px; width:80px; position: absolute; top:27%;left: 20.8%;" icon-data="南化码头"
         icon-type='pier'>
        <div style="margin: 0 auto;float: top;width: 12px;height: 12px;border-radius:50%;background-image: url('/shipplatform2/tpl/default/Index/Public/image/unclick.png');background-size: 100%; background-repeat: no-repeat; background-position: center center;"></div>
        <div style="margin: 0 auto;float: top;width:58px;height:7px;font-size:14px;font-family:MicrosoftYaHei;font-weight:400;color:rgba(255,255,255,1);text-align: center;">
            南化码头
        </div>
    </div>

    <div id="showPier"
         style="width:125px;height:30px;position: absolute; top:46.4%;left: 40.5%;font-size:30px;font-family:MicrosoftYaHei-Bold;font-weight:bold;color:rgba(255,255,255,1);text-align: center;">
        惠宁码头
    </div>
</div>
<script type="text/javascript">
    var setting = {
        selector: "#showPier", // target element selector
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
                height: "12px"
            });

            $(this).css({height: "70px", width: "100px"});


            $(this).find('div:eq(0)').css({
                backgroundImage: "url(/shipplatform2/tpl/default/Index/Public/image/clicked.png)",
            });

            $(this).find('div:eq(0)').animate({
                width: "30px",
                height: "30px",
            });


            $("#showPier").animate({opacity: "0"}, function () {
                $("#showPier").css({opacity: "1"});
                setting.words = [htmltext];
                let typer = new autoTyper(setting);
                typer.start();
                console.log(htmltext)
            })
        });
    });
</script>
</body>
</html>