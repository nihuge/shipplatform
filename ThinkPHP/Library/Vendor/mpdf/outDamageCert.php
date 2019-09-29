<?php
require_once 'mpdf.php';

//header("Content-type: text/html; charset=utf-8");

class outDamageCert
{

    private $search_arr = array(
        '{ship_name}',
        '{voyage}',
        '{nationality}',
        '{berth}',
        '{commenced_time}',
        '{list_time}',
        '{content}',
        '{remarks}',
        '{operator_name}',
        '{sign_picture}',
    );
    private $replace_arr = array();

    /*    private $headerhtml = <<<headhtml
    <table width="80%" style="margin:0 auto;border-bottom: 1px solid #4F81BD; vertical-align: middle; font-family:
    serif; font-size: 9pt; color: #000088;">
    <tbody>
    <tr>
    <td width="60%"></td>
    <td width="40%" align="right" style="text-align: right;"><img src="report_logo.png" width="148"></td>
    </tr>
    </tbody>
    </table>
    headhtml;
*/
    private $foothtml = <<<foothtml
    <table width="80%" style=" vertical-align: bottom; font-family:
    serif; font-size: 9pt; color: #000088;">
    <tbody>
    <tr>
    <td style="text-align: center">{PAGENO}&nbsp;/&nbsp;{nb}</td>
    </tr>
    </tbody>
    </table>
foothtml;

    private $html = <<<Reporthtml
<table width="88%" style="border-collapse: collapse; font-size: 15px;margin-left: 5px;text-align: center;">
	<tbody>
	<tr>
		<td align="center">
            <img src="Public/img/cert_logo.png" height="70px">
		</td>
	</tr>
	<tr>
		<td style="font-size: 25px">
			<strong>OUTTURN&nbsp;LIST&nbsp;FOR&nbsp;CONTAINERS</strong>
		</td>
	</tr>
	</tbody>
</table>
<table width="90%" style="border-collapse: collapse; font-size: 15px;margin-left: 35px;text-align: center;">
	<tbody>
	<tr>
	<td width="200px;" style="text-align: left">
		Vessel:&nbsp;<u>&nbsp;&nbsp;{ship_name}&nbsp;&nbsp;</u>
	</td>
	<td>
		Voy.&nbsp;No.&nbsp;<u>&nbsp;&nbsp;{voyage}&nbsp;&nbsp;</u>
	</td>
	<td style="text-align: left">
		Nationality:&nbsp;<u>&nbsp;&nbsp;{nationality}&nbsp;&nbsp;</u>
	</td>
	<td>
		Berth:&nbsp;<u>&nbsp;&nbsp;{berth}&nbsp;&nbsp;</u>
	</td>
	</tr>
	<tr>
	<td colspan="2" style="text-align: left">
		Tally&nbsp;commenced&nbsp;on:&nbsp;<u>&nbsp;&nbsp;{commenced_time}&nbsp;&nbsp;</u>
	</td>

	<td style="text-align: left">
		Date&nbsp;&nbsp;of&nbsp;&nbsp;list:&nbsp;<u>&nbsp;&nbsp;{list_time}&nbsp;&nbsp;</u>
	</td>
	<td>
	</td>
</tr>
</tbody>
</table>
<table width="88%" style="border-collapse: collapse; font-size: 15px;margin-left: 30px;text-align: center;">
	<thead>
		<tr>
			
