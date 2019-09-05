<?php
// 设置页面编码
header("Content-type:text/html;charset=utf-8");
//设置时区
date_default_timezone_set('Asia/Shanghai');
// 允许APP端AJAX跨域请求
header("Access-Control-Allow-Origin: *");

echo "每个骰子有6面，共4个骰子，各个骰子之间的概率<br/><br/>";

$result = getResult(4);

foreach ($result as $key => $value) {
    echo "结果" . $key . "的出现次数为" . ($value * 1296) . "，概率为" . $value . "<br/>";
}

$z = 0;
$o = 0;
$t = 0;
$m = 0;
$m2 =0;
$j = 0;
$ou = 0;

for ($i = 4; $i <= 24; $i++) {
    $m = $i % 3;
    $m2 = $i%2;
    if ($m == 0) {
        $z += $result[$i] * 1296;
    } elseif ($m == 1) {
        $o += $result[$i] * 1296;
    } elseif ($m == 2) {
        $t += $result[$i] * 1296;
    }

    if($m2 == 0){
        $j += $result[$i] * 1296;
    }elseif($m2 == 1){
        $ou += $result[$i] * 1296;
    }
}


echo "<br/>根据计算，出现0的次数为" . $z . ",出现1的次数为" . $o . ",出现2的次数为" . $t . (($z === $o and $o === $t) ? '，概率相等。' : '，概率不等。');

echo "<br/>根据计算，出现奇数的次数为" . $j . ",出现偶数的次数为" . $ou;

/**
 * [getResult description]
 * @param  [type]  $n [骰子个数]
 * @return [array]    [数组，点数和=>相应概率]
 */
function getResult($n)
{
    // 定义骰子的点数及其概率
    $sixArr = array(
        1 => 1 / 6,
        2 => 1 / 6,
        3 => 1 / 6,
        4 => 1 / 6,
        5 => 1 / 6,
        6 => 1 / 6,
    );

    if ($n == 1) { // 只有一个骰子的情况
        return $sixArr;
    } else { // 多个骰子情况
        $result = $sixArr;
        // 假定结果是由一个骰子和其他任意个骰子的组合成的结果集
        // 当有N个骰子的时候，需要组合N-1次
        for ($i = 0; $i < $n - 1; $i++) {
            $result = getDiffArrResult($result, $sixArr);
        }
    }
    return $result;
}

/**
 * [getDiffArrResult 将2个点数概率的数组进行组合，获取这2个数组组合而成的 数字和及其概率]
 * @param  [type] $arr1 [第一个数组]
 * @param  [type] $arr2 [第二个数组]
 * @return [array]      [数组，点数和=>相应概率]
 */
function getDiffArrResult($arr1, $arr2)
{
    $result = array();
    foreach ($arr1 as $k1 => $v1) {
        foreach ($arr2 as $k2 => $v2) {
            if (!isset($result[$k1 + $k2])) $result[$k1 + $k2] = 0;
            $result[$k1 + $k2] += $v1 * $v2;
        }
    }
    return $result;
}
/*
echo "4人掷骰子，最小数为4，最大数为24.<br/>";


$s = 4;
$sm = 6;
for ($i1 = 1;$i1<=$s;$i1++){
    $resultlist = array();
    for($i2 = 1;$i2<=$sm;$i2++){
        $resultlist[] = $i2;
    }
}




$z = 0;
$o = 0;
$t = 0;
$m = 0;

for ($i = 4; $i <= 24; $i++) {
    $m = $i % 3;
    if ($m == 0) {
        $z += 1;
    } elseif ($m == 1) {
        $o += 1;
    } elseif ($m == 2) {
        $t += 1;
    }
}



echo "根据计算，出现0的次数为" . $z . ",出现1的次数为" . $o . ",出现2的次数为" . $t.(($z===$o and $o===$t)?'，概率相等。':'，概率不等。');*/