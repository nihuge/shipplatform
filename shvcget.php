<?php
// 设置页面编码
header("Content-type:text/html;charset=utf-8");
//设置时区
date_default_timezone_set('Asia/Shanghai');
// 允许APP端AJAX跨域请求
header("Access-Control-Allow-Origin: *");

/**
 * 插值计算函数,核心算法，根据大参数和小参数算出中间的参数
 * @param int|float $Cbig 大数值
 * @param int|float $Csmall 小数值
 * @param int|float $Xbig 大刻度
 * @param int|float $Xsmall 小刻度
 * @param int|float $X 当前刻度
 * @return float|int 中间插值
 */
function getMiddleValue($Cbig, $Csmall, $Xbig, $Xsmall, $X)
{
//    $this->function_remark .= "\r\n进入插值计算函数：round(($Cbig - ($Csmall)), 3) / ($Xbig - ($Xsmall)) * ($X - ($Xsmall)) + $Csmall ，\r\n";
    $suanfa = round(($Cbig - ($Csmall)), 3) / ($Xbig - ($Xsmall)) * ($X - ($Xsmall)) + $Csmall;
//    $this->function_remark .= '\r\n得出结果为：' . $suanfa . '\r\n';
    return $suanfa;
}

/**
 * @param float $D_M 拱陷修正吃水
 * @param float $Dup 拱陷修正吃水上位值
 * @param float $Ddown 拱陷修正吃水下位值
 * @param float $TPCup 拱陷修正上位TPC值
 * @param float $TPCdown 拱陷修正下位TPC值
 * @param float $DSup 拱陷修正上位DS值
 * @param float $DSdown 拱陷修正下位DS值
 * @param float $Xfup 拱陷修正上位XF（LCF）值
 * @param float $Xfdown 拱陷修正下位XF（LCF）值
 * @return array $res 插值计算后的所有数据
 */
function getDS($D_M, $Dup, $Ddown, $TPCup, $TPCdown, $DSup, $DSdown, $Xfup, $Xfdown)
{
    if ($D_M >= $Dup) {
        return array(
            'TPC' => $TPCup,
            'DS' => $DSup,
            'Xf' => $Xfup,
        );
    } elseif ($D_M <= $Ddown) {
        return array(
            'TPC' => $TPCdown,
            'DS' => $DSdown,
            'Xf' => $Xfdown,
        );
    } else {
        $TPC = getMiddleValue($TPCup, $TPCdown, $Dup, $Ddown, $D_M);#todo 写成类时需要加this
        $DS = getMiddleValue($DSup, $DSdown, $Dup, $Ddown, $D_M);#todo 写成类时需要加this
        $Xf = getMiddleValue($Xfup, $Xfdown, $Dup, $Ddown, $D_M);#todo 写成类时需要加this
        return array(
            'TPC' => $TPC,
            'DS' => $DS,
            'Xf' => $Xf,
        );
    }
}


/*$Fp = isset($_POST['forntleft']) ? $_POST['forntleft'] : 5.54; //艏左吃水
$Fs = isset($_POST['forntright']) ? $_POST['forntright'] : 5.58;//艏右吃水
$Ap = isset($_POST['afterleft']) ? $_POST['afterleft'] : 5.04;//艉左吃水
$As = isset($_POST['afterright']) ? $_POST['afterright'] : 5.08;//艉右吃水
$Mp = isset($_POST['midleft']) ? $_POST['midleft'] : 5.26;//舯左吃水
$Ms = isset($_POST['midleft']) ? $_POST['midleft'] : 5.3;//舯右吃水*/

/*$Fp = isset($_POST['forntleft']) ? $_POST['forntleft'] : 6.39; //艏左吃水
$Fs = isset($_POST['forntright']) ? $_POST['forntright'] : 6.42;//艏右吃水
$Ap = isset($_POST['afterleft']) ? $_POST['afterleft'] : 8.5;//艉左吃水
$As = isset($_POST['afterright']) ? $_POST['afterright'] : 8.53;//艉右吃水
$Mp = isset($_POST['midleft']) ? $_POST['midleft'] : 7.45;//舯左吃水
$Ms = isset($_POST['midleft']) ? $_POST['midleft'] : 7.6;//舯右吃水*/

