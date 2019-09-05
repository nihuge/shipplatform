<?php
// 设置页面编码
header("Content-type:text/html;charset=utf-8");
//设置时区
date_default_timezone_set('Asia/Shanghai');
// 允许APP端AJAX跨域请求
header("Access-Control-Allow-Origin: *");
/**
 * 自动对比两个数组相同键值部分的值有哪些不同，并且返回不同的差异数组
 * @param $array1 新数组
 * @param $array2 原数组
 */
function changeSave($array1,$array2){
    $array1_count = count($array1);
    $array2_count = count($array2);
    $array3 = array();
    if($array1_count <= $array2_count){
        foreach ($array1 as $k=>$value){
            if(isset($array2[$k])){
                $array3[$k] = $array2[$k];
            }
        }
        $diff_arr = array_diff_assoc($array1, $array3);
    }else{
        foreach ($array2 as $k=>$value){
            if(isset($array1[$k])) {
                $array3[$k] = $array1[$k];
            }
        }
        $diff_arr = array_diff_assoc($array3,$array2);
    }
    return $diff_arr;
}

$a = array('a' => 'a', 'b' => 'a', 'c' => 'ab1c','e'=>'aaccs1','f'=>'aac1');
$b = array('a' => 'a', 'b' => 'b', 'c' => 'abc','e'=>'aacz');
$diff_arr = changeSave($a,$b);

echo "两个数据改动的字段分别为：".implode('和',array_keys($diff_arr));
echo "两个数据改动的值分别为：".implode('和',$diff_arr);
