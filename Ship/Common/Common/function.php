<?php
header("Content-type:text/html;charset=utf-8");
/**
 * 公共函数库
 */
/**
 * 传递数据以易于阅读的样式格式化后输出
 * @param $data 需要格式化显示的数据
 * @return $str 返回数据
 */
function p($data)
{
    // 定义样式
    $str = '<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
    // 如果是boolean或者null直接显示文字；否则print
    if (is_bool($data)) {
        $show_data = $data ? 'true' : 'false';
    } elseif (is_null($data)) {
        $show_data = 'null';
    } else {
        $show_data = print_r($data, true);
    }
    $str .= $show_data;
    $str .= '</pre>';
    echo $str;
}

/**
 * 获取重复数据的数组
 * @param $array 需要格式化显示的数据
 * @return $repeat_arr 返回数据
 */
function FetchRepeatMemberInArray($array)
{
    // 获取去掉重复数据的数组 
    $unique_arr = array_unique($array);
    // 获取重复数据的数组 
    $repeat_arr = array_diff_assoc($array, $unique_arr);
    return $repeat_arr;
}


/**
 * 分页
 * @param $count 总记录数
 * @param $per 每页显示的记录数
 * @return $show 分页显示输出
 * */
function fenye($count, $per)
{
    $Page = new \Think\Page($count, $per);// 实例化分页类 传入总记录数和每页显示的记录数(25)
    $Page->rollPage = 10; // 分页栏每页显示的页数
    $Page->setConfig('header', '共%TOTAL_ROW%条');
    $Page->setConfig('first', '首页');
    $Page->setConfig('last', '尾页');
    $Page->setConfig('prev', '上一页');
    $Page->setConfig('next', '下一页');
    $Page->setConfig('link', 'indexpagenumb');//pagenumb 会替换成页码
    $Page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% 第 ' . I('p', 1) . ' 页/共 %TOTAL_PAGE% 页 (<font color="red">' . $per . '</font> 条/页 共 %TOTAL_ROW% 条)');
    $show = $Page->show();// 分页显示输出
    return $show;
}

/**
 * 生成验证码
 * @return img 验证码图片路径
 */
function show_verify()
{
    ob_clean();
    $config = array(
        'codeSet' => '1234567890',
        'expire' => 1800,            // 验证码过期时间（s）
        'useImgBg' => false,           // 使用背景图片
        'fontSize' => 17,              // 验证码字体大小(px)
        'useCurve' => false,            // 是否画混淆曲线
        'useNoise' => false,            // 是否添加杂点
        'imageH' => 40,               // 验证码图片高度
        'imageW' => 130,               // 验证码图片宽度
        'length' => 4,               // 验证码位数
        'fontttf' => 'Alpha-Silouettes-2.ttf',              // 验证码字体，不设置随机获取
        'bg' => array(243, 251, 254),  // 背景颜色
    );
    $verify = new \Think\Verify($config);
    return $verify->entry();
}

/**
 * 检测验证码
 * @param string code 输入的验证码
 * @return bool true / false
 */
function check_verify($code)
{
    $verify = new \Think\Verify();
    return $verify->check($code);
}

/**
 * 判断字符串是否含有特殊字符
 * @param string $string :字符串
 * @return boolan
 */
function judgeOneString($string)
{
    // 特殊字符
    $pattern = "/[\',:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/";
    // 验证是否含有特殊字符
    if (preg_match($pattern, $string)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断二维数组是否含有特殊字符
 * @param array $string :数组
 * @return boolan
 */
function judgeTwoString($array)
{
    foreach ($array as $key => $value) {
        $res = judgeOneString($value);
        if ($res == true) {
            return false;
            exit;
        }
    }
    return true;
}

/**
 * 密码加密
 * @param string $pwd :用户密码
 * @return string
 */
function encrypt($pwd)
{
    $password = md5($pwd . 'user' . substr($pwd, 0, 3));
    return $password;
}

/**
 * Ajax方式返回数据
 * @access protected
 * @param mixed $data 要返回的数据
 * @param String $type AJAX返回数据格式
 * @return void
 */
function ajaxReturn($data, $type = '')
{
    if (empty($type)) $type = C('DEFAULT_AJAX_RETURN');
    switch (strtoupper($type)) {
        case 'JSON' :
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode($data, JSON_UNESCAPED_UNICODE));
        case 'XML'  :
            // 返回xml格式数据
            header('Content-Type:text/xml; charset=utf-8');
            exit(xml_encode($data));
        case 'JSONP':
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
            exit($handler . '(' . json_encode($data) . ');');
        case 'EVAL' :
            // 返回可执行的js脚本
            header('Content-Type:text/html; charset=utf-8');
            exit($data);
    }
}

/**
 * json方式返回数据
 * @access protected
 * @param mixed $data 要返回的数据
 * @return void
 */
function jsonreturn($data)
{
    // 返回JSON数据格式到客户端 包含状态信息
    header('Content-Type:application/json; charset=utf-8');
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/*
 * 删除空格、单引号
 * @param string $str:字符串
 * @return string
 */
function trimall($str)
{
    $qian = array(" ", "　", "\t", "\n", "\r", "'", "   ", "    ");
    $hou = array("", "", "", "", "", "", "", "");
    return str_replace($qian, $hou, $str);
}

/**
 * 循环删除目录和文件
 * @param string $dirName 文件夹目录
 * @return
 */
function delDirAndFile($dirName)
{
    if ($handle = opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item")) {
                    delDirAndFile("$dirName/$item");
                } else {
                    unlink("$dirName/$item");
                }
            }
        }
        closedir($handle);
        rmdir($dirName);
    }
}

/**
 * 冒泡排序
 * @param array $arr :数组
 * @return array
 * */
function getpao($arr)
{
    $len = count($arr);
    //设置一个空数组 用来接收冒出来的泡
    //该层循环控制 需要冒泡的轮数
    for ($i = 1; $i < $len; $i++) { //该层循环用来控制每轮 冒出一个数 需要比较的次数
        for ($k = 0; $k < $len - $i; $k++) {
            if ($arr[$k] > $arr[$k + 1]) {
                $tmp = $arr[$k + 1];
                $arr[$k + 1] = $arr[$k];
                $arr[$k] = $tmp;
            }
        }
    }
    return $arr;
}