$Fp = isset($_POST['forntleft']) ? $_POST['forntleft'] : 10.39; //艏左吃水
$Fs = isset($_POST['forntright']) ? $_POST['forntright'] : 10.41;//艏右吃水
$Ap = isset($_POST['afterleft']) ? $_POST['afterleft'] : 10.31;//艉左吃水
$As = isset($_POST['afterright']) ? $_POST['afterright'] : 10.40;//艉右吃水
$Mp = isset($_POST['midleft']) ? $_POST['midleft'] : 10.40;//舯左吃水
$Ms = isset($_POST['midleft']) ? $_POST['midleft'] : 10.26;//舯右吃水


/*//查表输入项
$LBP = 102;//垂线间长,单位m
$Df = 0.5;//艏水尺距艏垂线距离，单位m
$Da = 4.5;//艉水尺距艉垂线距离，单位m
$Dm = 1.5;//舯水尺距艉垂线距离，单位m

$Pf = 0;//艏水尺相对艏垂线位置（垂线前为1、垂线后为0）
$Pa = 1;//艉水尺相对艉垂线位置（垂线前为1、垂线后为0）
$Pm = 0;//舯水尺相对舯垂线位置（垂线前为1、垂线后为0）*/

/*//查表输入项
$LBP = 152;//垂线间长,单位m
$Df = 0.3;//艏水尺距艏垂线距离，单位m
$Da = 1;//艉水尺距艉垂线距离，单位m
$Dm = 0;//舯水尺距艉垂线距离，单位m

$Pf = 0;//艏水尺相对艏垂线位置（垂线前为1、垂线后为0）
$Pa = 1;//艉水尺相对艉垂线位置（垂线前为1、垂线后为0）
$Pm = 0;//舯水尺相对舯垂线位置（垂线前为1、垂线后为0）*/

//查表输入项
$LBP = 192;//垂线间长,单位m
$Df = 3.4;//艏水尺距艏垂线距离，单位m
$Da = 6.9;//艉水尺距艉垂线距离，单位m
$Dm = 0.46;//舯水尺距艉垂线距离，单位m

$Pf = 1;//艏水尺相对艏垂线位置（垂线前为0、垂线后为1）
$Pa = 0;//艉水尺相对艉垂线位置（垂线前为0、垂线后为1）
$Pm = 1;//舯水尺相对舯垂线位置（垂线前为0、垂线后为1）

$Fps = ($Fp + $Fs) / 2;//计算艏平均水尺
$Aps = ($Ap + $As) / 2;//计算艉平均水尺
$Mps = ($Mp + $Ms) / 2;//计算舯平均水尺

$T = $Aps - $Fps; //计算吃水差

//判断吃水差为正数还是负数
if ($T > 0) {
    $Tf = 0; //吃水差正负状态
} else {
    $Tf = 1; //吃水差正负状态
}

$Fflag = $Tf ^ $Pf;//计算矫正艏吃水flag
$Aflag = $Tf ^ $Pa;//计算矫正艏吃水flag
$Mflag = $Tf ^ $Pm;//计算矫正艏吃水flag

$LBM = $LBP + pow(-1, $Pf) * $Df - pow(-1, $Pa) * $Da;//计算艏艉水尺间长

$Fc = abs($Df * $T / $LBM);//计算艏吃水校正值
$Ac = abs($Da * $T / $LBM);//计算艉吃水校正值
$Mc = abs($Dm * $T / $LBM);//舯吃水校正值

/*$Fc = $T*$Df/($LBP+$Df-$Da);//计算艏吃水校正值
$Ac = $T*$Da/($LBP+$Df-$Da);//计算艏吃水校正值
$Mc = $T*$Dm/($LBP+$Df-$Da);//计算艏吃水校正值*/

$Fm = $Fps + pow(-1, $Fflag) * $Fc;//计算校正后艏吃水
$Am = $Aps + pow(-1, $Aflag) * $Ac;//计算校正后艉吃水
$Mm = $Mps + pow(-1, $Mflag) * $Mc;//计算校正后舯吃水

$TC = $Am - $Fm;//计算矫正后吃水差

$D_M = ($Fm + $Am + (6 * $Mm)) / 8;//计算拱陷矫正后总平均吃水


/**
 * 排水量计算
 * */
$p = 1.0185;//港水密度
$pt = 1.025;//制表时港水密度

