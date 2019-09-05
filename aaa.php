<?php


$jsondata = <<<json
{"trimvalue1":"-0.4","trimvalue2":"-0.2","trimvalue3":"0","trimvalue4":"0.2","trimvalue5":"0.4","trimvalue6":"0.6","trimvalue7":"0.8","trimvalue8":"1","trimvalue9":"1.2","trimvalue10":"1.4","trimvalue11":"1.6"}
json;

function getjsonarray($data, $chishui)
{
    // 计算纵倾修正
    // json转化数组
    $arrtb = json_decode($data, true);
    $array = array();
    $arrayxiao = array();
    $arrayda = array();
    // 判断数据是否在纵倾修正值数组内
    foreach ($arrtb as $key => $value) {
        if ($chishui == $value) {
            $array[] = array(
                $key => $value
            );
        } elseif ($chishui > $value) {
            //获取所有比纵倾值小
            $arrayxiao[$key] = $value;
        } elseif ($chishui < $value) {
            //获取所有比纵倾值大
            $arrayda[$key] = $value;
        }
    }
    //判断是否有对应的纵倾修正值
    if (count($array) == '1') {
        //①正巧取到纵倾修正值
        //舱容表对应的key与value
        $qiu = $array[0];
    } elseif (count($array) == '0' and count($arrayxiao) >= '1' and count($arrayda) >= '1') {
        // ②取到两条数据，最小的最大数据、最大的最小数据
        // 获取最小列表的最大值(比吃水值小)
        $k = array_search(max($arrayxiao), $arrayxiao);
        $qiu[$k] = $arrayxiao[$k];
        //获取最大列表的最小值(比吃水值大)
        $x = array_search(min($arrayda), $arrayda);
        $qiu[$x] = $arrayda[$x];
    } elseif (count($array) == '0' and count($arrayxiao) == '0' and count($arrayda) >= '1') {
        //③只取到一条最大的最小数据
        //获取最大列表的最小值(比吃水值大)
        $x = array_search(min($arrayda), $arrayda);
        $qiu[$x] = $arrayda[$x];
    } elseif (count($array) == '0' and count($arrayxiao) >= '1' and count($arrayda) == '0') {
        //④只取到一条最小的最大数据
        //获取最小列表的最大值(比吃水值小)
        $k = array_search(max($arrayxiao), $arrayxiao);
        $qiu[$k] = $arrayxiao[$k];
    }
    return $qiu;
}


print_r(getjsonarray($jsondata, 0.8));