/**
 * 体积修正
 * 8 15度的密度、仓壁温度
 */
function corrent($midu, $wendu)
{
    //初始化模型静态变量
    \Common\Model\WorkModel::$function_process = '';
    //设 M=15摄氏度时的实验室密度，T=舱壁温度，Vc=体积修正系数
    if ($midu >= 0.99) {
        $vc = 1.0094684142 - 6.33413410744 * 0.0001 * $wendu + 1.45710416212 * 0.0000001 * ($wendu * $wendu);
        \Common\Model\WorkModel::$function_process .= '\t VC= ROUND(1.0094684142 - 6.33413410744 * 0.0001 * Cabin_termperature + 1.45710416212 * 0.0000001 * (Cabin_termperature * Cabin_termperature),4)=' . round($vc, 4);
    } else {
        $vc = 1.0108020095 - 7.2343515319 * 0.0001 * $wendu + 2.1996598346 * 0.0000001 * ($wendu * $wendu);
        \Common\Model\WorkModel::$function_process .= '\t VC= ROUND(1.0108020095 - 7.2343515319 * 0.0001 *  Cabin_termperature + 2.1996598346 * 0.0000001 * (Cabin_termperature * Cabin_termperature),4)=' . round($vc, 4);
    }
    return round($vc, 4);
    // $a = '1';
    // return $a;
}

/**
 * 膨胀修正
 * @param $a 膨胀系数
 * @param $b wendu
 * @return @param string
 */
function expand($a, $b)
{
    \Common\Model\WorkModel::$function_process = '';
    $a = round((1 + 0.000012 * ($a) * (($b) - 20)), 6);
    \Common\Model\WorkModel::$function_process .= '\t EC= round((1 + 0.000012 * (coefficient) * ((Cabin_temperature) - 20)), 6)=' . $a;
    return eval("return $a;");
}

/**
 * 5002计算公式
 */
function suanfa5002($a, $b, $c, $d, $e)
{

    $suanfa = "round(($a-($b)),3)/($c-($d))*($e-($d))+$b";
    \Common\Model\WorkModel::$function_process .= "interpolation_calculation_result =round(Cbig-Csmall,3)/(Xbig-Xsmall)*(X-Xsmall)+Csmall=" . $suanfa;
    return eval("return $suanfa;");
    // return $a.'~~~~'.$b.'~~~~'.$c.'~~~~'.$d.'~~~~'.$e;  
}

/**
 * 数据去除尾部的0
 * @param int $list
 * @return string
 */
function dateRemoveZero($list)
{
    //数据处理
    foreach ($list as $key => $value) {
        foreach ($value as $k => $v) {
            if ($k !== 'shipname' and $k !== 'time' and $k !== 'voyage') {
                $list[$key][$k] = floatval($v);
            }
        }
    }
    return $list;
}

/**
 * 检测文件是否存在，不存在则新建
 * @param $dir
 * @param int $mode
 * @return bool
 */
function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
    if (!mkdirs(dirname($dir), $mode)) return FALSE;
    return @mkdir($dir, $mode);
}

/**
 * 删除创建时间超过5天的文件
 * @param $dir
 * @return int
 */
function read_all_dir($dir)
{
    $num = 0;
    $handle = opendir($dir);//读资源
    if ($handle) {
        $file = readdir($handle);
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $cur_path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($cur_path)) {//判断是否为目录，递归读取文件
                    $num += read_all_dir($cur_path);
                } else {
                    if (time() - filemtime($cur_path) > 432000) {//如果此文件创建时间超过了5天则删除432000
                        @unlink($cur_path);
                        $num++;
                    }
//                    else{
//
//                    }
//                    $result['file'][] = $cur_path;
                }
            }
        }
        closedir($handle);
    }
    return $num;
}

//mkdirs("aa01");

/**
 * 生成pdf
 * @param string $html 需要生成的内容
 */
function pdf($data = '', $functionname = '', $miniAppPath = "shipPlatform", $PDFfileDir = "", $PDFfilename = "print.pdf")
{
    //判断文件是否存在
    $file = $_SERVER['DOCUMENT_ROOT'] . $miniAppPath . '/Public/pdf/' . $PDFfileDir . $PDFfilename;

    $fileDir = $_SERVER['DOCUMENT_ROOT'] . $miniAppPath . '/Public/pdf/' . $PDFfileDir;

    $delDir = $_SERVER['DOCUMENT_ROOT'] . $miniAppPath . '/Public/pdf/miniprogram';

//echo $_SERVER['DOCUMENT_ROOT'];
    //检测文件夹是否存在,不存在创建
    if (mkdirs($fileDir) == false) {
        return;
    }

    //删除创建时间超过5天的文件
    $delnum = read_all_dir($delDir);
    //写出日志，删除了多少个文件
    writeLog("deleted_filenum:" . $delnum);

    //检测文件是否存在，存在则删除
    if (is_file($file)) {
        unlink($file);
    }
    vendor('Tcpdf.tcpdf');
    $pdf = new \Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // 设置打印模式
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 004');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
    // 是否显示页眉
    $pdf->setPrintHeader(false);
    // 设置页眉显示的内容
    // $pdf->SetHeaderData('', 60, '', '（本单证版权归中理检验公司所有，由南京携众提供技术支持。）', array(0,64,255), array(0,64,128));
    $pdf->SetHeaderData('logo.png', 30, 'Helloweba.com', '致力于WEB前端技术在中国的应用',
        array(0, 64, 255), array(0, 64, 128));
    // 设置页眉字体
    $pdf->setHeaderFont(Array('dejavusans', '', '12'));
    // 页眉距离顶部的距离
    $pdf->SetHeaderMargin('5');
    // 是否显示页脚
    $pdf->setPrintFooter(false);
    // 设置页脚显示的内容
    $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
    // 设置页脚的字体
    $pdf->setFooterFont(Array('dejavusans', '', '10'));
    // 设置页脚距离底部的距离
    $pdf->SetFooterMargin('10');
    // 设置默认等宽字体
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    // 设置行高
    $pdf->setCellHeightRatio(1);
    // 设置左、上、右的间距
    $pdf->SetMargins('10', '2', '10');
    // 设置是否自动分页  距离底部多少距离时分页
    $pdf->SetAutoPageBreak(TRUE, '15');
    // 设置图像比例因子
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
        require_once(dirname(__FILE__) . '/lang/eng.php');
        $pdf->setLanguageArray($l);
    }
    $pdf->setFontSubsetting(true);
    // 设置字体
    $pdf->SetFont('droidsansfallback', '', 11);
    //新增一个页面 
    $pdf->AddPage('L', 'A4');

    // 区分打印的模板
    $html1 = $functionname['pdf']($data);

    //内容写入PDF
    $pdf->writeHTMLCell(0, 0, '', '', $html1, 0, 1, 0, true, '', true);
    //输出
    $pdf->Output($_SERVER['DOCUMENT_ROOT'] . $miniAppPath . '/Public/pdf/' . $PDFfileDir . $PDFfilename, 'F');
    return 'print.pdf';
    // return $html1;
}