/*//构建表数据
$Dup = 7.6;//拱陷修正吃水的上位值
$Ddown = 7.5;//拱陷修正吃水的下位值
$TPCup = 24.18;//拱陷修正吃水上位值的TPC值
$TPCdown = 24.13;//拱陷修正吃水下位值TPC值
$DSup = 16611;//拱陷修正吃水上位值的排水量
$DSdown = 16369;//拱陷修正吃水下位值的排水量
$Xfup = 0.32;//拱陷修正吃水下位值的漂心距舯长度
$Xfdown = 0.26;//拱陷修正吃水下位值的漂心距舯长度*/

//获取D_M+50和-50的MTC值

#todo 获取MTCup，MTCdown
/*
$MTCdown1 = 187.6;//上MTC的上位值
$MTCdown2 = 188.4;//上MTC的下位值
$MTCdown_D_M1 = 7;//上MTC的上位吃水值
$MTCdown_D_M = $D_M - 0.5;
$MTCdown_D_M2 = 7.1;//上MTC的下位吃水值
$MTCdown = getMiddleValue($MTCdown1, $MTCdown2, $MTCdown_D_M1, $MTCdown_D_M2, $MTCdown_D_M);


$MTCup1 = 200.8;//下MTC的上位值
$MTCup2 = 202.2;//下MTC的下位值
$MTCup_D_M1 = 8;//下MTC的上位吃水值
$MTCup_D_M = $D_M + 0.5;
$MTCup_D_M2 = 8.1;//下MTC的下位吃水值
$MTCup = getMiddleValue($MTCup1, $MTCup2, $MTCup_D_M1, $MTCup_D_M2, $MTCup_D_M);*/


$MTCup = 870;
$MTCdown = 842.8;

/*//开始插值计算
$getDS_arr = getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
$TPC = $getDS_arr['TPC'];
$DS = $getDS_arr['DS'];
$Xf = $getDS_arr['Xf'];*/

$TPC = 59.5;
$DS = 55118.7;
$Xf = 2.85;
$dmdz = $MTCup - $MTCdown;

#todo 计算dmdz=MTCup-MTCdown

$Dc1 = 100 * $TC * $Xf * $TPC / $LBP;
$Dc2 = 50 * $dmdz * pow($TC, 2) / $LBP;
$Dc = $Dc1 + $Dc2;
$Dsc = $DS + $Dc;
$Dpc = $Dsc * ($p - $pt) / $pt;
$Dspc = $Dsc + $Dpc;


/**
 * 压载水计算
 * part1,有横倾修正表计量方法
 * */

/**
 * 压载水计算
 * part2,无横倾修正表计量方法
 * */


echo "Fp:" . $Fp . "<br/>Fs:" . $Fs . "<br/>Ap:" . $Ap . "<br/>As:" . $As . "<br/>Mp:" . $Mp
    . "<br/>Ms:" . $Ms . "<br/>LBP:" . $LBP . "<br/>Df:" . $Df . "<br/>Da:" . $Da
    . "<br/>Dm:" . $Dm . "<br/>Pf:" . $Pf . "<br/>Pa:" . $Pa . "<br/>Pm:" . $Pm
    . "<br/>Tf:" . $Tf . "<br/>Fps:" . $Fps . "<br/>Aps:" . $Aps . "<br/>Mps:" . $Mps . "<br/>T:" . $T
    . "<br/>Fflag:" . $Fflag . "<br/>Aflag:" . $Aflag . "<br/>Mflag:" . $Mflag . "<br/>LBM:" . $LBM
    . "<br/>Fc:" . $Fc . "<br/>Ac:" . $Ac . "<br/>Mc:" . $Mc . "<br/>Fm:" . $Fm
    . "<br/>Am:" . $Am . "<br/>Mm:" . $Mm . "<br/>TC:" . $TC . "<br/>D_M:" . $D_M
    . "<br/>TPC:" . $TPC . "<br/>DS:" . $DS . "<br/>Xf:" . $Xf . "<br/>Dc1=100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1
    . "<br/>Dc2=50 * dmdz * TC² / LBP = 50 * $dmdz * " . pow($TC, 2) . " / $LBP = " . $Dc2 . "<br/>Dc:" . $Dc . "<br/>Dsc:" . $Dsc
    . "<br/>Dpc =Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc . "<br/>Dspc:" . $Dspc;