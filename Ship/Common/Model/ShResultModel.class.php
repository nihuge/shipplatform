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
    private $process = array();
    static $function_process = array();

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
                    $arr = $consump->buckleMoney($uid, $firmid, $id, 2);
                    if ($arr['code'] == '1') {
                        #todo 添加船舶统计停泊港功能，自动添加公司，用户，船的历史作业次数。
                        // 扣费成功
                        // 根据船ID获取是否底量字段

                        if (isset($datas['start']) || isset($datas['objective'])) {
                            // 获取船舶原始起始点、终点港原来的统计停泊港
                            $moorings = M('sh_ship_historical_sum')->getFieldByshipid($data['shipid'], 'mooring');
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
                            M('sh_ship_historical_sum')->where(array('shipid' => $data['shipid']))->save(array('mooring' => $moorings));
                        }

                        // 修改公司历史数据--作业次数
                        M('firm_historical_sum')->where(array('firmid' => $firmid))->setInc('num');
                        M('user_historical_sum')->where(array('userid' => $uid))->setInc('num');
                        M('sh_ship_historical_sum')->where(array('shipid' => $data['shipid']))->setInc('num');


                        M()->commit();
                        //成功 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']],
                            'resultid' => $id,
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
        return $res;
    }

    /**
     * 完成作业
     * @param $result_id
     * @param $uid
     * @return array
     */
    public function finishResult($result_id, $uid)
    {
        $user = new \Common\Model\UserModel();
        $result_where = array(
            's.id' => ":id",
        );
        //预编译绑定参数
        $result_bind = array(
            ':id' => intval($result_id),
        );
        $result_msg = $this
            ->alias("r")
            ->field("r.uid,u.firmid,r.qian_constant,r.hou_constant,r.weight")
            ->join("left join user as u on u.id=r.uid")
            ->where($result_where)
            ->bind($result_bind)
            ->find();

        //判断用户权限，只允许用户自己或者所属公司的管理员操作，报错2024，不允许其他人操作
        if ((intval($uid) != $result_msg['uid'] && !$user->checkAdmin($uid, $result_msg['firm_id'])) || intval($uid) == 0) return array('code' => $this->ERROR_CODE_RESULT['OTHERS_OPERATE']);
        //作业必须完成，否则报错2019,作业未完成
        if ($result_msg['qian_constant'] === null || $result_msg['hou_constant'] === null || $result_msg['hou_constant'] === null) return array('code' => $this->ERROR_CODE_RESULT['NOT_EVAL']);
        //修改状态
        $edit_data = array(
            'finish' => 1,
            'finish_time' => time(),
        );


        $edit_result = $this->where($result_where)->bind($result_bind)->save($edit_data);
        if ($edit_result === false) {
            //失败返回修改失败 11
            return array('code' => $this->ERROR_CODE_COMMON['EDIT_FALL']);
        } else {
            //成功
            return array('code' => $this->ERROR_CODE_COMMON['SUCCESS']);
        }
    }

    /**
     * 检查作业是否结束
     * @param $resultid
     * @return bool
     */
    public function checkFinish($resultid)
    {
        //获取作业状态
        $finish_state = $this->getFieldById(intval($resultid), "finish");
        if ($finish_state == 1) {
            return true;
        } else {
            return false;
        }
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
//            $rl = new \Common\Model\ShResultlistModel();
            // $re = $rl->where(array('resultid'=>I('post.resultid')))->count();
//            $re = $rl->valiname($data['resultid'], 'resultid');
//            if ($re === false) {
//                // 指令有作业，不能修改 2004
//                $res = array(
//                    'code' => $this->ERROR_CODE_RESULT['IS_RESULT_IS'],
//                    'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['IS_RESULT_IS']],
//                );
//            } else {
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
//            }
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
                ->field('*')
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

//        $LBP = (float)$ship_msg['lbp'];
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
                //获取船数据
                $ship = new \Common\Model\ShShipModel();
                $result_ship_id = $this->field('shipid')->where(array('id' => $resultid))->find();
                $wheres = array(
                    'id' => $result_ship_id['shipid'],
                );
                $ship_msg = $ship->field('data_ship')->where($wheres)->find();

                //成功 1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $data,
                    'data_ship' => $ship_msg['data_ship']
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
//    public function forntOperation($datas)
//    {
//        $user = new \Common\Model\UserModel();
//        //判断用户状态、是否到期、标识比对
//        $msg1 = $user->is_judges($datas['uid'], $datas['imei']);
//
//        $this->process = $this->getFieldById($datas['resultid'],"process");
//
//        if ($msg1['code'] == '1') {
//            $res_num = $this->where(array('id' => $datas['resultid']))->count();
//            if ($res_num > 0) {
//
////                if($datas['solt'] == '2'){
////
////                }
//
//                //开始接受值到变量，类型转换，防止用户乱填
//                // 艏左，艏右
//                $forntleft = (float)$datas['forntleft'];
//                $forntright = (float)$datas['forntright'];
//                // 艉左，艉右
//                $afterleft = (float)$datas['afterleft'];
//                $afterright = (float)$datas['afterright'];
//                //舯左，舯右
//                $centerleft = (float)$datas['centerleft'];
//                $centerright = (float)$datas['centerright'];
//                //获取港水密度
//                $PWD = (float)$datas['pwd'];
//
//
//                //获取船数据
//                $ship = new \Common\Model\ShShipModel();
//                $result_ship_id = $this->field('shipid')->where(array('id' => $datas['resultid']))->find();
//                $wheres = array(
//                    'id' => $result_ship_id['shipid'],
//                );
//                $ship_msg = $ship->field('lbp,df,da,dm')->where($wheres)->find();
////            exit(json_encode($ship_msg));
//                $LBP = (float)$ship_msg['lbp'];
//
//                //获取水尺距垂线距离
//                $Df = (float)$ship_msg['df'];
//                $Da = (float)$ship_msg['da'];
//                $Dm = (float)$ship_msg['dm'];
//
//                //初始化水尺相对垂线位置
//                $Pf = 0;
//                $Pa = 0;
//                $Pm = 0;
//
//                if ($Df <= 0) {
//                    $Pf = 1;
//                    $Df = abs($Df);
//                }
//
//                if ($Da <= 0) {
//                    $Pa = 1;
//                    $Da = abs($Da);
//                }
//
//                if ($Dm <= 0) {
//                    $Pm = 1;
//                    $Dm = abs($Dm);
//                }
//
//                //计算平均吃水差
//
//                $Fps = round(($forntleft + $forntright) / 2, 5);
//                $Aps = round(($afterleft + $afterright) / 2, 5);
//                $Mps = round(($centerleft + $centerright) / 2, 5);
//
//                //拿到吃水差
//                $T = $Aps - $Fps;
//
//                //吃水差正负状态
//                if ($T > 0) {
//                    $Tf = 0;
//                } else {
//                    $Tf = 1;
//                }
//
//                $Fflag = $Tf ^ $Pf; //矫正吃水flag
//                $Aflag = $Tf ^ $Pa; //矫正吃水flag
//                $Mflag = $Tf ^ $Pm; //矫正吃水flag
//
//                $LBM = round($LBP + pow(-1, $Pf) * $Df - pow(-1, $Pa) * $Da, 5);//计算艏艉水尺间长
//
//                $Fc = round(abs($Df * $T / $LBM), 5);//计算艏吃水矫正值
//                $Ac = round(abs($Da * $T / $LBM), 5);//计算艉吃水校正值
//                $Mc = round(abs($Dm * $T / $LBM), 5);//舯吃水校正值
//
//                $Fm = round($Fps + pow(-1, $Fflag) * $Fc, 5);//计算校正后艏吃水
//                $Am = round($Aps + pow(-1, $Aflag) * $Ac, 5);//计算校正后艉吃水
//                $Mm = round($Mps + pow(-1, $Mflag) * $Mc, 5);//计算校正后舯吃水
//
//                $TC = round($Am - $Fm, 5);//计算矫正后吃水差
//
//                $D_M = round(($Fm + $Am + (6 * $Mm)) / 8, 5);//计算拱陷矫正后总平均吃水
//
//                $this->process .= "nowtime:" . date('Y-m-d H:i:s', time()) . "------------------\r\nforntleft=" . $forntleft . ",forntright=" . $forntright
//                    . ",\r\n afterleft=" . $afterleft . ",afterright=" . $afterright . ",\r\n centerleft=" . $centerleft . ",centerright=" . $centerright . ",\r\np=" . $PWD
//                    . ",LBP=" . $LBP . ",\r\n Df=" . $Df . ",Da=" . $Da . ",Dm=" . $Dm . ",\r\nPf=" . $Pf . ",Pa=" . $Pa . ",Pm=" . $Pm
//                    . ",\r\nFPS=round(($forntleft + $forntright) / 2, 5)=" . $Fps . ",Aps=round(($afterleft + $afterright) / 2, 5)=" . $Aps . ",Mps=round(($centerleft + $centerright) / 2, 5)=" . $Mps
//                    . ",\r\nT=$Aps - $Fps=" . $T . " then Tf=" . $Tf . ",Fflag=" . $Fflag . ",Aflag=" . $Aflag . ",Mflag=" . $Mflag
//                    . ",\r\nLBM=round($LBP + pow(-1, $Pf) * $Df - pow(-1, $Pa) * $Da, 5)=" . $LBM
//                    . ",\r\nFc=round(abs($Df * $T / $LBM), 5)=" . $Fc . ",Ac=round(abs($Da * $T / $LBM), 5)=" . $Ac . ",Mc=round(abs($Dm * $T / $LBM), 5)=" . $Mc
//                    . ",\r\nFm=round($Fps + pow(-1, $Fflag) * $Fc, 5)=" . $Fm . ",Am=round($Aps + pow(-1, $Aflag) * $Ac, 5)=" . $Am . ",Mm=round($Mps + pow(-1, $Mflag) * $Mc, 5)=" . $Mm
//                    . ",\r\nTC = round($Am - $Fm, 5)=" . $TC . ",D_M=round(($Fm + $Am + (6 * $Mm)) / 8, 5)=" . $D_M . "\r\n";
//
//
//
//
//
//                //水尺数据
//                $data = array(
//                    'forntleft' => $forntleft,
//                    'forntright' => $forntright,
//                    'centerleft' => $centerleft,
//                    'centerright' => $centerright,
//                    'afterleft' => $afterleft,
//                    'afterright' => $afterright,
//                    'fornt' => $Fps,
//                    'center' => $Mps,
//                    'after' => $Aps,
//                    'fc' => $Fc,
//                    'ac' => $Ac,
//                    'mc' => $Mc,
//                    'fm' => $Fm,
//                    'am' => $Am,
//                    'mm' => $Mm,
//                    'solt' => $datas['solt'],
//                    'resultid' => $datas['resultid']
//                );
//
//                //作业表的数据
//                $data1 = array(
//                    'solt' => $datas['solt'],
//                );
//                $result_field = "";
//                if ($datas['solt'] == '1') {
//                    $this->process .= "soltType=作业前 then:\r\n";
//                    //存储吃水差和拱陷修正后总平均吃水
//                    $data1['qian_tc'] = $TC;
//                    $data1['qian_d_m'] = $D_M;
//                    $data1['qian_pwd'] = (float)$datas['pwd'];//存储密度
//                    $data1['qian_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
//                    $data1['qian_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
//                    $data1['qian_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
//                    $data1['qian_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
//                    $result_field = "qian_dspc as dspc,qian_constant as constant";
//                    $this->process .= "\t TC=" . $data1['qian_tc'] . ",D_M=" . $data1['qian_d_m'] . "\r\n fwater_weight=" . $data1['qian_fwater_weight']
//                        . ",sewage_weight=" . $data1['qian_sewage_weight'] . ",fuel_weight=" . $data1['qian_fuel_weight'] . ",fwater_weight=" . $data1['qian_other_weight'] . "\r\n";
//
//                    $data1['qianprocess'] = urlencode($this->process);
//                } elseif ($datas['solt'] == '2') {
//                    $this->process .= "soltType: 作业后 then:\r\n";
//                    //存储吃水差和拱陷修正后总平均吃水
//                    $data1['hou_tc'] = $TC;
//                    $data1['hou_d_m'] = $D_M;
//                    $data1['hou_pwd'] = $datas['pwd'];//存储密度
//                    $data1['hou_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
//                    $data1['hou_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
//                    $data1['hou_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
//                    $data1['hou_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
//                    $result_field = "hou_dspc as dspc,hou_constant as constant";
//                    $this->process .= "\t TC=" . $data1['hou_tc'] . ",D_M=" . $data1['hou_d_m'] . "\r\n";
//                    //将过程存入数据库
//                    $data1['houprocess'] = urlencode($this->process);
//                }
//
//                // 判断水尺数据是否存在 添加/修改数据
//                $map = array(
//                    'solt' => $datas['solt'],
//                    'resultid' => $datas['resultid']
//                );
//                $num = M('sh_forntrecord')->where($map)->count();
//                M()->startTrans();  // 开启事物
//                if ($num > 0) {
//                    //数据存在--修改
//                    $r = M('sh_forntrecord')->where($map)->save($data);
//                } else {
//                    //数据不存在--新增
//                    $r = M('sh_forntrecord')->add($data);
//                }
//
//
//                #todo 支持添加水尺计量图片
//                /*$datafile = array();
//                // 判断是否存在首吃水 尾吃水
//                if (!empty($datas['firstfiles']) && $datas['firstfiles'] != '[]') {
//                    $firstfiles = substr($datas['firstfiles'], 1);
//                    $firstfiles = substr($firstfiles, 0, -1);
//                    $firstfiles = explode(',', $firstfiles);
//                    foreach ($firstfiles as $key => $value) {
//                        $datafile[] = array(
//                            'img' => trim($value),
//                            'result_id' => $datas['resultid'],
//                            'types' => 1,
//                            'solt' => $datas['solt']
//                        );
//                    }
//                }
//
//                // 判断是否存在尾吃水
//                if (!empty($datas['tailfiles']) && $datas['tailfiles'] != '[]') {
//
//                    $tailfiles = substr($datas['tailfiles'], 1);
//                    $tailfiles = substr($tailfiles, 0, -1);
//                    $tailfiles = explode(',', $tailfiles);
//                    foreach ($tailfiles as $key => $value) {
//                        $datafile[] = array(
//                            'img' => trim($value),
//                            'result_id' => $datas['resultid'],
//                            'types' => 2,
//                            'solt' => $datas['solt']
//                        );
//                    }
//                }
//
//                // 删除原有的首吃水 尾吃水
//                $fornt_img = M('fornt_img')->where(array('result_id' => $datas['resultid'], 'solt' => $datas['solt']))->select();
//
//                if (!empty($datafile)) {
//                    M('fornt_img')->where(array('result_id' => $datas['resultid'], 'solt' => $datas['solt']))->delete();
//                    // 新增图片
//                    $aa = M('fornt_img')->addAll($datafile);
//                    if ($aa == false) {
//                        M()->rollback();
//                        // 数据库错误	3
//                        $res = array(
//                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
//                        );
//                        echo jsonreturn($res);
//                        die;
//                    }
//                }*/
//
//                // 添加/修改数据
//                $m = array(
//                    'id' => $datas['resultid']
//                );
//                $res = $this->editData($m, $data1);
//                // 修改数据成功
//                if ($res['code'] !== false and $r !== false) {
//                    #todo 如果存在已有的作业数据，则重新计算已录入的数据
//                    M()->commit();
//                    $r_data = $this->field($result_field)->where($m)->find();
//
//                    if ($r_data['dspc'] > 0) {
//                        $dspc_res = $this->suanDspc($datas['resultid'], $datas['solt']);
//                    } else {
//                        $dspc_res['code'] = 1;
//                    }
//
//                    if ($r_data['constant'] > 0) {
//                        $weight_res = $this->suanWeight($datas['resultid']);
//                    } else {
//                        $weight_res['code'] = 1;
//                    }
//
//                    if ($dspc_res['code'] == 1 and $weight_res['code'] == 1) {
//                        //成功 1
//                        $res = array(
//                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                            'D_M' => $D_M,
//                        );
//                    } else {
//                        $res = array(
//                            'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
//                            'dspc' => $dspc_res['code'],
//                            'weight' => $weight_res['code'],
//                        );
//                    }
//                    /*$where = array(
//                        'solt' => $datas['solt'],
//                        'resultid' => $datas['resultid'],
//                        'is_work' => array('eq', 1)
//                    );
//                    // 如果存在作业数据，重新计算已录入的数据
//                    $n = M('resultrecord')->where($where)->select();
//                    if (!empty($n)) {
//                        foreach ($n as $key => $value) {
//                            $this->process = "";
//                            $value['uid'] = $datas['uid'];
//                            $value['imei'] = $datas['imei'];
//                            $this->reckon($value);
//                        }
//                    }*/
//
//                } else {
//                    //数据库连接错误	3
//                    M()->rollback();
//                    $res = array(
//                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
//                    );
//                }
//            } else {
//                $res = array(
//                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                );
//            }
//        } else {
//            // 错误信息
//            $res = $msg1;
//        }
//        return $res;
//    }


    /**
     * 水尺操作(水尺信息记录、修改作业水尺差数据)
     */
    public function forntOperation1($datas)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($datas['uid'], $datas['imei']);

        if ($datas['solt'] == '1') {
            $this->process = json_decode($this->getFieldById($datas['resultid'], "qianprocess"), true);
        } elseif ($datas['solt'] == '2') {
            $this->process = json_decode($this->getFieldById($datas['resultid'], "houprocess"), true);
        } else {

            //其他错误 4
            return array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );
        }

        if ($this->process == null) {
            $this->process = array();
        }

        if ($msg1['code'] == '1') {
            $res_num = $this->where(array('id' => $datas['resultid']))->count();
            if ($res_num > 0) {
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
                $ship_msg = $ship->field('shipname,lbp,df,da,dm')->where($wheres)->find();
                $LBP = (float)$ship_msg['lbp'];

                //获取水尺距垂线距离
                $Df = (float)$ship_msg['df'];
                $Da = (float)$ship_msg['da'];
                $Dm = (float)$ship_msg['dm'];

                //初始化水尺相对垂线位置
                $Pf = 0;
                $Pa = 0;
                $Pm = 0;

                //判断flag变量
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


                /**
                 * 记录计算过程重要变量
                 */
                $this->process['nowtime'] = date('Y-m-d H:i:s', time());
                $this->process['forntleft'] = $forntleft;
                $this->process['forntright'] = $forntright;
                $this->process['afterleft'] = $afterleft;
                $this->process['afterright'] = $afterright;
                $this->process['centerleft'] = $centerleft;
                $this->process['centerright'] = $centerright;

                $this->process['p'] = $PWD;
                $this->process['LBP'] = $LBP;
                $this->process['Df'] = $Df;
                $this->process['Da'] = $Da;
                $this->process['Dm'] = $Dm;
                $this->process['Pf'] = $Pf;
                $this->process['Pa'] = $Pa;
                $this->process['Pm'] = $Pm;

                $this->process['Fps'] = $Fps;
                $this->process['Aps'] = $Aps;
                $this->process['Mps'] = $Mps;
                $this->process['T'] = $T;
                $this->process['Tf'] = $Tf;
                $this->process['Fflag'] = $Fflag;
                $this->process['Aflag'] = $Aflag;
                $this->process['Mflag'] = $Mflag;
                $this->process['LBM'] = $LBM;

                $this->process['Fc'] = $Fc;
                $this->process['Ac'] = $Ac;
                $this->process['Mc'] = $Mc;

                $this->process['Fm'] = $Fm;
                $this->process['Am'] = $Am;
                $this->process['Mm'] = $Mm;

                $this->process['TC'] = $TC;
                $this->process['D_M'] = $D_M;

                $this->process['ship_name'] = $ship_msg['shipname'];


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

                if ($datas['solt'] == '1') {
//                    $this->process .= "soltType=作业前 then:\r\n";
                    $this->process['soltType'] = "作业前";

                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['qian_tc'] = $TC;
                    $data1['qian_d_m'] = $D_M;
                    $data1['qian_pwd'] = (float)$datas['pwd'];//存储密度
                    if (isset($datas['fwater_weight'])) {
                        $data1['qian_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                        $this->process['fwater_weight'] = $data1['qian_fwater_weight'];

                    }
                    if (isset($datas['sewage_weight'])) {
                        $data1['qian_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                        $this->process['sewage_weight'] = $data1['qian_sewage_weight'];

                    }
                    if (isset($datas['fuel_weight'])) {
                        $data1['qian_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                        $this->process['fuel_weight'] = $data1['qian_fuel_weight'];

                    }
                    if (isset($datas['other_weight'])) {
                        $data1['qian_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                        $this->process['other_weight'] = $data1['qian_other_weight'];
                    }

                    $result_field = "qian_dspc as dspc,qian_constant as constant";

                    /*$this->process .= "\t TC=" . $data1['qian_tc'] . ",D_M=" . $data1['qian_d_m'] . "\r\n fwater_weight=" . $data1['qian_fwater_weight']
                        . ",sewage_weight=" . $data1['qian_sewage_weight'] . ",fuel_weight=" . $data1['qian_fuel_weight'] . ",other_weight=" . $data1['qian_other_weight'] . "\r\n";*/

                    //存储计算过程变量
                    $data1['qianprocess'] = json_encode($this->process);

                } elseif ($datas['solt'] == '2') {
//                    $this->process .= "soltType: 作业后 then:\r\n";
                    $this->process['soltType'] = "作业后";

                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['hou_tc'] = $TC;
                    $data1['hou_d_m'] = $D_M;
                    $data1['hou_pwd'] = $datas['pwd'];//存储密度
                    if (isset($datas['fwater_weight'])) {
                        $data1['hou_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                        $this->process['fwater_weight'] = $data1['qian_fwater_weight'];

                    }

                    if (isset($datas['sewage_weight'])) {
                        $data1['hou_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                        $this->process['sewage_weight'] = $data1['qian_sewage_weight'];

                    }

                    if (isset($datas['fuel_weight'])) {
                        $data1['hou_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                        $this->process['fuel_weight'] = $data1['qian_fuel_weight'];

                    }

                    if (isset($datas['other_weight'])) {
                        $data1['hou_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                        $this->process['other_weight'] = $data1['qian_other_weight'];
                    }

                    $result_field = "hou_dspc as dspc,hou_constant as constant";
//                    $this->process .= "\t TC=" . $data1['hou_tc'] . ",D_M=" . $data1['hou_d_m'] . "\r\n";

                    //存储计算过程变量
                    //将过程存入数据库
                    $data1['houprocess'] = json_encode($this->process);
                } else {
                    //其他错误 4
                    return array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
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


                //自动填充下一界面的累积数据,不需要报错
                $hydrostatic_data = $this->get_cumulative_hydrostatic_data($result_ship_id['shipid'], $D_M);

                if($hydrostatic_data !== false){
                    $resultrecord = M('sh_resultrecord');
                    //检索条件构成
                    $where_r = array(
                        'resultid' => $data['resultid'],
                        'solt' => $data['solt'],
                    );
                    $record_data = array_merge($hydrostatic_data, $where_r);
                    $record_data['shipid'] = $result_ship_id['shipid'];
                    $recodenums = $resultrecord->where($where_r)->count();
                    //添加数据到数据库排水量表数据表,如果存在则修改
                    if ($recodenums > 0) {
                        $re = $resultrecord->where($where_r)->save($record_data);
                    } else {
                        $re = $resultrecord->add($record_data);
                    }
                }else{
                    $re = true;
                }


                // 添加/修改数据
                $m = array(
                    'id' => $datas['resultid']
                );
                $res = $this->editData($m, $data1);
                // 修改数据成功
                if ($res !== false and $r !== false and $re !== false) {

                    M()->commit();
                    $r_data = $this->field($result_field)->where($m)->find();

                    // 如果存在已有的作业数据，则重新计算已录入的数据
                    if ($r_data['dspc'] > 0) {
                        $dspc_res = $this->suanDspc1($datas['resultid'], $datas['solt']);
                    } else {
                        $dspc_res['code'] = 1;
                    }

                    if ($r_data['constant'] > 0) {
                        $weight_res = $this->suanWeight($datas['resultid'], $result_ship_id);
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
                } else {
                    //数据库连接错误	3
                    M()->rollback();
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                //其他错误 4
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
     * 水尺操作(水尺信息记录、修改作业水尺差数据)
     */
    public function forntOperation2($datas)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($datas['uid'], $datas['imei']);

        if ($datas['solt'] == '1') {
            $this->process = json_decode($this->getFieldById($datas['resultid'], "qianprocess"), true);
        } elseif ($datas['solt'] == '2') {
            $this->process = json_decode($this->getFieldById($datas['resultid'], "houprocess"), true);
        } else {

            //其他错误 4
            return array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );
        }

        if ($this->process == null) {
            $this->process = array();
        }

        if ($msg1['code'] == '1') {
            $res_num = $this->where(array('id' => $datas['resultid']))->count();
            if ($res_num > 0) {
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
                $ship_msg = $ship->field('shipname,lbp,df,da,dm,ptwd,ds_table')->where($wheres)->find();
                $LBP = (float)$ship_msg['lbp'];

                //获取水尺距垂线距离
                $Df = (float)$ship_msg['df'];
                $Da = (float)$ship_msg['da'];
                $Dm = (float)$ship_msg['dm'];

                //初始化水尺相对垂线位置
                $Pf = 0;
                $Pa = 0;
                $Pm = 0;

                //判断flag变量
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


                /**
                 * 记录计算过程重要变量
                 */
                $this->process['nowtime'] = date('Y-m-d H:i:s', time());
                $this->process['forntleft'] = $forntleft;
                $this->process['forntright'] = $forntright;
                $this->process['afterleft'] = $afterleft;
                $this->process['afterright'] = $afterright;
                $this->process['centerleft'] = $centerleft;
                $this->process['centerright'] = $centerright;

                $this->process['p'] = $PWD;
                $this->process['LBP'] = $LBP;
                $this->process['Df'] = $Df;
                $this->process['Da'] = $Da;
                $this->process['Dm'] = $Dm;
                $this->process['Pf'] = $Pf;
                $this->process['Pa'] = $Pa;
                $this->process['Pm'] = $Pm;

                $this->process['Fps'] = $Fps;
                $this->process['Aps'] = $Aps;
                $this->process['Mps'] = $Mps;
                $this->process['T'] = $T;
                $this->process['Tf'] = $Tf;
                $this->process['Fflag'] = $Fflag;
                $this->process['Aflag'] = $Aflag;
                $this->process['Mflag'] = $Mflag;
                $this->process['LBM'] = $LBM;

                $this->process['Fc'] = $Fc;
                $this->process['Ac'] = $Ac;
                $this->process['Mc'] = $Mc;

                $this->process['Fm'] = $Fm;
                $this->process['Am'] = $Am;
                $this->process['Mm'] = $Mm;

                $this->process['TC'] = $TC;
                $this->process['D_M'] = $D_M;

                $this->process['ship_name'] = $ship_msg['shipname'];


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

                if ($datas['solt'] == '1') {
//                    $this->process .= "soltType=作业前 then:\r\n";
                    $this->process['soltType'] = "作业前";

                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['qian_tc'] = $TC;
                    $data1['qian_d_m'] = $D_M;
                    $data1['qian_pwd'] = (float)$datas['pwd'];//存储密度
                    if (isset($datas['fwater_weight'])) {
                        $data1['qian_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                        $this->process['fwater_weight'] = $data1['qian_fwater_weight'];

                    }
                    if (isset($datas['sewage_weight'])) {
                        $data1['qian_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                        $this->process['sewage_weight'] = $data1['qian_sewage_weight'];

                    }
                    if (isset($datas['fuel_weight'])) {
                        $data1['qian_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                        $this->process['fuel_weight'] = $data1['qian_fuel_weight'];

                    }
                    if (isset($datas['other_weight'])) {
                        $data1['qian_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                        $this->process['other_weight'] = $data1['qian_other_weight'];
                    }

                    $result_field = "qian_dspc as dspc,qian_constant as constant";

                    /*$this->process .= "\t TC=" . $data1['qian_tc'] . ",D_M=" . $data1['qian_d_m'] . "\r\n fwater_weight=" . $data1['qian_fwater_weight']
                        . ",sewage_weight=" . $data1['qian_sewage_weight'] . ",fuel_weight=" . $data1['qian_fuel_weight'] . ",other_weight=" . $data1['qian_other_weight'] . "\r\n";*/

                    //存储计算过程变量
                    $data1['qianprocess'] = json_encode($this->process);

                } elseif ($datas['solt'] == '2') {
//                    $this->process .= "soltType: 作业后 then:\r\n";
                    $this->process['soltType'] = "作业后";

                    //存储吃水差和拱陷修正后总平均吃水
                    $data1['hou_tc'] = $TC;
                    $data1['hou_d_m'] = $D_M;
                    $data1['hou_pwd'] = $datas['pwd'];//存储密度
                    if (isset($datas['fwater_weight'])) {
                        $data1['hou_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
                        $this->process['fwater_weight'] = $data1['qian_fwater_weight'];

                    }

                    if (isset($datas['sewage_weight'])) {
                        $data1['hou_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
                        $this->process['sewage_weight'] = $data1['qian_sewage_weight'];

                    }

                    if (isset($datas['fuel_weight'])) {
                        $data1['hou_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
                        $this->process['fuel_weight'] = $data1['qian_fuel_weight'];

                    }

                    if (isset($datas['other_weight'])) {
                        $data1['hou_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
                        $this->process['other_weight'] = $data1['qian_other_weight'];
                    }

                    $result_field = "hou_dspc as dspc,hou_constant as constant";
//                    $this->process .= "\t TC=" . $data1['hou_tc'] . ",D_M=" . $data1['hou_d_m'] . "\r\n";

                    //存储计算过程变量
                    //将过程存入数据库
                    $data1['houprocess'] = json_encode($this->process);
                } else {
                    //其他错误 4
                    return array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
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




                // 添加/修改数据
                $m = array(
                    'id' => $datas['resultid']
                );
                $res = $this->editData($m, $data1);
                // 修改数据成功
                if ($res !== false and $r !== false) {

                    M()->commit();
                    $r_data = $this->field($result_field)->where($m)->find();

                    // 如果存在已有的作业数据，则重新计算已录入的数据
                    if ($r_data['dspc'] > 0) {
                        $dspc_res = $this->suanDspc1($datas['resultid'], $datas['solt']);
                    } else {
                        $dspc_res['code'] = 1;
                    }

                    if ($r_data['constant'] > 0) {
                        $weight_res = $this->suanWeight($datas['resultid'], $result_ship_id);
                    } else {
                        $weight_res['code'] = 1;
                    }


                    if ($dspc_res['code'] == 1 and $weight_res['code'] == 1) {
                        $this->process = array();
                        //检索条件构成
                        $where_r = array(
                            'resultid' => $datas['resultid'],
                            'solt' => $datas['solt'],
                        );
                        $resultrecord = M('sh_resultrecord');
                        //获取舱计算过程
                        $process = $resultrecord->field("process")->where($where_r)->find();

                        //过程转换数组
                        if ($process !== false) {
                            $this->process = json_decode($process['process'], true);
                            if ($this->process === null) {
                                $this->process = array(
                                    'table' => array()
                                );
                            }
                        } else {
                            $this->process = array(
                                'table' => array()
                            );
                        }
                        //开始自动计算压载水
                        //取船舶信息
                        /**
                         * 开始计算排水量
                         */

                        $p = $PWD;
                        $LBP = $ship_msg['lbp'];
                        $pt = $ship_msg['ptwd'];

                        /**
                         * 整理数据
                         *
                         * */
                        //开始寻找有表船的静水力表数据
                        $table_data = $this->downup($D_M,$ship_msg['ds_table'],true);
//                echo $D_M;
//                exit(jsonreturn($table_data));

                        if(count($table_data) == 1){
                            $Dup = $table_data[0]['d_e_m'];
                            $Ddown = $table_data[0]['d_e_m'];
                            $TPCup = $table_data[0]['tpc'];
                            $TPCdown = $table_data[0]['tpc'];
                            $DSup = $table_data[0]['ds'];
                            $DSdown = $table_data[0]['ds'];
                            $Xfdown = $table_data[0]['lcf'];
                            $Xfup = $table_data[0]['lcf'];

                        }else if(count($table_data) == 2){
                            $Dup = $table_data[0]['d_e_m'];
                            $Ddown = $table_data[1]['d_e_m'];
                            $TPCup = $table_data[0]['tpc'];
                            $TPCdown = $table_data[1]['tpc'];
                            $DSup = $table_data[0]['ds'];
                            $DSdown = $table_data[1]['ds'];
                            $Xfdown = $table_data[0]['lcf'];
                            $Xfup = $table_data[1]['lcf'];
                        }

                        $mtc_up_data = $this->downup(round($D_M-0.5,3),$ship_msg['ds_table'],false);
                        if(count($mtc_up_data) == 1){
                            $MTCup = $mtc_up_data[0]['mct'];
                        }else if(count($mtc_up_data) == 2){
                            $up = $mtc_up_data[0]['mct'];
                            $down = $mtc_up_data[1]['mct'];
                            $MTCup = $this->getMiddleValue($up, $down, $mtc_up_data[0]['d_e_m'], $mtc_up_data[1]['d_e_m'], $D_M-0.5);
                        }

                        $mtc_down_data = $this->downup(round($D_M+0.5,3),$ship_msg['ds_table'],false);
                        if(count($mtc_down_data) == 1){
                            $MTCdown = $mtc_down_data[0]['mct'];
                        }else if(count($mtc_down_data) == 2){
                            $up = $mtc_down_data[0]['mct'];
                            $down = $mtc_down_data[1]['mct'];
                            $MTCdown = $this->getMiddleValue($up, $down, $mtc_down_data[0]['d_e_m'], $mtc_down_data[1]['d_e_m'], $D_M+0.5);
                        }

                        $data_r = array();
                        $data_r['d_up'] = $Dup;
                        $data_r['d_down'] = $Ddown;
                        $data_r['tpc_up'] = $TPCup;
                        $data_r['tpc_down'] = $TPCdown;
                        $data_r['ds_up'] = $DSup;
                        $data_r['ds_down'] = $DSdown;
                        $data_r['d_up'] = $Dup;
                        $data_r['d_up'] = $Dup;
                        $data_r['xf_up'] = $Xfup;
                        $data_r['xf_down'] = $Xfdown;
                        $data_r['mtc_up'] = $MTCup;
                        $data_r['mtc_down'] = $MTCdown;
                        $data_r['solt'] = $datas['solt'];
                        $data_r['resultid'] = $datas['resultid'];
                        $data_r['shipid'] = $result_ship_id['shipid'];



                        $this->process['table']['time'] = date('Y-m-d H:i:s', time());
                        $this->process['table']['Dup'] = $Dup;
                        $this->process['table']['Ddown'] = $Ddown;

                        $this->process['table']['TPCup'] = $TPCup;
                        $this->process['table']['TPCdown'] = $TPCdown;

                        $this->process['table']['DSdown'] = $DSdown;
                        $this->process['table']['DSup'] = $DSup;

                        $this->process['table']['Xfdown'] = $Xfdown;
                        $this->process['table']['Xfup'] = $Xfup;

                        $this->process['table']['MTCup'] = $MTCup;

                        $this->process['table']['MTCdown'] = $MTCdown;

                        $this->process['LBP'] = $LBP;
                        $this->process['table']['pt'] = $pt;


                        //开始计算
                        //开始插值计算
                        $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值

                        $TPC = round($getDS_arr['TPC'], 5);
                        $DS = round($getDS_arr['DS'], 5);
                        $Xf = round($getDS_arr['Xf'], 5);
                        $this->process['table']['TPC'] = $TPC;
                        $this->process['table']['DS'] = $DS;
                        $this->process['table']['Xf'] = $Xf;




                        $dmdz = round($MTCup - $MTCdown, 5);
                        $this->process['dmdz'] = $dmdz;

                        $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                        $this->process['Dc1'] = $Dc1;

                        $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                        $this->process['Dc2'] = $Dc2;

                        $Dc = round($Dc1 + $Dc2, 5);
                        $this->process['Dc'] = $Dc;

                        $Dsc = round($DS + $Dc, 5);
                        $this->process['Dsc'] = $Dsc;

                        $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                        $this->process['Dpc'] = $Dpc;

                        $Dspc = round($Dsc + $Dpc, 5);
                        $this->process['Dspc'] = $Dspc;

                        $data_r['tpc'] = $TPC;
                        $data_r['ds'] = $DS;
                        $data_r['xf'] = $Xf;
                        $data_r['dmdz'] = $dmdz;
                        $data_r['dc1'] = $Dc1;
                        $data_r['dc2'] = $Dc2;
                        $data_r['dc'] = $Dc;
                        $data_r['dsc'] = $Dsc;
                        $data_r['dpc'] = $Dpc;
                        $data_r['xf_up'] = $Xfup;
                        $data_r['xf_down'] = $Xfdown;
                        $data_r['process'] = json_encode($this->process);

                        $recodenums = $resultrecord->where($where_r)->count();

                        M()->startTrans();    //开启事物
                        //添加数据到数据库排水量表数据表,如果存在则修改
                        if ($recodenums > 0) {
                            $re = $resultrecord->where($where_r)->save($data_r);
                        } else {
//                            exit(jsonreturn($data_r));
                            $re = $resultrecord->add($data_r);
                        }
                        if ($re !== false) {
                            //计算总货重并修改
                            //作业前作业后区分是否计算总货重
                            switch ($datas['solt']) {
                                case '1':
                                    //作业前
                                    //修改作业前总货重、总容量
                                    $g = array(
                                        'qian_dspc' => $Dspc,
                                    );
                                    $res_r = $this
                                        ->where(array('id' => $datas['resultid']))
                                        ->save($g);
                                    if ($res_r !== false) {
                                        M()->commit();
                                        if ($r['constant'] > 0) {
                                            $weight_res = $this->suanWeight($datas['resultid'], $result_ship_id['shipid']);
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
                                        ->where(array('id' => $datas['resultid']))
                                        ->save($g);
                                    if ($r !== false) {
                                        M()->commit();

                                        if ($r['constant'] > 0) {
                                            $weight_res = $this->suanWeight($datas['resultid'], $result_ship_id['shipid']);
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
                                    M()->rollback();
                                    //其它错误  2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                    );
                                    # 不是作业前后，跳出
                                    break;
                            }

                        } else {
                            M()->rollback();
                            //其它错误 2
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                            );
                        }
                    } else {
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                            'dspc' => $dspc_res['code'],
                            'weight' => $weight_res['code'],
                        );
                    }
                } else {
                    //数据库连接错误	3
                    M()->rollback();
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                //其他错误 4
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
     * 获取
     */
    public function downup($d_m, $tablename,$need_tpc = true)
    {
        $tname = M($tablename);
        $u = $tname
            ->field('d_e_m,ds,lcf')
            ->where(array('d_e_m' => $d_m))
            ->find();
        if (!empty($u)) {
            if($need_tpc){
                $lt_tpc = $tname
                    ->field('d_e_m,ds')
                    ->where(array('d_e_m'=>array('LT', floatval($d_m))))
                    ->order('d_e_m desc')
                    ->find();
//            print_r($lt_tpc);
                $u['tpc'] = ($u['ds'] - $lt_tpc['ds'])/(($u['d_e_m']-$lt_tpc['d_e_m'])*100);

            }
            $res[] = $u;
        } else {
            //查不到数据，搜索它的上一条或者下一条数据
            //上一条数据
            $wherelt = array(
                'd_e_m' => array('LT', $d_m)
            );
            $e = $tname
                ->where($wherelt)
                ->order('d_e_m desc')
                ->find();
            if (!empty($e)) {
                if($need_tpc) {
                    $lt_tpc = $tname
                        ->field('d_e_m,ds')
                        ->where(array('d_e_m' => array('LT', floatval($e['d_e_m']))))
                        ->order('d_e_m desc')
                        ->find();
//                print_r($lt_tpc);
                    $e['tpc'] = ($e['ds'] - $lt_tpc['ds']) / (($e['d_e_m'] - $lt_tpc['d_e_m']) * 100);
                }
                $res[] = $e;
            }
            //下一条数据
            $wheregt = array(
                'd_e_m' => array('GT', $d_m)
            );
            $f = $tname
                ->where($wheregt)
                ->order('d_e_m asc')
                ->find();
            if (!empty($f)) {
                if($need_tpc) {
                    $lt_tpc = $tname
                        ->field('d_e_m,ds')
                        ->where(array('d_e_m' => array('LT', floatval($f['d_e_m']))))
                        ->order('d_e_m desc')
                        ->find();
//                print_r($lt_tpc);
                    $f['tpc'] = ($f['ds'] - $lt_tpc['ds']) / (($f['d_e_m'] - $lt_tpc['d_e_m']) * 100);
                }
                $res[] = $f;
            }
        }
        return $res;
    }

    /**
     * 添加补充船舶重量常数
     * @param $datas
     * @return array
     */
    public function add_constant($datas)
    {

        if ($datas['solt'] == '1') {
            $this->process = json_decode($this->getFieldById($datas['resultid'], "qianprocess"), true);
        } elseif ($datas['solt'] == '2') {
            $this->process = json_decode($this->getFieldById($datas['resultid'], "houprocess"), true);
        } else {

            //其他错误 4
            return array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );
        }

        if ($this->process == null) {
            $this->process = array();
        }

        if ($datas['solt'] == '1') {
            //存储常量
            $data1['qian_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
            $data1['qian_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
            $data1['qian_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
            $data1['qian_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
            $result_field = "qian_dspc as dspc,qian_constant as constant,shipid";

            //存储计算过程变量
            $this->process['fwater_weight'] = $data1['qian_fwater_weight'];
            $this->process['sewage_weight'] = $data1['qian_sewage_weight'];
            $this->process['fuel_weight'] = $data1['qian_fuel_weight'];
            $this->process['other_weight'] = $data1['qian_other_weight'];
            $data1['qianprocess'] = json_encode($this->process);

        } elseif ($datas['solt'] == '2') {

            //存储常量
            $data1['hou_fwater_weight'] = round((float)$datas['fwater_weight'], 5);//存储淡水量
            $data1['hou_sewage_weight'] = round((float)$datas['sewage_weight'], 5);//存储污水量
            $data1['hou_fuel_weight'] = round((float)$datas['fuel_weight'], 5);//存储燃油量
            $data1['hou_other_weight'] = round((float)$datas['other_weight'], 5);//存储其他货物重量
            $result_field = "hou_dspc as dspc,hou_constant as constant,shipid";

            //存储计算过程变量
            $this->process['fwater_weight'] = $data1['qian_fwater_weight'];
            $this->process['sewage_weight'] = $data1['qian_sewage_weight'];
            $this->process['fuel_weight'] = $data1['qian_fuel_weight'];
            $this->process['other_weight'] = $data1['qian_other_weight'];
            //将过程存入数据库
            $data1['houprocess'] = json_encode($this->process);
        } else {
            //其他错误 4
            return array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
            );
        }
        M()->startTrans();
        // 添加/修改数据
        $m = array(
            'id' => $datas['resultid']
        );
        $res = $this->editData($m, $data1);
        // 修改数据成功
        if ($res['code'] !== false) {
            #todo 如果存在已有的作业数据，则重新计算已录入的数据
            M()->commit();
            $r_data = $this->field($result_field)->where($m)->find();

            if ($r_data['dspc'] > 0) {
                $dspc_res = $this->suanDspc1($datas['resultid'], $datas['solt']);
            } else {
                $dspc_res['code'] = 1;
            }

            if ($r_data['constant'] > 0) {
                $weight_res = $this->suanWeight($datas['resultid'], $r_data['shipid']);
            } else {
                $weight_res['code'] = 1;
            }

            if ($dspc_res['code'] == 1 and $weight_res['code'] == 1) {
                //成功 1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                );
            } else {
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['RE_RECKON_FALL'],
                    'dspc' => $dspc_res['code'],
                    'weight' => $weight_res['code'],
                );
            }
        } else {
            //数据库连接错误	3
            M()->rollback();
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
            );
        }
        return $res;
    }


    /**
     * 计算器计算容量
     * 表载水密度使用船舶信息内的
     * */
    public function reckon2($data)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($data['uid'], $data['imei']);
        if ($msg1['code'] == '1') {
            $resultrecord = M('sh_resultrecord');
            //检索条件构成
            $where_r = array(
                'resultid' => $data['resultid'],
                'solt' => $data['solt'],
            );

            //获取舱计算过程
            $process = $resultrecord->field("process")->where($where_r)->find();

            //过程转换数组
            if ($process !== false) {
                $this->process = json_decode($process['process'], true);
                if ($this->process === null) {
                    $this->process = array(
                        'table' => array()
                    );
                }
            } else {
                $this->process = array(
                    'table' => array()
                );
            }

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
                $Dup = (float)$data['d_up'];
                $Ddown = (float)$data['d_down'];
                $TPCup = (float)$data['tpc_up'];
                $TPCdown = (float)$data['tpc_down'];
                $DSup = (float)$data['ds_up'];
                $DSdown = (float)$data['ds_down'];

                if (isset($data['lca_up'])) {
                    $Xfup = ($LBP / 2) - (float)$data['lca_up'];
                    $Xfdown = ($LBP / 2) - (float)$data['lca_down'];
                } elseif (isset($data['xf_up'])) {
                    $Xfup = (float)$data['xf_up'];
                    $Xfdown = (float)$data['xf_down'];
                } elseif (isset($data['lcb_up'])) {
                    $Xfup = (float)$data['lcb_up'] - ($LBP / 2);
                    $Xfdown = (float)$data['lcb_down'] - ($LBP / 2);
                } else {
                    $Xfup = (float)$data['xf_up'];
                    $Xfdown = (float)$data['xf_down'];
                }

                $MTCup = (float)$data['mtc_up'];
                $MTCdown = (float)$data['mtc_down'];


                $cumulative_data = array(
                    'd_up' => $data['d_up'],
                    'd_down' => $data['d_down'],
                    'tpc_up' => $data['tpc_up'],
                    'tpc_down' => $data['tpc_down'],
                    'ds_down' => $data['ds_down'],
                    'ds_up' => $data['ds_up'],
                    'xf_down' => $Xfdown,
                    'xf_up' => $Xfup,
                );
                $this->process['table']['time'] = date('Y-m-d H:i:s', time());
                $this->process['table']['Dup'] = $data['d_up'];
                $this->process['table']['Ddown'] = $data['d_down'];

                $this->process['table']['TPCup'] = $data['tpc_up'];
                $this->process['table']['TPCdown'] = $data['tpc_down'];

                $this->process['table']['DSdown'] = $data['ds_down'];
                $this->process['table']['DSup'] = $data['ds_up'];

                $this->process['table']['LCAdown'] = $data['lca_down'];
                $this->process['table']['LCAup'] = $data['lca_up'];

                $this->process['table']['Xfdown'] = $Xfdown;
                $this->process['table']['Xfup'] = $Xfup;

                $this->process['table']['MTCup'] = $data['mtc_up'];
                $this->process['table']['MTCdown'] = $data['mtc_down'];

                $this->process['LBP'] = $LBP;
                $this->process['table']['pt'] = $pt;

//                $pt = (float)$data['ptwd'];

                //开始计算
                //开始插值计算
                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
                $TPC = round($getDS_arr['TPC'], 5);
                $DS = round($getDS_arr['DS'], 5);
                $Xf = round($getDS_arr['Xf'], 5);
                $this->process['table']['TPC'] = $TPC;
                $this->process['table']['DS'] = $DS;
                $this->process['table']['Xf'] = $Xf;


                /*                $TPC = 59.5;
                                $DS = 55118.7;
                                $Xf = 2.85;*/

                $dmdz = round($MTCup - $MTCdown, 5);
//                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                $this->process['dmdz'] = $dmdz;

                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
//                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;
                $this->process['Dc1'] = $Dc1;

                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
//                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;
                $this->process['Dc2'] = $Dc2;

                $Dc = round($Dc1 + $Dc2, 5);
//                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;
                $this->process['Dc'] = $Dc;

                $Dsc = round($DS + $Dc, 5);
//                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;
                $this->process['Dsc'] = $Dsc;

                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
//                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;
                $this->process['Dpc'] = $Dpc;

                $Dspc = round($Dsc + $Dpc, 5);
//                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;
                $this->process['Dspc'] = $Dspc;

                $data['tpc'] = $TPC;
                $data['ds'] = $DS;
                $data['xf'] = $Xf;
                $data['dmdz'] = $dmdz;
                $data['dc1'] = $Dc1;
                $data['dc2'] = $Dc2;
                $data['dc'] = $Dc;
                $data['dsc'] = $Dsc;
                $data['dpc'] = $Dpc;
                $data['xf_up'] = $Xfup;
                $data['xf_down'] = $Xfdown;
                $data['process'] = json_encode($this->process);


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
                                $this->adjust_cumulative_hydrostatic_data(intval($data['resultid']), intval($data['solt']), intval($data['shipid']), $cumulative_data);
                                if ($r['constant'] > 0) {
                                    $weight_res = $this->suanWeight($data['resultid'], $data['shipid']);
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
                                    $weight_res = $this->suanWeight($data['resultid'], $data['shipid']);
                                } else {
                                    $weight_res['code'] = 1;
                                }
                                $this->adjust_cumulative_hydrostatic_data(intval($data['resultid']), intval($data['solt']), intval($data['shipid']), $cumulative_data);

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
     * 计算器计算容量(新版，视图省略tpc的值)
     * 表载水密度使用船舶信息内的
     * */
    public function reckon3($data)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($data['uid'], $data['imei']);
        if ($msg1['code'] == '1') {
            $resultrecord = M('sh_resultrecord');
            //检索条件构成
            $where_r = array(
                'resultid' => $data['resultid'],
                'solt' => $data['solt'],
            );

            //获取舱计算过程
            $process = $resultrecord->field("process")->where($where_r)->find();

            //过程转换数组
            if ($process !== false) {
                $this->process = json_decode($process['process'], true);
                if ($this->process === null) {
                    $this->process = array(
                        'table' => array()
                    );
                }
            } else {
                $this->process = array(
                    'table' => array()
                );
            }

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
                $Dup = (float)$data['d_up'];
                $Ddown = (float)$data['d_down'];
//                $TPCup = (float)$data['tpc_up'];
//                $TPCdown = (float)$data['tpc_down'];
                $DSup = (float)$data['ds_up'];
                $DSdown = (float)$data['ds_down'];

                if (isset($data['lca_up'])) {
                    $Xfup = ($LBP / 2) - (float)$data['lca_up'];
                    $Xfdown = ($LBP / 2) - (float)$data['lca_down'];
                } elseif (isset($data['xf_up'])) {
                    $Xfup = (float)$data['xf_up'];
                    $Xfdown = (float)$data['xf_down'];
                } elseif (isset($data['lcb_up'])) {
                    $Xfup = (float)$data['lcb_up'] - ($LBP / 2);
                    $Xfdown = (float)$data['lcb_down'] - ($LBP / 2);
                } else {
                    $Xfup = (float)$data['xf_up'];
                    $Xfdown = (float)$data['xf_down'];
                }

                $MTCup = (float)$data['mtc_up'];
                $MTCdown = (float)$data['mtc_down'];


                $cumulative_data = array(
                    'd_up' => $data['d_up'],
                    'd_down' => $data['d_down'],
                    'tpc_up' => 0,
                    'tpc_down' => 0,
                    'ds_down' => $data['ds_down'],
                    'ds_up' => $data['ds_up'],
                    'xf_down' => $Xfdown,
                    'xf_up' => $Xfup,
                );
                $this->process['table']['time'] = date('Y-m-d H:i:s', time());
                $this->process['table']['Dup'] = $data['d_up'];
                $this->process['table']['Ddown'] = $data['d_down'];

//                $this->process['table']['TPCup'] = $data['tpc_up'];
//                $this->process['table']['TPCdown'] = $data['tpc_down'];

                $this->process['table']['DSdown'] = $data['ds_down'];
                $this->process['table']['DSup'] = $data['ds_up'];

                $this->process['table']['LCAdown'] = $data['lca_down'];
                $this->process['table']['LCAup'] = $data['lca_up'];

                $this->process['table']['Xfdown'] = $Xfdown;
                $this->process['table']['Xfup'] = $Xfup;

                $this->process['table']['MTCup'] = $data['mtc_up'];
                $this->process['table']['MTCdown'] = $data['mtc_down'];

                $this->process['LBP'] = $LBP;
                $this->process['table']['pt'] = $pt;

//                $pt = (float)$data['ptwd'];

                //开始计算
                //开始插值计算
                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, 0, 0, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值

                $TPC = round($getDS_arr['TPC'], 5);
                $DS = round($getDS_arr['DS'], 5);
                $Xf = round($getDS_arr['Xf'], 5);
                $this->process['table']['TPC'] = $TPC;
                $this->process['table']['DS'] = $DS;
                $this->process['table']['Xf'] = $Xf;


                /*                $TPC = 59.5;
                                $DS = 55118.7;
                                $Xf = 2.85;*/

                $dmdz = round($MTCup - $MTCdown, 5);
//                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                $this->process['dmdz'] = $dmdz;

                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
//                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;
                $this->process['Dc1'] = $Dc1;

                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
//                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;
                $this->process['Dc2'] = $Dc2;

                $Dc = round($Dc1 + $Dc2, 5);
//                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;
                $this->process['Dc'] = $Dc;

                $Dsc = round($DS + $Dc, 5);
//                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;
                $this->process['Dsc'] = $Dsc;

                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
//                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;
                $this->process['Dpc'] = $Dpc;

                $Dspc = round($Dsc + $Dpc, 5);
//                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;
                $this->process['Dspc'] = $Dspc;

                $data['tpc'] = $TPC;
                $data['ds'] = $DS;
                $data['xf'] = $Xf;
                $data['dmdz'] = $dmdz;
                $data['dc1'] = $Dc1;
                $data['dc2'] = $Dc2;
                $data['dc'] = $Dc;
                $data['dsc'] = $Dsc;
                $data['dpc'] = $Dpc;
                $data['xf_up'] = $Xfup;
                $data['xf_down'] = $Xfdown;
                $data['process'] = json_encode($this->process);


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
                                $this->adjust_cumulative_hydrostatic_data(intval($data['resultid']), intval($data['solt']), intval($data['shipid']), $cumulative_data);
                                if ($r['constant'] > 0) {
                                    $weight_res = $this->suanWeight($data['resultid'], $data['shipid']);
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
                                    $weight_res = $this->suanWeight($data['resultid'], $data['shipid']);
                                } else {
                                    $weight_res['code'] = 1;
                                }
                                $this->adjust_cumulative_hydrostatic_data(intval($data['resultid']), intval($data['solt']), intval($data['shipid']), $cumulative_data);

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
                            M()->rollback();
                            //其它错误  2
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                            );
                            # 不是作业前后，跳出
                            break;
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
//    public function suanDspc($resultid, $solt)
//    {
//        $this->process = "";
//        #todo 检测计算中需要用到的数据是否缺失，缺失则返回步骤错位
//        // 将录入数据更新到表中
//        $resultrecord = M('sh_resultrecord');
//
//
//        $field_str = "";
//
//        $where_data = array(
//            'resultid' => $resultid,
//            'solt' => 1,
//        );
//
//        $record_num = $resultrecord->where($where_data)->count();
//        if ($record_num > 0) {
//
//            $data = $resultrecord->where($where_data)->find();
//            $field_str = "r.qian_tc as tc,r.qian_d_m as d_m,r.qian_pwd as pwd,s.lbp,s.ptwd";
//            $where = array(
//                'r.id' => $resultid,
//            );
//
//            $r = $this
//                ->alias('r')
//                ->field($field_str)
//                ->join('left join sh_ship s on s.id=r.shipid')
//                ->where($where)
//                ->find();
//
//            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
//                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
//                // 	// 空高有误 2009
//                //     $res = array(
//                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
//                //     );
//                // } else {
//
//                /**
//                 * 开始计算排水量
//                 */
//
//                $TC = $r['tc'];
//                $D_M = $r['d_m'];
//                $p = $r['pwd'];
//                $LBP = $r['lbp'];
////                $pt = $r['ptwd'];
//
//                $Dup = (float)$data['d_up'];
//                $Ddown = (float)$data['d_down'];
//                $TPCup = (float)$data['tpc_up'];
//                $TPCdown = (float)$data['tpc_down'];
//                $DSup = (float)$data['ds_up'];
//                $DSdown = (float)$data['ds_down'];
//                $Xfup = (float)$data['xf_up'];
//                $Xfdown = (float)$data['xf_down'];
//                $MTCup = (float)$data['mtc_up'];
//                $MTCdown = (float)$data['mtc_down'];
//                $pt = (float)$data['ptwd'];
//
//                /**
//                 * 整理数据
//                 *
//                 * */
//                $this->process .= "Received table:\r\n\t"
//                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
//                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
//                    . "\r\n\tMTCup=" . $data['mtc_up'] . ",MTCdown=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
//                    . $pt . "\r\n";
//
//
//                //开始计算
//                //开始插值计算
//                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
//                $TPC = round($getDS_arr['TPC'], 5);
//                $DS = round($getDS_arr['DS'], 5);
//                $Xf = round($getDS_arr['Xf'], 5);
//
//                /*                $TPC = 59.5;
//                                $DS = 55118.7;
//                                $Xf = 2.85;*/
//
//                $dmdz = round($MTCup - $MTCdown, 5);
//                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
//                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
//                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;
//
//                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
//                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;
//
//                $Dc = round($Dc1 + $Dc2, 5);
//                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;
//
//                $Dsc = round($DS + $Dc, 5);
//                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;
//
//                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
//                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;
//
//                $Dspc = round($Dsc + $Dpc, 5);
//                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;
//
//
//                $data['tpc'] = $TPC;
//                $data['ds'] = $DS;
//                $data['xf'] = $Xf;
//                $data['dmdz'] = $dmdz;
//                $data['dc1'] = $Dc1;
//                $data['dc2'] = $Dc2;
//                $data['dc'] = $Dc;
//                $data['dsc'] = $Dsc;
//                $data['dpc'] = $Dpc;
//                $data['process'] = urlencode($this->process);
//
//                $where_r = array(
//                    'resultid' => $resultid,
//                    'solt' => 1,
//                );
//
//                $recodenums = $resultrecord->where($where_r)->count();
//
//
//                M()->startTrans();    //开启事物
//                //添加数据到数据库排水量表数据表,如果存在则修改
//                if ($recodenums > 0) {
//                    $re = $resultrecord->where($where_r)->save($data);
//                } else {
//                    $re = $resultrecord->add($data);
//                }
//                if ($re !== false) {
//
//                    //计算总货重并修改
//                    //作业前作业后区分是否计算总货重
//
//                    //作业前
//                    //修改作业前总货重、总容量
//                    $this->process = "";
//                    $g = array(
//                        'qian_dspc' => $Dspc,
//                    );
//                    $r = $this
//                        ->where(array('id' => $resultid))
//                        ->save($g);
//                    if ($r !== false) {
//                        $where_data = array(
//                            'resultid' => $resultid,
//                            'solt' => 2,
//                        );
//                        $hou_count = $resultrecord->where($where_data)->count();
//                        $data = $resultrecord->where($where_data)->find();
//                        if ($hou_count > 0) {
//                            $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,s.lbp,s.ptwd";
//                            $where = array(
//                                'r.id' => $resultid,
//                            );
//
//                            $r = $this
//                                ->alias('r')
//                                ->field($field_str)
//                                ->join('left join sh_ship s on s.id=r.shipid')
//                                ->where($where)
//                                ->find();
//
//                            if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
//                                // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
//                                // 	// 空高有误 2009
//                                //     $res = array(
//                                //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
//                                //     );
//                                // } else {
//
//                                /**
//                                 * 开始计算排水量
//                                 */
//
//                                $TC = $r['tc'];
//                                $D_M = $r['d_m'];
//                                $p = $r['pwd'];
//                                $LBP = $r['lbp'];
////                                $pt = $r['ptwd'];
//
//
//                                $Dup = (float)$data['d_up'];
//                                $Ddown = (float)$data['d_down'];
//                                $TPCup = (float)$data['tpc_up'];
//                                $TPCdown = (float)$data['tpc_down'];
//                                $DSup = (float)$data['ds_up'];
//                                $DSdown = (float)$data['ds_down'];
//                                $Xfup = (float)$data['xf_up'];
//                                $Xfdown = (float)$data['xf_down'];
//                                $MTCup = (float)$data['mtc_up'];
//                                $MTCdown = (float)$data['mtc_down'];
//                                $pt = (float)$data['ptwd'];
//
//                                /**
//                                 * 整理数据
//                                 *
//                                 * */
//                                $this->process .= "Received table:\r\n\t"
//                                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
//                                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
//                                    . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
//                                    . $pt . "\r\n";
//
//
//                                //开始计算
//                                //开始插值计算
//                                $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
//                                $TPC = round($getDS_arr['TPC'], 5);
//                                $DS = round($getDS_arr['DS'], 5);
//                                $Xf = round($getDS_arr['Xf'], 5);
//
//                                /*                $TPC = 59.5;
//                                                $DS = 55118.7;
//                                                $Xf = 2.85;*/
//
//                                $dmdz = round($MTCup - $MTCdown, 5);
//                                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
//                                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
//                                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;
//
//                                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
//                                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;
//
//                                $Dc = round($Dc1 + $Dc2, 5);
//                                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;
//
//                                $Dsc = round($DS + $Dc, 5);
//                                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;
//
//                                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
//                                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;
//
//                                $Dspc = round($Dsc + $Dpc, 5);
//                                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;
//
//
//                                $data['tpc'] = $TPC;
//                                $data['ds'] = $DS;
//                                $data['xf'] = $Xf;
//                                $data['dmdz'] = $dmdz;
//                                $data['dc1'] = $Dc1;
//                                $data['dc2'] = $Dc2;
//                                $data['dc'] = $Dc;
//                                $data['dsc'] = $Dsc;
//                                $data['dpc'] = $Dpc;
//                                $data['process'] = urlencode($this->process);
//
//                                $where_r = array(
//                                    'resultid' => $resultid,
//                                    'solt' => 2,
//                                );
//
//                                $recodenums = $resultrecord->where($where_r)->count();
//
//
//                                M()->startTrans();    //开启事物
//                                //添加数据到数据库排水量表数据表,如果存在则修改
//                                if ($recodenums > 0) {
//                                    $re = $resultrecord->where($where_r)->save($data);
//                                } else {
//                                    $re = $resultrecord->add($data);
//                                }
//                                if ($re !== false) {
//
//                                    //计算总货重并修改
//                                    //作业前作业后区分是否计算总货重
//
//                                    //作业前
//                                    //修改作业前总货重、总容量
//                                    $g = array(
//                                        'hou_dspc' => $Dspc,
//                                    );
//                                    $r = $this
//                                        ->where(array('id' => $resultid))
//                                        ->save($g);
//                                    if ($r !== false) {
//                                        M()->commit();
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                        );
//                                    } else {
//                                        M()->rollback();
//                                        // $trans->rollback();
//                                        //其它错误  2
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                        );
//                                    }
//
//                                } else {
//                                    M()->rollback();
//                                    //其它错误 2
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                                    );
//                                }
//                                // }
//                            } else {
//                                if ($solt == 1) {
//                                    M()->commit();
//                                    //作业前的时候没有作业后的数据不应该报错
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
//                                    );
//                                } else {
//                                    M()->rollback();
//                                    //其它错误 2
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                                    );
//                                }
//                            }
//                        } else {
//                            if ($solt == 1) {
//                                M()->commit();
//                                //作业前的时候没有作业后的数据不应该报错
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS']
//                                );
//                            } else {
//                                M()->rollback();
//                                //其它错误 2
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                                );
//                            }
//                        }
//                    } else {
//                        M()->rollback();
//                        // $trans->rollback();
//                        //其它错误  2
//                        $res = array(
//                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                        );
//                    }
//
//                } else {
//                    M()->rollback();
//                    //其它错误 2
//                    $res = array(
//                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                    );
//                }
//                // }
//
//            } else {
//                //其它错误 2
//                $res = array(
//                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                );
//            }
//        } else {
//            if ($solt == "2") {
//                M()->startTrans();    //开启事物
//                $where_data = array(
//                    'resultid' => $resultid,
//                    'solt' => 2,
//                );
//                $hou_count = $resultrecord->where($where_data)->count();
//                $data = $resultrecord->where($where_data)->find();
//                if ($hou_count > 0) {
//                    $field_str = "r.hou_tc as tc,r.hou_d_m as d_m,r.hou_pwd as pwd,s.lbp,s.ptwd";
//                    $where = array(
//                        'r.id' => $resultid,
//                    );
//
//                    $r = $this
//                        ->alias('r')
//                        ->field($field_str)
//                        ->join('left join sh_ship s on s.id=r.shipid')
//                        ->where($where)
//                        ->find();
//
//                    if ($r['tc'] != null and $r['d_m'] != null and $r['pwd'] != null and $r['lbp'] != null) {
//                        // if ($r['ullage']>$data['ullage2'] or $r['ullage']<$data['ullage1']) {
//                        // 	// 空高有误 2009
//                        //     $res = array(
//                        //         'code'  =>  $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
//                        //     );
//                        // } else {
//
//                        /**
//                         * 开始计算排水量
//                         */
//
//                        $TC = $r['tc'];
//                        $D_M = $r['d_m'];
//                        $p = $r['pwd'];
//                        $LBP = $r['lbp'];
////                        $pt = $r['ptwd'];
//
//
//                        $Dup = (float)$data['d_up'];
//                        $Ddown = (float)$data['d_down'];
//                        $TPCup = (float)$data['tpc_up'];
//                        $TPCdown = (float)$data['tpc_down'];
//                        $DSup = (float)$data['ds_up'];
//                        $DSdown = (float)$data['ds_down'];
//                        $Xfup = (float)$data['xf_up'];
//                        $Xfdown = (float)$data['xf_down'];
//                        $MTCup = (float)$data['mtc_up'];
//                        $MTCdown = (float)$data['mtc_down'];
//                        $pt = (float)$data['ptwd'];
//
//                        /**
//                         * 整理数据
//                         *
//                         * */
//                        $this->process .= "Received table:\r\n\t"
//                            . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
//                            . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
//                            . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
//                            . $pt . "\r\n";
//
//
//                        //开始计算
//                        //开始插值计算
//                        $getDS_arr = $this->getDS((float)$D_M, (float)$Dup, (float)$Ddown, (float)$TPCup, (float)$TPCdown, (float)$DSup, (float)$DSdown, (float)$Xfup, (float)$Xfdown);//计算排水量插值
//                        $TPC = round($getDS_arr['TPC'], 5);
//                        $DS = round($getDS_arr['DS'], 5);
//                        $Xf = round($getDS_arr['Xf'], 5);
//
//                        /*                $TPC = 59.5;
//                                        $DS = 55118.7;
//                                        $Xf = 2.85;*/
//
//                        $dmdz = round($MTCup - $MTCdown, 5);
//                        $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
//                        $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
//                        $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;
//
//                        $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
//                        $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;
//
//                        $Dc = round($Dc1 + $Dc2, 5);
//                        $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;
//
//                        $Dsc = round($DS + $Dc, 5);
//                        $this->process .= "Dsc = $DS + $Dc = " . $Dsc;
//
//                        $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
//                        $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;
//
//                        $Dspc = round($Dsc + $Dpc, 5);
//                        $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;
//
//
//                        $data['tpc'] = $TPC;
//                        $data['ds'] = $DS;
//                        $data['xf'] = $Xf;
//                        $data['dmdz'] = $dmdz;
//                        $data['dc1'] = $Dc1;
//                        $data['dc2'] = $Dc2;
//                        $data['dc'] = $Dc;
//                        $data['dsc'] = $Dsc;
//                        $data['dpc'] = $Dpc;
//                        $data['process'] = urlencode($this->process);
//
//                        $where_r = array(
//                            'resultid' => $resultid,
//                            'solt' => 2,
//                        );
//
//                        $recodenums = $resultrecord->where($where_r)->count();
//
//
//                        M()->startTrans();    //开启事物
//                        //添加数据到数据库排水量表数据表,如果存在则修改
//                        if ($recodenums > 0) {
//                            $re = $resultrecord->where($where_r)->save($data);
//                        } else {
//                            $re = $resultrecord->add($data);
//                        }
//                        if ($re !== false) {
//
//                            //计算总货重并修改
//                            //作业前作业后区分是否计算总货重
//
//                            //作业前
//                            //修改作业前总货重、总容量
//                            $g = array(
//                                'hou_dspc' => $Dspc,
//                            );
//                            $r = $this
//                                ->where(array('id' => $resultid))
//                                ->save($g);
//                            if ($r !== false) {
//                                M()->commit();
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                );
//                            } else {
//                                M()->rollback();
//                                // $trans->rollback();
//                                //其它错误  2
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                );
//                            }
//
//                        } else {
//                            M()->rollback();
//                            //其它错误 2
//                            $res = array(
//                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                            );
//                        }
//                        // }
//                    } else {
//                        if ($solt == 1) {
//                            M()->commit();
//                            //作业前的时候没有作业后的数据不应该报错
//                            $res = array(
//                                'code' => $this->ERROR_CODE_COMMON['SUCCESS']
//                            );
//                        } else {
//                            M()->rollback();
//                            //其它错误 2
//                            $res = array(
//                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                            );
//                        }
//                    }
//                } else {
//                    M()->rollback();
//                    #todo 存在争议，本方法只有一个录入/修改水尺的方法调用，调用时会判断是否压载水计算过
//                    #todo 如果进入本方法说明计算过，没有找到数据确实应该报错，但是不清楚会不会出现
//                    #todo 系统判断失误的情况，导致进入此方法后无法找到数据。目前决定报错
//                    //其他错误
//                    $res = array(
//                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                    );
//                }
//            } else {
//                //作业前的时候重新计算没有作业前的数据说明有问题，应该报错
//                $res = array(
//                    'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
//                );
//            }
//        }
//        return $res;
//    }


    /**
     * 重新计算排水量
     * @param $resultid
     * @param $solt
     */
    public function suanDspc1($resultid, $solt)
    {
        $this->process = array();
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
            //过程转换数组
            if ($data !== false) {
                $this->process = json_decode($data['process'], true);
                if ($this->process === null) {
                    $this->process = array(
                        'table' => array()
                    );
                }
            } else {
                $this->process = array(
                    'table' => array()
                );
            }

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
                /*$this->process .= "Received table:\r\n\t"
                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
                    . "\r\n\tMTCup=" . $data['mtc_up'] . ",MTCdown=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
                    . $pt . "\r\n";*/

                $this->process['table']['Dup'] = $data['d_up'];
                $this->process['table']['Ddown'] = $data['d_down'];

                $this->process['table']['TPCup'] = $data['tpc_up'];
                $this->process['table']['TPCdown'] = $data['tpc_down'];

                $this->process['table']['DSdown'] = $data['ds_down'];
                $this->process['table']['DSup'] = $data['ds_up'];

                $this->process['table']['Xfdown'] = $data['xf_down'];
                $this->process['table']['Xfup'] = $data['xf_up'];

                $this->process['table']['MTCup'] = $data['mtc_up'];
                $this->process['table']['MTCdown'] = $data['mtc_down'];

                $this->process['LBP'] = $LBP;
                $this->process['table']['pt'] = $pt;


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
//                $this->process .= "dmdz = $MTCup - $MTCdown = " . $dmdz;
                $this->process['dmdz'] = $dmdz;

                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
//                $this->process .= "Dc1 = 100 * TC * Xf * TPC / LBP = 100 * $TC * $Xf * $TPC / $LBP = " . $Dc1;
                $this->process['Dc1'] = $Dc1;

                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
//                $this->process .= "Dc2 = 50 * dmdz * pow(TC, 2) / LBP = 50 * $dmdz * pow($TC, 2) / $LBP = " . $Dc2;
                $this->process['Dc2'] = $Dc2;

                $Dc = round($Dc1 + $Dc2, 5);
//                $this->process .= "Dc = $Dc1 + $Dc2 = " . $Dc;
                $this->process['Dc'] = $Dc;

                $Dsc = round($DS + $Dc, 5);
//                $this->process .= "Dsc = $DS + $Dc = " . $Dsc;
                $this->process['Dsc'] = $Dsc;

                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
//                $this->process .= "Dpc =  Dsc * (p - pt) / pt = $Dsc * ($p - $pt) / $pt = " . $Dpc;
                $this->process['Dpc'] = $Dpc;

                $Dspc = round($Dsc + $Dpc, 5);
//                $this->process .= "Dspc = $Dsc + $Dpc = " . $Dspc;
                $this->process['Dspc'] = $Dspc;


                $data['tpc'] = $TPC;
                $data['ds'] = $DS;
                $data['xf'] = $Xf;
                $data['dmdz'] = $dmdz;
                $data['dc1'] = $Dc1;
                $data['dc2'] = $Dc2;
                $data['dc'] = $Dc;
                $data['dsc'] = $Dsc;
                $data['dpc'] = $Dpc;
                $data['process'] = json_encode($this->process);

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
                    $this->process = array();

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
                        if ($hou_count > 0) {
                            $data = $resultrecord->where($where_data)->find();
                            //过程转换数组
                            if ($data !== false) {
                                $this->process = json_decode($data['process'], true);
                                if ($this->process === null) {
                                    $this->process = array();
                                }
                            } else {
                                $this->process = array();
                            }
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
//                                $this->process .= "Received table:\r\n\t"
//                                    . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
//                                    . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
//                                    . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
//                                    . $pt . "\r\n";
                                $this->process['table'] = array();

                                $this->process['table']['Dup'] = $data['d_up'];
                                $this->process['table']['Ddown'] = $data['d_down'];

                                $this->process['table']['TPCup'] = $data['tpc_up'];
                                $this->process['table']['TPCdown'] = $data['tpc_down'];

                                $this->process['table']['DSdown'] = $data['ds_down'];
                                $this->process['table']['DSup'] = $data['ds_up'];

                                $this->process['table']['Xfdown'] = $data['xf_down'];
                                $this->process['table']['Xfup'] = $data['xf_up'];

                                $this->process['table']['MTCup'] = $data['mtc_up'];
                                $this->process['table']['MTCdown'] = $data['mtc_down'];

                                $this->process['LBP'] = $LBP;
                                $this->process['table']['pt'] = $pt;

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


                                $dmdz = round($MTCup - $MTCdown, 5);
                                $this->process['dmdz'] = $dmdz;

                                $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                                $this->process['Dc1'] = $Dc1;

                                $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                                $this->process['Dc2'] = $Dc2;

                                $Dc = round($Dc1 + $Dc2, 5);
                                $this->process['Dc'] = $Dc;

                                $Dsc = round($DS + $Dc, 5);
                                $this->process['Dsc'] = $Dsc;

                                $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                                $this->process['Dpc'] = $Dpc;

                                $Dspc = round($Dsc + $Dpc, 5);
                                $this->process['Dspc'] = $Dspc;


                                $data['tpc'] = $TPC;
                                $data['ds'] = $DS;
                                $data['xf'] = $Xf;
                                $data['dmdz'] = $dmdz;
                                $data['dc1'] = $Dc1;
                                $data['dc2'] = $Dc2;
                                $data['dc'] = $Dc;
                                $data['dsc'] = $Dsc;
                                $data['dpc'] = $Dpc;
                                $data['process'] = json_encode($this->process);

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
//                        $this->process .= "Received table:\r\n\t"
//                            . "Dup=" . $data['d_up'] . ", TPCup=" . $data['tpc_up'] . ",DSup=" . $data['ds_up'] . ",Xfup=" . $data['xf_up'] . "\r\n\t"
//                            . "Ddown=" . $data['d_down'] . ", TPCdown=" . $data['tpc_down'] . ",DSdown=" . $data['ds_down'] . ",Xfdown=" . $data['xf_down']
//                            . "\r\n\tMTCbig=" . $data['mtc_up'] . ",MTCsmall=" . $data['mtc_down'] . "\r\n---------------------------------------\r\n pt="
//                            . $pt . "\r\n";
                        $this->process['table'] = array();

                        $this->process['table']['Dup'] = $data['d_up'];
                        $this->process['table']['Ddown'] = $data['d_down'];

                        $this->process['table']['TPCup'] = $data['tpc_up'];
                        $this->process['table']['TPCdown'] = $data['tpc_down'];

                        $this->process['table']['DSdown'] = $data['ds_down'];
                        $this->process['table']['DSup'] = $data['ds_up'];

                        $this->process['table']['Xfdown'] = $data['xf_down'];
                        $this->process['table']['Xfup'] = $data['xf_up'];

                        $this->process['table']['MTCup'] = $data['mtc_up'];
                        $this->process['table']['MTCdown'] = $data['mtc_down'];

                        $this->process['LBP'] = $LBP;
                        $this->process['table']['pt'] = $pt;

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
                        $this->process['dmdz'] = $dmdz;

                        $Dc1 = round(100 * $TC * $Xf * $TPC / $LBP, 5);
                        $this->process['Dc1'] = $Dc1;

                        $Dc2 = round(50 * $dmdz * pow($TC, 2) / $LBP, 5);
                        $this->process['Dc2'] = $Dc2;

                        $Dc = round($Dc1 + $Dc2, 5);
                        $this->process['Dc'] = $Dc;

                        $Dsc = round($DS + $Dc, 5);
                        $this->process['Dsc'] = $Dsc;

                        $Dpc = round($Dsc * ($p - $pt) / $pt, 5);
                        $this->process['Dpc'] = $Dpc;

                        $Dspc = round($Dsc + $Dpc, 5);
                        $this->process['Dspc'] = $Dspc;


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

                    /**
                     * #todo
                     *      存在争议，本方法只有一个录入/修改水尺的方法调用，调用时会判断是否压载水计算过
                     *  如果进入本方法说明计算过，没有找到数据确实应该报错，但是不清楚会不会出现
                     *  系统判断失误的情况，导致进入此方法后无法找到数据。目前决定报错
                     *
                     */

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
     * 重新计算重量
     * @param $resultid
     * @param $shipid
     * @return array
     */
    public function suanWeight($resultid, $shipid)
    {
        //作业前计算总重量
        $sh_ship = new \Common\Model\ShShipModel();
        $ship_weight = $sh_ship->getFieldById($shipid, 'weight');//获取船舶自重

        M()->startTrans();

        //获取计算过程
        $this->process = array();
        $this->process = json_decode($this->getFieldById($resultid, "qianprocess"), true);
        if ($this->process == null) {
            $this->process = array();
        }

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
            'qian_constant' => round((float)$result_msg['qian_dspc'] - (float)$total_weight['t_weight'] - (float)$result_msg['qian_fwater_weight'] - (float)$result_msg['qian_sewage_weight'] - (float)$result_msg['qian_fuel_weight'] - (float)$result_msg['qian_other_weight'] - $ship_weight, 5),
        );

//        $this->process = "t_weight=" . (float)$total_weight['t_weight'] . " \r\n qian_constant=dspc - t_weight - fwater_weight- sewage_weight - fuel_weight - other_weight=" . $data_r['qian_constant'];
        $this->process['t_weight'] = (float)$total_weight['t_weight'];
        $this->process['ship_weight'] = $ship_weight;
        $this->process['constant'] = (float)$data_r['qian_constant'];

        if ($result_msg['hou_constant'] > 0) {
            if ($result_msg['qian_constant'] >= $result_msg['hou_constant']) {
                $data_r['weight'] = round((float)$result_msg['qian_constant'] - (float)$result_msg['hou_constant'], 5);
                $this->process['heavier'] = 'q';
            } else {
                $data_r['weight'] = round((float)$result_msg['hou_constant'] - (float)$result_msg['qian_constant'], 5);
                $this->process['heavier'] = 'h';
            }
            $this->process['weight'] = (float)$data_r['weight'];
        }

        $data_r['qianprocess'] = json_encode($this->process);


        $resr = $this->editData(array('id' => $resultid), $data_r);

        if ($resr === false) {
            M()->rollback();
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                'msg' => $this->getDbError(),
            );
            exit(json_encode($res));
        } else {
            //作业后计算总重量,获取计算过程
            $this->process = array();
            $this->process = json_decode($this->getFieldById($resultid, "houprocess"), true);
            if ($this->process == null) {
                $this->process = array();
            }

            $wherelist = array(
                'resultid' => $resultid,
                'solt' => 2,
            );
            //获取压载水总重
            $total_weight = $resultlist->field('sum(weight) as t_weight')->where($wherelist)->find();
            //获取重量
            $result_msg = $this->field('hou_dspc,hou_fwater_weight,hou_sewage_weight,hou_fuel_weight,hou_other_weight,qian_constant,hou_constant')->where(array('id' => $resultid))->find();
            $data_r = array(
                'hou_constant' => round((float)$result_msg['hou_dspc'] - (float)$total_weight['t_weight'] - (float)$result_msg['hou_fwater_weight'] - (float)$result_msg['hou_sewage_weight'] - (float)$result_msg['hou_fuel_weight'] - (float)$result_msg['hou_other_weight'] - $ship_weight, 5),
            );
//            $this->process = "t_weight=" . (float)$total_weight['t_weight'] . " \r\n hou_constant=dspc - t_weight - fwater_weight- sewage_weight - fuel_weight - other_weight=" . $data_r['hou_constant'];
            $this->process['t_weight'] = (float)$total_weight['t_weight'];
            $this->process['ship_weight'] = $ship_weight;
            $this->process['constant'] = (float)$data_r['hou_constant'];

            if ($result_msg['qian_constant'] >= $result_msg['hou_constant']) {
                $data_r['weight'] = round((float)$result_msg['qian_constant'] - (float)$result_msg['hou_constant'], 5);
                $this->process['heavier'] = 'q';
            } else {
                $data_r['weight'] = round((float)$result_msg['hou_constant'] - (float)$result_msg['qian_constant'], 5);
                $this->process['heavier'] = 'h';
            }
            $this->process['weight'] = (float)$data_r['weight'];


            $data_r['houprocess'] = json_encode($this->process);

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
     * @param int|float $Xbig 上刻度
     * @param int|float $Xsmall 下刻度
     * @param int|float $X 当前刻度
     * @return float|int 中间插值
     */
    public
    function getMiddleValue($Cbig, $Csmall, $Xbig, $Xsmall, $X)
    {
        return round(($Cbig - ($Csmall)), 3) / ($Xbig - ($Xsmall)) * ($X - ($Xsmall)) + $Csmall;
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
                $this->process['table']['TPC'] = $TPCup;
                $this->process['table']['DS'] = $DSup;
                $this->process['table']['Xf'] = $Xfup;
                $this->process['table']['type'] = 'u';
                $this->process['table']['method'] = 'u';

                return array(
                    'TPC' => $TPCup,
                    'DS' => $DSup,
                    'Xf' => $Xfup,
                );
            } else {
                $this->process['table']['TPC'] = $TPCdown;
                $this->process['table']['DS'] = $DSdown;
                $this->process['table']['Xf'] = $Xfdown;
                $this->process['table']['type'] = 'u';
                $this->process['table']['method'] = 'd';
                return array(
                    'TPC' => $TPCdown,
                    'DS' => $DSdown,
                    'Xf' => $Xfdown,
                );

            }

        } elseif ($D_M <= $minD_M) {

            if ($Dup <= $Ddown) {

                $this->process['table']['TPC'] = $TPCup;
                $this->process['table']['DS'] = $DSup;
                $this->process['table']['Xf'] = $Xfup;
                $this->process['table']['type'] = 'd';
                $this->process['table']['method'] = 'u';

                return array(
                    'TPC' => $TPCup,
                    'DS' => $DSup,
                    'Xf' => $Xfup,
                );

            } else {

                $this->process['table']['TPC'] = $TPCdown;
                $this->process['table']['DS'] = $DSdown;
                $this->process['table']['Xf'] = $Xfdown;
                $this->process['table']['type'] = 'd';
                $this->process['table']['method'] = 'd';

                return array(
                    'TPC' => $TPCdown,
                    'DS' => $DSdown,
                    'Xf' => $Xfdown,
                );

            }
        } else {

            $TPC = $this->getMiddleValue($TPCup, $TPCdown, $Dup, $Ddown, $D_M);
            $DS = $this->getMiddleValue($DSup, $DSdown, $Dup, $Ddown, $D_M);
            $Xf = $this->getMiddleValue($Xfup, $Xfdown, $Dup, $Ddown, $D_M);

            $this->process['table']['TPC'] = $TPC;
            $this->process['table']['DS'] = $DS;
            $this->process['table']['Xf'] = $Xf;
            $this->process['table']['type'] = 'm';
            $this->process['table']['method'] = 'm';

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
    /*public function capacityreckon($data)
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
    }*/

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
    public function historical_sum($historical, $name, $id, $dataweight)
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
     * 打印pdf
     */
    public function pdf($arr, $resultid, $uid)
    {
        //判断文件是否存在
        $file = $_SERVER['DOCUMENT_ROOT'] . '/Public/pdf/ShMiniProgram/' . $uid . "/" . $resultid . ".pdf";

        $fileDir = $_SERVER['DOCUMENT_ROOT'] . '/Public/pdf/ShMiniProgram/' . $uid . "/";

        $delDir = $_SERVER['DOCUMENT_ROOT'] . '/Public/pdf/ShMiniProgram';

        //echo $_SERVER['DOCUMENT_ROOT'];
        //检测文件夹是否存在,不存在创建
        if (mkdirs($fileDir) == false) {
            return false;
        }

        //删除创建时间超过5天的文件
        $delnum = read_all_dir($delDir);

        //检测文件是否存在，存在则删除
        if (is_file($file)) {
            if (!unlink($file)) return "";
        }

        vendor('mpdf.mpdf');
        $pdf = new \mPDF('zh-cn', 'A4', 0, '宋体', 0, 0);
        // 设置打印模式

        // 设置是否自动分页  距离底部多少距离时分页
        $pdf->SetAutoPageBreak(TRUE, '1');

        //新增一个页面
//        $pdf->AddPage('R', 'A4');

        // 获取打印的模板
        $html1 = $this->getPDFHtmlCode($arr);

        //内容写入PDF
        $pdf->WriteHTML($html1);
        //输出
        $pdf->Output($file, 'F');
        return '/Public/pdf/ShMiniProgram/' . $uid . "/" . $resultid . ".pdf";
//        $pdf->Output();
    }

    public function getPDFHtmlCode($arr)
    {
        $html_tpl = <<<html_tpl
<table style="text-align: center;width: 95%;border-bottom: 3px double;margin:0 auto;">
	<tbody>
		<tr>
			<td style="font-size:xx-large" colspan="3">
				南&nbsp;京&nbsp;中&nbsp;理&nbsp;外&nbsp;轮&nbsp;理&nbsp;货&nbsp;有&nbsp;限&nbsp;公&nbsp;司
			</td>
		</tr>
		<tr>
			<td style="width: 250px;text-align: right;" rowspan="2">
				<img src="Public/home/img/aaa.png">
			</td>
			<td style="text-align: left">
				<span style="text-align: left">
				CHINA&nbsp;&nbsp;OCEAN&nbsp;&nbsp;SHIPPING&nbsp;&nbsp;TALLY&nbsp;&nbsp;CO.,&nbsp;LTD.&nbsp;&nbsp;NANJING
				</span>
			</td>
			<td style="width: 130px;text-align: center;" rowspan="3">
                <img src="{{content.verify}}" style="width: 80px;height: 80px;"/>
                <p style="font-size:12;">扫描上方二维码验真伪</p>
            </td>
		</tr>
		<tr>
			<td style="text-align: left">
				<span style="margin-left: 40px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Add:68#Beizushian,Guolou&nbsp;District,Nanjing,China</span>
			</td>
		</tr>
		<tr>
			<td></td>
			<td style="text-align: left">
				<span style="margin-left: 10px;">&nbsp;&nbsp;Tel:86-25-58582066&nbsp;&nbsp;&nbsp;Fax:86-25-58752747&nbsp;&nbsp;Postcode:210015</span>
			</td>
		</tr>
	</tbody>
</table>


<div style="margin-top: 30px;text-align: center;font-size:x-large;">
	<strong>水尺计重记录单</strong>
</div>
<div style="text-align: center;font-size:large;">
	<strong>海伦船型</strong>
</div>
<table style="width: 95%;margin: 0 auto;text-align: center;border-collapse: collapse;">
	<tbody>
		<tr style="height: 10px">
			<td style="width: 11%"></td>
			<td style="width: 11%"></td>
			<td style="width: 11%"></td>
			<td style="width: 11%"></td>
			<td style="width: 11%"></td>
			<td style="width: 11%"></td>
			<td style="width: 11%"></td>
			<td style="width: 11%"></td>
			<td style="width: 12%"></td>
		</tr>
		<tr>
			<td><strong>船名</strong></td>
			<td colspan="2" style="border-bottom: 1px solid;">{{content.shipname}}</td>
			<td><strong>航次</strong></td>
			<td colspan="2" style="border-bottom: 1px solid;">{{personality.voyage}}</td>
			<td><strong>货名</strong></td>
			<td colspan="2" style="border-bottom: 1px solid;">{{personality.cargoname}}</td>
		</tr>
		<tr>
			<td><strong>装货港</strong></td>
			<td colspan="2" style="border-bottom: 1px solid;">{{personality.start}}</td>
			<td><strong>卸货港</strong></td>
			<td colspan="2" style="border-bottom: 1px solid;">{{personality.objective}}</td>
			<td><strong>检验地点</strong></td>
			<td colspan="2" style="border-bottom: 1px solid;">{{personality.locationname}}</td>
		</tr>
		<tr>
			<td><strong>开工时间</strong></td>
			<td colspan="3" style="border-bottom: 1px solid;">{{content.time}}</td>
			<td><strong>完工时间</strong></td>
			<td colspan="4" style="border-bottom: 1px solid;">{{nowTime}}</td>
		</tr>
		<tr></tr>
		<tr>
			<td style="height: 20px"></td>
			<td colspan="3" style="height: 20px"><strong>空载吃水</strong></td>
			<td style="height: 20px"></td>
			<td style="height: 20px"></td>
			<td style="height: 20px" colspan="3"><strong>重载吃水</strong></td>
		</tr>
		<tr>
			<td></td>
			<td style="border: 1px solid;">艏</td>
			<td style="border: 1px solid;">艉</td>
			<td style="border: 1px solid;">舯</td>
			<td></td>
			<td></td>
			<td style="border: 1px solid;">艏</td>
			<td style="border: 1px solid;">艉</td>
			<td style="border: 1px solid;">舯</td>
		</tr>
		<tr>
			<td>左舷</td>
			<td style="border: 1px solid;">{{fornt.q.forntleft}}</td>
			<td style="border: 1px solid;">{{fornt.q.afterleft}}</td>
			<td style="border: 1px solid;">{{fornt.q.centerleft}}</td>
			<td></td>
			<td>左舷</td>
			<td style="border: 1px solid;">{{fornt.h.forntleft}}</td>
			<td style="border: 1px solid;">{{fornt.h.afterleft}}</td>
			<td style="border: 1px solid;">{{fornt.h.centerleft}}</td>
		</tr>
		<tr>
			<td>右舷</td>
			<td style="border: 1px solid;">{{fornt.q.forntright}}</td>
			<td style="border: 1px solid;">{{fornt.q.afterright}}</td>
			<td style="border: 1px solid;">{{fornt.q.centerright}}</td>
			<td></td>
			<td>右舷</td>
			<td style="border: 1px solid;">{{fornt.h.forntright}}</td>
			<td style="border: 1px solid;">{{fornt.h.afterright}}</td>
			<td style="border: 1px solid;">{{fornt.h.centerright}}</td>
		</tr>
		<tr>
			<td>平均值</td>
			<td style="border: 1px solid;">{{fornt.q.fornt}}</td>
			<td style="border: 1px solid;">{{fornt.q.after}}</td>
			<td style="border: 1px solid;">{{fornt.q.center}}</td>
			<td></td>
			<td>平均值</td>
			<td style="border: 1px solid;">{{fornt.h.fornt}}</td>
			<td style="border: 1px solid;">{{fornt.h.after}}</td>
			<td style="border: 1px solid;">{{fornt.h.center}}</td>
		</tr>
		<tr>
			<td>修正值</td>
			<td style="border: 1px solid;">{{fornt.q.fc}}</td>
			<td style="border: 1px solid;">{{fornt.q.ac}}</td>
			<td style="border: 1px solid;">{{fornt.q.mc}}</td>
			<td></td>
			<td>修正值</td>
			<td style="border: 1px solid;">{{fornt.h.fc}}</td>
			<td style="border: 1px solid;">{{fornt.h.ac}}</td>
			<td style="border: 1px solid;">{{fornt.h.mc}}</td>
		</tr>
		<tr>
			<td>龙骨修正</td>
			<td style="border: 1px solid;">0</td>
			<td style="border: 1px solid;">0</td>
			<td style="border: 1px solid;">0</td>
			<td></td>
			<td>龙骨修正</td>
			<td style="border: 1px solid;">0</td>
			<td style="border: 1px solid;">0</td>
			<td style="border: 1px solid;">0</td>
		</tr>
		<tr>
			<td>修正后</td>
			<td style="border: 1px solid;">{{fornt.q.fm}}</td>
			<td style="border: 1px solid;">{{fornt.q.am}}</td>
			<td style="border: 1px solid;">{{fornt.q.mm}}</td>
			<td></td>
			<td>修正后</td>
			<td style="border: 1px solid;">{{fornt.h.fm}}</td>
			<td style="border: 1px solid;">{{fornt.h.am}}</td>
			<td style="border: 1px solid;">{{fornt.h.mm}}</td>
		</tr>
		<tr>
			<td colspan="9" style="height: 20px;"></td>
		</tr>
		<tr>
			<td><strong>平均吃水</strong></td>
			<td><strong>{{content.qian_d_m}}</strong></td>
			<td><strong>m</strong></td>
			<td colspan="2"></td>
			<td><strong>平均吃水</strong></td>
			<td><strong>{{content.hou_d_m}}</strong></td>
			<td><strong>m</strong></td>
			<td></td>
		</tr>
		<tr>
			<td>排水量</td>
			<td>{{record.q.dsc}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>排水量</td>
			<td>{{record.h.dsc}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>纵倾修正</td>
			<td>{{record.q.dc}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>纵倾修正</td>
			<td>{{record.h.dc}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>港水修正</td>
			<td>{{record.q.dpc}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>港水修正</td>
			<td>{{record.h.dpc}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>修正后</td>
			<td>{{content.qian_dspc}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>修正后</td>
			<td>{{content.hou_dspc}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td colspan="9" style="height: 10px;"></td>
		</tr>
		<tr>
			<td colspan="3"><strong>空载相关数据</strong></td>
			<td colspan="2"></td>
			<td colspan="3"><strong>重载相关数据</strong></td>
			<td>
		</tr>
		<tr>
			<td>空 船</td>
			<td>{{content.ship_weight}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>空 船</td>
			<td>{{content.ship_weight}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>轻 油</td>
			<td>{{content.qian_fuel_weight}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>轻 油</td>
			<td>{{content.hou_fuel_weight}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>重 油</td>
			<td>0</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>重 油</td>
			<td>0</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>润滑油</td>
			<td>0</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>润滑油</td>
			<td>0</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>淡 水</td>
			<td>{{content.qian_fwater_weight}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>淡 水</td>
			<td>{{content.hou_fwater_weight}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>常 数</td>
			<td>0</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>常 数</td>
			<td>0</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>压载水</td>
			<td>{{content.qian_bw}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>压载水</td>
			<td>{{content.hou_bw}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>其它货物</td>
			<td>0</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>其它货物</td>
			<td>0</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>其 它</td>
			<td>0</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>其 它</td>
			<td>0</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>合 计</td>
			<td>{{qian_total}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>合 计</td>
			<td>{{hou_total}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td>排水量</td>
			<td>{{content.qian_dspc}}</td>
			<td>MT</td>
			<td colspan="2"></td>
			<td>排水量</td>
			<td>{{content.hou_dspc}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td colspan="5"></td>
			<td>排水量差值</td>
			<td>{{hou-qian_dspc}}</td>
			<td>MT</td>
			<td></td>
		</tr>
		<tr>
			<td colspan="5"></td>
			<td>水油差值</td>
			<td>{{hou-qian_total}}</td>
			<td>MT</td>
			<td>
		</tr>
		<tr>
			<td colspan="5"></td>
			<td><strong>总计货重</strong></td>
			<td><strong>{{content.weight}}</strong></td>
			<td><strong>MT</strong></td>
			<td>
		</tr>
		<tr style="height: 20px;">
			<td colspan="9"></td>
		</tr>
		<tr>
			<td style="border-bottom: 1px solid;"><strong>备注：</strong></td>
				<td colspan="8" style="border-bottom: 1px solid;text-align: left;">{{content.remark}}</td>
		</tr>
		<tr style="height: 25px;">
				<td colspan="9" style="border-bottom: 1px solid;height: 25px;"></td>
		</tr>
		<tr style="height: 25px;">
				<td colspan="9" style="border-bottom: 1px solid;height: 25px;"></td>
		</tr>
		<tr style="height: 40px;">
			<td></td>
			<td colspan="3" style="border-bottom: 1px solid;height: 40px;">{{content.username}}</td>
			<td colspan="2"></td>
			<td colspan="2" style="border-bottom: 1px solid;height: 40px;"></td>
			<td></td>
		</tr>
		<tr>
			<td></td>
			<td colspan="3" align="center" style="text-align: center"><strong>检验员</strong></td>
			<td colspan="2"></td>
			<td colspan="2" align="center" style="text-align: center"><strong>大副</strong></td>
			<td></td>
		</tr>
	</tbody>
</table>
html_tpl;

        $tpl_var = array(
            "{{content.verify}}",
            "{{content.shipname}}",
            "{{personality.voyage}}",
            "{{personality.cargoname}}",
            "{{personality.start}}",
            "{{personality.objective}}",
            "{{personality.locationname}}",
            "{{content.time}}",
            "{{nowTime}}",
            "{{fornt.q.forntleft}}",
            "{{fornt.q.afterleft}}",
            "{{fornt.q.centerleft}}",
            "{{fornt.h.forntleft}}",
            "{{fornt.h.afterleft}}",
            "{{fornt.h.centerleft}}",
            "{{fornt.q.forntright}}",
            "{{fornt.q.afterright}}",
            "{{fornt.q.centerright}}",
            "{{fornt.h.forntright}}",
            "{{fornt.h.afterright}}",
            "{{fornt.h.centerright}}",
            "{{fornt.q.fornt}}",
            "{{fornt.q.after}}",
            "{{fornt.q.center}}",
            "{{fornt.h.fornt}}",
            "{{fornt.h.after}}",
            "{{fornt.h.center}}",
            "{{fornt.q.fc}}",
            "{{fornt.q.ac}}",
            "{{fornt.q.mc}}",
            "{{fornt.h.fc}}",
            "{{fornt.h.ac}}",
            "{{fornt.h.mc}}",
            "{{fornt.q.fm}}",
            "{{fornt.q.am}}",
            "{{fornt.q.mm}}",
            "{{fornt.h.fm}}",
            "{{fornt.h.am}}",
            "{{fornt.h.mm}}",
            "{{content.qian_d_m}}",
            "{{content.hou_d_m}}",
            "{{record.q.dsc}}",
            "{{record.h.dsc}}",
            "{{record.q.dc}}",
            "{{record.h.dc}}",
            "{{record.q.dpc}}",
            "{{record.h.dpc}}",
            "{{content.qian_dspc}}",
            "{{content.hou_dspc}}",
            "{{content.ship_weight}}",
            "{{content.ship_weight}}",
            "{{content.qian_fuel_weight}}",
            "{{content.hou_fuel_weight}}",
            "{{content.qian_fwater_weight}}",
            "{{content.hou_fwater_weight}}",
            "{{content.qian_bw}}",
            "{{content.hou_bw}}",
            "{{qian_total}}",
            "{{hou_total}}",
            "{{content.qian_dspc}}",
            "{{content.hou_dspc}}",
            "{{hou-qian_dspc}}",
            "{{hou-qian_total}}",
            "{{content.weight}}",
            "{{content.remark}}",
            "{{content.username}}"
        );

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


        $tpl_value = array(
            $arr['content']['verify'],
            $arr['content']['shipname'],
            $arr['personality']['voyage'],
            $arr['personality']['cargoname'],
            $arr['personality']['start'],
            $arr['personality']['objective'],
            $arr['personality']['locationname'],
            date('Y-m-d H:i:s', $arr['content']['time']),
            date('Y-m-d H:i:s', time()),
            $arr['fornt']['q']['forntleft'],
            $arr['fornt']['q']['afterleft'],
            $arr['fornt']['q']['centerleft'],
            $arr['fornt']['h']['forntleft'],
            $arr['fornt']['h']['afterleft'],
            $arr['fornt']['h']['centerleft'],
            $arr['fornt']['q']['forntright'],
            $arr['fornt']['q']['afterright'],
            $arr['fornt']['q']['centerright'],
            $arr['fornt']['h']['forntright'],
            $arr['fornt']['h']['afterright'],
            $arr['fornt']['h']['centerright'],
            $arr['fornt']['q']['fornt'],
            $arr['fornt']['q']['after'],
            $arr['fornt']['q']['center'],
            $arr['fornt']['h']['fornt'],
            $arr['fornt']['h']['after'],
            $arr['fornt']['h']['center'],
            $arr['fornt']['q']['fc'],
            $arr['fornt']['q']['ac'],
            $arr['fornt']['q']['mc'],
            $arr['fornt']['h']['fc'],
            $arr['fornt']['h']['ac'],
            $arr['fornt']['h']['mc'],
            $arr['fornt']['q']['fm'],
            $arr['fornt']['q']['am'],
            $arr['fornt']['q']['mm'],
            $arr['fornt']['h']['fm'],
            $arr['fornt']['h']['am'],
            $arr['fornt']['h']['mm'],
            $arr['content']['qian_d_m'],
            $arr['content']['qian_d_m'],
            $arr['record']['q']['dsc'],
            $arr['record']['h']['dsc'],
            $arr['record']['q']['dc'],
            $arr['record']['h']['dc'],
            $arr['record']['q']['dpc'],
            $arr['record']['h']['dpc'],
            $arr['content']['qian_dspc'],
            $arr['content']['hou_dspc'],
            $arr['content']['ship_weight'],
            $arr['content']['ship_weight'],
            $arr['content']['qian_fuel_weight'],
            $arr['content']['hou_fuel_weight'],
            $arr['content']['qian_fwater_weight'],
            $arr['content']['hou_fwater_weight'],
            $arr['content']['qian_bw'],
            $arr['content']['hou_bw'],
            $qian_total,
            $hou_total,
            $arr['content']['qian_dspc'],
            $arr['content']['hou_dspc'],
            $arr['content']['hou_dspc'] - $arr['content']['qian_dspc'],
            $hou_total - $qian_total,
            $arr['content']['weight'],
            $arr['content']['remark'],
            $arr['content']['username']
        );

        $html = str_replace($tpl_var, $tpl_value, $html_tpl);

        return $html;
    }

    /**
     * 积累静水压力排水量表数据
     */
    public function cumulative_hydrostatic_data($data)
    {
        if (!judgeTwoString($data)) {
            //如果含有特殊字符，返回0
            $res = 0;
        } else {
            $cumulative_hydrostatic_data = M('cumulative_hydrostatic_data');
            //先判断此累积数据是否存在公司的数据内
            $trim_where = array('ship_id' => $data['shipid']);

            if ($data['d_up'] == $data['d_down']) {
                //如果空高正好落在空高刻度上，只需要查询是否空高1或者空高2等于该刻度值，其中一项有且刻度对上则算作数据已经有了
                $trim_where['d_up|d_down'] = floatval($data['d_up']);
            } else {
                $trim_where['d_up&d_down'] = array(floatval($data['d_up']), floatval($data['d_down']), '_multi' => true);
            }

            $hydrostatic_where['tpc_up'] = floatval($data['tpc_up']);
            $hydrostatic_where['tpc_down'] = floatval($data['tpc_down']);
            $hydrostatic_where['ds_up'] = floatval($data['ds_up']);
            $hydrostatic_where['ds_down'] = floatval($data['ds_down']);
            $hydrostatic_where['xf_up'] = floatval($data['xf_up']);
            $hydrostatic_where['xf_down'] = floatval($data['xf_down']);


            $data_count = $cumulative_hydrostatic_data->where($hydrostatic_where)->count();

            if ($data_count == 0) {
                $data['ins_time'] = time();
                //如果没有则插入累积值
                $result = $cumulative_hydrostatic_data->add($data);
                if ($result !== false) {
                    //存入成功，返回1
                    $res = 1;
                } else {
                    //存入失败，返回3
                    $res = 3;
                }
            } else {
                //如果数据已存在，返回2
                $res = 2;
            }
        }
        return $res;
    }

    /**
     * 调整累积静力水表数据
     * @param       $result_id 作业ID
     * @param       $solt 作业前后
     * @param       $shipid 船ID
     * @param array $data 静力水表数据
     * @return int $res 返回值：0错误，有特殊字符、1成功、2新值检测到重复，不录入、3失败
     */
    public function adjust_cumulative_hydrostatic_data($result_id, $solt, $shipid, $data)
    {
        $cumulative_hydrostatic_data = M('cumulative_hydrostatic_data');
        $result_mark = strval($result_id) . ($solt == 1 ? "Q" : "H");
        $repeat_where = array(
            'result_mark' => $result_mark,
            'ship_id' => $shipid,
        );
        $cumulative_hydrostatic_data->where($repeat_where)->delete();
        $ins_data = $data;
        $ins_data['ship_id'] = $shipid;
        $ins_data['data_sources'] = 1;
        $ins_data['result_mark'] = $result_mark;

        return $this->cumulative_hydrostatic_data($ins_data);
    }

    /**
     * 获取对应的纵倾修正表字段
     * @param int       $shipid 船ID
     * @param float|int $d_m 平均吃水
     * @param bool      $fullsearch 是否全部查询 true查询全部字段，false查询必要字段
     * @return array|bool
     */
    public function get_cumulative_hydrostatic_data($shipid, $d_m, $fullsearch = false)
    {
        $cumulative_hydrostatic_data = M('cumulative_hydrostatic_data');
        $trim_where = array(
            'ship_id' => intval($shipid),
            'd_up' => array('ELT', floatval($d_m)),
            'd_down' => array('EGT', floatval($d_m)),
        );

        //查找
        if (false == $fullsearch) {
            $data = $cumulative_hydrostatic_data->field('d_up,d_down,tpc_up,tpc_down,ds_up,ds_down,xf_up,xf_down')->where($trim_where)->order('data_sources desc,ins_time desc')->find();
        } else {
            $data = $cumulative_hydrostatic_data->where($trim_where)->order('data_sources desc,ins_time desc')->find();
        }

        if (!empty($data)) {
            //调用次数+1
            $cumulative_hydrostatic_data->where($trim_where)->setInc('use_count');
            return $data;
        } else {
            //无数据返回false
            return false;
        }
    }


}