/**
 * 服务器网络请求方法，如果需要支持https访问请配置apache的根证书
 * @param string $url 网址
 * @param int $type 请求类型，0为get,1为post
 * @param string $data 请求参数，非必填，请求类型为post时必填
 */
function curldo($url, $type = 0, $data = "")
{
    // 1. 初始化
    $ch = curl_init();
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($type === 0) {
        // 2. 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, 0);

        //从证书中检查SSL加密算法
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    } else {

        //设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, 0);


        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //从证书中检查SSL加密算法
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    //执行命令
    $output = curl_exec($ch);
    if ($output === FALSE) {
        $res = array(
            'code' => 2,
            'error' => curl_error($ch),
        );
    } else {
        $res = array(
            'code' => 1,
            'content' => $output
        );
    }
    // 4. 释放curl句柄
    curl_close($ch);
    return $res;
}

/**
 * 三通pdf模板
 * @param string $html 需要生成的内容
 */
function santongpdf($data = '')
{
    static $t = '';
    foreach ($data["resultmsg"] as $key => $v) {
        $t .= '<tr>
                <td>' . $v[0]['cabinname'] . '</td>
                <td>' . $v[0]['temperature'] . '</td>
                <td>' . $v[0]['ullage'] . '</td>
                <td>' . $v[0]['listcorrection'] . '</td>
                <td>' . $v[0]['correntkong'] . '</td>
                <td>' . $v[0]['cabinweight'] . '</td>
                <td>' . $v[0]['volume'] . '</td>
                <td>' . $v[0]['expand'] . '</td>
                <td>' . $v[0]['standardcapacity'] . '</td>
                <td></td>
                <td>' . $v[1]['temperature'] . '</td>
                <td>' . $v[1]['ullage'] . '</td>
                <td>' . $v[1]['listcorrection'] . '</td>
                <td>' . $v[1]['correntkong'] . '</td>
                <td>' . $v[1]['cabinweight'] . '</td>
                <td>' . $v[1]['volume'] . '</td>
                <td>' . $v[1]['expand'] . '</td>
                <td>' . $v[1]['standardcapacity'] . '</td>
        </tr>';
    }
    // 签名判断
    $pan1 = '';
    if ($data['content']['ffirmtype'] == '1') {
        $pan1 .= $data['content']['username'];
    } else {
        if (!empty($data['content']['eimg'])) {
            $pan1 .= '<img src="' . $data['content']['eimg'] . '" style="height: 100px;width:180px">';
        }
    }

    $pan2 = '';
    if ($data['content']['ffirmtype'] == '1') {
        if (!empty($data['content']['eimg'])) {
            $pan2 .= '<img src="' . $data['content']['eimg'] . '" style="height: 100px;width:180px">';
        }

    } else {

    }
    //模板样式
    $html1 = '
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
</head>
<style>
td{
    height:21px;line-height:21px;
}
</style>
<body>
    <h1 align="center">沥青计重记算单</h1>
    <table border="0" cellspacing="0" cellpadding="0" width="1000px">
        <tr>
            <th width="45px">船名：</th>
            <th style="border-bottom:solid 1px black" width="100px" align="center"> ' . $data['content']['shipname'] . ' </th>
            <th width="60px">航次号：</th>
            <th style="border-bottom:solid 1px black" width="80px" align="center">' . $data['personality']['voyage'] . '</th>
            <th width="60px">起运港：</th>
            <th style="border-bottom:solid 1px black" width="85px" align="center">' . $data['personality']['start'] . '</th>
            <th width="60px">目的港：</th>
            <th style="border-bottom:solid 1px black" width="85px" align="center">' . $data['personality']['objective'] . '</th>
            <th width="70px">作业时间：</th>
            <th style="border-bottom:solid 1px black" width="140px" align="center">' . $data['endtime'] . '</th>
            <th  width="45px">编号：</th>
            <th style="border-bottom:solid 1px black" align="center">' . $data['personality']['number'] . '</th>
        </tr>
        <br/>
        <tr>
            <th width="70px">海船船名：</th>
            <th style="border-bottom:solid 1px black" width="110px" align="center">' . $data['personality']['feedershipname'] . '</th>
            <th  width="85px">海船发货量：</th>
            <th style="border-bottom:solid 1px black" align="center">' . $data['personality']['volume'] . '</th>
            <th width="100px">海船装运码头：</th>
            <th style="border-bottom:solid 1px black" width="110px" align="center">' . $data['personality']['wharf'] . '</th>
            <th  width="85px">海船商检量：</th>
            <th style="border-bottom:solid 1px black" align="center">' . $data['personality']['inspection'] . '</th>
            <th  width="70px">接舶码头：</th>
            <th style="border-bottom:solid 1px black" width="110px" align="center">' . $data['personality']['locationname'] . '</th>
        </tr>
        <br>
        <tr>
            <th width="110px">首次吃水差(米)：</th>
            <th style="border-bottom:solid 1px black" width="50px" align="center">' . $data['content']['qianchi'] . '</th>
            <th  width="110px">末次吃水差(米)：</th>
            <th style="border-bottom:solid 1px black" width="50px"  align="center">' . $data['content']['houchi'] . '</th>
            <th width="60px">发货方：</th>
            <th style="border-bottom:solid 1px black" width="485px" align="center">' . $data['personality']['shipper'] . '</th>
        </tr>
    </table>
    <h3></h3>
    <table border="1" cellspacing="0" cellpadding="1" align="center" width="1060px">
        <tr>
            <th>油舱名称</th>
            <th>温度</th>
            <th>空距<br>(米)</th>
            <th>纵倾修正值(米)</th>
            <th>修正后空距(米)</th>
            <th>容量<br>(米 <sup>3</sup> )</th>
            <th>体积修正系数</th>
            <th>膨胀修正系数</th>
            <th>标准容量</th>
            <th width="2px"></th>
            <th>温度</th>
            <th>空距<br>(米)</th>
            <th>纵倾修正值(米)</th>
            <th>修正后空距(米)</th>
            <th>容量<br>(米 <sup>3</sup> )</th>
            <th>体积修正系数</th>
            <th>膨胀修正系数</th>
            <th>标准容量</th>
        </tr>
        ' .
        $t
        . '
        <tr>
            <td style="height:20px"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>总容量</td>
            <td colspan="6"></td>
            <td colspan="2">' . $data['content']['qianweight'] . '</td>
            <td></td>
            <td colspan="6"></td>
            <td colspan="2">' . $data['content']['houweight'] . '</td>
        </tr>
        <tr>
            <td colspan="7" align="left">&nbsp;实验室密度15℃(克/厘米<sup>3</sup>)</td>
            <td colspan="2" style="vertical-align:middle;">' . $data['content']['qiandensity'] . '</td>
            <td></td>
            <td colspan="6"></td>
            <td colspan="2" style="vertical-align:middle;">' . $data['content']['houdensity'] . '</td>
        </tr>
        <tr>
            <td colspan="7" align="left">&nbsp;底量(吨)</td>
            <td colspan="2">' . $data['content']['qiantotal'] . '</td>
            <td></td>
            <td colspan="6"></td>
            <td colspan="2">' . $data['content']['houtotal'] . '</td>
        </tr>
        <tr>
            <td colspan="16" align="left">&nbsp;货重(吨)</td>
            <td colspan="2">' . $data['content']['weight'] . '</td>
        </tr>
    </table>
    <h3></h3>
    <table border="0" cellspacing="0" cellpadding="0" width="1000px">
        <tr>
            <th colspan=4>&nbsp;</th>
        </tr>
        <tr>
            <th width="50px" align="left">备注：</th>
            <th colspan=3 style="border-bottom:solid 1px black;text-align:left;">&nbsp;&nbsp;' . $data['content']['remark'] . '</th>
        </tr>
        <tr>
            <th colspan="4" style="border-bottom:solid 1px black;height:30px;width:100%">&nbsp;</th>
        </tr>
        <tr>
            <th colspan=4>&nbsp;</th>
        </tr>
        <tr>
            <th colspan=4>&nbsp;</th>
        </tr>
        <tr>
            <th width="50px" align="left" class="ju02">计量员：</th>
            <th width="185px" style="">
                ' . $pan1 . '
            </th>
            <th width="285px" align="right" class="ju02">船舶签章：</th>
            <th width="175px" style="">
                ' . $pan2 . '
            </th>
        </tr>
        
    </table>
</body>
</html>
    ';
    return $html1;
}

/**
 * 生成统计pdf
 * @param string $html 需要生成的内容
 */
function countpdf($list, $sum)
{
    //判断文件是否存在
    $file = $_SERVER['DOCUMENT_ROOT'] . 'shipPlatform/Public/pdf/countpdf.pdf';
    if (is_file($file)) {
        unlink($file);
    }
    vendor('Tcpdf.tcpdf');
    $pdf = new \Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // 设置打印模式
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 004');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
    // 是否显示页眉
    $pdf->setPrintHeader(false);
    // 设置页眉显示的内容
    $pdf->SetHeaderData('', 60, '', '', array(0, 64, 255), array(0, 64, 128));
    // 设置页眉字体
    $pdf->setHeaderFont(Array('dejavusans', '', '12'));
    // 页眉距离顶部的距离
    $pdf->SetHeaderMargin('5');
    // 是否显示页脚
    $pdf->setPrintFooter(false);
    // 设置页脚显示的内容
    $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
    // 设置页脚的字体
    $pdf->setFooterFont(Array('dejavusans', '', '10'));
    // 设置页脚距离底部的距离
    $pdf->SetFooterMargin('10');
    // 设置默认等宽字体
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    // 设置行高
    $pdf->setCellHeightRatio(1);
    // 设置左、上、右的间距
    $pdf->SetMargins('8', '10', '10');
    // 设置是否自动分页  距离底部多少距离时分页
    $pdf->SetAutoPageBreak(TRUE, '15');
    // 设置图像比例因子
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
        require_once(dirname(__FILE__) . '/lang/eng.php');
        $pdf->setLanguageArray($l);
    }
    $pdf->setFontSubsetting(true);
    // 设置字体
    $pdf->SetFont('droidsansfallback', '', 11);
    //新增一个页面
    $pdf->AddPage('P', 'A4');

    static $t = '';
    foreach ($list as $key => $v) {
        $t .= '<tr>
                <td>' . date("Y-m-d", $v['time']) . '</td>
                <td>' . $v['shipname'] . '</td>
                <td>' . $v['voyage'] . '</td>
                <td>' . $v['pretend'] . '</td>
                <td>' . $v['discharge'] . '</td>
                <td>' . $v['deliver'] . '</td>
                <td>' . $v['status'] . '</td>
        </tr>';
    }
    $html1 = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>统计</title>
</head>
<style type="text/css">
    td{
        height:21px;
        line-height: 21px;
        text-align: center
    }
</style>
<body>
    <h1 align="center">&nbsp;&nbsp;年&nbsp;&nbsp;&nbsp;季度装卸盈亏统计表</h1>
    <table cellspacing="0" cellpadding="0" border="1">
        <tr>
            <td>时间</td>
            <td>船名</td>
            <td>航次</td>
            <td>装载</td>
            <td>卸载</td>
            <td>发货量</td>
            <td>盈亏</td>
        </tr>
        ' . $t . '
        <tr>
            <td>合计：</td>
            <td>' . $sum[0]["countsum"] . '</td>
            <td>' . $sum[0]["countsum"] . '</td>
            <td>' . $sum[0]["sumpretend"] . '</td>
            <td>' . $sum[0]["sumdischarge"] . '</td>
            <td>' . $sum[0]["sumdeliver"] . '</td>
            <td>' . $sum[0]["sumstatus"] . '</td>
        </tr>
    </table>
    <h3></h3>
    <table cellspacing="0" cellpadding="0" border="0">
        <tr>
            <th width="45px">合计：</th>
            <th style="border-bottom:solid 1px black" width="55px" align="center"> ' . $sum[0]["countsum"] . ' </th>
            <th width="100px">个船次；共装载</th>
            <th style="border-bottom:solid 1px black" width="105px" align="center">' . $sum[0]["sumpretend"] . '</th>
            <th  width="90px">吨；共卸载</th>
            <th style="border-bottom:solid 1px black" align="center">' . $sum[0]["sumdischarge"] . '</th>
            <th width="50px">吨；</th>
        </tr>   
    </table>
</body>
</html>
    ';

    //内容写入PDF
    $pdf->writeHTMLCell(0, 0, '', '', $html1, 0, 1, 0, true, '', true);
    //输出
    $pdf->Output($_SERVER['DOCUMENT_ROOT'] . 'shipPlatform/Public/pdf/countpdf.pdf', 'F');
    return 'countpdf.pdf';
}