		</tr>
	</thead>
	<tbody>
		<thead>
			<tr style="text-align:center;font-size:14px;height: 40px;">
				<th style="border: 1px solid #000;width: 25%; border-left: 0px;" colspan="2">Overlanded containers</th>
				<th style="border: 1px solid #000;width: 25%;" colspan="2">Shortlanded containers</th>
				<th style="border: 1px solid #000;width: 50%;border-right: 0px;" colspan="3">Damaged containers</th>
			</tr>
			<tr style="text-align:center;font-size:14px;height: 40px;">
				<th style="border: 1px solid #000;width: 15%; border-left: 0px;">Container No.</th>
				<th style="border: 1px solid #000;width: 10%;" >Seal No.</th>
				<th style="border: 1px solid #000;width: 15%; border-left: 0px;">Container No.</th>
				<th style="border: 1px solid #000;width: 10%;" >Seal No.</th>
				<th style="border: 1px solid #000;width: 15%; border-left: 0px;">Container No.</th>
				<th style="border: 1px solid #000;width: 10%;" >Seal No.</th>
				<th style="border: 1px solid #000;width: 25%; border-right: 0px;">Condition of damage</th>
			</tr>
		</thead>
		<tbody>
{content}
			<tr>
				<td style="border: 1px solid #000;width: 15%; border-left: 0px;">
					Remarks
				</td>
				<td colspan="6" style="border: 1px solid #000;width: 25%; border-right: 0px;">
					{remarks}
				</td>
			</tr>
			<tr style="height: 110px;">
				<td colspan="3">
					Chief Tally: <u>&nbsp;&nbsp;{operator_name}&nbsp;&nbsp;</u>
				</td>
				<td colspan="3" style="text-align: right">
					Master/Chief Officer:
                </td>
                <td align="left">
					{sign_picture}
				</td>
			</tr>
		</tbody>
	</tbody>
</table>
Reporthtml;


    public function setReportValue($report_arr)
    {

        //构成数据
        $this->replace_arr[] = str_replace(array(" ", "\r\n", "\n"), array("&nbsp;", "<br/>", "<br/>"), isset($report_arr['ship_name']) ? $report_arr['ship_name'] : "");
        $this->replace_arr[] = str_replace(array(" ", "\r\n", "\n"), array("&nbsp;", "<br/>", "<br/>"), isset($report_arr['voyage']) ? $report_arr['voyage'] : "");
        $this->replace_arr[] = str_replace(array(" ", "\r\n", "\n"), array("&nbsp;", "<br/>", "<br/>"), isset($report_arr['nationality']) ? $report_arr['nationality'] : "");
        $this->replace_arr[] = str_replace(array(" ", "\r\n", "\n"), array("&nbsp;", "<br/>", "<br/>"), isset($report_arr['berth']) ? $report_arr['berth'] : "");
        $this->replace_arr[] = str_replace(array(" ", "\r\n", "\n"), array("&nbsp;", "<br/>", "<br/>"), isset($report_arr['commenced_time']) ? $report_arr['commenced_time'] : "");
        $this->replace_arr[] = str_replace(array(" ", "\r\n", "\n"), array("&nbsp;", "<br/>", "<br/>"), isset($report_arr['list_time']) ? $report_arr['list_time'] : "");

        $content = '<tr style="text-align:center;font-size:12px;">
				<td style="border: 1px solid #000;width: 15%; border-left: 0px;height: 40px;">{over.ctn}</td>
				<td style="border: 1px solid #000;width: 10%;" >{over.sealno}</td>
				<td style="border: 1px solid #000;width: 15%; border-left: 0px;height: 40px;">{short.ctn}</td>
				<td style="border: 1px solid #000;width: 10%;" >{short.sealno}</td>
				<td style="border: 1px solid #000;width: 15%; border-left: 0px;height: 40px;">{damage.ctn}</td>
				<td style="border: 1px solid #000;width: 10%;" >{damage.sealno}</td>
				<td style="border: 1px solid #000;width: 25%; border-right: 0px;height: 40px;">{damage.condition}</td>
			</tr>';

        $content_html = "";

        //获得残损说明中最大的一位数
        $a = count($report_arr['content']['over']);
        $b = count($report_arr['content']['short']);
        $c = count($report_arr['content']['damage']);
        $e = ($c >= ($a >= $b ? $a : $b) ? $c : ($a >= $b ? $a : $b));

        for ($i = 0; $i < $e; $i++) {
            $content_html .= str_replace(array("{over.ctn}", "{over.sealno}", "{short.ctn}", "{short.sealno}", "{damage.ctn}", "{damage.sealno}", "{damage.condition}"), array($report_arr['content']['over'][$i]['ctn'], $report_arr['content']['over'][$i]['sealno'], $report_arr['content']['short'][$i]['ctn'], $report_arr['content']['short'][$i]['sealno'], $report_arr['content']['damage'][$i]['ctn'], $report_arr['content']['damage'][$i]['sealno'], $report_arr['content']['damage'][$i]['condition']), $content);
        }

        $this->replace_arr[] = $content_html;

        $this->replace_arr[] = $report_arr['content']['remark'];
        $this->replace_arr[] = $report_arr['operator_name'];
        if ($report_arr['sign_picture'] != "") {
            $this->replace_arr[] = '<img src="Public/upload/cert/damageSign/' . $report_arr['sign_picture'] . '" width="250px" height="80px" style="margin-right:2px"/>';
        } else {
            $this->replace_arr[] = "_________________";
        }
    }

