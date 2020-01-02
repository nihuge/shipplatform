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
        $img_dir = './Upload/table/test11123.jpg';
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
            'request_type'=>'json',
        );
        $result = $Ocr->tableRecognitionAsync($img,$option);
//        $result_id = $result['result'][0]["request_id"];
        $result['log_id'] = number_format($result['log_id'], 0, '', '');
        echo json_encode($result);
        echo "共耗时：".number_format(microtime(1) - $start_time, 6);
    }


}