/**
 * 测试模板
 * */
function ceshipdf($data = '')
{
    static $t = '';
    foreach ($data["resultmsg"] as $key => $v) {
        $t .= "<tr style='valign:middle;height:30px;'>
                <td style='height:20px;width:100px;'>{$v[0]['cabinname']}</td>
                <td>{$v[0]['temperature']}</td>
                <td>{$v[0]['ullage']}</td>
                <td>{$v[0]['listcorrection']}</td>
                <td>{$v[0]['correntkong']}</td>
                <td>{$v[0]['cabinweight']}</td>
                <td>{$v[0]['volume']}</td>
                <td>{$v[0]['expand']}</td>
                <td>{$v[0]['standardcapacity']}</td>
                <td></td>
                <td>{$v[1]['temperature']}</td>
                <td>{$v[1]['ullage']}</td>
                <td>{$v[1]['listcorrection']}</td>
                <td>{$v[1]['correntkong']}</td>
                <td>{$v[1]['cabinweight']}</td>
                <td>{$v[1]['volume']}</td>
                <td>{$v[1]['expand']}</td>
                <td>{$v[1]['standardcapacity']}</td>
        </tr>";
    }
    // 签名判断
    $pan1 = '';
    if ($data['content']['ffirmtype'] == '1') {
        $pan1 .= $data['content']['username'];
    } else {
        if (!empty($data['content']['eimg'])) {
            $pan1 .= '<img src="' . $data['content']['eimg'] . '" style="height: 100px;width:180px">';
        }
    }

    $pan2 = '';
    if ($data['content']['ffirmtype'] == '1') {
        if (!empty($data['content']['eimg'])) {
            $pan2 .= '<img src="' . $data['content']['eimg'] . '" style="height: 100px;width:180px">';
        }

    } else {

    }
    //模板样式
    $html1 = '
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h2 align="center">计重记录单</h2>
    <table border="0" cellspacing="0" cellpadding="0" width="1000px">
        <tr>
            <th width="45px">船名：</th>
            <th style="border-bottom:solid 1px black" width="170px" align="center"> ' . $data['content']['shipname'] . ' </th>
            <th width="60px">航次号：</th>
            <th style="border-bottom:solid 1px black" width="145px" align="center">' . $data['personality']['voyage'] . '</th>
            <th  width="60px">运单量：</th>
            <th style="border-bottom:solid 1px black" align="center">' . $data['personality']['transport'] . '</th>
            <th width="45px">货名：</th>
            <th style="border-bottom:solid 1px black" width="180px" align="center">' . $data['personality']['goodsname'] . '</th>
            <th  width="45px">编号：</th>
            <th style="border-bottom:solid 1px black" align="center">' . $data['personality']['number'] . '</th>
        </tr>
        <br/>
        <tr>
            <th width="60px">起运港：</th>
            <th style="border-bottom:solid 1px black" width="165px" align="center">' . $data['personality']['start'] . '</th>
            <th width="60px">目的港：</th>
            <th style="border-bottom:solid 1px black" width="165px" align="center">' . $data['personality']['objective'] . '</th>
            <th width="120px">作业起止时间：</th>
            <th style="border-bottom:solid 1px black" width="170px" align="center">' . $data['endtime'] . '</th>
            <th  width="50px">到</th>
            <th style="border-bottom:solid 1px black"  align="center">' . $data['starttime'] . '</th>
        </tr>
        <br>
        <tr>
            <th width="150px">首次：</th>
            <th width="90px">吃水差(米)：</th>
            <th style="border-bottom:solid 1px black" width="160px" align="center">' . $data['content']['qianchi'] . '</th>
            <th style="width="90px">&nbsp;</th>
            <th style="width="90px">&nbsp;</th>
            <th width="120px" style="margin-left:25px;">末次：</th>
            <th  width="90px">吃水差(米)：</th>
            <th style="border-bottom:solid 1px black" width="160px"  align="center">' . $data['content']['houchi'] . '</th>
        </tr>
    </table>
    <h3></h3>
    <table border="1" cellspacing="0" cellpadding="0" align="center" width="1050px">
        <tr>
            <th style="width:90px;padding:1px;">油舱名称</th>
            <th style="width:35px;">温度</th>
            <th>空距<br>(米)</th>
            <th>纵倾修正值(米)</th>
            <th>修正后空距(米)</th>
            <th>容量<br>(米 <sup>3</sup> )</th>
            <th>体积修正系数</th>
            <th>膨胀修正系数</th>
            <th>标准容量</th>
            <th width="2px"></th>
            <th style="width:35px;">温度</th>
            <th>空距<br>(米)</th>
            <th>纵倾修正值(米)</th>
            <th>修正后空距(米)</th>
            <th>容量<br>(米 <sup>3</sup> )</th>
            <th>体积修正系数</th>
            <th>膨胀修正系数</th>
            <th style="vertical-align:middle;">标准容量</th>
        </tr>
        ' .
        $t
        . '
        <tr>
            <td style="height:20px"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td style="height:20px"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>总容量</td>
            <td colspan="6"></td>
            <td colspan="2">' . $data['content']['qianweight'] . '</td>
            <td></td>
            <td colspan="6"></td>
            <td colspan="2">' . $data['content']['houweight'] . '</td>
        </tr>
        <tr>
            <td colspan="7" align="left" style="height:20px">&nbsp;实验室密度15℃(克/厘米<sup>3</sup>)</td>
            <td colspan="2" style="vertical-align:middle;">' . $data['content']['qiandensity'] . '</td>
            <td></td>
            <td colspan="6"></td>
            <td colspan="2" style="vertical-align:middle;">' . $data['content']['houdensity'] . '</td>
        </tr>
        <tr>
            <td colspan="7" align="left" style="height:20px">&nbsp;重量(吨)</td>
            <td colspan="2">' . $data['content']['qiantotal'] . '</td>
            <td></td>
            <td colspan="6"></td>
            <td colspan="2">' . $data['content']['houtotal'] . '</td>
        </tr>
        <tr>
            <td colspan="16" align="left" style="height:20px">&nbsp;货重(吨)</td>
            <td colspan="2">' . $data['content']['weight'] . '</td>
        </tr>
    </table>
    <h3></h3>
    <table border="0" cellspacing="0" cellpadding="0" width="1000px">
        <tr>
            <th colspan=4>&nbsp;</th>
        </tr>
        <tr>
            <th width="50px" align="left" class="ju02">备注：</th>
            <th colspan=3 style="border-bottom:solid 1px black">' . $data['content']['remark'] . '</th>
        </tr>
        <tr>
            <th colspan="4" style="border-bottom:solid 1px black;height:30px;width:100%">&nbsp;</th>
        </tr>
        <tr>
            <th colspan=4>&nbsp;</th>
        </tr>
        <tr>
            <th colspan=4>&nbsp;</th>
        </tr>
        <tr>
            <th colspan=4>&nbsp;</th>
        </tr>
        <tr>
            <th width="50px" align="left" class="ju02">计量员：</th>
            <th width="185px" style="">
                ' . $pan1 . '
            </th>
            <th width="285px" align="right" class="ju02">船舶签章：</th>
            <th width="175px" style="">
                ' . $pan2 . '
            </th>
        </tr>
    </table>
</body>
</html>
    ';
    return $html1;
}