    /**
     * 检测文件是否存在，不存在则新建
     * @param $dir
     * @param int $mode
     * @return bool
     */
    public function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
        if (!$this->mkdirs(dirname($dir), $mode)) return FALSE;
        return @mkdir($dir, $mode);
    }

    /**
     * 删除创建时间超过5天的文件
     * @param $dir
     * @return int
     */
    public function read_all_dir($dir)
    {
        $num = 0;
        $handle = opendir($dir);//读资源
        if ($handle) {
            $file = readdir($handle);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $cur_path = $dir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($cur_path)) {//判断是否为目录，递归读取文件
                        $num += $this->read_all_dir($cur_path);
                    } else {
                        if (time() - filemtime($cur_path) > 3600) {//如果此文件创建时间超过了1个小时则删除3600
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


    public function outputReport($type = "")
    {

        $mpdf = new mPDF('zh-cn', 'A4', 0, '宋体', 0, 0);
        //加入数据并渲染
//        if (!empty($this->replace_arr)) {
        $this->html = str_replace($this->search_arr, $this->replace_arr, $this->html);

//            $mpdf->allow_charset_conversion = true;
//            $mpdf->charset_in = 'iso-8859-4';


        //添加页眉和页脚到pdf中
//            $mpdf->SetHTMLHeader($this->headerhtml);
        $mpdf->SetHTMLFooter($this->foothtml);

//            $mpdf->SetDisplayMode('fullpage');
        $mpdf->shrink_tables_to_fit = 1;
//            $this->html = mb_convert_encoding($this->html, 'UTF-8', 'UTF-8');
        $mpdf->CurOrientation = "l";
        $mpdf->WriteHTML($this->html);


        if ($type != "") {
            $document_path = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
            $return_path = "/Public/PDF/DamageCert/";
            $filename = uniqid("DamageCert__") . rand(100, 999) . ".pdf";

            $real_path = $document_path . $return_path;

            //判断目录是否存在。删除5天前创建的数据
            $this->mkdirs($real_path);
            $this->read_all_dir($real_path);


            $mpdf->Output($real_path . $filename, 'F');
            exit(json_encode(array(
                'code' => 1,
                'path' => $return_path . $filename
            ), JSON_UNESCAPED_SLASHES));
        } else {
            $mpdf->Output('DamageCert.pdf', 'I');
            exit;
        }
//            echo $this->html;
//        } else {
//            return array('error' => 1, 'msg' => "值为空");
//        }
    }
}

/*$aa = new outDamageCert();
$value = array(
    'khmc' => '客户名称123123',
    'khbm' => '客户部门123123',
    'bfsj' => '拜访时间12312312',
    'bfdd' => '拜访地点123123123',
    'bfry' => '拜访人员123123123',
    'wfry' => '我方人员123123',
    'bfsy' => '拜访事由123123',
    'jlnr' => '交流内容',
    'jljg' => '交流结果',
    'ft' => array('http://imgbdb2.bendibao.com/img/20194/17/2019417114841_12763.png', 'http://imgbdb2.bendibao.com/img/20194/17/2019417114841_12763.png', 'http://imgbdb2.bendibao.com/img/20194/17/2019417114841_12763.png', 'http://imgbdb2.bendibao.com/img/20194/17/2019417114841_12763.png', 'http://imgbdb2.bendibao.com/img/20194/17/2019417114841_12763.png'),
);

$aa->setReportValue($value);
$aa->outputReport(111);*/
