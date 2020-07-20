<?php

namespace Index\Controller;

use Think\Controller;
use Common\Controller\AppBaseController;

/**
 * 验证
 * */
class ReportController extends Controller
{
    public $ERROR_CODE_COMMON = array();         // 公共返回码
    public $ERROR_CODE_COMMON_ZH = array();      // 公共返回码中文描述
    public $ERROR_CODE_USER = array();           // 用户相关返回码
    public $ERROR_CODE_USER_ZH = array();        // 用户相关返回码中文描述
    public $ERROR_CODE_RESULT = array();         // 作业相关返回码
    public $ERROR_CODE_RESULT_ZH = array();      // 作业相关返回码中文描述

    /**
     * 初始化方法
     */
    public function _initialize()
    {
        // 返回码配置
        $this->ERROR_CODE_COMMON = json_decode(error_code_common, true);
        $this->ERROR_CODE_COMMON_ZH = json_decode(error_code_common_zh, true);
        $this->ERROR_CODE_USER = json_decode(error_code_user, true);
        $this->ERROR_CODE_USER_ZH = json_decode(error_code_user_zh, true);
        $this->ERROR_CODE_RESULT = json_decode(error_code_result, true);
        $this->ERROR_CODE_RESULT_ZH = json_decode(error_code_result_zh, true);

    }

    /**
     * 液货船作业验证
     */
    public function verification($result_id, $uid, $sign)
    {
        if (strval($sign) !== reportCodeEncode(intval($result_id), intval($uid))) {
            $this->display();
        } else {
            $work = new \Common\Model\WorkModel();
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            //获取水尺数据
            $where = array(
                'r.id' => intval($result_id),
                'r.uid' => intval($uid)
            );
            //查询作业列表
            $list = $work
                ->alias('r')
                ->field('r.*,s.shipname,s.is_guanxian,s.suanfa,u.username,r.qianchi,r.houchi,s.goodsname goodname,f.firmtype as ffirmtype,e.img as eimg,s.number as ship_number')
                ->join('left join ship s on r.shipid=s.id')
                ->join('left join user u on r.uid = u.id')
                ->join('left join firm f on u.firmid = f.id')
                ->join('left join electronic_visa e on e.resultid = r.id')
                ->where($where)
                ->find();
            $list['weight'] = sprintf("%1\$.3f", abs($list['weight']));
            $list['qiantotal'] = sprintf("%1\$.3f", abs($list['qiantotal']));
            $list['houtotal'] = sprintf("%1\$.3f", abs($list['houtotal']));
            // 获取当前登陆用户的公司类型
            $map = array(
                'u.id' => intval($uid)
            );
            $a = $user
                ->alias('u')
                ->field('f.firmtype')
                ->join('left join firm f on u.firmid = f.id')
                ->where($map)
                ->find();
            $list['firmtype'] = $a['firmtype'];
            if ($list !== false) {
                $resultrecord = M('resultrecord');
                $where1 = array('re.resultid' => $list['id']);
                $resultlist = new \Common\Model\ResultlistModel();
                $resultmsg = $resultlist
                    ->alias('re')
                    ->field('re.*,c.cabinname,c.pipe_line')
                    ->join('left join cabin c on c.id = re.cabinid')
                    ->where($where1)
                    ->order('re.solt asc,re.cabinid asc')
                    ->select();

                //初始化管线信息
                $gxinfo = array(
                    'qiangx' => 0,
                    'qianxgx' => 0,
                    'hougx' => 0,
                    'houxgx' => 0,
                );
                //以舱区分数据（）
                foreach ($resultmsg as $k => $v) {
                    //获取计算数据
                    $recordmsg = $resultrecord
                        ->where(
                            array(
                                'resultid' => $v['resultid'],
                                'solt' => $v['solt'],
                                'cabinid' => $v['cabinid'])
                        )
                        ->find();

                    /**
                     * 此处处理管线单列
                     */
                    //初始化修正后管线容量
                    $xgx = 0;
                    //如果需要单列管线的同时，舱容表不包含管线且当前检验管线内有货。报告时货物容量减去管线容量
                    if ($list['is_guanxian'] == 2 and $recordmsg['is_pipeline'] == 1) {
                        // 计算修正后管道容量   管道容量*体积*膨胀
                        $xgx = round($v['pipe_line'] * $v['volume'] * $v['expand'], 3);
                        //作业舱容量减去管线容量
                        $v['cabinweight'] -= $v['pipe_line'];
                        //作业前舱容量减去修正后管线容量
                        $v['standardcapacity'] -= $xgx;

                        //作业前后管道容量汇总相加
                        if ($v['solt'] == 1) {
                            //作业前管线容量总和相加
                            $gxinfo['qiangx'] += $v['pipe_line'];
                            //作业前修正后管线容量总和相加,先计算修正后管线容量
                            $gxinfo['qianxgx'] += $xgx;
                        } elseif ($v['solt'] == 2) {
                            //作业前管线容量总和相加
                            $gxinfo['hougx'] += $v['pipe_line'];
                            //作业前修正后管线容量总和相加,先计算修正后管线容量
                            $gxinfo['houxgx'] += $xgx;
                        }
                    }

                    $v['standardcapacity'] = sprintf("%1\$.3f", $v['standardcapacity']);
                    $v['volume'] = sprintf("%1\$.4f", $v['volume']);
                    $v['expand'] = sprintf("%1\$.6f", $v['expand']);
                    $v['listcorrection'] = sprintf("%1\$.3f", $v['listcorrection']);
                    $v['cabinweight'] = sprintf("%1\$.3f", $v['cabinweight']);
                    $v['ullage'] = sprintf("%1\$.3f", $v['ullage']);

                    $result[$v['cabinid']][] = $v;
                }


                // 个性化信息
                $personality = json_decode($list['personality'], true);
                if (!empty($resultmsg)) {
                    //取出舱详情最后一个元素时间
                    $start = end($resultmsg);
                    $starttime = date("Y-m-d H:i", $start['time']);
                    //取出舱详情第一个元素时间
                    $end = reset($resultmsg);
                    $endtime = date("Y-m-d H:i", $end['time']);
                } else {
                    $starttime = '';
                    $endtime = '';
                }

                $gxinfo['qiangx'] = sprintf("%1\$.3f", $gxinfo['qiangx']);
                $gxinfo['qianxgx'] = sprintf("%1\$.3f", $gxinfo['qianxgx']);
                $gxinfo['hougx'] = sprintf("%1\$.3f", $gxinfo['hougx']);
                $gxinfo['houxgx'] = sprintf("%1\$.3f", $gxinfo['houxgx']);

                // 获取公司模板文件名
                $ship = new \Common\Model\ShipModel();
                $msg = $user
                    ->alias('u')
                    ->field('u.firmid,f.firmname,f.pdf')
                    ->join('left join firm f on f.id = u.firmid')
                    ->where($map)
                    ->find();

                // 判断作业是否完成----电子签证
                $coun = M('electronic_visa')
                    ->where(array('resultid' => $list['id']))
                    ->count();

                // 判断作业属于哪个类型的公司
                if ($msg['pdf'] == 'null' or empty($msg['pdf'])) {
                    $pdf = 'ceshipdf';
                } else {
                    $pdf = $msg['pdf'];
                }
                $assign = array(
                    'verify' => "Y",
                    'content' => $list,
                    'result' => $result,
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                    'personality' => $personality,
                    'coun' => $coun,
                    'gx' => $gxinfo
                );
                $this->assign($assign);
                $this->display($pdf);
            } else {
                $this->error('数据库连接错误');
            }
        }
    }


    /**
     * 散货船作业验证
     */
    public function sh_verify($result_id, $uid, $sign)
    {
        if (strval($sign) !== shReportCodeEncode(intval($result_id), intval($uid))) {
            $this->display();
        } else {
            $work = new \Common\Model\ShResultModel();

            //获取水尺数据
            $where = array(
                'r.id' => intval($result_id),
                'r.uid' => intval($uid),
            );

            #todo 每一位数据自动去除没用的0
            //查询作业列表
            $list = $work
                ->alias('r')
                ->field('r.*,s.shipname,0 + CAST(s.lbp AS CHAR) as lbp,0 + CAST(s.df AS CHAR) as df , 0 + CAST(s.da AS CHAR) as da, 0 + CAST(s.dm AS CHAR) as dm, 0 + CAST(s.weight AS CHAR) as ship_weight, u.username,f.firmtype as ffirmtype,r.finish,r.finish_time')
                ->join('left join sh_ship s on s.id=r.shipid')
                ->join('left join user u on r.uid = u.id')
                ->join('left join firm f on u.firmid = f.id')
                ->where($where)
                ->find();
            $list['finish_time'] = date('Y-m-d H:i:s', $list['finish_time']);

            unset($list['qianprocess']);
            unset($list['houprocess']);

            $record = M("sh_resultrecord");

            $where_ds = array(
                'resultid' => intval($result_id)
            );
            $ds = $record->where($where_ds)->select();
            foreach ($ds as $keyds => $valueds) {
                unset($ds[$keyds]['process']);
            }

            $wherelist_qian = array(
                'resultid' => intval($result_id),
                'solt' => 1,
            );

            $wherelist_hou = array(
                'resultid' => intval($result_id),
                'solt' => 2,
            );

            $resultlist = new \Common\Model\ShResultlistModel();
            $total_weight_qian = $resultlist->field('sum(weight) as t_weight')->where($wherelist_qian)->find();
            $total_weight_hou = $resultlist->field('sum(weight) as t_weight')->where($wherelist_hou)->find();

            $list['qian_bw'] = $total_weight_qian['t_weight'];
            $list['hou_bw'] = $total_weight_hou['t_weight'];

            //获取水尺数据
            $where = array(
                'resultid' => intval($result_id),
            );
            $forntrecord = M("sh_forntrecord");

            $msg = $forntrecord
                ->field(true)
                ->where($where)
                ->select();


            $forntData = array();
            foreach ($msg as $k => $v) {
                if ($v['solt'] == '1') {
                    $forntData['q'] = $v;
                } else {
                    $forntData['h'] = $v;
                }
            }

            // 个性化组装
            $personality = json_decode($list['personality'], true);
            $personality['num'] = count($personality);
            unset($list['personality']);

            if ($list !== false) {
                $where1 = array('resultid' => $list['id']);

                $resultmsg = $resultlist
                    ->where($where1)
                    ->order('solt asc')
                    ->select();
                // 以舱区分数据
                $result = '';
                foreach ($resultmsg as $k => $v) {
                    $result[$v['id']][] = $v;
                }

                $a = array();
                foreach ($result as $key => $value) {
                    $a[] = $value;
                }
                //成功	1
                $arr = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $list,
                    'ds' => $ds,
                    'fornt' => $forntData,
                    'personality' => $personality,
                    'resultmsg' => $a,
                );
            } else {
                $this->error("数据库连接错误");
            }

            $qian_total = $arr['content']['ship_weight'] + $arr['content']['qian_fuel_weight'] + $arr['content']['qian_fwater_weight'] + $arr['content']['qian_bw'];
            $hou_total = $arr['content']['ship_weight'] + $arr['content']['hou_fuel_weight'] + $arr['content']['hou_fwater_weight'] + $arr['content']['hou_bw'];

            $arr['record'] = array();
            $arr['record']['q'] = array();
            $arr['record']['h'] = array();

            foreach ($arr['ds'] as $key => $value) {
                if ($value['solt'] == 1) {
                    $arr['record']['q']['dsc'] = $value['dsc'];
                    $arr['record']['q']['dc'] = $value['dc'];
                    $arr['record']['q']['dpc'] = $value['dpc'];
                } else {
                    $arr['record']['h']['dsc'] = $value['dsc'];
                    $arr['record']['h']['dc'] = $value['dc'];
                    $arr['record']['h']['dpc'] = $value['dpc'];
                }
            }
            $arr['content']['time'] = date('Y-m-d H:i:s', $arr['content']['time']);
            $NowTime = date('Y-m-d H:i:s', time());

            $this->assign("arr", $arr);
            $this->assign("qian_total", $qian_total);
            $this->assign("hou_total", $hou_total);
            $this->assign("hou_qian_dspc", (float)$arr['content']['hou_dspc'] - (float)$arr['content']['qian_dspc']);
            $this->assign("hou_qian_total", (float)$hou_total - (float)$qian_total);
            $this->assign("nowTime", $NowTime);

            $this->display("shpdf");
        }
    }

    public function gettableimg()
    {
        $start_time = microtime(1);
        //调用使用的方法
        $img_dir = './Upload/table/test3_horizontal.jpg';
        $copy_dir = './Upload/table_copy/test1112.jpg';
//        $img_base64 = imgToBase64($img_dir);
        vendor("baiduimage.AipOcr");
        /*        vendor("imgoperation.imgcompress");
        //
                $img_op = new \imgcompress($img_dir,1);
        //        $img_op->compressImg($copy_dir);
                $a = imagecolorallocate($img_op->image,0,0,0);

                //获取图片信息
                $img_height = $img_op->imageinfo['height'];
                $img_width = $img_op->imageinfo['width'];

                //脉冲计数法画表格直线
                $img_times = 57;//脉冲次数，数据行数+空行数
                $img_header_per = 0.0032;//脉冲头部大小比例
                $img_header_size = round($img_height*$img_header_per,0);//脉冲头部大小
                $img_size = round(($img_height-$img_header_size)/(float)$img_times,0);//脉冲尺寸
                $img_mod = round(($img_height-$img_header_size)%(float)$img_times,0);//脉冲尺寸
                for ($i=1;$i<=$img_times;$i++){
                    $mod = 0;
                    //取模递增
        //            if($img_times-$i < $img_mod){
        //                $mod = 1;
        //            }
                    $line_height = $img_size*$i+$img_header_size+$mod;

                    $img_op->imagelinethick(0,$line_height,$img_width,$line_height,$a,2);
                }
                $img_op->saveImage($copy_dir);*/
        /*        echo $img_size;
                echo $img_header_size;
                echo $img_mod;
                exit("共耗时：".number_format(microtime(1) - $start_time, 6)."秒<br/>图片参数：".json_encode($img_op->imageinfo));*/

        // 你的 APPID AK SK
        $APP_ID = '18037745';
        $API_KEY = 'nsc7qNv6ZTa6pFChL1dEMqEG';
        $SECRET_KEY = 'Ci9MZ8Q8QXBf4ap916D0M8eRCxGCNLep';
        $img = file_get_contents($img_dir);
        $Ocr = new \AipOcr($APP_ID, $API_KEY, $SECRET_KEY);
        $option = array(
            'is_sync' => "true",
            'request_type' => 'json',
//            'table_border' => 'none',
        );
        $result = $Ocr->tableRecognitionAsync($img, $option);
//        $result_id = $result['result'][0]["request_id"];
        $result['log_id'] = number_format($result['log_id'], 0, '', '');
        echo json_encode($result);
        echo "共耗时：" . number_format(microtime(1) - $start_time, 6);
    }

    public function seriali_data()
    {
        $ullage = 0.24;
        $draft = 0.2;

//        $test_json = <<<test_json
//{"result":{"result_data":"{\"form_num\":1,\"forms\":[{\"footer\":[],\"header\":[{\"rect\":{\"top\":0,\"left\":0,\"width\":1080,\"height\":6},\"column\":[1],\"row\":[1],\"word\":\"\"}],\"body\":[{\"rect\":{\"height\":52,\"left\":107,\"top\":6,\"width\":106},\"column\":[1],\"row\":[1],\"word\":\"\u7a7a\u9ad8\"},{\"rect\":{\"height\":34,\"left\":107,\"top\":58,\"width\":106},\"column\":[1],\"row\":[2],\"word\":\"Ullage\"},{\"rect\":{\"height\":42,\"left\":107,\"top\":92,\"width\":106},\"column\":[1],\"row\":[3],\"word\":\"()\"},{\"rect\":{\"height\":47,\"left\":107,\"top\":134,\"width\":106},\"column\":[1],\"row\":[4],\"word\":\"0.200\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":181,\"width\":106},\"column\":[1],\"row\":[5],\"word\":\"0.210\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":211,\"width\":106},\"column\":[1],\"row\":[6],\"word\":\"0.220\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":243,\"width\":106},\"column\":[1],\"row\":[7],\"word\":\"0.230\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":273,\"width\":106},\"column\":[1],\"row\":[8],\"word\":\"0.240\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":305,\"width\":106},\"column\":[1],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":337,\"width\":106},\"column\":[1],\"row\":[10],\"word\":\"0.250\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":367,\"width\":106},\"column\":[1],\"row\":[11],\"word\":\"0.260\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":399,\"width\":106},\"column\":[1],\"row\":[12],\"word\":\"0.270\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":429,\"width\":106},\"column\":[1],\"row\":[13],\"word\":\"0.280\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":461,\"width\":106},\"column\":[1],\"row\":[14],\"word\":\"0.290\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":493,\"width\":106},\"column\":[1],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":523,\"width\":106},\"column\":[1],\"row\":[16],\"word\":\"0.300\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":555,\"width\":106},\"column\":[1],\"row\":[17],\"word\":\"0.400\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":587,\"width\":106},\"column\":[1],\"row\":[18],\"word\":\"0.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":617,\"width\":106},\"column\":[1],\"row\":[19],\"word\":\"0.600\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":649,\"width\":106},\"column\":[1],\"row\":[20],\"word\":\"0.700\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":678,\"width\":106},\"column\":[1],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":710,\"width\":106},\"column\":[1],\"row\":[22],\"word\":\"0.800\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":742,\"width\":106},\"column\":[1],\"row\":[23],\"word\":\"0.900\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":772,\"width\":106},\"column\":[1],\"row\":[24],\"word\":\"1.00\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":804,\"width\":106},\"column\":[1],\"row\":[25],\"word\":\"1.100\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":834,\"width\":106},\"column\":[1],\"row\":[26],\"word\":\"1.200\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":866,\"width\":106},\"column\":[1],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":896,\"width\":106},\"column\":[1],\"row\":[28],\"word\":\"1.300\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":928,\"width\":106},\"column\":[1],\"row\":[29],\"word\":\"1.400\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":958,\"width\":106},\"column\":[1],\"row\":[30],\"word\":\"1.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":990,\"width\":106},\"column\":[1],\"row\":[31],\"word\":\"2.000\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1022,\"width\":106},\"column\":[1],\"row\":[32],\"word\":\"2.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1052,\"width\":106},\"column\":[1],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1084,\"width\":106},\"column\":[1],\"row\":[34],\"word\":\"3.000\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1114,\"width\":106},\"column\":[1],\"row\":[35],\"word\":\"3.500\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1146,\"width\":106},\"column\":[1],\"row\":[36],\"word\":\"4.000\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1175,\"width\":106},\"column\":[1],\"row\":[37],\"word\":\"4.500\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1205,\"width\":106},\"column\":[1],\"row\":[38],\"word\":\"5.000\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1235,\"width\":106},\"column\":[1],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1267,\"width\":106},\"column\":[1],\"row\":[40],\"word\":\"5.890\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1297,\"width\":106},\"column\":[1],\"row\":[41],\"word\":\"5.900\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1327,\"width\":106},\"column\":[1],\"row\":[42],\"word\":\"5.910\"},{\"rect\":{\"height\":28,\"left\":107,\"top\":1359,\"width\":106},\"column\":[1],\"row\":[43],\"word\":\"5.920\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1387,\"width\":106},\"column\":[1],\"row\":[44],\"word\":\"5.930\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1419,\"width\":106},\"column\":[1],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1449,\"width\":106},\"column\":[1],\"row\":[46],\"word\":\"5.937\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1478,\"width\":106},\"column\":[1],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1510,\"width\":106},\"column\":[1],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1540,\"width\":106},\"column\":[1],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1572,\"width\":106},\"column\":[1],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1602,\"width\":106},\"column\":[1],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1634,\"width\":106},\"column\":[1],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1666,\"width\":106},\"column\":[1],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1698,\"width\":106},\"column\":[1],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":107,\"top\":1728,\"width\":106},\"column\":[1],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1756,\"width\":106},\"column\":[1],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1786,\"width\":106},\"column\":[1],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1815,\"width\":106},\"column\":[1],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":107,\"top\":1847,\"width\":106},\"column\":[1],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":107,\"top\":1884,\"width\":106},\"column\":[1],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":213,\"top\":6,\"width\":77},\"column\":[2],\"row\":[1],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":213,\"top\":58,\"width\":77},\"column\":[2],\"row\":[2],\"word\":\"-0.4m\"},{\"rect\":{\"height\":42,\"left\":213,\"top\":92,\"width\":77},\"column\":[2],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":213,\"top\":134,\"width\":77},\"column\":[2],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":181,\"width\":77},\"column\":[2],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":211,\"width\":77},\"column\":[2],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":243,\"width\":77},\"column\":[2],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":273,\"width\":77},\"column\":[2],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":305,\"width\":77},\"column\":[2],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":337,\"width\":77},\"column\":[2],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":367,\"width\":77},\"column\":[2],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":399,\"width\":77},\"column\":[2],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":429,\"width\":77},\"column\":[2],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":461,\"width\":77},\"column\":[2],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":493,\"width\":77},\"column\":[2],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":523,\"width\":77},\"column\":[2],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":555,\"width\":77},\"column\":[2],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":587,\"width\":77},\"column\":[2],\"row\":[18],\"word\":\"31\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":617,\"width\":77},\"column\":[2],\"row\":[19],\"word\":\"33\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":649,\"width\":77},\"column\":[2],\"row\":[20],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":678,\"width\":77},\"column\":[2],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":710,\"width\":77},\"column\":[2],\"row\":[22],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":742,\"width\":77},\"column\":[2],\"row\":[23],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":772,\"width\":77},\"column\":[2],\"row\":[24],\"word\":\"032\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":804,\"width\":77},\"column\":[2],\"row\":[25],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":834,\"width\":77},\"column\":[2],\"row\":[26],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":866,\"width\":77},\"column\":[2],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":896,\"width\":77},\"column\":[2],\"row\":[28],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":928,\"width\":77},\"column\":[2],\"row\":[29],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":958,\"width\":77},\"column\":[2],\"row\":[30],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":990,\"width\":77},\"column\":[2],\"row\":[31],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1022,\"width\":77},\"column\":[2],\"row\":[32],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1052,\"width\":77},\"column\":[2],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1084,\"width\":77},\"column\":[2],\"row\":[34],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1114,\"width\":77},\"column\":[2],\"row\":[35],\"word\":\"32\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1146,\"width\":77},\"column\":[2],\"row\":[36],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1175,\"width\":77},\"column\":[2],\"row\":[37],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1205,\"width\":77},\"column\":[2],\"row\":[38],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1235,\"width\":77},\"column\":[2],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1267,\"width\":77},\"column\":[2],\"row\":[40],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1297,\"width\":77},\"column\":[2],\"row\":[41],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1327,\"width\":77},\"column\":[2],\"row\":[42],\"word\":\"34\"},{\"rect\":{\"height\":28,\"left\":213,\"top\":1359,\"width\":77},\"column\":[2],\"row\":[43],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1387,\"width\":77},\"column\":[2],\"row\":[44],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1419,\"width\":77},\"column\":[2],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1449,\"width\":77},\"column\":[2],\"row\":[46],\"word\":\"35\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1478,\"width\":77},\"column\":[2],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1510,\"width\":77},\"column\":[2],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1540,\"width\":77},\"column\":[2],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1572,\"width\":77},\"column\":[2],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1602,\"width\":77},\"column\":[2],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1634,\"width\":77},\"column\":[2],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1666,\"width\":77},\"column\":[2],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1698,\"width\":77},\"column\":[2],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":213,\"top\":1728,\"width\":77},\"column\":[2],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1756,\"width\":77},\"column\":[2],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1786,\"width\":77},\"column\":[2],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1815,\"width\":77},\"column\":[2],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":213,\"top\":1847,\"width\":77},\"column\":[2],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":213,\"top\":1884,\"width\":77},\"column\":[2],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":290,\"top\":6,\"width\":79},\"column\":[3],\"row\":[1],\"word\":\"\u7eb5\u503e\u503c\"},{\"rect\":{\"height\":34,\"left\":290,\"top\":58,\"width\":79},\"column\":[3],\"row\":[2],\"word\":\"-0.2m\"},{\"rect\":{\"height\":42,\"left\":290,\"top\":92,\"width\":79},\"column\":[3],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":290,\"top\":134,\"width\":79},\"column\":[3],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":181,\"width\":79},\"column\":[3],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":211,\"width\":79},\"column\":[3],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":243,\"width\":79},\"column\":[3],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":273,\"width\":79},\"column\":[3],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":305,\"width\":79},\"column\":[3],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":337,\"width\":79},\"column\":[3],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":367,\"width\":79},\"column\":[3],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":399,\"width\":79},\"column\":[3],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":429,\"width\":79},\"column\":[3],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":461,\"width\":79},\"column\":[3],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":493,\"width\":79},\"column\":[3],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":523,\"width\":79},\"column\":[3],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":555,\"width\":79},\"column\":[3],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":587,\"width\":79},\"column\":[3],\"row\":[18],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":617,\"width\":79},\"column\":[3],\"row\":[19],\"word\":\"17\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":649,\"width\":79},\"column\":[3],\"row\":[20],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":678,\"width\":79},\"column\":[3],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":710,\"width\":79},\"column\":[3],\"row\":[22],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":742,\"width\":79},\"column\":[3],\"row\":[23],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":772,\"width\":79},\"column\":[3],\"row\":[24],\"word\":\"616\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":804,\"width\":79},\"column\":[3],\"row\":[25],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":834,\"width\":79},\"column\":[3],\"row\":[26],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":866,\"width\":79},\"column\":[3],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":896,\"width\":79},\"column\":[3],\"row\":[28],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":928,\"width\":79},\"column\":[3],\"row\":[29],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":958,\"width\":79},\"column\":[3],\"row\":[30],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":990,\"width\":79},\"column\":[3],\"row\":[31],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1022,\"width\":79},\"column\":[3],\"row\":[32],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1052,\"width\":79},\"column\":[3],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1084,\"width\":79},\"column\":[3],\"row\":[34],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1114,\"width\":79},\"column\":[3],\"row\":[35],\"word\":\"16\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1146,\"width\":79},\"column\":[3],\"row\":[36],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1175,\"width\":79},\"column\":[3],\"row\":[37],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1205,\"width\":79},\"column\":[3],\"row\":[38],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1235,\"width\":79},\"column\":[3],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1267,\"width\":79},\"column\":[3],\"row\":[40],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1297,\"width\":79},\"column\":[3],\"row\":[41],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1327,\"width\":79},\"column\":[3],\"row\":[42],\"word\":\"17\"},{\"rect\":{\"height\":28,\"left\":290,\"top\":1359,\"width\":79},\"column\":[3],\"row\":[43],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1387,\"width\":79},\"column\":[3],\"row\":[44],\"word\":\"18\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1419,\"width\":79},\"column\":[3],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1449,\"width\":79},\"column\":[3],\"row\":[46],\"word\":\"18\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1478,\"width\":79},\"column\":[3],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1510,\"width\":79},\"column\":[3],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1540,\"width\":79},\"column\":[3],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1572,\"width\":79},\"column\":[3],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1602,\"width\":79},\"column\":[3],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1634,\"width\":79},\"column\":[3],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1666,\"width\":79},\"column\":[3],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1698,\"width\":79},\"column\":[3],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":290,\"top\":1728,\"width\":79},\"column\":[3],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1756,\"width\":79},\"column\":[3],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1786,\"width\":79},\"column\":[3],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1815,\"width\":79},\"column\":[3],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":290,\"top\":1847,\"width\":79},\"column\":[3],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":290,\"top\":1884,\"width\":79},\"column\":[3],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":369,\"top\":6,\"width\":79},\"column\":[4],\"row\":[1],\"word\":\"(\u8249\u5403\"},{\"rect\":{\"height\":34,\"left\":369,\"top\":58,\"width\":79},\"column\":[4],\"row\":[2],\"word\":\"0.0m\"},{\"rect\":{\"height\":42,\"left\":369,\"top\":92,\"width\":79},\"column\":[4],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":369,\"top\":134,\"width\":79},\"column\":[4],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":181,\"width\":79},\"column\":[4],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":211,\"width\":79},\"column\":[4],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":243,\"width\":79},\"column\":[4],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":273,\"width\":79},\"column\":[4],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":305,\"width\":79},\"column\":[4],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":337,\"width\":79},\"column\":[4],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":367,\"width\":79},\"column\":[4],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":399,\"width\":79},\"column\":[4],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":429,\"width\":79},\"column\":[4],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":461,\"width\":79},\"column\":[4],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":493,\"width\":79},\"column\":[4],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":523,\"width\":79},\"column\":[4],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":555,\"width\":79},\"column\":[4],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":587,\"width\":79},\"column\":[4],\"row\":[18],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":617,\"width\":79},\"column\":[4],\"row\":[19],\"word\":\"0\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":649,\"width\":79},\"column\":[4],\"row\":[20],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":678,\"width\":79},\"column\":[4],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":710,\"width\":79},\"column\":[4],\"row\":[22],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":742,\"width\":79},\"column\":[4],\"row\":[23],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":772,\"width\":79},\"column\":[4],\"row\":[24],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":804,\"width\":79},\"column\":[4],\"row\":[25],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":834,\"width\":79},\"column\":[4],\"row\":[26],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":866,\"width\":79},\"column\":[4],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":896,\"width\":79},\"column\":[4],\"row\":[28],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":928,\"width\":79},\"column\":[4],\"row\":[29],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":958,\"width\":79},\"column\":[4],\"row\":[30],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":990,\"width\":79},\"column\":[4],\"row\":[31],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1022,\"width\":79},\"column\":[4],\"row\":[32],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1052,\"width\":79},\"column\":[4],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1084,\"width\":79},\"column\":[4],\"row\":[34],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1114,\"width\":79},\"column\":[4],\"row\":[35],\"word\":\"0\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1146,\"width\":79},\"column\":[4],\"row\":[36],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1175,\"width\":79},\"column\":[4],\"row\":[37],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1205,\"width\":79},\"column\":[4],\"row\":[38],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1235,\"width\":79},\"column\":[4],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1267,\"width\":79},\"column\":[4],\"row\":[40],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1297,\"width\":79},\"column\":[4],\"row\":[41],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1327,\"width\":79},\"column\":[4],\"row\":[42],\"word\":\"0\"},{\"rect\":{\"height\":28,\"left\":369,\"top\":1359,\"width\":79},\"column\":[4],\"row\":[43],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1387,\"width\":79},\"column\":[4],\"row\":[44],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1419,\"width\":79},\"column\":[4],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1449,\"width\":79},\"column\":[4],\"row\":[46],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1478,\"width\":79},\"column\":[4],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1510,\"width\":79},\"column\":[4],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1540,\"width\":79},\"column\":[4],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1572,\"width\":79},\"column\":[4],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1602,\"width\":79},\"column\":[4],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1634,\"width\":79},\"column\":[4],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1666,\"width\":79},\"column\":[4],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1698,\"width\":79},\"column\":[4],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":369,\"top\":1728,\"width\":79},\"column\":[4],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1756,\"width\":79},\"column\":[4],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1786,\"width\":79},\"column\":[4],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1815,\"width\":79},\"column\":[4],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":369,\"top\":1847,\"width\":79},\"column\":[4],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":369,\"top\":1884,\"width\":79},\"column\":[4],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":448,\"top\":6,\"width\":77},\"column\":[5],\"row\":[1],\"word\":\"\u6c34\u4e00\u824f\"},{\"rect\":{\"height\":34,\"left\":448,\"top\":58,\"width\":77},\"column\":[5],\"row\":[2],\"word\":\"0.2m\"},{\"rect\":{\"height\":42,\"left\":448,\"top\":92,\"width\":77},\"column\":[5],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":448,\"top\":134,\"width\":77},\"column\":[5],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":181,\"width\":77},\"column\":[5],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":211,\"width\":77},\"column\":[5],\"row\":[6],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":243,\"width\":77},\"column\":[5],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":273,\"width\":77},\"column\":[5],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":305,\"width\":77},\"column\":[5],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":337,\"width\":77},\"column\":[5],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":367,\"width\":77},\"column\":[5],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":399,\"width\":77},\"column\":[5],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":429,\"width\":77},\"column\":[5],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":461,\"width\":77},\"column\":[5],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":493,\"width\":77},\"column\":[5],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":523,\"width\":77},\"column\":[5],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":555,\"width\":77},\"column\":[5],\"row\":[17],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":587,\"width\":77},\"column\":[5],\"row\":[18],\"word\":\"-19\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":617,\"width\":77},\"column\":[5],\"row\":[19],\"word\":\"-17\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":649,\"width\":77},\"column\":[5],\"row\":[20],\"word\":\"-18\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":678,\"width\":77},\"column\":[5],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":710,\"width\":77},\"column\":[5],\"row\":[22],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":742,\"width\":77},\"column\":[5],\"row\":[23],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":772,\"width\":77},\"column\":[5],\"row\":[24],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":804,\"width\":77},\"column\":[5],\"row\":[25],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":834,\"width\":77},\"column\":[5],\"row\":[26],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":866,\"width\":77},\"column\":[5],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":896,\"width\":77},\"column\":[5],\"row\":[28],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":928,\"width\":77},\"column\":[5],\"row\":[29],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":958,\"width\":77},\"column\":[5],\"row\":[30],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":990,\"width\":77},\"column\":[5],\"row\":[31],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1022,\"width\":77},\"column\":[5],\"row\":[32],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1052,\"width\":77},\"column\":[5],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1084,\"width\":77},\"column\":[5],\"row\":[34],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1114,\"width\":77},\"column\":[5],\"row\":[35],\"word\":\"-17\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1146,\"width\":77},\"column\":[5],\"row\":[36],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1175,\"width\":77},\"column\":[5],\"row\":[37],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1205,\"width\":77},\"column\":[5],\"row\":[38],\"word\":\"-16\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1235,\"width\":77},\"column\":[5],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1267,\"width\":77},\"column\":[5],\"row\":[40],\"word\":\"-18\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1297,\"width\":77},\"column\":[5],\"row\":[41],\"word\":\"-18\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1327,\"width\":77},\"column\":[5],\"row\":[42],\"word\":\"17\"},{\"rect\":{\"height\":28,\"left\":448,\"top\":1359,\"width\":77},\"column\":[5],\"row\":[43],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1387,\"width\":77},\"column\":[5],\"row\":[44],\"word\":\"14\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1419,\"width\":77},\"column\":[5],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1449,\"width\":77},\"column\":[5],\"row\":[46],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1478,\"width\":77},\"column\":[5],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1510,\"width\":77},\"column\":[5],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1540,\"width\":77},\"column\":[5],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1572,\"width\":77},\"column\":[5],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1602,\"width\":77},\"column\":[5],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1634,\"width\":77},\"column\":[5],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1666,\"width\":77},\"column\":[5],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1698,\"width\":77},\"column\":[5],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":448,\"top\":1728,\"width\":77},\"column\":[5],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1756,\"width\":77},\"column\":[5],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1786,\"width\":77},\"column\":[5],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1815,\"width\":77},\"column\":[5],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":448,\"top\":1847,\"width\":77},\"column\":[5],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":448,\"top\":1884,\"width\":77},\"column\":[5],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":525,\"top\":6,\"width\":77},\"column\":[6],\"row\":[1],\"word\":\"\u6c34)\"},{\"rect\":{\"height\":34,\"left\":525,\"top\":58,\"width\":77},\"column\":[6],\"row\":[2],\"word\":\"0.4m\"},{\"rect\":{\"height\":42,\"left\":525,\"top\":92,\"width\":77},\"column\":[6],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":525,\"top\":134,\"width\":77},\"column\":[6],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":181,\"width\":77},\"column\":[6],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":211,\"width\":77},\"column\":[6],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":243,\"width\":77},\"column\":[6],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":273,\"width\":77},\"column\":[6],\"row\":[8],\"word\":\"00\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":305,\"width\":77},\"column\":[6],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":337,\"width\":77},\"column\":[6],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":367,\"width\":77},\"column\":[6],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":399,\"width\":77},\"column\":[6],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":429,\"width\":77},\"column\":[6],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":461,\"width\":77},\"column\":[6],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":493,\"width\":77},\"column\":[6],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":523,\"width\":77},\"column\":[6],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":555,\"width\":77},\"column\":[6],\"row\":[17],\"word\":\"-38\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":587,\"width\":77},\"column\":[6],\"row\":[18],\"word\":\"-37\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":617,\"width\":77},\"column\":[6],\"row\":[19],\"word\":\"36\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":649,\"width\":77},\"column\":[6],\"row\":[20],\"word\":\"-34\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":678,\"width\":77},\"column\":[6],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":710,\"width\":77},\"column\":[6],\"row\":[22],\"word\":\"-34\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":742,\"width\":77},\"column\":[6],\"row\":[23],\"word\":\"34\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":772,\"width\":77},\"column\":[6],\"row\":[24],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":804,\"width\":77},\"column\":[6],\"row\":[25],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":834,\"width\":77},\"column\":[6],\"row\":[26],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":866,\"width\":77},\"column\":[6],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":896,\"width\":77},\"column\":[6],\"row\":[28],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":928,\"width\":77},\"column\":[6],\"row\":[29],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":958,\"width\":77},\"column\":[6],\"row\":[30],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":990,\"width\":77},\"column\":[6],\"row\":[31],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1022,\"width\":77},\"column\":[6],\"row\":[32],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1052,\"width\":77},\"column\":[6],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1084,\"width\":77},\"column\":[6],\"row\":[34],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1114,\"width\":77},\"column\":[6],\"row\":[35],\"word\":\"-33\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1146,\"width\":77},\"column\":[6],\"row\":[36],\"word\":\"33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1175,\"width\":77},\"column\":[6],\"row\":[37],\"word\":\"33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1205,\"width\":77},\"column\":[6],\"row\":[38],\"word\":\"-32\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1235,\"width\":77},\"column\":[6],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1267,\"width\":77},\"column\":[6],\"row\":[40],\"word\":\"-32\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1297,\"width\":77},\"column\":[6],\"row\":[41],\"word\":\"30\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1327,\"width\":77},\"column\":[6],\"row\":[42],\"word\":\"-28\"},{\"rect\":{\"height\":28,\"left\":525,\"top\":1359,\"width\":77},\"column\":[6],\"row\":[43],\"word\":\"24\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1387,\"width\":77},\"column\":[6],\"row\":[44],\"word\":\"1\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1419,\"width\":77},\"column\":[6],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1449,\"width\":77},\"column\":[6],\"row\":[46],\"word\":\"11\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1478,\"width\":77},\"column\":[6],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1510,\"width\":77},\"column\":[6],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1540,\"width\":77},\"column\":[6],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1572,\"width\":77},\"column\":[6],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1602,\"width\":77},\"column\":[6],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1634,\"width\":77},\"column\":[6],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1666,\"width\":77},\"column\":[6],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1698,\"width\":77},\"column\":[6],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":525,\"top\":1728,\"width\":77},\"column\":[6],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1756,\"width\":77},\"column\":[6],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1786,\"width\":77},\"column\":[6],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1815,\"width\":77},\"column\":[6],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":525,\"top\":1847,\"width\":77},\"column\":[6],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":525,\"top\":1884,\"width\":77},\"column\":[6],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":602,\"top\":6,\"width\":79},\"column\":[7],\"row\":[1],\"word\":\"im[draf\"},{\"rect\":{\"height\":34,\"left\":602,\"top\":58,\"width\":79},\"column\":[7],\"row\":[2],\"word\":\"0.6m\"},{\"rect\":{\"height\":42,\"left\":602,\"top\":92,\"width\":79},\"column\":[7],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":602,\"top\":134,\"width\":79},\"column\":[7],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":181,\"width\":79},\"column\":[7],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":211,\"width\":79},\"column\":[7],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":243,\"width\":79},\"column\":[7],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":273,\"width\":79},\"column\":[7],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":305,\"width\":79},\"column\":[7],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":337,\"width\":79},\"column\":[7],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":367,\"width\":79},\"column\":[7],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":399,\"width\":79},\"column\":[7],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":429,\"width\":79},\"column\":[7],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":461,\"width\":79},\"column\":[7],\"row\":[14],\"word\":\"-28\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":493,\"width\":79},\"column\":[7],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":523,\"width\":79},\"column\":[7],\"row\":[16],\"word\":\"-9\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":555,\"width\":79},\"column\":[7],\"row\":[17],\"word\":\"-58\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":587,\"width\":79},\"column\":[7],\"row\":[18],\"word\":\"-56\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":617,\"width\":79},\"column\":[7],\"row\":[19],\"word\":\"-52\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":649,\"width\":79},\"column\":[7],\"row\":[20],\"word\":\"-53\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":678,\"width\":79},\"column\":[7],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":710,\"width\":79},\"column\":[7],\"row\":[22],\"word\":\"-51\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":742,\"width\":79},\"column\":[7],\"row\":[23],\"word\":\"-52\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":772,\"width\":79},\"column\":[7],\"row\":[24],\"word\":\"-49\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":804,\"width\":79},\"column\":[7],\"row\":[25],\"word\":\"-49\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":834,\"width\":79},\"column\":[7],\"row\":[26],\"word\":\"-49\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":866,\"width\":79},\"column\":[7],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":896,\"width\":79},\"column\":[7],\"row\":[28],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":928,\"width\":79},\"column\":[7],\"row\":[29],\"word\":\"49\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":958,\"width\":79},\"column\":[7],\"row\":[30],\"word\":\"-49\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":990,\"width\":79},\"column\":[7],\"row\":[31],\"word\":\"49\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1022,\"width\":79},\"column\":[7],\"row\":[32],\"word\":\"48\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1052,\"width\":79},\"column\":[7],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1084,\"width\":79},\"column\":[7],\"row\":[34],\"word\":\"-48\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1114,\"width\":79},\"column\":[7],\"row\":[35],\"word\":\"48\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":1146,\"width\":79},\"column\":[7],\"row\":[36],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1175,\"width\":79},\"column\":[7],\"row\":[37],\"word\":\"-47\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1205,\"width\":79},\"column\":[7],\"row\":[38],\"word\":\"-7\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1235,\"width\":79},\"column\":[7],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1267,\"width\":79},\"column\":[7],\"row\":[40],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1297,\"width\":79},\"column\":[7],\"row\":[41],\"word\":\"7\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1327,\"width\":79},\"column\":[7],\"row\":[42],\"word\":\"-2\"},{\"rect\":{\"height\":28,\"left\":602,\"top\":1359,\"width\":79},\"column\":[7],\"row\":[43],\"word\":\"27\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1387,\"width\":79},\"column\":[7],\"row\":[44],\"word\":\"-19\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1419,\"width\":79},\"column\":[7],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":1449,\"width\":79},\"column\":[7],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1478,\"width\":79},\"column\":[7],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1510,\"width\":79},\"column\":[7],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1540,\"width\":79},\"column\":[7],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1572,\"width\":79},\"column\":[7],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1602,\"width\":79},\"column\":[7],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1634,\"width\":79},\"column\":[7],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1666,\"width\":79},\"column\":[7],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1698,\"width\":79},\"column\":[7],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":602,\"top\":1728,\"width\":79},\"column\":[7],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1756,\"width\":79},\"column\":[7],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":1786,\"width\":79},\"column\":[7],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1815,\"width\":79},\"column\":[7],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":602,\"top\":1847,\"width\":79},\"column\":[7],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":602,\"top\":1884,\"width\":79},\"column\":[7],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":681,\"top\":6,\"width\":76},\"column\":[8],\"row\":[1],\"word\":\"taft\"},{\"rect\":{\"height\":34,\"left\":681,\"top\":58,\"width\":76},\"column\":[8],\"row\":[2],\"word\":\"0.8m\"},{\"rect\":{\"height\":42,\"left\":681,\"top\":92,\"width\":76},\"column\":[8],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":681,\"top\":134,\"width\":76},\"column\":[8],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":181,\"width\":76},\"column\":[8],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":211,\"width\":76},\"column\":[8],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":243,\"width\":76},\"column\":[8],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":273,\"width\":76},\"column\":[8],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":305,\"width\":76},\"column\":[8],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":337,\"width\":76},\"column\":[8],\"row\":[10],\"word\":\"-40\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":367,\"width\":76},\"column\":[8],\"row\":[11],\"word\":\"-133\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":399,\"width\":76},\"column\":[8],\"row\":[12],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":429,\"width\":76},\"column\":[8],\"row\":[13],\"word\":\"-120\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":461,\"width\":76},\"column\":[8],\"row\":[14],\"word\":\"-116\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":493,\"width\":76},\"column\":[8],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":523,\"width\":76},\"column\":[8],\"row\":[16],\"word\":\"-112\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":555,\"width\":76},\"column\":[8],\"row\":[17],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":587,\"width\":76},\"column\":[8],\"row\":[18],\"word\":\"-74\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":617,\"width\":76},\"column\":[8],\"row\":[19],\"word\":\"-71\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":649,\"width\":76},\"column\":[8],\"row\":[20],\"word\":\"69\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":678,\"width\":76},\"column\":[8],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":710,\"width\":76},\"column\":[8],\"row\":[22],\"word\":\"-68\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":742,\"width\":76},\"column\":[8],\"row\":[23],\"word\":\"-68\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":772,\"width\":76},\"column\":[8],\"row\":[24],\"word\":\"-65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":804,\"width\":76},\"column\":[8],\"row\":[25],\"word\":\"-65\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":834,\"width\":76},\"column\":[8],\"row\":[26],\"word\":\"65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":866,\"width\":76},\"column\":[8],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":896,\"width\":76},\"column\":[8],\"row\":[28],\"word\":\"-65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":928,\"width\":76},\"column\":[8],\"row\":[29],\"word\":\"-65\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":958,\"width\":76},\"column\":[8],\"row\":[30],\"word\":\"-64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":990,\"width\":76},\"column\":[8],\"row\":[31],\"word\":\"-64\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1022,\"width\":76},\"column\":[8],\"row\":[32],\"word\":\"-64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1052,\"width\":76},\"column\":[8],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1084,\"width\":76},\"column\":[8],\"row\":[34],\"word\":\"64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1114,\"width\":76},\"column\":[8],\"row\":[35],\"word\":\"64\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1146,\"width\":76},\"column\":[8],\"row\":[36],\"word\":\"-63\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1175,\"width\":76},\"column\":[8],\"row\":[37],\"word\":\"-63\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1205,\"width\":76},\"column\":[8],\"row\":[38],\"word\":\"-63\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1235,\"width\":76},\"column\":[8],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1267,\"width\":76},\"column\":[8],\"row\":[40],\"word\":\"-46\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1297,\"width\":76},\"column\":[8],\"row\":[41],\"word\":\"-41\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1327,\"width\":76},\"column\":[8],\"row\":[42],\"word\":\"-35\"},{\"rect\":{\"height\":28,\"left\":681,\"top\":1359,\"width\":76},\"column\":[8],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1387,\"width\":76},\"column\":[8],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1419,\"width\":76},\"column\":[8],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1449,\"width\":76},\"column\":[8],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1478,\"width\":76},\"column\":[8],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1510,\"width\":76},\"column\":[8],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1540,\"width\":76},\"column\":[8],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1572,\"width\":76},\"column\":[8],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1602,\"width\":76},\"column\":[8],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1634,\"width\":76},\"column\":[8],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1666,\"width\":76},\"column\":[8],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1698,\"width\":76},\"column\":[8],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":681,\"top\":1728,\"width\":76},\"column\":[8],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1756,\"width\":76},\"column\":[8],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1786,\"width\":76},\"column\":[8],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1815,\"width\":76},\"column\":[8],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":681,\"top\":1847,\"width\":76},\"column\":[8],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":681,\"top\":1884,\"width\":76},\"column\":[8],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":757,\"top\":6,\"width\":79},\"column\":[9],\"row\":[1],\"word\":\"tern)\"},{\"rect\":{\"height\":34,\"left\":757,\"top\":58,\"width\":79},\"column\":[9],\"row\":[2],\"word\":\"1.0m\"},{\"rect\":{\"height\":42,\"left\":757,\"top\":92,\"width\":79},\"column\":[9],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":757,\"top\":134,\"width\":79},\"column\":[9],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":181,\"width\":79},\"column\":[9],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":211,\"width\":79},\"column\":[9],\"row\":[6],\"word\":\"-50\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":243,\"width\":79},\"column\":[9],\"row\":[7],\"word\":\"-165\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":273,\"width\":79},\"column\":[9],\"row\":[8],\"word\":\"-160\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":305,\"width\":79},\"column\":[9],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":337,\"width\":79},\"column\":[9],\"row\":[10],\"word\":\"-154\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":367,\"width\":79},\"column\":[9],\"row\":[11],\"word\":\"-148\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":399,\"width\":79},\"column\":[9],\"row\":[12],\"word\":\"-142\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":429,\"width\":79},\"column\":[9],\"row\":[13],\"word\":\"-138\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":461,\"width\":79},\"column\":[9],\"row\":[14],\"word\":\"-133\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":493,\"width\":79},\"column\":[9],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":523,\"width\":79},\"column\":[9],\"row\":[16],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":555,\"width\":79},\"column\":[9],\"row\":[17],\"word\":\"-99\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":587,\"width\":79},\"column\":[9],\"row\":[18],\"word\":\"-92\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":617,\"width\":79},\"column\":[9],\"row\":[19],\"word\":\"-89\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":649,\"width\":79},\"column\":[9],\"row\":[20],\"word\":\"-87\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":678,\"width\":79},\"column\":[9],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":710,\"width\":79},\"column\":[9],\"row\":[22],\"word\":\"-85\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":742,\"width\":79},\"column\":[9],\"row\":[23],\"word\":\"-83\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":772,\"width\":79},\"column\":[9],\"row\":[24],\"word\":\"-80\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":804,\"width\":79},\"column\":[9],\"row\":[25],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":834,\"width\":79},\"column\":[9],\"row\":[26],\"word\":\"-80\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":866,\"width\":79},\"column\":[9],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":896,\"width\":79},\"column\":[9],\"row\":[28],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":928,\"width\":79},\"column\":[9],\"row\":[29],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":958,\"width\":79},\"column\":[9],\"row\":[30],\"word\":\"-80\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":990,\"width\":79},\"column\":[9],\"row\":[31],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1022,\"width\":79},\"column\":[9],\"row\":[32],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1052,\"width\":79},\"column\":[9],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1084,\"width\":79},\"column\":[9],\"row\":[34],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1114,\"width\":79},\"column\":[9],\"row\":[35],\"word\":\"-79\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1146,\"width\":79},\"column\":[9],\"row\":[36],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1175,\"width\":79},\"column\":[9],\"row\":[37],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1205,\"width\":79},\"column\":[9],\"row\":[38],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1235,\"width\":79},\"column\":[9],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1267,\"width\":79},\"column\":[9],\"row\":[40],\"word\":\"-50\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1297,\"width\":79},\"column\":[9],\"row\":[41],\"word\":\"-44\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1327,\"width\":79},\"column\":[9],\"row\":[42],\"word\":\"-36\"},{\"rect\":{\"height\":28,\"left\":757,\"top\":1359,\"width\":79},\"column\":[9],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1387,\"width\":79},\"column\":[9],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1419,\"width\":79},\"column\":[9],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1449,\"width\":79},\"column\":[9],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1478,\"width\":79},\"column\":[9],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1510,\"width\":79},\"column\":[9],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1540,\"width\":79},\"column\":[9],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1572,\"width\":79},\"column\":[9],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1602,\"width\":79},\"column\":[9],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1634,\"width\":79},\"column\":[9],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1666,\"width\":79},\"column\":[9],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1698,\"width\":79},\"column\":[9],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":757,\"top\":1728,\"width\":79},\"column\":[9],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1756,\"width\":79},\"column\":[9],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1786,\"width\":79},\"column\":[9],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1815,\"width\":79},\"column\":[9],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":757,\"top\":1847,\"width\":79},\"column\":[9],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":757,\"top\":1884,\"width\":79},\"column\":[9],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":836,\"top\":6,\"width\":77},\"column\":[10],\"row\":[1],\"word\":\"draft\"},{\"rect\":{\"height\":34,\"left\":836,\"top\":58,\"width\":77},\"column\":[10],\"row\":[2],\"word\":\"1.2m\"},{\"rect\":{\"height\":42,\"left\":836,\"top\":92,\"width\":77},\"column\":[10],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":836,\"top\":134,\"width\":77},\"column\":[10],\"row\":[4],\"word\":\"-199\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":181,\"width\":77},\"column\":[10],\"row\":[5],\"word\":\"-192\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":211,\"width\":77},\"column\":[10],\"row\":[6],\"word\":\"-186\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":243,\"width\":77},\"column\":[10],\"row\":[7],\"word\":\"-180\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":273,\"width\":77},\"column\":[10],\"row\":[8],\"word\":\"-174\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":305,\"width\":77},\"column\":[10],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":337,\"width\":77},\"column\":[10],\"row\":[10],\"word\":\"-168\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":367,\"width\":77},\"column\":[10],\"row\":[11],\"word\":\"-162\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":399,\"width\":77},\"column\":[10],\"row\":[12],\"word\":\"-158\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":429,\"width\":77},\"column\":[10],\"row\":[13],\"word\":\"-153\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":461,\"width\":77},\"column\":[10],\"row\":[14],\"word\":\"-150\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":493,\"width\":77},\"column\":[10],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":523,\"width\":77},\"column\":[10],\"row\":[16],\"word\":\"-145\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":555,\"width\":77},\"column\":[10],\"row\":[17],\"word\":\"-119\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":587,\"width\":77},\"column\":[10],\"row\":[18],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":617,\"width\":77},\"column\":[10],\"row\":[19],\"word\":\"-106\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":649,\"width\":77},\"column\":[10],\"row\":[20],\"word\":\"-104\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":678,\"width\":77},\"column\":[10],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":710,\"width\":77},\"column\":[10],\"row\":[22],\"word\":\"-102\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":742,\"width\":77},\"column\":[10],\"row\":[23],\"word\":\"-99\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":772,\"width\":77},\"column\":[10],\"row\":[24],\"word\":\"-96\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":804,\"width\":77},\"column\":[10],\"row\":[25],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":834,\"width\":77},\"column\":[10],\"row\":[26],\"word\":\"-96\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":866,\"width\":77},\"column\":[10],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":896,\"width\":77},\"column\":[10],\"row\":[28],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":928,\"width\":77},\"column\":[10],\"row\":[29],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":958,\"width\":77},\"column\":[10],\"row\":[30],\"word\":\"-96\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":990,\"width\":77},\"column\":[10],\"row\":[31],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1022,\"width\":77},\"column\":[10],\"row\":[32],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1052,\"width\":77},\"column\":[10],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1084,\"width\":77},\"column\":[10],\"row\":[34],\"word\":\"-95-\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1114,\"width\":77},\"column\":[10],\"row\":[35],\"word\":\"-95\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1146,\"width\":77},\"column\":[10],\"row\":[36],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1175,\"width\":77},\"column\":[10],\"row\":[37],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1205,\"width\":77},\"column\":[10],\"row\":[38],\"word\":\"-96\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1235,\"width\":77},\"column\":[10],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1267,\"width\":77},\"column\":[10],\"row\":[40],\"word\":\"-52\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1297,\"width\":77},\"column\":[10],\"row\":[41],\"word\":\"-45\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1327,\"width\":77},\"column\":[10],\"row\":[42],\"word\":\"-37\"},{\"rect\":{\"height\":28,\"left\":836,\"top\":1359,\"width\":77},\"column\":[10],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1387,\"width\":77},\"column\":[10],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1419,\"width\":77},\"column\":[10],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1449,\"width\":77},\"column\":[10],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1478,\"width\":77},\"column\":[10],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1510,\"width\":77},\"column\":[10],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1540,\"width\":77},\"column\":[10],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1572,\"width\":77},\"column\":[10],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1602,\"width\":77},\"column\":[10],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1634,\"width\":77},\"column\":[10],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1666,\"width\":77},\"column\":[10],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1698,\"width\":77},\"column\":[10],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":836,\"top\":1728,\"width\":77},\"column\":[10],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1756,\"width\":77},\"column\":[10],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1786,\"width\":77},\"column\":[10],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1815,\"width\":77},\"column\":[10],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":836,\"top\":1847,\"width\":77},\"column\":[10],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":836,\"top\":1884,\"width\":77},\"column\":[10],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":913,\"top\":6,\"width\":81},\"column\":[11],\"row\":[1],\"word\":\"orwar(\"},{\"rect\":{\"height\":34,\"left\":913,\"top\":58,\"width\":81},\"column\":[11],\"row\":[2],\"word\":\"1.4m\"},{\"rect\":{\"height\":42,\"left\":913,\"top\":92,\"width\":81},\"column\":[11],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":913,\"top\":134,\"width\":81},\"column\":[11],\"row\":[4],\"word\":\"-211\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":181,\"width\":81},\"column\":[11],\"row\":[5],\"word\":\"-204\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":211,\"width\":81},\"column\":[11],\"row\":[6],\"word\":\"-199\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":243,\"width\":81},\"column\":[11],\"row\":[7],\"word\":\"-193\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":273,\"width\":81},\"column\":[11],\"row\":[8],\"word\":\"-188\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":305,\"width\":81},\"column\":[11],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":337,\"width\":81},\"column\":[11],\"row\":[10],\"word\":\"-184\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":367,\"width\":81},\"column\":[11],\"row\":[11],\"word\":\"-178\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":399,\"width\":81},\"column\":[11],\"row\":[12],\"word\":\"-174\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":429,\"width\":81},\"column\":[11],\"row\":[13],\"word\":\"-171\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":461,\"width\":81},\"column\":[11],\"row\":[14],\"word\":\"-167\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":493,\"width\":81},\"column\":[11],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":523,\"width\":81},\"column\":[11],\"row\":[16],\"word\":\"-163\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":555,\"width\":81},\"column\":[11],\"row\":[17],\"word\":\"-139\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":587,\"width\":81},\"column\":[11],\"row\":[18],\"word\":\"-130\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":617,\"width\":81},\"column\":[11],\"row\":[19],\"word\":\"-124\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":649,\"width\":81},\"column\":[11],\"row\":[20],\"word\":\"-121\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":678,\"width\":81},\"column\":[11],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":710,\"width\":81},\"column\":[11],\"row\":[22],\"word\":\"-119\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":742,\"width\":81},\"column\":[11],\"row\":[23],\"word\":\"-115\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":772,\"width\":81},\"column\":[11],\"row\":[24],\"word\":\"-112\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":804,\"width\":81},\"column\":[11],\"row\":[25],\"word\":\"111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":834,\"width\":81},\"column\":[11],\"row\":[26],\"word\":\"-112\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":866,\"width\":81},\"column\":[11],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":896,\"width\":81},\"column\":[11],\"row\":[28],\"word\":\"111\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":928,\"width\":81},\"column\":[11],\"row\":[29],\"word\":\"-111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":958,\"width\":81},\"column\":[11],\"row\":[30],\"word\":\"-112\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":990,\"width\":81},\"column\":[11],\"row\":[31],\"word\":\"-111\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1022,\"width\":81},\"column\":[11],\"row\":[32],\"word\":\"-111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1052,\"width\":81},\"column\":[11],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1084,\"width\":81},\"column\":[11],\"row\":[34],\"word\":\"111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1114,\"width\":81},\"column\":[11],\"row\":[35],\"word\":\"-110\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1146,\"width\":81},\"column\":[11],\"row\":[36],\"word\":\"-110\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1175,\"width\":81},\"column\":[11],\"row\":[37],\"word\":\"-110\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1205,\"width\":81},\"column\":[11],\"row\":[38],\"word\":\"-113\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1235,\"width\":81},\"column\":[11],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1267,\"width\":81},\"column\":[11],\"row\":[40],\"word\":\"-54\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1297,\"width\":81},\"column\":[11],\"row\":[41],\"word\":\"-46\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1327,\"width\":81},\"column\":[11],\"row\":[42],\"word\":\"-37\"},{\"rect\":{\"height\":28,\"left\":913,\"top\":1359,\"width\":81},\"column\":[11],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1387,\"width\":81},\"column\":[11],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1419,\"width\":81},\"column\":[11],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1449,\"width\":81},\"column\":[11],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1478,\"width\":81},\"column\":[11],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1510,\"width\":81},\"column\":[11],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1540,\"width\":81},\"column\":[11],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1572,\"width\":81},\"column\":[11],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1602,\"width\":81},\"column\":[11],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1634,\"width\":81},\"column\":[11],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1666,\"width\":81},\"column\":[11],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1698,\"width\":81},\"column\":[11],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":913,\"top\":1728,\"width\":81},\"column\":[11],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1756,\"width\":81},\"column\":[11],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1786,\"width\":81},\"column\":[11],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1815,\"width\":81},\"column\":[11],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":913,\"top\":1847,\"width\":81},\"column\":[11],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":913,\"top\":1884,\"width\":81},\"column\":[11],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":994,\"top\":6,\"width\":79},\"column\":[12],\"row\":[1],\"word\":\"bow\"},{\"rect\":{\"height\":34,\"left\":994,\"top\":58,\"width\":79},\"column\":[12],\"row\":[2],\"word\":\"1.6m\"},{\"rect\":{\"height\":42,\"left\":994,\"top\":92,\"width\":79},\"column\":[12],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":994,\"top\":134,\"width\":79},\"column\":[12],\"row\":[4],\"word\":\"-224\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":181,\"width\":79},\"column\":[12],\"row\":[5],\"word\":\"-218\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":211,\"width\":79},\"column\":[12],\"row\":[6],\"word\":\"-212\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":243,\"width\":79},\"column\":[12],\"row\":[7],\"word\":\"-208\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":273,\"width\":79},\"column\":[12],\"row\":[8],\"word\":\"-205\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":305,\"width\":79},\"column\":[12],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":337,\"width\":79},\"column\":[12],\"row\":[10],\"word\":\"-200\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":367,\"width\":79},\"column\":[12],\"row\":[11],\"word\":\"-195\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":399,\"width\":79},\"column\":[12],\"row\":[12],\"word\":\"-192\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":429,\"width\":79},\"column\":[12],\"row\":[13],\"word\":\"-189\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":461,\"width\":79},\"column\":[12],\"row\":[14],\"word\":\"-185\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":493,\"width\":79},\"column\":[12],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":523,\"width\":79},\"column\":[12],\"row\":[16],\"word\":\"-181\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":555,\"width\":79},\"column\":[12],\"row\":[17],\"word\":\"-159\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":587,\"width\":79},\"column\":[12],\"row\":[18],\"word\":\"-147\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":617,\"width\":79},\"column\":[12],\"row\":[19],\"word\":\"-142\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":649,\"width\":79},\"column\":[12],\"row\":[20],\"word\":\"-138\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":678,\"width\":79},\"column\":[12],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":710,\"width\":79},\"column\":[12],\"row\":[22],\"word\":\"-137\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":742,\"width\":79},\"column\":[12],\"row\":[23],\"word\":\"-131\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":772,\"width\":79},\"column\":[12],\"row\":[24],\"word\":\"-128\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":804,\"width\":79},\"column\":[12],\"row\":[25],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":834,\"width\":79},\"column\":[12],\"row\":[26],\"word\":\"-127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":866,\"width\":79},\"column\":[12],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":896,\"width\":79},\"column\":[12],\"row\":[28],\"word\":\"-127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":928,\"width\":79},\"column\":[12],\"row\":[29],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":958,\"width\":79},\"column\":[12],\"row\":[30],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":990,\"width\":79},\"column\":[12],\"row\":[31],\"word\":\"127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1022,\"width\":79},\"column\":[12],\"row\":[32],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1052,\"width\":79},\"column\":[12],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1084,\"width\":79},\"column\":[12],\"row\":[34],\"word\":\"-126\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1114,\"width\":79},\"column\":[12],\"row\":[35],\"word\":\"-126\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1146,\"width\":79},\"column\":[12],\"row\":[36],\"word\":\"-126\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1175,\"width\":79},\"column\":[12],\"row\":[37],\"word\":\"-126\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1205,\"width\":79},\"column\":[12],\"row\":[38],\"word\":\"-130\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1235,\"width\":79},\"column\":[12],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1267,\"width\":79},\"column\":[12],\"row\":[40],\"word\":\"-55\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1297,\"width\":79},\"column\":[12],\"row\":[41],\"word\":\"-46\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1327,\"width\":79},\"column\":[12],\"row\":[42],\"word\":\"-38\"},{\"rect\":{\"height\":28,\"left\":994,\"top\":1359,\"width\":79},\"column\":[12],\"row\":[43],\"word\":\"-30\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1387,\"width\":79},\"column\":[12],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1419,\"width\":79},\"column\":[12],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1449,\"width\":79},\"column\":[12],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1478,\"width\":79},\"column\":[12],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1510,\"width\":79},\"column\":[12],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1540,\"width\":79},\"column\":[12],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1572,\"width\":79},\"column\":[12],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1602,\"width\":79},\"column\":[12],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1634,\"width\":79},\"column\":[12],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1666,\"width\":79},\"column\":[12],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1698,\"width\":79},\"column\":[12],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":994,\"top\":1728,\"width\":79},\"column\":[12],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1756,\"width\":79},\"column\":[12],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1786,\"width\":79},\"column\":[12],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1815,\"width\":79},\"column\":[12],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":994,\"top\":1847,\"width\":79},\"column\":[12],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":994,\"top\":1884,\"width\":79},\"column\":[12],\"row\":[60],\"word\":\"\"}]}]}","ret_msg":"\u5df2\u5b8c\u6210","percent":100,"ret_code":3},"log_id":"1584681967548143"}
//test_json;
        $test_json = <<<test_json
{"result":{"result_data":"{\"form_num\":1,\"forms\":[{\"footer\":[],\"header\":[{\"rect\":{\"top\":0,\"left\":0,\"width\":1080,\"height\":6},\"column\":[1],\"row\":[1],\"word\":\"\"}],\"body\":[{\"rect\":{\"height\":52,\"left\":107,\"top\":6,\"width\":106},\"column\":[1],\"row\":[1],\"word\":\"\u7a7a\u9ad8\"},{\"rect\":{\"height\":34,\"left\":107,\"top\":58,\"width\":106},\"column\":[1],\"row\":[2],\"word\":\"Ullage\"},{\"rect\":{\"height\":42,\"left\":107,\"top\":92,\"width\":106},\"column\":[1],\"row\":[3],\"word\":\"(m)\"},{\"rect\":{\"height\":47,\"left\":107,\"top\":134,\"width\":106},\"column\":[1],\"row\":[4],\"word\":\"0.200\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":181,\"width\":106},\"column\":[1],\"row\":[5],\"word\":\"0.210\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":211,\"width\":106},\"column\":[1],\"row\":[6],\"word\":\"0.220\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":243,\"width\":106},\"column\":[1],\"row\":[7],\"word\":\"0.230\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":273,\"width\":106},\"column\":[1],\"row\":[8],\"word\":\"0.240\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":305,\"width\":106},\"column\":[1],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":337,\"width\":106},\"column\":[1],\"row\":[10],\"word\":\"0.250\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":367,\"width\":106},\"column\":[1],\"row\":[11],\"word\":\"0.260\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":399,\"width\":106},\"column\":[1],\"row\":[12],\"word\":\"0.270\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":429,\"width\":106},\"column\":[1],\"row\":[13],\"word\":\"0.280\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":461,\"width\":106},\"column\":[1],\"row\":[14],\"word\":\"0.290\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":493,\"width\":106},\"column\":[1],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":523,\"width\":106},\"column\":[1],\"row\":[16],\"word\":\"0.300\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":555,\"width\":106},\"column\":[1],\"row\":[17],\"word\":\"0.400\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":587,\"width\":106},\"column\":[1],\"row\":[18],\"word\":\"0.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":617,\"width\":106},\"column\":[1],\"row\":[19],\"word\":\"0.600\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":649,\"width\":106},\"column\":[1],\"row\":[20],\"word\":\"0.700\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":678,\"width\":106},\"column\":[1],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":710,\"width\":106},\"column\":[1],\"row\":[22],\"word\":\"0.800\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":742,\"width\":106},\"column\":[1],\"row\":[23],\"word\":\"0.900\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":772,\"width\":106},\"column\":[1],\"row\":[24],\"word\":\"1.00\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":804,\"width\":106},\"column\":[1],\"row\":[25],\"word\":\"1.100\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":834,\"width\":106},\"column\":[1],\"row\":[26],\"word\":\"1.200\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":866,\"width\":106},\"column\":[1],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":896,\"width\":106},\"column\":[1],\"row\":[28],\"word\":\"1.300\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":928,\"width\":106},\"column\":[1],\"row\":[29],\"word\":\"1.400\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":958,\"width\":106},\"column\":[1],\"row\":[30],\"word\":\"1.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":990,\"width\":106},\"column\":[1],\"row\":[31],\"word\":\"2.000\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1022,\"width\":106},\"column\":[1],\"row\":[32],\"word\":\"2.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1052,\"width\":106},\"column\":[1],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1084,\"width\":106},\"column\":[1],\"row\":[34],\"word\":\"3.000\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1114,\"width\":106},\"column\":[1],\"row\":[35],\"word\":\"3.500\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1146,\"width\":106},\"column\":[1],\"row\":[36],\"word\":\"4.00\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1175,\"width\":106},\"column\":[1],\"row\":[37],\"word\":\"4.500\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1207,\"width\":106},\"column\":[1],\"row\":[38],\"word\":\"5.000\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1237,\"width\":106},\"column\":[1],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1267,\"width\":106},\"column\":[1],\"row\":[40],\"word\":\"5.890\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1297,\"width\":106},\"column\":[1],\"row\":[41],\"word\":\"5.900\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1327,\"width\":106},\"column\":[1],\"row\":[42],\"word\":\"5.910\"},{\"rect\":{\"height\":28,\"left\":107,\"top\":1359,\"width\":106},\"column\":[1],\"row\":[43],\"word\":\"5.920\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1387,\"width\":106},\"column\":[1],\"row\":[44],\"word\":\"5.930\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1419,\"width\":106},\"column\":[1],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1449,\"width\":106},\"column\":[1],\"row\":[46],\"word\":\"5.937\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1478,\"width\":106},\"column\":[1],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1510,\"width\":106},\"column\":[1],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1540,\"width\":106},\"column\":[1],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1572,\"width\":106},\"column\":[1],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1602,\"width\":106},\"column\":[1],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1634,\"width\":106},\"column\":[1],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1666,\"width\":106},\"column\":[1],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1698,\"width\":106},\"column\":[1],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":107,\"top\":1728,\"width\":106},\"column\":[1],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1756,\"width\":106},\"column\":[1],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1786,\"width\":106},\"column\":[1],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":107,\"top\":1815,\"width\":106},\"column\":[1],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":107,\"top\":1850,\"width\":106},\"column\":[1],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":107,\"top\":1884,\"width\":106},\"column\":[1],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":213,\"top\":6,\"width\":77},\"column\":[2],\"row\":[1],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":213,\"top\":58,\"width\":77},\"column\":[2],\"row\":[2],\"word\":\"-0.4m\"},{\"rect\":{\"height\":42,\"left\":213,\"top\":92,\"width\":77},\"column\":[2],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":213,\"top\":134,\"width\":77},\"column\":[2],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":181,\"width\":77},\"column\":[2],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":211,\"width\":77},\"column\":[2],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":243,\"width\":77},\"column\":[2],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":273,\"width\":77},\"column\":[2],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":305,\"width\":77},\"column\":[2],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":337,\"width\":77},\"column\":[2],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":367,\"width\":77},\"column\":[2],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":399,\"width\":77},\"column\":[2],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":429,\"width\":77},\"column\":[2],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":461,\"width\":77},\"column\":[2],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":493,\"width\":77},\"column\":[2],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":523,\"width\":77},\"column\":[2],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":555,\"width\":77},\"column\":[2],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":587,\"width\":77},\"column\":[2],\"row\":[18],\"word\":\"31\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":617,\"width\":77},\"column\":[2],\"row\":[19],\"word\":\"33\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":649,\"width\":77},\"column\":[2],\"row\":[20],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":678,\"width\":77},\"column\":[2],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":710,\"width\":77},\"column\":[2],\"row\":[22],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":742,\"width\":77},\"column\":[2],\"row\":[23],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":772,\"width\":77},\"column\":[2],\"row\":[24],\"word\":\"032\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":804,\"width\":77},\"column\":[2],\"row\":[25],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":834,\"width\":77},\"column\":[2],\"row\":[26],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":866,\"width\":77},\"column\":[2],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":896,\"width\":77},\"column\":[2],\"row\":[28],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":928,\"width\":77},\"column\":[2],\"row\":[29],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":958,\"width\":77},\"column\":[2],\"row\":[30],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":990,\"width\":77},\"column\":[2],\"row\":[31],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1022,\"width\":77},\"column\":[2],\"row\":[32],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1052,\"width\":77},\"column\":[2],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1084,\"width\":77},\"column\":[2],\"row\":[34],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1114,\"width\":77},\"column\":[2],\"row\":[35],\"word\":\"32\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1146,\"width\":77},\"column\":[2],\"row\":[36],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1175,\"width\":77},\"column\":[2],\"row\":[37],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1207,\"width\":77},\"column\":[2],\"row\":[38],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1237,\"width\":77},\"column\":[2],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1267,\"width\":77},\"column\":[2],\"row\":[40],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1297,\"width\":77},\"column\":[2],\"row\":[41],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1327,\"width\":77},\"column\":[2],\"row\":[42],\"word\":\"34\"},{\"rect\":{\"height\":28,\"left\":213,\"top\":1359,\"width\":77},\"column\":[2],\"row\":[43],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1387,\"width\":77},\"column\":[2],\"row\":[44],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1419,\"width\":77},\"column\":[2],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1449,\"width\":77},\"column\":[2],\"row\":[46],\"word\":\"35\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1478,\"width\":77},\"column\":[2],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1510,\"width\":77},\"column\":[2],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1540,\"width\":77},\"column\":[2],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1572,\"width\":77},\"column\":[2],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1602,\"width\":77},\"column\":[2],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1634,\"width\":77},\"column\":[2],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1666,\"width\":77},\"column\":[2],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1698,\"width\":77},\"column\":[2],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":213,\"top\":1728,\"width\":77},\"column\":[2],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1756,\"width\":77},\"column\":[2],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1786,\"width\":77},\"column\":[2],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":213,\"top\":1815,\"width\":77},\"column\":[2],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":213,\"top\":1850,\"width\":77},\"column\":[2],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":213,\"top\":1884,\"width\":77},\"column\":[2],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":290,\"top\":6,\"width\":79},\"column\":[3],\"row\":[1],\"word\":\"\u7eb5\u503e\u503c\"},{\"rect\":{\"height\":34,\"left\":290,\"top\":58,\"width\":79},\"column\":[3],\"row\":[2],\"word\":\"-0.2m\"},{\"rect\":{\"height\":42,\"left\":290,\"top\":92,\"width\":79},\"column\":[3],\"row\":[3],\"word\":\"()\"},{\"rect\":{\"height\":47,\"left\":290,\"top\":134,\"width\":79},\"column\":[3],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":181,\"width\":79},\"column\":[3],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":211,\"width\":79},\"column\":[3],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":243,\"width\":79},\"column\":[3],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":273,\"width\":79},\"column\":[3],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":305,\"width\":79},\"column\":[3],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":337,\"width\":79},\"column\":[3],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":367,\"width\":79},\"column\":[3],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":399,\"width\":79},\"column\":[3],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":429,\"width\":79},\"column\":[3],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":461,\"width\":79},\"column\":[3],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":493,\"width\":79},\"column\":[3],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":523,\"width\":79},\"column\":[3],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":555,\"width\":79},\"column\":[3],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":587,\"width\":79},\"column\":[3],\"row\":[18],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":617,\"width\":79},\"column\":[3],\"row\":[19],\"word\":\"17\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":649,\"width\":79},\"column\":[3],\"row\":[20],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":678,\"width\":79},\"column\":[3],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":710,\"width\":79},\"column\":[3],\"row\":[22],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":742,\"width\":79},\"column\":[3],\"row\":[23],\"word\":\"117\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":772,\"width\":79},\"column\":[3],\"row\":[24],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":804,\"width\":79},\"column\":[3],\"row\":[25],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":834,\"width\":79},\"column\":[3],\"row\":[26],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":866,\"width\":79},\"column\":[3],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":896,\"width\":79},\"column\":[3],\"row\":[28],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":928,\"width\":79},\"column\":[3],\"row\":[29],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":958,\"width\":79},\"column\":[3],\"row\":[30],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":990,\"width\":79},\"column\":[3],\"row\":[31],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1022,\"width\":79},\"column\":[3],\"row\":[32],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1052,\"width\":79},\"column\":[3],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1084,\"width\":79},\"column\":[3],\"row\":[34],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1114,\"width\":79},\"column\":[3],\"row\":[35],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1146,\"width\":79},\"column\":[3],\"row\":[36],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1175,\"width\":79},\"column\":[3],\"row\":[37],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1207,\"width\":79},\"column\":[3],\"row\":[38],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1237,\"width\":79},\"column\":[3],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1267,\"width\":79},\"column\":[3],\"row\":[40],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1297,\"width\":79},\"column\":[3],\"row\":[41],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1327,\"width\":79},\"column\":[3],\"row\":[42],\"word\":\"17\"},{\"rect\":{\"height\":28,\"left\":290,\"top\":1359,\"width\":79},\"column\":[3],\"row\":[43],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1387,\"width\":79},\"column\":[3],\"row\":[44],\"word\":\"18\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1419,\"width\":79},\"column\":[3],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1449,\"width\":79},\"column\":[3],\"row\":[46],\"word\":\"18\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1478,\"width\":79},\"column\":[3],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1510,\"width\":79},\"column\":[3],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1540,\"width\":79},\"column\":[3],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1572,\"width\":79},\"column\":[3],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1602,\"width\":79},\"column\":[3],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1634,\"width\":79},\"column\":[3],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1666,\"width\":79},\"column\":[3],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1698,\"width\":79},\"column\":[3],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":290,\"top\":1728,\"width\":79},\"column\":[3],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1756,\"width\":79},\"column\":[3],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1786,\"width\":79},\"column\":[3],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":290,\"top\":1815,\"width\":79},\"column\":[3],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":290,\"top\":1850,\"width\":79},\"column\":[3],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":290,\"top\":1884,\"width\":79},\"column\":[3],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":369,\"top\":6,\"width\":79},\"column\":[4],\"row\":[1],\"word\":\"(\u8249\u5403\"},{\"rect\":{\"height\":34,\"left\":369,\"top\":58,\"width\":79},\"column\":[4],\"row\":[2],\"word\":\"0.0m\"},{\"rect\":{\"height\":42,\"left\":369,\"top\":92,\"width\":79},\"column\":[4],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":369,\"top\":134,\"width\":79},\"column\":[4],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":181,\"width\":79},\"column\":[4],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":211,\"width\":79},\"column\":[4],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":243,\"width\":79},\"column\":[4],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":273,\"width\":79},\"column\":[4],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":305,\"width\":79},\"column\":[4],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":337,\"width\":79},\"column\":[4],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":367,\"width\":79},\"column\":[4],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":399,\"width\":79},\"column\":[4],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":429,\"width\":79},\"column\":[4],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":461,\"width\":79},\"column\":[4],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":493,\"width\":79},\"column\":[4],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":523,\"width\":79},\"column\":[4],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":555,\"width\":79},\"column\":[4],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":587,\"width\":79},\"column\":[4],\"row\":[18],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":617,\"width\":79},\"column\":[4],\"row\":[19],\"word\":\"0\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":649,\"width\":79},\"column\":[4],\"row\":[20],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":678,\"width\":79},\"column\":[4],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":710,\"width\":79},\"column\":[4],\"row\":[22],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":742,\"width\":79},\"column\":[4],\"row\":[23],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":772,\"width\":79},\"column\":[4],\"row\":[24],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":804,\"width\":79},\"column\":[4],\"row\":[25],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":834,\"width\":79},\"column\":[4],\"row\":[26],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":866,\"width\":79},\"column\":[4],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":896,\"width\":79},\"column\":[4],\"row\":[28],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":928,\"width\":79},\"column\":[4],\"row\":[29],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":958,\"width\":79},\"column\":[4],\"row\":[30],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":990,\"width\":79},\"column\":[4],\"row\":[31],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1022,\"width\":79},\"column\":[4],\"row\":[32],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1052,\"width\":79},\"column\":[4],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1084,\"width\":79},\"column\":[4],\"row\":[34],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1114,\"width\":79},\"column\":[4],\"row\":[35],\"word\":\"0\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1146,\"width\":79},\"column\":[4],\"row\":[36],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1175,\"width\":79},\"column\":[4],\"row\":[37],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1207,\"width\":79},\"column\":[4],\"row\":[38],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1237,\"width\":79},\"column\":[4],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1267,\"width\":79},\"column\":[4],\"row\":[40],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1297,\"width\":79},\"column\":[4],\"row\":[41],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1327,\"width\":79},\"column\":[4],\"row\":[42],\"word\":\"0\"},{\"rect\":{\"height\":28,\"left\":369,\"top\":1359,\"width\":79},\"column\":[4],\"row\":[43],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1387,\"width\":79},\"column\":[4],\"row\":[44],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1419,\"width\":79},\"column\":[4],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1449,\"width\":79},\"column\":[4],\"row\":[46],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1478,\"width\":79},\"column\":[4],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1510,\"width\":79},\"column\":[4],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1540,\"width\":79},\"column\":[4],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1572,\"width\":79},\"column\":[4],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1602,\"width\":79},\"column\":[4],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1634,\"width\":79},\"column\":[4],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1666,\"width\":79},\"column\":[4],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1698,\"width\":79},\"column\":[4],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":369,\"top\":1728,\"width\":79},\"column\":[4],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1756,\"width\":79},\"column\":[4],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1786,\"width\":79},\"column\":[4],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":369,\"top\":1815,\"width\":79},\"column\":[4],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":369,\"top\":1850,\"width\":79},\"column\":[4],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":369,\"top\":1884,\"width\":79},\"column\":[4],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":448,\"top\":6,\"width\":77},\"column\":[5],\"row\":[1],\"word\":\"\u6c34\u4e00\u824f\u5403\"},{\"rect\":{\"height\":34,\"left\":448,\"top\":58,\"width\":77},\"column\":[5],\"row\":[2],\"word\":\"0.2m\"},{\"rect\":{\"height\":42,\"left\":448,\"top\":92,\"width\":77},\"column\":[5],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":448,\"top\":134,\"width\":77},\"column\":[5],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":181,\"width\":77},\"column\":[5],\"row\":[5],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":211,\"width\":156},\"column\":[5,6],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":243,\"width\":77},\"column\":[5],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":273,\"width\":77},\"column\":[5],\"row\":[8],\"word\":\"90\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":305,\"width\":77},\"column\":[5],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":337,\"width\":77},\"column\":[5],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":367,\"width\":77},\"column\":[5],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":399,\"width\":77},\"column\":[5],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":429,\"width\":77},\"column\":[5],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":461,\"width\":77},\"column\":[5],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":493,\"width\":77},\"column\":[5],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":523,\"width\":77},\"column\":[5],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":555,\"width\":77},\"column\":[5],\"row\":[17],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":587,\"width\":77},\"column\":[5],\"row\":[18],\"word\":\"-19\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":617,\"width\":77},\"column\":[5],\"row\":[19],\"word\":\"-17\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":649,\"width\":77},\"column\":[5],\"row\":[20],\"word\":\"-18\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":678,\"width\":77},\"column\":[5],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":710,\"width\":77},\"column\":[5],\"row\":[22],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":742,\"width\":77},\"column\":[5],\"row\":[23],\"word\":\"-18\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":772,\"width\":77},\"column\":[5],\"row\":[24],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":804,\"width\":77},\"column\":[5],\"row\":[25],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":834,\"width\":77},\"column\":[5],\"row\":[26],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":866,\"width\":77},\"column\":[5],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":896,\"width\":77},\"column\":[5],\"row\":[28],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":928,\"width\":77},\"column\":[5],\"row\":[29],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":958,\"width\":77},\"column\":[5],\"row\":[30],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":990,\"width\":77},\"column\":[5],\"row\":[31],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1022,\"width\":77},\"column\":[5],\"row\":[32],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1052,\"width\":77},\"column\":[5],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1084,\"width\":77},\"column\":[5],\"row\":[34],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1114,\"width\":77},\"column\":[5],\"row\":[35],\"word\":\"-17\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1146,\"width\":77},\"column\":[5],\"row\":[36],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1175,\"width\":77},\"column\":[5],\"row\":[37],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1207,\"width\":77},\"column\":[5],\"row\":[38],\"word\":\"-16\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1237,\"width\":77},\"column\":[5],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1267,\"width\":77},\"column\":[5],\"row\":[40],\"word\":\"-18\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1297,\"width\":77},\"column\":[5],\"row\":[41],\"word\":\"-18\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1327,\"width\":77},\"column\":[5],\"row\":[42],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":448,\"top\":1359,\"width\":156},\"column\":[5,6],\"row\":[43],\"word\":\"24\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1387,\"width\":156},\"column\":[5,6],\"row\":[44],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1419,\"width\":77},\"column\":[5],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1449,\"width\":77},\"column\":[5],\"row\":[46],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1478,\"width\":77},\"column\":[5],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1510,\"width\":77},\"column\":[5],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1540,\"width\":77},\"column\":[5],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1572,\"width\":77},\"column\":[5],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1602,\"width\":77},\"column\":[5],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1634,\"width\":77},\"column\":[5],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1666,\"width\":77},\"column\":[5],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1698,\"width\":77},\"column\":[5],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":448,\"top\":1728,\"width\":77},\"column\":[5],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1756,\"width\":77},\"column\":[5],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1786,\"width\":77},\"column\":[5],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":448,\"top\":1815,\"width\":77},\"column\":[5],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":448,\"top\":1850,\"width\":77},\"column\":[5],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":448,\"top\":1884,\"width\":77},\"column\":[5],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":525,\"top\":6,\"width\":79},\"column\":[6],\"row\":[1],\"word\":\"\u6c34)ti\"},{\"rect\":{\"height\":34,\"left\":525,\"top\":58,\"width\":79},\"column\":[6],\"row\":[2],\"word\":\"0.4m\"},{\"rect\":{\"height\":42,\"left\":525,\"top\":92,\"width\":79},\"column\":[6],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":525,\"top\":134,\"width\":79},\"column\":[6],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":181,\"width\":79},\"column\":[6],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":243,\"width\":79},\"column\":[6],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":273,\"width\":79},\"column\":[6],\"row\":[8],\"word\":\"00\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":305,\"width\":79},\"column\":[6],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":337,\"width\":79},\"column\":[6],\"row\":[10],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":367,\"width\":79},\"column\":[6],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":399,\"width\":79},\"column\":[6],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":429,\"width\":79},\"column\":[6],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":461,\"width\":79},\"column\":[6],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":493,\"width\":79},\"column\":[6],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":523,\"width\":79},\"column\":[6],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":555,\"width\":79},\"column\":[6],\"row\":[17],\"word\":\"-38\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":587,\"width\":79},\"column\":[6],\"row\":[18],\"word\":\"-37\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":617,\"width\":79},\"column\":[6],\"row\":[19],\"word\":\"-36\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":649,\"width\":79},\"column\":[6],\"row\":[20],\"word\":\"-34\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":678,\"width\":79},\"column\":[6],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":710,\"width\":79},\"column\":[6],\"row\":[22],\"word\":\"-34\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":742,\"width\":79},\"column\":[6],\"row\":[23],\"word\":\"-34\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":772,\"width\":79},\"column\":[6],\"row\":[24],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":804,\"width\":79},\"column\":[6],\"row\":[25],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":834,\"width\":79},\"column\":[6],\"row\":[26],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":866,\"width\":79},\"column\":[6],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":896,\"width\":79},\"column\":[6],\"row\":[28],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":928,\"width\":79},\"column\":[6],\"row\":[29],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":958,\"width\":79},\"column\":[6],\"row\":[30],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":990,\"width\":79},\"column\":[6],\"row\":[31],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1022,\"width\":79},\"column\":[6],\"row\":[32],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1052,\"width\":79},\"column\":[6],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1084,\"width\":79},\"column\":[6],\"row\":[34],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1114,\"width\":79},\"column\":[6],\"row\":[35],\"word\":\"-33\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1146,\"width\":79},\"column\":[6],\"row\":[36],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1175,\"width\":79},\"column\":[6],\"row\":[37],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1207,\"width\":79},\"column\":[6],\"row\":[38],\"word\":\"-32\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1237,\"width\":79},\"column\":[6],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1267,\"width\":79},\"column\":[6],\"row\":[40],\"word\":\"-32\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1297,\"width\":79},\"column\":[6],\"row\":[41],\"word\":\"30\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1327,\"width\":79},\"column\":[6],\"row\":[42],\"word\":\"28\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1419,\"width\":232},\"column\":[6,8],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1449,\"width\":232},\"column\":[6,8],\"row\":[46],\"word\":\"1-13\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1478,\"width\":156},\"column\":[6,7],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1510,\"width\":79},\"column\":[6],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1540,\"width\":79},\"column\":[6],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1572,\"width\":79},\"column\":[6],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1602,\"width\":79},\"column\":[6],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1634,\"width\":79},\"column\":[6],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1666,\"width\":79},\"column\":[6],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1698,\"width\":79},\"column\":[6],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":525,\"top\":1728,\"width\":79},\"column\":[6],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1756,\"width\":79},\"column\":[6],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1786,\"width\":79},\"column\":[6],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":525,\"top\":1815,\"width\":79},\"column\":[6],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":525,\"top\":1850,\"width\":79},\"column\":[6],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":525,\"top\":1884,\"width\":79},\"column\":[6],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":604,\"top\":6,\"width\":77},\"column\":[7],\"row\":[1],\"word\":\"m[draf\"},{\"rect\":{\"height\":34,\"left\":604,\"top\":58,\"width\":77},\"column\":[7],\"row\":[2],\"word\":\"0.m\"},{\"rect\":{\"height\":42,\"left\":604,\"top\":92,\"width\":77},\"column\":[7],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":604,\"top\":134,\"width\":77},\"column\":[7],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":181,\"width\":77},\"column\":[7],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":211,\"width\":77},\"column\":[7],\"row\":[6],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":243,\"width\":77},\"column\":[7],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":273,\"width\":77},\"column\":[7],\"row\":[8],\"word\":\"90\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":305,\"width\":77},\"column\":[7],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":337,\"width\":77},\"column\":[7],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":367,\"width\":77},\"column\":[7],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":399,\"width\":77},\"column\":[7],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":429,\"width\":77},\"column\":[7],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":461,\"width\":77},\"column\":[7],\"row\":[14],\"word\":\"-28\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":493,\"width\":77},\"column\":[7],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":523,\"width\":77},\"column\":[7],\"row\":[16],\"word\":\"-9\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":555,\"width\":77},\"column\":[7],\"row\":[17],\"word\":\"-58\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":587,\"width\":77},\"column\":[7],\"row\":[18],\"word\":\"-56\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":617,\"width\":77},\"column\":[7],\"row\":[19],\"word\":\"-52\"},{\"rect\":{\"height\":29,\"left\":604,\"top\":649,\"width\":77},\"column\":[7],\"row\":[20],\"word\":\"-53\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":678,\"width\":77},\"column\":[7],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":710,\"width\":77},\"column\":[7],\"row\":[22],\"word\":\"-51\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":742,\"width\":77},\"column\":[7],\"row\":[23],\"word\":\"-52\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":772,\"width\":77},\"column\":[7],\"row\":[24],\"word\":\"-49\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":804,\"width\":77},\"column\":[7],\"row\":[25],\"word\":\"-49\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":834,\"width\":77},\"column\":[7],\"row\":[26],\"word\":\"-49\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":866,\"width\":77},\"column\":[7],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":896,\"width\":77},\"column\":[7],\"row\":[28],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":928,\"width\":77},\"column\":[7],\"row\":[29],\"word\":\"49\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":958,\"width\":77},\"column\":[7],\"row\":[30],\"word\":\"-49\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":990,\"width\":77},\"column\":[7],\"row\":[31],\"word\":\"49\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1022,\"width\":77},\"column\":[7],\"row\":[32],\"word\":\"48\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1052,\"width\":77},\"column\":[7],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1084,\"width\":77},\"column\":[7],\"row\":[34],\"word\":\"-48\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1114,\"width\":77},\"column\":[7],\"row\":[35],\"word\":\"-48\"},{\"rect\":{\"height\":29,\"left\":604,\"top\":1146,\"width\":77},\"column\":[7],\"row\":[36],\"word\":\"-47\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1175,\"width\":77},\"column\":[7],\"row\":[37],\"word\":\"-47\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1207,\"width\":77},\"column\":[7],\"row\":[38],\"word\":\"47\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1237,\"width\":77},\"column\":[7],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1267,\"width\":77},\"column\":[7],\"row\":[40],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1297,\"width\":77},\"column\":[7],\"row\":[41],\"word\":\"37\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1327,\"width\":77},\"column\":[7],\"row\":[42],\"word\":\"-32\"},{\"rect\":{\"height\":28,\"left\":604,\"top\":1359,\"width\":77},\"column\":[7],\"row\":[43],\"word\":\"27\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1387,\"width\":153},\"column\":[7,8],\"row\":[44],\"word\":\"19-20\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1510,\"width\":77},\"column\":[7],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1540,\"width\":77},\"column\":[7],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1572,\"width\":77},\"column\":[7],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1602,\"width\":77},\"column\":[7],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1634,\"width\":77},\"column\":[7],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":604,\"top\":1666,\"width\":77},\"column\":[7],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1698,\"width\":77},\"column\":[7],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":604,\"top\":1728,\"width\":77},\"column\":[7],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":604,\"top\":1756,\"width\":77},\"column\":[7],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":604,\"top\":1786,\"width\":77},\"column\":[7],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":604,\"top\":1815,\"width\":77},\"column\":[7],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":604,\"top\":1850,\"width\":77},\"column\":[7],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":604,\"top\":1884,\"width\":77},\"column\":[7],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":681,\"top\":6,\"width\":76},\"column\":[8],\"row\":[1],\"word\":\"taft(\"},{\"rect\":{\"height\":34,\"left\":681,\"top\":58,\"width\":76},\"column\":[8],\"row\":[2],\"word\":\"0.8m\"},{\"rect\":{\"height\":42,\"left\":681,\"top\":92,\"width\":76},\"column\":[8],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":681,\"top\":134,\"width\":76},\"column\":[8],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":181,\"width\":76},\"column\":[8],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":211,\"width\":76},\"column\":[8],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":243,\"width\":76},\"column\":[8],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":273,\"width\":76},\"column\":[8],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":305,\"width\":76},\"column\":[8],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":337,\"width\":76},\"column\":[8],\"row\":[10],\"word\":\"-0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":367,\"width\":76},\"column\":[8],\"row\":[11],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":399,\"width\":76},\"column\":[8],\"row\":[12],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":429,\"width\":76},\"column\":[8],\"row\":[13],\"word\":\"-120\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":461,\"width\":76},\"column\":[8],\"row\":[14],\"word\":\"-116\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":493,\"width\":76},\"column\":[8],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":523,\"width\":76},\"column\":[8],\"row\":[16],\"word\":\"-112\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":555,\"width\":76},\"column\":[8],\"row\":[17],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":587,\"width\":76},\"column\":[8],\"row\":[18],\"word\":\"-74\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":617,\"width\":76},\"column\":[8],\"row\":[19],\"word\":\"-71\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":649,\"width\":76},\"column\":[8],\"row\":[20],\"word\":\"-69\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":678,\"width\":76},\"column\":[8],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":710,\"width\":76},\"column\":[8],\"row\":[22],\"word\":\"-68\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":742,\"width\":76},\"column\":[8],\"row\":[23],\"word\":\"-68\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":772,\"width\":76},\"column\":[8],\"row\":[24],\"word\":\"-65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":804,\"width\":76},\"column\":[8],\"row\":[25],\"word\":\"-65\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":834,\"width\":76},\"column\":[8],\"row\":[26],\"word\":\"65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":866,\"width\":76},\"column\":[8],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":896,\"width\":76},\"column\":[8],\"row\":[28],\"word\":\"-65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":928,\"width\":76},\"column\":[8],\"row\":[29],\"word\":\"-65\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":958,\"width\":76},\"column\":[8],\"row\":[30],\"word\":\"-64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":990,\"width\":76},\"column\":[8],\"row\":[31],\"word\":\"-64\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1022,\"width\":76},\"column\":[8],\"row\":[32],\"word\":\"-64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1052,\"width\":76},\"column\":[8],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1084,\"width\":76},\"column\":[8],\"row\":[34],\"word\":\"64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1114,\"width\":76},\"column\":[8],\"row\":[35],\"word\":\"-64\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1146,\"width\":76},\"column\":[8],\"row\":[36],\"word\":\"-63\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1175,\"width\":76},\"column\":[8],\"row\":[37],\"word\":\"63\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1207,\"width\":76},\"column\":[8],\"row\":[38],\"word\":\"-63\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1237,\"width\":76},\"column\":[8],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1267,\"width\":76},\"column\":[8],\"row\":[40],\"word\":\"-46\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1297,\"width\":76},\"column\":[8],\"row\":[41],\"word\":\"-41\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1327,\"width\":76},\"column\":[8],\"row\":[42],\"word\":\"-35\"},{\"rect\":{\"height\":28,\"left\":681,\"top\":1359,\"width\":76},\"column\":[8],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1478,\"width\":76},\"column\":[8],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1510,\"width\":76},\"column\":[8],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1540,\"width\":76},\"column\":[8],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1572,\"width\":76},\"column\":[8],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1602,\"width\":76},\"column\":[8],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1634,\"width\":76},\"column\":[8],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1666,\"width\":76},\"column\":[8],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1698,\"width\":76},\"column\":[8],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":681,\"top\":1728,\"width\":76},\"column\":[8],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1756,\"width\":76},\"column\":[8],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1786,\"width\":76},\"column\":[8],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":681,\"top\":1815,\"width\":76},\"column\":[8],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":681,\"top\":1850,\"width\":76},\"column\":[8],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":681,\"top\":1884,\"width\":76},\"column\":[8],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":757,\"top\":6,\"width\":79},\"column\":[9],\"row\":[1],\"word\":\"tern)\"},{\"rect\":{\"height\":34,\"left\":757,\"top\":58,\"width\":79},\"column\":[9],\"row\":[2],\"word\":\"1.0m\"},{\"rect\":{\"height\":42,\"left\":757,\"top\":92,\"width\":79},\"column\":[9],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":757,\"top\":134,\"width\":79},\"column\":[9],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":181,\"width\":79},\"column\":[9],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":211,\"width\":79},\"column\":[9],\"row\":[6],\"word\":\"-50\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":243,\"width\":79},\"column\":[9],\"row\":[7],\"word\":\"-165\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":273,\"width\":79},\"column\":[9],\"row\":[8],\"word\":\"-160\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":305,\"width\":79},\"column\":[9],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":337,\"width\":79},\"column\":[9],\"row\":[10],\"word\":\"-154\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":367,\"width\":79},\"column\":[9],\"row\":[11],\"word\":\"-148\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":399,\"width\":79},\"column\":[9],\"row\":[12],\"word\":\"-142\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":429,\"width\":79},\"column\":[9],\"row\":[13],\"word\":\"-138\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":461,\"width\":79},\"column\":[9],\"row\":[14],\"word\":\"-133\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":493,\"width\":79},\"column\":[9],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":523,\"width\":79},\"column\":[9],\"row\":[16],\"word\":\"128\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":555,\"width\":79},\"column\":[9],\"row\":[17],\"word\":\"-99\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":587,\"width\":79},\"column\":[9],\"row\":[18],\"word\":\"-92\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":617,\"width\":79},\"column\":[9],\"row\":[19],\"word\":\"-89\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":649,\"width\":79},\"column\":[9],\"row\":[20],\"word\":\"-87\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":678,\"width\":79},\"column\":[9],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":710,\"width\":79},\"column\":[9],\"row\":[22],\"word\":\"-85\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":742,\"width\":79},\"column\":[9],\"row\":[23],\"word\":\"-83\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":772,\"width\":79},\"column\":[9],\"row\":[24],\"word\":\"-80\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":804,\"width\":79},\"column\":[9],\"row\":[25],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":834,\"width\":79},\"column\":[9],\"row\":[26],\"word\":\"-80\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":866,\"width\":79},\"column\":[9],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":896,\"width\":79},\"column\":[9],\"row\":[28],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":928,\"width\":79},\"column\":[9],\"row\":[29],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":958,\"width\":79},\"column\":[9],\"row\":[30],\"word\":\"-80\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":990,\"width\":79},\"column\":[9],\"row\":[31],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1022,\"width\":79},\"column\":[9],\"row\":[32],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1052,\"width\":79},\"column\":[9],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1084,\"width\":79},\"column\":[9],\"row\":[34],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1114,\"width\":79},\"column\":[9],\"row\":[35],\"word\":\"-79\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1146,\"width\":79},\"column\":[9],\"row\":[36],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1175,\"width\":79},\"column\":[9],\"row\":[37],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1207,\"width\":79},\"column\":[9],\"row\":[38],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1237,\"width\":79},\"column\":[9],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1267,\"width\":79},\"column\":[9],\"row\":[40],\"word\":\"-50\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1297,\"width\":79},\"column\":[9],\"row\":[41],\"word\":\"-44\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1327,\"width\":79},\"column\":[9],\"row\":[42],\"word\":\"-36\"},{\"rect\":{\"height\":28,\"left\":757,\"top\":1359,\"width\":79},\"column\":[9],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1387,\"width\":79},\"column\":[9],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1419,\"width\":79},\"column\":[9],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1449,\"width\":79},\"column\":[9],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1478,\"width\":79},\"column\":[9],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1510,\"width\":79},\"column\":[9],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1540,\"width\":79},\"column\":[9],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1572,\"width\":79},\"column\":[9],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1602,\"width\":79},\"column\":[9],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1634,\"width\":79},\"column\":[9],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1666,\"width\":79},\"column\":[9],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1698,\"width\":79},\"column\":[9],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":757,\"top\":1728,\"width\":79},\"column\":[9],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1756,\"width\":79},\"column\":[9],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1786,\"width\":79},\"column\":[9],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":757,\"top\":1815,\"width\":79},\"column\":[9],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":757,\"top\":1850,\"width\":79},\"column\":[9],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":757,\"top\":1884,\"width\":79},\"column\":[9],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":836,\"top\":6,\"width\":77},\"column\":[10],\"row\":[1],\"word\":\"draftf\"},{\"rect\":{\"height\":34,\"left\":836,\"top\":58,\"width\":77},\"column\":[10],\"row\":[2],\"word\":\"1.2m\"},{\"rect\":{\"height\":42,\"left\":836,\"top\":92,\"width\":77},\"column\":[10],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":836,\"top\":134,\"width\":77},\"column\":[10],\"row\":[4],\"word\":\"-199\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":181,\"width\":77},\"column\":[10],\"row\":[5],\"word\":\"-192\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":211,\"width\":77},\"column\":[10],\"row\":[6],\"word\":\"-186\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":243,\"width\":77},\"column\":[10],\"row\":[7],\"word\":\"-180\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":273,\"width\":77},\"column\":[10],\"row\":[8],\"word\":\"-174\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":305,\"width\":77},\"column\":[10],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":337,\"width\":77},\"column\":[10],\"row\":[10],\"word\":\"-168\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":367,\"width\":77},\"column\":[10],\"row\":[11],\"word\":\"-162\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":399,\"width\":77},\"column\":[10],\"row\":[12],\"word\":\"-158\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":429,\"width\":77},\"column\":[10],\"row\":[13],\"word\":\"-153\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":461,\"width\":77},\"column\":[10],\"row\":[14],\"word\":\"-150\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":493,\"width\":77},\"column\":[10],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":523,\"width\":77},\"column\":[10],\"row\":[16],\"word\":\"-145\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":555,\"width\":77},\"column\":[10],\"row\":[17],\"word\":\"-119\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":587,\"width\":77},\"column\":[10],\"row\":[18],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":617,\"width\":77},\"column\":[10],\"row\":[19],\"word\":\"-106\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":649,\"width\":77},\"column\":[10],\"row\":[20],\"word\":\"-104\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":678,\"width\":77},\"column\":[10],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":710,\"width\":77},\"column\":[10],\"row\":[22],\"word\":\"-102\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":742,\"width\":77},\"column\":[10],\"row\":[23],\"word\":\"-99-\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":772,\"width\":77},\"column\":[10],\"row\":[24],\"word\":\"-96\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":804,\"width\":77},\"column\":[10],\"row\":[25],\"word\":\"-95-\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":834,\"width\":77},\"column\":[10],\"row\":[26],\"word\":\"-96\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":866,\"width\":77},\"column\":[10],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":896,\"width\":77},\"column\":[10],\"row\":[28],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":928,\"width\":77},\"column\":[10],\"row\":[29],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":958,\"width\":77},\"column\":[10],\"row\":[30],\"word\":\"-96\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":990,\"width\":77},\"column\":[10],\"row\":[31],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1022,\"width\":77},\"column\":[10],\"row\":[32],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1052,\"width\":77},\"column\":[10],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1084,\"width\":77},\"column\":[10],\"row\":[34],\"word\":\"-95-\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1114,\"width\":77},\"column\":[10],\"row\":[35],\"word\":\"-95\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1146,\"width\":77},\"column\":[10],\"row\":[36],\"word\":\"-95-\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1175,\"width\":77},\"column\":[10],\"row\":[37],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1207,\"width\":77},\"column\":[10],\"row\":[38],\"word\":\"-96\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1237,\"width\":77},\"column\":[10],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1267,\"width\":77},\"column\":[10],\"row\":[40],\"word\":\"-52\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1297,\"width\":77},\"column\":[10],\"row\":[41],\"word\":\"-45\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1327,\"width\":77},\"column\":[10],\"row\":[42],\"word\":\"-37\"},{\"rect\":{\"height\":28,\"left\":836,\"top\":1359,\"width\":77},\"column\":[10],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1387,\"width\":77},\"column\":[10],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1419,\"width\":77},\"column\":[10],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1449,\"width\":77},\"column\":[10],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1478,\"width\":77},\"column\":[10],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1510,\"width\":77},\"column\":[10],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1540,\"width\":77},\"column\":[10],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1572,\"width\":77},\"column\":[10],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1602,\"width\":77},\"column\":[10],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1634,\"width\":77},\"column\":[10],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1666,\"width\":77},\"column\":[10],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1698,\"width\":77},\"column\":[10],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":836,\"top\":1728,\"width\":77},\"column\":[10],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1756,\"width\":77},\"column\":[10],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1786,\"width\":77},\"column\":[10],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":836,\"top\":1815,\"width\":77},\"column\":[10],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":836,\"top\":1850,\"width\":77},\"column\":[10],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":836,\"top\":1884,\"width\":77},\"column\":[10],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":913,\"top\":6,\"width\":81},\"column\":[11],\"row\":[1],\"word\":\"orwar\"},{\"rect\":{\"height\":34,\"left\":913,\"top\":58,\"width\":81},\"column\":[11],\"row\":[2],\"word\":\"1.4m\"},{\"rect\":{\"height\":42,\"left\":913,\"top\":92,\"width\":81},\"column\":[11],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":913,\"top\":134,\"width\":81},\"column\":[11],\"row\":[4],\"word\":\"-211\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":181,\"width\":81},\"column\":[11],\"row\":[5],\"word\":\"-204\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":211,\"width\":81},\"column\":[11],\"row\":[6],\"word\":\"-199\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":243,\"width\":81},\"column\":[11],\"row\":[7],\"word\":\"-193\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":273,\"width\":81},\"column\":[11],\"row\":[8],\"word\":\"-188\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":305,\"width\":81},\"column\":[11],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":337,\"width\":81},\"column\":[11],\"row\":[10],\"word\":\"-184\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":367,\"width\":81},\"column\":[11],\"row\":[11],\"word\":\"-178\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":399,\"width\":81},\"column\":[11],\"row\":[12],\"word\":\"-174\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":429,\"width\":81},\"column\":[11],\"row\":[13],\"word\":\"-171\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":461,\"width\":81},\"column\":[11],\"row\":[14],\"word\":\"-167\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":493,\"width\":81},\"column\":[11],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":523,\"width\":81},\"column\":[11],\"row\":[16],\"word\":\"-163\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":555,\"width\":81},\"column\":[11],\"row\":[17],\"word\":\"-139\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":587,\"width\":81},\"column\":[11],\"row\":[18],\"word\":\"-130\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":617,\"width\":81},\"column\":[11],\"row\":[19],\"word\":\"-124\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":649,\"width\":81},\"column\":[11],\"row\":[20],\"word\":\"-121\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":678,\"width\":81},\"column\":[11],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":710,\"width\":81},\"column\":[11],\"row\":[22],\"word\":\"-119\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":742,\"width\":81},\"column\":[11],\"row\":[23],\"word\":\"115\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":772,\"width\":81},\"column\":[11],\"row\":[24],\"word\":\"-112\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":804,\"width\":81},\"column\":[11],\"row\":[25],\"word\":\"111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":834,\"width\":81},\"column\":[11],\"row\":[26],\"word\":\"-112\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":866,\"width\":81},\"column\":[11],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":896,\"width\":81},\"column\":[11],\"row\":[28],\"word\":\"-111\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":928,\"width\":81},\"column\":[11],\"row\":[29],\"word\":\"-111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":958,\"width\":81},\"column\":[11],\"row\":[30],\"word\":\"-112\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":990,\"width\":81},\"column\":[11],\"row\":[31],\"word\":\"-111\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1022,\"width\":81},\"column\":[11],\"row\":[32],\"word\":\"-111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1052,\"width\":81},\"column\":[11],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1084,\"width\":81},\"column\":[11],\"row\":[34],\"word\":\"111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1114,\"width\":81},\"column\":[11],\"row\":[35],\"word\":\"-110\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1146,\"width\":81},\"column\":[11],\"row\":[36],\"word\":\"110\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1175,\"width\":81},\"column\":[11],\"row\":[37],\"word\":\"-110\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1207,\"width\":81},\"column\":[11],\"row\":[38],\"word\":\"-113\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1237,\"width\":81},\"column\":[11],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1267,\"width\":81},\"column\":[11],\"row\":[40],\"word\":\"-54\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1297,\"width\":81},\"column\":[11],\"row\":[41],\"word\":\"-46\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1327,\"width\":81},\"column\":[11],\"row\":[42],\"word\":\"-37\"},{\"rect\":{\"height\":28,\"left\":913,\"top\":1359,\"width\":81},\"column\":[11],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1387,\"width\":81},\"column\":[11],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1419,\"width\":81},\"column\":[11],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1449,\"width\":81},\"column\":[11],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1478,\"width\":81},\"column\":[11],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1510,\"width\":81},\"column\":[11],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1540,\"width\":81},\"column\":[11],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1572,\"width\":81},\"column\":[11],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1602,\"width\":81},\"column\":[11],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1634,\"width\":81},\"column\":[11],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1666,\"width\":81},\"column\":[11],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1698,\"width\":81},\"column\":[11],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":913,\"top\":1728,\"width\":81},\"column\":[11],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1756,\"width\":81},\"column\":[11],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1786,\"width\":81},\"column\":[11],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":913,\"top\":1815,\"width\":81},\"column\":[11],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":913,\"top\":1850,\"width\":81},\"column\":[11],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":913,\"top\":1884,\"width\":81},\"column\":[11],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":994,\"top\":6,\"width\":79},\"column\":[12],\"row\":[1],\"word\":\"(bow\"},{\"rect\":{\"height\":34,\"left\":994,\"top\":58,\"width\":79},\"column\":[12],\"row\":[2],\"word\":\"1.6m\"},{\"rect\":{\"height\":42,\"left\":994,\"top\":92,\"width\":79},\"column\":[12],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":994,\"top\":134,\"width\":79},\"column\":[12],\"row\":[4],\"word\":\"-224\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":181,\"width\":79},\"column\":[12],\"row\":[5],\"word\":\"-218\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":211,\"width\":79},\"column\":[12],\"row\":[6],\"word\":\"-212\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":243,\"width\":79},\"column\":[12],\"row\":[7],\"word\":\"-208\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":273,\"width\":79},\"column\":[12],\"row\":[8],\"word\":\"-205\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":305,\"width\":79},\"column\":[12],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":337,\"width\":79},\"column\":[12],\"row\":[10],\"word\":\"-200\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":367,\"width\":79},\"column\":[12],\"row\":[11],\"word\":\"-195\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":399,\"width\":79},\"column\":[12],\"row\":[12],\"word\":\"-192\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":429,\"width\":79},\"column\":[12],\"row\":[13],\"word\":\"-189\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":461,\"width\":79},\"column\":[12],\"row\":[14],\"word\":\"-185\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":493,\"width\":79},\"column\":[12],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":523,\"width\":79},\"column\":[12],\"row\":[16],\"word\":\"-181\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":555,\"width\":79},\"column\":[12],\"row\":[17],\"word\":\"-159\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":587,\"width\":79},\"column\":[12],\"row\":[18],\"word\":\"-147\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":617,\"width\":79},\"column\":[12],\"row\":[19],\"word\":\"-142\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":649,\"width\":79},\"column\":[12],\"row\":[20],\"word\":\"-138\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":678,\"width\":79},\"column\":[12],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":710,\"width\":79},\"column\":[12],\"row\":[22],\"word\":\"-137\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":742,\"width\":79},\"column\":[12],\"row\":[23],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":772,\"width\":79},\"column\":[12],\"row\":[24],\"word\":\"-128\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":804,\"width\":79},\"column\":[12],\"row\":[25],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":834,\"width\":79},\"column\":[12],\"row\":[26],\"word\":\"-127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":866,\"width\":79},\"column\":[12],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":896,\"width\":79},\"column\":[12],\"row\":[28],\"word\":\"-127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":928,\"width\":79},\"column\":[12],\"row\":[29],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":958,\"width\":79},\"column\":[12],\"row\":[30],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":990,\"width\":79},\"column\":[12],\"row\":[31],\"word\":\"127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1022,\"width\":79},\"column\":[12],\"row\":[32],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1052,\"width\":79},\"column\":[12],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1084,\"width\":79},\"column\":[12],\"row\":[34],\"word\":\"-126\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1114,\"width\":79},\"column\":[12],\"row\":[35],\"word\":\"-126\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1146,\"width\":79},\"column\":[12],\"row\":[36],\"word\":\"-126\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1175,\"width\":79},\"column\":[12],\"row\":[37],\"word\":\"-126\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1207,\"width\":79},\"column\":[12],\"row\":[38],\"word\":\"-130\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1237,\"width\":79},\"column\":[12],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1267,\"width\":79},\"column\":[12],\"row\":[40],\"word\":\"-55\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1297,\"width\":79},\"column\":[12],\"row\":[41],\"word\":\"-46\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1327,\"width\":79},\"column\":[12],\"row\":[42],\"word\":\"-38\"},{\"rect\":{\"height\":28,\"left\":994,\"top\":1359,\"width\":79},\"column\":[12],\"row\":[43],\"word\":\"-30\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1387,\"width\":79},\"column\":[12],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1419,\"width\":79},\"column\":[12],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1449,\"width\":79},\"column\":[12],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1478,\"width\":79},\"column\":[12],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1510,\"width\":79},\"column\":[12],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1540,\"width\":79},\"column\":[12],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1572,\"width\":79},\"column\":[12],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1602,\"width\":79},\"column\":[12],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1634,\"width\":79},\"column\":[12],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1666,\"width\":79},\"column\":[12],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1698,\"width\":79},\"column\":[12],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":994,\"top\":1728,\"width\":79},\"column\":[12],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1756,\"width\":79},\"column\":[12],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1786,\"width\":79},\"column\":[12],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":35,\"left\":994,\"top\":1815,\"width\":79},\"column\":[12],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":994,\"top\":1850,\"width\":79},\"column\":[12],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":994,\"top\":1884,\"width\":79},\"column\":[12],\"row\":[60],\"word\":\"\"}]}]}","ret_msg":"\u5df2\u5b8c\u6210","percent":100,"ret_code":3},"log_id":"1584684892720516"}
test_json;

        $test_arr = json_decode($test_json, true);
        $test_date_arr = json_decode($test_arr['result']['result_data'], true);
        //获取数据。如果收到两个数据
//        print_r($test_date_arr['forms']);
        $table_datas = array();

        $max_column = 0;
        $max_row = 0;

        $draft_up = 0;
        $draft_down = 0;

        $ullage_up = 0;
        $ullage_down = 0;

        //吃水差数组
        $draft_arr = array();
        //空高数组
        $ullage_arr = array();
        //序列化数组，行内包含单元格
        foreach ($test_date_arr['forms'] as $key => $form_data) {
            foreach ($form_data['body'] as $k => $v) {
                $column = $v['column'][0];
                $max_column = $column > $max_column ? $column : $max_column;
                $row = $v['row'][0];
                $max_row = $row > $max_row ? $row : $max_row;
                //如果不存在行数组，自动创建
                if (!isset($table_datas[$row])) $table_datas[$row] = array();
                $table_datas[$row][$column] = $v['word'];


                //获取吃水差列表
                if ($row == 2 and $column > 1 and $v['word'] != "") {
                    //处理一下数字
                    $now_draft = (float)$v['word'];
                    //获取当前吃水差和上一个吃水差
                    if ($draft_up == 0) {
                        $pre_arr = end($draft_arr);
                        if ($draft == $now_draft) {
                            $draft_up = $column;
                            $draft_down = $column;
                        } else {
                            if ($pre_arr['val'] < $draft and $now_draft > $draft) {
                                $draft_up = $pre_arr['column'];
                                $draft_down = $column;
                            }
                        }
                    }
                    $draft_arr[] = array('val' => $now_draft, 'column' => $column);
                }
                //获取空高列表
                if ($column == 1 and $row > 3 and $v['word'] != "") {
                    //处理一下数字
                    $now_ullage = (float)$v['word'];
                    //去除空白列如果内容为空的话
                    if (!(($row - 3) % 6 == 0 and $now_ullage == 0)) {
                        //获取当前吃水差和上一个吃水差
                        if ($ullage_up == 0) {
                            $pre_arr = end($ullage_arr);
                            if ($ullage == $now_ullage) {
                                $ullage_up = $row;
                                $ullage_down = $row;
                            } else {
                                if ($pre_arr['val'] < $ullage and $now_ullage > $ullage) {
                                    $ullage_up = $pre_arr['row'];
                                    $ullage_down = $row;
                                }
                            }
                        }
                        $ullage_arr[] = array('val' => $now_ullage, 'row' => $row);
                    }
                }
            }
        }
        /*//序列化数组，行内包含单元格
        foreach ($test_date_arr['forms'] as $key => $form_data) {
            foreach ($form_data['body'] as $k => $v) {
                $column = $v['column'][0];
                $max_column = $column > $max_column ? $column : $max_column;
                $row = $v['row'][0];
                $max_row = $row > $max_row ? $row : $max_row;
                //如果不存在行数组，自动创建
                if (!isset($table_datas[$row])) $table_datas[$row] = array();
                $table_datas[$row][$column] = $v['word'];



                //获取吃水差列表
                if ($row == 2 and $column > 1 and $v['word']!="") {
                    //处理一下数字
                    $now_draft =(float)$v['word'];
                    //获取当前吃水差和上一个吃水差
                    if($draft_up ==0){
                        $pre_arr = end($draft_arr);
                        if($draft == $now_draft){
                            $draft_up = $column;
                            $draft_down = $column;
                        }else{
                            if($pre_arr['val']<$draft and $now_draft>$draft){
                                $draft_up = $pre_arr['column'];
                                $draft_down = $column;
                            }
                        }
                    }
                    $draft_arr[] = array('val'=>$now_draft,'column'=>$column);
                }
                //获取空高列表
                if ($column == 1 and $row > 3 and $v['word']!="") {
                    //处理一下数字
                    $now_ullage=(float)$v['word'];
                    //去除空白列如果内容为空的话
                    if (!(($row - 3) % 6 == 0 and  $now_ullage== 0)) {
                        //获取当前吃水差和上一个吃水差
                        if($ullage_up ==0){
                            $pre_arr = end($ullage_arr);
                            if($ullage == $now_ullage){
                                $ullage_up = $row;
                                $ullage_down = $row;
                            }else{
                                if($pre_arr['val']<$ullage and $now_ullage>$ullage){
                                    $ullage_up = $pre_arr['row'];
                                    $ullage_down = $row;
                                }
                            }
                        }
                        $ullage_arr[] = array('val'=>$now_ullage,'row'=>$row);
                    }
                }
            }
        }*/

        /*
         * 判断极值
         */
        if ($draft_up == 0) {
            $first = reset($draft_arr);
            $last = end($draft_arr);
            if ($first['val'] > $draft) {
                $draft_up = $first['column'];
                $draft_down = $first['column'];
            }
            if ($last['val'] < $draft) {
                $draft_up = $last['column'];
                $draft_down = $last['column'];
            }
        }

        if ($ullage_up == 0) {
            $first = reset($ullage_arr);
            $last = end($ullage_arr);
            if ($first['val'] > $ullage) {
                $ullage_up = $first['row'];
                $ullage_down = $first['row'];
            }
            if ($last['val'] < $ullage) {
                $ullage_up = $last['row'];
                $ullage_down = $last['row'];
            }
        }
        /*        print_r($draft_arr);
                echo "<br>";
                print_r($ullage_arr);
                echo "<br>";
                print_r($draft_up);
                echo "<br>";
                print_r($draft_down);
                echo "<br>";
                print_r($ullage_up);
                echo "<br>";
                print_r($ullage_down);*/
//        echo (float)'-0.2m';
        $assign = array(
            'table' => $table_datas,
            'max_column' => $max_column,
            'max_row' => $max_row,
            'draft_up' => $draft_up,
            'draft_down' => $draft_down,
            'ullage_up' => $ullage_up,
            'ullage_down' => $ullage_down,
            'ullage' => $ullage,
            'draft' => $draft,
        );
        $this->assign($assign);
        $this->display();
    }

    public function seriali_ca_data()
    {
        $ullage = 5.442;
        $draft = 0.2;

//        $test_json = <<<test_json
//{"result":{"result_data":"{\"form_num\":1,\"forms\":[{\"footer\":[],\"header\":[{\"rect\":{\"top\":0,\"left\":0,\"width\":1080,\"height\":6},\"column\":[1],\"row\":[1],\"word\":\"\"}],\"body\":[{\"rect\":{\"height\":52,\"left\":107,\"top\":6,\"width\":106},\"column\":[1],\"row\":[1],\"word\":\"\u7a7a\u9ad8\"},{\"rect\":{\"height\":34,\"left\":107,\"top\":58,\"width\":106},\"column\":[1],\"row\":[2],\"word\":\"Ullage\"},{\"rect\":{\"height\":42,\"left\":107,\"top\":92,\"width\":106},\"column\":[1],\"row\":[3],\"word\":\"()\"},{\"rect\":{\"height\":47,\"left\":107,\"top\":134,\"width\":106},\"column\":[1],\"row\":[4],\"word\":\"0.200\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":181,\"width\":106},\"column\":[1],\"row\":[5],\"word\":\"0.210\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":211,\"width\":106},\"column\":[1],\"row\":[6],\"word\":\"0.220\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":243,\"width\":106},\"column\":[1],\"row\":[7],\"word\":\"0.230\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":273,\"width\":106},\"column\":[1],\"row\":[8],\"word\":\"0.240\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":305,\"width\":106},\"column\":[1],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":337,\"width\":106},\"column\":[1],\"row\":[10],\"word\":\"0.250\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":367,\"width\":106},\"column\":[1],\"row\":[11],\"word\":\"0.260\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":399,\"width\":106},\"column\":[1],\"row\":[12],\"word\":\"0.270\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":429,\"width\":106},\"column\":[1],\"row\":[13],\"word\":\"0.280\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":461,\"width\":106},\"column\":[1],\"row\":[14],\"word\":\"0.290\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":493,\"width\":106},\"column\":[1],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":523,\"width\":106},\"column\":[1],\"row\":[16],\"word\":\"0.300\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":555,\"width\":106},\"column\":[1],\"row\":[17],\"word\":\"0.400\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":587,\"width\":106},\"column\":[1],\"row\":[18],\"word\":\"0.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":617,\"width\":106},\"column\":[1],\"row\":[19],\"word\":\"0.600\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":649,\"width\":106},\"column\":[1],\"row\":[20],\"word\":\"0.700\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":678,\"width\":106},\"column\":[1],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":710,\"width\":106},\"column\":[1],\"row\":[22],\"word\":\"0.800\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":742,\"width\":106},\"column\":[1],\"row\":[23],\"word\":\"0.900\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":772,\"width\":106},\"column\":[1],\"row\":[24],\"word\":\"1.00\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":804,\"width\":106},\"column\":[1],\"row\":[25],\"word\":\"1.100\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":834,\"width\":106},\"column\":[1],\"row\":[26],\"word\":\"1.200\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":866,\"width\":106},\"column\":[1],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":896,\"width\":106},\"column\":[1],\"row\":[28],\"word\":\"1.300\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":928,\"width\":106},\"column\":[1],\"row\":[29],\"word\":\"1.400\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":958,\"width\":106},\"column\":[1],\"row\":[30],\"word\":\"1.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":990,\"width\":106},\"column\":[1],\"row\":[31],\"word\":\"2.000\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1022,\"width\":106},\"column\":[1],\"row\":[32],\"word\":\"2.500\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1052,\"width\":106},\"column\":[1],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1084,\"width\":106},\"column\":[1],\"row\":[34],\"word\":\"3.000\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1114,\"width\":106},\"column\":[1],\"row\":[35],\"word\":\"3.500\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1146,\"width\":106},\"column\":[1],\"row\":[36],\"word\":\"4.000\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1175,\"width\":106},\"column\":[1],\"row\":[37],\"word\":\"4.500\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1205,\"width\":106},\"column\":[1],\"row\":[38],\"word\":\"5.000\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1235,\"width\":106},\"column\":[1],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1267,\"width\":106},\"column\":[1],\"row\":[40],\"word\":\"5.890\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1297,\"width\":106},\"column\":[1],\"row\":[41],\"word\":\"5.900\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1327,\"width\":106},\"column\":[1],\"row\":[42],\"word\":\"5.910\"},{\"rect\":{\"height\":28,\"left\":107,\"top\":1359,\"width\":106},\"column\":[1],\"row\":[43],\"word\":\"5.920\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1387,\"width\":106},\"column\":[1],\"row\":[44],\"word\":\"5.930\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1419,\"width\":106},\"column\":[1],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1449,\"width\":106},\"column\":[1],\"row\":[46],\"word\":\"5.937\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1478,\"width\":106},\"column\":[1],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1510,\"width\":106},\"column\":[1],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1540,\"width\":106},\"column\":[1],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1572,\"width\":106},\"column\":[1],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1602,\"width\":106},\"column\":[1],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1634,\"width\":106},\"column\":[1],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1666,\"width\":106},\"column\":[1],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1698,\"width\":106},\"column\":[1],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":107,\"top\":1728,\"width\":106},\"column\":[1],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":107,\"top\":1756,\"width\":106},\"column\":[1],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":107,\"top\":1786,\"width\":106},\"column\":[1],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":107,\"top\":1815,\"width\":106},\"column\":[1],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":107,\"top\":1847,\"width\":106},\"column\":[1],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":107,\"top\":1884,\"width\":106},\"column\":[1],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":213,\"top\":6,\"width\":77},\"column\":[2],\"row\":[1],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":213,\"top\":58,\"width\":77},\"column\":[2],\"row\":[2],\"word\":\"-0.4m\"},{\"rect\":{\"height\":42,\"left\":213,\"top\":92,\"width\":77},\"column\":[2],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":213,\"top\":134,\"width\":77},\"column\":[2],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":181,\"width\":77},\"column\":[2],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":211,\"width\":77},\"column\":[2],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":243,\"width\":77},\"column\":[2],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":273,\"width\":77},\"column\":[2],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":305,\"width\":77},\"column\":[2],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":337,\"width\":77},\"column\":[2],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":367,\"width\":77},\"column\":[2],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":399,\"width\":77},\"column\":[2],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":429,\"width\":77},\"column\":[2],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":461,\"width\":77},\"column\":[2],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":493,\"width\":77},\"column\":[2],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":523,\"width\":77},\"column\":[2],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":555,\"width\":77},\"column\":[2],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":587,\"width\":77},\"column\":[2],\"row\":[18],\"word\":\"31\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":617,\"width\":77},\"column\":[2],\"row\":[19],\"word\":\"33\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":649,\"width\":77},\"column\":[2],\"row\":[20],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":678,\"width\":77},\"column\":[2],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":710,\"width\":77},\"column\":[2],\"row\":[22],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":742,\"width\":77},\"column\":[2],\"row\":[23],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":772,\"width\":77},\"column\":[2],\"row\":[24],\"word\":\"032\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":804,\"width\":77},\"column\":[2],\"row\":[25],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":834,\"width\":77},\"column\":[2],\"row\":[26],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":866,\"width\":77},\"column\":[2],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":896,\"width\":77},\"column\":[2],\"row\":[28],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":928,\"width\":77},\"column\":[2],\"row\":[29],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":958,\"width\":77},\"column\":[2],\"row\":[30],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":990,\"width\":77},\"column\":[2],\"row\":[31],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1022,\"width\":77},\"column\":[2],\"row\":[32],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1052,\"width\":77},\"column\":[2],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1084,\"width\":77},\"column\":[2],\"row\":[34],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1114,\"width\":77},\"column\":[2],\"row\":[35],\"word\":\"32\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1146,\"width\":77},\"column\":[2],\"row\":[36],\"word\":\"32\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1175,\"width\":77},\"column\":[2],\"row\":[37],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1205,\"width\":77},\"column\":[2],\"row\":[38],\"word\":\"32\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1235,\"width\":77},\"column\":[2],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1267,\"width\":77},\"column\":[2],\"row\":[40],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1297,\"width\":77},\"column\":[2],\"row\":[41],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1327,\"width\":77},\"column\":[2],\"row\":[42],\"word\":\"34\"},{\"rect\":{\"height\":28,\"left\":213,\"top\":1359,\"width\":77},\"column\":[2],\"row\":[43],\"word\":\"33\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1387,\"width\":77},\"column\":[2],\"row\":[44],\"word\":\"34\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1419,\"width\":77},\"column\":[2],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1449,\"width\":77},\"column\":[2],\"row\":[46],\"word\":\"35\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1478,\"width\":77},\"column\":[2],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1510,\"width\":77},\"column\":[2],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1540,\"width\":77},\"column\":[2],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1572,\"width\":77},\"column\":[2],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1602,\"width\":77},\"column\":[2],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1634,\"width\":77},\"column\":[2],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1666,\"width\":77},\"column\":[2],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1698,\"width\":77},\"column\":[2],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":213,\"top\":1728,\"width\":77},\"column\":[2],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":213,\"top\":1756,\"width\":77},\"column\":[2],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":213,\"top\":1786,\"width\":77},\"column\":[2],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":213,\"top\":1815,\"width\":77},\"column\":[2],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":213,\"top\":1847,\"width\":77},\"column\":[2],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":213,\"top\":1884,\"width\":77},\"column\":[2],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":290,\"top\":6,\"width\":79},\"column\":[3],\"row\":[1],\"word\":\"\u7eb5\u503e\u503c\"},{\"rect\":{\"height\":34,\"left\":290,\"top\":58,\"width\":79},\"column\":[3],\"row\":[2],\"word\":\"-0.2m\"},{\"rect\":{\"height\":42,\"left\":290,\"top\":92,\"width\":79},\"column\":[3],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":290,\"top\":134,\"width\":79},\"column\":[3],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":181,\"width\":79},\"column\":[3],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":211,\"width\":79},\"column\":[3],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":243,\"width\":79},\"column\":[3],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":273,\"width\":79},\"column\":[3],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":305,\"width\":79},\"column\":[3],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":337,\"width\":79},\"column\":[3],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":367,\"width\":79},\"column\":[3],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":399,\"width\":79},\"column\":[3],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":429,\"width\":79},\"column\":[3],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":461,\"width\":79},\"column\":[3],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":493,\"width\":79},\"column\":[3],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":523,\"width\":79},\"column\":[3],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":555,\"width\":79},\"column\":[3],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":587,\"width\":79},\"column\":[3],\"row\":[18],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":617,\"width\":79},\"column\":[3],\"row\":[19],\"word\":\"17\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":649,\"width\":79},\"column\":[3],\"row\":[20],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":678,\"width\":79},\"column\":[3],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":710,\"width\":79},\"column\":[3],\"row\":[22],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":742,\"width\":79},\"column\":[3],\"row\":[23],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":772,\"width\":79},\"column\":[3],\"row\":[24],\"word\":\"616\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":804,\"width\":79},\"column\":[3],\"row\":[25],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":834,\"width\":79},\"column\":[3],\"row\":[26],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":866,\"width\":79},\"column\":[3],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":896,\"width\":79},\"column\":[3],\"row\":[28],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":928,\"width\":79},\"column\":[3],\"row\":[29],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":958,\"width\":79},\"column\":[3],\"row\":[30],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":990,\"width\":79},\"column\":[3],\"row\":[31],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1022,\"width\":79},\"column\":[3],\"row\":[32],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1052,\"width\":79},\"column\":[3],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1084,\"width\":79},\"column\":[3],\"row\":[34],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1114,\"width\":79},\"column\":[3],\"row\":[35],\"word\":\"16\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1146,\"width\":79},\"column\":[3],\"row\":[36],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1175,\"width\":79},\"column\":[3],\"row\":[37],\"word\":\"16\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1205,\"width\":79},\"column\":[3],\"row\":[38],\"word\":\"16\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1235,\"width\":79},\"column\":[3],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1267,\"width\":79},\"column\":[3],\"row\":[40],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1297,\"width\":79},\"column\":[3],\"row\":[41],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1327,\"width\":79},\"column\":[3],\"row\":[42],\"word\":\"17\"},{\"rect\":{\"height\":28,\"left\":290,\"top\":1359,\"width\":79},\"column\":[3],\"row\":[43],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1387,\"width\":79},\"column\":[3],\"row\":[44],\"word\":\"18\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1419,\"width\":79},\"column\":[3],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1449,\"width\":79},\"column\":[3],\"row\":[46],\"word\":\"18\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1478,\"width\":79},\"column\":[3],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1510,\"width\":79},\"column\":[3],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1540,\"width\":79},\"column\":[3],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1572,\"width\":79},\"column\":[3],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1602,\"width\":79},\"column\":[3],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1634,\"width\":79},\"column\":[3],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1666,\"width\":79},\"column\":[3],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1698,\"width\":79},\"column\":[3],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":290,\"top\":1728,\"width\":79},\"column\":[3],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":290,\"top\":1756,\"width\":79},\"column\":[3],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":290,\"top\":1786,\"width\":79},\"column\":[3],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":290,\"top\":1815,\"width\":79},\"column\":[3],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":290,\"top\":1847,\"width\":79},\"column\":[3],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":290,\"top\":1884,\"width\":79},\"column\":[3],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":369,\"top\":6,\"width\":79},\"column\":[4],\"row\":[1],\"word\":\"(\u8249\u5403\"},{\"rect\":{\"height\":34,\"left\":369,\"top\":58,\"width\":79},\"column\":[4],\"row\":[2],\"word\":\"0.0m\"},{\"rect\":{\"height\":42,\"left\":369,\"top\":92,\"width\":79},\"column\":[4],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":369,\"top\":134,\"width\":79},\"column\":[4],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":181,\"width\":79},\"column\":[4],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":211,\"width\":79},\"column\":[4],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":243,\"width\":79},\"column\":[4],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":273,\"width\":79},\"column\":[4],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":305,\"width\":79},\"column\":[4],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":337,\"width\":79},\"column\":[4],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":367,\"width\":79},\"column\":[4],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":399,\"width\":79},\"column\":[4],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":429,\"width\":79},\"column\":[4],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":461,\"width\":79},\"column\":[4],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":493,\"width\":79},\"column\":[4],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":523,\"width\":79},\"column\":[4],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":555,\"width\":79},\"column\":[4],\"row\":[17],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":587,\"width\":79},\"column\":[4],\"row\":[18],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":617,\"width\":79},\"column\":[4],\"row\":[19],\"word\":\"0\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":649,\"width\":79},\"column\":[4],\"row\":[20],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":678,\"width\":79},\"column\":[4],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":710,\"width\":79},\"column\":[4],\"row\":[22],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":742,\"width\":79},\"column\":[4],\"row\":[23],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":772,\"width\":79},\"column\":[4],\"row\":[24],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":804,\"width\":79},\"column\":[4],\"row\":[25],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":834,\"width\":79},\"column\":[4],\"row\":[26],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":866,\"width\":79},\"column\":[4],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":896,\"width\":79},\"column\":[4],\"row\":[28],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":928,\"width\":79},\"column\":[4],\"row\":[29],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":958,\"width\":79},\"column\":[4],\"row\":[30],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":990,\"width\":79},\"column\":[4],\"row\":[31],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1022,\"width\":79},\"column\":[4],\"row\":[32],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1052,\"width\":79},\"column\":[4],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1084,\"width\":79},\"column\":[4],\"row\":[34],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1114,\"width\":79},\"column\":[4],\"row\":[35],\"word\":\"0\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1146,\"width\":79},\"column\":[4],\"row\":[36],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1175,\"width\":79},\"column\":[4],\"row\":[37],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1205,\"width\":79},\"column\":[4],\"row\":[38],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1235,\"width\":79},\"column\":[4],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1267,\"width\":79},\"column\":[4],\"row\":[40],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1297,\"width\":79},\"column\":[4],\"row\":[41],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1327,\"width\":79},\"column\":[4],\"row\":[42],\"word\":\"0\"},{\"rect\":{\"height\":28,\"left\":369,\"top\":1359,\"width\":79},\"column\":[4],\"row\":[43],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1387,\"width\":79},\"column\":[4],\"row\":[44],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1419,\"width\":79},\"column\":[4],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1449,\"width\":79},\"column\":[4],\"row\":[46],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1478,\"width\":79},\"column\":[4],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1510,\"width\":79},\"column\":[4],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1540,\"width\":79},\"column\":[4],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1572,\"width\":79},\"column\":[4],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1602,\"width\":79},\"column\":[4],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1634,\"width\":79},\"column\":[4],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1666,\"width\":79},\"column\":[4],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1698,\"width\":79},\"column\":[4],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":369,\"top\":1728,\"width\":79},\"column\":[4],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":369,\"top\":1756,\"width\":79},\"column\":[4],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":369,\"top\":1786,\"width\":79},\"column\":[4],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":369,\"top\":1815,\"width\":79},\"column\":[4],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":369,\"top\":1847,\"width\":79},\"column\":[4],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":369,\"top\":1884,\"width\":79},\"column\":[4],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":448,\"top\":6,\"width\":77},\"column\":[5],\"row\":[1],\"word\":\"\u6c34\u4e00\u824f\"},{\"rect\":{\"height\":34,\"left\":448,\"top\":58,\"width\":77},\"column\":[5],\"row\":[2],\"word\":\"0.2m\"},{\"rect\":{\"height\":42,\"left\":448,\"top\":92,\"width\":77},\"column\":[5],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":448,\"top\":134,\"width\":77},\"column\":[5],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":181,\"width\":77},\"column\":[5],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":211,\"width\":77},\"column\":[5],\"row\":[6],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":243,\"width\":77},\"column\":[5],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":273,\"width\":77},\"column\":[5],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":305,\"width\":77},\"column\":[5],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":337,\"width\":77},\"column\":[5],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":367,\"width\":77},\"column\":[5],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":399,\"width\":77},\"column\":[5],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":429,\"width\":77},\"column\":[5],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":461,\"width\":77},\"column\":[5],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":493,\"width\":77},\"column\":[5],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":523,\"width\":77},\"column\":[5],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":555,\"width\":77},\"column\":[5],\"row\":[17],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":587,\"width\":77},\"column\":[5],\"row\":[18],\"word\":\"-19\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":617,\"width\":77},\"column\":[5],\"row\":[19],\"word\":\"-17\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":649,\"width\":77},\"column\":[5],\"row\":[20],\"word\":\"-18\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":678,\"width\":77},\"column\":[5],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":710,\"width\":77},\"column\":[5],\"row\":[22],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":742,\"width\":77},\"column\":[5],\"row\":[23],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":772,\"width\":77},\"column\":[5],\"row\":[24],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":804,\"width\":77},\"column\":[5],\"row\":[25],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":834,\"width\":77},\"column\":[5],\"row\":[26],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":866,\"width\":77},\"column\":[5],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":896,\"width\":77},\"column\":[5],\"row\":[28],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":928,\"width\":77},\"column\":[5],\"row\":[29],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":958,\"width\":77},\"column\":[5],\"row\":[30],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":990,\"width\":77},\"column\":[5],\"row\":[31],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1022,\"width\":77},\"column\":[5],\"row\":[32],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1052,\"width\":77},\"column\":[5],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1084,\"width\":77},\"column\":[5],\"row\":[34],\"word\":\"-17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1114,\"width\":77},\"column\":[5],\"row\":[35],\"word\":\"-17\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1146,\"width\":77},\"column\":[5],\"row\":[36],\"word\":\"-17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1175,\"width\":77},\"column\":[5],\"row\":[37],\"word\":\"17\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1205,\"width\":77},\"column\":[5],\"row\":[38],\"word\":\"-16\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1235,\"width\":77},\"column\":[5],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1267,\"width\":77},\"column\":[5],\"row\":[40],\"word\":\"-18\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1297,\"width\":77},\"column\":[5],\"row\":[41],\"word\":\"-18\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1327,\"width\":77},\"column\":[5],\"row\":[42],\"word\":\"17\"},{\"rect\":{\"height\":28,\"left\":448,\"top\":1359,\"width\":77},\"column\":[5],\"row\":[43],\"word\":\"17\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1387,\"width\":77},\"column\":[5],\"row\":[44],\"word\":\"14\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1419,\"width\":77},\"column\":[5],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1449,\"width\":77},\"column\":[5],\"row\":[46],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1478,\"width\":77},\"column\":[5],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1510,\"width\":77},\"column\":[5],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1540,\"width\":77},\"column\":[5],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1572,\"width\":77},\"column\":[5],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1602,\"width\":77},\"column\":[5],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1634,\"width\":77},\"column\":[5],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1666,\"width\":77},\"column\":[5],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1698,\"width\":77},\"column\":[5],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":448,\"top\":1728,\"width\":77},\"column\":[5],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":448,\"top\":1756,\"width\":77},\"column\":[5],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":448,\"top\":1786,\"width\":77},\"column\":[5],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":448,\"top\":1815,\"width\":77},\"column\":[5],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":448,\"top\":1847,\"width\":77},\"column\":[5],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":448,\"top\":1884,\"width\":77},\"column\":[5],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":525,\"top\":6,\"width\":77},\"column\":[6],\"row\":[1],\"word\":\"\u6c34)\"},{\"rect\":{\"height\":34,\"left\":525,\"top\":58,\"width\":77},\"column\":[6],\"row\":[2],\"word\":\"0.4m\"},{\"rect\":{\"height\":42,\"left\":525,\"top\":92,\"width\":77},\"column\":[6],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":525,\"top\":134,\"width\":77},\"column\":[6],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":181,\"width\":77},\"column\":[6],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":211,\"width\":77},\"column\":[6],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":243,\"width\":77},\"column\":[6],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":273,\"width\":77},\"column\":[6],\"row\":[8],\"word\":\"00\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":305,\"width\":77},\"column\":[6],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":337,\"width\":77},\"column\":[6],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":367,\"width\":77},\"column\":[6],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":399,\"width\":77},\"column\":[6],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":429,\"width\":77},\"column\":[6],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":461,\"width\":77},\"column\":[6],\"row\":[14],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":493,\"width\":77},\"column\":[6],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":523,\"width\":77},\"column\":[6],\"row\":[16],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":555,\"width\":77},\"column\":[6],\"row\":[17],\"word\":\"-38\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":587,\"width\":77},\"column\":[6],\"row\":[18],\"word\":\"-37\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":617,\"width\":77},\"column\":[6],\"row\":[19],\"word\":\"36\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":649,\"width\":77},\"column\":[6],\"row\":[20],\"word\":\"-34\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":678,\"width\":77},\"column\":[6],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":710,\"width\":77},\"column\":[6],\"row\":[22],\"word\":\"-34\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":742,\"width\":77},\"column\":[6],\"row\":[23],\"word\":\"34\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":772,\"width\":77},\"column\":[6],\"row\":[24],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":804,\"width\":77},\"column\":[6],\"row\":[25],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":834,\"width\":77},\"column\":[6],\"row\":[26],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":866,\"width\":77},\"column\":[6],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":896,\"width\":77},\"column\":[6],\"row\":[28],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":928,\"width\":77},\"column\":[6],\"row\":[29],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":958,\"width\":77},\"column\":[6],\"row\":[30],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":990,\"width\":77},\"column\":[6],\"row\":[31],\"word\":\"-33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1022,\"width\":77},\"column\":[6],\"row\":[32],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1052,\"width\":77},\"column\":[6],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1084,\"width\":77},\"column\":[6],\"row\":[34],\"word\":\"-33\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1114,\"width\":77},\"column\":[6],\"row\":[35],\"word\":\"-33\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1146,\"width\":77},\"column\":[6],\"row\":[36],\"word\":\"33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1175,\"width\":77},\"column\":[6],\"row\":[37],\"word\":\"33\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1205,\"width\":77},\"column\":[6],\"row\":[38],\"word\":\"-32\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1235,\"width\":77},\"column\":[6],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1267,\"width\":77},\"column\":[6],\"row\":[40],\"word\":\"-32\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1297,\"width\":77},\"column\":[6],\"row\":[41],\"word\":\"30\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1327,\"width\":77},\"column\":[6],\"row\":[42],\"word\":\"-28\"},{\"rect\":{\"height\":28,\"left\":525,\"top\":1359,\"width\":77},\"column\":[6],\"row\":[43],\"word\":\"24\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1387,\"width\":77},\"column\":[6],\"row\":[44],\"word\":\"1\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1419,\"width\":77},\"column\":[6],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1449,\"width\":77},\"column\":[6],\"row\":[46],\"word\":\"11\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1478,\"width\":77},\"column\":[6],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1510,\"width\":77},\"column\":[6],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1540,\"width\":77},\"column\":[6],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1572,\"width\":77},\"column\":[6],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1602,\"width\":77},\"column\":[6],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1634,\"width\":77},\"column\":[6],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1666,\"width\":77},\"column\":[6],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1698,\"width\":77},\"column\":[6],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":525,\"top\":1728,\"width\":77},\"column\":[6],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":525,\"top\":1756,\"width\":77},\"column\":[6],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":525,\"top\":1786,\"width\":77},\"column\":[6],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":525,\"top\":1815,\"width\":77},\"column\":[6],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":525,\"top\":1847,\"width\":77},\"column\":[6],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":525,\"top\":1884,\"width\":77},\"column\":[6],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":602,\"top\":6,\"width\":79},\"column\":[7],\"row\":[1],\"word\":\"im[draf\"},{\"rect\":{\"height\":34,\"left\":602,\"top\":58,\"width\":79},\"column\":[7],\"row\":[2],\"word\":\"0.6m\"},{\"rect\":{\"height\":42,\"left\":602,\"top\":92,\"width\":79},\"column\":[7],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":602,\"top\":134,\"width\":79},\"column\":[7],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":181,\"width\":79},\"column\":[7],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":211,\"width\":79},\"column\":[7],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":243,\"width\":79},\"column\":[7],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":273,\"width\":79},\"column\":[7],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":305,\"width\":79},\"column\":[7],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":337,\"width\":79},\"column\":[7],\"row\":[10],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":367,\"width\":79},\"column\":[7],\"row\":[11],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":399,\"width\":79},\"column\":[7],\"row\":[12],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":429,\"width\":79},\"column\":[7],\"row\":[13],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":461,\"width\":79},\"column\":[7],\"row\":[14],\"word\":\"-28\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":493,\"width\":79},\"column\":[7],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":523,\"width\":79},\"column\":[7],\"row\":[16],\"word\":\"-9\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":555,\"width\":79},\"column\":[7],\"row\":[17],\"word\":\"-58\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":587,\"width\":79},\"column\":[7],\"row\":[18],\"word\":\"-56\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":617,\"width\":79},\"column\":[7],\"row\":[19],\"word\":\"-52\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":649,\"width\":79},\"column\":[7],\"row\":[20],\"word\":\"-53\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":678,\"width\":79},\"column\":[7],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":710,\"width\":79},\"column\":[7],\"row\":[22],\"word\":\"-51\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":742,\"width\":79},\"column\":[7],\"row\":[23],\"word\":\"-52\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":772,\"width\":79},\"column\":[7],\"row\":[24],\"word\":\"-49\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":804,\"width\":79},\"column\":[7],\"row\":[25],\"word\":\"-49\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":834,\"width\":79},\"column\":[7],\"row\":[26],\"word\":\"-49\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":866,\"width\":79},\"column\":[7],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":896,\"width\":79},\"column\":[7],\"row\":[28],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":928,\"width\":79},\"column\":[7],\"row\":[29],\"word\":\"49\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":958,\"width\":79},\"column\":[7],\"row\":[30],\"word\":\"-49\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":990,\"width\":79},\"column\":[7],\"row\":[31],\"word\":\"49\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1022,\"width\":79},\"column\":[7],\"row\":[32],\"word\":\"48\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1052,\"width\":79},\"column\":[7],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1084,\"width\":79},\"column\":[7],\"row\":[34],\"word\":\"-48\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1114,\"width\":79},\"column\":[7],\"row\":[35],\"word\":\"48\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":1146,\"width\":79},\"column\":[7],\"row\":[36],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1175,\"width\":79},\"column\":[7],\"row\":[37],\"word\":\"-47\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1205,\"width\":79},\"column\":[7],\"row\":[38],\"word\":\"-7\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1235,\"width\":79},\"column\":[7],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1267,\"width\":79},\"column\":[7],\"row\":[40],\"word\":\"-4\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1297,\"width\":79},\"column\":[7],\"row\":[41],\"word\":\"7\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1327,\"width\":79},\"column\":[7],\"row\":[42],\"word\":\"-2\"},{\"rect\":{\"height\":28,\"left\":602,\"top\":1359,\"width\":79},\"column\":[7],\"row\":[43],\"word\":\"27\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1387,\"width\":79},\"column\":[7],\"row\":[44],\"word\":\"-19\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1419,\"width\":79},\"column\":[7],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":1449,\"width\":79},\"column\":[7],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1478,\"width\":79},\"column\":[7],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1510,\"width\":79},\"column\":[7],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1540,\"width\":79},\"column\":[7],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1572,\"width\":79},\"column\":[7],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1602,\"width\":79},\"column\":[7],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1634,\"width\":79},\"column\":[7],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1666,\"width\":79},\"column\":[7],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1698,\"width\":79},\"column\":[7],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":602,\"top\":1728,\"width\":79},\"column\":[7],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":602,\"top\":1756,\"width\":79},\"column\":[7],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":602,\"top\":1786,\"width\":79},\"column\":[7],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":602,\"top\":1815,\"width\":79},\"column\":[7],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":602,\"top\":1847,\"width\":79},\"column\":[7],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":602,\"top\":1884,\"width\":79},\"column\":[7],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":681,\"top\":6,\"width\":76},\"column\":[8],\"row\":[1],\"word\":\"taft\"},{\"rect\":{\"height\":34,\"left\":681,\"top\":58,\"width\":76},\"column\":[8],\"row\":[2],\"word\":\"0.8m\"},{\"rect\":{\"height\":42,\"left\":681,\"top\":92,\"width\":76},\"column\":[8],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":681,\"top\":134,\"width\":76},\"column\":[8],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":181,\"width\":76},\"column\":[8],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":211,\"width\":76},\"column\":[8],\"row\":[6],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":243,\"width\":76},\"column\":[8],\"row\":[7],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":273,\"width\":76},\"column\":[8],\"row\":[8],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":305,\"width\":76},\"column\":[8],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":337,\"width\":76},\"column\":[8],\"row\":[10],\"word\":\"-40\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":367,\"width\":76},\"column\":[8],\"row\":[11],\"word\":\"-133\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":399,\"width\":76},\"column\":[8],\"row\":[12],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":429,\"width\":76},\"column\":[8],\"row\":[13],\"word\":\"-120\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":461,\"width\":76},\"column\":[8],\"row\":[14],\"word\":\"-116\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":493,\"width\":76},\"column\":[8],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":523,\"width\":76},\"column\":[8],\"row\":[16],\"word\":\"-112\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":555,\"width\":76},\"column\":[8],\"row\":[17],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":587,\"width\":76},\"column\":[8],\"row\":[18],\"word\":\"-74\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":617,\"width\":76},\"column\":[8],\"row\":[19],\"word\":\"-71\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":649,\"width\":76},\"column\":[8],\"row\":[20],\"word\":\"69\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":678,\"width\":76},\"column\":[8],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":710,\"width\":76},\"column\":[8],\"row\":[22],\"word\":\"-68\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":742,\"width\":76},\"column\":[8],\"row\":[23],\"word\":\"-68\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":772,\"width\":76},\"column\":[8],\"row\":[24],\"word\":\"-65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":804,\"width\":76},\"column\":[8],\"row\":[25],\"word\":\"-65\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":834,\"width\":76},\"column\":[8],\"row\":[26],\"word\":\"65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":866,\"width\":76},\"column\":[8],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":896,\"width\":76},\"column\":[8],\"row\":[28],\"word\":\"-65\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":928,\"width\":76},\"column\":[8],\"row\":[29],\"word\":\"-65\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":958,\"width\":76},\"column\":[8],\"row\":[30],\"word\":\"-64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":990,\"width\":76},\"column\":[8],\"row\":[31],\"word\":\"-64\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1022,\"width\":76},\"column\":[8],\"row\":[32],\"word\":\"-64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1052,\"width\":76},\"column\":[8],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1084,\"width\":76},\"column\":[8],\"row\":[34],\"word\":\"64\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1114,\"width\":76},\"column\":[8],\"row\":[35],\"word\":\"64\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1146,\"width\":76},\"column\":[8],\"row\":[36],\"word\":\"-63\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1175,\"width\":76},\"column\":[8],\"row\":[37],\"word\":\"-63\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1205,\"width\":76},\"column\":[8],\"row\":[38],\"word\":\"-63\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1235,\"width\":76},\"column\":[8],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1267,\"width\":76},\"column\":[8],\"row\":[40],\"word\":\"-46\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1297,\"width\":76},\"column\":[8],\"row\":[41],\"word\":\"-41\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1327,\"width\":76},\"column\":[8],\"row\":[42],\"word\":\"-35\"},{\"rect\":{\"height\":28,\"left\":681,\"top\":1359,\"width\":76},\"column\":[8],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1387,\"width\":76},\"column\":[8],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1419,\"width\":76},\"column\":[8],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1449,\"width\":76},\"column\":[8],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1478,\"width\":76},\"column\":[8],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1510,\"width\":76},\"column\":[8],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1540,\"width\":76},\"column\":[8],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1572,\"width\":76},\"column\":[8],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1602,\"width\":76},\"column\":[8],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1634,\"width\":76},\"column\":[8],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1666,\"width\":76},\"column\":[8],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1698,\"width\":76},\"column\":[8],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":681,\"top\":1728,\"width\":76},\"column\":[8],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":681,\"top\":1756,\"width\":76},\"column\":[8],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":681,\"top\":1786,\"width\":76},\"column\":[8],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":681,\"top\":1815,\"width\":76},\"column\":[8],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":681,\"top\":1847,\"width\":76},\"column\":[8],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":681,\"top\":1884,\"width\":76},\"column\":[8],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":757,\"top\":6,\"width\":79},\"column\":[9],\"row\":[1],\"word\":\"tern)\"},{\"rect\":{\"height\":34,\"left\":757,\"top\":58,\"width\":79},\"column\":[9],\"row\":[2],\"word\":\"1.0m\"},{\"rect\":{\"height\":42,\"left\":757,\"top\":92,\"width\":79},\"column\":[9],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":757,\"top\":134,\"width\":79},\"column\":[9],\"row\":[4],\"word\":\"0\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":181,\"width\":79},\"column\":[9],\"row\":[5],\"word\":\"0\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":211,\"width\":79},\"column\":[9],\"row\":[6],\"word\":\"-50\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":243,\"width\":79},\"column\":[9],\"row\":[7],\"word\":\"-165\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":273,\"width\":79},\"column\":[9],\"row\":[8],\"word\":\"-160\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":305,\"width\":79},\"column\":[9],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":337,\"width\":79},\"column\":[9],\"row\":[10],\"word\":\"-154\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":367,\"width\":79},\"column\":[9],\"row\":[11],\"word\":\"-148\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":399,\"width\":79},\"column\":[9],\"row\":[12],\"word\":\"-142\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":429,\"width\":79},\"column\":[9],\"row\":[13],\"word\":\"-138\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":461,\"width\":79},\"column\":[9],\"row\":[14],\"word\":\"-133\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":493,\"width\":79},\"column\":[9],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":523,\"width\":79},\"column\":[9],\"row\":[16],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":555,\"width\":79},\"column\":[9],\"row\":[17],\"word\":\"-99\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":587,\"width\":79},\"column\":[9],\"row\":[18],\"word\":\"-92\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":617,\"width\":79},\"column\":[9],\"row\":[19],\"word\":\"-89\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":649,\"width\":79},\"column\":[9],\"row\":[20],\"word\":\"-87\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":678,\"width\":79},\"column\":[9],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":710,\"width\":79},\"column\":[9],\"row\":[22],\"word\":\"-85\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":742,\"width\":79},\"column\":[9],\"row\":[23],\"word\":\"-83\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":772,\"width\":79},\"column\":[9],\"row\":[24],\"word\":\"-80\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":804,\"width\":79},\"column\":[9],\"row\":[25],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":834,\"width\":79},\"column\":[9],\"row\":[26],\"word\":\"-80\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":866,\"width\":79},\"column\":[9],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":896,\"width\":79},\"column\":[9],\"row\":[28],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":928,\"width\":79},\"column\":[9],\"row\":[29],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":958,\"width\":79},\"column\":[9],\"row\":[30],\"word\":\"-80\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":990,\"width\":79},\"column\":[9],\"row\":[31],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1022,\"width\":79},\"column\":[9],\"row\":[32],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1052,\"width\":79},\"column\":[9],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1084,\"width\":79},\"column\":[9],\"row\":[34],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1114,\"width\":79},\"column\":[9],\"row\":[35],\"word\":\"-79\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1146,\"width\":79},\"column\":[9],\"row\":[36],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1175,\"width\":79},\"column\":[9],\"row\":[37],\"word\":\"-79\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1205,\"width\":79},\"column\":[9],\"row\":[38],\"word\":\"-79\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1235,\"width\":79},\"column\":[9],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1267,\"width\":79},\"column\":[9],\"row\":[40],\"word\":\"-50\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1297,\"width\":79},\"column\":[9],\"row\":[41],\"word\":\"-44\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1327,\"width\":79},\"column\":[9],\"row\":[42],\"word\":\"-36\"},{\"rect\":{\"height\":28,\"left\":757,\"top\":1359,\"width\":79},\"column\":[9],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1387,\"width\":79},\"column\":[9],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1419,\"width\":79},\"column\":[9],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1449,\"width\":79},\"column\":[9],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1478,\"width\":79},\"column\":[9],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1510,\"width\":79},\"column\":[9],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1540,\"width\":79},\"column\":[9],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1572,\"width\":79},\"column\":[9],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1602,\"width\":79},\"column\":[9],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1634,\"width\":79},\"column\":[9],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1666,\"width\":79},\"column\":[9],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1698,\"width\":79},\"column\":[9],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":757,\"top\":1728,\"width\":79},\"column\":[9],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":757,\"top\":1756,\"width\":79},\"column\":[9],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":757,\"top\":1786,\"width\":79},\"column\":[9],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":757,\"top\":1815,\"width\":79},\"column\":[9],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":757,\"top\":1847,\"width\":79},\"column\":[9],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":757,\"top\":1884,\"width\":79},\"column\":[9],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":836,\"top\":6,\"width\":77},\"column\":[10],\"row\":[1],\"word\":\"draft\"},{\"rect\":{\"height\":34,\"left\":836,\"top\":58,\"width\":77},\"column\":[10],\"row\":[2],\"word\":\"1.2m\"},{\"rect\":{\"height\":42,\"left\":836,\"top\":92,\"width\":77},\"column\":[10],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":836,\"top\":134,\"width\":77},\"column\":[10],\"row\":[4],\"word\":\"-199\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":181,\"width\":77},\"column\":[10],\"row\":[5],\"word\":\"-192\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":211,\"width\":77},\"column\":[10],\"row\":[6],\"word\":\"-186\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":243,\"width\":77},\"column\":[10],\"row\":[7],\"word\":\"-180\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":273,\"width\":77},\"column\":[10],\"row\":[8],\"word\":\"-174\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":305,\"width\":77},\"column\":[10],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":337,\"width\":77},\"column\":[10],\"row\":[10],\"word\":\"-168\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":367,\"width\":77},\"column\":[10],\"row\":[11],\"word\":\"-162\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":399,\"width\":77},\"column\":[10],\"row\":[12],\"word\":\"-158\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":429,\"width\":77},\"column\":[10],\"row\":[13],\"word\":\"-153\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":461,\"width\":77},\"column\":[10],\"row\":[14],\"word\":\"-150\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":493,\"width\":77},\"column\":[10],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":523,\"width\":77},\"column\":[10],\"row\":[16],\"word\":\"-145\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":555,\"width\":77},\"column\":[10],\"row\":[17],\"word\":\"-119\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":587,\"width\":77},\"column\":[10],\"row\":[18],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":617,\"width\":77},\"column\":[10],\"row\":[19],\"word\":\"-106\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":649,\"width\":77},\"column\":[10],\"row\":[20],\"word\":\"-104\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":678,\"width\":77},\"column\":[10],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":710,\"width\":77},\"column\":[10],\"row\":[22],\"word\":\"-102\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":742,\"width\":77},\"column\":[10],\"row\":[23],\"word\":\"-99\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":772,\"width\":77},\"column\":[10],\"row\":[24],\"word\":\"-96\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":804,\"width\":77},\"column\":[10],\"row\":[25],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":834,\"width\":77},\"column\":[10],\"row\":[26],\"word\":\"-96\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":866,\"width\":77},\"column\":[10],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":896,\"width\":77},\"column\":[10],\"row\":[28],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":928,\"width\":77},\"column\":[10],\"row\":[29],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":958,\"width\":77},\"column\":[10],\"row\":[30],\"word\":\"-96\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":990,\"width\":77},\"column\":[10],\"row\":[31],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1022,\"width\":77},\"column\":[10],\"row\":[32],\"word\":\"-95\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1052,\"width\":77},\"column\":[10],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1084,\"width\":77},\"column\":[10],\"row\":[34],\"word\":\"-95-\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1114,\"width\":77},\"column\":[10],\"row\":[35],\"word\":\"-95\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1146,\"width\":77},\"column\":[10],\"row\":[36],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1175,\"width\":77},\"column\":[10],\"row\":[37],\"word\":\"-95\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1205,\"width\":77},\"column\":[10],\"row\":[38],\"word\":\"-96\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1235,\"width\":77},\"column\":[10],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1267,\"width\":77},\"column\":[10],\"row\":[40],\"word\":\"-52\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1297,\"width\":77},\"column\":[10],\"row\":[41],\"word\":\"-45\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1327,\"width\":77},\"column\":[10],\"row\":[42],\"word\":\"-37\"},{\"rect\":{\"height\":28,\"left\":836,\"top\":1359,\"width\":77},\"column\":[10],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1387,\"width\":77},\"column\":[10],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1419,\"width\":77},\"column\":[10],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1449,\"width\":77},\"column\":[10],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1478,\"width\":77},\"column\":[10],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1510,\"width\":77},\"column\":[10],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1540,\"width\":77},\"column\":[10],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1572,\"width\":77},\"column\":[10],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1602,\"width\":77},\"column\":[10],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1634,\"width\":77},\"column\":[10],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1666,\"width\":77},\"column\":[10],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1698,\"width\":77},\"column\":[10],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":836,\"top\":1728,\"width\":77},\"column\":[10],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":836,\"top\":1756,\"width\":77},\"column\":[10],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":836,\"top\":1786,\"width\":77},\"column\":[10],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":836,\"top\":1815,\"width\":77},\"column\":[10],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":836,\"top\":1847,\"width\":77},\"column\":[10],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":836,\"top\":1884,\"width\":77},\"column\":[10],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":913,\"top\":6,\"width\":81},\"column\":[11],\"row\":[1],\"word\":\"orwar(\"},{\"rect\":{\"height\":34,\"left\":913,\"top\":58,\"width\":81},\"column\":[11],\"row\":[2],\"word\":\"1.4m\"},{\"rect\":{\"height\":42,\"left\":913,\"top\":92,\"width\":81},\"column\":[11],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":913,\"top\":134,\"width\":81},\"column\":[11],\"row\":[4],\"word\":\"-211\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":181,\"width\":81},\"column\":[11],\"row\":[5],\"word\":\"-204\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":211,\"width\":81},\"column\":[11],\"row\":[6],\"word\":\"-199\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":243,\"width\":81},\"column\":[11],\"row\":[7],\"word\":\"-193\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":273,\"width\":81},\"column\":[11],\"row\":[8],\"word\":\"-188\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":305,\"width\":81},\"column\":[11],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":337,\"width\":81},\"column\":[11],\"row\":[10],\"word\":\"-184\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":367,\"width\":81},\"column\":[11],\"row\":[11],\"word\":\"-178\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":399,\"width\":81},\"column\":[11],\"row\":[12],\"word\":\"-174\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":429,\"width\":81},\"column\":[11],\"row\":[13],\"word\":\"-171\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":461,\"width\":81},\"column\":[11],\"row\":[14],\"word\":\"-167\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":493,\"width\":81},\"column\":[11],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":523,\"width\":81},\"column\":[11],\"row\":[16],\"word\":\"-163\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":555,\"width\":81},\"column\":[11],\"row\":[17],\"word\":\"-139\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":587,\"width\":81},\"column\":[11],\"row\":[18],\"word\":\"-130\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":617,\"width\":81},\"column\":[11],\"row\":[19],\"word\":\"-124\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":649,\"width\":81},\"column\":[11],\"row\":[20],\"word\":\"-121\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":678,\"width\":81},\"column\":[11],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":710,\"width\":81},\"column\":[11],\"row\":[22],\"word\":\"-119\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":742,\"width\":81},\"column\":[11],\"row\":[23],\"word\":\"-115\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":772,\"width\":81},\"column\":[11],\"row\":[24],\"word\":\"-112\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":804,\"width\":81},\"column\":[11],\"row\":[25],\"word\":\"111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":834,\"width\":81},\"column\":[11],\"row\":[26],\"word\":\"-112\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":866,\"width\":81},\"column\":[11],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":896,\"width\":81},\"column\":[11],\"row\":[28],\"word\":\"111\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":928,\"width\":81},\"column\":[11],\"row\":[29],\"word\":\"-111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":958,\"width\":81},\"column\":[11],\"row\":[30],\"word\":\"-112\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":990,\"width\":81},\"column\":[11],\"row\":[31],\"word\":\"-111\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1022,\"width\":81},\"column\":[11],\"row\":[32],\"word\":\"-111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1052,\"width\":81},\"column\":[11],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1084,\"width\":81},\"column\":[11],\"row\":[34],\"word\":\"111\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1114,\"width\":81},\"column\":[11],\"row\":[35],\"word\":\"-110\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1146,\"width\":81},\"column\":[11],\"row\":[36],\"word\":\"-110\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1175,\"width\":81},\"column\":[11],\"row\":[37],\"word\":\"-110\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1205,\"width\":81},\"column\":[11],\"row\":[38],\"word\":\"-113\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1235,\"width\":81},\"column\":[11],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1267,\"width\":81},\"column\":[11],\"row\":[40],\"word\":\"-54\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1297,\"width\":81},\"column\":[11],\"row\":[41],\"word\":\"-46\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1327,\"width\":81},\"column\":[11],\"row\":[42],\"word\":\"-37\"},{\"rect\":{\"height\":28,\"left\":913,\"top\":1359,\"width\":81},\"column\":[11],\"row\":[43],\"word\":\"-29\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1387,\"width\":81},\"column\":[11],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1419,\"width\":81},\"column\":[11],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1449,\"width\":81},\"column\":[11],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1478,\"width\":81},\"column\":[11],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1510,\"width\":81},\"column\":[11],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1540,\"width\":81},\"column\":[11],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1572,\"width\":81},\"column\":[11],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1602,\"width\":81},\"column\":[11],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1634,\"width\":81},\"column\":[11],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1666,\"width\":81},\"column\":[11],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1698,\"width\":81},\"column\":[11],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":913,\"top\":1728,\"width\":81},\"column\":[11],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":913,\"top\":1756,\"width\":81},\"column\":[11],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":913,\"top\":1786,\"width\":81},\"column\":[11],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":913,\"top\":1815,\"width\":81},\"column\":[11],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":913,\"top\":1847,\"width\":81},\"column\":[11],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":913,\"top\":1884,\"width\":81},\"column\":[11],\"row\":[60],\"word\":\"\"},{\"rect\":{\"height\":52,\"left\":994,\"top\":6,\"width\":79},\"column\":[12],\"row\":[1],\"word\":\"bow\"},{\"rect\":{\"height\":34,\"left\":994,\"top\":58,\"width\":79},\"column\":[12],\"row\":[2],\"word\":\"1.6m\"},{\"rect\":{\"height\":42,\"left\":994,\"top\":92,\"width\":79},\"column\":[12],\"row\":[3],\"word\":\"(mm)\"},{\"rect\":{\"height\":47,\"left\":994,\"top\":134,\"width\":79},\"column\":[12],\"row\":[4],\"word\":\"-224\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":181,\"width\":79},\"column\":[12],\"row\":[5],\"word\":\"-218\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":211,\"width\":79},\"column\":[12],\"row\":[6],\"word\":\"-212\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":243,\"width\":79},\"column\":[12],\"row\":[7],\"word\":\"-208\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":273,\"width\":79},\"column\":[12],\"row\":[8],\"word\":\"-205\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":305,\"width\":79},\"column\":[12],\"row\":[9],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":337,\"width\":79},\"column\":[12],\"row\":[10],\"word\":\"-200\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":367,\"width\":79},\"column\":[12],\"row\":[11],\"word\":\"-195\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":399,\"width\":79},\"column\":[12],\"row\":[12],\"word\":\"-192\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":429,\"width\":79},\"column\":[12],\"row\":[13],\"word\":\"-189\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":461,\"width\":79},\"column\":[12],\"row\":[14],\"word\":\"-185\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":493,\"width\":79},\"column\":[12],\"row\":[15],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":523,\"width\":79},\"column\":[12],\"row\":[16],\"word\":\"-181\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":555,\"width\":79},\"column\":[12],\"row\":[17],\"word\":\"-159\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":587,\"width\":79},\"column\":[12],\"row\":[18],\"word\":\"-147\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":617,\"width\":79},\"column\":[12],\"row\":[19],\"word\":\"-142\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":649,\"width\":79},\"column\":[12],\"row\":[20],\"word\":\"-138\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":678,\"width\":79},\"column\":[12],\"row\":[21],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":710,\"width\":79},\"column\":[12],\"row\":[22],\"word\":\"-137\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":742,\"width\":79},\"column\":[12],\"row\":[23],\"word\":\"-131\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":772,\"width\":79},\"column\":[12],\"row\":[24],\"word\":\"-128\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":804,\"width\":79},\"column\":[12],\"row\":[25],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":834,\"width\":79},\"column\":[12],\"row\":[26],\"word\":\"-127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":866,\"width\":79},\"column\":[12],\"row\":[27],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":896,\"width\":79},\"column\":[12],\"row\":[28],\"word\":\"-127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":928,\"width\":79},\"column\":[12],\"row\":[29],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":958,\"width\":79},\"column\":[12],\"row\":[30],\"word\":\"-128\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":990,\"width\":79},\"column\":[12],\"row\":[31],\"word\":\"127\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1022,\"width\":79},\"column\":[12],\"row\":[32],\"word\":\"-127\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1052,\"width\":79},\"column\":[12],\"row\":[33],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1084,\"width\":79},\"column\":[12],\"row\":[34],\"word\":\"-126\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1114,\"width\":79},\"column\":[12],\"row\":[35],\"word\":\"-126\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1146,\"width\":79},\"column\":[12],\"row\":[36],\"word\":\"-126\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1175,\"width\":79},\"column\":[12],\"row\":[37],\"word\":\"-126\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1205,\"width\":79},\"column\":[12],\"row\":[38],\"word\":\"-130\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1235,\"width\":79},\"column\":[12],\"row\":[39],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1267,\"width\":79},\"column\":[12],\"row\":[40],\"word\":\"-55\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1297,\"width\":79},\"column\":[12],\"row\":[41],\"word\":\"-46\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1327,\"width\":79},\"column\":[12],\"row\":[42],\"word\":\"-38\"},{\"rect\":{\"height\":28,\"left\":994,\"top\":1359,\"width\":79},\"column\":[12],\"row\":[43],\"word\":\"-30\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1387,\"width\":79},\"column\":[12],\"row\":[44],\"word\":\"-20\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1419,\"width\":79},\"column\":[12],\"row\":[45],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1449,\"width\":79},\"column\":[12],\"row\":[46],\"word\":\"-13\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1478,\"width\":79},\"column\":[12],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1510,\"width\":79},\"column\":[12],\"row\":[48],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1540,\"width\":79},\"column\":[12],\"row\":[49],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1572,\"width\":79},\"column\":[12],\"row\":[50],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1602,\"width\":79},\"column\":[12],\"row\":[51],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1634,\"width\":79},\"column\":[12],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1666,\"width\":79},\"column\":[12],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1698,\"width\":79},\"column\":[12],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":28,\"left\":994,\"top\":1728,\"width\":79},\"column\":[12],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":994,\"top\":1756,\"width\":79},\"column\":[12],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":29,\"left\":994,\"top\":1786,\"width\":79},\"column\":[12],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":994,\"top\":1815,\"width\":79},\"column\":[12],\"row\":[58],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":994,\"top\":1847,\"width\":79},\"column\":[12],\"row\":[59],\"word\":\"\"},{\"rect\":{\"height\":38,\"left\":994,\"top\":1884,\"width\":79},\"column\":[12],\"row\":[60],\"word\":\"\"}]}]}","ret_msg":"\u5df2\u5b8c\u6210","percent":100,"ret_code":3},"log_id":"1584681967548143"}
//test_json;
//        $test_json = <<<test_json
//{"result":{"result_data":"{\"form_num\":1,\"forms\":[{\"footer\":[{\"rect\":{\"top\":1916,\"left\":0,\"width\":1080,\"height\":1920},\"column\":[1],\"row\":[1],\"word\":\"\"}],\"header\":[{\"rect\":{\"top\":0,\"left\":0,\"width\":1080,\"height\":9},\"column\":[1],\"row\":[1],\"word\":\"\"}],\"body\":[{\"rect\":{\"height\":49,\"left\":134,\"top\":9,\"width\":133},\"column\":[1],\"row\":[1],\"word\":\"\u7a7a\u9ad8\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":58,\"width\":133},\"column\":[1],\"row\":[2],\"word\":\"Ullage\"},{\"rect\":{\"height\":47,\"left\":134,\"top\":92,\"width\":133},\"column\":[1],\"row\":[3],\"word\":\"(m)\"},{\"rect\":{\"height\":47,\"left\":134,\"top\":139,\"width\":133},\"column\":[1],\"row\":[4],\"word\":\"5.000\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":186,\"width\":133},\"column\":[1],\"row\":[5],\"word\":\"5.010\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":220,\"width\":133},\"column\":[1],\"row\":[6],\"word\":\"5.020\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":254,\"width\":133},\"column\":[1],\"row\":[7],\"word\":\"5.030\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":286,\"width\":133},\"column\":[1],\"row\":[8],\"word\":\"5.040\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":320,\"width\":133},\"column\":[1],\"row\":[9],\"word\":\"5.050\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":354,\"width\":133},\"column\":[1],\"row\":[10],\"word\":\"5.060\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":386,\"width\":133},\"column\":[1],\"row\":[11],\"word\":\"5.070\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":420,\"width\":133},\"column\":[1],\"row\":[12],\"word\":\"5.080\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":452,\"width\":133},\"column\":[1],\"row\":[13],\"word\":\"5.090\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":484,\"width\":133},\"column\":[1],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":518,\"width\":133},\"column\":[1],\"row\":[15],\"word\":\"5.100\"},{\"rect\":{\"height\":35,\"left\":134,\"top\":550,\"width\":133},\"column\":[1],\"row\":[16],\"word\":\"5.110\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":585,\"width\":133},\"column\":[1],\"row\":[17],\"word\":\"5.120\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":617,\"width\":133},\"column\":[1],\"row\":[18],\"word\":\"5.130\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":651,\"width\":133},\"column\":[1],\"row\":[19],\"word\":\"5.140\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":683,\"width\":133},\"column\":[1],\"row\":[20],\"word\":\"5.150\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":717,\"width\":133},\"column\":[1],\"row\":[21],\"word\":\"5.160\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":751,\"width\":133},\"column\":[1],\"row\":[22],\"word\":\"5.170\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":783,\"width\":133},\"column\":[1],\"row\":[23],\"word\":\"5.180\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":815,\"width\":133},\"column\":[1],\"row\":[24],\"word\":\"5.190\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":847,\"width\":133},\"column\":[1],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":879,\"width\":133},\"column\":[1],\"row\":[26],\"word\":\"5.200\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":911,\"width\":133},\"column\":[1],\"row\":[27],\"word\":\"5.210\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":943,\"width\":133},\"column\":[1],\"row\":[28],\"word\":\"5.220\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":975,\"width\":133},\"column\":[1],\"row\":[29],\"word\":\"5.230\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1007,\"width\":133},\"column\":[1],\"row\":[30],\"word\":\"5.240\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1041,\"width\":133},\"column\":[1],\"row\":[31],\"word\":\"5.250\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1073,\"width\":133},\"column\":[1],\"row\":[32],\"word\":\"5.260\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1107,\"width\":133},\"column\":[1],\"row\":[33],\"word\":\"5.270\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1139,\"width\":133},\"column\":[1],\"row\":[34],\"word\":\"5.280\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1171,\"width\":133},\"column\":[1],\"row\":[35],\"word\":\"5.290\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1203,\"width\":133},\"column\":[1],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1235,\"width\":133},\"column\":[1],\"row\":[37],\"word\":\"5.300\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1267,\"width\":133},\"column\":[1],\"row\":[38],\"word\":\"5.310\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1299,\"width\":133},\"column\":[1],\"row\":[39],\"word\":\"5.320\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1331,\"width\":133},\"column\":[1],\"row\":[40],\"word\":\"5.330\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1365,\"width\":133},\"column\":[1],\"row\":[41],\"word\":\"5.340\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1397,\"width\":133},\"column\":[1],\"row\":[42],\"word\":\"5.350\"},{\"rect\":{\"height\":30,\"left\":134,\"top\":1431,\"width\":133},\"column\":[1],\"row\":[43],\"word\":\"5.360\"},{\"rect\":{\"height\":30,\"left\":134,\"top\":1461,\"width\":133},\"column\":[1],\"row\":[44],\"word\":\"5.370\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1491,\"width\":133},\"column\":[1],\"row\":[45],\"word\":\"5.380\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1525,\"width\":133},\"column\":[1],\"row\":[46],\"word\":\"5.390\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1557,\"width\":133},\"column\":[1],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":134,\"top\":1589,\"width\":133},\"column\":[1],\"row\":[48],\"word\":\"5.400\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1619,\"width\":133},\"column\":[1],\"row\":[49],\"word\":\"5.410\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1651,\"width\":133},\"column\":[1],\"row\":[50],\"word\":\"5.420\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1683,\"width\":133},\"column\":[1],\"row\":[51],\"word\":\"5.430\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1715,\"width\":133},\"column\":[1],\"row\":[52],\"word\":\"5.440\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1749,\"width\":133},\"column\":[1],\"row\":[53],\"word\":\"5.450\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1781,\"width\":133},\"column\":[1],\"row\":[54],\"word\":\"5.460\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1815,\"width\":133},\"column\":[1],\"row\":[55],\"word\":\"5.470\"},{\"rect\":{\"height\":37,\"left\":134,\"top\":1847,\"width\":133},\"column\":[1],\"row\":[56],\"word\":\"5.480\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1884,\"width\":133},\"column\":[1],\"row\":[57],\"word\":\"5.490\"},{\"rect\":{\"height\":49,\"left\":267,\"top\":9,\"width\":134},\"column\":[2],\"row\":[1],\"word\":\"\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":58,\"width\":134},\"column\":[2],\"row\":[2],\"word\":\"Capacity\"},{\"rect\":{\"height\":47,\"left\":267,\"top\":92,\"width\":134},\"column\":[2],\"row\":[3],\"word\":\"(m3)\"},{\"rect\":{\"height\":47,\"left\":267,\"top\":139,\"width\":134},\"column\":[2],\"row\":[4],\"word\":\"108.521\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":186,\"width\":134},\"column\":[2],\"row\":[5],\"word\":\"107.248\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":220,\"width\":134},\"column\":[2],\"row\":[6],\"word\":\"105.974\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":254,\"width\":134},\"column\":[2],\"row\":[7],\"word\":\"104.700\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":286,\"width\":134},\"column\":[2],\"row\":[8],\"word\":\"103.427\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":320,\"width\":134},\"column\":[2],\"row\":[9],\"word\":\"102.152\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":354,\"width\":134},\"column\":[2],\"row\":[10],\"word\":\"100.878\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":386,\"width\":134},\"column\":[2],\"row\":[11],\"word\":\"99.604\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":420,\"width\":134},\"column\":[2],\"row\":[12],\"word\":\"98.363\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":452,\"width\":134},\"column\":[2],\"row\":[13],\"word\":\"97.172\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":484,\"width\":134},\"column\":[2],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":518,\"width\":134},\"column\":[2],\"row\":[15],\"word\":\"95.986\"},{\"rect\":{\"height\":35,\"left\":267,\"top\":550,\"width\":134},\"column\":[2],\"row\":[16],\"word\":\"94.800\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":585,\"width\":134},\"column\":[2],\"row\":[17],\"word\":\"93.615\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":617,\"width\":134},\"column\":[2],\"row\":[18],\"word\":\"92.430\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":651,\"width\":134},\"column\":[2],\"row\":[19],\"word\":\"91.243\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":683,\"width\":134},\"column\":[2],\"row\":[20],\"word\":\"90.057\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":717,\"width\":134},\"column\":[2],\"row\":[21],\"word\":\"88.872\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":751,\"width\":134},\"column\":[2],\"row\":[22],\"word\":\"87.687\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":783,\"width\":134},\"column\":[2],\"row\":[23],\"word\":\"86.502\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":815,\"width\":134},\"column\":[2],\"row\":[24],\"word\":\"85.315\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":847,\"width\":134},\"column\":[2],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":879,\"width\":134},\"column\":[2],\"row\":[26],\"word\":\"84.130\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":911,\"width\":134},\"column\":[2],\"row\":[27],\"word\":\"82.944\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":943,\"width\":134},\"column\":[2],\"row\":[28],\"word\":\"81.758\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":975,\"width\":134},\"column\":[2],\"row\":[29],\"word\":\"80.572\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1007,\"width\":134},\"column\":[2],\"row\":[30],\"word\":\"79.386\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1041,\"width\":134},\"column\":[2],\"row\":[31],\"word\":\"78.201\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1073,\"width\":134},\"column\":[2],\"row\":[32],\"word\":\"77.016\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1107,\"width\":134},\"column\":[2],\"row\":[33],\"word\":\"75.829\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1139,\"width\":134},\"column\":[2],\"row\":[34],\"word\":\"74.643\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1171,\"width\":134},\"column\":[2],\"row\":[35],\"word\":\"73.458\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1203,\"width\":134},\"column\":[2],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1235,\"width\":134},\"column\":[2],\"row\":[37],\"word\":\"72.272\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1267,\"width\":134},\"column\":[2],\"row\":[38],\"word\":\"71.086\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1299,\"width\":134},\"column\":[2],\"row\":[39],\"word\":\"69.899\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1331,\"width\":134},\"column\":[2],\"row\":[40],\"word\":\"68.713\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1365,\"width\":134},\"column\":[2],\"row\":[41],\"word\":\"67.528\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1397,\"width\":134},\"column\":[2],\"row\":[42],\"word\":\"66.342\"},{\"rect\":{\"height\":30,\"left\":267,\"top\":1431,\"width\":134},\"column\":[2],\"row\":[43],\"word\":\"65.155\"},{\"rect\":{\"height\":30,\"left\":267,\"top\":1461,\"width\":134},\"column\":[2],\"row\":[44],\"word\":\"63.969\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1491,\"width\":134},\"column\":[2],\"row\":[45],\"word\":\"62.783\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1525,\"width\":134},\"column\":[2],\"row\":[46],\"word\":\"61.597\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1557,\"width\":134},\"column\":[2],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":267,\"top\":1589,\"width\":134},\"column\":[2],\"row\":[48],\"word\":\"60.425\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1619,\"width\":134},\"column\":[2],\"row\":[49],\"word\":\"59.278\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1651,\"width\":134},\"column\":[2],\"row\":[50],\"word\":\"58.134\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1683,\"width\":134},\"column\":[2],\"row\":[51],\"word\":\"56.991\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1715,\"width\":134},\"column\":[2],\"row\":[52],\"word\":\"55.846\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1749,\"width\":134},\"column\":[2],\"row\":[53],\"word\":\"54.702\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1781,\"width\":134},\"column\":[2],\"row\":[54],\"word\":\"53.559\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1815,\"width\":134},\"column\":[2],\"row\":[55],\"word\":\"52.415\"},{\"rect\":{\"height\":37,\"left\":267,\"top\":1847,\"width\":134},\"column\":[2],\"row\":[56],\"word\":\"51.270\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1884,\"width\":134},\"column\":[2],\"row\":[57],\"word\":\"50.126\"},{\"rect\":{\"height\":49,\"left\":401,\"top\":9,\"width\":132},\"column\":[3],\"row\":[1],\"word\":\"\u5398\u7c73\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":58,\"width\":132},\"column\":[3],\"row\":[2],\"word\":\"Diff\"},{\"rect\":{\"height\":47,\"left\":401,\"top\":92,\"width\":132},\"column\":[3],\"row\":[3],\"word\":\"(m3\/cm)\"},{\"rect\":{\"height\":47,\"left\":401,\"top\":139,\"width\":132},\"column\":[3],\"row\":[4],\"word\":\"1.273\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":186,\"width\":132},\"column\":[3],\"row\":[5],\"word\":\"1.274\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":220,\"width\":132},\"column\":[3],\"row\":[6],\"word\":\"1.274\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":254,\"width\":132},\"column\":[3],\"row\":[7],\"word\":\"1.273\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":286,\"width\":132},\"column\":[3],\"row\":[8],\"word\":\"1.275\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":320,\"width\":132},\"column\":[3],\"row\":[9],\"word\":\"1.274\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":354,\"width\":132},\"column\":[3],\"row\":[10],\"word\":\"1.274\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":386,\"width\":132},\"column\":[3],\"row\":[11],\"word\":\"1.241*\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":420,\"width\":132},\"column\":[3],\"row\":[12],\"word\":\"1.191\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":452,\"width\":132},\"column\":[3],\"row\":[13],\"word\":\"1.186\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":484,\"width\":132},\"column\":[3],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":518,\"width\":132},\"column\":[3],\"row\":[15],\"word\":\"1.186*\"},{\"rect\":{\"height\":35,\"left\":401,\"top\":550,\"width\":132},\"column\":[3],\"row\":[16],\"word\":\"1.185*\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":585,\"width\":132},\"column\":[3],\"row\":[17],\"word\":\"1.185\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":617,\"width\":132},\"column\":[3],\"row\":[18],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":651,\"width\":132},\"column\":[3],\"row\":[19],\"word\":\"1.186\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":683,\"width\":132},\"column\":[3],\"row\":[20],\"word\":\"1.185\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":717,\"width\":132},\"column\":[3],\"row\":[21],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":751,\"width\":132},\"column\":[3],\"row\":[22],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":783,\"width\":132},\"column\":[3],\"row\":[23],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":815,\"width\":132},\"column\":[3],\"row\":[24],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":847,\"width\":132},\"column\":[3],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":879,\"width\":132},\"column\":[3],\"row\":[26],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":911,\"width\":132},\"column\":[3],\"row\":[27],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":943,\"width\":132},\"column\":[3],\"row\":[28],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":975,\"width\":132},\"column\":[3],\"row\":[29],\"word\":\"1.186*\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1007,\"width\":132},\"column\":[3],\"row\":[30],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1041,\"width\":132},\"column\":[3],\"row\":[31],\"word\":\"1.185\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1073,\"width\":132},\"column\":[3],\"row\":[32],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1107,\"width\":132},\"column\":[3],\"row\":[33],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1139,\"width\":132},\"column\":[3],\"row\":[34],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1171,\"width\":132},\"column\":[3],\"row\":[35],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1203,\"width\":132},\"column\":[3],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1235,\"width\":132},\"column\":[3],\"row\":[37],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1267,\"width\":132},\"column\":[3],\"row\":[38],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1299,\"width\":132},\"column\":[3],\"row\":[39],\"word\":\"1.186\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1331,\"width\":132},\"column\":[3],\"row\":[40],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1365,\"width\":132},\"column\":[3],\"row\":[41],\"word\":\"1.186\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1397,\"width\":132},\"column\":[3],\"row\":[42],\"word\":\"1.187\"},{\"rect\":{\"height\":30,\"left\":401,\"top\":1431,\"width\":132},\"column\":[3],\"row\":[43],\"word\":\"1.186\"},{\"rect\":{\"height\":30,\"left\":401,\"top\":1461,\"width\":132},\"column\":[3],\"row\":[44],\"word\":\"1.186\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1491,\"width\":132},\"column\":[3],\"row\":[45],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1525,\"width\":132},\"column\":[3],\"row\":[46],\"word\":\"1.172\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1557,\"width\":132},\"column\":[3],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":401,\"top\":1589,\"width\":132},\"column\":[3],\"row\":[48],\"word\":\"1.147\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1619,\"width\":132},\"column\":[3],\"row\":[49],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1651,\"width\":132},\"column\":[3],\"row\":[50],\"word\":\"1.143\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1683,\"width\":132},\"column\":[3],\"row\":[51],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1715,\"width\":132},\"column\":[3],\"row\":[52],\"word\":\"1144\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1749,\"width\":132},\"column\":[3],\"row\":[53],\"word\":\"1.143\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1781,\"width\":132},\"column\":[3],\"row\":[54],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1815,\"width\":132},\"column\":[3],\"row\":[55],\"word\":\"1.145\"},{\"rect\":{\"height\":37,\"left\":401,\"top\":1847,\"width\":132},\"column\":[3],\"row\":[56],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1884,\"width\":132},\"column\":[3],\"row\":[57],\"word\":\"1.144\"},{\"rect\":{\"height\":49,\"left\":533,\"top\":9,\"width\":135},\"column\":[4],\"row\":[1],\"word\":\"\u5b9e\u9ad8\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":58,\"width\":135},\"column\":[4],\"row\":[2],\"word\":\"Sounding\"},{\"rect\":{\"height\":47,\"left\":533,\"top\":92,\"width\":135},\"column\":[4],\"row\":[3],\"word\":\"(m)\"},{\"rect\":{\"height\":47,\"left\":533,\"top\":139,\"width\":135},\"column\":[4],\"row\":[4],\"word\":\"0.421\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":186,\"width\":135},\"column\":[4],\"row\":[5],\"word\":\"0.411\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":220,\"width\":135},\"column\":[4],\"row\":[6],\"word\":\"0.401\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":254,\"width\":135},\"column\":[4],\"row\":[7],\"word\":\"0.391\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":286,\"width\":135},\"column\":[4],\"row\":[8],\"word\":\"0.381\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":320,\"width\":135},\"column\":[4],\"row\":[9],\"word\":\"0.371\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":354,\"width\":135},\"column\":[4],\"row\":[10],\"word\":\"0.36\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":386,\"width\":135},\"column\":[4],\"row\":[11],\"word\":\"0.35\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":420,\"width\":135},\"column\":[4],\"row\":[12],\"word\":\"*0.341\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":452,\"width\":135},\"column\":[4],\"row\":[13],\"word\":\"0.331\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":484,\"width\":135},\"column\":[4],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":518,\"width\":135},\"column\":[4],\"row\":[15],\"word\":\"0.321\"},{\"rect\":{\"height\":35,\"left\":533,\"top\":550,\"width\":135},\"column\":[4],\"row\":[16],\"word\":\"0.311\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":585,\"width\":135},\"column\":[4],\"row\":[17],\"word\":\"*0.301\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":617,\"width\":135},\"column\":[4],\"row\":[18],\"word\":\"0.291\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":651,\"width\":135},\"column\":[4],\"row\":[19],\"word\":\"0.281\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":683,\"width\":135},\"column\":[4],\"row\":[20],\"word\":\"0.271\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":717,\"width\":135},\"column\":[4],\"row\":[21],\"word\":\"0.261\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":751,\"width\":135},\"column\":[4],\"row\":[22],\"word\":\"0.251\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":783,\"width\":135},\"column\":[4],\"row\":[23],\"word\":\"0.241\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":815,\"width\":135},\"column\":[4],\"row\":[24],\"word\":\"0.231\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":847,\"width\":135},\"column\":[4],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":879,\"width\":135},\"column\":[4],\"row\":[26],\"word\":\"0.221\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":911,\"width\":135},\"column\":[4],\"row\":[27],\"word\":\"0.211\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":943,\"width\":135},\"column\":[4],\"row\":[28],\"word\":\"0.201\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":975,\"width\":135},\"column\":[4],\"row\":[29],\"word\":\"0.19\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1007,\"width\":135},\"column\":[4],\"row\":[30],\"word\":\"0.181\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1041,\"width\":135},\"column\":[4],\"row\":[31],\"word\":\"*0.171\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1073,\"width\":135},\"column\":[4],\"row\":[32],\"word\":\"0.161\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1107,\"width\":135},\"column\":[4],\"row\":[33],\"word\":\"0.151\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1139,\"width\":135},\"column\":[4],\"row\":[34],\"word\":\"0.141\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1171,\"width\":135},\"column\":[4],\"row\":[35],\"word\":\"0.131\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1203,\"width\":135},\"column\":[4],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1235,\"width\":135},\"column\":[4],\"row\":[37],\"word\":\"0.121\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1267,\"width\":135},\"column\":[4],\"row\":[38],\"word\":\"0.11\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1299,\"width\":135},\"column\":[4],\"row\":[39],\"word\":\"0.101\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1331,\"width\":135},\"column\":[4],\"row\":[40],\"word\":\"*0.091\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1365,\"width\":135},\"column\":[4],\"row\":[41],\"word\":\"*0.081\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1397,\"width\":135},\"column\":[4],\"row\":[42],\"word\":\"*0.071\"},{\"rect\":{\"height\":30,\"left\":533,\"top\":1431,\"width\":135},\"column\":[4],\"row\":[43],\"word\":\"*0.061\"},{\"rect\":{\"height\":30,\"left\":533,\"top\":1461,\"width\":135},\"column\":[4],\"row\":[44],\"word\":\"*0.051\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1491,\"width\":135},\"column\":[4],\"row\":[45],\"word\":\"*0.041\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1525,\"width\":135},\"column\":[4],\"row\":[46],\"word\":\"*0.031\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1557,\"width\":135},\"column\":[4],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":533,\"top\":1589,\"width\":135},\"column\":[4],\"row\":[48],\"word\":\"0.021\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1619,\"width\":135},\"column\":[4],\"row\":[49],\"word\":\"0.011\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1651,\"width\":135},\"column\":[4],\"row\":[50],\"word\":\"0.001\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1683,\"width\":135},\"column\":[4],\"row\":[51],\"word\":\"0.000\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1715,\"width\":135},\"column\":[4],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1749,\"width\":135},\"column\":[4],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1781,\"width\":135},\"column\":[4],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1815,\"width\":135},\"column\":[4],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":533,\"top\":1847,\"width\":135},\"column\":[4],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1884,\"width\":135},\"column\":[4],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":49,\"left\":668,\"top\":9,\"width\":134},\"column\":[5],\"row\":[1],\"word\":\"\u7a7a\u9ad8\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":58,\"width\":134},\"column\":[5],\"row\":[2],\"word\":\"Ullage\"},{\"rect\":{\"height\":47,\"left\":668,\"top\":92,\"width\":134},\"column\":[5],\"row\":[3],\"word\":\"(m)\"},{\"rect\":{\"height\":47,\"left\":668,\"top\":139,\"width\":134},\"column\":[5],\"row\":[4],\"word\":\"5.500\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":186,\"width\":134},\"column\":[5],\"row\":[5],\"word\":\"5.510\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":220,\"width\":134},\"column\":[5],\"row\":[6],\"word\":\"5.520\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":254,\"width\":134},\"column\":[5],\"row\":[7],\"word\":\"5.530\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":286,\"width\":134},\"column\":[5],\"row\":[8],\"word\":\"5.540\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":320,\"width\":134},\"column\":[5],\"row\":[9],\"word\":\"5.550\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":354,\"width\":134},\"column\":[5],\"row\":[10],\"word\":\"5.560\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":386,\"width\":134},\"column\":[5],\"row\":[11],\"word\":\"5.570\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":420,\"width\":134},\"column\":[5],\"row\":[12],\"word\":\"5.580\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":452,\"width\":134},\"column\":[5],\"row\":[13],\"word\":\"5.590\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":484,\"width\":134},\"column\":[5],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":518,\"width\":134},\"column\":[5],\"row\":[15],\"word\":\"5.600\"},{\"rect\":{\"height\":35,\"left\":668,\"top\":550,\"width\":134},\"column\":[5],\"row\":[16],\"word\":\"5.610\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":585,\"width\":134},\"column\":[5],\"row\":[17],\"word\":\"5.620\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":617,\"width\":134},\"column\":[5],\"row\":[18],\"word\":\"5.630\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":651,\"width\":134},\"column\":[5],\"row\":[19],\"word\":\"5.640\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":683,\"width\":134},\"column\":[5],\"row\":[20],\"word\":\"5.650\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":717,\"width\":134},\"column\":[5],\"row\":[21],\"word\":\"5.660\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":751,\"width\":134},\"column\":[5],\"row\":[22],\"word\":\"5.670\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":783,\"width\":134},\"column\":[5],\"row\":[23],\"word\":\"5.680\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":815,\"width\":134},\"column\":[5],\"row\":[24],\"word\":\"5.690\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":847,\"width\":134},\"column\":[5],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":879,\"width\":134},\"column\":[5],\"row\":[26],\"word\":\"5.700\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":911,\"width\":134},\"column\":[5],\"row\":[27],\"word\":\"5.710\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":943,\"width\":134},\"column\":[5],\"row\":[28],\"word\":\"5.720\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":975,\"width\":134},\"column\":[5],\"row\":[29],\"word\":\"5.730\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1007,\"width\":134},\"column\":[5],\"row\":[30],\"word\":\"5.740\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1041,\"width\":134},\"column\":[5],\"row\":[31],\"word\":\"5750\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1073,\"width\":134},\"column\":[5],\"row\":[32],\"word\":\"5.760\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1107,\"width\":134},\"column\":[5],\"row\":[33],\"word\":\"5.770\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1139,\"width\":134},\"column\":[5],\"row\":[34],\"word\":\"5.780\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1171,\"width\":134},\"column\":[5],\"row\":[35],\"word\":\"5.790\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1203,\"width\":134},\"column\":[5],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1235,\"width\":134},\"column\":[5],\"row\":[37],\"word\":\"5.800\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1267,\"width\":134},\"column\":[5],\"row\":[38],\"word\":\"5.810\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1299,\"width\":134},\"column\":[5],\"row\":[39],\"word\":\"5.820\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1331,\"width\":134},\"column\":[5],\"row\":[40],\"word\":\"5.830\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1365,\"width\":134},\"column\":[5],\"row\":[41],\"word\":\"5.840\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1397,\"width\":134},\"column\":[5],\"row\":[42],\"word\":\"5.850\"},{\"rect\":{\"height\":30,\"left\":668,\"top\":1431,\"width\":134},\"column\":[5],\"row\":[43],\"word\":\"5.860\"},{\"rect\":{\"height\":30,\"left\":668,\"top\":1461,\"width\":134},\"column\":[5],\"row\":[44],\"word\":\"5.870\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1491,\"width\":134},\"column\":[5],\"row\":[45],\"word\":\"5.880\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1525,\"width\":134},\"column\":[5],\"row\":[46],\"word\":\"5.890\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1557,\"width\":134},\"column\":[5],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":668,\"top\":1589,\"width\":134},\"column\":[5],\"row\":[48],\"word\":\"5.900\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1619,\"width\":134},\"column\":[5],\"row\":[49],\"word\":\"5.910\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1651,\"width\":134},\"column\":[5],\"row\":[50],\"word\":\"5.920\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1683,\"width\":134},\"column\":[5],\"row\":[51],\"word\":\"5.921\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1715,\"width\":134},\"column\":[5],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1749,\"width\":134},\"column\":[5],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1781,\"width\":134},\"column\":[5],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1815,\"width\":134},\"column\":[5],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":668,\"top\":1847,\"width\":134},\"column\":[5],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1884,\"width\":134},\"column\":[5],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":49,\"left\":802,\"top\":9,\"width\":135},\"column\":[6],\"row\":[1],\"word\":\"\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":58,\"width\":135},\"column\":[6],\"row\":[2],\"word\":\"Capacity\"},{\"rect\":{\"height\":47,\"left\":802,\"top\":92,\"width\":135},\"column\":[6],\"row\":[3],\"word\":\"(m3)\"},{\"rect\":{\"height\":47,\"left\":802,\"top\":139,\"width\":135},\"column\":[6],\"row\":[4],\"word\":\"48.982\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":186,\"width\":135},\"column\":[6],\"row\":[5],\"word\":\"47.838\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":220,\"width\":135},\"column\":[6],\"row\":[6],\"word\":\"46.694\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":254,\"width\":135},\"column\":[6],\"row\":[7],\"word\":\"45.549\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":286,\"width\":135},\"column\":[6],\"row\":[8],\"word\":\"44.404\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":320,\"width\":135},\"column\":[6],\"row\":[9],\"word\":\"43.260\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":354,\"width\":135},\"column\":[6],\"row\":[10],\"word\":\"42.116\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":386,\"width\":135},\"column\":[6],\"row\":[11],\"word\":\"40.971\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":420,\"width\":135},\"column\":[6],\"row\":[12],\"word\":\"39.826\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":452,\"width\":135},\"column\":[6],\"row\":[13],\"word\":\"38.682\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":484,\"width\":135},\"column\":[6],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":518,\"width\":135},\"column\":[6],\"row\":[15],\"word\":\"37.537\"},{\"rect\":{\"height\":35,\"left\":802,\"top\":550,\"width\":135},\"column\":[6],\"row\":[16],\"word\":\"36.392\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":585,\"width\":135},\"column\":[6],\"row\":[17],\"word\":\"35.248\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":617,\"width\":135},\"column\":[6],\"row\":[18],\"word\":\"34.102\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":651,\"width\":135},\"column\":[6],\"row\":[19],\"word\":\"32.957\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":683,\"width\":135},\"column\":[6],\"row\":[20],\"word\":\"31.812\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":717,\"width\":135},\"column\":[6],\"row\":[21],\"word\":\"30.668\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":751,\"width\":135},\"column\":[6],\"row\":[22],\"word\":\"29.522\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":783,\"width\":135},\"column\":[6],\"row\":[23],\"word\":\"28.376\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":815,\"width\":135},\"column\":[6],\"row\":[24],\"word\":\"27.232\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":847,\"width\":135},\"column\":[6],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":879,\"width\":135},\"column\":[6],\"row\":[26],\"word\":\"26.086\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":911,\"width\":135},\"column\":[6],\"row\":[27],\"word\":\"24.941\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":943,\"width\":135},\"column\":[6],\"row\":[28],\"word\":\"23.796\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":975,\"width\":135},\"column\":[6],\"row\":[29],\"word\":\"22.650\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1007,\"width\":135},\"column\":[6],\"row\":[30],\"word\":\"21.490\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1041,\"width\":135},\"column\":[6],\"row\":[31],\"word\":\"20.304\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1073,\"width\":135},\"column\":[6],\"row\":[32],\"word\":\"19.116\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1107,\"width\":135},\"column\":[6],\"row\":[33],\"word\":\"17.928\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1139,\"width\":135},\"column\":[6],\"row\":[34],\"word\":\"16.740\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1171,\"width\":135},\"column\":[6],\"row\":[35],\"word\":\"15.551\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1203,\"width\":135},\"column\":[6],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1235,\"width\":135},\"column\":[6],\"row\":[37],\"word\":\"14.363\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1267,\"width\":135},\"column\":[6],\"row\":[38],\"word\":\"13.175\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1299,\"width\":135},\"column\":[6],\"row\":[39],\"word\":\"11.986\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1331,\"width\":135},\"column\":[6],\"row\":[40],\"word\":\"10.798\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1365,\"width\":135},\"column\":[6],\"row\":[41],\"word\":\"9.609\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1397,\"width\":135},\"column\":[6],\"row\":[42],\"word\":\"8.420\"},{\"rect\":{\"height\":30,\"left\":802,\"top\":1431,\"width\":135},\"column\":[6],\"row\":[43],\"word\":\"7.231\"},{\"rect\":{\"height\":30,\"left\":802,\"top\":1461,\"width\":135},\"column\":[6],\"row\":[44],\"word\":\"6.042\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1491,\"width\":135},\"column\":[6],\"row\":[45],\"word\":\"4.852\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1525,\"width\":135},\"column\":[6],\"row\":[46],\"word\":\"3.666\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1557,\"width\":135},\"column\":[6],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":802,\"top\":1589,\"width\":135},\"column\":[6],\"row\":[48],\"word\":\"2.539\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1619,\"width\":135},\"column\":[6],\"row\":[49],\"word\":\"1.634\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1651,\"width\":135},\"column\":[6],\"row\":[50],\"word\":\"0.941\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1683,\"width\":135},\"column\":[6],\"row\":[51],\"word\":\"0.874\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1715,\"width\":135},\"column\":[6],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1749,\"width\":135},\"column\":[6],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1781,\"width\":135},\"column\":[6],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1815,\"width\":135},\"column\":[6],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":802,\"top\":1847,\"width\":135},\"column\":[6],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1884,\"width\":135},\"column\":[6],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":49,\"left\":937,\"top\":9,\"width\":136},\"column\":[7],\"row\":[1],\"word\":\"\u5398\u7c73\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":58,\"width\":136},\"column\":[7],\"row\":[2],\"word\":\"Diff\"},{\"rect\":{\"height\":47,\"left\":937,\"top\":92,\"width\":136},\"column\":[7],\"row\":[3],\"word\":\"(m3\/cm)\"},{\"rect\":{\"height\":47,\"left\":937,\"top\":139,\"width\":136},\"column\":[7],\"row\":[4],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":186,\"width\":136},\"column\":[7],\"row\":[5],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":220,\"width\":136},\"column\":[7],\"row\":[6],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":254,\"width\":136},\"column\":[7],\"row\":[7],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":286,\"width\":136},\"column\":[7],\"row\":[8],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":320,\"width\":136},\"column\":[7],\"row\":[9],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":354,\"width\":136},\"column\":[7],\"row\":[10],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":386,\"width\":136},\"column\":[7],\"row\":[11],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":420,\"width\":136},\"column\":[7],\"row\":[12],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":452,\"width\":136},\"column\":[7],\"row\":[13],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":484,\"width\":136},\"column\":[7],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":518,\"width\":136},\"column\":[7],\"row\":[15],\"word\":\"1145\"},{\"rect\":{\"height\":35,\"left\":937,\"top\":550,\"width\":136},\"column\":[7],\"row\":[16],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":585,\"width\":136},\"column\":[7],\"row\":[17],\"word\":\"1.146\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":617,\"width\":136},\"column\":[7],\"row\":[18],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":651,\"width\":136},\"column\":[7],\"row\":[19],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":683,\"width\":136},\"column\":[7],\"row\":[20],\"word\":\"1.14\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":717,\"width\":136},\"column\":[7],\"row\":[21],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":751,\"width\":136},\"column\":[7],\"row\":[22],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":783,\"width\":136},\"column\":[7],\"row\":[23],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":815,\"width\":136},\"column\":[7],\"row\":[24],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":847,\"width\":136},\"column\":[7],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":879,\"width\":136},\"column\":[7],\"row\":[26],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":911,\"width\":136},\"column\":[7],\"row\":[27],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":943,\"width\":136},\"column\":[7],\"row\":[28],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":975,\"width\":136},\"column\":[7],\"row\":[29],\"word\":\"1.160\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1007,\"width\":136},\"column\":[7],\"row\":[30],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1041,\"width\":136},\"column\":[7],\"row\":[31],\"word\":\"1.188\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1073,\"width\":136},\"column\":[7],\"row\":[32],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1107,\"width\":136},\"column\":[7],\"row\":[33],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1139,\"width\":136},\"column\":[7],\"row\":[34],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1171,\"width\":136},\"column\":[7],\"row\":[35],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1203,\"width\":136},\"column\":[7],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1235,\"width\":136},\"column\":[7],\"row\":[37],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1267,\"width\":136},\"column\":[7],\"row\":[38],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1299,\"width\":136},\"column\":[7],\"row\":[39],\"word\":\"1.188\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1331,\"width\":136},\"column\":[7],\"row\":[40],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1365,\"width\":136},\"column\":[7],\"row\":[41],\"word\":\"1.189\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1397,\"width\":136},\"column\":[7],\"row\":[42],\"word\":\"1.189\"},{\"rect\":{\"height\":30,\"left\":937,\"top\":1431,\"width\":136},\"column\":[7],\"row\":[43],\"word\":\"1.189\"},{\"rect\":{\"height\":30,\"left\":937,\"top\":1461,\"width\":136},\"column\":[7],\"row\":[44],\"word\":\"1.190\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1491,\"width\":136},\"column\":[7],\"row\":[45],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1525,\"width\":136},\"column\":[7],\"row\":[46],\"word\":\"1.127\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1557,\"width\":136},\"column\":[7],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":937,\"top\":1589,\"width\":136},\"column\":[7],\"row\":[48],\"word\":\"0.905\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1619,\"width\":136},\"column\":[7],\"row\":[49],\"word\":\"0.693\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1651,\"width\":136},\"column\":[7],\"row\":[50],\"word\":\"0.067\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1683,\"width\":136},\"column\":[7],\"row\":[51],\"word\":\"0.874\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1715,\"width\":136},\"column\":[7],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1749,\"width\":136},\"column\":[7],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1781,\"width\":136},\"column\":[7],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1815,\"width\":136},\"column\":[7],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":937,\"top\":1847,\"width\":136},\"column\":[7],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1884,\"width\":136},\"column\":[7],\"row\":[57],\"word\":\"\"}]}]}","ret_msg":"\u5df2\u5b8c\u6210","percent":100,"ret_code":3},"log_id":"1589341091046642"}
//test_json;

        $test_json = <<<test_json
{"result":{"result_data":"{\"form_num\":1,\"forms\":[{\"footer\":[{\"rect\":{\"top\":1916,\"left\":0,\"width\":1080,\"height\":1920},\"column\":[1],\"row\":[1],\"word\":\"\"}],\"header\":[{\"rect\":{\"top\":0,\"left\":0,\"width\":1080,\"height\":9},\"column\":[1],\"row\":[1],\"word\":\"\"}],\"body\":[{\"rect\":{\"height\":49,\"left\":0,\"top\":9,\"width\":134},\"column\":[1],\"row\":[1],\"word\":\"\u5b9e\u9ad8\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":58,\"width\":134},\"column\":[1],\"row\":[2],\"word\":\"Sounding\"},{\"rect\":{\"height\":47,\"left\":0,\"top\":92,\"width\":134},\"column\":[1],\"row\":[3],\"word\":\"(m)\"},{\"rect\":{\"height\":47,\"left\":0,\"top\":139,\"width\":134},\"column\":[1],\"row\":[4],\"word\":\"0.921\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":186,\"width\":134},\"column\":[1],\"row\":[5],\"word\":\"0.911\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":220,\"width\":134},\"column\":[1],\"row\":[6],\"word\":\"0.901\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":254,\"width\":134},\"column\":[1],\"row\":[7],\"word\":\"0.891\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":286,\"width\":134},\"column\":[1],\"row\":[8],\"word\":\"0.881\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":320,\"width\":134},\"column\":[1],\"row\":[9],\"word\":\"0.871\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":354,\"width\":134},\"column\":[1],\"row\":[10],\"word\":\"0.861\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":386,\"width\":134},\"column\":[1],\"row\":[11],\"word\":\"0.851\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":420,\"width\":134},\"column\":[1],\"row\":[12],\"word\":\"0.841\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":452,\"width\":134},\"column\":[1],\"row\":[13],\"word\":\"0.831\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":484,\"width\":134},\"column\":[1],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":518,\"width\":134},\"column\":[1],\"row\":[15],\"word\":\"0.821\"},{\"rect\":{\"height\":35,\"left\":0,\"top\":550,\"width\":134},\"column\":[1],\"row\":[16],\"word\":\"0.811\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":585,\"width\":134},\"column\":[1],\"row\":[17],\"word\":\"0.801\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":617,\"width\":134},\"column\":[1],\"row\":[18],\"word\":\"0.791\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":651,\"width\":134},\"column\":[1],\"row\":[19],\"word\":\"0.781\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":685,\"width\":134},\"column\":[1],\"row\":[20],\"word\":\"0.771\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":717,\"width\":134},\"column\":[1],\"row\":[21],\"word\":\"0.761\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":751,\"width\":134},\"column\":[1],\"row\":[22],\"word\":\"0.751\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":783,\"width\":134},\"column\":[1],\"row\":[23],\"word\":\"0.741\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":815,\"width\":134},\"column\":[1],\"row\":[24],\"word\":\"0.731\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":847,\"width\":134},\"column\":[1],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":879,\"width\":134},\"column\":[1],\"row\":[26],\"word\":\"0.721\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":911,\"width\":134},\"column\":[1],\"row\":[27],\"word\":\"0.711\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":943,\"width\":134},\"column\":[1],\"row\":[28],\"word\":\"0.701\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":975,\"width\":134},\"column\":[1],\"row\":[29],\"word\":\"0.691\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":1007,\"width\":134},\"column\":[1],\"row\":[30],\"word\":\"0.681\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1041,\"width\":134},\"column\":[1],\"row\":[31],\"word\":\"0.671\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":1073,\"width\":134},\"column\":[1],\"row\":[32],\"word\":\"0.661\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1107,\"width\":134},\"column\":[1],\"row\":[33],\"word\":\"0.651\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1139,\"width\":134},\"column\":[1],\"row\":[34],\"word\":\"0.641\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1171,\"width\":134},\"column\":[1],\"row\":[35],\"word\":\"0.631\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1203,\"width\":134},\"column\":[1],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1235,\"width\":134},\"column\":[1],\"row\":[37],\"word\":\"0.621\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1267,\"width\":134},\"column\":[1],\"row\":[38],\"word\":\"0.611\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1299,\"width\":134},\"column\":[1],\"row\":[39],\"word\":\"0.601\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1331,\"width\":134},\"column\":[1],\"row\":[40],\"word\":\"0.591\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":1363,\"width\":134},\"column\":[1],\"row\":[41],\"word\":\"0.581\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1397,\"width\":134},\"column\":[1],\"row\":[42],\"word\":\"0.571\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1429,\"width\":134},\"column\":[1],\"row\":[43],\"word\":\"0.561\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1461,\"width\":134},\"column\":[1],\"row\":[44],\"word\":\"0.551\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1493,\"width\":134},\"column\":[1],\"row\":[45],\"word\":\"*0.541\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1525,\"width\":134},\"column\":[1],\"row\":[46],\"word\":\"0.531\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1557,\"width\":134},\"column\":[1],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":0,\"top\":1589,\"width\":134},\"column\":[1],\"row\":[48],\"word\":\"0.521\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1619,\"width\":134},\"column\":[1],\"row\":[49],\"word\":\"0.511\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1651,\"width\":134},\"column\":[1],\"row\":[50],\"word\":\"0.501\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1683,\"width\":134},\"column\":[1],\"row\":[51],\"word\":\"0.491\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":1715,\"width\":134},\"column\":[1],\"row\":[52],\"word\":\"0.481\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1749,\"width\":134},\"column\":[1],\"row\":[53],\"word\":\"0.471\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1781,\"width\":134},\"column\":[1],\"row\":[54],\"word\":\"0.461\"},{\"rect\":{\"height\":34,\"left\":0,\"top\":1813,\"width\":134},\"column\":[1],\"row\":[55],\"word\":\"0.451\"},{\"rect\":{\"height\":37,\"left\":0,\"top\":1847,\"width\":134},\"column\":[1],\"row\":[56],\"word\":\"0.441\"},{\"rect\":{\"height\":32,\"left\":0,\"top\":1884,\"width\":134},\"column\":[1],\"row\":[57],\"word\":\"*0.431\"},{\"rect\":{\"height\":49,\"left\":134,\"top\":9,\"width\":133},\"column\":[2],\"row\":[1],\"word\":\"\u7a7a\u9ad8\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":58,\"width\":133},\"column\":[2],\"row\":[2],\"word\":\"Ullage\"},{\"rect\":{\"height\":47,\"left\":134,\"top\":92,\"width\":133},\"column\":[2],\"row\":[3],\"word\":\"(m\"},{\"rect\":{\"height\":47,\"left\":134,\"top\":139,\"width\":133},\"column\":[2],\"row\":[4],\"word\":\"5.000\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":186,\"width\":133},\"column\":[2],\"row\":[5],\"word\":\"5.010\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":220,\"width\":133},\"column\":[2],\"row\":[6],\"word\":\"5.020\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":254,\"width\":133},\"column\":[2],\"row\":[7],\"word\":\"5.030\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":286,\"width\":133},\"column\":[2],\"row\":[8],\"word\":\"5.040\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":320,\"width\":133},\"column\":[2],\"row\":[9],\"word\":\"5.050\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":354,\"width\":133},\"column\":[2],\"row\":[10],\"word\":\"5.060\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":386,\"width\":133},\"column\":[2],\"row\":[11],\"word\":\"5.070\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":420,\"width\":133},\"column\":[2],\"row\":[12],\"word\":\"5.080\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":452,\"width\":133},\"column\":[2],\"row\":[13],\"word\":\"5.090\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":484,\"width\":133},\"column\":[2],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":518,\"width\":133},\"column\":[2],\"row\":[15],\"word\":\"5.100\"},{\"rect\":{\"height\":35,\"left\":134,\"top\":550,\"width\":133},\"column\":[2],\"row\":[16],\"word\":\"5.110\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":585,\"width\":133},\"column\":[2],\"row\":[17],\"word\":\"5.120\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":617,\"width\":133},\"column\":[2],\"row\":[18],\"word\":\"5.130\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":651,\"width\":133},\"column\":[2],\"row\":[19],\"word\":\"5.140\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":685,\"width\":133},\"column\":[2],\"row\":[20],\"word\":\"5.150\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":717,\"width\":133},\"column\":[2],\"row\":[21],\"word\":\"5.160\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":751,\"width\":133},\"column\":[2],\"row\":[22],\"word\":\"5.170\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":783,\"width\":133},\"column\":[2],\"row\":[23],\"word\":\"5.180\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":815,\"width\":133},\"column\":[2],\"row\":[24],\"word\":\"5.190\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":847,\"width\":133},\"column\":[2],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":879,\"width\":133},\"column\":[2],\"row\":[26],\"word\":\"5.200\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":911,\"width\":133},\"column\":[2],\"row\":[27],\"word\":\"5.210\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":943,\"width\":133},\"column\":[2],\"row\":[28],\"word\":\"5.220\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":975,\"width\":133},\"column\":[2],\"row\":[29],\"word\":\"5.230\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1007,\"width\":133},\"column\":[2],\"row\":[30],\"word\":\"5.240\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1041,\"width\":133},\"column\":[2],\"row\":[31],\"word\":\"5.250\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1073,\"width\":133},\"column\":[2],\"row\":[32],\"word\":\"5.260\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1107,\"width\":133},\"column\":[2],\"row\":[33],\"word\":\"5.270\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1139,\"width\":133},\"column\":[2],\"row\":[34],\"word\":\"5.280\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1171,\"width\":133},\"column\":[2],\"row\":[35],\"word\":\"5.290\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1203,\"width\":133},\"column\":[2],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1235,\"width\":133},\"column\":[2],\"row\":[37],\"word\":\"5.300\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1267,\"width\":133},\"column\":[2],\"row\":[38],\"word\":\"5.310\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1299,\"width\":133},\"column\":[2],\"row\":[39],\"word\":\"5.320\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1331,\"width\":133},\"column\":[2],\"row\":[40],\"word\":\"5.330\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1363,\"width\":133},\"column\":[2],\"row\":[41],\"word\":\"5.340\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1397,\"width\":133},\"column\":[2],\"row\":[42],\"word\":\"5.350\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1429,\"width\":133},\"column\":[2],\"row\":[43],\"word\":\"5.360\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1461,\"width\":133},\"column\":[2],\"row\":[44],\"word\":\"5.370\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1493,\"width\":133},\"column\":[2],\"row\":[45],\"word\":\"5.380\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1525,\"width\":133},\"column\":[2],\"row\":[46],\"word\":\"5.390\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1557,\"width\":133},\"column\":[2],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":134,\"top\":1589,\"width\":133},\"column\":[2],\"row\":[48],\"word\":\"5.400\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1619,\"width\":133},\"column\":[2],\"row\":[49],\"word\":\"5.410\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1651,\"width\":133},\"column\":[2],\"row\":[50],\"word\":\"5.420\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1683,\"width\":133},\"column\":[2],\"row\":[51],\"word\":\"5.430\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1715,\"width\":133},\"column\":[2],\"row\":[52],\"word\":\"5.440\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1749,\"width\":133},\"column\":[2],\"row\":[53],\"word\":\"5.450\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1781,\"width\":133},\"column\":[2],\"row\":[54],\"word\":\"5.460\"},{\"rect\":{\"height\":34,\"left\":134,\"top\":1813,\"width\":133},\"column\":[2],\"row\":[55],\"word\":\"5.470\"},{\"rect\":{\"height\":37,\"left\":134,\"top\":1847,\"width\":133},\"column\":[2],\"row\":[56],\"word\":\"5.480\"},{\"rect\":{\"height\":32,\"left\":134,\"top\":1884,\"width\":133},\"column\":[2],\"row\":[57],\"word\":\"5.490\"},{\"rect\":{\"height\":49,\"left\":267,\"top\":9,\"width\":134},\"column\":[3],\"row\":[1],\"word\":\"\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":58,\"width\":134},\"column\":[3],\"row\":[2],\"word\":\"Capacity\"},{\"rect\":{\"height\":47,\"left\":267,\"top\":92,\"width\":134},\"column\":[3],\"row\":[3],\"word\":\"(m3)\"},{\"rect\":{\"height\":47,\"left\":267,\"top\":139,\"width\":134},\"column\":[3],\"row\":[4],\"word\":\"108.521\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":186,\"width\":134},\"column\":[3],\"row\":[5],\"word\":\"107.248\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":220,\"width\":134},\"column\":[3],\"row\":[6],\"word\":\"105.974\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":254,\"width\":134},\"column\":[3],\"row\":[7],\"word\":\"104.700\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":286,\"width\":134},\"column\":[3],\"row\":[8],\"word\":\"103.427\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":320,\"width\":134},\"column\":[3],\"row\":[9],\"word\":\"102.152\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":354,\"width\":134},\"column\":[3],\"row\":[10],\"word\":\"100.878\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":386,\"width\":134},\"column\":[3],\"row\":[11],\"word\":\"99.604\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":420,\"width\":134},\"column\":[3],\"row\":[12],\"word\":\"98.363\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":452,\"width\":134},\"column\":[3],\"row\":[13],\"word\":\"97.172\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":484,\"width\":134},\"column\":[3],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":518,\"width\":134},\"column\":[3],\"row\":[15],\"word\":\"95.986\"},{\"rect\":{\"height\":35,\"left\":267,\"top\":550,\"width\":134},\"column\":[3],\"row\":[16],\"word\":\"94.800\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":585,\"width\":134},\"column\":[3],\"row\":[17],\"word\":\"93.615\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":617,\"width\":134},\"column\":[3],\"row\":[18],\"word\":\"92.430\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":651,\"width\":134},\"column\":[3],\"row\":[19],\"word\":\"91.243\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":685,\"width\":134},\"column\":[3],\"row\":[20],\"word\":\"90.057\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":717,\"width\":134},\"column\":[3],\"row\":[21],\"word\":\"88.872\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":751,\"width\":134},\"column\":[3],\"row\":[22],\"word\":\"87.687\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":783,\"width\":134},\"column\":[3],\"row\":[23],\"word\":\"86.502\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":815,\"width\":134},\"column\":[3],\"row\":[24],\"word\":\"85.315\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":847,\"width\":134},\"column\":[3],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":879,\"width\":134},\"column\":[3],\"row\":[26],\"word\":\"84.130\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":911,\"width\":134},\"column\":[3],\"row\":[27],\"word\":\"82.944\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":943,\"width\":134},\"column\":[3],\"row\":[28],\"word\":\"81.758\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":975,\"width\":134},\"column\":[3],\"row\":[29],\"word\":\"80.572\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1007,\"width\":134},\"column\":[3],\"row\":[30],\"word\":\"79.386\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1041,\"width\":134},\"column\":[3],\"row\":[31],\"word\":\"78.201\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1073,\"width\":134},\"column\":[3],\"row\":[32],\"word\":\"77.016\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1107,\"width\":134},\"column\":[3],\"row\":[33],\"word\":\"75.829\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1139,\"width\":134},\"column\":[3],\"row\":[34],\"word\":\"74.643\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1171,\"width\":134},\"column\":[3],\"row\":[35],\"word\":\"73.458\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1203,\"width\":134},\"column\":[3],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1235,\"width\":134},\"column\":[3],\"row\":[37],\"word\":\"72.272\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1267,\"width\":134},\"column\":[3],\"row\":[38],\"word\":\"71.086\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1299,\"width\":134},\"column\":[3],\"row\":[39],\"word\":\"69.899\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1331,\"width\":134},\"column\":[3],\"row\":[40],\"word\":\"68.713\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1363,\"width\":134},\"column\":[3],\"row\":[41],\"word\":\"67.528\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1397,\"width\":134},\"column\":[3],\"row\":[42],\"word\":\"66.342\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1429,\"width\":134},\"column\":[3],\"row\":[43],\"word\":\"65.155\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1461,\"width\":134},\"column\":[3],\"row\":[44],\"word\":\"63.969\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1493,\"width\":134},\"column\":[3],\"row\":[45],\"word\":\"62.783\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1525,\"width\":134},\"column\":[3],\"row\":[46],\"word\":\"61.597\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1557,\"width\":134},\"column\":[3],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":267,\"top\":1589,\"width\":134},\"column\":[3],\"row\":[48],\"word\":\"60.425\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1619,\"width\":134},\"column\":[3],\"row\":[49],\"word\":\"59.278\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1651,\"width\":134},\"column\":[3],\"row\":[50],\"word\":\"58.134\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1683,\"width\":134},\"column\":[3],\"row\":[51],\"word\":\"56.991\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1715,\"width\":134},\"column\":[3],\"row\":[52],\"word\":\"55.846\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1749,\"width\":134},\"column\":[3],\"row\":[53],\"word\":\"54.702\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1781,\"width\":134},\"column\":[3],\"row\":[54],\"word\":\"53.559\"},{\"rect\":{\"height\":34,\"left\":267,\"top\":1813,\"width\":134},\"column\":[3],\"row\":[55],\"word\":\"52.415\"},{\"rect\":{\"height\":37,\"left\":267,\"top\":1847,\"width\":134},\"column\":[3],\"row\":[56],\"word\":\"51.270\"},{\"rect\":{\"height\":32,\"left\":267,\"top\":1884,\"width\":134},\"column\":[3],\"row\":[57],\"word\":\"50.126\"},{\"rect\":{\"height\":49,\"left\":401,\"top\":9,\"width\":132},\"column\":[4],\"row\":[1],\"word\":\"\u5398\u7c73\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":58,\"width\":132},\"column\":[4],\"row\":[2],\"word\":\"Diff\"},{\"rect\":{\"height\":47,\"left\":401,\"top\":92,\"width\":132},\"column\":[4],\"row\":[3],\"word\":\"(m3\/cm)\"},{\"rect\":{\"height\":47,\"left\":401,\"top\":139,\"width\":132},\"column\":[4],\"row\":[4],\"word\":\"1.273\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":186,\"width\":132},\"column\":[4],\"row\":[5],\"word\":\"1.274\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":220,\"width\":132},\"column\":[4],\"row\":[6],\"word\":\"1.274\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":254,\"width\":132},\"column\":[4],\"row\":[7],\"word\":\"1.273\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":286,\"width\":132},\"column\":[4],\"row\":[8],\"word\":\"1.275\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":320,\"width\":132},\"column\":[4],\"row\":[9],\"word\":\"1.274\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":354,\"width\":132},\"column\":[4],\"row\":[10],\"word\":\"1.274\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":386,\"width\":132},\"column\":[4],\"row\":[11],\"word\":\"1.241\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":420,\"width\":132},\"column\":[4],\"row\":[12],\"word\":\"1.191\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":452,\"width\":132},\"column\":[4],\"row\":[13],\"word\":\"1.186\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":484,\"width\":132},\"column\":[4],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":518,\"width\":132},\"column\":[4],\"row\":[15],\"word\":\"1.186*\"},{\"rect\":{\"height\":35,\"left\":401,\"top\":550,\"width\":132},\"column\":[4],\"row\":[16],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":585,\"width\":132},\"column\":[4],\"row\":[17],\"word\":\"1.185\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":617,\"width\":132},\"column\":[4],\"row\":[18],\"word\":\"1.187\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":651,\"width\":132},\"column\":[4],\"row\":[19],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":685,\"width\":132},\"column\":[4],\"row\":[20],\"word\":\"1.185\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":717,\"width\":132},\"column\":[4],\"row\":[21],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":751,\"width\":132},\"column\":[4],\"row\":[22],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":783,\"width\":132},\"column\":[4],\"row\":[23],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":815,\"width\":132},\"column\":[4],\"row\":[24],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":847,\"width\":132},\"column\":[4],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":879,\"width\":132},\"column\":[4],\"row\":[26],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":911,\"width\":132},\"column\":[4],\"row\":[27],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":943,\"width\":132},\"column\":[4],\"row\":[28],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":975,\"width\":132},\"column\":[4],\"row\":[29],\"word\":\"1.186\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1007,\"width\":132},\"column\":[4],\"row\":[30],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1041,\"width\":132},\"column\":[4],\"row\":[31],\"word\":\"1.185\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1073,\"width\":132},\"column\":[4],\"row\":[32],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1107,\"width\":132},\"column\":[4],\"row\":[33],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1139,\"width\":132},\"column\":[4],\"row\":[34],\"word\":\"1.185\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1171,\"width\":132},\"column\":[4],\"row\":[35],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1203,\"width\":132},\"column\":[4],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1235,\"width\":132},\"column\":[4],\"row\":[37],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1267,\"width\":132},\"column\":[4],\"row\":[38],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1299,\"width\":132},\"column\":[4],\"row\":[39],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1331,\"width\":132},\"column\":[4],\"row\":[40],\"word\":\"1.185\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1363,\"width\":132},\"column\":[4],\"row\":[41],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1397,\"width\":132},\"column\":[4],\"row\":[42],\"word\":\"1.187\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1429,\"width\":132},\"column\":[4],\"row\":[43],\"word\":\"1.186*\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1461,\"width\":132},\"column\":[4],\"row\":[44],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1493,\"width\":132},\"column\":[4],\"row\":[45],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1525,\"width\":132},\"column\":[4],\"row\":[46],\"word\":\"1.172\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1557,\"width\":132},\"column\":[4],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":401,\"top\":1589,\"width\":132},\"column\":[4],\"row\":[48],\"word\":\"1.147\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1619,\"width\":132},\"column\":[4],\"row\":[49],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1651,\"width\":132},\"column\":[4],\"row\":[50],\"word\":\"1.143\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1683,\"width\":132},\"column\":[4],\"row\":[51],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1715,\"width\":132},\"column\":[4],\"row\":[52],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1749,\"width\":132},\"column\":[4],\"row\":[53],\"word\":\"1.143\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1781,\"width\":132},\"column\":[4],\"row\":[54],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":401,\"top\":1813,\"width\":132},\"column\":[4],\"row\":[55],\"word\":\"1.145\"},{\"rect\":{\"height\":37,\"left\":401,\"top\":1847,\"width\":132},\"column\":[4],\"row\":[56],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":401,\"top\":1884,\"width\":132},\"column\":[4],\"row\":[57],\"word\":\"1.144\"},{\"rect\":{\"height\":49,\"left\":533,\"top\":9,\"width\":135},\"column\":[5],\"row\":[1],\"word\":\"\u5b9e\u9ad8\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":58,\"width\":135},\"column\":[5],\"row\":[2],\"word\":\"Sounding\"},{\"rect\":{\"height\":47,\"left\":533,\"top\":92,\"width\":135},\"column\":[5],\"row\":[3],\"word\":\"*(m)\"},{\"rect\":{\"height\":47,\"left\":533,\"top\":139,\"width\":135},\"column\":[5],\"row\":[4],\"word\":\"0421\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":186,\"width\":135},\"column\":[5],\"row\":[5],\"word\":\"0.411\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":220,\"width\":135},\"column\":[5],\"row\":[6],\"word\":\"0.401\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":254,\"width\":135},\"column\":[5],\"row\":[7],\"word\":\"0.391\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":286,\"width\":135},\"column\":[5],\"row\":[8],\"word\":\"0.381\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":320,\"width\":135},\"column\":[5],\"row\":[9],\"word\":\"0.371\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":354,\"width\":135},\"column\":[5],\"row\":[10],\"word\":\"0.361\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":386,\"width\":135},\"column\":[5],\"row\":[11],\"word\":\"*0.351\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":420,\"width\":135},\"column\":[5],\"row\":[12],\"word\":\"*0.341\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":452,\"width\":135},\"column\":[5],\"row\":[13],\"word\":\"0.331\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":484,\"width\":135},\"column\":[5],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":518,\"width\":135},\"column\":[5],\"row\":[15],\"word\":\"0.321\"},{\"rect\":{\"height\":35,\"left\":533,\"top\":550,\"width\":135},\"column\":[5],\"row\":[16],\"word\":\"*0.311\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":585,\"width\":135},\"column\":[5],\"row\":[17],\"word\":\"0.301\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":617,\"width\":135},\"column\":[5],\"row\":[18],\"word\":\"0.291\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":651,\"width\":135},\"column\":[5],\"row\":[19],\"word\":\"0.281\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":685,\"width\":135},\"column\":[5],\"row\":[20],\"word\":\"0.271\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":717,\"width\":135},\"column\":[5],\"row\":[21],\"word\":\"0.261\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":751,\"width\":135},\"column\":[5],\"row\":[22],\"word\":\"0.251\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":783,\"width\":135},\"column\":[5],\"row\":[23],\"word\":\"0.241\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":815,\"width\":135},\"column\":[5],\"row\":[24],\"word\":\"0.231\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":847,\"width\":135},\"column\":[5],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":879,\"width\":135},\"column\":[5],\"row\":[26],\"word\":\"0.221\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":911,\"width\":135},\"column\":[5],\"row\":[27],\"word\":\"*0.211\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":943,\"width\":135},\"column\":[5],\"row\":[28],\"word\":\"*0.201\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":975,\"width\":135},\"column\":[5],\"row\":[29],\"word\":\"*0.191\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1007,\"width\":135},\"column\":[5],\"row\":[30],\"word\":\"*0.181\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1041,\"width\":135},\"column\":[5],\"row\":[31],\"word\":\"0.171\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1073,\"width\":135},\"column\":[5],\"row\":[32],\"word\":\"0.161\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1107,\"width\":135},\"column\":[5],\"row\":[33],\"word\":\"0.151\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1139,\"width\":135},\"column\":[5],\"row\":[34],\"word\":\"0.141\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1171,\"width\":135},\"column\":[5],\"row\":[35],\"word\":\"0.131\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1203,\"width\":135},\"column\":[5],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1235,\"width\":135},\"column\":[5],\"row\":[37],\"word\":\"0.121\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1267,\"width\":135},\"column\":[5],\"row\":[38],\"word\":\"0.11\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1299,\"width\":135},\"column\":[5],\"row\":[39],\"word\":\"0.101\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1331,\"width\":135},\"column\":[5],\"row\":[40],\"word\":\"0.091\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1363,\"width\":135},\"column\":[5],\"row\":[41],\"word\":\"*0.081\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1397,\"width\":135},\"column\":[5],\"row\":[42],\"word\":\"0.071\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1429,\"width\":135},\"column\":[5],\"row\":[43],\"word\":\"0.061\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1461,\"width\":135},\"column\":[5],\"row\":[44],\"word\":\"0.051\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1493,\"width\":135},\"column\":[5],\"row\":[45],\"word\":\"*0.041\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1525,\"width\":135},\"column\":[5],\"row\":[46],\"word\":\"*0.031\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1557,\"width\":135},\"column\":[5],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":533,\"top\":1589,\"width\":135},\"column\":[5],\"row\":[48],\"word\":\"0.021\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1619,\"width\":135},\"column\":[5],\"row\":[49],\"word\":\"0.011\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1651,\"width\":135},\"column\":[5],\"row\":[50],\"word\":\"0.001\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1683,\"width\":135},\"column\":[5],\"row\":[51],\"word\":\"0.000\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1715,\"width\":135},\"column\":[5],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1749,\"width\":135},\"column\":[5],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1781,\"width\":135},\"column\":[5],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":533,\"top\":1813,\"width\":135},\"column\":[5],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":533,\"top\":1847,\"width\":135},\"column\":[5],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":533,\"top\":1884,\"width\":135},\"column\":[5],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":49,\"left\":668,\"top\":9,\"width\":134},\"column\":[6],\"row\":[1],\"word\":\"\u7a7a\u9ad8\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":58,\"width\":134},\"column\":[6],\"row\":[2],\"word\":\"Ullage\"},{\"rect\":{\"height\":47,\"left\":668,\"top\":92,\"width\":134},\"column\":[6],\"row\":[3],\"word\":\"(m)\"},{\"rect\":{\"height\":47,\"left\":668,\"top\":139,\"width\":134},\"column\":[6],\"row\":[4],\"word\":\"5.500\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":186,\"width\":134},\"column\":[6],\"row\":[5],\"word\":\"5.510\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":220,\"width\":134},\"column\":[6],\"row\":[6],\"word\":\"5.520\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":254,\"width\":134},\"column\":[6],\"row\":[7],\"word\":\"5.530\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":286,\"width\":134},\"column\":[6],\"row\":[8],\"word\":\"5.540\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":320,\"width\":134},\"column\":[6],\"row\":[9],\"word\":\"5.550\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":354,\"width\":134},\"column\":[6],\"row\":[10],\"word\":\"5.560\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":386,\"width\":134},\"column\":[6],\"row\":[11],\"word\":\"5.570\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":420,\"width\":134},\"column\":[6],\"row\":[12],\"word\":\"5.580\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":452,\"width\":134},\"column\":[6],\"row\":[13],\"word\":\"5.590\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":484,\"width\":134},\"column\":[6],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":518,\"width\":134},\"column\":[6],\"row\":[15],\"word\":\"5.600\"},{\"rect\":{\"height\":35,\"left\":668,\"top\":550,\"width\":134},\"column\":[6],\"row\":[16],\"word\":\"5.610\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":585,\"width\":134},\"column\":[6],\"row\":[17],\"word\":\"5.620\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":617,\"width\":134},\"column\":[6],\"row\":[18],\"word\":\"5.630\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":651,\"width\":134},\"column\":[6],\"row\":[19],\"word\":\"5.640\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":685,\"width\":134},\"column\":[6],\"row\":[20],\"word\":\"5.650\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":717,\"width\":134},\"column\":[6],\"row\":[21],\"word\":\"5.660\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":751,\"width\":134},\"column\":[6],\"row\":[22],\"word\":\"5.670\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":783,\"width\":134},\"column\":[6],\"row\":[23],\"word\":\"5.680\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":815,\"width\":134},\"column\":[6],\"row\":[24],\"word\":\"5.690\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":847,\"width\":134},\"column\":[6],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":879,\"width\":134},\"column\":[6],\"row\":[26],\"word\":\"5.700\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":911,\"width\":134},\"column\":[6],\"row\":[27],\"word\":\"5.710\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":943,\"width\":134},\"column\":[6],\"row\":[28],\"word\":\"5.720\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":975,\"width\":134},\"column\":[6],\"row\":[29],\"word\":\"5.730\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1007,\"width\":134},\"column\":[6],\"row\":[30],\"word\":\"5.740\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1041,\"width\":134},\"column\":[6],\"row\":[31],\"word\":\"5.750\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1073,\"width\":134},\"column\":[6],\"row\":[32],\"word\":\"5.760\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1107,\"width\":134},\"column\":[6],\"row\":[33],\"word\":\"5.770\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1139,\"width\":134},\"column\":[6],\"row\":[34],\"word\":\"5.780\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1171,\"width\":134},\"column\":[6],\"row\":[35],\"word\":\"5.790\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1203,\"width\":134},\"column\":[6],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1235,\"width\":134},\"column\":[6],\"row\":[37],\"word\":\"5.800\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1267,\"width\":134},\"column\":[6],\"row\":[38],\"word\":\"5.810\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1299,\"width\":134},\"column\":[6],\"row\":[39],\"word\":\"5.820\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1331,\"width\":134},\"column\":[6],\"row\":[40],\"word\":\"5.830\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1363,\"width\":134},\"column\":[6],\"row\":[41],\"word\":\"5.840\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1397,\"width\":134},\"column\":[6],\"row\":[42],\"word\":\"5.850\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1429,\"width\":134},\"column\":[6],\"row\":[43],\"word\":\"5.860\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1461,\"width\":134},\"column\":[6],\"row\":[44],\"word\":\"5.870\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1493,\"width\":134},\"column\":[6],\"row\":[45],\"word\":\"5.880\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1525,\"width\":134},\"column\":[6],\"row\":[46],\"word\":\"5.890\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1557,\"width\":134},\"column\":[6],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":668,\"top\":1589,\"width\":134},\"column\":[6],\"row\":[48],\"word\":\"5.900\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1619,\"width\":134},\"column\":[6],\"row\":[49],\"word\":\"5.910\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1651,\"width\":134},\"column\":[6],\"row\":[50],\"word\":\"5.920\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1683,\"width\":134},\"column\":[6],\"row\":[51],\"word\":\"5.921\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1715,\"width\":134},\"column\":[6],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1749,\"width\":134},\"column\":[6],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1781,\"width\":134},\"column\":[6],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":668,\"top\":1813,\"width\":134},\"column\":[6],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":668,\"top\":1847,\"width\":134},\"column\":[6],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":668,\"top\":1884,\"width\":134},\"column\":[6],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":49,\"left\":802,\"top\":9,\"width\":135},\"column\":[7],\"row\":[1],\"word\":\"\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":58,\"width\":135},\"column\":[7],\"row\":[2],\"word\":\"Capacity\"},{\"rect\":{\"height\":47,\"left\":802,\"top\":92,\"width\":135},\"column\":[7],\"row\":[3],\"word\":\"(m3)\"},{\"rect\":{\"height\":47,\"left\":802,\"top\":139,\"width\":135},\"column\":[7],\"row\":[4],\"word\":\"48.982\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":186,\"width\":135},\"column\":[7],\"row\":[5],\"word\":\"47.838\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":220,\"width\":135},\"column\":[7],\"row\":[6],\"word\":\"46.694\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":254,\"width\":135},\"column\":[7],\"row\":[7],\"word\":\"45.549\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":286,\"width\":135},\"column\":[7],\"row\":[8],\"word\":\"44.404\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":320,\"width\":135},\"column\":[7],\"row\":[9],\"word\":\"43.260\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":354,\"width\":135},\"column\":[7],\"row\":[10],\"word\":\"42.116\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":386,\"width\":135},\"column\":[7],\"row\":[11],\"word\":\"40.971\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":420,\"width\":135},\"column\":[7],\"row\":[12],\"word\":\"39.826\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":452,\"width\":135},\"column\":[7],\"row\":[13],\"word\":\"38.682\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":484,\"width\":135},\"column\":[7],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":518,\"width\":135},\"column\":[7],\"row\":[15],\"word\":\"37.537\"},{\"rect\":{\"height\":35,\"left\":802,\"top\":550,\"width\":135},\"column\":[7],\"row\":[16],\"word\":\"36.392\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":585,\"width\":135},\"column\":[7],\"row\":[17],\"word\":\"35.248\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":617,\"width\":135},\"column\":[7],\"row\":[18],\"word\":\"34.102\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":651,\"width\":135},\"column\":[7],\"row\":[19],\"word\":\"32.957\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":685,\"width\":135},\"column\":[7],\"row\":[20],\"word\":\"31.812\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":717,\"width\":135},\"column\":[7],\"row\":[21],\"word\":\"30.668\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":751,\"width\":135},\"column\":[7],\"row\":[22],\"word\":\"29.522\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":783,\"width\":135},\"column\":[7],\"row\":[23],\"word\":\"28.376\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":815,\"width\":135},\"column\":[7],\"row\":[24],\"word\":\"27.232\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":847,\"width\":135},\"column\":[7],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":879,\"width\":135},\"column\":[7],\"row\":[26],\"word\":\"26.086\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":911,\"width\":135},\"column\":[7],\"row\":[27],\"word\":\"24.941\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":943,\"width\":135},\"column\":[7],\"row\":[28],\"word\":\"23.796\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":975,\"width\":135},\"column\":[7],\"row\":[29],\"word\":\"22.650\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1007,\"width\":135},\"column\":[7],\"row\":[30],\"word\":\"21.490\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1041,\"width\":135},\"column\":[7],\"row\":[31],\"word\":\"20.304\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1073,\"width\":135},\"column\":[7],\"row\":[32],\"word\":\"19.116\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1107,\"width\":135},\"column\":[7],\"row\":[33],\"word\":\"17.928\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1139,\"width\":135},\"column\":[7],\"row\":[34],\"word\":\"16.740\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1171,\"width\":135},\"column\":[7],\"row\":[35],\"word\":\"15.551\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1203,\"width\":135},\"column\":[7],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1235,\"width\":135},\"column\":[7],\"row\":[37],\"word\":\"14.363\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1267,\"width\":135},\"column\":[7],\"row\":[38],\"word\":\"13.175\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1299,\"width\":135},\"column\":[7],\"row\":[39],\"word\":\"11.986\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1331,\"width\":135},\"column\":[7],\"row\":[40],\"word\":\"10.798\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1363,\"width\":135},\"column\":[7],\"row\":[41],\"word\":\"9.609\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1397,\"width\":135},\"column\":[7],\"row\":[42],\"word\":\"8.420\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1429,\"width\":135},\"column\":[7],\"row\":[43],\"word\":\"7.231\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1461,\"width\":135},\"column\":[7],\"row\":[44],\"word\":\"6.042\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1493,\"width\":135},\"column\":[7],\"row\":[45],\"word\":\"4.852\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1525,\"width\":135},\"column\":[7],\"row\":[46],\"word\":\"3.666\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1557,\"width\":135},\"column\":[7],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":802,\"top\":1589,\"width\":135},\"column\":[7],\"row\":[48],\"word\":\"2.539\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1619,\"width\":135},\"column\":[7],\"row\":[49],\"word\":\"1.634\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1651,\"width\":135},\"column\":[7],\"row\":[50],\"word\":\"0.941\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1683,\"width\":135},\"column\":[7],\"row\":[51],\"word\":\"0.874\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1715,\"width\":135},\"column\":[7],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1749,\"width\":135},\"column\":[7],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1781,\"width\":135},\"column\":[7],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":802,\"top\":1813,\"width\":135},\"column\":[7],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":802,\"top\":1847,\"width\":135},\"column\":[7],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":802,\"top\":1884,\"width\":135},\"column\":[7],\"row\":[57],\"word\":\"\"},{\"rect\":{\"height\":49,\"left\":937,\"top\":9,\"width\":136},\"column\":[8],\"row\":[1],\"word\":\"\u5398\u7c73\u5bb9\u91cf\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":58,\"width\":136},\"column\":[8],\"row\":[2],\"word\":\"Diff\"},{\"rect\":{\"height\":47,\"left\":937,\"top\":92,\"width\":136},\"column\":[8],\"row\":[3],\"word\":\"(m3\/cm)\"},{\"rect\":{\"height\":47,\"left\":937,\"top\":139,\"width\":136},\"column\":[8],\"row\":[4],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":186,\"width\":136},\"column\":[8],\"row\":[5],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":220,\"width\":136},\"column\":[8],\"row\":[6],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":254,\"width\":136},\"column\":[8],\"row\":[7],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":286,\"width\":136},\"column\":[8],\"row\":[8],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":320,\"width\":136},\"column\":[8],\"row\":[9],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":354,\"width\":136},\"column\":[8],\"row\":[10],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":386,\"width\":136},\"column\":[8],\"row\":[11],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":420,\"width\":136},\"column\":[8],\"row\":[12],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":452,\"width\":136},\"column\":[8],\"row\":[13],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":484,\"width\":136},\"column\":[8],\"row\":[14],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":518,\"width\":136},\"column\":[8],\"row\":[15],\"word\":\"1.145\"},{\"rect\":{\"height\":35,\"left\":937,\"top\":550,\"width\":136},\"column\":[8],\"row\":[16],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":585,\"width\":136},\"column\":[8],\"row\":[17],\"word\":\"1.146\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":617,\"width\":136},\"column\":[8],\"row\":[18],\"word\":\"1.145\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":651,\"width\":136},\"column\":[8],\"row\":[19],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":685,\"width\":136},\"column\":[8],\"row\":[20],\"word\":\"1.144\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":717,\"width\":136},\"column\":[8],\"row\":[21],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":751,\"width\":136},\"column\":[8],\"row\":[22],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":783,\"width\":136},\"column\":[8],\"row\":[23],\"word\":\"1.144\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":815,\"width\":136},\"column\":[8],\"row\":[24],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":847,\"width\":136},\"column\":[8],\"row\":[25],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":879,\"width\":136},\"column\":[8],\"row\":[26],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":911,\"width\":136},\"column\":[8],\"row\":[27],\"word\":\"1.145\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":943,\"width\":136},\"column\":[8],\"row\":[28],\"word\":\"1.146\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":975,\"width\":136},\"column\":[8],\"row\":[29],\"word\":\"1.160\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1007,\"width\":136},\"column\":[8],\"row\":[30],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1041,\"width\":136},\"column\":[8],\"row\":[31],\"word\":\"1.188\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1073,\"width\":136},\"column\":[8],\"row\":[32],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1107,\"width\":136},\"column\":[8],\"row\":[33],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1139,\"width\":136},\"column\":[8],\"row\":[34],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1171,\"width\":136},\"column\":[8],\"row\":[35],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1203,\"width\":136},\"column\":[8],\"row\":[36],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1235,\"width\":136},\"column\":[8],\"row\":[37],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1267,\"width\":136},\"column\":[8],\"row\":[38],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1299,\"width\":136},\"column\":[8],\"row\":[39],\"word\":\"1.188\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1331,\"width\":136},\"column\":[8],\"row\":[40],\"word\":\"1.189\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1363,\"width\":136},\"column\":[8],\"row\":[41],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1397,\"width\":136},\"column\":[8],\"row\":[42],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1429,\"width\":136},\"column\":[8],\"row\":[43],\"word\":\"1.189\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1461,\"width\":136},\"column\":[8],\"row\":[44],\"word\":\"1.190\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1493,\"width\":136},\"column\":[8],\"row\":[45],\"word\":\"1.186\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1525,\"width\":136},\"column\":[8],\"row\":[46],\"word\":\"1.127\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1557,\"width\":136},\"column\":[8],\"row\":[47],\"word\":\"\"},{\"rect\":{\"height\":30,\"left\":937,\"top\":1589,\"width\":136},\"column\":[8],\"row\":[48],\"word\":\"0.905\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1619,\"width\":136},\"column\":[8],\"row\":[49],\"word\":\"0.693\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1651,\"width\":136},\"column\":[8],\"row\":[50],\"word\":\"0.067\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1683,\"width\":136},\"column\":[8],\"row\":[51],\"word\":\"0.874\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1715,\"width\":136},\"column\":[8],\"row\":[52],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1749,\"width\":136},\"column\":[8],\"row\":[53],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1781,\"width\":136},\"column\":[8],\"row\":[54],\"word\":\"\"},{\"rect\":{\"height\":34,\"left\":937,\"top\":1813,\"width\":136},\"column\":[8],\"row\":[55],\"word\":\"\"},{\"rect\":{\"height\":37,\"left\":937,\"top\":1847,\"width\":136},\"column\":[8],\"row\":[56],\"word\":\"\"},{\"rect\":{\"height\":32,\"left\":937,\"top\":1884,\"width\":136},\"column\":[8],\"row\":[57],\"word\":\"\"}]}]}","ret_msg":"\u5df2\u5b8c\u6210","percent":100,"ret_code":3},"log_id":"1589349060129827"}
test_json;

        $test_arr = json_decode($test_json, true);
        $test_date_arr = json_decode($test_arr['result']['result_data'], true);
        //获取数据。如果收到两个数据
//        print_r($test_date_arr['forms']);
        $table_datas = array();

        $max_column = 0;
        $max_row = 0;

//        $edition = 0;

        $draft_up = 0;
        $draft_down = 0;

        $ullage_up = 0;
        $ullage_down = 0;


        //空高数组
        $ullage_arr = array();
        //序列化数组，行内包含单元格
        foreach ($test_date_arr['forms'] as $key => $form_data) {
            foreach ($form_data['body'] as $k => $v) {
                $column = $v['column'][0];
                //更新最大列
                $max_column = $column > $max_column ? $column : $max_column;
                $row = $v['row'][0];
                //更新最大行
                $max_row = $row > $max_row ? $row : $max_row;
                //如果不存在行数组，自动创建
                if (!isset($table_datas[$row])) $table_datas[$row] = array();
                $v['word'] = preg_replace('/\*/', '', $v['word']);
                if (!strpos($v['word'], '.') and $v['word'] != "" and $row > 3) $v['word'] = intval($v['word']) / 1000;
                $table_datas[$row][$column] = $v['word'];
                //获取空高列表
                if (($column == 2 or $column == 6) and $row > 3 and $v['word'] != "") {
                    //处理一下数字
                    $now_ullage = (float)$v['word'];
                    //去除空白列如果内容为空的话
                    if ($now_ullage != 0) {
                        $ullage_arr[] = array('val' => $now_ullage, 'row' => $row, 'column' => $column);
                    }
                }
            }
        }


        //获取空高区间
        $count_ullage = count($ullage_arr);
        for ($i = 0; $i < $count_ullage; $i++) {
            $row = $ullage_arr[$i]['row'];
            $column = $ullage_arr[$i]['column'];
            $now_ullage = $ullage_arr[$i]['val'];
            if ($ullage_up == 0) {
                if ($i > 0) {
                    if ($ullage == $now_ullage) {
                        $ullage_up = $row;
                        $ullage_down = $row;
                        $draft_up = $column;
                        $draft_down = $column;
                    } else {
                        $pre_arr = $ullage_arr[$i - 1];
                        if ($pre_arr['val'] < $ullage and $now_ullage > $ullage) {
                            $ullage_up = $pre_arr['row'];
                            $ullage_down = $row;
                            $draft_up = $pre_arr['column'];
                            $draft_down = $column;
                        }
                    }
                } else {
                    if ($ullage == $now_ullage) {
                        $ullage_up = $row;
                        $ullage_down = $row;
                        $draft_up = $column;
                        $draft_down = $column;
                    }
                }
            }
        }

        /*
         * 判断极值
         */
        if ($ullage_up == 0) {
            $first = reset($ullage_arr);
            $last = end($ullage_arr);
            if ($first['val'] > $ullage) {
                $ullage_up = $first['row'];
                $ullage_down = $first['row'];
            }
            if ($last['val'] < $ullage) {
                $ullage_up = $last['row'];
                $ullage_down = $last['row'];
            }
        }

        $assign = array(
            'table' => $table_datas,
            'max_column' => $max_column,
            'max_row' => $max_row,
            'ullage_up' => $ullage_up,
            'ullage_down' => $ullage_down,
            'draft_up' => $draft_up,
            'draft_down' => $draft_down,
            'ullage' => $ullage,
            'ullage_arr' => $ullage_arr,
        );
//        exit(json_encode($assign));
        $this->assign($assign);
        $this->display();
    }

    public function send_sms()
    {
//        die(date('Y-m-d H:i:s',time()));
        $phone = I('get.phone');
        $sms = new \Common\Model\SmsVerifyCodeModel();
        print_r($sms->sendSms($phone));
    }

//    public function add_sms(){
//        $sms = new \Common\Model\SmsVerifyCodeModel();
//        print_r($sms->addSmsResult());
//    }
    public function add_trim_data()
    {
        $data = I('post.');
        $result = new \Common\Model\WorkModel();
        echo $result->cumulative_trim_data($data);
    }

    public function get_trim_data()
    {
        $result = new \Common\Model\WorkModel();
        $this->ajaxReturn($result->get_cumulative_trim_data(I('post.cabinid'), I('post.ullage'), I('post.draft')));
    }

    public function get_capacity_data()
    {
        $result = new \Common\Model\WorkModel();
        print_r($result->get_cumulative_capacity_data(I('post.cabinid'), I('post.ullage')));
    }

    public function get_All_value()
    {
        echo json_encode($this->get());
    }

    public function te()
    {
        $aa = array(
            '1.4' => '488',
        );
        print_r($aa);
    }

    public function get_cum_table()
    {
        $shipid = intval(I('post.shipid'));
//            $cabinid = intval(I('post.cabinid'));
        $type = I('post.tname');
        $ship = new \Common\Model\ShipFormModel();
        /*            $types = array(
                        'rl'=>array('table'=>'cumulative_capacity_data','book'=>1),
                        'zx'=>array('table'=>'cumulative_trim_data','book'=>1),
                        'rl_1'=>array('table'=>'cumulative_capacity_data','book'=>2),
                        'zx_1'=>array('table'=>'cumulative_trim_data','book'=>2),
                    );*/
//            $types = array(
//                'rl'=>array('field'=>'cumulative_capacity_data'),
//                'zx'=>array('field'=>'cumulative_trim_data'),
//                'rl_1'=>array('field'=>'cumulative_capacity_data'),
//                'zx_1'=>array('field'=>'cumulative_trim_data'),
//            );
        $cum_data = $ship->field('tripbystern,trimcorrection,trimcorrection1,suanfa')->where(array('id' => $shipid))->find();
        $tvalue = "";
        if ($type == "zx" or $type == "zx_1") {
            $tvalue = "<thead><th>空高</th><th>容量</th></thead>";
            for ($i = 0; $i < 50; $i++) {
                $tvalue .= "<tr><td><input style='border:0;' name='data[" . $i . "][ullage]'></td><td><input style='border:0;' name='data[" . $i . "][capacity]'></td></tr>";
            }
        } else {
            $kedu = array();
            if ($cum_data['suanfa'] == 'a' and $type = 'rl') $kedu = json_decode($cum_data['tripbystern']);
            if ($cum_data['suanfa'] == 'b' and $type = 'rl') $kedu = json_decode($cum_data['trimcorrection']);
            if ($cum_data['suanfa'] == 'c') {
                if ($type == 'rl') {
                    $kedu = json_decode($cum_data['trimcorrection']);
                } elseif ($type == 'rl_1') {
                    $kedu = json_decode($cum_data['trimcorrection1']);
                }
            }
            if ($cum_data['suanfa'] == 'd') {
                if ($type == 'rl') {
                    $kedu = json_decode($cum_data['trimcorrection']);
                } elseif ($type == 'rl_1') {
                    $kedu = json_decode($cum_data['trimcorrection1']);
                }
            }
            $tvalue = "<thead><th>空高</th>";
            foreach ($kedu as $key => $value) {
                $tvalue .= "<th>纵倾值" . $value . "/m</th>";
            }
            $tvalue .= "</thead>";
            $column = count($kedu);
            for ($i = 0; $i < 50; $i++) {
                $tvalue .= "<tr><td><input style='border:0;' name='data[" . $i . "][ullage]'></td>";
                foreach ($kedu as $v) {
                    $tvalue .= "<td><input style='border:0;' name='data[" . $i . "][" . $v . "]'></td>";
                }
                $tvalue .= "</tr>";
            }
        }

        $res = array(
            'code' => 0,
            'tvalue' => $tvalue
        );
        echo json_encode($res);
    }


    public function get_ullage()
    {
        $weight = floatval(I('post.weight'));
        $resultid = intval(I('post.resultid'));
        $solt = intval(I('post.solt'));
        $cabinid = intval(I('post.cabinid'));

        if (!$weight or !$resultid or !$solt or !$cabinid) {
            exit(json_encode(array('code' => 4)));
        }
        $result = new \Common\Model\WorkModel();
        $resultlist = new \Common\Model\ResultlistModel();
        $result_record = M('resultrecord');
        $ship = new \Common\Model\ShipFormModel();
        $cabin = new \Common\Model\CabinModel();

        $data = $result
            ->field('shipid,houdensity,qiandensity,qianchi,houchi')
            ->where(array(
                'id' => $resultid
            ))->find();

        $shipmsg = $ship
            ->field('is_guanxian,coefficient,suanfa,tankcapacityshipid,tripbystern,trimcorrection,trimcorrection1,rongliang,zx,rongliang_1,zx_1')
            ->where(array('id' => $data['shipid']))
            ->find();

        $listmsg = $result_record->field('qufen,is_pipeline,temperature')->where(array('resultid' => $resultid, 'cabinid' => $cabinid, 'solt' => $solt))->find();

        $qufen = $listmsg['qufen'];
        $is_pipeline = $listmsg['is_pipeline'];
        $wendu = $listmsg['temperature'];

        $pipe_line = $cabin->getFieldById($cabinid, 'pipe_line');

        if ($solt == 1) {
            $midu = $data['qiandensity'];
            $chishui = $data['qianchi'];
        } else {
            $midu = $data['houdensity'];
            $chishui = $data['houchi'];
        }

        /*
                if ($shipmsg['suanfa'] == "a") {
                    $table = $shipmsg['tankcapacityshipid'];
                } elseif ($shipmsg['suanfa'] == "b") {
                    $table = $shipmsg['zx'];
                    $table1 = $shipmsg['rongliang'];
                } elseif ($shipmsg['suanfa'] == "c") {
                    if ($qufen = "diliang") {
                        $table = $shipmsg['zx_1'];
                        $table1 = $shipmsg['rongliang_1'];
                    } else {
                        $table = $shipmsg['zx'];
                        $table1 = $shipmsg['rongliang'];
                    }
                } elseif ($shipmsg['suanfa'] == "d") {
                    if ($qufen = "diliang") {
                        $table1 = $shipmsg['rongliang_1'];
                    } else {
                        $table1 = $shipmsg['rongliang'];
                    }
                }*/
        //体积修正系数
        $volume = corrent($midu, $wendu);
        //膨胀修正系数
        $expand = expand($shipmsg['coefficient'], $wendu);

        //考虑空气浮力
        $standardcapacity = $weight / ($midu - 0.0011);//重量除以密度等于修正后体积

        //判断有无管线，有管线的要减去管线
        if ($is_pipeline == 1) $standardcapacity -= $pipe_line;

        //修正后体积除以体积修正系数除以膨胀修正系数等于体积查表插值
        $cabin_weight = $standardcapacity / $volume / $expand;

        //根据得到的插值计算舱容表范围
        switch ($shipmsg['suanfa']) {
            case 'a':
                $tablename = $shipmsg['tankcapacityshipid'];
                // json转化数组
                $qiu = $this->getjsonarray($shipmsg['tripbystern'], $chishui);
                //返回的吃水差跟纵倾值
                $keys = array_keys($qiu);
                $values = array_values($qiu);
                $values[] = $chishui;
                //根据空高查询数据
                $ulist = $this->back_downup($cabin_weight, $tablename, $keys, $cabinid);
                break;
            case 'b':
                $rl_tablename = $shipmsg['rongliang'];
                $zx_tablename = $shipmsg['zx'];
                //先根据容量值计算修正后的空高
                $field1 = array('capacity');
                //反推修正后空高
                $xiukong = $this->capacity_downup($cabin_weight, $rl_tablename, $field1, $cabinid);
                //通过修正后空高计算表格对应值

                // json转化数组
                $qiu = $this->getjsonarray($shipmsg['trimcorrection'], $chishui);
                //返回的吃水差跟纵倾值
                $keys = array_keys($qiu);
                $values = array_values($qiu);
                $ulist = $this->ullage_downup($xiukong['ullage'], $zx_tablename, $keys, $cabinid, $values);
                break;
            case 'c':
                if ($qufen == "diliang") {
                    $rl_tablename = $shipmsg['rongliang_1'];
                    $zx_tablename = $shipmsg['zx_1'];
                } else {
                    $rl_tablename = $shipmsg['rongliang'];
                    $zx_tablename = $shipmsg['zx'];
                }
                //先根据容量值计算修正后的空高
                $field1 = array('capacity');
                //反推修正后空高
                $xiukong = $this->capacity_downup($cabin_weight, $rl_tablename, $field1, $cabinid);
                //通过修正后空高计算表格对应值

                // json转化数组
                $qiu = $this->getjsonarray($shipmsg['trimcorrection'], $chishui);
                //返回的吃水差跟纵倾值
                $keys = array_keys($qiu);
                $values = array_values($qiu);
                $ulist = $this->ullage_downup($xiukong['ullage'], $zx_tablename, $keys, $cabinid, $values);

                break;
            case 'd':
                if ($qufen == "diliang") {
                    $tablename = $shipmsg['rongliang_1'];
                } else {
                    $tablename = $shipmsg['rongliang'];
                }
                // json转化数组
                $qiu = $this->getjsonarray($shipmsg['tripbystern'], $chishui);
                //返回的吃水差跟纵倾值
                $keys = array_keys($qiu);
                $values = array_values($qiu);
                $values[] = $chishui;
                //根据空高查询数据
                $ulist = $this->back_downup($cabin_weight, $tablename, $keys, $cabinid);

                break;
            default:
                break;
        }
        echo jsonreturn($ulist);
    }


    /**
     * 计算上一条与下一条数据
     */
    public function ullage_downup($xiu_ullage, $tablename, $field, $cabinid, $draft = array())
    {
        $tname = M($tablename);
        if (count($field) == 1) {
            $field[] = 'ullage';
            $where = array(
                'cabinid' => $cabinid,
                '_string' => "ullage- round(" . $field[0] . "/1000,3)=" . $xiu_ullage,
            );
            $u = $tname
                ->field('ullage')
                ->where($where)
                ->find();
            if (!empty($u)) {
                $res = $u;
            } else {
                //查不到数据，搜索它的上一条或者下一条数据
                //上一条数据
                $wherelt = array(
                    'cabinid' => $cabinid,
                    '_string' => "ullage- round(" . $field[0] . "/1000,3)<" . $xiu_ullage,
                );
                $e = $tname
                    ->field('ullage,(ullage- round(' . $field[0] . '/1000,3)) as cz')
                    ->where($wherelt)
                    ->order('(ullage- round(' . $field[0] . '/1000,3)) desc')
                    ->find();

                //下一条数据
                $wheregt = array(
                    'cabinid' => $cabinid,
                    '_string' => "ullage- round(" . $field[0] . "/1000,3)>" . $xiu_ullage,
                );
                $f = $tname
                    ->field('ullage,(ullage- round(' . $field[0] . '/1000,3)) as cz')
                    ->where($wheregt)
                    ->order('(ullage- round(' . $field[0] . '/1000,3)) asc')
                    ->find();
                $res['ullage'] = $this->middle($e['ullage'], $f['ullage'], $e['cz'], $f['cz'], $xiu_ullage);
            }
        } elseif (count($field) == 2) {
            $field[] = 'ullage';
            $where = array(
                'cabinid' => $cabinid,
                '_string' => '(ullage - round(((' . $field['0'] . ' - ' . $field['1'] . ') / ' . ($draft['0'] - $draft['1']) . ' * ' . ($draft['3'] - $draft['1']) . ' + ' . $field['1'] . ') / 1000, 3))=' . $xiu_ullage,
            );
            $u = $tname
                ->field('ullage')
                ->where($where)
                ->find();
            if (!empty($u)) {
                $res = $u;
            } else {
                //查不到数据，搜索它的上一条或者下一条数据
                //上一条数据
                $wherelt = array(
                    'cabinid' => $cabinid,
                    '_string' => '(ullage - round(((' . $field['0'] . ' - ' . $field['1'] . ') / ' . ($draft['0'] - $draft['1']) . ' * ' . ($draft['3'] - $draft['1']) . ' + ' . $field['1'] . ') / 1000, 3))<' . $xiu_ullage,
                );
                $e = $tname
                    ->field('ullage,(ullage - round(((' . $field['0'] . ' - ' . $field['1'] . ') / ' . ($draft['0'] - $draft['1']) . ' * ' . ($draft['3'] - $draft['1']) . ' + ' . $field['1'] . ') / 1000, 3)) as cz')
                    ->where($wherelt)
                    ->order('(ullage - round(((' . $field['0'] . ' - ' . $field['1'] . ') / ' . ($draft['0'] - $draft['1']) . ' * ' . ($draft['3'] - $draft['1']) . ' + ' . $field['1'] . ') / 1000, 3)) desc')
                    ->find();

                //下一条数据
                $wheregt = array(
                    'cabinid' => $cabinid,
                    '_string' => '(ullage - round(((' . $field['0'] . ' - ' . $field['1'] . ') / ' . ($draft['0'] - $draft['1']) . ' * ' . ($draft['3'] - $draft['1']) . ' + ' . $field['1'] . ') / 1000, 3))>' . $xiu_ullage,
                );
                $f = $tname
                    ->field('ullage,(ullage - round(((' . $field['0'] . ' - ' . $field['1'] . ') / ' . ($draft['0'] - $draft['1']) . ' * ' . ($draft['3'] - $draft['1']) . ' + ' . $field['1'] . ') / 1000, 3)) as cz')
                    ->where($wheregt)
                    ->order('(ullage - round(((' . $field['0'] . ' - ' . $field['1'] . ') / ' . ($draft['0'] - $draft['1']) . ' * ' . ($draft['3'] - $draft['1']) . ' + ' . $field['1'] . ') / 1000, 3)) asc')
                    ->find();
                $res['ullage'] = $this->middle($e['ullage'], $f['ullage'], $e['cz'], $f['cz'], $xiu_ullage);
            }
        }
        return $res;
    }

    /**
     * 计算上一条与下一条数据
     */
    public function capacity_downup($cabin_weight, $tablename, $field, $cabinid)
    {
        $tname = M($tablename);
        $field[] = 'ullage';
        $u = $tname
            ->field('ullage')
            ->where(array($field[0] => $cabin_weight, 'cabinid' => $cabinid))
            ->find();
        if (!empty($u)) {
            $res = $u;
        } else {
            //查不到数据，搜索它的上一条或者下一条数据
            //上一条数据
            $wherelt = array(
                'cabinid' => $cabinid,
                $field[0] => array('LT', $cabin_weight)
            );
            $e = $tname
                ->field($field)
                ->where($wherelt)
                ->order($field[0] . ' desc')
                ->find();

            //下一条数据
            $wheregt = array(
                'cabinid' => $cabinid,
                $field[0] => array('GT', $cabin_weight)
            );
            $f = $tname
                ->field($field)
                ->where($wheregt)
                ->order($field[0] . ' asc')
                ->find();
            $res['ullage'] = $this->middle($e['ullage'], $f['ullage'], $e[$field[0]], $f[$field[0]], $cabin_weight);
        }
        return $res;
    }


    /**
     * 倒推计算上一条与下一条数据
     */
    public function back_downup($cabin_weight, $tablename, $field, $cabinid, $draft = array())
    {
        $tname = M($tablename);
        $res = array();

        if (count($field) == 1) {
            $u = $tname
                ->field('ullage')
                ->where(array($field[0] => $cabin_weight, 'cabinid' => $cabinid))
                ->find();
            if (!empty($u)) {
                $res = $u;
            } else {
                //查不到数据，搜索它的上一条或者下一条数据
                //上一条数据
                $wherelt = array(
                    'cabinid' => $cabinid,
                    $field[0] => array('LT', $cabin_weight)
                );
                $e = $tname
                    ->field('ullage,' . $field[0] . " as cz")
                    ->where($wherelt)
                    ->order($field[0] . ' desc')
                    ->find();

                //下一条数据
                $wheregt = array(
                    'cabinid' => $cabinid,
                    $field[0] => array('GT', $cabin_weight)
                );
                $f = $tname
                    ->field('ullage,' . $field[0] . " as cz")
                    ->where($wheregt)
                    ->order($field[0] . ' asc')
                    ->find();
                $res['ullage'] = $this->middle($e['ullage'], $f['ullage'], $e['cz'], $f['cz'], $cabin_weight);
            }
        } elseif (count($field) == 2) {
            $u = $tname
                ->field('ullage')
                ->where(array($field[0] => $cabin_weight, 'cabinid' => $cabinid))
                ->find();
            if (!empty($u)) {
                $res = $u;
            } else {
                //查不到数据，搜索它的上一条或者下一条数据
                //上一条数据
                $wherelt = array(
                    'cabinid' => $cabinid,
                    '_string' => '((' . $field[0] . '-' . $field[1] . ')/' . ($draft[0] - $draft[1]) . '*' . ($draft[2] - $draft[1]) . '+' . $field[1] . ')<' . $cabin_weight,
                );
                $e = $tname
                    ->field('ullage,((' . $field[0] . '-' . $field[1] . ')/' . ($draft[0] - $draft[1]) . '*' . ($draft[2] - $draft[1]) . '+' . $field[1] . ') as cz')
                    ->where($wherelt)
                    ->order('((' . $field[0] . '-' . $field[1] . ')/' . ($draft[0] - $draft[1]) . '*' . ($draft[2] - $draft[1]) . '+' . $field[1] . ') desc')
                    ->find();
                //下一条数据
                $wheregt = array(
                    'cabinid' => $cabinid,
                    '_string' => '((' . $field[0] . '-' . $field[1] . ')/' . ($draft[0] - $draft[1]) . '*' . ($draft[2] - $draft[1]) . '+' . $field[1] . ')>' . $cabin_weight,
                );
                $f = $tname
                    ->field('ullage,((' . $field[0] . '-' . $field[1] . ')/' . ($draft[0] - $draft[1]) . '*' . ($draft[2] - $draft[1]) . '+' . $field[1] . ') as cz')
                    ->where($wheregt)
                    ->order('((' . $field[0] . '-' . $field[1] . ')/' . ($draft[0] - $draft[1]) . '*' . ($draft[2] - $draft[1]) . '+' . $field[1] . ') asc')
                    ->find();
                $res['ullage'] = $this->middle($e['ullage'], $f['ullage'], $e['cz'], $f['cz'], $cabin_weight);
            }
        }
        return $res;
    }

    /**
     * 计算纵倾修正 json转化数组
     */
    public function getjsonarray($data, $chishui)
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


    public function test_downup()
    {
        $cabin_weight = I('post.cabin_weight');
        $tablename = I('post.tablename');
        $field[] = I('post.field1');
        $field[] = I('post.field2');
        $draft[] = I('post.draft1');
        $draft[] = I('post.draft2');
        $draft[] = I('post.draft');
        $cabinid = I('post.cabinid');
        echo json_encode($this->back_downup($cabin_weight, $tablename, $field, $cabinid, $draft));
    }


    function middle($a, $b, $c, $d, $e)
    {
        $suanfa = round(($a - $b) / ($c - $d) * ($e - $d) + $b, 3);
        return $suanfa;
    }


    public function get_middle($c_big, $c_small, $x_big, $x_small, $x)
    {
        echo $this->middle($c_big, $c_small, $x_big, $x_small, $x);
    }

    /**
     * 更新无表船和有表船的状态
     */
    public function aaa()
    {
        $ship = new \Common\Model\ShipFormModel();
        echo jsonreturn($ship->updata_data_ship());
    }

    /**
     * 更新无表船和有表船的状态
     */
    public function aaa1()
    {
        $ship = new \Common\Model\ShipFormModel();
        echo jsonreturn($ship->updata_one_ship(162));
    }

    /**
     * 和python进行通讯
     */
    public function python_shell()
    {
        exec('py D:\pythonWork\opencv\worker.py D:\phpStudy\PHPTutorial\WWW\shipplatform2\Upload\test\4\4.jpg 2>&1', $out);
        $result = json_decode($out[0], true);
        var_dump($result);
        if ($result['status'] == "success") {
            $img_dir = $result['path'];
            vendor("baiduimage.AipOcr");
            $APP_ID = '18037745';
            $API_KEY = 'nsc7qNv6ZTa6pFChL1dEMqEG';
            $SECRET_KEY = 'Ci9MZ8Q8QXBf4ap916D0M8eRCxGCNLep';
            $img = file_get_contents($img_dir);
            $Ocr = new \AipOcr($APP_ID, $API_KEY, $SECRET_KEY);
            $option = array(
                'is_sync' => "true",
                'request_type' => 'json',
            );
            $result = $Ocr->tableRecognitionAsync($img, $option);

            $ullage = 0.24;
            $draft = 0.2;

            $test_date_arr = json_decode($result['result']['result_data'], true);
            //获取数据。如果收到两个数据
            $table_datas = array();

            $max_column = 0;
            $max_row = 0;

            $draft_up = 0;
            $draft_down = 0;

            $ullage_up = 0;
            $ullage_down = 0;

            //吃水差数组
            $draft_arr = array();
            //空高数组
            $ullage_arr = array();
            //序列化数组，行内包含单元格
            foreach ($test_date_arr['forms'] as $key => $form_data) {
                foreach ($form_data['body'] as $k => $v) {
                    $column = $v['column'][0];
                    $max_column = $column > $max_column ? $column : $max_column;
                    $row = $v['row'][0];
                    $max_row = $row > $max_row ? $row : $max_row;
                    //如果不存在行数组，自动创建
                    if (!isset($table_datas[$row])) $table_datas[$row] = array();
                    $table_datas[$row][$column] = $v['word'];

                    //获取吃水差列表
                    if ($row == 2 and $column > 1 and $v['word'] != "") {
                        //处理一下数字
                        $now_draft = (float)$v['word'];
                        //获取当前吃水差和上一个吃水差
                        if ($draft_up == 0) {
                            $pre_arr = end($draft_arr);
                            if ($draft == $now_draft) {
                                $draft_up = $column;
                                $draft_down = $column;
                            } else {
                                if ($pre_arr['val'] < $draft and $now_draft > $draft) {
                                    $draft_up = $pre_arr['column'];
                                    $draft_down = $column;
                                }
                            }
                        }
                        $draft_arr[] = array('val' => $now_draft, 'column' => $column);
                    }
                    //获取空高列表
                    if ($column == 1 and $row > 3 and $v['word'] != "") {
                        //处理一下数字
                        $now_ullage = (float)$v['word'];
                        //去除空白列如果内容为空的话
                        if (!(($row - 3) % 6 == 0 and $now_ullage == 0)) {
                            //获取当前吃水差和上一个吃水差
                            if ($ullage_up == 0) {
                                $pre_arr = end($ullage_arr);
                                if ($ullage == $now_ullage) {
                                    $ullage_up = $row;
                                    $ullage_down = $row;
                                } else {
                                    if ($pre_arr['val'] < $ullage and $now_ullage > $ullage) {
                                        $ullage_up = $pre_arr['row'];
                                        $ullage_down = $row;
                                    }
                                }
                            }
                            $ullage_arr[] = array('val' => $now_ullage, 'row' => $row);
                        }
                    }
                }
            }
            /*//序列化数组，行内包含单元格
            foreach ($test_date_arr['forms'] as $key => $form_data) {
                foreach ($form_data['body'] as $k => $v) {
                    $column = $v['column'][0];
                    $max_column = $column > $max_column ? $column : $max_column;
                    $row = $v['row'][0];
                    $max_row = $row > $max_row ? $row : $max_row;
                    //如果不存在行数组，自动创建
                    if (!isset($table_datas[$row])) $table_datas[$row] = array();
                    $table_datas[$row][$column] = $v['word'];



                    //获取吃水差列表
                    if ($row == 2 and $column > 1 and $v['word']!="") {
                        //处理一下数字
                        $now_draft =(float)$v['word'];
                        //获取当前吃水差和上一个吃水差
                        if($draft_up ==0){
                            $pre_arr = end($draft_arr);
                            if($draft == $now_draft){
                                $draft_up = $column;
                                $draft_down = $column;
                            }else{
                                if($pre_arr['val']<$draft and $now_draft>$draft){
                                    $draft_up = $pre_arr['column'];
                                    $draft_down = $column;
                                }
                            }
                        }
                        $draft_arr[] = array('val'=>$now_draft,'column'=>$column);
                    }
                    //获取空高列表
                    if ($column == 1 and $row > 3 and $v['word']!="") {
                        //处理一下数字
                        $now_ullage=(float)$v['word'];
                        //去除空白列如果内容为空的话
                        if (!(($row - 3) % 6 == 0 and  $now_ullage== 0)) {
                            //获取当前吃水差和上一个吃水差
                            if($ullage_up ==0){
                                $pre_arr = end($ullage_arr);
                                if($ullage == $now_ullage){
                                    $ullage_up = $row;
                                    $ullage_down = $row;
                                }else{
                                    if($pre_arr['val']<$ullage and $now_ullage>$ullage){
                                        $ullage_up = $pre_arr['row'];
                                        $ullage_down = $row;
                                    }
                                }
                            }
                            $ullage_arr[] = array('val'=>$now_ullage,'row'=>$row);
                        }
                    }
                }
            }*/

            /*
             * 判断极值
             */
            if ($draft_up == 0) {
                $first = reset($draft_arr);
                $last = end($draft_arr);
                if ($first['val'] > $draft) {
                    $draft_up = $first['column'];
                    $draft_down = $first['column'];
                }
                if ($last['val'] < $draft) {
                    $draft_up = $last['column'];
                    $draft_down = $last['column'];
                }
            }

            if ($ullage_up == 0) {
                $first = reset($ullage_arr);
                $last = end($ullage_arr);
                if ($first['val'] > $ullage) {
                    $ullage_up = $first['row'];
                    $ullage_down = $first['row'];
                }
                if ($last['val'] < $ullage) {
                    $ullage_up = $last['row'];
                    $ullage_down = $last['row'];
                }
            }
            /*        print_r($draft_arr);
                    echo "<br>";
                    print_r($ullage_arr);
                    echo "<br>";
                    print_r($draft_up);
                    echo "<br>";
                    print_r($draft_down);
                    echo "<br>";
                    print_r($ullage_up);
                    echo "<br>";
                    print_r($ullage_down);*/
//        echo (float)'-0.2m';
            $assign = array(
                'table' => $table_datas,
                'max_column' => $max_column,
                'max_row' => $max_row,
                'draft_up' => $draft_up,
                'draft_down' => $draft_down,
                'ullage_up' => $ullage_up,
                'ullage_down' => $ullage_down,
                'ullage' => $ullage,
                'draft' => $draft,
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 和python进行通讯
     */
    public function ca_python_shell()
    {


        //设置好的空高
        $ullage = 5.442;

        exec('py D:\pythonWork\opencv\ca_worker.py D:\phpStudy\PHPTutorial\WWW\shipplatform2\Upload\ca_test\3\3.jpg 2>&1', $out);
        $result = json_decode($out[0], true);
//        var_dump($result);
        if ($result['status'] == "success") {
            $img_dir = $result['path'];
            vendor("baiduimage.AipOcr");
            $APP_ID = '18037745';
            $API_KEY = 'nsc7qNv6ZTa6pFChL1dEMqEG';
            $SECRET_KEY = 'Ci9MZ8Q8QXBf4ap916D0M8eRCxGCNLep';
            $img = file_get_contents($img_dir);
            $Ocr = new \AipOcr($APP_ID, $API_KEY, $SECRET_KEY);
            $option = array(
                'is_sync' => "true",
                'request_type' => 'json',
            );
            $result = $Ocr->tableRecognitionAsync($img, $option);

//            echo $result;

//            $test_arr = json_decode($result, true);
            $test_date_arr = json_decode($result['result']['result_data'], true);
            //获取数据。如果收到两个数据
//        print_r($test_date_arr['forms']);
            $table_datas = array();

            $max_column = 0;
            $max_row = 0;

//        $edition = 0;

            $draft_up = 0;
            $draft_down = 0;

            $ullage_up = 0;
            $ullage_down = 0;


            //空高数组
            $ullage_arr = array();
            //序列化数组，行内包含单元格
            foreach ($test_date_arr['forms'] as $key => $form_data) {
                foreach ($form_data['body'] as $k => $v) {
                    $column = $v['column'][0];
                    //更新最大列
                    $max_column = $column > $max_column ? $column : $max_column;
                    $row = $v['row'][0];
                    //更新最大行
                    $max_row = $row > $max_row ? $row : $max_row;
                    //如果不存在行数组，自动创建
                    if (!isset($table_datas[$row])) $table_datas[$row] = array();
                    $v['word'] = preg_replace('/\*|米/', '', $v['word']);
                    if (!strpos($v['word'], '.') and $v['word'] != "" and $row > 3) $v['word'] = intval($v['word']) / 1000;
                    $table_datas[$row][$column] = $v['word'];
                    //获取空高列表
                    if (($column == 2 or $column == 6) and $row > 3 and $v['word'] != "") {
                        //处理一下数字
                        $now_ullage = (float)$v['word'];
                        //去除空白列如果内容为空的话
                        if ($now_ullage != 0) {
                            $ullage_arr[] = array('val' => $now_ullage, 'row' => $row, 'column' => $column);
                        }
                    }
                }
            }


            //获取空高区间
            $count_ullage = count($ullage_arr);
            for ($i = 0; $i < $count_ullage; $i++) {
                $row = $ullage_arr[$i]['row'];
                $column = $ullage_arr[$i]['column'];
                $now_ullage = $ullage_arr[$i]['val'];
                if ($ullage_up == 0) {
                    if ($i > 0) {
                        if ($ullage == $now_ullage) {
                            $ullage_up = $row;
                            $ullage_down = $row;
                            $draft_up = $column;
                            $draft_down = $column;
                        } else {
                            $pre_arr = $ullage_arr[$i - 1];
                            if ($pre_arr['val'] < $ullage and $now_ullage > $ullage) {
                                $ullage_up = $pre_arr['row'];
                                $ullage_down = $row;
                                $draft_up = $pre_arr['column'];
                                $draft_down = $column;
                            }
                        }
                    } else {
                        if ($ullage == $now_ullage) {
                            $ullage_up = $row;
                            $ullage_down = $row;
                            $draft_up = $column;
                            $draft_down = $column;
                        }
                    }
                }
            }

            /*
             * 判断极值
             */
            if ($ullage_up == 0) {
                $first = reset($ullage_arr);
                $last = end($ullage_arr);
                if ($first['val'] > $ullage) {
                    $ullage_up = $first['row'];
                    $ullage_down = $first['row'];
                }
                if ($last['val'] < $ullage) {
                    $ullage_up = $last['row'];
                    $ullage_down = $last['row'];
                }
            }

            $assign = array(
                'table' => $table_datas,
                'max_column' => $max_column,
                'max_row' => $max_row,
                'ullage_up' => $ullage_up,
                'ullage_down' => $ullage_down,
                'draft_up' => $draft_up,
                'draft_down' => $draft_down,
                'ullage' => $ullage,
                'ullage_arr' => $ullage_arr,
            );
//        exit(json_encode($assign));
            $this->assign($assign);
            $this->display();
        } else {
            exit(json_encode($result));
        }
    }

    public function del()
    {
        $firm_id = I('post.firmid', 65);
        $uid = I('post.uid', 32);
        $firm = new \Common\Model\FirmModel();
        $user = new \Common\Model\UserModel();
        $firm_review = M('firm_review');
        $data = array(
            'claimed_img' => '',
            'claimed_code' => '',
        );
        $res = $firm->editData(array('id' => $firm_id), $data);
        $res1 = $firm_review->where(array('firmid' => $firm_id))->delete();
        $uid = $user->editData(array('id' => $uid), array('reg_status' => 0));
        echo "OK";
    }

    public function up_txt($shipid, $qufen)
    {
//        $shipid,$qufen,$trim_kedu

        $file_path = "./Upload/txt/baoying003.txt";
        $cabin = new \Common\Model\CabinModel();
        $ship = new \Common\Model\ShipFormModel();
        $cabins_info = $cabin->field('id,cabinname')->where(array('shipid' => $shipid))->select();
//        $ship_info = $ship->field('suanfa,is_diliang,tripbystern,trimcorrection,trimcorrection1,heelingcorrection,heelingcorrection1,tankcapacityshipid,rongliang,zx,hx,rongliang_1,zx_1,hx_1')->where(array('id' => $shipid))->find();
        $list_data = $this->read_list_data($file_path, $cabins_info);
        $ship_info = $this->auto_create_list_table($shipid, $list_data['list_kedu'], $qufen);
//        exit(json_encode($list_data,JSON_UNESCAPED_UNICODE));
        switch ($ship_info['suanfa']) {
            case 'a':
                $trim_kedu = $ship_info['tripbystern'];
                $table_name_1 = $ship_info['tankcapacityshipid'];
                $table_name_2 = "";
                $table_name_3 = "";
                break;
            case 'b':
                $trim_kedu = $ship_info['trimcorrection'];
                $table_name_1 = $ship_info['zx'];
                $table_name_2 = $ship_info['rongliang'];
                $table_name_3 = $ship_info['hx'];
                break;
            case 'c':
                if ($qufen == 'diliang') {
                    $trim_kedu = $ship_info['trimcorrection1'];
                    $table_name_1 = $ship_info['zx_1'];
                    $table_name_2 = $ship_info['rongliang_1'];
                    $table_name_3 = $ship_info['hx_1'];
                } else {
                    $trim_kedu = $ship_info['trimcorrection'];
                    $table_name_1 = $ship_info['zx'];
                    $table_name_2 = $ship_info['rongliang'];
                    $table_name_3 = $ship_info['hx'];
                }
                break;
            case 'd':
                if ($qufen == 'diliang') {
                    $trim_kedu = $ship_info['trimcorrection1'];
                    $table_name_1 = $ship_info['zx_1'];
                    $table_name_2 = "";
                    $table_name_3 = "";
                } else {
                    $trim_kedu = $ship_info['trimcorrection'];
                    $table_name_1 = $ship_info['zx'];
                    $table_name_2 = "";
                    $table_name_3 = "";
                }
                break;
        }
//        echo "kedu:".$trim_kedu . "   table1：" .$table1. " table2：".$table2." <br/>";
        //        array_merge_recursive($a, $b);
        M()->startTrans();
        if ($table_name_1 != "") {
            $table1 = M($table_name_1);
            $trim = $this->read_trim_data($trim_kedu, $file_path, $cabins_info);
//            exit(json_encode($trim));
//            foreach ($trim as $value) {
//                if ($table1->addAll($value['tirm_data']) === false) {
//                    M()->rollback();
//                    exit(jsonreturn(array('code' => 3, 'error' => $table1->getDbError())));
//                };
//            }
        }
        if ($table_name_2 != "") {
            $table2 = M($table_name_2);
            $ca = $this->read_ca_data($file_path, $cabins_info);
            exit(json_encode($ca));
            foreach ($ca as $value1) {
                if ($table2->addAll($value1['ca_data']) === false) {
                    M()->rollback();
                    exit(jsonreturn(array('code' => 4, 'error' => $table2->getDbError())));
                };
            }
        }

//        if ($table_name_3 != "") {
//            $table3 = M($table_name_3);
////            exit(json_encode($ca));
//            foreach ($list_data['data'] as $value1) {
//                //横倾修正表，录入时不报错,防止影响正常的纵倾修正表录入业务
//                @$table3->addAll($value1['list_data']);
//            }
//        }


        M()->commit();
        exit(jsonreturn(array('code' => 1, 'msg' => "导入成功")));
    }

    /**
     * 正则匹配文本内的纵倾修正表数据并返回
     * @param $trim_kedu
     * @param $file_path
     * @return array
     */
    function read_trim_data($trim_kedu, $file_path, $cabins_info)
    {
        //        https://regex101.com/r/ozVP4k/2 纵倾修正表正则视图

        $orgin_txt = file_get_contents($file_path);
//        dump($orgin_txt);
        $re = '/[\-\ ]+\s?Page\s([\d]+)[\-\ ]+\s+有效期至([\S]+)\s*?纵 倾 修 正 表\s*?实 高\s*?Sounding\s*?\(m\)\s*?空 高 纵倾值\（艉吃水\－艏吃水\）\s*?Ullage\s*?\(m\)\s*?Trim\[draft aft\(stern\)\- draft forward\(bow\)\]\s*?\[([^\]]+)\] Trim Correction Table ([\S]+) ([A-Za-z0-9]+)\s*?[\(m\) ]+\s*?([\-\.m 0-9]+)\s*?[\* \r\n]+[\s\S]*?([0-9\.\- \r\n]+)[\s\S]*?有效期/m';
        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
        $res = array();
        $trim_kedu = json_decode($trim_kedu, true);
        foreach ($matches as $key => $value) {
            $data = array();
            //处理页数编号等信息
//            echo "第".$value[1]."页 ， 有效期：".$value[2]."， 舱号：".$value[3].", 船名：".$value[4]."，书编号：".$value[5]."<br/>";
            $data['page'] = $value[1];
            $data['expire'] = $value[2];
            $data['cabin_name'] = preg_replace("/[左右]+污油舱 /", "", $value[3]);
            $data['ship_name'] = $value[4];
            $data['book_number'] = $value[5];
//            $cabin_id = 0;
//            foreach ($cabins_info as $v11) {
//                if (trimall($data['cabin_name']) == $v11['cabinname']) {
//                    $cabin_id = $v11['id'];
//                }
//            }
//            if ($cabin_id == 0) continue;
            //开始分开吃水刻度
            $kedu = explode('m ', $value[6]);
            $kedu[count($kedu) - 1] = str_replace("m", "", $kedu[count($kedu) - 1]);
//            print_r($kedu);
            $data['kedu'] = $kedu;
//            echo "<table style='text-align: center' border='1px solid'>";
//            echo "<thead><th>实高</th><th>空高</th>";
//            foreach ($kedu as $k=>$v){
//                echo "<th>".$v."</th>";
//            }
//            echo "</thead>";
//            echo "<tbody>";
            $data_row = explode("\r\n", $value[7]);
//            array_pop($data_row);
            $data['tirm_data'] = array();
            foreach ($data_row as $k1 => $v1) {
                $qian = array(" ", "　", "   ", "    ", '-', '0', '.');
                $hou = array("", "", "", "", "", "", "");
                if (str_replace($qian, $hou, $v1) == "") continue;
                $data_cloumn = explode(" ", $v1);
                $data_cloumn[count($data_cloumn) - 1] = str_replace("\r", "", $data_cloumn[count($data_cloumn) - 1]);

                $td = array('sounding' => $data_cloumn[0], 'ullage' => $data_cloumn[1], 'cabinid' => $cabin_id);
                $i = 0;
//                print_r($trim_kedu);
                foreach ($trim_kedu as $k2 => $v2) {
                    $td["$k2"] = $data_cloumn[$i + 2];
                    $i++;
                }
                array_push($data['tirm_data'], $td);
//                foreach ($data_cloumn as $k2=>$v2){
//                    echo "<td>".$v2."</td>";
//                }
//                echo "</tr>";
            }
            array_push($res, $data);
//            echo "</tbody>";
//            echo "</table>";
//            $ullage =
//            echo "<br/>";
        }
//        return $res;
//        exit(json_encode($res));
    }

    /**
     * 正则匹配文本内的容量表数据并返回
     * @param $file_path
     */
    function read_ca_data($file_path, $cabins_info)
    {
//        https://regex101.com/r/y0rt5d/2 容量表正则视图
        $orgin_txt = file_get_contents($file_path);
//        dump($orgin_txt);

        $orgin_txt = preg_replace("/[\r\n]{2}/", "\r\n", $orgin_txt);

//        $re = '/[\-]+\s+Page\s+([\d]+)[\-]+\s+有效期至([\S]+)\s*?容 量 表\s*?[\S\s]*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?[\S\s]*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+([\d\. \r\n]+)/m';
//        $re = '/[\-]{23}\s+Page\s+([\d]+)[\-]{23}\s+有效期至([\S]+)\s*?容 量 表\s*?[\S\s]*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?[\S\s]*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+((?:[\d\. \-]+[\r\n]+)+)/m';
        $re = '/[\-]{23}\s+Page\s+([\d]+)[\-]{23}\s+有效期至([\S]+)\s*?容 量 表\s*?[\S\s]*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?[\S\s]*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+(?:\d+\.\d{3} \d+\.\d{3} [\D]+ [\D]+[\r\n]+)*?((?:\d+\.\d{3} \d+\.\d{3} \d+\.\d{3} [\d\.\-]+[\r\n]+)+)/m';
        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
        $res = array();
        foreach ($matches as $key => $value) {
            $data = array();
            //处理页数编号等信息
//            echo "第" . $value[1] . "页 ， 有效期：" . $value[2] . "， 舱号：" . $value[3] . ", 船名：" . $value[4] . "，书编号：" . $value[5] . "<br/>";
            $data['page'] = $value[1];
            $data['expire'] = $value[2];
            $data['cabin_name'] = $value[3];
            $data['ship_name'] = $value[4];
            $data['book_number'] = $value[5];
//            $cabin_id = 0;
//            foreach ($cabins_info as $v11) {
//                if (trimall($data['cabin_name']) == $v11['cabinname']) {
//                    $cabin_id = $v11['id'];
//                }
//            }
//            if ($cabin_id == 0) continue;
//            echo "<table style='text-align: center' border='1px solid'>";
//            echo "<thead><th>实高</th><th>空高</th><th>容量</th><th>厘米容量</th>";
//            foreach ($kedu as $k=>$v){
//                echo "<th>".$v."</th>";
//            }
//            echo "</thead>";
//            echo "<tbody>";
            $data_row = explode("\r\n", $value[8]);
//            array_pop($data_row);
            $data['ca_data'] = array();
            foreach ($data_row as $k1 => $v1) {
                $qian = array(" ", "　", "   ", "    ", '-', '0', '.');
                $hou = array("", "", "", "", "", "", "");
                if (str_replace($qian, $hou, $v1) == "") continue;
                $data_cloumn = explode(" ", $v1);
                $data_cloumn[count($data_cloumn) - 1] = str_replace("\r", "", $data_cloumn[count($data_cloumn) - 1]);

                $td = array('sounding' => preg_replace("/[\-]+/", "0.000", $data_cloumn[0]), 'ullage' => preg_replace("/[\-]+/", "0.000", $data_cloumn[1]), 'capacity' => preg_replace("/[\-]+/", "0.000", $data_cloumn[2]), 'diff' => preg_replace("/[\-]+/", "0.000", $data_cloumn[3]), 'cabinid' => $cabin_id);

//                print_r($trim_kedu);
//                foreach ($trim_kedu as $k2 => $v2) {
//                    $td["$k2"] = $data_cloumn[$i+2];
//                    $i++;
//                }
//                echo "<tr>";
                array_push($data['ca_data'], $td);
//                foreach ($data_cloumn as $k2 => $v2) {
//                    echo "<td>" . $v2 . "</td>";
//                }
//                echo "</tr>";
            }
            array_push($res, $data);
//            echo "</tbody>";
//            echo "</table>";
//            $ullage =
//            echo "<br/>";
        }
//        exit(json_encode($res));
        return $res;
    }

    /**
     * 正则匹配文本内的舱数据并返回
     * @param $file_path
     */
    public function read_cabin_info()
    {
//        https://regex101.com/r/lLKWjQ/4 舱的基准高度数据正则视图
//        https://regex101.com/r/VPpjh4/2 舱

        $file_path_rong = "./Upload/txt/cabin_rong.txt";
//        $qufen = "diliang";
        $has_diliang = '2'; //表内是否有底量列
        $is_diliang = '2'; //是否有底量书

        $orgin_txt_rong = file_get_contents($file_path_rong);
//        dump($orgin_txt);
        //去除多余行
        $orgin_txt_rong = preg_replace("/[\r\n]{2}/", "\r\n", $orgin_txt_rong);


        //去除干扰特征
        $orgin_txt_rong = preg_replace("/舱 名 Tank Name H h|Pipe Line Position/", "", $orgin_txt_rong);

        //匹配基准高度部分
        $re1 = '/单位\(Unit\)：mm([\S\s]*?)有效期/m';
        preg_match_all($re1, $orgin_txt_rong, $matches_height_rong, PREG_SET_ORDER, 0);
        //匹配到了以后，将数据的异常特征处理掉
        $height_txt_rong = $matches_height_rong[0][1];

//        exit($height_txt);
        //去除左右污油舱的异常特征
        $height_txt_rong = preg_replace("/[左右]+污油舱 /", "", $height_txt_rong);
//        exit($height_txt);

        //去除P.SLOP中间有换行符的问题
        $height_txt_rong = preg_replace("/P\.[\r\n]+SLOP/", "P.SLOP", $height_txt_rong);

        //将表格中---的符号换成0.000
        $height_txt_rong = preg_replace("/\-+([ \r\n]+)/", "0.000$1", $height_txt_rong);

        //开始匹配基准高度
        $re2 = '/([左右]+\.[\d]+[ PS\.\d]*?|[PS\.LO]{6}) ([\d]+) ([\d]+)/m';
        //得到结果
        preg_match_all($re2, $height_txt_rong, $matches_height_data_rong, PREG_SET_ORDER, 0);
        dump($matches_height_data_rong);
        exit;

        $re3 = '/单位\(Unit\)：m3([\S\s]*?)总计/m';
        preg_match_all($re3, $orgin_txt_rong, $matches_pipe_rong, PREG_SET_ORDER, 0);

        //匹配到了以后，将数据的异常特征处理掉
        $pipe_txt_rong = $matches_pipe_rong[0][1];

        //去除左右污油舱的异常特征
        $pipe_txt_rong = preg_replace("/[左右]+污油舱 /", "", $pipe_txt_rong);
//        exit($height_txt);
//        exit($pipe_txt_rong);
        //去除P.SLOP中间有换行符的问题
        $pipe_txt_rong = preg_replace("/P\.[\r\n]+SLOP/", "P.SLOP", $pipe_txt_rong);
//        exit($pipe_txt_rong);
        //将表格中---的符号换成0.000
        $pipe_txt_rong = preg_replace("/\-+([ \r\n]+)/", "0.000$1", $pipe_txt_rong);
//        echo $pipe_txt_di;
//        exit($pipe_txt_rong);
        $re4 = '/((?:[左右]\.[\d]+|[PS\.LO]{6}) ?(?:[PS][\.\d]{0,4})?) ([\d\. \-]+)/m';
        preg_match_all($re4, $pipe_txt_rong, $matches_pipe_data_rong, PREG_SET_ORDER, 0);
        dump($matches_pipe_data_rong);
        exit;

        if ($is_diliang == '1') {
            $file_path_di = "./Upload/txt/cabin_di.txt";
            $orgin_txt_di = file_get_contents($file_path_di);
            $orgin_txt_di = preg_replace("/[\r\n]{2}/", "\r\n", $orgin_txt_di);
            $orgin_txt_di = preg_replace("/舱 名 Tank Name H h|Pipe Line Position/", "", $orgin_txt_di);
            preg_match_all($re1, $orgin_txt_di, $matches_height_di, PREG_SET_ORDER, 0);
            $height_txt_di = $matches_height_di[0][1];
            $height_txt_di = preg_replace("/[左右]+污油舱 /", "", $height_txt_di);
            $height_txt_di = preg_replace("/P\.[\r\n]+SLOP/", "P.SLOP", $height_txt_di);
            $height_txt_di = preg_replace("/\-+([ \r\n]+)/", "0.000$1", $height_txt_di);
            preg_match_all($re2, $height_txt_di, $matches_height_data_di, PREG_SET_ORDER, 0);
            preg_match_all($re3, $orgin_txt_di, $matches_pipe_di, PREG_SET_ORDER, 0);
            $pipe_txt_di = $matches_pipe_di[0][1];
            $pipe_txt_di = preg_replace("/[左右]+污油舱 /", "", $pipe_txt_di);
            $pipe_txt_di = preg_replace("/P\.[\r\n]+SLOP/", "P.SLOP", $pipe_txt_di);
            $pipe_txt_di = preg_replace("/\-+([ \r\n]+)/", "0.000$1", $pipe_txt_di);
            preg_match_all($re4, $pipe_txt_di, $matches_pipe_data_di, PREG_SET_ORDER, 0);
        } else {
            $matches_pipe_data_di = array();
            $matches_height_data_di = array();
        }

//        echo ajaxReturn($matches_height_data);
//        dump($matches_pipe_data_di);
//        exit;

        $res = array();
        $height = array();
        $pipe = array();
        $match1_height = array();
        $match1_pipe = array();
        $match2_height = array();
        $match2_pipe = array();
        $match_mod = "";
        $di_count = count($matches_height_data_di);
        $rong_count = count($matches_height_data_rong);
        if ($di_count > $rong_count) {
            $match1_height = $matches_height_data_di;
            $match1_pipe = $matches_pipe_data_di;
            $match2_height = $matches_height_data_rong;
            $match2_pipe = $matches_pipe_data_rong;
            $match_mod = 'd';
        } else {
            $match1_height = $matches_height_data_rong;
            $match1_pipe = $matches_pipe_data_rong;
            $match2_height = $matches_height_data_di;
            $match2_pipe = $matches_pipe_data_di;
            $match_mod = 'r';
        }

        //开始序列化基准高度部分数据
        foreach ($match1_height as $k => $v) {
            $data = array('cabinname' => $v[1]);
            if ($match_mod == "d") {
                $data['dialtitudeheight'] = $v[2];
                $data['altitudeheight'] = isset($match2_height[$k][2]) ? $match2_height[$k][2] : 0;
            } else {
                $data['dialtitudeheight'] = isset($match2_height[$k][2]) ? $match2_height[$k][2] : 0;
                $data['altitudeheight'] = $v[2];
            }
            //处理的数据放入数据
            array_push($height, $data);
        }

        //开始序列化底量和管线部分数据
        foreach ($match1_pipe as $k1 => $v1) {
            $data = array('cabinname' => $v1[1]);
            $data_split1 = explode(' ', $v1[2]);
            $data_split2 = explode(' ', isset($match2_pipe[$k1][2]) ? $match2_pipe[$k1][2] : '');
//            $data['light'] = $data_split;
            if ($match_mod == "d") {
                if ($has_diliang == "1") {
                    $data['bottom_volume_di'] = isset($data_split1[0]) ? $data_split1[0] : 0;
                    $data['bottom_volume'] = isset($data_split2[0]) ? $data_split2[0] : 0;
                } else {
                    $data['bottom_volume_di'] = 0;
                    $data['bottom_volume'] = 0;
                }
            } else {
                if ($has_diliang == "1") {
                    $data['bottom_volume_di'] = isset($data_split2[0]) ? $data_split2[0] : 0;
                    $data['bottom_volume'] = isset($data_split1[0]) ? $data_split1[0] : 0;
                } else {
                    $data['bottom_volume_di'] = 0;
                    $data['bottom_volume'] = 0;
                }
            }
            $data['pipe'] = isset($data_split1[count($data_split1) - 2]) ? $data_split1[count($data_split1) - 2] : 0;
            array_push($pipe, $data);
        }


        foreach ($height as $k2 => $v2) {
            foreach ($pipe as $k3 => $v3) {
                if ($v2['cabinname'] == $v3['cabinname']) {
                    $data = array(
                        'cabinname' => $v2['cabinname'],
                        'altitudeheight' => $v2['altitudeheight'],
                        'dialtitudeheight' => $v2['dialtitudeheight'],
                        'bottom_volume' => $v3['bottom_volume'],
                        'bottom_volume_di' => $v3['bottom_volume_di'],
                        'pipe' => $v3['pipe'],
                    );
                    array_push($res, $data);
                }
            }
        }
//        return $res;
        dump($res);


//
//
//        $re = '/[\-]+\s+Page\s+([\d]+)[\-]+\s+有效期至([\S]+)\s*?容 量 表\s*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?实 高 实 高\s*?Sounding Sounding\s*?\(m\) \(m\)\s*?空 高 容 量 厘米容量 空 高 容 量 厘米容量\s*?Ullage Capacity Diff Ullage Capacity Diff\s*?\(m\) \(m3\) \(m3\/cm\) \(m\) \(m3\) \(m3\/cm\)\s*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+([\d\. \r\n]+)/m';
//        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
//        $res = array();
//        foreach ($matches as $key => $value) {
//            $data = array();
//            //处理页数编号等信息
////                echo "第" . $value[1] . "页 ， 有效期：" . $value[2] . "， 舱号：" . $value[3] . ", 船名：" . $value[4] . "，书编号：" . $value[5] . "<br/>";
//            $data['page'] = $value[1];
//            $data['expire'] = $value[2];
//            $data['cabin_name'] = $value[3];
//            $data['ship_name'] = $value[4];
//            $data['book_number'] = $value[5];
//
////                echo "<table style='text-align: center' border='1px solid'>";
////                echo "<thead><th>实高</th><th>空高</th><th>容量</th><th>厘米容量</th>";
////            foreach ($kedu as $k=>$v){
////                echo "<th>".$v."</th>";
////            }
////                echo "</thead>";
////                echo "<tbody>";
//            $data_row = explode("\r\n", $value[8]);
////            array_pop($data_row);
//            $data['ca_data'] = array();
//            foreach ($data_row as $k1 => $v1) {
//                $data_cloumn = explode(" ", $v1);
//                $data_cloumn[count($data_cloumn) - 1] = str_replace("\r", "", $data_cloumn[count($data_cloumn) - 1]);
//
//                $td = array('sounding' => $data_cloumn[0], 'ullage' => $data_cloumn[1], 'capacity' => $data_cloumn[2], 'diff' => $data_cloumn[3]);
//
////                print_r($trim_kedu);
////                foreach ($trim_kedu as $k2 => $v2) {
////                    $td["$k2"] = $data_cloumn[$i+2];
////                    $i++;
////                }
////                    echo "<tr>";
//                array_push($data['ca_data'], $td);
////                    foreach ($data_cloumn as $k2 => $v2) {
////                        echo "<td>" . $v2 . "</td>";
////                    }
////                    echo "</tr>";
//            }
//            array_push($res, $data);
////                echo "</tbody>";
////                echo "</table>";
////            $ullage =
////            echo "<br/>";
//        }
//        exit(json_encode($res));
    }


    /**
     * 匹配横倾修正表
     */
//    public function read_list_data(){
    public function read_list_data($file_path, $cabins_info)
    {
//        $orgin_txt = file_get_contents($file_path);
        $orgin_txt = file_get_contents("./Upload/txt/dayang28_rong.txt");
        $re = '/\-{23}\s*?Page\s*?(\d+)\-{23}\s*?有效期至[\d年月日]+\s*?横 倾 修 正 表[\s\S]*?\[([左右]+\.\d+\s?(?:[PS]+\.\d+))\]\s*?LIST CORRECTION TABLE\s*?[\S]+\s*?[A-Za-z0-9]+[\s\S]*?左 倾 List to Port 右 倾 List to Starb\'d \*\s*?[\*\s]+((?:[\d°\.]+\s*?\(mm\)\s+)+)[\S\s]*?([0-9\.\- \r\n]+)[\s\S]*?有效期/m';
        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
//        exit(json_encode($matches));
        $res = array('data' => array());
        $list_kedu_arr = explode("\r\n", preg_replace("/[\r\n]{2,6}/m", "\r\n", $matches[0][3]));
        $list_kedu = array();
        $kedu_num = 1;
        $pre_kedu = 180;//是否需要变成负数
        for ($i = 0; $i < count($list_kedu_arr); $i += 2) {
            if ($list_kedu_arr[$i] != "") {
                $listValue = preg_replace("/°/", "", $list_kedu_arr[$i]);
                if ($pre_kedu - $listValue > 0) $listValue *= -1;//如果上一个数减当前数是正数则变为负数
//                echo $pre_kedu."<br/>";
//                echo $listValue;
                $list_kedu['listvalue' . $kedu_num] = $listValue;
                $pre_kedu = abs($listValue);//赋值上一个数
                $kedu_num++;
            }
        }
        $res['list_kedu'] = $list_kedu;

//        exit(jsonreturn($list_kedu));
//        $res['list_kedu'] = $list_kedu;
        foreach ($matches as $key => $value) {
            $data = array();
            //处理页数编号等信息
//            echo "第".$value[1]."页 ， 有效期：".$value[2]."， 舱号：".$value[3].", 船名：".$value[4]."，书编号：".$value[5]."<br/>";
            $data['page'] = $value[1];
            $data['cabin_name'] = preg_replace("/[左右]+污油舱 /", "", $value[2]);
//            $cabin_id = 0;
//            foreach ($cabins_info as $v11) {
//                if (trimall($data['cabin_name']) == $v11['cabinname']) {
//                    $cabin_id = $v11['id'];
//                }
//            }
//            if ($cabin_id == 0) continue;


            $data_row = explode("\r\n", $value[4]);
//            array_pop($data_row);
            $data['list_data'] = array();
            foreach ($data_row as $k1 => $v1) {
                $qian = array(" ", "　", "   ", "    ", '-', '0', '.');
                $hou = array("", "", "", "", "", "", "");
                if (str_replace($qian, $hou, $v1) == "") continue;
                $data_cloumn = explode(" ", $v1);
                $data_cloumn[count($data_cloumn) - 1] = str_replace("\r", "", $data_cloumn[count($data_cloumn) - 1]);

                $td = array('sounding' => $data_cloumn[0], 'ullage' => $data_cloumn[1], 'cabinid' => $cabin_id);
                $i = 0;
//                print_r($trim_kedu);
                foreach ($list_kedu as $k2 => $v2) {
                    $td["$k2"] = $data_cloumn[$i + 2];
                    $i++;
                }
                array_push($data['list_data'], $td);
//                foreach ($data_cloumn as $k2=>$v2){
//                    echo "<td>".$v2."</td>";
//                }
//                echo "</tr>";
            }
            array_push($res['data'], $data);
//            echo "</tbody>";
//            echo "</table>";
//            $ullage =
//            echo "<br/>";
        }
        return $res;
    }

    public function auto_create_list_table($shipid, $kedu, $qufen)
    {
        $ship = new \Common\Model\ShipFormModel();
        $where = array('id' => $shipid);
        $ship_info = $ship->field('shipname,suanfa,heelingcorrection,heelingcorrection1,hx,hx_1')->where($where)->find();
//        $isTable = M()->query('SHOW TABLES LIKE "user"');
//        if($isTable){
//            echo '表存在';
//        }else{
//            echo '表不存在';
//        }
        if ($ship_info['suanfa'] == 'b' and $ship_info['hx'] == "") {
            $hxname = 'tablelistcorrectionzi' . time() . chr(rand(97, 122));
            $shipname = $ship_info['name'];
            // 确定刻度

            $str = '';
            foreach ($kedu as $key => $value) {
                $str .= "`" . $key . "` int(11) DEFAULT NULL COMMENT '横倾值" . $value . "°',";
            }

            $sql1 = <<<sql
CREATE TABLE `${hxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}横倾表';
sql;
            M()->execute($sql1);
            if (!empty($kedu)) {
                $kedu = json_encode($kedu, JSON_UNESCAPED_UNICODE);
            } else {
                $kedu = '';
            }

            $datas = array(
                'hx' => $hxname,
                'heelingcorrection' => $kedu
            );

        } elseif ($ship_info['suanfa'] == 'c') {
            $datas = array();
            $time = time() . chr(rand(97, 122));
            if ($ship_info['hx'] == "") {
                $hxname = 'tablelistcorrectionzi' . $time . "_1";
                $shipname = $ship_info['name'];
                // 确定刻度

                $str = '';
                foreach ($kedu as $key => $value) {
                    $str .= "`" . $key . "` int(11) DEFAULT NULL COMMENT '横倾值" . $value . "°',";
                }
                $sql1 = <<<sql
CREATE TABLE `${hxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}横倾表';
sql;
                M()->execute($sql1);
                if (!empty($kedu)) {
                    $kedu_str = json_encode($kedu, JSON_UNESCAPED_UNICODE);
                } else {
                    $kedu_str = '';
                }

                $datas['hx'] = $hxname;
                $datas['heelingcorrection'] = $kedu_str;
            }


            if ($ship_info['hx_1'] == "") {
                $hxname = 'tablelistcorrectionzi' . $time . "_2";
                $shipname = $ship_info['name'];
                // 确定刻度

                $str = '';
                foreach ($kedu as $key => $value) {
                    $str .= "`" . $key . "` int(11) DEFAULT NULL COMMENT '横倾值" . $value . "°',";
                }
                $sql1 = <<<sql
CREATE TABLE `${hxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}横倾表';
sql;
                M()->execute($sql1);
                if (!empty($kedu)) {
                    $kedu_str = json_encode($kedu, JSON_UNESCAPED_UNICODE);
                } else {
                    $kedu_str = '';
                }

                $datas['hx_1'] = $hxname;
                $datas['heelingcorrection1'] = $kedu_str;
            }
        }
        $ship->editData($where, $datas);
        return $ship->field('suanfa,is_diliang,tripbystern,trimcorrection,trimcorrection1,heelingcorrection,heelingcorrection1,tankcapacityshipid,rongliang,zx,hx,rongliang_1,zx_1,hx_1')->where(array('id' => $shipid))->find();
    }


    /**
     * 更新所有船的有锁无锁状态
     */
    public function allid()
    {
        $ship = new \Common\Model\ShipFormModel();
        exit($ship->updata_lock_ships());
    }


    /**
     * 除沥青以外油品的体积修正系数
     * @param int   $oilType 油类型，2：原油，3：石油产品，4：润滑油
     * @param float $obTemperature 视温度，测量油密度时油的温度
     * @param float $obDensity 视密度，测量油密度时观测密度计得到的密度
     * @param float $oilTemperature 油温，测量空高时，油体的温度
     */
    public function getVcf()
    {
//        echo "视温度：".$obTemperature."℃，视密度：".$obDensity."Kg/m³，油品温度：".$oilTemperature."℃<br/>";
        $oilType = intval(I('post.oilType'));
        $obTemperature = floatval(I('post.obTemperature'));
        $obDensity = floatval(I('post.obDensity'));
        $oilTemperature = floatval(I('post.oilTemperature'));
        //获取15摄氏度下的视密度修正系数
        $cp15 = $this->getApparentDensityCorrectionFactor($obTemperature, 15);
        $cp20 = $this->getApparentDensityCorrectionFactor(15, 20);
        //获取视密度修正系数
//        $cp15 = $this->getApparentDensityCorrectionFactor();
//        $cp20 = $this->getApparentDensityCorrectionFactor();

        $density = $obDensity * $cp15;
        //获取该油的α
//        $Alpha = $this->getAlpha($oilType,$density);
        //获取该油在15℃下的标准密度
        $P15 = $this->iterativeCalculation($density, $obTemperature, $oilType, $cp15);
        //获取该油在20℃下的实际密度
        $P20 = $this->getP20($P15, $oilType);
        //标准密度
        $CrP20 = $P20 * $cp20;

        //得出修正系数,参与运算的P20为视密度，即未被修正的P20
        $Vcf20 = $this->getVcf20($P15, $oilTemperature, $oilType, $P20);


//        echo "15℃的视密度修正系数：".$cp15."<br/>";
//        echo "20℃的视密度修正系数：".$cp20."<br/>";
//        echo "20℃下的体积修正系数：".$Vcf20."<br/>";
        $data = array(
            "P15" => $P15,
            "cp15" => $cp15,
            "P20" => $CrP20,
            "RP20" => $P20,
            "cp20" => $cp20,
            "Vcf20" => $Vcf20,
        );
        \Think\Log::record("\r\n \r\n [ trans!!! ] 1." . $oilType . "，2." . $obTemperature . "，3." . $obDensity . "，4." . $oilTemperature . " \r\n \r\n ", "DEBUG", true);

        \Think\Log::record("\r\n \r\n [ trans!!! ] " . json_encode($data) . " \r\n \r\n ", "DEBUG", true);

        exit(jsonreturn($data));

//        echo $Vcf20;

    }


    /**
     * 开放接口，密度转换
     */
    public function getTransform()
    {
        $type = I('post.type');
        $density = I('post.density');
        $oilType = I('post.oilType');

        $res = array(
            'density' => $this->transform($type, $density, $oilType),
        );

        exit(jsonreturn($res));
    }

    function transform($type, $density, $oilType)
    {
        //type=1,表示15℃转20℃
        if ($type == 1) {
            //获取该油在20℃下的实际密度
            return $this->getP20($density, $oilType);
        } else {
            //获取该油在15℃下的实际密度
            return $this->getP15($density, $oilType);
        }
    }

    /**
     * 开放接口，计算体积修正系数
     */
    public function getOilVCF()
    {
        //油类型
        $oilType = I('post.oilType/d');
        //标准密度
        $density = I('post.density/f');
        //油温
        $OilTemperature = I('post.OilTemperature/f');
        //获取视密度修正系数
        $cp20 = $this->getApparentDensityCorrectionFactor(15, 20);
        //获取该油在20℃下的实际密度
        $P20 = $density / $cp20;
        //根据p20换算p15
        $P15 = $this->transform(2, $density, $oilType);

        //获取该油在15℃下的实际密度
        $res = array(
            'VCF' => $this->getVcf20($P15, $OilTemperature, $oilType, $P20),
        );
        exit(jsonreturn($res));
    }


    /**
     * 获取20℃下的体积修正系数
     * @param float $P15 15℃的标准温度
     * @param float $oilTemperature 油温，测量空高时，油体的温度
     * @param int   $oilType 油品类型
     * @param float $P20 20℃的标准温度
     * @return float|int
     */
    function getVcf20($P15, $oilTemperature, $oilType, $P20)
    {
        $deltaT = $oilTemperature - 15;
        $Alpha = $this->getAlpha($oilType, $P15);
        return $P15 * exp(-1 * $Alpha * $deltaT * (1 + 0.8 * $Alpha * $deltaT)) / $P20;
    }


    /**
     * 获取20℃下的标准密度
     * @param float $P15 15℃的标准温度
     * @param int   $oilType 油品类型
     * @return float|int
     */
    function getP20($P15, $oilType)
    {
        return $P15 * exp(-1 * ($this->getAlpha($oilType, $P15)) * 5 * (1 + 0.8 * ($this->getAlpha($oilType, $P15)) * 5));
    }

    /**
     * 获取15℃下的标准密度
     * @param float $P20 20℃的标准温度
     * @param int   $oilType 油品类型
     * @return float|int
     */
    function getP15($P20, $oilType)
    {
        return $P20 * exp(-1 * ($this->getAlpha($oilType, $P20)) * -5 * (1 + 0.8 * ($this->getAlpha($oilType, $P20)) * -5));
    }

    /**
     * 迭代算法获取15℃下的标准密度
     * @param float $density 实际密度
     * @param float $Alpha 油的Alpha
     * @param float $obTemperature 测量密度时的视温度
     */
    function iterativeCalculation($density, $obTemperature, $oilType, $cp15)
    {
        $deltaT = $obTemperature - 15;
        $P = $density;
        for ($i = 0; $i <= 999; $i++) {
            $Alpha = $this->getAlpha($oilType, $P);
            //exp函数为自然对数  exp(2)=e的二次方
            $P15 = $density / exp((-1 * $Alpha * $deltaT) * (1 + 0.8 * $Alpha * $deltaT));
            //如果差值小于阈值，退出循环
            if (abs($P - $P15) < 0.000001) break;
            //否则将结果代入density继续参与计算
            $P = $P15;
        }
        return $P15;
    }


    /**
     * 获取视密度修正系数
     * @param float $obTemperature 视温度
     * @param float $t 希望修正到的标准温度
     * @return float|int
     */
    function getApparentDensityCorrectionFactor($obTemperature, $t)
    {
        return 1 - 2.3 * 0.00001 * ($obTemperature - $t) - 2 * 0.00000001 * pow(($obTemperature - $t), 2);
    }
//    /**
//     * 获取视密度修正系数
//     * @param float $obTemperature 视温度
//     * @param float $t 希望修正到的标准温度
//     * @return float|int
//     */
//    function getApparentDensityCorrectionFactor(){
//        return 1-2.3*0.00001*(-5)-2*0.00000001*pow((-5),2);
//    }


    /**
     * 根据密度获取不同油品的Alpha
     * @param int   $oilType 油类型，2：原油，3：石油产品，4：润滑油
     * @param float $density 油品密度 t/m³
     */
    function getAlpha($oilType, $density)
    {
        //初始化,默认为0
        $k0 = 0;
        $k1 = 0;
        $A = 0;
        if ($oilType == 2) {
            $k0 = 613.9723;
        } elseif ($oilType == 3) {
            if ($density <= 770.3) {
                $k0 = 346.4228;
                $k1 = 0.4388;
            } elseif ($density > 770.3 && $density <= 787.5) {
                $k0 = 2680.3206;
                $A = -0.00336312;
            } elseif ($density > 787.5 && $density <= 838.3) {
                $k0 = 594.5418;
            } elseif ($density > 838.3 && $density <= 1163.5) {
                $k0 = 186.9696;
                $k1 = 0.4862;
            }
        } elseif ($oilType == 4) {
            $k1 = 0.6278;
        }
        //返回算法
        return $k0 / ($density * $density) + $k1 / $density + $A;
    }

    public function getlcf()
    {

        if (I('post.lbp', null) === null) {
            //缺少参数 4
            exit(jsonreturn(array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            )));
        }
        $lbp = I('post.lbp');
        if (I('post.lca', null) !== null) {
            $lca = I('post.lca');
            //计算lcf
            $lcf = $lbp / 2 - $lca;
            exit(jsonreturn(array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                'lcf' => $lcf
            )));
        } elseif (I('post.lcb', null) !== null) {
            $lcb = I('post.lcb');
            //计算lcf
            $lcf = $lcb - ($lbp / 2);
            exit(jsonreturn(array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                'lcf' => $lcf
            )));
        } else {
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }


    /**
     *  压载水计算器
     * @param array $data
     */
    public function reckon_sw($data)
    {


        //补充下一行
        if ($data['sounding_down'] == "") {
            //如果水深未落在水深刻度或者不等于0，则代表参数不正确，参数缺失，报错4
            if ($data['sounding'] != $data['sounding_up']) return array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "type" => "sounding_down");
            $data['sounding_down'] = $data['sounding_up'];
            $data['value3'] = $data['value1'];
            $data['value4'] = $data['value2'];
        } else {
            if ($data['value3'] == "") return array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "type" => "value3");
            if ($data['value4'] == "" and $data['draft2'] != "") return array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "type" => "value4");
        }

        //补充右列
        if ($data['draft2'] == "") {
            $data['draft2'] = $data['draft1'];
            $data['value2'] = $data['value1'];
            $data['value4'] = $data['value3'];
        }


        $sounding = floatval($data['sounding']);
        $draft = floatval($data['draft']);
        $density = floatval($data['density']);
//
//        $sounding1 = floatval($data['sounding1']);
//        $sounding2 = floatval($data['sounding2']);
//
//        $draft1 = floatval($data['draft1']);
//        $draft2 = floatval($data['draft2']);
//        $value1 = floatval($data['value1']);
//        $value2 = floatval($data['value2']);
//        $value3 = floatval($data['value3']);
//        $value4 = floatval($data['value4']);

        /*
         * 表数据排序，小值在ullage1,draft1，大值在ullage2,draft2
         */
        if ($data['sounding1'] > $data['sounding2']) {

            $sounding1 = floatval($data['sounding1']);
            $sounding2 = floatval($data['sounding2']);

            $value1 = floatval($data['value1']);
            $value2 = floatval($data['value2']);
            $value3 = floatval($data['value3']);
            $value4 = floatval($data['value4']);

            $data['sounding1'] = $sounding2;
            $data['sounding2'] = $sounding1;

            $data['value1'] = $value3;
            $data['value2'] = $value4;
            $data['value3'] = $value1;
            $data['value4'] = $value2;

        }


        /*
         * 依旧排序，排序吃水顺序，排序后顺带将对应值排序
         */
        if ($data['draft1'] > $data['draft2']) {
            $draft1 = floatval($data['draft1']);
            $draft2 = floatval($data['draft2']);

            $value1 = floatval($data['value1']);
            $value2 = floatval($data['value2']);
            $value3 = floatval($data['value3']);
            $value4 = floatval($data['value4']);

            $data['draft1'] = $draft2;
            $data['draft2'] = $draft1;

            $data['value1'] = $value2;
            $data['value3'] = $value4;
            $data['value2'] = $value1;
            $data['value4'] = $value3;
        }

        /*
         * 开始构建插值计算的计算数组，历史遗留原因，数组不太好解释结构，详情计算请看控制器中的
         * suanfa函数
         *
         * 这一段的代码意思：先判断纵倾是否是极值或者落在刻度的情况下，如果是这种情况只需要放入最
         * 接近的纵倾刻度值和对应容量值即可，不是这种情况则都放入，水深也是如此。
         */
        if ($draft <= $data['draft1']) {
            $qiu[] = $draft;
            $keys = array(
                0 => 'draft1'
            );
            // 判断水深是否在两个数之间
            if ($sounding <= $data['sounding1']) {
                //如果水深比最小值还小，取极值
                $ulist[] = array(
                    'sounding' => $data['sounding1'],   //输入的水深
                    'draft1' => $data['value1']
                );
            } elseif ($sounding >= $data['sounding2']) {
                //如果水深比最大值还大，取极值
                $ulist[] = array(
                    'sounding' => $data['sounding2'],   //输入的水深
                    'draft1' => $data['value3']
                );
            } else {
                //否则开始插值计算，现在开始处理
                $ulist = array(
                    0 => array(
                        'sounding' => $data['sounding1'],   //输入的水深
                        'draft1' => $data['value1']
                    ),
                    1 => array(
                        'sounding' => $data['sounding2'],   //输入的水深
                        'draft1' => $data['value3']
                    )
                );
            }
        } elseif ($draft >= $data['draft2']) {
            $qiu[] = $draft;
            // 下标
            $keys = array(
                0 => 'draft2'
            );
            // 判断水深是否在两个数中间
            if ($sounding <= $data['sounding1']) {
                $ulist[] = array(
                    'sounding' => $data['sounding1'],   //输入的水深
                    'draft2' => $data['value2']
                );
            } elseif ($sounding >= $data['sounding2']) {
                $ulist[] = array(
                    'sounding' => $data['sounding2'],   //输入的水深
                    'draft2' => $data['value4']
                );
            } else {
                $ulist = array(
                    0 => array(
                        'sounding' => $data['sounding1'],   //输入的水深
                        'draft2' => $data['value2']
                    ),
                    1 => array(
                        'sounding' => $data['sounding2'],   //输入的水深
                        'draft2' => $data['value4']
                    )
                );
            }
        } else {
            $qiu = array(
                'draft1' => $data['draft1'],
                'draft2' => $data['draft2']
            );
            // 下标
            $keys = array(
                0 => 'draft1',
                1 => 'draft2'
            );
            $ulist = array(
                0 => array(
                    'sounding' => $data['sounding1'],   //输入的水深
                    'draft1' => $data['value1'],
                    'draft2' => $data['value2']
                ),
                1 => array(
                    'sounding' => $data['sounding2'],   //输入的水深
                    'draft1' => $data['value3'],
                    'draft2' => $data['value4']
                )
            );
        }

        //开始根据填好的结构开始插值计算
        $msg = $this->suanfa($qiu, $ulist, $keys, $sounding, $draft);

        //如果返回的是数组，代表返回错误了，返回错误代码
        if (is_array($msg)) {
            exit(json_encode($msg));
        }

        $msg = round((float)$msg, 3);

        $res = array(
            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
            'volume' => $msg,
            'density' => $density,//密度
            'air_buoyancy' => 0.0011,//空气浮力
            'weight' => round((float)($msg * ($density - 0.0011)), 3),//重量
            'suanfa' => "volume*(density-air_buoyancy)",//算法字符串
        );

        exit(json_encode($res));
    }


    /**
     * 根据算法，插值计算各数据，返回数据
     * @param array  $qiu 结构：
     * @param array  $ulist
     * @param string $keys
     * @param string $sounding
     * @param string $chishui
     * @return array|float
     */
    function suanfa($qiu, $ulist, $keys = '', $sounding = '', $chishui = '')
    {
        //四种情况计算容量
        if (count($qiu) == '1' and count($ulist) == '1') {
            //【1】纵倾（吃水差）查出一条，空高查出1条
            $res = $ulist[0][$keys[0]];
        } elseif (count($qiu) == '2' and count($ulist) == '2') {
            //【2】纵倾（吃水差）查出2条，空高查出2条
            $hou = $this->get_middle((float)$ulist[1][$keys[1]], (float)$ulist[0][$keys[1]], (float)$ulist[1]['sounding'], (float)$ulist[0]['sounding'], $sounding);
            $qian = $this->get_middle((float)$ulist[1][$keys[0]], (float)$ulist[0][$keys[0]], (float)$ulist[1]['sounding'], (float)$ulist[0]['sounding'], $sounding);
            $res = $this->get_middle($hou, $qian, $qiu[$keys[1]], $qiu[$keys[0]], $chishui);
        } elseif (count($qiu) == '1' and count($ulist) == '2') {
            //【3】纵倾（吃水差）查出1条，空高查出2条
            $res = $this->get_middle((float)$ulist[1][$keys[0]], (float)$ulist[0][$keys[0]], (float)$ulist[1]['sounding'], (float)$ulist[0]['sounding'], $sounding);
        } elseif (count($qiu) == '2' and count($ulist) == '1') {
            //【4】纵倾（吃水差）查出2条，空高查出1条
            $res = $this->get_middle($ulist[0][$keys[1]], $ulist[0][$keys[0]], $qiu[$keys[1]], $qiu[$keys[0]], $chishui);
        } else {
            //其他错误	2
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );
        }
        return $res;
    }


    public function up_word($shipid,$qufen)
    {

        $file_path = "./Upload/txt/5003.txt";
        $cabin = new \Common\Model\CabinModel();
        $ship = new \Common\Model\ShipFormModel();
        $cabins_info = $cabin->field('id,cabinname')->where(array('shipid' => $shipid))->select();
        $ship_info = $ship->field('suanfa,is_diliang,tripbystern,trimcorrection,trimcorrection1,heelingcorrection,heelingcorrection1,tankcapacityshipid,rongliang,zx,hx,rongliang_1,zx_1,hx_1')->where(array('id' => $shipid))->find();
//        $list_data = $this->read_list_data($file_path, $cabins_info);
//        $ship_info = $ship_info = $ship->field('shipname,suanfa')->where(array('id' => $shipid))->find();
//        exit(json_encode($list_data,JSON_UNESCAPED_UNICODE));
        switch ($ship_info['suanfa']) {
            case 'a':
                $trim_kedu = $ship_info['tripbystern'];
                $table_name_1 = $ship_info['tankcapacityshipid'];
//                $table_name_2 = "";
//                $table_name_3 = "";
                break;
//            case 'b':
//                $trim_kedu = $ship_info['trimcorrection'];
//                $table_name_1 = $ship_info['zx'];
//                $table_name_2 = $ship_info['rongliang'];
//                $table_name_3 = $ship_info['hx'];
//                break;
//            case 'c':
//                if ($qufen == 'diliang') {
//                    $trim_kedu = $ship_info['trimcorrection1'];
//                    $table_name_1 = $ship_info['zx_1'];
//                    $table_name_2 = $ship_info['rongliang_1'];
//                    $table_name_3 = $ship_info['hx_1'];
//                } else {
//                    $trim_kedu = $ship_info['trimcorrection'];
//                    $table_name_1 = $ship_info['zx'];
//                    $table_name_2 = $ship_info['rongliang'];
//                    $table_name_3 = $ship_info['hx'];
//                }
//                break;
            case 'd':
                if ($qufen == 'diliang') {
                    $trim_kedu = $ship_info['trimcorrection1'];
                    $table_name_1 = $ship_info['zx_1'];
                    $table_name_2 = "";
                    $table_name_3 = "";
                } else {
                    $trim_kedu = $ship_info['trimcorrection'];
                    $table_name_1 = $ship_info['zx'];
                    $table_name_2 = "";
                    $table_name_3 = "";
                }
                break;
        }
//        echo "kedu:".$trim_kedu . "   table1：" .$table1. " table2：".$table2." <br/>";
        //        array_merge_recursive($a, $b);
        M()->startTrans();
        if ($table_name_1 != "") {
            $table1 = M($table_name_1);
            $trim = $this->read_word_trim_data($trim_kedu, $file_path, $cabins_info);
//            exit(json_encode($trim));
            foreach ($trim as $value) {
                if ($table1->addAll($value['tirm_data']) === false) {
                    M()->rollback();
                    exit(jsonreturn(array('code' => 3, 'error' => $table1->getDbError())));
                };
            }
        }
//        if ($table_name_2 != "") {
//            $table2 = M($table_name_2);
//            $ca = $this->read_ca_data($file_path, $cabins_info);
//            exit(json_encode($ca));
//            foreach ($ca as $value1) {
//                if ($table2->addAll($value1['ca_data']) === false) {
//                    M()->rollback();
//                    exit(jsonreturn(array('code' => 4, 'error' => $table2->getDbError())));
//                };
//            }
//        }

//        if ($table_name_3 != "") {
//            $table3 = M($table_name_3);
////            exit(json_encode($ca));
//            foreach ($list_data['data'] as $value1) {
//                //横倾修正表，录入时不报错,防止影响正常的纵倾修正表录入业务
//                @$table3->addAll($value1['list_data']);
//            }
//        }

        M()->commit();
        exit(jsonreturn(array('code' => 1, 'msg' => "导入成功")));
    }

    /**
     * 正则匹配文本内的纵倾修正表数据并返回
     * @param $trim_kedu
     * @param $file_path
     * @return array
     */
    function read_word_trim_data($trim_kedu, $file_path, $cabins_info)
    {
        //        https://regex101.com/r/ozVP4k/2 纵倾修正表正则视图

        $orgin_txt = file_get_contents($file_path);
//        dump($orgin_txt);
        $re = '/\s*?证书编号\s*?(?:\:|：)\s*?([a-zA-Z0-9]+)\s*?船名\s*?(?:\:|：)\s*?(\S+)\s*?第\s*?(\d+)\s*?页\s*?舱名(?:\:|：)\s*?(\S+)\s*?\S*?\s*?基准高度\/REFERENCE\s*?HEIGHT\:\s*?([\d]{1,2}\.[\d]{0,3})\(m\)\s*?\*+\s*?纵\s*?倾\s*?值\/TRIM\s*?BY\s*?STERN\s*?测\s*?深\s*?空\s*?高\s*?\*+\s*?SOUNDING\s*?ULLAGE\s*?([ \t\-\.\d]+)\s*?(?:\(m\)\s+)+\*+\s+([0-9\.\- \r\n]+)/m';
        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
        $res = array();
        $trim_kedu = json_decode($trim_kedu, true);

//        exit(jsonreturn($matches));

        foreach ($matches as $key => $value) {
            $data = array();
            //处理页数编号等信息
//            echo "第".$value[1]."页 ， 有效期：".$value[2]."， 舱号：".$value[3].", 船名：".$value[4]."，书编号：".$value[5]."<br/>";
            $data['page'] = $value[3];
            $data['cabin_name'] = preg_replace("/[左右]+污油舱 /", "", $value[4]);
            $data['ship_name'] = $value[2];
            $data['book_number'] = $value[1];
            $cabin_id = 0;
            $cabin_name = trimall($data['cabin_name']);
            $digital_cabin_name = $this->chineseToDigital($cabin_name);
            foreach ($cabins_info as $v11) {
                if ($cabin_name == $v11['cabinname'] or $digital_cabin_name == $v11['cabinname']) {
                    $cabin_id = $v11['id'];
                }
            }
            if ($cabin_id == 0) continue;
            //开始分开吃水刻度
            $kedu_str = $this->removeExtraSpace($value[6]);
//            exit($kedu_str);

            $kedu = explode(' ', $kedu_str);
//            $kedu[count($kedu) - 1] = str_replace("m", "", $kedu[count($kedu) - 1]);
//            exit(jsonreturn($kedu));
//            exit;
            $data['kedu'] = $kedu;
//            echo "<table style='text-align: center' border='1px solid'>";
//            echo "<thead><th>实高</th><th>空高</th>";
//            foreach ($kedu as $k=>$v){
//                echo "<th>".$v."</th>";
//            }
//            echo "</thead>";
//            echo "<tbody>";
            $data_row = explode("\r\n", $this->removeExtraSpace($value[7]));
//            array_pop($data_row);
//            exit(jsonreturn($data_row));

            $data['tirm_data'] = array();
            foreach ($data_row as $k1 => $v1) {
                $qian = array(" ", "　", "   ", "    ", '-', '0', '.');
                $hou = array("", "", "", "", "", "", "");
                if (str_replace($qian, $hou, $v1) == "") continue;
                $data_cloumn = explode(" ", $v1);
                $data_cloumn[count($data_cloumn) - 1] = str_replace("\r", "", $data_cloumn[count($data_cloumn) - 1]);

                $td = array('sounding' => $data_cloumn[0], 'ullage' => $data_cloumn[1], 'cabinid' => $cabin_id);
                $i = 0;
//                print_r($trim_kedu);
                foreach ($trim_kedu as $k2 => $v2) {
                    $td["$k2"] = $data_cloumn[$i + 2];
                    $i++;
                }
//                exit(jsonreturn($td));

                array_push($data['tirm_data'], $td);
//                foreach ($data_cloumn as $k2=>$v2){
//                    echo "<td>".$v2."</td>";
//                }
//                echo "</tr>";
            }

            if (count($data['tirm_data']) >= 1) {
                array_push($res, $data);
            }
//            echo "</tbody>";
//            echo "</table>";
//            $ullage =
//            echo "<br/>";
        }
        return $res;
//        exit(json_encode($res));
    }

    /**
     * 去除文本内多余空格，并且去除头部和结尾的空格
     * @param $txt
     * @return string
     */
    public function removeExtraSpace($txt)
    {
        $txt1 = preg_replace("/^ {2,}/m", "", $txt);
        $txt2 = preg_replace("/(\d) {2,}/m", "$1 ", $txt1);
        $txt3 = preg_replace("/ {2,}$/m", "", $txt2);
        return $txt3;
    }

    /**
     * 去除中文汉字转数字
     * @param $txt
     * @return string
     */
    public function chineseToDigital($txt)
    {
        $arr1 = array('一', '二', '三', '四', '五', '六', '七', '八', '九');
        $arr2 = array('1', '2', '3', '4', '5', '6', '7', '8', '9');
        $txt1 = str_replace($arr1, $arr2, $txt);
        return $txt1;
    }
}