/**
 * 中理检验模板
 * */
function csicpdf($data = '')
{
    static $t = '';
    // $count = $data["resultmsg"];
    for ($i = 0; $i < 13; $i++) {
        $t .= "<tr style='valign:middle;height:30px;'>
                <td style='height:20px;width:100px;'>{$data["resultmsg"][$i][0]['cabinname']}</td>
                <td>{$data["resultmsg"][$i][0]['temperature']}</td>
                <td>{$data["resultmsg"][$i][0]['ullage']}</td>
                <td>{$data["resultmsg"][$i][0]['listcorrection']}</td>
                <td>{$data["resultmsg"][$i][0]['correntkong']}</td>
                <td>{$data["resultmsg"][$i][0]['cabinweight']}</td>
                <td>{$data["resultmsg"][$i][0]['volume']}</td>
                <td>{$data["resultmsg"][$i][0]['expand']}</td>
                <td>{$data["resultmsg"][$i][0]['standardcapacity']}</td>
                <td></td>
                <td>{$data["resultmsg"][$i][1]['temperature']}</td>
                <td>{$data["resultmsg"][$i][1]['ullage']}</td>
                <td>{$data["resultmsg"][$i][1]['listcorrection']}</td>
                <td>{$data["resultmsg"][$i][1]['correntkong']}</td>
                <td>{$data["resultmsg"][$i][1]['cabinweight']}</td>
                <td>{$data["resultmsg"][$i][1]['volume']}</td>
                <td>{$data["resultmsg"][$i][1]['expand']}</td>
                <td>{$data["resultmsg"][$i][1]['standardcapacity']}</td>
        </tr>";
    }

    // 签名判断 
    $pan1 = '';
    if ($data['content']['ffirmtype'] == '1') {
        $pan1 .= $data['content']['username'];
    } else {
        if (!empty($data['content']['eimg'])) {
            $pan1 .= '<img src="' . $data['content']['eimg'] . '" style="height: 70px;width:180px">';
        }
    }

    $pan2 = '';
    if ($data['content']['ffirmtype'] == '1') {
        if (!empty($data['content']['eimg'])) {
            $pan2 .= '<img src="' . $data['content']['eimg'] . '" style="height: 60px;width:180px">';
        }

    } else {

    }

    //模板样式
    $html1 = '
    <!DOCTYPE html>
 <html lang="en">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <img src="http://121.41.22.2/shipPlatform/Upload/logo/csic_logo.png" style="display:inline-block;">
    <h2 align="center">计重记录单</h2>
    <table border="0" cellspacing="0" cellpadding="0" width="1000px">
        <tr>
            <th width="45px">船名：</th>
            <th style="border-bottom:solid 1px black" width="170px" align="center"> ' . $data['content']['shipname'] . ' </th>
            <th width="60px">航次：</th>
            <th style="border-bottom:solid 1px black" width="145px" align="center">' . $data['personality']['voyage'] . '</th>
            <th  width="60px">运单量：</th>
            <th style="border-bottom:solid 1px black" align="center">' . $data['personality']['transport'] . '</th>
            <th width="45px">货名：</th>
            <th style="border-bottom:solid 1px black" width="180px" align="center">' . $data['personality']['goodsname'] . '</th>
            <th  width="45px">编号：</th>
            <th style="border-bottom:solid 1px black" align="center">' . $data['personality']['number'] . '</th>
        </tr>
        <br/>
        <tr>
            <th width="65px">&nbsp;起运港：</th>
            <th style="border-bottom:solid 1px black" width="150px" align="center">' . $data['personality']['start'] . '</th>
            <th width="60px">目的港：</th>
            <th style="border-bottom:solid 1px black" width="145px" align="center">' . $data['personality']['objective'] . '</th>
            <th width="120px">作业起止时间：</th>
            <th style="border-bottom:solid 1px black" width="170px" align="center">' . $data['endtime'] . '</th>
            <th  width="50px">到</th>
            <th style="border-bottom:solid 1px black"  align="center">' . $data['starttime'] . '</th>
        </tr>
    </table>
    <h3></h3>
    <table border="1" cellspacing="0" cellpadding="0" align="center" width="1050px">
        <tr>
            <th colspan="9" width="524px" style="height:17px"> &nbsp; &nbsp; &nbsp; &nbsp;首次检验&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;吃水差：' . $data['content']['qianchi'] . '</th>
            <th width="2px"></th>
            <th colspan="8" width="434px" style="height:17px"> &nbsp; &nbsp; &nbsp; &nbsp;末次检验&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;吃水差：' . $data['content']['houchi'] . '</th>
        </tr>
    </table>
    <table border="1" cellspacing="0" cellpadding="0" align="center" width="1050px">
        <tr>
            <th style="width:90px;padding:1px;">油舱名称</th>
            <th width="35px">温度</th>
            <th width="57px">空距<br>(米)</th>
            <th width="57px">纵倾修正值(米)</th>
            <th width="57px">修正后空距(米)</th>
            <th width="57px">容量<br>(米 <sup>3</sup> )</th>
            <th width="57px">体积修正系数</th>
            <th width="57px">膨胀修正系数</th>
            <th width="57px">标准容量</th>
            <th width="2px"></th>
            <th width="35px">温度</th>
            <th width="57px">空距<br>(米)</th>
            <th width="57px">纵倾修正值(米)</th>
            <th width="57px">修正后空距(米)</th>
            <th width="57px">容量<br>(米 <sup>3</sup> )</th>
            <th width="57px">体积修正系数</th>
            <th width="57px">膨胀修正系数</th>
            <th width="57px" style="vertical-align:middle;">标准容量</th>
        </tr>
        ' .
        $t
        . '
        <tr>
            <td colspan="7" align="left">&nbsp;总容量</td>
            <td colspan="2">' . $data['content']['qianweight'] . '</td>
            <td></td>
            <td colspan="6" align="left">&nbsp;总容量</td>
            <td colspan="2">' . $data['content']['houweight'] . '</td>
        </tr>
        <tr>
            <td colspan="7" align="left" style="height:20px">&nbsp;实验室密度15℃(克/厘米<sup>3</sup>)</td>
            <td colspan="2" style="vertical-align:middle;">' . $data['content']['qiandensity'] . '</td>
            <td></td>
            <td colspan="6" align="left">&nbsp;实验室密度15℃(克/厘米<sup>3</sup>)</td>
            <td colspan="2" style="vertical-align:middle;">' . $data['content']['houdensity'] . '</td>
        </tr>
        <tr>
            <td colspan="7" align="left" style="height:20px">&nbsp;重量(吨)</td>
            <td colspan="2">' . $data['content']['qiantotal'] . '</td>
            <td></td>
            <td colspan="6" align="left">&nbsp;重量(吨)</td>
            <td colspan="2">' . $data['content']['houtotal'] . '</td>
        </tr>
        <tr>
            <td colspan="16" align="right" style="height:20px">&nbsp;货重(吨)</td>
            <td colspan="2">' . $data['content']['weight'] . '</td>
        </tr>
    </table>
    <h3></h3>
    <table border="0" cellspacing="0" cellpadding="0" width="1000px">
        <tr>
            <th  width="125px">图表编号：</th>
            <th style="border-bottom:solid 1px black; width="305px" ">' . $data['content']['ship_number'] . '</th>
            <th  width="125px">&nbsp;&nbsp;温度计编号：</th>
            <th style="border-bottom:solid 1px black; width="305px" ">' . $data['personality']['thermometer'] . '</th>
            <th  width="125px">&nbsp;&nbsp;量油尺编号：</th>
            <th style="border-bottom:solid 1px black; width="305px" ">' . $data['personality']['dipstick'] . '</th>
        </tr>
        <tr>
            <th colspan=6>&nbsp;</th>
        </tr>
        <tr>
            <th width="50px" align="left" class="ju02">备注：</th>
            <th width="950px" colspan=5 style="border-bottom:solid 1px black;">' . $data['content']['remark'] . '</th>
        </tr>
        <tr>
            <th colspan="6" style="border-bottom:solid 1px black;height:30px;width:100%">&nbsp;</th>
        </tr>
        <tr>
            <th colspan=6>&nbsp;</th>
        </tr>
        <tr>
            <th colspan=6>&nbsp;</th>
        </tr>
        <tr>
            <th width="60px" align="left" class="ju02">计量员：</th>
            <th width="205px" style="" colspan=2>
                ' . $pan1 . '
            </th>
            <th width="255px" align="right" class="ju02">船舶签章：</th>
            <th width="175px" style="" colspan=2>
                ' . $pan2 . '
            </th>
        </tr>
    </table>
    <div style="width:1050px;text-align:right">（本单证版权归中理检验公司所有，由南京携众提供技术支持。）</div>
</body>
</html>
   ';
    return $html1;
}


