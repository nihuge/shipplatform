<?php

namespace Common\Model;

use Common\Model\BaseModel;

/**
 * 作业Model
 * */
class ShResultModel extends BaseModel
{
    /**
     * 自动验证
     */
    protected $_validate = array(
        //array('voyage','1,7','航次长度不能超过7个字符',0,'length'),//存在即验证 长度不能超过12个字符
        array('locationname', '0,7', '作业地点长度不能超过7个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        //array('start','0,7','起运港长度不能超过7个字符',0,'length'),//存在即验证 长度不能超过12个字符
        //array('objective','0,7','目的港长度不能超过7个字符',0,'length'),//存在即验证 长度不能超过12个字符
        //array('goodsname','0,7','货名长度不能超过7个字符',0,'length'),//存在即验证 长度不能超过12个字符
        array('imei', '0,32', '标识长度不能超过32个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        //array('transport','0,10','运单量长度不能超过10个字符',0,'length'),//存在即验证 长度不能超过12个字符
        array('uid', '/^[1-9]\d*$/', '用户ID必须为自然数', 0, 'regex'),//存在即验证 必须为正整数
        array('shipid', '/^[1-9]\d*$/', '船ID必须为自然数', 0, 'regex'),//存在即验证 必须为正整数
        array('reamrk', '0,200', '备注长度不能超过200个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        //array('shipper','0,100','发货方长度不能超过100个字符',0,'length'),//存在即验证 长度不能超过12个字符
        //array('feedershipname','0,20','海船船名长度不能超过20个字符',0,'length'),//存在即验证 长度不能超过12个字符
        //array('number','0,20','编号长度不能超过20个字符',0,'length'),//存在即验证 长度不能超过12个字符
        //array('wharf','0,7','海船装运码头长度不能超过7个字符',0,'length'),//存在即验证 长度不能超过12个字符
        //array('inspection','0,7','海船商检量长度不能超过7个字符',0,'length'),//存在即验证 长度不能超过12个字符
    );
    private $process = "";
    static $function_process = "";

    /**
     * 添加作业数据
     * @param array $data 添加的数据
     * @return int          新增的数据id
     */
    public function addResult($data, $uid)
    {
        $datas = $data;
        //判断船驳舱容表时间是否到期
        $ship = new \Common\Model\ShShipModel();
        $expire_time = $ship->getFieldById($data['shipid'], 'expire_time');
        if ($expire_time > time()) {
            //判断相同船是否有相同的航次
            $result = new \Common\Model\ShResultModel();
            $v = trimall(I('post.voyage'));
            $voyage = '"voyage":"' . $v . '"';
            $where = array(
                'shipid' => I('post.shipid'),
                'personality' => array('like', '%' . $voyage . '%')
            );
            $res = $result
                ->where($where)
                ->count();
            if ($res < '1') {
                // 获取公司的ID
                $user = new \Common\Model\UserModel();
                $firmid = $user->getFieldById($uid, 'firmid');

                M()->startTrans();
                // 对data数据进行验证
                if (!$this->create($data)) {
                    // 验证不通过返回错误
                    // 数据格式有错	7
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                        // 'msg'	 =>	$this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['ERROR_DATA']]
                        'msg' => $this->getError()
                    );
                    // $a = $this->getError();
                    // writeLog($a);
                } else {
                    // 组装个性化数据
                    $data = $this->arrange_data($data);

                    $id = $this->addData($data);
                    if ($id) {
                        // 作业扣费
                        $consump = new \Common\Model\ConsumptionModel();

                        //这里加上扣费区分，散货船扣费时，标注是散货
                        $arr = $consump->buckleMoney($uid, $firmid, 'Sh' . $id);
                        if ($arr['code'] == '1') {
                            #todo 添加船舶统计停泊港功能，自动添加公司，用户，船的历史作业次数。
                            // 扣费成功
                            // 根据船ID获取是否底量字段

                            /*                            $ship = new \Common\Model\ShipModel();
                                                        $shipmsg = $ship
                                                            ->field('is_diliang,suanfa')
                                                            ->where(array('id' => $data['shipid']))
                                                            ->find();
                                                        $is_have_data = $ship->is_have_data($data['shipid']);
                                                        if (isset($datas['start']) || isset($datas['objective'])) {
                                                            // 获取船舶原始起始点、终点港原来的统计停泊港
                                                            $moorings = M('ship_historical_sum')->getFieldByshipid($data['shipid'], 'mooring');
                                                            if (empty($moorings)) {
                                                                $moorings = array();
                                                            } else {
                                                                $moorings = explode(',', $moorings);
                                                            }


                                                            array_push($moorings, $datas['start']);
                                                            array_push($moorings, $datas['objective']);
                                                            $moorings = array_unique($moorings);
                                                            $moorings = implode(',', $moorings);
                                                            // 修改船舶统计停泊港
                                                            M('ship_historical_sum')->where(array('shipid' => $data['shipid']))->save(array('mooring' => $moorings));
                                                        }

                                                        // 修改公司历史数据--作业次数
                                                        M('firm_historical_sum')->where(array('firmid' => $firmid))->setInc('num');
                                                        M('user_historical_sum')->where(array('userid' => $uid))->setInc('num');
                                                        M('ship_historical_sum')->where(array('shipid' => $data['shipid']))->setInc('num');*/


                            M()->commit();
                            //成功 1
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']],
                                'resultid' => $id,
//                                'd' => $shipmsg['is_diliang'],
//                                'is_have_data' => $is_have_data,
//                                'suanfa' => $shipmsg['suanfa']
                            );
                        } else {
                            // 扣费失败 8
                            M()->rollback();
                            $res = $arr;    //返回错误信息码
                        }
                    } else {
                        M()->rollback();
                        //数据库连接错误	3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                            'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DB_ERROR']],
                        );
                    }
                }
            } else {
                // 已存在相同的作业！ 2018
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['IS_REPEAT_RESULT'],
                    'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['IS_REPEAT_RESULT']],
                );
            }
        } else {
            //船舶舱容表已到期 2015
            $res = array(
                'code' => $this->ERROR_CODE_RESULT['EXPIRETIME_TIME_RONG'],
                'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['EXPIRETIME_TIME_RONG']]
            );
        }
        return $res;
    }

    /**
     * 修改作业数据
     */
    public function editResult($data)
    {
        //判断相同船是否有相同的航次
        $voyage = '"voyage":"' . $data['voyage'] . '"';

        $where = array(
            'shipid' => $data['shipid'],
            'personality' => array('like', '%' . $voyage . '%'),
            'id' => array('NEQ', $data['resultid'])
        );
        $res = $this
            ->where($where)
            ->count();
        if ($res < '1') {
            //判断指令有没有作业，
            $rl = new \Common\Model\ShResultlistModel();
            // $re = $rl->where(array('resultid'=>I('post.resultid')))->count();
            $re = $rl->valiname($data['resultid'], 'resultid');
            if ($re === false) {
                // 指令有作业，不能修改 2004
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['IS_RESULT_IS'],
                    'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['IS_RESULT_IS']],
                );
            } else {
                // 对数据进行验证
                if (!$this->create($data)) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    // $this->error($result->getError());
                    //数据格式有错   7
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                        'msg' => $this->getError()
                    );
                } else {
                    // 组装个性化数据
                    $data = $this->arrange_data($data);

                    //修改数据
                    $map = array(
                        'id' => $data['resultid']
                    );
                    $msg = $this->editData($map, $data);
                    if ($msg !== false) {
                        //成功 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']],
                            'resultid' => I('post.resultid')
                        );
                    } else {
                        //数据库连接错误   3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                            'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DB_ERROR']],
                        );
                    }
                }
            }
        } else {
            // 已存在相同的作业！ 2018
            $res = array(
                'code' => $this->ERROR_CODE_RESULT['IS_REPEAT_RESULT'],
                'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['IS_REPEAT_RESULT']],
            );
        }
        return $res;
    }

    /**
     * 作业详情查看
     * @param string resultid 计量ID
     * @param string imei 标识
     * @param int uid 用户ID
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function resultsearch($resultid, $uid, $imei)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($uid, $imei);
        if ($msg1['code'] == '1') {
            //获取水尺数据
            $where = array(
                'r.id' => $resultid
            );


            #todo 每一位数据自动去除没用的0
            //查询作业列表
            $list = $this
                ->alias('r')
                ->field('r.*,s.shipname,0 + CAST(s.lbp AS CHAR) as lbp,0 + CAST(s.df AS CHAR) as df , 0 + CAST(s.da AS CHAR) as da, 0 + CAST(s.dm AS CHAR) as dm, 0 + CAST(s.weight AS CHAR) as ship_weight, u.username,f.firmtype as ffirmtype')
                ->join('left join sh_ship s on s.id=r.shipid')
                ->join('left join user u on r.uid = u.id')
                ->join('left join firm f on u.firmid = f.id')
                ->where($where)
                ->find();
            unset($list['qianprocess']);
            unset($list['houprocess']);

            $record = M("sh_resultrecord");

            $where_ds = array(
                'resultid' => $resultid
            );
            $ds = $record->where($where_ds)->select();
            foreach ($ds as $keyds => $valueds) {
                unset($ds[$keyds]['process']);
            }

            $wherelist_qian = array(
                'resultid' => $resultid,
                'solt' => 1,
            );

            $wherelist_hou = array(
                'resultid' => $resultid,
                'solt' => 2,
            );

            $resultlist = new \Common\Model\ShResultlistModel();
            $total_weight_qian = $resultlist->field('sum(weight) as t_weight')->where($wherelist_qian)->find();
            $total_weight_hou = $resultlist->field('sum(weight) as t_weight')->where($wherelist_hou)->find();

            $list['qian_bw'] = $total_weight_qian['t_weight'];
            $list['hou_bw'] = $total_weight_hou['t_weight'];

            //获取水尺数据
            $where = array(
                'resultid' => $resultid,
            );
            $forntrecord = M("sh_forntrecord");

            $msg = $forntrecord
                ->field('forntleft,forntright,centerleft,centerright,afterleft,afterright,fornt,center,after,solt')
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

            #todo 详情得到水尺照片
            /*// 获取水尺照片
            $forntimg = M('fornt_img')
                ->field('img,types,solt')
                ->where(array('result_id' => $resultid))
                ->select();
            $list['qianchiimg'] = array();
            $list['houchiimg'] = array();
            foreach ($forntimg as $key => $value) {
                if ($value['solt'] == '1') {
                    array_push($list['qianchiimg'], $value['img']);
                } else {
                    array_push($list['houchiimg'], $value['img']);
                }
            }*/

            // 个性化组装
            $personality = json_decode($list['personality'], true);
            $personality['num'] = count($personality);
            unset($list['personality']);

            /*// 判断作业是否完成----电子签证
            $coun = M('electronic_visa')
                ->where(array('resultid' => $resultid))
                ->count();
            if ($coun > 0) {
                $ship = new \Common\Model\ShipModel();
                // 船舶所属公司
                $rfirmid = $ship->getFieldById($list['shipid'], 'firmid');
                if ($list['uid'] == $uid) {
                    // 检验公司评价
                    if ($list['grade1'] != 0) {
                        $list['is_coun'] = '4';
                    } else {
                        $list['is_coun'] = '2';
                    }
                } elseif ($rfirmid == $a['id']) {
                    // 船舶公司评价
                    if ($list['grade2'] != 0) {
                        $list['is_coun'] = '4';
                    } else {
                        $list['is_coun'] = '2';
                    }
                } else {
                    $list['is_coun'] = '1';
                }
            } else {
                $list['is_coun'] = '3';
            }*/

            if ($list !== false) {
                $where1 = array('resultid' => $list['id']);
                $resultlist = new \Common\Model\ShResultlistModel();

                $resultmsg = $resultlist
                    ->where($where1)
                    ->order('solt asc')
                    ->select();
                // 以舱区分数据
                $result = '';
                foreach ($resultmsg as $k => $v) {
                    #todo 获取舱作业照片

                    /*$v['ullageimg'] = array();
                    $v['soundingimg'] = array();
                    $v['temperatureimg'] = array();
                    // 获取作业照片
                    $listimg = M('resultlist_img')
                        ->where(array('resultlist_id' => $v['id']))
                        ->select();
                    foreach ($listimg as $key => $value) {
                        if ($value['types'] == '1') {
                            $v['ullageimg'][] = $value['img'];
                        } else if ($value['types'] == '2') {
                            $v['soundingimg'][] = $value['img'];
                        } else if ($value['types'] == '3') {
                            $v['temperatureimg'][] = $value['img'];
                        }
                    }*/

                    $result[$v['id']][] = $v;
                }

                #todo 获取本次作业共耗时多少
                /*if (!empty($resultmsg)) {
                    //取出舱详情最后一个元素时间
                    $start = end($resultmsg);
                    $starttime = date("Y-m-d H:i", $start['time']);
                    //取出舱详情第一个元素时间
                    $end = reset($resultmsg);
                    $endtime = date("Y-m-d H:i", $end['time']);
                } else {
                    $starttime = '';
                    $endtime = '';
                }*/

                $a = array();
                foreach ($result as $key => $value) {
                    $a[] = $value;
                }
                //成功	1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $list,
                    'ds' => $ds,
                    'fornt' => $forntData,
                    'personality' => $personality,
                    'resultmsg' => $a,
                );
            } else {
                //数据库连接错误	3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                );
            }
        } else {
            // 错误信息返回码
            $res = $msg1;
        }
        return $res;
    }

    /**
     * 获取作业水尺
     * @param string resultid 计量ID
     * @param string imei 标识
     * @param int uid 用户ID
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function forntsearch($resultid)
    {
        //获取水尺数据
        $where = array(
            'resultid' => $resultid,
        );
        $forntrecord = M("sh_forntrecord");

        $msg = $forntrecord
            ->field('forntleft,forntright,centerleft,centerright,afterleft,afterright,fornt,center,after,solt')
            ->where($where)
            ->select();

//        $result = new \Common\Model\ShResultModel();

        if ($msg !== false) {
            $data = array();
            $result_msg = $this->field('hou_fwater_weight,hou_sewage_weight,hou_fuel_weight,hou_other_weight,qian_fwater_weight,qian_sewage_weight,qian_fuel_weight,qian_other_weight,qian_pwd,hou_pwd')
                ->where(array('id' => $resultid))
                ->find();
            if ($result_msg !== false) {
                foreach ($msg as $key => $value) {
                    if ($value['solt'] == 1) {
                        $msg[$key]['fwater_weight'] = $result_msg['qian_fwater_weight'];
                        $msg[$key]['sewage_weight'] = $result_msg['qian_sewage_weight'];
                        $msg[$key]['fuel_weight'] = $result_msg['qian_fuel_weight'];
                        $msg[$key]['other_weight'] = $result_msg['qian_other_weight'];
                        $msg[$key]['pwd'] = $result_msg['qian_pwd'];
                        $data['q'] = $msg[$key];
                    } else {
                        $msg[$key]['fwater_weight'] = $result_msg['hou_fwater_weight'];
                        $msg[$key]['sewage_weight'] = $result_msg['hou_sewage_weight'];
                        $msg[$key]['fuel_weight'] = $result_msg['hou_fuel_weight'];
                        $msg[$key]['other_weight'] = $result_msg['hou_other_weight'];
                        $msg[$key]['pwd'] = $result_msg['hou_pwd'];
                        $data['h'] = $msg[$key];
                    }
                }

                //成功 1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $data
                );
            } else {
                //数据库连接错误	3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                );
            }
            #todo 加上水尺照片功能
            /*$data = M('fornt_img')
                ->where(array('result_id' => $resultid, 'solt' => $msg['solt']))
                ->select();

            if (empty($data)) {
                $msg['firstfiles'] = array();
                $msg['tailfiles'] = array();
            } else {
                foreach ($data as $key => $value) {
                    if (file_exists($value['img'])) {
                        if ($value['types'] == '1') {
                            $msg['firstfiles'][] = $value['img'];
                        } else {
                            $msg['tailfiles'][] = $value['img'];
                        }
                    }

                }
            }
            if (empty($msg['firstfiles'])) {
                $msg['firstfiles'] = array();
            }
            if (empty($msg['tailfiles'])) {
                $msg['tailfiles'] = array();
            }
*/


        } else {
            //数据库连接错误	3
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
            );
        }
        return $res;
    }

    /**
     * 水尺操作(水尺信息记录、修改作业水尺差数据)
     */
    public function forntOperation($datas)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($datas['uid'], $datas['imei']);

        if ($msg1['code'] == '1') {
            $res_num = $this->where(array('id' => $datas['resultid']))->count();
            if ($res_num > 0) {

//                if($datas['solt'] == '2'){
//
//                }

                //开始接受值到变量，类型转换，防止用户乱填
                // 艏左，艏右
                $forntleft = (float)$datas['forntleft'];
                $forntright = (float)$datas['forntright'];
                // 艉左，艉右
                $afterleft = (float)$datas['afterleft'];
                $afterright = (float)$datas['afterright'];
                //舯左，舯右
                $centerleft = (float)$datas['centerleft'];
                $centerright = (float)$datas['centerright'];
                //获取港水密度
                $PWD = (float)$datas['pwd'];


                //获取船数据
                $ship = new \Common\Model\ShShipModel();
                $result_ship_id = $this->field('shipid')->where(array('id' => $datas['resultid']))->find();
                $wheres = array(
                    'id' => $result_ship_id['shipid'],
                );
                $ship_msg = $ship->field('lbp,df,da,dm')->where($wheres)->find();
//            exit(json_encode($ship_msg));
                $LBP = (float)$ship_msg['lbp'];

                //获取水尺距垂线距离
                $Df = (float)$ship_msg['df'];
                $Da = (float)$ship_msg['da'];
                $Dm = (float)$ship_msg['dm'];

                //初始化水尺相对垂线位置
                $Pf = 0;
                $Pa = 0;
                $Pm = 0;

                if ($Df <= 0) {
                    $Pf = 1;
                    $Df = abs($Df);
                }

                if ($Da <= 0) {
                    $Pa = 1;
                    $Da = abs($Da);
                }

                if ($Dm <= 0) {
                    $Pm = 1;
                    $Dm = abs($Dm);
                }

                //计算平均吃水差

                $Fps = round(($forntleft + $forntright) / 2, 5);
                $Aps = round(($afterleft + $afterright) / 2, 5);
                $Mps = round(($centerleft + $centerright) / 2, 5);

                //拿到吃水差
                $T = $Aps - $Fps;

                //吃水差正负状态
                if ($T > 0) {
                    $Tf = 0;
                } else {
                    $Tf = 1;
                }

                $Fflag = $Tf ^ $Pf; //矫正吃水flag
                $Aflag = $Tf ^ $Pa; //矫正吃水flag
                $Mflag = $Tf ^ $Pm; //矫正吃水flag

                $LBM = round($LBP + pow(-1, $Pf) * $Df - pow(-1, $Pa) * $Da, 5);//计算艏艉水尺间长

                $Fc = round(abs($Df * $T / $LBM), 5);//计算艏吃水矫正值
                $Ac = round(abs($Da * $T / $LBM), 5);//计算艉吃水校正值
                $Mc = round(abs($Dm * $T / $LBM), 5);//舯吃水校正值

                $Fm = round($Fps + pow(-1, $Fflag) * $Fc, 5);//计算校正后艏吃水
                $Am = round($Aps + pow(-1, $Aflag) * $Ac, 5);//计算校正后艉吃水
                $Mm = round($Mps + pow(-1, $Mflag) * $Mc, 5);//计算校正后舯吃水

                $TC = round($Am - $Fm, 5);//计算矫正后吃水差

                $D_M = round(($Fm + $Am + (6 * $Mm)) / 8, 5);//计算拱陷矫正后总平均吃水

                $this->process .= "nowtime:" . date('Y-m-d H:i:s', time()) . "------------------\r\nforntleft=" . $forntleft . ",forntright=" . $forntright
                    . ",\r\n afterleft=" . $afterleft . ",afterright=" . $afterright . ",\r\n centerleft=" . $centerleft . ",centerright=" . $centerright . ",\r\np=" . $PWD
                    . ",LBP=" . $LBP . ",\r\n Df=" . $Df . ",Da=" . $Da . ",Dm=" . $Dm . ",\r\nPf=" . $Pf . ",Pa=" . $Pa . ",Pm=" . $Pm
                    . ",\r\nFPS=round(($forntleft + $forntright) / 2, 5)=" . $Fps . ",Aps=round(($afterleft + $afterright) / 2, 5)=" . $Aps . ",Mps=round(($centerleft + $centerright) / 2, 5)=" . $Mps
                    . ",\r\nT=$Aps - $Fps=" . $T . " then Tf=" . $Tf . ",Fflag=" . $Fflag . ",Aflag=" . $Aflag . ",Mflag=" . $Mflag
                    . ",\r\nLBM=round($LBP + pow(-1, $Pf) * $Df - pow(-1, $Pa) * $Da, 5)=" . $LBM
                    . ",\r\nFc=round(abs($Df * $T / $LBM), 5)=" . $Fc . ",Ac=round(abs($Da * $T / $LBM), 5)=" . $Ac . ",Mc=round(abs($Dm * $T / $LBM), 5)=" . $Mc
                    . ",\r\nFm=round($Fps + pow(-1, $Fflag) * $Fc, 5)=" . $Fm . ",Am=round($Aps + pow(-1, $Aflag) * $Ac, 5)=" . $Am . ",Mm=round($Mps + pow(-1, $Mflag) * $Mc, 5)=" . $Mm
                    . ",\r\nTC = round($Am - $Fm, 5)=" . $TC . ",D_M=round(($Fm + $Am + (6 * $Mm)) / 8, 5)=" . $D_M . "\r\n";

                //水尺数据
                $data = array(
                    'forntleft' => $forntleft,
                    'forntright' => $forntright,
                    'centerleft' => $centerleft,
                    'centerright' => $centerright,
                    'afterleft' => $afterleft,
                    'afterright' => $afterright,
                    'fornt' => $Fps,
                    'center' => $Mps,
                    'after' => $Aps,
                    'fc' => $Fc,
                    'ac' => $Ac,
                    'mc' => $Mc,
                    'fm' => $Fm,
                    'am' => $Am,
                    'mm' => $Mm,
                    'solt' => $datas['solt'],
                    'resultid' => $datas['resultid']
                );

                //作业表的数据
                $data1 = array(
                    'solt' => $datas['solt'],
                );
                $result_field = "";
                if ($datas['solt'] == '1') {
                    $this->process .= "soltType=作业前 then:\r\n";
                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['qian_tc'] = $TC;
                    $data1['qian_d_m'] = $D_M;
                    $data1['qian_pwd'] = (float)$datas['pwd'];//存储密度
                    $data1['qian_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                    $data1['qian_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                    $data1['qian_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                    $data1['qian_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                    $result_field = "qian_dspc as dspc,qian_constant as constant";
                    $this->process .= "\t TC=" . $data1['qian_tc'] . ",D_M=" . $data1['qian_d_m'] . "\r\n fwater_weight=" . $data1['qian_fwater_weight']
                        . ",sewage_weight=" . $data1['qian_sewage_weight'] . ",fuel_weight=" . $data1['qian_fuel_weight'] . ",fwater_weight=" . $data1['qian_other_weight'] . "\r\n";

                    $data1['qianprocess'] = urlencode($this->process);
                } elseif ($datas['solt'] == '2') {
                    $this->process .= "soltType: 作业后 then:\r\n";
                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['hou_tc'] = $TC;
                    $data1['hou_d_m'] = $D_M;
                    $data1['hou_pwd'] = $datas['pwd'];//存储密度
                    $data1['hou_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                    $data1['hou_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                    $data1['hou_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                    $data1['hou_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                    $result_field = "hou_dspc as dspc,hou_constant as constant";
                    $this->process .= "\t TC=" . $data1['hou_tc'] . ",D_M=" . $data1['hou_d_m'] . "\r\n";
                    //将过程存入数据库
                    $data1['houprocess'] = urlencode($this->process);
                }

                // 判断水尺数据是否存在 添加/修改数据
                $map = array(
                    'solt' => $datas['solt'],
                    'resultid' => $datas['resultid']
                );
                $num = M('sh_forntrecord')->where($map)->count();
                M()->startTrans();  // 开启事物
                if ($num > 0) {
                    //数据存在--修改
                    $r = M('sh_forntrecord')->where($map)->save($data);
                } else {
                    //数据不存在--新增
                    $r = M('sh_forntrecord')->add($data);
                }


                #todo 支持添加水尺计量图片
                /*$datafile = array();
                // 判断是否存在首吃水 尾吃水
                if (!empty($datas['firstfiles']) && $datas['firstfiles'] != '[]') {
                    $firstfiles = substr($datas['firstfiles'], 1);
                    $firstfiles = substr($firstfiles, 0, -1);
                    $firstfiles = explode(',', $firstfiles);
                    foreach ($firstfiles as $key => $value) {
                        $datafile[] = array(
                            'img' => trim($value),
                            'result_id' => $datas['resultid'],
                            'types' => 1,
                            'solt' => $datas['solt']
                        );
                    }
                }

                // 判断是否存在尾吃水
                if (!empty($datas['tailfiles']) && $datas['tailfiles'] != '[]') {

                    $tailfiles = substr($datas['tailfiles'], 1);
                    $tailfiles = substr($tailfiles, 0, -1);
                    $tailfiles = explode(',', $tailfiles);
                    foreach ($tailfiles as $key => $value) {
                        $datafile[] = array(
                            'img' => trim($value),
                            'result_id' => $datas['resultid'],
                            'types' => 2,
                            'solt' => $datas['solt']
                        );
                    }
                }

                // 删除原有的首吃水 尾吃水
                $fornt_img = M('fornt_img')->where(array('result_id' => $datas['resultid'], 'solt' => $datas['solt']))->select();

                if (!empty($datafile)) {
                    M('fornt_img')->where(array('result_id' => $datas['resultid'], 'solt' => $datas['solt']))->delete();
                    // 新增图片
                    $aa = M('fornt_img')->addAll($datafile);
                    if ($aa == false) {
                        M()->rollback();
                        // 数据库错误	3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                        );
                        echo jsonreturn($res);
                        die;
                    }
                }*/

                // 添加/修改数据
                $m = array(
                    'id' => $datas['resultid']
                );
                $res = $this->editData($m, $data1);
                // 修改数据成功
                if ($res['code'] !== false and $r !== false) {
                    #todo 如果存在已有的作业数据，则重新计算已录入的数据
                    M()->commit();
                    $r_data = $this->field($result_field)->where($m)->find();

                    if ($r_data['dspc'] > 0) {
                        $dspc_res = $this->suanDspc($datas['resultid'], $datas['solt']);
                    } else {
                        $dspc_res['code'] = 1;
                    }

                    if ($r_data['constant'] > 0) {
                        $weight_res = $this->suanWeight($datas['resultid']);
                    } else {
                        $weight_res['code'] = 1;
                    }

                    if ($dspc_res['code'] == 1 and $weight_res['code'] == 1) {
                        //成功 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'D_M' => $D_M,
                        );
                    } else {
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                            'dspc' => $dspc_res['code'],
                            'weight' => $weight_res['code'],
                        );
                    }
                    /*$where = array(
                        'solt' => $datas['solt'],
                        'resultid' => $datas['resultid'],
                        'is_work' => array('eq', 1)
                    );
                    // 如果存在作业数据，重新计算已录入的数据
                    $n = M('resultrecord')->where($where)->select();
                    if (!empty($n)) {
                        foreach ($n as $key => $value) {
                            $this->process = "";
                            $value['uid'] = $datas['uid'];
                            $value['imei'] = $datas['imei'];
                            $this->reckon($value);
                        }
                    }*/

                } else {
                    //数据库连接错误	3
                    M()->rollback();
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                );
            }
        } else {
            // 错误信息
            $res = $msg1;
        }
        return $res;
    }


    public function forntOperation1($datas)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($datas['uid'], $datas['imei']);

        if ($msg1['code'] == '1') {
            $res_num = $this->where(array('id' => $datas['resultid']))->count();
            if ($res_num > 0) {

//                if($datas['solt'] == '2'){
//
//                }

                //开始接受值到变量，类型转换，防止用户乱填
                // 艏左，艏右
                $forntleft = (float)$datas['forntleft'];
                $forntright = (float)$datas['forntright'];
                // 艉左，艉右
                $afterleft = (float)$datas['afterleft'];
                $afterright = (float)$datas['afterright'];
                //舯左，舯右
                $centerleft = (float)$datas['centerleft'];
                $centerright = (float)$datas['centerright'];
                //获取港水密度
                $PWD = (float)$datas['pwd'];


                //获取船数据
                $ship = new \Common\Model\ShShipModel();
                $result_ship_id = $this->field('shipid')->where(array('id' => $datas['resultid']))->find();
                $wheres = array(
                    'id' => $result_ship_id['shipid'],
                );
                $ship_msg = $ship->field('lbp,df,da,dm')->where($wheres)->find();
//            exit(json_encode($ship_msg));
                $LBP = (float)$ship_msg['lbp'];

                //获取水尺距垂线距离
                $Df = (float)$ship_msg['df'];
                $Da = (float)$ship_msg['da'];
                $Dm = (float)$ship_msg['dm'];

                //初始化水尺相对垂线位置
                $Pf = 0;
                $Pa = 0;
                $Pm = 0;

                if ($Df <= 0) {
                    $Pf = 1;
                    $Df = abs($Df);
                }

                if ($Da <= 0) {
                    $Pa = 1;
                    $Da = abs($Da);
                }

                if ($Dm <= 0) {
                    $Pm = 1;
                    $Dm = abs($Dm);
                }

                //计算平均吃水差

                $Fps = round(($forntleft + $forntright) / 2, 5);
                $Aps = round(($afterleft + $afterright) / 2, 5);
                $Mps = round(($centerleft + $centerright) / 2, 5);

                //拿到吃水差
                $T = $Aps - $Fps;

                //吃水差正负状态
                if ($T > 0) {
                    $Tf = 0;
                } else {
                    $Tf = 1;
                }

                $Fflag = $Tf ^ $Pf; //矫正吃水flag
                $Aflag = $Tf ^ $Pa; //矫正吃水flag
                $Mflag = $Tf ^ $Pm; //矫正吃水flag

                $LBM = round($LBP + pow(-1, $Pf) * $Df - pow(-1, $Pa) * $Da, 5);//计算艏艉水尺间长

                $Fc = round(abs($Df * $T / $LBM), 5);//计算艏吃水矫正值
                $Ac = round(abs($Da * $T / $LBM), 5);//计算艉吃水校正值
                $Mc = round(abs($Dm * $T / $LBM), 5);//舯吃水校正值

                $Fm = round($Fps + pow(-1, $Fflag) * $Fc, 5);//计算校正后艏吃水
                $Am = round($Aps + pow(-1, $Aflag) * $Ac, 5);//计算校正后艉吃水
                $Mm = round($Mps + pow(-1, $Mflag) * $Mc, 5);//计算校正后舯吃水

                $TC = round($Am - $Fm, 5);//计算矫正后吃水差

                $D_M = round(($Fm + $Am + (6 * $Mm)) / 8, 5);//计算拱陷矫正后总平均吃水

                $this->process .= "nowtime:" . date('Y-m-d H:i:s', time()) . "------------------\r\nforntleft=" . $forntleft . ",forntright=" . $forntright
                    . ",\r\n afterleft=" . $afterleft . ",afterright=" . $afterright . ",\r\n centerleft=" . $centerleft . ",centerright=" . $centerright . ",\r\np=" . $PWD
                    . ",LBP=" . $LBP . ",\r\n Df=" . $Df . ",Da=" . $Da . ",Dm=" . $Dm . ",\r\nPf=" . $Pf . ",Pa=" . $Pa . ",Pm=" . $Pm
                    . ",\r\nFPS=round(($forntleft + $forntright) / 2, 5)=" . $Fps . ",Aps=round(($afterleft + $afterright) / 2, 5)=" . $Aps . ",Mps=round(($centerleft + $centerright) / 2, 5)=" . $Mps
                    . ",\r\nT=$Aps - $Fps=" . $T . " then Tf=" . $Tf . ",Fflag=" . $Fflag . ",Aflag=" . $Aflag . ",Mflag=" . $Mflag
                    . ",\r\nLBM=round($LBP + pow(-1, $Pf) * $Df - pow(-1, $Pa) * $Da, 5)=" . $LBM
                    . ",\r\nFc=round(abs($Df * $T / $LBM), 5)=" . $Fc . ",Ac=round(abs($Da * $T / $LBM), 5)=" . $Ac . ",Mc=round(abs($Dm * $T / $LBM), 5)=" . $Mc
                    . ",\r\nFm=round($Fps + pow(-1, $Fflag) * $Fc, 5)=" . $Fm . ",Am=round($Aps + pow(-1, $Aflag) * $Ac, 5)=" . $Am . ",Mm=round($Mps + pow(-1, $Mflag) * $Mc, 5)=" . $Mm
                    . ",\r\nTC = round($Am - $Fm, 5)=" . $TC . ",D_M=round(($Fm + $Am + (6 * $Mm)) / 8, 5)=" . $D_M . "\r\n";

                //水尺数据
                $data = array(
                    'forntleft' => $forntleft,
                    'forntright' => $forntright,
                    'centerleft' => $centerleft,
                    'centerright' => $centerright,
                    'afterleft' => $afterleft,
                    'afterright' => $afterright,
                    'fornt' => $Fps,
                    'center' => $Mps,
                    'after' => $Aps,
                    'fc' => $Fc,
                    'ac' => $Ac,
                    'mc' => $Mc,
                    'fm' => $Fm,
                    'am' => $Am,
                    'mm' => $Mm,
                    'solt' => $datas['solt'],
                    'resultid' => $datas['resultid']
                );

                //作业表的数据
                $data1 = array(
                    'solt' => $datas['solt'],
                );
                $result_field = "";
                if ($datas['solt'] == '1') {
                    $this->process .= "soltType=作业前 then:\r\n";
                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['qian_tc'] = $TC;
                    $data1['qian_d_m'] = $D_M;
                    $data1['qian_pwd'] = (float)$datas['pwd'];//存储密度
                    $data1['qian_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                    $data1['qian_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                    $data1['qian_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                    $data1['qian_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                    $result_field = "qian_dspc as dspc,qian_constant as constant";
                    $this->process .= "\t TC=" . $data1['qian_tc'] . ",D_M=" . $data1['qian_d_m'] . "\r\n fwater_weight=" . $data1['qian_fwater_weight']
                        . ",sewage_weight=" . $data1['qian_sewage_weight'] . ",fuel_weight=" . $data1['qian_fuel_weight'] . ",fwater_weight=" . $data1['qian_other_weight'] . "\r\n";

                    $data1['qianprocess'] = urlencode($this->process);
                } elseif ($datas['solt'] == '2') {
                    $this->process .= "soltType: 作业后 then:\r\n";
                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['hou_tc'] = $TC;
                    $data1['hou_d_m'] = $D_M;
                    $data1['hou_pwd'] = $datas['pwd'];//存储密度
                    $data1['hou_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                    $data1['hou_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                    $data1['hou_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                    $data1['hou_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                    $result_field = "hou_dspc as dspc,hou_constant as constant";
                    $this->process .= "\t TC=" . $data1['hou_tc'] . ",D_M=" . $data1['hou_d_m'] . "\r\n";
                    //将过程存入数据库
                    $data1['houprocess'] = urlencode($this->process);
                }

                // 判断水尺数据是否存在 添加/修改数据
                $map = array(
                    'solt' => $datas['solt'],
                    'resultid' => $datas['resultid']
                );
                $num = M('sh_forntrecord')->where($map)->count();
                M()->startTrans();  // 开启事物
                if ($num > 0) {
                    //数据存在--修改
                    $r = M('sh_forntrecord')->where($map)->save($data);
                } else {
                    //数据不存在--新增
                    $r = M('sh_forntrecord')->add($data);
                }


                #todo 支持添加水尺计量图片
                /*$datafile = array();
                // 判断是否存在首吃水 尾吃水
                if (!empty($datas['firstfiles']) && $datas['firstfiles'] != '[]') {
                    $firstfiles = substr($datas['firstfiles'], 1);
                    $firstfiles = substr($firstfiles, 0, -1);
                    $firstfiles = explode(',', $firstfiles);
                    foreach ($firstfiles as $key => $value) {
                        $datafile[] = array(
                            'img' => trim($value),
                            'result_id' => $datas['resultid'],
                            'types' => 1,
                            'solt' => $datas['solt']
                        );
                    }
                }

                // 判断是否存在尾吃水
                if (!empty($datas['tailfiles']) && $datas['tailfiles'] != '[]') {

                    $tailfiles = substr($datas['tailfiles'], 1);
                    $tailfiles = substr($tailfiles, 0, -1);
                    $tailfiles = explode(',', $tailfiles);
                    foreach ($tailfiles as $key => $value) {
                        $datafile[] = array(
                            'img' => trim($value),
                            'result_id' => $datas['resultid'],
                            'types' => 2,
                            'solt' => $datas['solt']
                        );
                    }
                }

                // 删除原有的首吃水 尾吃水
                $fornt_img = M('fornt_img')->where(array('result_id' => $datas['resultid'], 'solt' => $datas['solt']))->select();

                if (!empty($datafile)) {
                    M('fornt_img')->where(array('result_id' => $datas['resultid'], 'solt' => $datas['solt']))->delete();
                    // 新增图片
                    $aa = M('fornt_img')->addAll($datafile);
                    if ($aa == false) {
                        M()->rollback();
                        // 数据库错误	3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                        );
                        echo jsonreturn($res);
                        die;
                    }
                }*/

                // 添加/修改数据
                $m = array(
                    'id' => $datas['resultid']
                );
                $res = $this->editData($m, $data1);
                // 修改数据成功
                if ($res['code'] !== false and $r !== false) {
                    #todo 如果存在已有的作业数据，则重新计算已录入的数据
                    M()->commit();
                    $r_data = $this->field($result_field)->where($m)->find();

                    if ($r_data['dspc'] > 0) {
                        $dspc_res = $this->suanDspc1($datas['resultid'], $datas['solt']);
                    } else {
                        $dspc_res['code'] = 1;
                    }

                    if ($r_data['constant'] > 0) {
                        $weight_res = $this->suanWeight($datas['resultid']);
                    } else {
                        $weight_res['code'] = 1;
                    }

                    if ($dspc_res['code'] == 1 and $weight_res['code'] == 1) {
                        //成功 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'D_M' => $D_M,
                        );
                    } else {
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                            'dspc' => $dspc_res['code'],
                            'weight' => $weight_res['code'],
                        );
                    }
                    /*$where = array(
                        'solt' => $datas['solt'],
                        'resultid' => $datas['resultid'],
                        'is_work' => array('eq', 1)
                    );
                    // 如果存在作业数据，重新计算已录入的数据
                    $n = M('resultrecord')->where($where)->select();
                    if (!empty($n)) {
                        foreach ($n as $key => $value) {
                            $this->process = "";
                            $value['uid'] = $datas['uid'];
                            $value['imei'] = $datas['imei'];
                            $this->reckon($value);
                        }
                    }*/

                } else {
                    //数据库连接错误	3
                    M()->rollback();
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                );
            }
        } else {
            // 错误信息
            $res = $msg1;
        }
        return $res;
    }

    /**
     * 计算容量
     * */
    public function suanfa($qiu, $ulist, $keys = '', $ullage = '', $chishui = '')
    {
        self::$function_process = "";
        //四种情况计算容量
        if (count($qiu) == '1' and count($ulist) == '1') {
            //【1】纵倾（吃水差）查出一条，空高查出1条
            self::$function_process .= "count(ulist):1,count(Draught):1 then:\r\n";
            $res = $ulist[0][$keys[0]];
            self::$function_process .= "final_result=" . $res;
        } elseif (count($qiu) == '2' and count($ulist) == '2') {
            self::$function_process .= "count(ulist):2,count(Draught):2 then:\r\n";
            //【2】纵倾（吃水差）查出2条，空高查出2条
            $hou = suanfa5002((float)$ulist[1][$keys[1]], (float)$ulist[0][$keys[1]], (float)$ulist[1]['ullage'], (float)$ulist[0]['ullage'], $ullage);
            self::$function_process .= " = " . $hou . "\r\n first_result=" . $hou . " \r\n";
            $qian = suanfa5002((float)$ulist[1][$keys[0]], (float)$ulist[0][$keys[0]], (float)$ulist[1]['ullage'], (float)$ulist[0]['ullage'], $ullage);
            self::$function_process .= " = " . $qian . "\r\n second_result=" . $qian . " \r\n";

            $res = suanfa5002($hou, $qian, $qiu[$keys[1]], $qiu[$keys[0]], $chishui);
            self::$function_process .= " = " . $res . "\r\n final_result=" . $res . " \r\n";
        } elseif (count($qiu) == '1' and count($ulist) == '2') {
            self::$function_process .= "count(ulist):2,count(Draught):1 then:\r\n";
            //【3】纵倾（吃水差）查出1条，空高查出2条
            $res = suanfa5002((float)$ulist[1][$keys[0]], (float)$ulist[0][$keys[0]], (float)$ulist[1]['ullage'], (float)$ulist[0]['ullage'], $ullage);
            self::$function_process .= " = " . $res . "\r\n final_result=" . $res . " \r\n";

        } elseif (count($qiu) == '2' and count($ulist) == '1') {
            self::$function_process .= "count(ulist):1,count(Draught):2 then:\r\n";
            //【4】纵倾（吃水差）查出2条，空高查出1条
            $res = suanfa5002($ulist[0][$keys[1]], $ulist[0][$keys[0]], $qiu[$keys[1]], $qiu[$keys[0]], $chishui);
            self::$function_process .= " = " . $res . "\r\n final_result=" . $res . " \r\n";
        } else {
            //其他错误	2
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );
        }
        return $res;
    }

    /**
     * 计算上一条与下一条数据
     */
    public function downup($ullage, $tablename, $field, $cabinid)
    {
        $tname = M($tablename);
        $u = $tname
            ->field($field)
            ->where(array('ullage' => $ullage, 'cabinid' => $cabinid))
            ->find();
        if (!empty($u)) {
            $res[] = $u;
        } else {
            //查不到数据，搜索它的上一条或者下一条数据
            //上一条数据
            $wherelt = array(
                'cabinid' => $cabinid,
                'ullage' => array('LT', $ullage)
            );
            $e = $tname
                ->field($field)
                ->where($wherelt)
                ->order('ullage desc')
                ->find();
            if (!empty($e)) {
                $res[] = $e;
            }
            //下一条数据
            $wheregt = array(
                'cabinid' => $cabinid,
                'ullage' => array('GT', $ullage)
            );
            $f = $tname
                ->field($field)
                ->where($wheregt)
                ->order('ullage asc')
                ->find();
            if (!empty($f)) {
                $res[] = $f;
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


    /**
     * 计算
     * @param array data 数据
     * @return array
     * @return @param code
     */
    public function reckon($data)
    {
        // 根据船ID获取纵倾值
        $ship = new \Common\Model\ShipModel();
        $shipmsg = $ship
            ->where(array('id' => $data['shipid']))
            ->find();
        if ($shipmsg == false and empty($shipmsg)) {
            //其他错误	2
            return $res = array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );
            exit;
        }

        $resultlist = new \Common\Model\ResultlistModel();
        // 根据计量ID获取吃水差，温度。密度，
        $msg = $this
            ->field('qianchi,houchi,qiantemperature,qiandensity,houtemperature,houdensity,qianweight')
            ->where(array('id' => $data['resultid']))
            ->find();
        if ($msg == false || empty($msg)) {
            //数据库连接错误	3
            return $res = array(
                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
            );
            die;
        }
        // 根据前后状态获取吃水差
        if ($data['solt'] == '1') {
            $chishui = $msg['qianchi'];
        } elseif ($data['solt'] == '2') {
            $chishui = $msg['houchi'];
        } else {
            //其他错误	2
            return $res = array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );

            die;
        }

        // 区分获取的作业前后的密度、温度
        if ($data['solt'] == '1') {
            $midu = $msg['qiandensity'];
        } else {
            $midu = $msg['houdensity'];
        }
        $this->process = "nowtime:" . date('Y-m-d H:i:s', time()) . "------------------\r\ndensity:" . $midu . " then:\r\n";

        // 获取体积修正(15度的密度、温度)
        // $volume = corrent($midu,$temperature
        $volume = corrent($midu, $data['temperature']);
        $this->process .= self::$function_process . "\r\n\tVC=" . $volume . "\r\n";

        // 膨胀修正
        $this->process .= "coefficient = " . $shipmsg['coefficient'] . ", Cabin_temperature:" . $data['temperature'] . "℃ then:\r\n";
        $expand = expand($shipmsg['coefficient'], $data['temperature']);
        $this->process .= self::$function_process . "\r\n \tEC=" . $expand . "\r\n";


        //判断船是否加管线
        $cabin = new \Common\Model\CabinModel();
        $guan = $cabin
            ->field('id,pipe_line')
            ->where(array('id' => $data['cabinid']))
            ->find();

        if ($shipmsg['is_guanxian'] == '2' and $data['is_pipeline'] == '1') {
            // 船容量不包含管线，管线有容量--容量=舱管线容量+舱容量
            $gx = $guan['pipe_line'];
        } elseif ($shipmsg['is_guanxian'] == '2' and $data['is_pipeline'] == '2') {
            // 船容量不包含管线，管线无容量
            $gx = 0;
        } elseif ($shipmsg['is_guanxian'] == '1' and $data['is_pipeline'] == '1') {
            // 船容量包含管线，管线有容量
            $gx = 0;
        } elseif ($shipmsg['is_guanxian'] == '1' and $data['is_pipeline'] == '2') {
            // 船容量包含管线，管线无容量--容量=舱容量-舱管线容量
            // $gx = 0-$guan['pipe_line'];
            // 2018/12/18    根据三通809的管线计算错误做修改
            $gx = 0;
        }


        // 判断是否有舱容数据
        $is_have_data = $ship->is_have_data($data['shipid']);
        if ($is_have_data !== 'y') {
            //没有舱容数据	2012
            return $res = array(
                'code' => $this->ERROR_CODE_RESULT['NOT_HAVE_CABINDATA']
            );
            exit;
        }

        $temperature = $data['temperature'];


        $bilge_stock = '';
        $pipeline_stock = '';
        $soltType = '';
        $table_contain_pipeline = '';
        //将某些变量格式化，方便读取计算过程,格式化是否有底量
        if ($data['quantity'] == "1") {
            $bilge_stock = 'true';
        } else {
            $bilge_stock = 'false';
        }

        //格式化是否有管线容量
        if ($data['is_pipeline'] == "1") {
            $pipeline_stock = 'true';
        } else {
            $pipeline_stock = 'false';
        }

        //格式化容量表是否包含管线容量
        if ($data['is_guanxian'] == "1") {
            $table_contain_pipeline = 'true';
        } else {
            $table_contain_pipeline = 'false';
        }

        $this->process .= "table_contain_pipeline = " . $table_contain_pipeline . ", pipeline_stock:" . $pipeline_stock . " then:\r\n\tpipeline_volume=" . $gx . "\r\n";


        $ullage = $data['ullage'];        // 空高
        $altitudeheight = round($data['altitudeheight'], 3);        // 基准高度

        // 根据船区分算法
        switch ($shipmsg['suanfa']) {
            case 'a':
                //当空高大于等于基准高度并且不计算底量的时候
                if ($data['quantity'] == '2' and $altitudeheight == $ullage) {
                    $this->process .= "ullage = altitudeheight,bilge_stock == false then:Cabin_volume=0\r\n\t";
                    $cabinweight = 0;
                } else {
                    $this->process .= "ullage != altitudeheight or bilge_stock == true then:\r\n\t";
                    // json转化数组
                    $qiu = $this->getjsonarray($shipmsg['tripbystern'], $chishui);
                    //返回的吃水差跟纵倾值
                    $keys = array_keys($qiu);

                    //根据空高查询数据
                    $field = $keys;
                    $field[] = 'ullage';
                    $ulist = $this->downup($ullage, $shipmsg['tankcapacityshipid'], $field, $data['cabinid']);
                    $this->process .= "Search trim_table:\r\n\t";
                    if (count($qiu) == '1' and count($ulist) == '1') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '2' and count($ulist) == '2') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . ")=Cab(" . $ulist[0][$keys[1]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . ")=Cba(" . $ulist[1][$keys[0]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Db(" . $qiu[1] . ")=Cbb(" . $ulist[1][$keys[1]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '1' and count($ulist) == '2') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . "=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . "=Cba(" . $ulist[1][$keys[0]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '2' and count($ulist) == '1') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . "=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . "=Cab(" . $ulist[0][$keys[1]] . ")\r\n---------------------------------------\r\n";
                    } else {
                        $this->process .= "无法获取到表，错误！\r\n\t";
                    }

                    //四种情况计算容量
                    $cabinweight = round($this->suanfa($qiu, $ulist, $keys, $ullage, $chishui), 3) + $gx;
                    $this->process .= self::$function_process . "SCV=" . $cabinweight . "\r\n\tCabin_volume=pipeline_volume+SCV=" . $cabinweight . "\r\n\t";
                }

                // 计算标准容量   容量*体积*膨胀
                $standardcapacity = round($cabinweight * $volume * $expand, 3);
                $this->process .= "now_cabin_volume = round(Cabin_volume*VC*EC,4) = " . $standardcapacity . "\r\n";

                //整合数据保存数据库
                $datas = array(
                    'temperature' => $data['temperature'],
                    'cabinweight' => $cabinweight,
                    'cabinid' => $data['cabinid'],
                    'ullage' => $ullage,
                    'sounding' => $data['sounding'],
                    'time' => time(),
                    'resultid' => $data['resultid'],
                    'solt' => $data['solt'],
                    'standardcapacity' => $standardcapacity,
                    'volume' => $volume,
                    'expand' => $expand,
                    'is_work' => '1'
                );

                break;
            case 'b':
                $qiu = $this->getjsonarray($shipmsg['trimcorrection'], $chishui);
                // 根据吃水差获取数组键值（纵倾表的字段名）
                $keys = array_keys($qiu);

                //根据空高查询数据
                $field = $keys;
                $field[] = 'ullage';
                $ulist = $this->downup($ullage, $shipmsg['zx'], $field, $data['cabinid']);

                $this->process .= "Search trim_table:\r\n\t";
                if (count($qiu) == '1' and count($ulist) == '1') {
                    $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n---------------------------------------\r\n";
                } elseif (count($qiu) == '2' and count($ulist) == '2') {
                    $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                        . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . ")=Cab(" . $ulist[0][$keys[1]] . ")\r\n\t"
                        . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . ")=Cba(" . $ulist[1][$keys[0]] . ")\r\n\t"
                        . "Ub(" . $ulist[1]['ullage'] . "):Db(" . $qiu[1] . ")=Cbb(" . $ulist[1][$keys[1]] . ")\r\n---------------------------------------\r\n";
                } elseif (count($qiu) == '1' and count($ulist) == '2') {
                    $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                        . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . ")=Cba(" . $ulist[1][$keys[0]] . ")\r\n---------------------------------------\r\n";
                } elseif (count($qiu) == '2' and count($ulist) == '1') {
                    $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                        . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . ")=Cab(" . $ulist[0][$keys[1]] . ")\r\n---------------------------------------\r\n";
                } else {
                    $this->process .= "无法获取到表，错误！\r\n\t";
                }


                //计算纵倾修正值

                $zongxiu1 = round($this->suanfa($qiu, $ulist, $keys, $ullage, $chishui), 0) / 1000;
                $this->process .= self::$function_process . "TC=" . $zongxiu1 . "\r\n";

                //根据纵修与基准高度-空高的差值比较取小
                $chazhi = round(($ullage - $data['altitudeheight']), 3);
                if ($chazhi > $zongxiu1) {
                    $zongxiu = $chazhi;
                } elseif ($chazhi < $zongxiu1) {
                    $zongxiu = $zongxiu1;
                } elseif ($chazhi == $zongxiu1) {
                    $zongxiu = $chazhi;
                }

                $this->process .= " TC:" . $zongxiu1 . ", -Sounding:" . $chazhi . " then:\r\n\tNowTC=" . $zongxiu . "\r\n";


                //得到修正空距 空距+纵倾修正值
                $xiukong = round($ullage - $zongxiu, 3);
                $this->process .= "C_ullage = ullage - NowTC =" . $ullage . " - " . $zongxiu . "=" . $xiukong . "\r\n";
                //当修正空高大于等于基准高度并且不计算底量的时候
                if ($data['quantity'] == '2' and $altitudeheight == $xiukong) {
                    $this->process .= "ullage = altitudeheight,bilge_stock == false then:Cabin_volume=0\r\n\t";
                    $cabinweight = 0;
                } else {
                    $this->process .= "ullage != altitudeheight or bilge_stock == true then:\r\n\t";
                    //根据修正空距到容量表查询数据
                    $field1 = array('ullage', 'capacity');
                    $ulist1 = $this->downup($xiukong, $shipmsg['rongliang'], $field1, $data['cabinid']);
                    //计算容量
                    $keys1[] = 'capacity';  //容量表代表容量的字段
                    $qiu1 = array('capacity' => 1);    //随意定义，只要是一位数组
                    if (count($ulist) == '1') {
                        $this->process .= "Received capacity_table: \r\n\t"
                            . "U1(" . $ulist[0]['ullage'] . ")->CV1(" . $ulist[0][$keys[0]] . ")\r\n------------------------------------\r\nC_ullage:" . $data['correntkong'] . " then:";
                    } elseif (count($ulist) == '2') {
                        $this->process .= "Received capacity_table: \r\n\t"
                            . "U1(" . $ulist[0]['ullage'] . ")->CV1(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "U2(" . $ulist[1]['ullage'] . ")->CV2(" . $ulist[0][$keys[0]] . ")\r\n------------------------------------\r\nC_ullage:" . $data['correntkong'] . " then:";
                    }
                    $cabinweight = round($this->suanfa($qiu1, $ulist1, $keys1, $xiukong, $chishui), 3) + $gx;
                    $this->process .= self::$function_process . "cabin_first_result=" . $cabinweight . "\r\n\tCabin_volume=pipeline_volume+cabin_first_result=" . $cabinweight . "\r\n\t";
                }


                // 计算标准容量   容量*体积*膨胀
                $standardcapacity = round($cabinweight * $volume * $expand, 3);
                $this->process .= "now_cabin_volume = round(Cabin_volume*VC*EC,4) = " . $standardcapacity . "\r\n";


                //整合数据保存数据库
                $datas = array(
                    'temperature' => $data['temperature'],    //温度
                    'cabinweight' => $cabinweight,
                    'cabinid' => $data['cabinid'],
                    'ullage' => $ullage,            //空高
                    'sounding' => $data['sounding'],    //实高
                    'time' => time(),
                    'resultid' => $data['resultid'],
                    'solt' => $data['solt'],        //作业标识
                    'standardcapacity' => $standardcapacity,        //标准容量
                    'volume' => $volume,        //体积修正
                    'expand' => $expand,        //膨胀修正系数
                    'correntkong' => $xiukong,        //修正空距
                    'listcorrection' => $zongxiu,        //纵倾修正
                    'is_work' => '1'
                );
                break;
            case 'c':
                //判断底量计算
                if ($data['qufen'] == 'diliang') {
                    $this->process .= "method:C,table_used:diliang then\r\n\t";
                    if (empty($shipmsg['trimcorrection1'])) {
                        $trimcorrection1 = $shipmsg['trimcorrection'];
                    } else {
                        $trimcorrection1 = $shipmsg['trimcorrection1'];
                    }
                    //纵倾修正
                    $qiu = $this->getjsonarray($trimcorrection1, $chishui);
                    // 根据吃水差获取数组键值（纵倾表的字段名）
                    $keys = array_keys($qiu);

                    // 主表806_2(底量计算)
                    // 根据空高查询数据
                    $field = $keys;
                    $field[] = 'ullage';

                    $ulist = $this->downup($ullage, $shipmsg['zx_1'], $field, $data['cabinid']);

                    $this->process .= "Search trim_table:\r\n\t";
                    if (count($qiu) == '1' and count($ulist) == '1') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '2' and count($ulist) == '2') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . "=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . ")=Cab(" . $ulist[0][$keys[1]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . ")=Cba(" . $ulist[1][$keys[0]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Db(" . $qiu[1] . ")=Cbb(" . $ulist[1][$keys[1]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '1' and count($ulist) == '2') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . ")=Cba(" . $ulist[1][$keys[0]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '2' and count($ulist) == '1') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . ")=Cab(" . $ulist[0][$keys[1]] . ")\r\n---------------------------------------\r\n";
                    } else {
                        $this->process .= "无法获取到表，错误！\r\n\t";
                    }

                    //计算纵倾修正值
                    $zongxiu1 = round($this->suanfa($qiu, $ulist, $keys, $ullage, $chishui), 0) / 1000;
                    $this->process .= self::$function_process . "TC=" . $zongxiu1 . "\r\n";

                    // writeLog(json_encode($zongxiu1));
                    //根据纵修与基准高度-空高的差值比较取小
                    $chazhi = round(($ullage - $altitudeheight), 3);
                    if ($chazhi > $zongxiu1) {
                        $zongxiu = $chazhi;
                    } elseif ($chazhi < $zongxiu1) {
                        $zongxiu = $zongxiu1;
                    } elseif ($chazhi == $zongxiu1) {
                        $zongxiu = $chazhi;
                    }
                    $this->process .= " TC:" . $zongxiu1 . ", -Sounding:" . $chazhi . " then:\r\n\tNowTC=" . $zongxiu . "\r\n";

                    //得到修正空距 空距+纵倾修正值
                    $xiukong = $ullage - $zongxiu;
                    $this->process .= "C_ullage = ullage - NowTC =" . $ullage . " - " . $zongxiu . "=" . $xiukong . "\r\n";

                    //根据修正空距到容量表查询数据
                    $field1 = array('ullage', 'capacity');
                    $ulist1 = $this->downup($xiukong, $shipmsg['rongliang_1'], $field1, $data['cabinid']);
                } else {
                    $this->process .= "method:C and table_used:rongliang then\r\n\t";

                    //纵倾修正
                    $qiu = $this->getjsonarray($shipmsg['trimcorrection'], $chishui);
                    // 根据吃水差获取数组键值（纵倾表的字段名）
                    $keys = array_keys($qiu);
                    //主表806_1(普通容量计算)
                    //根据空高查询数据
                    $field = $keys;
                    $field[] = 'ullage';
                    $ulist = $this->downup($ullage, $shipmsg['zx'], $field, $data['cabinid']);

                    $this->process .= "Search trim_table:\r\n\t";
                    if (count($qiu) == '1' and count($ulist) == '1') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '2' and count($ulist) == '2') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . ")=Cab(" . $ulist[0][$keys[1]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . ")=Cba(" . $ulist[1][$keys[0]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Db(" . $qiu[1] . ")=Cbb(" . $ulist[1][$keys[1]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '1' and count($ulist) == '2') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ub(" . $ulist[1]['ullage'] . "):Da(" . $qiu[0] . "=Cba(" . $ulist[1][$keys[0]] . ")\r\n---------------------------------------\r\n";
                    } elseif (count($qiu) == '2' and count($ulist) == '1') {
                        $this->process .= "Ua(" . $ulist[0]['ullage'] . "):Da(" . $qiu[0] . ")=Caa(" . $ulist[0][$keys[0]] . ")\r\n\t"
                            . "Ua(" . $ulist[0]['ullage'] . "):Db(" . $qiu[1] . ")=Cab(" . $ulist[0][$keys[1]] . ")\r\n---------------------------------------\r\n";
                    } else {
                        $this->process .= "无法获取到表，错误！\r\n\t";
                    }

                    //计算纵倾修正值
                    $zongxiu1 = round($this->suanfa($qiu, $ulist, $keys, $ullage, $chishui), 0) / 1000;
                    $this->process .= self::$function_process . "TC=" . $zongxiu1 . "\r\n";

                    //根据纵修与基准高度-空高的差值比较取小
                    $chazhi = round(($ullage - $altitudeheight), 3);

                    if ($chazhi > $zongxiu1) {
                        $zongxiu = $chazhi;
                    } elseif ($chazhi < $zongxiu1) {
                        $zongxiu = $zongxiu1;
                    } elseif ($chazhi == $zongxiu1) {
                        $zongxiu = $chazhi;
                    }
                    $this->process .= " TC:" . $zongxiu1 . ", -Sounding:" . $chazhi . " then:\r\n\tNowTC=" . $zongxiu . "\r\n";

                    //得到修正空距 空距+纵倾修正值
                    $xiukong = $ullage - $zongxiu;
                    $this->process .= "C_ullage = ullage - NowTC =" . $ullage . " - " . $zongxiu . "=" . $xiukong . "\r\n";

                    //根据修正空距到容量表查询数据
                    $field1 = array('ullage', 'capacity');
                    $ulist1 = $this->downup($xiukong, $shipmsg['rongliang'], $field1, $data['cabinid']);
                }
                //计算容量
                //当修正空高大于等于基准高度并且不计算底量的时候
                if ($data['quantity'] == '2' and $altitudeheight == $xiukong) {
                    $this->process .= "ullage = altitudeheight,bilge_stock == false then:Cabin_volume=0\r\n\t";

                    $cabinweight = 0;
                } else {
                    $this->process .= "ullage != altitudeheight or bilge_stock == true then:\r\n\t";
                    $keys1[] = 'capacity';  //容量表代表容量的字段
                    $qiu1 = array('capacity' => 1);    //随意定义，只要是一位数组
                    if (count($ulist1) == '1') {
                        $this->process .= 'Received capacity_table:\r\n\t'
                            . "U1(" . $ulist1[0]['ullage'] . ")->CV1(" . $ulist1[0][$keys1[0]] . ")\r\n------------------------------------\r\nC_ullage:" . $data['correntkong'] . " then:";
                    } elseif (count($ulist1) == '2') {
                        $this->process .= 'Received capacity_table:\r\n\t'
                            . "U1(" . $ulist1[0]['ullage'] . ")->CV1(" . $ulist1[0][$keys1[0]] . ")\r\n\t"
                            . "U2(" . $ulist1[1]['ullage'] . ")->CV2(" . $ulist1[0][$keys1[0]] . ")\r\n------------------------------------\r\nC_ullage:" . $data['correntkong'] . " then:";
                    }
                    $cabinweight = round($this->suanfa($qiu1, $ulist1, $keys1, $xiukong, $chishui), 3) + $gx;
                    $this->process .= self::$function_process . "cabin_first_result=" . $cabinweight . "\r\n\tCabin_volume=pipeline_volume+cabin_first_result=" . $cabinweight . "\r\n\t";
                    // writeLog(json_encode($cabinweight));
                    // writeLog(json_encode($gx));
                }

                // 计算标准容量   容量*体积*膨胀
                $standardcapacity = round($cabinweight * $volume * $expand, 3);
                $this->process .= "now_cabin_volume = round(Cabin_volume*VC*EC,3) = " . $standardcapacity . "\r\n";
                //整合数据保存数据库
                $datas = array(
                    'temperature' => $data['temperature'],    //温度
                    'cabinweight' => $cabinweight,
                    'cabinid' => $data['cabinid'],
                    'ullage' => $ullage,            //空高
                    'sounding' => $data['sounding'],    //实高
                    'time' => time(),
                    'resultid' => $data['resultid'],
                    'solt' => $data['solt'],        //作业标识
                    'standardcapacity' => $standardcapacity,        //标准容量
                    'volume' => $volume,        //体积修正
                    'expand' => $expand,        //膨胀修正系数
                    'correntkong' => $xiukong,        //修正空距
                    'listcorrection' => $zongxiu,        //纵倾修正
                    'is_work' => '1'
                );
                break;
            default:
                return $res = array(
                    'code' => 2
                );
                exit;
                break;
        }

        // 判断是否已存在数据，已存在就修改，不存在就新增
        $wheres = array(
            'cabinid' => $data['cabinid'],
            'resultid' => $data['resultid'],
            'solt' => $data['solt']
        );
        $nums = $resultlist->where($wheres)->count();
        $trans = M();
        $trans->startTrans();   // 开启事务
        if ($nums == '1') {
            unset($datas['time']);
            // 获取舱作业ID
            $listid = $resultlist->where($wheres)->getField('id');
            //修改数据
            $resultlist->editData($wheres, $datas);
        } else {
            //新增数据
            $listid = $resultlist->add($datas);
        }
        // 保存图片资源
        $datafile = $this->imgfile($data, $listid);
        // 删除原有的首吃水 尾吃水
        $fornt_img = M('resultlist_img')->where(array('resultlist_id' => $listid))->select();
        // foreach ($fornt_img as $e => $a) {
        // 	@unlink ($a['img']);
        // }
        M('resultlist_img')->where(array('resultlist_id' => $listid))->delete();

        // 新增图片
        if (!empty($datafile)) {
            $aa = M('resultlist_img')->addAll($datafile);
            if ($aa == false) {
                M()->rollback();
                // 数据库错误	3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                );
                echo jsonreturn($res);
                die;
            }
        }

        //计算所有舱作业前/后总标准容量
        $wheres1 = array(
            'resultid' => $data['resultid'],
            'solt' => $data['solt']
        );
        $allweight = $resultlist
            ->field("sum(standardcapacity) as sums")
            ->where($wheres1)
            ->select();
        //根据总标准容量*密度得到作业前/后总的货重
        $total = round($allweight[0]['sums'] * ($midu - 0.0011), 3);
        $this->process .= "now_result_cargo_weight = round(sum(now_cabin_volume) * (density - AB),4) =round(" . $allweight[0]['sums'] . " * (" . $midu . " - 0.0011),4) =" . $total . "\r\n";

        //作业前作业后区分是否计算总货重
        switch ($data['solt']) {
            case '1':
                //作业前
                //修改作业前总货重、总容量
                $g = array(
                    'qianweight' => round($allweight[0]['sums'], 3),
                    'qiantotal' => $total,
                );
                $r = $this
                    ->where(array('id' => $data['resultid']))
                    ->save($g);
                if ($r !== false) {
                    // 获取作业前、后的总货重
                    $sunmmsg = $this
                        ->field('qiantotal,houtotal')
                        ->where(array('id' => $data['resultid']))
                        ->find();
                    // 计算总容量 后-前
                    $weight = round(($sunmmsg['houtotal'] - $sunmmsg['qiantotal']), 3);
                    // 修改总货重
                    $res1 = $this
                        ->where(array('id' => $data['resultid']))
                        ->save(array('weight' => $weight));
                    if ($res1 !== false) {
                        $trans->commit();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        );
                    } else {
                        $trans->rollback();
                        //其它错误  2
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                        );
                    }
                } else {
                    $trans->rollback();
                    //其它错误  2
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                    );
                }
                break;
            case '2':
                // 作业后（需要计算总货重）
                // 修改作业后总货重、总容量
                // 判断前后密度是否一样,如果不一样计算密度差
                // 重量2-（密度2-密度1）*体积1
                if ($msg['qiandensity'] != $msg['houdensity']) {
                    $total1 = $total;
                    // \Think\Log::record(($msg['houdensity']-$msg['qiandensity'])*$msg['qianweight']);
                    $total = round($total - ($msg['houdensity'] - $msg['qiandensity']) * $msg['qianweight'], 3);
                    //记录过程
                    $this->process .= "soltType:作业后,now_result_cargo_weight:" . $total1 . ",before_result_cargo_weight = " . $msg['qianweight'] . ",now_density = "
                        . $msg['houdensity'] . ",before_density=" . $msg['houdensity']
                        . " then:\r\n\ttotal_cargo_weight = round(now_result_cargo_weight - (now_density - before_density) * before_result_cargo_weight, 3)=round("
                        . $total1 . "-(" . $msg['houdensity'] . "-" . $msg['qiandensity'] . ")*" . $msg['qianweight'] . ",3)=" . $total;

                }

                $hou = array(
                    'houweight' => round($allweight[0]['sums'], 3),
                    'houtotal' => $total,
                );
                $r = $this->where(array('id' => $data['resultid']))->save($hou);
                if ($r !== false) {
                    // 获取作业前、后的总货重
                    $sunmmsg = $this
                        ->field('qiantotal,houtotal')
                        ->where(array('id' => $data['resultid']))
                        ->find();
                    // 计算总容量 后-前
                    $weight = round(($sunmmsg['houtotal'] - $sunmmsg['qiantotal']), 3);
                    // 修改总货重
                    $res1 = $this
                        ->where(array('id' => $data['resultid']))
                        ->save(array('weight' => $weight));
                    if ($res1 !== false) {
                        $trans->commit();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        );
                    } else {
                        $trans->rollback();
                        //其它错误  2
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                        );
                    }
                } else {
                    $trans->rollback();
                    //其它错误  2
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                    );
                }
                break;
            default:
                # 不是作业前后，跳出
                break;
        }
        //保存过程数据
        $resultlist->editData(
            array(
                'id' => $listid
            ),
            array(
                'process' => urlencode($this->process)
            )//计算过程
        );
        return $res;
    }

    /**
     * 实高图片、空高图片、温度图片路径整理
     */
    public function imgfile($data, $listid)
    {
        $resultlist_img = M('resultlist_img');
        $datafile = array();

        // 判断是否存在实高图片
        if (!empty($data['soundingfile']) && $datas['soundingfile'] != '[]') {
            $soundingfile = substr($data['soundingfile'], 1);
            $soundingfile = substr($soundingfile, 0, -1);
            $soundingfile = explode(',', $soundingfile);
            foreach ($soundingfile as $key => $value) {
                $datafile[] = array(
                    'img' => $value,
                    'types' => 2,
                    'resultlist_id' => $listid
                );
            }
        }

        // 判断是否存在空高图片
        if (!empty($data['ullagefile']) && $datas['ullagefile'] != '[]') {
            $ullagefile = substr($data['ullagefile'], 1);
            $ullagefile = substr($ullagefile, 0, -1);
            $ullagefile = explode(',', $ullagefile);
            foreach ($ullagefile as $key => $value) {
                $datafile[] = array(
                    'img' => $value,
                    'types' => 1,
                    'resultlist_id' => $listid
                );
            }

        }
        // 判断是否存在温度图片
        if (!empty($data['temperaturefile']) && $datas['temperaturefile'] != '[]') {
            $temperaturefile = substr($data['temperaturefile'], 1);
            $temperaturefile = substr($temperaturefile, 0, -1);
            $temperaturefile = explode(',', $temperaturefile);
            foreach ($temperaturefile as $key => $value) {
                $datafile[] = array(
                    'img' => $value,
                    'types' => 3,
                    'resultlist_id' => $listid
                );
            }
        }
        return $datafile;
    }

    /**
     * 计算器计算容量
     * */
    public function reckon1($data)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($data['uid'], $data['imei']);
        if ($msg1['code'] == '1') {

            // 将录入数据更新到表中
            $field_str = "";
            if ($data['solt'] == '1') {
                $field_str = "r.qian_tc as tc,r.qian_d_m as d_m,r.qian_pwd as pwd,r.qian_constant as constant,s.lbp";
            } elseif ($data['solt'] == '2') {
                $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,r.hou_constant as constant,s.lbp";
            }

            $where = array(
                'r.id' => $data['resultid'],
            );

            $r = $this
                ->alias('r')
                ->field($field_str)
                ->join('left join sh_ship s on s.id=r.shipid')
                ->where($where)
                ->find();

            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                // 	// 空高有误 2009
                //     $res = array(
                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                //     );
                // } else {

                /**
                 * 开始计算排水量
                 */

                $TC = $r['tc'];
                $D_M = $r['d_m'];
                $p = $r['pwd'];
                $LBP = $r['lbp'];

                /**
                 * 整理数据
                 *
                 * */
                $this->process .= "Received table:\r\n\t"
                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                    . "\r\n\tMTCup=" . $data['mtc_up'] . ",MTCdown=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                    . $data['ptwd'] . "\r\n";
                $Dup = (float)$data['d_up'];
                $Ddown = (float)$data['d_down'];
                $TPCup = (float)$data['tpc_up'];
                $TPCdown = (float)$data['tpc_down'];
                $DSup = (float)$data['ds_up'];
                $DSdown = (float)$data['ds_down'];
                $Xfup = (float)$data['xf_up'];
                $Xfdown = (float)$data['xf_down'];
                $MTCup = (float)$data['mtc_up'];
                $MTCdown = (float)$data['mtc_down'];
                $pt = (float)$data['ptwd'];

                //开始计算
                //开始插值计算
                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                $TPC = round($getDS_arr['TPC'], 5);
                $DS = round($getDS_arr['DS'], 5);
                $Xf = round($getDS_arr['Xf'], 5);

                /*                $TPC = 59.5;
                                $DS = 55118.7;
                                $Xf = 2.85;*/

                $dmdz = round($MTCup - $MTCdown, 5);
                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                $Dc = round($Dc1 + $Dc2, 5);
                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                $Dsc = round($DS + $Dc, 5);
                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                $Dspc = round($Dsc + $Dpc, 5);
                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;

                $resultrecord = M('sh_resultrecord');

                $data['tpc'] = $TPC;
                $data['ds'] = $DS;
                $data['xf'] = $Xf;
                $data['dmdz'] = $dmdz;
                $data['dc1'] = $Dc1;
                $data['dc2'] = $Dc2;
                $data['dc'] = $Dc;
                $data['dsc'] = $Dsc;
                $data['dpc'] = $Dpc;
                $data['process'] = urlencode($this->process);

                $where_r = array(
                    'resultid' => $data['resultid'],
                    'solt' => $data['solt'],
                );

                $recodenums = $resultrecord->where($where_r)->count();

                M()->startTrans();    //开启事物
                //添加数据到数据库排水量表数据表,如果存在则修改
                if ($recodenums > 0) {
                    $re = $resultrecord->where($where_r)->save($data);
                } else {
                    $re = $resultrecord->add($data);
                }
                if ($re !== false) {
                    //计算总货重并修改
                    //作业前作业后区分是否计算总货重
                    switch ($data['solt']) {
                        case '1':
                            //作业前
                            //修改作业前总货重、总容量
                            $g = array(
                                'qian_dspc' => $Dspc,
                            );
                            $res_r = $this
                                ->where(array('id' => $data['resultid']))
                                ->save($g);
                            if ($res_r !== false) {
                                M()->commit();

                                if ($r['constant'] > 0) {
                                    $weight_res = $this->suanWeight($data['resultid']);
                                } else {
                                    $weight_res['code'] = 1;
                                }

                                if ($weight_res['code'] == 1) {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'Dspc' => $Dspc,
                                    );
                                } else {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                                        'weight' => $weight_res['code'],
                                    );
                                }
                            } else {
                                M()->rollback();
                                // $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }
                            break;
                        case '2':
                            //作业前
                            //修改作业前总货重、总容量
                            $g = array(
                                'hou_dspc' => $Dspc,
                            );
                            $r = $this
                                ->where(array('id' => $data['resultid']))
                                ->save($g);
                            if ($r !== false) {
                                M()->commit();

                                if ($r['constant'] > 0) {
                                    $weight_res = $this->suanWeight($data['resultid']);
                                } else {
                                    $weight_res['code'] = 1;
                                }

                                if ($weight_res['code'] == 1) {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'Dspc' => $Dspc,
                                    );
                                } else {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                                        'weight' => $weight_res['code'],
                                    );
                                }
                            } else {
                                M()->rollback();
                                // $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }
                            break;
                        default:
                            # 不是作业前后，跳出
                            break;
                    }

                } else {
                    //其它错误 2
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }
                // }
            } else {
                //其它错误 2
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                );
            }
        } else {
            //未到期/状态禁止/标识错误
            $res = $msg1;
        }
        return $res;
    }


    /**
     * 计算器计算容量
     * */
    public function reckon2($data)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($data['uid'], $data['imei']);
        if ($msg1['code'] == '1') {

            // 将录入数据更新到表中
            $field_str = "";
            if ($data['solt'] == '1') {
                $field_str = "r.qian_tc as tc,r.qian_d_m as d_m,r.qian_pwd as pwd,r.qian_constant as constant,s.lbp,s.ptwd";
            } elseif ($data['solt'] == '2') {
                $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,r.hou_constant as constant,s.lbp,s.ptwd";
            }

            $where = array(
                'r.id' => $data['resultid'],
            );

            $r = $this
                ->alias('r')
                ->field($field_str)
                ->join('left join sh_ship s on s.id=r.shipid')
                ->where($where)
                ->find();

            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                // 	// 空高有误 2009
                //     $res = array(
                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                //     );
                // } else {

                /**
                 * 开始计算排水量
                 */

                $TC = $r['tc'];
                $D_M = $r['d_m'];
                $p = $r['pwd'];
                $LBP = $r['lbp'];
                $pt = $r['ptwd'];

                /**
                 * 整理数据
                 *
                 * */
                $this->process .= "Received table:\r\n\t"
                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                    . "\r\n\tMTCup=" . $data['mtc_up'] . ",MTCdown=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                    . $pt . "\r\n";
                $Dup = (float)$data['d_up'];
                $Ddown = (float)$data['d_down'];
                $TPCup = (float)$data['tpc_up'];
                $TPCdown = (float)$data['tpc_down'];
                $DSup = (float)$data['ds_up'];
                $DSdown = (float)$data['ds_down'];
                $Xfup = (float)$data['xf_up'];
                $Xfdown = (float)$data['xf_down'];
                $MTCup = (float)$data['mtc_up'];
                $MTCdown = (float)$data['mtc_down'];
//                $pt = (float)$data['ptwd'];

                //开始计算
                //开始插值计算
                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                $TPC = round($getDS_arr['TPC'], 5);
                $DS = round($getDS_arr['DS'], 5);
                $Xf = round($getDS_arr['Xf'], 5);

                /*                $TPC = 59.5;
                                $DS = 55118.7;
                                $Xf = 2.85;*/

                $dmdz = round($MTCup - $MTCdown, 5);
                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                $Dc = round($Dc1 + $Dc2, 5);
                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                $Dsc = round($DS + $Dc, 5);
                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                $Dspc = round($Dsc + $Dpc, 5);
                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;

                $resultrecord = M('sh_resultrecord');

                $data['tpc'] = $TPC;
                $data['ds'] = $DS;
                $data['xf'] = $Xf;
                $data['dmdz'] = $dmdz;
                $data['dc1'] = $Dc1;
                $data['dc2'] = $Dc2;
                $data['dc'] = $Dc;
                $data['dsc'] = $Dsc;
                $data['dpc'] = $Dpc;
                $data['process'] = urlencode($this->process);

                $where_r = array(
                    'resultid' => $data['resultid'],
                    'solt' => $data['solt'],
                );

                $recodenums = $resultrecord->where($where_r)->count();

                M()->startTrans();    //开启事物
                //添加数据到数据库排水量表数据表,如果存在则修改
                if ($recodenums > 0) {
                    $re = $resultrecord->where($where_r)->save($data);
                } else {
                    $re = $resultrecord->add($data);
                }
                if ($re !== false) {
                    //计算总货重并修改
                    //作业前作业后区分是否计算总货重
                    switch ($data['solt']) {
                        case '1':
                            //作业前
                            //修改作业前总货重、总容量
                            $g = array(
                                'qian_dspc' => $Dspc,
                            );
                            $res_r = $this
                                ->where(array('id' => $data['resultid']))
                                ->save($g);
                            if ($res_r !== false) {
                                M()->commit();

                                if ($r['constant'] > 0) {
                                    $weight_res = $this->suanWeight($data['resultid']);
                                } else {
                                    $weight_res['code'] = 1;
                                }

                                if ($weight_res['code'] == 1) {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'Dspc' => $Dspc,
                                    );
                                } else {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                                        'weight' => $weight_res['code'],
                                    );
                                }
                            } else {
                                M()->rollback();
                                // $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }
                            break;
                        case '2':
                            //作业前
                            //修改作业前总货重、总容量
                            $g = array(
                                'hou_dspc' => $Dspc,
                            );
                            $r = $this
                                ->where(array('id' => $data['resultid']))
                                ->save($g);
                            if ($r !== false) {
                                M()->commit();

                                if ($r['constant'] > 0) {
                                    $weight_res = $this->suanWeight($data['resultid']);
                                } else {
                                    $weight_res['code'] = 1;
                                }

                                if ($weight_res['code'] == 1) {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'Dspc' => $Dspc,
                                    );
                                } else {
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                                        'weight' => $weight_res['code'],
                                    );
                                }
                            } else {
                                M()->rollback();
                                // $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }
                            break;
                        default:
                            # 不是作业前后，跳出
                            break;
                    }

                } else {
                    //其它错误 2
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }
                // }
            } else {
                //其它错误 2
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                );
            }
        } else {
            //未到期/状态禁止/标识错误
            $res = $msg1;
        }
        return $res;
    }


    /**
     * 重新计算排水量
     * @param $resultid
     * @param $solt
     */
    public function suanDspc($resultid, $solt)
    {
        $this->process = "";
        #todo 检测计算中需要用到的数据是否缺失，缺失则返回步骤错位
        // 将录入数据更新到表中
        $resultrecord = M('sh_resultrecord');


        $field_str = "";

        $where_data = array(
            'resultid' => $resultid,
            'solt' => 1,
        );

        $record_num = $resultrecord->where($where_data)->count();
        if ($record_num > 0) {

            $data = $resultrecord->where($where_data)->find();
            $field_str = "r.qian_tc as tc,r.qian_d_m as d_m,r.qian_pwd as pwd,s.lbp,s.ptwd";
            $where = array(
                'r.id' => $resultid,
            );

            $r = $this
                ->alias('r')
                ->field($field_str)
                ->join('left join sh_ship s on s.id=r.shipid')
                ->where($where)
                ->find();

            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                // 	// 空高有误 2009
                //     $res = array(
                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                //     );
                // } else {

                /**
                 * 开始计算排水量
                 */

                $TC = $r['tc'];
                $D_M = $r['d_m'];
                $p = $r['pwd'];
                $LBP = $r['lbp'];
//                $pt = $r['ptwd'];

                $Dup = (float)$data['d_up'];
                $Ddown = (float)$data['d_down'];
                $TPCup = (float)$data['tpc_up'];
                $TPCdown = (float)$data['tpc_down'];
                $DSup = (float)$data['ds_up'];
                $DSdown = (float)$data['ds_down'];
                $Xfup = (float)$data['xf_up'];
                $Xfdown = (float)$data['xf_down'];
                $MTCup = (float)$data['mtc_up'];
                $MTCdown = (float)$data['mtc_down'];
                $pt = (float)$data['ptwd'];

                /**
                 * 整理数据
                 *
                 * */
                $this->process .= "Received table:\r\n\t"
                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                    . "\r\n\tMTCup=" . $data['mtc_up'] . ",MTCdown=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                    . $pt . "\r\n";


                //开始计算
                //开始插值计算
                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                $TPC = round($getDS_arr['TPC'], 5);
                $DS = round($getDS_arr['DS'], 5);
                $Xf = round($getDS_arr['Xf'], 5);

                /*                $TPC = 59.5;
                                $DS = 55118.7;
                                $Xf = 2.85;*/

                $dmdz = round($MTCup - $MTCdown, 5);
                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                $Dc = round($Dc1 + $Dc2, 5);
                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                $Dsc = round($DS + $Dc, 5);
                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                $Dspc = round($Dsc + $Dpc, 5);
                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;


                $data['tpc'] = $TPC;
                $data['ds'] = $DS;
                $data['xf'] = $Xf;
                $data['dmdz'] = $dmdz;
                $data['dc1'] = $Dc1;
                $data['dc2'] = $Dc2;
                $data['dc'] = $Dc;
                $data['dsc'] = $Dsc;
                $data['dpc'] = $Dpc;
                $data['process'] = urlencode($this->process);

                $where_r = array(
                    'resultid' => $resultid,
                    'solt' => 1,
                );

                $recodenums = $resultrecord->where($where_r)->count();


                M()->startTrans();    //开启事物
                //添加数据到数据库排水量表数据表,如果存在则修改
                if ($recodenums > 0) {
                    $re = $resultrecord->where($where_r)->save($data);
                } else {
                    $re = $resultrecord->add($data);
                }
                if ($re !== false) {

                    //计算总货重并修改
                    //作业前作业后区分是否计算总货重

                    //作业前
                    //修改作业前总货重、总容量
                    $this->process = "";
                    $g = array(
                        'qian_dspc' => $Dspc,
                    );
                    $r = $this
                        ->where(array('id' => $resultid))
                        ->save($g);
                    if ($r !== false) {
                        $where_data = array(
                            'resultid' => $resultid,
                            'solt' => 2,
                        );
                        $hou_count = $resultrecord->where($where_data)->count();
                        $data = $resultrecord->where($where_data)->find();
                        if ($hou_count > 0) {
                            $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,s.lbp,s.ptwd";
                            $where = array(
                                'r.id' => $resultid,
                            );

                            $r = $this
                                ->alias('r')
                                ->field($field_str)
                                ->join('left join sh_ship s on s.id=r.shipid')
                                ->where($where)
                                ->find();

                            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                                // 	// 空高有误 2009
                                //     $res = array(
                                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                                //     );
                                // } else {

                                /**
                                 * 开始计算排水量
                                 */

                                $TC = $r['tc'];
                                $D_M = $r['d_m'];
                                $p = $r['pwd'];
                                $LBP = $r['lbp'];
//                                $pt = $r['ptwd'];


                                $Dup = (float)$data['d_up'];
                                $Ddown = (float)$data['d_down'];
                                $TPCup = (float)$data['tpc_up'];
                                $TPCdown = (float)$data['tpc_down'];
                                $DSup = (float)$data['ds_up'];
                                $DSdown = (float)$data['ds_down'];
                                $Xfup = (float)$data['xf_up'];
                                $Xfdown = (float)$data['xf_down'];
                                $MTCup = (float)$data['mtc_up'];
                                $MTCdown = (float)$data['mtc_down'];
                                $pt = (float)$data['ptwd'];

                                /**
                                 * 整理数据
                                 *
                                 * */
                                $this->process .= "Received table:\r\n\t"
                                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                                    . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                                    . $pt . "\r\n";


                                //开始计算
                                //开始插值计算
                                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                                $TPC = round($getDS_arr['TPC'], 5);
                                $DS = round($getDS_arr['DS'], 5);
                                $Xf = round($getDS_arr['Xf'], 5);

                                /*                $TPC = 59.5;
                                                $DS = 55118.7;
                                                $Xf = 2.85;*/

                                $dmdz = round($MTCup - $MTCdown, 5);
                                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                                $Dc = round($Dc1 + $Dc2, 5);
                                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                                $Dsc = round($DS + $Dc, 5);
                                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                                $Dspc = round($Dsc + $Dpc, 5);
                                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;


                                $data['tpc'] = $TPC;
                                $data['ds'] = $DS;
                                $data['xf'] = $Xf;
                                $data['dmdz'] = $dmdz;
                                $data['dc1'] = $Dc1;
                                $data['dc2'] = $Dc2;
                                $data['dc'] = $Dc;
                                $data['dsc'] = $Dsc;
                                $data['dpc'] = $Dpc;
                                $data['process'] = urlencode($this->process);

                                $where_r = array(
                                    'resultid' => $resultid,
                                    'solt' => 2,
                                );

                                $recodenums = $resultrecord->where($where_r)->count();


                                M()->startTrans();    //开启事物
                                //添加数据到数据库排水量表数据表,如果存在则修改
                                if ($recodenums > 0) {
                                    $re = $resultrecord->where($where_r)->save($data);
                                } else {
                                    $re = $resultrecord->add($data);
                                }
                                if ($re !== false) {

                                    //计算总货重并修改
                                    //作业前作业后区分是否计算总货重

                                    //作业前
                                    //修改作业前总货重、总容量
                                    $g = array(
                                        'hou_dspc' => $Dspc,
                                    );
                                    $r = $this
                                        ->where(array('id' => $resultid))
                                        ->save($g);
                                    if ($r !== false) {
                                        M()->commit();
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        );
                                    } else {
                                        M()->rollback();
                                        // $trans->rollback();
                                        //其它错误  2
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                        );
                                    }

                                } else {
                                    M()->rollback();
                                    //其它错误 2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                    );
                                }
                                // }
                            } else {
                                if ($solt == 1) {
                                    M()->commit();
                                    //作业前的时候没有作业后的数据不应该报错
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                                    );
                                } else {
                                    M()->rollback();
                                    //其它错误 2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                    );
                                }
                            }
                        } else {
                            if ($solt == 1) {
                                M()->commit();
                                //作业前的时候没有作业后的数据不应该报错
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                                );
                            } else {
                                M()->rollback();
                                //其它错误 2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                );
                            }
                        }
                    } else {
                        M()->rollback();
                        // $trans->rollback();
                        //其它错误  2
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                        );
                    }

                } else {
                    M()->rollback();
                    //其它错误 2
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }
                // }

            } else {
                //其它错误 2
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                );
            }
        } else {
            if ($solt == "2") {
                M()->startTrans();    //开启事物
                $where_data = array(
                    'resultid' => $resultid,
                    'solt' => 2,
                );
                $hou_count = $resultrecord->where($where_data)->count();
                $data = $resultrecord->where($where_data)->find();
                if ($hou_count > 0) {
                    $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,s.lbp,s.ptwd";
                    $where = array(
                        'r.id' => $resultid,
                    );

                    $r = $this
                        ->alias('r')
                        ->field($field_str)
                        ->join('left join sh_ship s on s.id=r.shipid')
                        ->where($where)
                        ->find();

                    if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                        // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                        // 	// 空高有误 2009
                        //     $res = array(
                        //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                        //     );
                        // } else {

                        /**
                         * 开始计算排水量
                         */

                        $TC = $r['tc'];
                        $D_M = $r['d_m'];
                        $p = $r['pwd'];
                        $LBP = $r['lbp'];
//                        $pt = $r['ptwd'];


                        $Dup = (float)$data['d_up'];
                        $Ddown = (float)$data['d_down'];
                        $TPCup = (float)$data['tpc_up'];
                        $TPCdown = (float)$data['tpc_down'];
                        $DSup = (float)$data['ds_up'];
                        $DSdown = (float)$data['ds_down'];
                        $Xfup = (float)$data['xf_up'];
                        $Xfdown = (float)$data['xf_down'];
                        $MTCup = (float)$data['mtc_up'];
                        $MTCdown = (float)$data['mtc_down'];
                        $pt = (float)$data['ptwd'];

                        /**
                         * 整理数据
                         *
                         * */
                        $this->process .= "Received table:\r\n\t"
                            . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                            . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                            . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                            . $pt . "\r\n";


                        //开始计算
                        //开始插值计算
                        $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                        $TPC = round($getDS_arr['TPC'], 5);
                        $DS = round($getDS_arr['DS'], 5);
                        $Xf = round($getDS_arr['Xf'], 5);

                        /*                $TPC = 59.5;
                                        $DS = 55118.7;
                                        $Xf = 2.85;*/

                        $dmdz = round($MTCup - $MTCdown, 5);
                        $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                        $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                        $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                        $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                        $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                        $Dc = round($Dc1 + $Dc2, 5);
                        $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                        $Dsc = round($DS + $Dc, 5);
                        $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                        $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                        $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                        $Dspc = round($Dsc + $Dpc, 5);
                        $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;


                        $data['tpc'] = $TPC;
                        $data['ds'] = $DS;
                        $data['xf'] = $Xf;
                        $data['dmdz'] = $dmdz;
                        $data['dc1'] = $Dc1;
                        $data['dc2'] = $Dc2;
                        $data['dc'] = $Dc;
                        $data['dsc'] = $Dsc;
                        $data['dpc'] = $Dpc;
                        $data['process'] = urlencode($this->process);

                        $where_r = array(
                            'resultid' => $resultid,
                            'solt' => 2,
                        );

                        $recodenums = $resultrecord->where($where_r)->count();


                        M()->startTrans();    //开启事物
                        //添加数据到数据库排水量表数据表,如果存在则修改
                        if ($recodenums > 0) {
                            $re = $resultrecord->where($where_r)->save($data);
                        } else {
                            $re = $resultrecord->add($data);
                        }
                        if ($re !== false) {

                            //计算总货重并修改
                            //作业前作业后区分是否计算总货重

                            //作业前
                            //修改作业前总货重、总容量
                            $g = array(
                                'hou_dspc' => $Dspc,
                            );
                            $r = $this
                                ->where(array('id' => $resultid))
                                ->save($g);
                            if ($r !== false) {
                                M()->commit();
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                );
                            } else {
                                M()->rollback();
                                // $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }

                        } else {
                            M()->rollback();
                            //其它错误 2
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                            );
                        }
                        // }
                    } else {
                        if ($solt == 1) {
                            M()->commit();
                            //作业前的时候没有作业后的数据不应该报错
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                            );
                        } else {
                            M()->rollback();
                            //其它错误 2
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                            );
                        }
                    }
                } else {
                    M()->rollback();
                    #todo 存在争议，本方法只有一个录入/修改水尺的方法调用，调用时会判断是否压载水计算过
                    #todo 如果进入本方法说明计算过，没有找到数据确实应该报错，但是不清楚会不会出现
                    #todo 系统判断失误的情况，导致进入此方法后无法找到数据。目前决定报错
                    //其他错误
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }
            } else {
                //作业前的时候重新计算没有作业前的数据说明有问题，应该报错
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
                );
            }
        }
        return $res;
    }


    /**
     * 重新计算排水量
     * @param $resultid
     * @param $solt
     */
    public function suanDspc1($resultid, $solt)
    {
        $this->process = "";
        #todo 检测计算中需要用到的数据是否缺失，缺失则返回步骤错位
        // 将录入数据更新到表中
        $resultrecord = M('sh_resultrecord');


        $field_str = "";

        $where_data = array(
            'resultid' => $resultid,
            'solt' => 1,
        );

        $record_num = $resultrecord->where($where_data)->count();
        if ($record_num > 0) {

            $data = $resultrecord->where($where_data)->find();
            $field_str = "r.qian_tc as tc,r.qian_d_m as d_m,r.qian_pwd as pwd,s.lbp,s.ptwd";
            $where = array(
                'r.id' => $resultid,
            );

            $r = $this
                ->alias('r')
                ->field($field_str)
                ->join('left join sh_ship s on s.id=r.shipid')
                ->where($where)
                ->find();

            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                // 	// 空高有误 2009
                //     $res = array(
                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                //     );
                // } else {

                /**
                 * 开始计算排水量
                 */

                $TC = $r['tc'];
                $D_M = $r['d_m'];
                $p = $r['pwd'];
                $LBP = $r['lbp'];
                $pt = $r['ptwd'];

                /**
                 * 整理数据
                 *
                 * */
                $this->process .= "Received table:\r\n\t"
                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                    . "\r\n\tMTCup=" . $data['mtc_up'] . ",MTCdown=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                    . $pt . "\r\n";
                $Dup = (float)$data['d_up'];
                $Ddown = (float)$data['d_down'];
                $TPCup = (float)$data['tpc_up'];
                $TPCdown = (float)$data['tpc_down'];
                $DSup = (float)$data['ds_up'];
                $DSdown = (float)$data['ds_down'];
                $Xfup = (float)$data['xf_up'];
                $Xfdown = (float)$data['xf_down'];
                $MTCup = (float)$data['mtc_up'];
                $MTCdown = (float)$data['mtc_down'];
//                $pt = (float)$data['ptwd'];

                //开始计算
                //开始插值计算
                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                $TPC = round($getDS_arr['TPC'], 5);
                $DS = round($getDS_arr['DS'], 5);
                $Xf = round($getDS_arr['Xf'], 5);

                /*                $TPC = 59.5;
                                $DS = 55118.7;
                                $Xf = 2.85;*/

                $dmdz = round($MTCup - $MTCdown, 5);
                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                $Dc = round($Dc1 + $Dc2, 5);
                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                $Dsc = round($DS + $Dc, 5);
                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                $Dspc = round($Dsc + $Dpc, 5);
                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;


                $data['tpc'] = $TPC;
                $data['ds'] = $DS;
                $data['xf'] = $Xf;
                $data['dmdz'] = $dmdz;
                $data['dc1'] = $Dc1;
                $data['dc2'] = $Dc2;
                $data['dc'] = $Dc;
                $data['dsc'] = $Dsc;
                $data['dpc'] = $Dpc;
                $data['process'] = urlencode($this->process);

                $where_r = array(
                    'resultid' => $resultid,
                    'solt' => 1,
                );

                $recodenums = $resultrecord->where($where_r)->count();


                M()->startTrans();    //开启事物
                //添加数据到数据库排水量表数据表,如果存在则修改
                if ($recodenums > 0) {
                    $re = $resultrecord->where($where_r)->save($data);
                } else {
                    $re = $resultrecord->add($data);
                }
                if ($re !== false) {

                    //计算总货重并修改
                    //作业前作业后区分是否计算总货重

                    //作业前
                    //修改作业前总货重、总容量
                    $this->process = "";
                    $g = array(
                        'qian_dspc' => $Dspc,
                    );
                    $r = $this
                        ->where(array('id' => $resultid))
                        ->save($g);
                    if ($r !== false) {
                        $where_data = array(
                            'resultid' => $resultid,
                            'solt' => 2,
                        );
                        $hou_count = $resultrecord->where($where_data)->count();
                        $data = $resultrecord->where($where_data)->find();
                        if ($hou_count > 0) {
                            $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,s.lbp,s.ptwd";
                            $where = array(
                                'r.id' => $resultid,
                            );

                            $r = $this
                                ->alias('r')
                                ->field($field_str)
                                ->join('left join sh_ship s on s.id=r.shipid')
                                ->where($where)
                                ->find();

                            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                                // 	// 空高有误 2009
                                //     $res = array(
                                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                                //     );
                                // } else {

                                /**
                                 * 开始计算排水量
                                 */

                                $TC = $r['tc'];
                                $D_M = $r['d_m'];
                                $p = $r['pwd'];
                                $LBP = $r['lbp'];
                                $pt = $r['ptwd'];

                                /**
                                 * 整理数据
                                 *
                                 * */
                                $this->process .= "Received table:\r\n\t"
                                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                                    . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                                    . $pt . "\r\n";
                                $Dup = (float)$data['d_up'];
                                $Ddown = (float)$data['d_down'];
                                $TPCup = (float)$data['tpc_up'];
                                $TPCdown = (float)$data['tpc_down'];
                                $DSup = (float)$data['ds_up'];
                                $DSdown = (float)$data['ds_down'];
                                $Xfup = (float)$data['xf_up'];
                                $Xfdown = (float)$data['xf_down'];
                                $MTCup = (float)$data['mtc_up'];
                                $MTCdown = (float)$data['mtc_down'];
//                                $pt = (float)$data['ptwd'];

                                //开始计算
                                //开始插值计算
                                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                                $TPC = round($getDS_arr['TPC'], 5);
                                $DS = round($getDS_arr['DS'], 5);
                                $Xf = round($getDS_arr['Xf'], 5);

                                /*                $TPC = 59.5;
                                                $DS = 55118.7;
                                                $Xf = 2.85;*/

                                $dmdz = round($MTCup - $MTCdown, 5);
                                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                                $Dc = round($Dc1 + $Dc2, 5);
                                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                                $Dsc = round($DS + $Dc, 5);
                                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                                $Dspc = round($Dsc + $Dpc, 5);
                                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;


                                $data['tpc'] = $TPC;
                                $data['ds'] = $DS;
                                $data['xf'] = $Xf;
                                $data['dmdz'] = $dmdz;
                                $data['dc1'] = $Dc1;
                                $data['dc2'] = $Dc2;
                                $data['dc'] = $Dc;
                                $data['dsc'] = $Dsc;
                                $data['dpc'] = $Dpc;
                                $data['process'] = urlencode($this->process);

                                $where_r = array(
                                    'resultid' => $resultid,
                                    'solt' => 2,
                                );

                                $recodenums = $resultrecord->where($where_r)->count();


                                M()->startTrans();    //开启事物
                                //添加数据到数据库排水量表数据表,如果存在则修改
                                if ($recodenums > 0) {
                                    $re = $resultrecord->where($where_r)->save($data);
                                } else {
                                    $re = $resultrecord->add($data);
                                }
                                if ($re !== false) {

                                    //计算总货重并修改
                                    //作业前作业后区分是否计算总货重

                                    //作业前
                                    //修改作业前总货重、总容量
                                    $g = array(
                                        'hou_dspc' => $Dspc,
                                    );
                                    $r = $this
                                        ->where(array('id' => $resultid))
                                        ->save($g);
                                    if ($r !== false) {
                                        M()->commit();
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        );
                                    } else {
                                        M()->rollback();
                                        // $trans->rollback();
                                        //其它错误  2
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                        );
                                    }

                                } else {
                                    M()->rollback();
                                    //其它错误 2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                    );
                                }
                                // }
                            } else {
                                if ($solt == 1) {
                                    M()->commit();
                                    //作业前的时候没有作业后的数据不应该报错
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                                    );
                                } else {
                                    M()->rollback();
                                    //其它错误 2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                    );
                                }
                            }
                        } else {
                            if ($solt == 1) {
                                M()->commit();
                                //作业前的时候没有作业后的数据不应该报错
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                                );
                            } else {
                                M()->rollback();
                                //其它错误 2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                );
                            }
                        }
                    } else {
                        M()->rollback();
                        // $trans->rollback();
                        //其它错误  2
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                        );
                    }

                } else {
                    M()->rollback();
                    //其它错误 2
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }
                // }

            } else {
                //其它错误 2
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                );
            }
        } else {
            if ($solt == "2") {
                M()->startTrans();    //开启事物
                $where_data = array(
                    'resultid' => $resultid,
                    'solt' => 2,
                );
                $hou_count = $resultrecord->where($where_data)->count();
                $data = $resultrecord->where($where_data)->find();
                if ($hou_count > 0) {
                    $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,s.lbp,s.ptwd";
                    $where = array(
                        'r.id' => $resultid,
                    );

                    $r = $this
                        ->alias('r')
                        ->field($field_str)
                        ->join('left join sh_ship s on s.id=r.shipid')
                        ->where($where)
                        ->find();

                    if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
                        // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
                        // 	// 空高有误 2009
                        //     $res = array(
                        //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                        //     );
                        // } else {

                        /**
                         * 开始计算排水量
                         */

                        $TC = $r['tc'];
                        $D_M = $r['d_m'];
                        $p = $r['pwd'];
                        $LBP = $r['lbp'];
                        $pt = $r['ptwd'];
                        /**
                         * 整理数据
                         *
                         * */
                        $this->process .= "Received table:\r\n\t"
                            . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                            . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                            . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                            . $pt . "\r\n";
                        $Dup = (float)$data['d_up'];
                        $Ddown = (float)$data['d_down'];
                        $TPCup = (float)$data['tpc_up'];
                        $TPCdown = (float)$data['tpc_down'];
                        $DSup = (float)$data['ds_up'];
                        $DSdown = (float)$data['ds_down'];
                        $Xfup = (float)$data['xf_up'];
                        $Xfdown = (float)$data['xf_down'];
                        $MTCup = (float)$data['mtc_up'];
                        $MTCdown = (float)$data['mtc_down'];
//                        $pt = (float)$data['ptwd'];

                        //开始计算
                        //开始插值计算
                        $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                        $TPC = round($getDS_arr['TPC'], 5);
                        $DS = round($getDS_arr['DS'], 5);
                        $Xf = round($getDS_arr['Xf'], 5);

                        /*                $TPC = 59.5;
                                        $DS = 55118.7;
                                        $Xf = 2.85;*/

                        $dmdz = round($MTCup - $MTCdown, 5);
                        $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                        $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                        $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;

                        $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                        $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;

                        $Dc = round($Dc1 + $Dc2, 5);
                        $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;

                        $Dsc = round($DS + $Dc, 5);
                        $this->process .= "Dsc = $DS + $Dc = " . $Dsc;

                        $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                        $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;

                        $Dspc = round($Dsc + $Dpc, 5);
                        $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;


                        $data['tpc'] = $TPC;
                        $data['ds'] = $DS;
                        $data['xf'] = $Xf;
                        $data['dmdz'] = $dmdz;
                        $data['dc1'] = $Dc1;
                        $data['dc2'] = $Dc2;
                        $data['dc'] = $Dc;
                        $data['dsc'] = $Dsc;
                        $data['dpc'] = $Dpc;
                        $data['process'] = urlencode($this->process);

                        $where_r = array(
                            'resultid' => $resultid,
                            'solt' => 2,
                        );

                        $recodenums = $resultrecord->where($where_r)->count();


                        M()->startTrans();    //开启事物
                        //添加数据到数据库排水量表数据表,如果存在则修改
                        if ($recodenums > 0) {
                            $re = $resultrecord->where($where_r)->save($data);
                        } else {
                            $re = $resultrecord->add($data);
                        }
                        if ($re !== false) {

                            //计算总货重并修改
                            //作业前作业后区分是否计算总货重

                            //作业前
                            //修改作业前总货重、总容量
                            $g = array(
                                'hou_dspc' => $Dspc,
                            );
                            $r = $this
                                ->where(array('id' => $resultid))
                                ->save($g);
                            if ($r !== false) {
                                M()->commit();
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                );
                            } else {
                                M()->rollback();
                                // $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }

                        } else {
                            M()->rollback();
                            //其它错误 2
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                            );
                        }
                        // }
                    } else {
                        if ($solt == 1) {
                            M()->commit();
                            //作业前的时候没有作业后的数据不应该报错
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                            );
                        } else {
                            M()->rollback();
                            //其它错误 2
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                            );
                        }
                    }
                } else {
                    M()->rollback();
                    #todo 存在争议，本方法只有一个录入/修改水尺的方法调用，调用时会判断是否压载水计算过
                    #todo 如果进入本方法说明计算过，没有找到数据确实应该报错，但是不清楚会不会出现
                    #todo 系统判断失误的情况，导致进入此方法后无法找到数据。目前决定报错
                    //其他错误
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }
            } else {
                //作业前的时候重新计算没有作业前的数据说明有问题，应该报错
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
                );
            }
        }
        return $res;
    }




    /**
     * 重新计算常数和货物重量
     * @param $resultid
     */
    public function suanWeight($resultid)
    {
        M()->startTrans();
        $wherelist = array(
            'resultid' => $resultid,
            'solt' => 1,
        );
        $resultlist = new \Common\Model\ShResultlistModel();
        $total_weight = $resultlist->field('sum(weight) as t_weight')->where($wherelist)->find();
//        $result = new \Common\Model\ShResultModel();
        #todo 检测计算中需要用到的数据是否缺失，缺失则返回步骤错位

        $result_msg = $this->field('qian_dspc,qian_fwater_weight,qian_sewage_weight,qian_fuel_weight,qian_other_weight,qian_constant,hou_constant')->where(array('id' => $resultid))->find();

        $data_r = array(
            'qian_constant' => round((float)$result_msg['qian_dspc'] - (float)$total_weight['t_weight'] - (float)$result_msg['qian_fwater_weight'] - (float)$result_msg['qian_sewage_weight'] - (float)$result_msg['qian_fuel_weight'] - (float)$result_msg['qian_other_weight'], 5),
        );

        $this->process = "t_weight=" . (float)$total_weight['t_weight'] . " \r\n qian_constant=dspc - t_weight - fwater_weight- sewage_weight - fuel_weight - other_weight=" . $data_r['qian_constant'];

        if ($result_msg['hou_constant'] > 0) {
            if ($result_msg['qian_constant'] >= $result_msg['hou_constant']) {
                $data_r['weight'] = round((float)$result_msg['qian_constant'] - (float)$result_msg['hou_constant'], 5);
            } else {
                $data_r['weight'] = round((float)$result_msg['hou_constant'] - (float)$result_msg['qian_constant'], 5);
            }
        }

        $data_r['qianprocess'] = array('exp', 'concat(qianprocess,"' . urlencode($this->process) . '")');


        $resr = $this->editData(array('id' => $resultid), $data_r);

        if ($resr === false) {
            M()->rollback();
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                'msg' => $this->getDbError(),
            );
            exit(json_encode($res));
        } else {
            $wherelist = array(
                'resultid' => $resultid,
                'solt' => 2,
            );
            $total_weight = $resultlist->field('sum(weight) as t_weight')->where($wherelist)->find();
            $result_msg = $this->field('hou_dspc,hou_fwater_weight,hou_sewage_weight,hou_fuel_weight,hou_other_weight,qian_constant,hou_constant')->where(array('id' => $resultid))->find();
            $data_r = array(
                'hou_constant' => round((float)$result_msg['hou_dspc'] - (float)$total_weight['t_weight'] - (float)$result_msg['hou_fwater_weight'] - (float)$result_msg['hou_sewage_weight'] - (float)$result_msg['hou_fuel_weight'] - (float)$result_msg['hou_other_weight'], 5),
            );
            $this->process = "t_weight=" . (float)$total_weight['t_weight'] . " \r\n hou_constant=dspc - t_weight - fwater_weight- sewage_weight - fuel_weight - other_weight=" . $data_r['hou_constant'];

            if ($result_msg['qian_constant'] >= $result_msg['hou_constant']) {
                $data_r['weight'] = round((float)$result_msg['qian_constant'] - (float)$result_msg['hou_constant'], 5);
            } else {
                $data_r['weight'] = round((float)$result_msg['hou_constant'] - (float)$result_msg['qian_constant'], 5);
            }
            $data_r['houprocess'] = array('exp', 'concat(houprocess,"' . urlencode($this->process) . '")');

            $resr = $this->editData(array('id' => $resultid), $data_r);
            if ($resr === false) {
                M()->rollback();
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                    'msg' => $this->getDbError(),
                );
                exit(json_encode($res));
            } else {
                M()->commit();
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'constant' => $data_r['hou_constant'],
                    'weight' => isset($data_r['weight']) ? $data_r['weight'] : "",
                );
            }

            return $res;
        }


    }

    /**
     * 插值计算函数,核心算法，根据大参数和小参数算出中间的参数
     * @param int|float $Cbig 大数值
     * @param int|float $Csmall 小数值
     * @param int|float $Xbig 大刻度
     * @param int|float $Xsmall 小刻度
     * @param int|float $X 当前刻度
     * @return float|int 中间插值
     */
    public
    function getMiddleValue($Cbig, $Csmall, $Xbig, $Xsmall, $X)
    {
        $suanfa = round(($Cbig - ($Csmall)), 3) / ($Xbig - ($Xsmall)) * ($X - ($Xsmall)) + $Csmall;
        $this->process .= 'round(($Cbig - ($Csmall)), 3) / ($Xbig - ($Xsmall)) * ($X - ($Xsmall)) + $Csmall =' . $suanfa . '\r\n';
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
    public function getDS($D_M, $Dup, $Ddown, $TPCup, $TPCdown, $DSup, $DSdown, $Xfup, $Xfdown)
    {
        $maxD_M = $Dup > $Ddown ? ($Dup > $D_M ? $Dup : $D_M) : ($Ddown > $D_M ? $Ddown : $D_M);
        $minD_M = $Dup < $Ddown ? ($Dup < $D_M ? $Dup : $D_M) : ($Ddown < $D_M ? $Ddown : $D_M);

        if ($D_M >= $maxD_M) {
            if ($Dup >= $Ddown) {
                return array(
                    'TPC' => $TPCup,
                    'DS' => $DSup,
                    'Xf' => $Xfup,
                );

                $this->process .= " D_M>=Dup: then \r\n TPC,DS,Xf=TPCup,DSup,Xfup = $TPCup , $DSup , $Xfup \r\n";

            } else {
                return array(
                    'TPC' => $TPCdown,
                    'DS' => $DSdown,
                    'Xf' => $Xfdown,
                );
                $this->process .= " D_M>=Dup: then \r\n TPC,DS,Xf=TPCup,DSup,Xfup = $TPCdown , $DSdown , $Xfdown \r\n";

            }

        } elseif ($D_M <= $minD_M) {

            if ($Dup <= $Ddown) {
                return array(
                    'TPC' => $TPCup,
                    'DS' => $DSup,
                    'Xf' => $Xfup,
                );
                $this->process .= "D_M<=Ddown: then \r\n TPC,DS,Xf=TPCdown,DSdown,Xfdown = $TPCup , $DSup , $Xfup \r\n";

            } else {
                return array(
                    'TPC' => $TPCdown,
                    'DS' => $DSdown,
                    'Xf' => $Xfdown,
                );
                $this->process .= "D_M<=Ddown: then \r\n TPC,DS,Xf=TPCdown,DSdown,Xfdown = $TPCdown , $DSdown , $Xfdown \r\n";

            }
        } else {
            $this->process .= " Dup<  D_M  >Ddown: then \r\nTPC=";
            $TPC = $this->getMiddleValue($TPCup, $TPCdown, $Dup, $Ddown, $D_M);
            $this->process .= "DS=";
            $DS = $this->getMiddleValue($DSup, $DSdown, $Dup, $Ddown, $D_M);
            $this->process .= "Xf=";
            $Xf = $this->getMiddleValue($Xfup, $Xfdown, $Dup, $Ddown, $D_M);
            return array(
                'TPC' => $TPC,
                'DS' => $DS,
                'Xf' => $Xf,
            );

        }
    }


    /**
     * 录入书本容量数据
     * */
    public function capacityreckon($data)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($data['uid'], $data['imei']);
        if ($msg1['code'] == '1') {
            // 将录入数据更新到表中
            $resultrecord = M('resultrecord');
            $where = array(
                'resultid' => $data['resultid'],
                'cabinid' => $data['cabinid'],
                'solt' => $data['solt']
            );
            $r = $resultrecord
                ->where($where)
                ->find();
            if (!empty($r)) {
                $datam = array(
                    'xiuullage1' => $data['ullage1'],
                    'xiuullage2' => $data['ullage2'],
                    'capacity1' => $data['capacity1'],
                    'capacity2' => $data['capacity2']
                );
                $trans = M();
                $trans->startTrans();   // 开启事务
                $re = $resultrecord
                    ->where(array('id' => $r['id']))
                    ->save($datam);
                if ($re !== false) {
                    $ship = new \Common\Model\ShipModel();
                    $where1 = array(
                        's.id' => $data['shipid'],
                        'r.id' => $data['resultid']
                    );
                    $shipmsg = $ship
                        ->field('s.suanfa,s.is_guanxian,s.coefficient,r.qianchi,r.houchi,r.qiantemperature,r.qiandensity,r.houtemperature,r.houdensity')
                        ->alias('s')
                        ->join("left join result r on r.shipid=s.id ")
                        ->where($where1)
                        ->find();
                    // 根据前后状态获取吃水差
                    if ($data['solt'] == '1') {
                        $chishui = $shipmsg['qianchi'];
                    } elseif ($data['solt'] == '2') {
                        $chishui = $shipmsg['houchi'];
                    } else {
                        //其他错误	2
                        return $res = array(
                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                        );
                        die;
                    }
                    // 区分获取的作业前后的密度、温度
                    if ($data['solt'] == '1') {
                        $midu = $shipmsg['qiandensity'];
                    } else {
                        $midu = $shipmsg['houdensity'];
                    }

                    // 获取体积修正(15度的密度、温度)
                    $volume = corrent($midu, $r['temperature']);
                    // 膨胀修正
                    $expand = expand($shipmsg['coefficient'], $r['temperature']);
                    //判断船是否加管线,管线容量
                    $cabin = new \Common\Model\CabinModel();
                    $guan = $cabin
                        ->field('id,pipe_line')
                        ->where(array('id' => $data['cabinid']))
                        ->find();
                    if ($shipmsg['is_guanxian'] == '1' and $r['is_pipeline'] == '1') {
                        // 船容量不包含管线，管线有容量--容量=舱管线容量+舱容量
                        $gx = $guan['pipe_line'];
                    } elseif ($shipmsg['is_guanxian'] == '1' and $r['is_pipeline'] == '2') {
                        // 船容量不包含管线，管线无容量
                        $gx = 0;
                    } elseif ($shipmsg['is_guanxian'] == '2' and $r['is_pipeline'] == '1') {
                        // 船容量包含管线，管线有容量
                        $gx = 0;
                    } elseif ($shipmsg['is_guanxian'] == '2' and $r['is_pipeline'] == '2') {
                        // 船容量包含管线，管线无容量--容量=舱容量-舱管线容量
                        // $gx = 0-$guan['pipe_line'];
                        $gx = 0;
                    }
                    // 判断容量大小先后  dt1代表大   dt2代表小
                    if ($data['ullage1'] > $data['ullage2']) {
                        $dt1 = array(
                            'ullage' => $data['ullage1'],
                            'capacity' => $data['capacity1']
                        );
                        $dt2 = array(
                            'ullage' => $data['ullage2'],
                            'capacity' => $data['capacity2']
                        );
                    } else {
                        $dt1 = array(
                            'ullage' => $data['ullage2'],
                            'capacity' => $data['capacity2']
                        );
                        $dt2 = array(
                            'ullage' => $data['ullage1'],
                            'capacity' => $data['capacity1']
                        );
                    }
                    $this->process .= 'Received capacity_table:\r\n\t'
                        . "U1(" . $dt1['ullage'] . ")->CV1(" . $dt1['capacity'] . ")\r\n\t"
                        . "U2(" . $dt2['ullage'] . ")->CV2(" . $dt2['capacity'] . ")\r\n------------------------------------\r\nC_ullage:" . $data['correntkong'] . " then:";

                    // 判断修正后的空高是否在数据中存在
                    if ($data['correntkong'] >= $dt1['ullage']) {
                        $ulist[] = array(
                            'ullage' => $dt1['ullage'],   //输入的空高
                            'capacity' => $dt1['capacity']
                        );

                    } elseif ($data['correntkong'] <= $dt2['ullage']) {
                        $ulist[] = array(
                            'ullage' => $dt2['ullage'],   //输入的空高
                            'capacity' => $dt2['capacity']
                        );
                    } else {
                        $ulist = array(
                            0 => array(
                                'ullage' => $dt1['ullage'],   //输入的空高
                                'capacity' => $dt1['capacity']
                            ),
                            1 => array(
                                'ullage' => $dt2['ullage'],   //输入的空高
                                'capacity' => $dt2['capacity']
                            )
                        );
                    }
                    $qiu[] = array('capacity' => 1);
                    // 下标--随意定义，只要是一位数组
                    $keys[] = 'capacity';
                    //根据提交数据计算
                    //当修正空高大于等于基准高度并且不计算底量的时候
                    self::$function_process = "";
                    if ($r['quantity'] == '2' and $r['altitudeheight'] == $r['ullage']) {
                        $this->process .= 'bilge_stock:false,altitudeheight=C_ullage then: cabin_volume=0 \r\n';
                        $cabinweight = 0;
                    } else {
                        $this->process .= 'bilge_stock:ture or altitudeheight != C_ullage then:\r\n';
                        //计算容量
                        $cabinweight = round($this->suanfa($qiu, $ulist, $keys, $data['correntkong'], $chishui), 4) + $gx;
                        $this->process .= self::$function_process . ' cabin_volume=' . $cabinweight;
                    }

                    $ewer = array(
                        'cabinweight' => $cabinweight,
                        'qiu' => $qiu,
                        'ulist' => $ulist,
                        'keys' => $keys,
                        'correntkong' => $data['correntkong'],
                        'gx' => $gx,
                    );
                    writeLog(json_encode($ewer));
                    // 计算标准容量   容量*体积*膨胀
                    $standardcapacity = round($cabinweight * $volume * $expand, 4);
                    $this->process .= "now_cabin_volume = round(Cabin_volume*VC*EC,4) = " . $standardcapacity . "\r\n";
                    //整合数据保存数据库
                    $datas = array(
                        'temperature' => $r['temperature'],    //温度
                        'cabinweight' => $cabinweight,
                        'cabinid' => $data['cabinid'],
                        'ullage' => $r['ullage'],            //空高
                        'sounding' => $r['sounding'],    //实高
                        'time' => time(),
                        'resultid' => $data['resultid'],
                        'solt' => $data['solt'],        //作业标识
                        'standardcapacity' => $standardcapacity,        //标准容量
                        'volume' => $volume,        //体积修正
                        'expand' => $expand,        //膨胀修正系数
                        'correntkong' => $data['correntkong'],        //修正空距
                        'listcorrection' => $r['listcorrection'],        //纵倾修正
                    );
                    // 判断是否已存在数据，已存在就修改，不存在就新增
                    $wheres = array(
                        'cabinid' => $data['cabinid'],
                        'resultid' => $data['resultid'],
                        'solt' => $data['solt']
                    );
                    $resultlist = new \Common\Model\ResultlistModel();
                    $nums = $resultlist->where($wheres)->count();
                    if ($nums == '1') {
                        //修改数据
                        $resultlist->editData($wheres, $datas);
                        // 获取作业ID
                        $listid = $resultlist->where($wheres)->getField('id');
                    } else {
                        //新增数据
                        $listid = $resultlist->add($datas);
                    }
                    // 图片上传
                    // 保存图片资源
                    $datafile = $this->imgfile($data, $listid);
                    // 删除原有的首吃水 尾吃水
                    $fornt_img = M('resultlist_img')->where(array('resultlist_id' => $listid))->select();
                    // foreach ($fornt_img as $e => $a) {
                    // 	@unlink ($a['img']);
                    // }
                    M('resultlist_img')->where(array('resultlist_id' => $listid))->delete();
                    // 新增图片
                    if (!empty($datafile)) {
                        $aa = M('resultlist_img')->addAll($datafile);
                        if ($aa == false) {
                            M()->rollback();
                            // 数据库错误	3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                            );
                            echo jsonreturn($res);
                            die;
                        }
                    }
                    //计算所有舱作业前/后总标准容量
                    $wheres1 = array(
                        'resultid' => $data['resultid'],
                        'solt' => $data['solt']
                    );
                    $allweight = $resultlist
                        ->field("sum(standardcapacity) as sums")
                        ->where($wheres1)
                        ->select();
                    //根据总标准容量*密度得到作业前/后总的货重
                    $total = round($allweight[0]['sums'] * ($midu - 0.0011), 4);

                    $this->process .= "now_result_cargo_weight = round(sum(now_cabin_volume) * (density - AB),4) =round(" . $allweight[0]['sums'] . " * (" . $midu . " - 0.0011),4) =" . $total . "\r\n";


                    //作业前作业后区分是否计算总货重
                    switch ($data['solt']) {
                        case '1':
                            //作业前
                            //修改作业前总货重、总容量
                            $g = array(
                                'qianweight' => round($allweight[0]['sums'], 4),
                                'qiantotal' => $total,
                            );
                            $e = $this
                                ->where(array('id' => $data['resultid']))
                                ->save($g);
                            if ($e !== false) {
                                $trans->commit();
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                );
                            } else {
                                $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }
                            break;
                        case '2':
                            // 作业后（需要计算总货重）
                            // 修改作业后总货重、总容量
                            // // 判断前后密度是否一样,如果不一样计算密度差
                            // 重量2-（密度2-密度1）*体积1
                            if ($msg['qiandensity'] != $msg['houdensity']) {
                                \Think\Log::record(($msg['houdensity'] - $msg['qiandensity']) * $msg['qianweight']);
                                $total = round($total - ($msg['houdensity'] - $msg['qiandensity']) * $msg['qianweight'], 3);
                                //记录过程
                                $this->process .= "soltType:作业后,now_result_cargo_weight:" . $total . ",before_result_cargo_weight = " . $msg['qianweight'] . ",now_density = "
                                    . $msg['houdensity'] . ",before_density=" . $msg['houdensity']
                                    . " then:\r\n\ttotal_cargo_weight = round(now_result_cargo_weight - (now_density - before_density) * before_result_cargo_weight, 3)=round("
                                    . $total . "-(" . $msg['houdensity'] . "-" . $msg['qiandensity'] . ")*" . $msg['qianweight'] . ",3)=" . $total;

                            }

                            $hou = array(
                                'houweight' => round($allweight[0]['sums'], 4),
                                'houtotal' => $total,

                            );
                            $r = $this->where(array('id' => $data['resultid']))->save($hou);
                            if ($r !== false) {
                                // 获取作业前、后的总货重
                                $sunmmsg = $this
                                    ->field('qiantotal,houtotal')
                                    ->where(array('id' => $data['resultid']))
                                    ->find();
                                // 计算总容量 后-前
                                $weight = round(($sunmmsg['houtotal'] - $sunmmsg['qiantotal']), 4);
                                // 修改总货重
                                $res1 = $this
                                    ->where(array('id' => $data['resultid']))
                                    ->save(array('weight' => $weight));
                                if ($res1 !== false) {
                                    $trans->commit();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                    );
                                } else {
                                    $trans->rollback();
                                    //其它错误  2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                    );
                                }
                            } else {
                                $trans->rollback();
                                //其它错误  2
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                );
                            }
                            break;
                        default:
                            # 不是作业前后，跳出
                            break;
                    }

                    //保存过程数据
                    $resultlist->editData(
                        array(
                            'id' => $listid
                        ),
                        array(
                            'process' => array(
                                'exp', 'concat(process,"' . urlencode($this->process) . '")'
                            ))//计算过程
                    );
                } else {
                    //其他错误	2
                    return $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                    die;
                }
            } else {
                //其它错误 2
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                );
            }
        } else {
            //未到期/状态禁止/标识错误
            $res = $msg1;
        }
        return $res;
    }

    /**
     * 指令新增/修改的数据整理
     * @param array data
     */
    public
    function arrange_data($data)
    {
        $personality = array();
        $list = M('personality')->getField('name', true);
        foreach ($list as $key => $value) {
            if (isset($data[$value])) {
                $personality[$value] = $data[$value];
                unset($data[$value]);
            }
        }
        // 航次
        // if (isset($data['voyage'])) {
        //     $personality['voyage'] = $data['voyage'];
        //     unset($data['voyage']);
        // }
        // // 作业地点
        // if (isset($data['locationname'])) {
        //     $personality['locationname'] = $data['locationname'];
        //     unset($data['locationname']);
        // }
        // // 起运港
        // if (isset($data['start'])) {
        //     $personality['start'] = $data['start'];
        //     unset($data['start']);
        // }
        // // 目的港
        // if (isset($data['objective'])) {
        //     $personality['objective'] = $data['objective'];
        //     unset($data['objective']);
        // }
        // // 货名
        // if (isset($data['goodsname'])) {
        //     $personality['goodsname'] = $data['goodsname'];
        //     unset($data['goodsname']);
        // }
        // // 运当量
        // if (isset($data['transport'])) {
        //     $personality['transport'] = $data['transport'];
        //     unset($data['transport']);
        // }
        // // 发货方
        // if (isset($data['shipper'])) {
        //     $personality['shipper'] = $data['shipper'];
        //     unset($data['shipper']);
        // }
        // // 海船船名
        // if (isset($data['feedershipname'])) {
        //     $personality['feedershipname'] = $data['feedershipname'];
        //     unset($data['feedershipname']);
        // }
        // // 编号
        // if (isset($data['number'])) {
        //     $personality['number'] = $data['number'];
        //     unset($data['number']);
        // }
        // // 海船装运码头
        // if (isset($data['wharf'])) {
        //     $personality['wharf'] = $data['wharf'];
        //     unset($data['wharf']);
        // }
        // // 海船商检量
        // if (isset($data['inspection'])) {
        //     $personality['inspection'] = $data['inspection'];
        //     unset($data['inspection']);
        // }
        // // 海船发货量
        // if (isset($data['volume'])) {
        //     $personality['volume'] = $data['volume'];
        //     unset($data['volume']);
        // }

        if (empty($personality)) {
            $personality = "";
        } else {
            $personality = json_encode($personality, JSON_UNESCAPED_UNICODE);
        }
        $res = $data;
        $res['personality'] = $personality;
        return $res;
    }

    /**
     * 评价
     */
    public
    function evaluate($data)
    {
        $map = array('id' => $data['id']);
        // 获取作业原始评价分数
        $oldgrade = $this->field('grade1,grade2')->where($map)->find();
        M()->startTrans();   // 开启事物

        // 根据公司类型区分修改内容
        if ($data['firmtype'] == '1') {
            // 修改作业评价
            $da = array(
                'grade1' => $data['grade'],
                'evaluate1' => $data['content'],
                'operater1' => $data['operater']
            );
            $re = $this->editData($map, $da);
            if ($re !== false) {
                // 检验公司评价：船舶、船所属公司

                // 修改船舶评价
                $map = array('shipid' => $data['shipid']);
                // 获取船舶原先的评价数值
                $grade = M('ship_historical_sum')->where($map)->getField('grade');
                $datas = array(
                    'grade' => $grade + $data['grade'] - $oldgrade['grade1']
                );

                $res1 = M('ship_historical_sum')->where($map)->save($datas);
                // 评价分为0表示未评价
                if ($oldgrade['grade1'] == '0') {
                    M('ship_historical_sum')->where($map)->setInc('grade_num');
                }

                // 根据船获取所属公司
                $ship = new \Common\Model\ShipModel();
                $firmid = $ship->getFieldById($data['shipid'], 'firmid');
                // 修改公司评价数据
                $ress = $this->edit_firm_grade($firmid, $data['grade'], $oldgrade['grade2']);
                if ($ress['code'] == '1') {
                    M()->commit();  // 事物提交
                    $res = array('code' => 1);
                } else {
                    M()->rollback();    //事物回滚
                    // 作业评价失败！
                    $res = array(
                        'code' => 2,
                        'msg' => '作业评价失败！'
                    );
                }

            } else {
                M()->rollback();  // 事物回滚
                // 作业评价失败！
                $res = array(
                    'code' => 2,
                    'msg' => '作业评价失败！'
                );
            }
        } else if ($data['firmtype'] == '2') {
            // 船舶公司评价的是：操作员、检验公司
            // 修改作业评价
            $da = array(
                'grade2' => $data['grade'],
                'evaluate2' => $data['content'],
                'operater2' => $data['operater']
            );
            $re = $this->editData($map, $da);
            if ($re !== false) {
                // 修改用户评价
                $map = array('userid' => $data['uid']);
                // 获取原先的评价数值
                $grade = M('user_historical_sum')->where($map)->getField('grade');
                $da1 = array(
                    'grade' => $grade + $data['grade'] - $oldgrade['grade2']
                );

                $res1 = M('user_historical_sum')->where($map)->save($da1);

                if ($oldgrade['grade2'] == '0') {
                    M('user_historical_sum')->where($map)->setInc('grade_num');
                }


                // 根据操作人获取所属公司
                $user = new \Common\Model\UserModel();
                $firmid = $user->getFieldById($data['uid'], 'firmid');
                // 修改公司评价数据
                $ress = $this->edit_firm_grade($firmid, $data['grade'], $oldgrade['grade2']);
                if ($ress['code'] == '1') {
                    M()->commit();  // 事物提交
                    $res = array('code' => 1);
                } else {
                    M()->rollback();    //事物回滚
                    // 作业评价失败！
                    $res = array(
                        'code' => 2,
                        'msg' => '作业评价失败！'
                    );
                }

            } else {
                M()->rollback();  // 事物回滚
                // 作业评价失败！
                $res = array(
                    'code' => 2,
                    'msg' => '作业评价失败！'
                );
            }
        } else {
            M()->rollback();  // 事物回滚
            // 公司类型有误
            $res = array(
                'code' => 2,
                'msg' => '公司类型有误'
            );
        }
        return $res;
    }

