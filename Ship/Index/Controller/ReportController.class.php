<?php

namespace Index\Controller;

use Think\Controller;

/**
 * 验证
 * */
class ReportController extends Controller
{
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
        $img_dir = './Upload/table/ttt2.jpg';
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

        if(!$weight or !$resultid or !$solt or !$cabinid){
            exit(json_encode(array('code'=>4)));
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

        $listmsg =$result_record->field('qufen,is_pipeline,temperature')->where(array('resultid'=>$resultid,'cabinid'=>$cabinid,'solt'=>$solt))->find();

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
        $suanfa = ($a - $b) / ($c - $d) * ($e - $d) + $b;
        return $suanfa;
    }

    /**
     *
     */
    public function aaa(){
        $ship = new \Common\Model\ShipFormModel();
        echo jsonreturn($ship->updata_data_ship());
    }

}