/**
 * 记录日志文件
 * @param $content :内容
 * @param $filename :文件名
 * @param $dirname :文件夹名，用于区分不同的日志分类
 * */
function writeLog($content = '', $filename = '', $dirname = '')
{
    if (!$content) {
        return false;
    }
    $dir = getcwd() . DIRECTORY_SEPARATOR . 'Public/logs' . DIRECTORY_SEPARATOR . $dirname;
    if (!is_dir($dir)) {
        if (!mkdir($dir)) {
            return false;
        }
    }
    if (!empty($filename)) {
        $filename = iconv("UTF-8", "GB2312//IGNORE", $filename);
        $filename = $dir . DIRECTORY_SEPARATOR . $filename . '.log';
    } else {
        $filename = $dir . DIRECTORY_SEPARATOR . date('Ymd', time()) . '.log';
    }
    $str = 'Time:' . date("Y-m-d H:i:s") . "\r\n" . '内容:' . $content . "\r\n";
    if (!$fp = @fopen($filename, "a")) {
        return false;
    }
    if (!fwrite($fp, $str))
        return false;
    fclose($fp);
    return true;
}

/**
 * 判断字符串是否含有特殊字符
 * @param string $string :字符串
 * @return boolan true：不存在 false：存在
 */
function is_preg_match($string)
{
    // 特殊字符
    $pattern = "/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/";
    // 验证是否含有中文、数字、英文大小写的字符
    if (preg_match($pattern, $string)) {
        return false;
    } else {
        return true;
    }
}