// 修改公司评价
    public
    function edit_firm_grade($firmid, $data_grade, $oldgrade)
    {
        M()->startTrans();
        $firm_map = array('firmid' => $firmid);
        $grade = M('firm_historical_sum')->where($firm_map)->getField('grade');
        $grade = $grade + $data_grade - $oldgrade;
        $datas = array(
            'grade' => $grade
        );

        $res2 = M('firm_historical_sum')->where($firm_map)->save($datas);
        if ($oldgrade == '0') {
            M('firm_historical_sum')->where($firm_map)->setInc('grade_num');
        }

        // 判断两次评价修改是否成功
        if ($res2 !== false) {
            M()->commit();  // 事物提交
            $res = array('code' => 1);
        } else {
            M()->rollback();    //事物回滚
            // 作业评价失败！
            $res = array(
                'code' => 2,
                'msg' => '作业评价失败！'
            );
        }
        return $res;
    }

    /**
     * 作业重量汇总
     */
    public
    function weight($resultid)
    {
        $aa = array('r.id' => $resultid);
        // 根据作业ID获取操作人ID，船舶ID
        $data = $this
            ->field('r.uid,r.shipid,r.weight,u.firmid,f.firmtype')
            ->alias('r')
            ->join('left join user u on u.id = r.uid')
            ->join('left join firm f on u.firmid = f.id')
            ->where($aa)
            ->find();

        // 判断
        M()->startTrans();   // 开启事物

        // 修改公司工作总货重
        $firm = new \Common\Model\FirmModel();
        $res1 = $this->historical_sum('firm_historical_sum', 'firmid', $data['firmid'], $data['weight']);
        // 如果是检验公司建的指令，也需要给船舶公司也统计
        $ship = new \Common\Model\ShipModel();
        if ($data['firmtype'] == '1') {
            $firmid = $ship->getFieldById($data['shipid'], 'firmid');
            $res4 = $this->historical_sum('firm_historical_sum', 'firmid', $firmid, $data['weight']);
        }

        // 修改理货员总货重
        $user = new \Common\Model\UserModel();
        // $user_map = array('userid'=>$data['uid']);
        // 获取原先的评价数值
        // $user_historical_sum = M('user_historical_sum');
        // $weight1 = $user_historical_sum->where($user_map)->getField('weight');
        // $da2 = array(
        // 	'weight'	=> $weight1+$data['weight']
        // 	);
        // $res2 = $user_historical_sum->where($user_map)->save($da2);
        // $user_historical_sum->where($user_map)->setInc('num');
        $res2 = $this->historical_sum('user_historical_sum', 'userid', $data['uid'], $data['weight']);

        // 修改船舶工作总货重
        // $ship_map = array('shipid'=>$data['shipid']);
        // // 获取船舶原先的评价数值
        // $ship_historical_sum = M('ship_historical_sum');
        // $weight3 = $ship_historical_sum->where($ship_map)->getField('weight');
        // $da3 = array(
        // 	'weight'	=> $weight3+$data['weight']
        // 	);
        // $res3 = $ship_historical_sum->where($ship_map)->save($da3);
        // $ship_historical_sum->where($ship_map)->setInc('num');
        $res3 = $this->historical_sum('ship_historical_sum', 'shipid', $data['shipid'], $data['weight']);


        if ($res1 !== false and $res2 !== false and $res3 !== false) {
            M()->commit();
            $res = array(
                'code' => 1
            );
        } else {
            M()->rollback();
            // 作业数据汇总失败
            $res = array(
                'code' => 2
            );
        }
        return $res;
    }

    /**
     * 修改总量
     */
    public
    function historical_sum($historical, $name, $id, $dataweight)
    {
        $historical_sum = M($historical);
        $map[$name] = $id;
        $weight = $historical_sum->where($map)->getField('weight');
        $weight = $weight + abs($dataweight);
        $datas = array(
            'weight' => $weight
        );

        $res = $historical_sum->where($map)->save($datas);
        $historical_sum->where($map)->setInc('num');
        return $res;
    }

    /**
     * 新计算接口
     */
}