/*
 * 获取文件扩展名
 * $filename:文件名
 * 返回结果：后缀名，如.jpg
 */
function getFileExt($filename)
{
    //strrchr() 函数查找字符串在另一个字符串中最后一次出现的位置，并返回从该位置到字符串结尾的所有字符。
    return strrchr($filename, '.');
}

/**
 * 获取文件修改时间
 * @param string file 文件名
 * @param string DataDir 文件所在文件夹路径
 * @return datetime 时间
 * */
function getfiletime($file, $DataDir)
{
    $a = filemtime($DataDir . $file);
    $time = date("Y-m-d H:i:s", $a);
    return $time;
}

/**
 * 获取文件的大小
 * @param string file 文件名
 * @param string DataDir 文件所在文件夹路径
 * @return string 文件大小+KB
 * */
function getfilesize($file, $DataDir)
{
    $perms = stat($DataDir . $file);
    $size = $perms['size'];
    // 单位自动转换函数
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte

    if ($size < $kb) {
        return $size . " B";
    } else if ($size < $mb) {
        return round($size / $kb, 2) . " KB";
    } else if ($size < $gb) {
        return round($size / $mb, 2) . " MB";
    } else if ($size < $tb) {
        return round($size / $gb, 2) . " GB";
    } else {
        return round($size / $tb, 2) . " TB";
    }
}


/**
 * ajax图片上传
 * @param string base64_image_content 文件名
 */
function upload_ajax($base64_image_content)
{
    $zd = $_POST['zd'];
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
        $img = base64_decode(str_replace($result[1], '', $base64_image_content)); //返回文件流
    }
    $savename = uniqid() . '.jpg';
    $fileDir = "./Upload/firm/" . date("Y-m-d") . "/";     //==>定义上传路径
    // $file="/opt/lampp/htdocs/gonggup/".$fileDir;    ==>如果上传到线上，可能会需要此处来追加定义上传路径
    if (!is_dir($fileDir)) {
        @mkdir($fileDir, 0777, true);                  //==>图片读写权限，一般都是最大：0777
    }

    $savepath = $fileDir . $savename;                  //==>保存路径
    $ifp = fopen($savepath, "wb");
    fwrite($ifp, $img);
    fclose($ifp);
    if ($savepath) {
        // 组装图片路径 
        $savepath = __ROOT__ . "/Upload/firm/" . date("Y-m-d") . "/" . $savename;
        $_SESSION['info'][$zd] = $fileDir . $savename; //==>对图片“路径+文件名”进行处理，可存session进行下一次取值处理，也可以直接存数据库相关的表中
        $res = array("status" => 1, "content" => "上传成功", "url" => $savepath);
    } else {
        $res = array("status" => 0, "content" => "上传失败");
    }
    return $res;
}


/**
 * 上传base64图片
 * @param string $base64 :文件base64编码
 * @param string $path :文件保存路径
 * @param array $exts :允许上传的文件后缀
 * @param string $method :数据传输方式 POST GET
 * @return string|code
 * @return 成功返回文件完整路径
 * @return code:0成功 1不是正确的图片文件 2文件上传失败
 */
function base64_upload($base64, $path, $method = 'post')
{
    //post的数据里面，加号会被替换为空格，需要重新替换回来；如果不是post的数据，则注释掉这一行
    if ($method == 'post') {
        $base64_file = str_replace(' ', '+', $base64);
    }
    if (!file_exists($path)) {
        mkdir($path, 0777);
    }
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_file, $result)) {
        //匹配成功
        $file_name = uniqid() . rand(100, 999) . '.' . $result[2];
        $save_file = $path . $file_name;
        //服务器文件存储路径
        if (file_put_contents($save_file, base64_decode(str_replace($result[1], '', $base64_file)))) {
            $res = array(
                'code' => 0,
                'msg' => '成功',
                'file' => $save_file,
                'name' => $file_name
            );
        } else {
            $res = array(
                'code' => 2,
                'msg' => '文件上传失败'
            );
        }
    } else {
        $res = array(
            'code' => 1,
            'msg' => '不是正确的图片文件'
        );
    }
    return $res;
}

/**
 * 传入时间戳,计算距离现在的时间
 * @param number $time 时间戳
 * @return string     返回多少以前
 */
function word_time($time)
{
    $time = (int)substr($time, 0, 10);
    $int = time() - $time;
    $str = '';
    if ($int <= 2) {
        $str = sprintf('刚刚', $int);
    } elseif ($int < 60) {
        $str = sprintf('%d秒前', $int);
    } elseif ($int < 3600) {
        $str = sprintf('%d分钟前', floor($int / 60));
    } elseif ($int < 86400) {
        $str = sprintf('%d小时前', floor($int / 3600));
    } elseif ($int < 1728000) {
        $str = sprintf('%d天前', floor($int / 86400));
    } else {
        $str = date('Y-m-d H:i:s', $time);
    }
    return $str;
}

/**
 * 传递ueditor生成的内容获取其中图片的路径
 * @param string $str 含有图片链接的字符串
 * @return array       匹配的图片数组
 */
function get_ueditor_image_path($str)
{
    $preg = '/\/Upload\/image\/ueditor\/\d*\/\d*\.[jpg|jpeg|png|bmp|gif]*/i';
    preg_match_all($preg, $str, $data);
    return current($data);
}

/**
 * 将ueditor存入数据库的文章中的图片绝对路径转为相对路径
 * @param string $content 文章内容
 * @return string          转换后的数据
 */
function preg_ueditor_image_path($data)
{
    // 兼容图片路径
    $root_path = rtrim($_SERVER['SCRIPT_NAME'], '/index.php');
    // 正则替换图片
    $data = htmlspecialchars_decode($data);
    $data = preg_replace('/src=\"^\/.*\/Upload\/image\/ueditor$/', 'src="' . $root_path . '/Upload/image/ueditor', $data);
    return $data;
}