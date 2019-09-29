<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

class ShSearchController extends AdminBaseController
{
    /**
     * 查看计算过程
     */

    public function index()
    {
        $result = new \Common\Model\ShResultModel();
        $ship = new \Common\Model\ShShipModel();
        $where = '1';
        if (I('get.firmid')) {
            $firmid = trimall(I('get.firmid'));
            $where .= " and f.firmid=$firmid";
        }
        if (I('get.shipname')) {
            $shipname = trimall(I('get.shipname'));
            $shipid = $ship->field('id')->where("shipname like '%" . $shipname . "%'")->find();
//            $shipid = $ship->getFieldByShipname($shipname, 'id');
            // p($shipid);die;
            $where .= " and r.shipid=" . $shipid['id'];
        }
        if (I('get.voyage')) {
            $voyage = trimall(I('get.voyage'));
            $where .= " and r.personality like  '" . '%"voyage":"%' . $voyage . '%\'';
        }
        if (I('get.locationname')) {
            $locationname = trimall(I('get.locationname'));
            $where .= " and r.personality like  '" . '%"location":"%' . $locationname . '%\'';
        }
        if (I('get.start')) {
            $start = trimall(I('get.start'));
            $where .= " and r.personality like  '" . '%"start":"%' . $start . '%\'';
        }
        if (I('get.objective')) {
            $objective = trimall(I('get.objective'));
            $where .= " and r.personality like  '" . '%"objective":"%' . $objective . '%\'';
        }
        if (I('get.del_sign')) {
            $del_sign = trimall(I('get.del_sign'));
            $where .= " and r.del_sign=" . $del_sign;
        } else {
            $where .= " and r . del_sign < 2";
        }
        $count = $result
            ->alias('r')
            ->join('left join ship s on s.id = r.shipid')
//            ->join('left join consumption c on r.id = c.resultid')
            ->where($where)
            ->count();
        // p($count);die;
        $per = 30;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;
        $data = $result
            ->alias('r')
            ->field('r.id,r.personality,r.weight,r.del_sign,s.shipname,f.firmname')
            ->join('left join sh_ship s on s.id = r.shipid')
//            ->join('left join consumption c on r.id = c.resultid')
            ->join('left join firm f on f.id = s.firmid')
            ->where($where)
            ->limit($begin, $per)
            ->order('r.id desc')
            ->select();
        // p($data);
        foreach ($data as $key => $value) {
            $data[$key]['personality'] = json_decode($value['personality'], true);
        }
        // p($data);die;
        // 获取船列表
        $shiplist = $ship
            ->field('id,shipname')
            ->order('shipname asc')
            ->select();

        $firm = new \Common\Model\FirmModel();
        $firmlist = $firm
            ->field('id,firmname')
            // ->where(array('firmtype'=>'2'))
            ->order('id asc')
            ->select();
        $assign = array(
            'data' => $data,
            'page' => $page,
            'shiplist' => $shiplist,
            'firmlist' => $firmlist,
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 详情
     */
    public function msg($resultid)
    {
        $work = new \Common\Model\ShResultModel();
        //获取水尺数据
        $where = array(
            'r.id' => $resultid
        );


        #todo 每一位数据自动去除没用的0
        //查询作业列表
        $list = $work
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
//        $this->assign("hou_total", $hou_total);
        $this->assign("hou_qian_dspc", (float)$arr['content']['hou_dspc'] - (float)$arr['content']['qian_dspc']);
        $this->assign("hou_qian_total", (float)$hou_total - (float)$qian_total);
        $this->assign("nowTime", $NowTime);

        $this->display();
    }

    /**
     * 详情
     */
    public function process($resultid)
    {
        $res = new \Common\Model\ShResultModel();
        $res_list = new \Common\Model\ShResultlistModel();
        $res_record = M('sh_resultrecord');
//        $user = new \Common\Model\UserModel();
        //获取水尺数据
        $where = array(
            'id' => $resultid
        );
        //查询作业列表
        $list = $res
            ->field('qianprocess,houprocess')
            ->where($where)
            ->find();
        $qianprocess = urldecode($list['qianprocess']);
        $houprocess = urldecode($list['houprocess']);
        if ($list !== false) {

            /**
             * 获取舱压载水的计算过程
             */
            $where1 = array(
                'resultid' => $resultid,
                'solt' => 1
            );

            //获取作业前排水表计算过程
            $record_qian_process = $res_record
                ->field('process')
                ->where($where1)
                ->find();

            $where1['solt'] = 2;

            //获取作业后排水表计算过程
            $record_hou_process = $res_record
                ->field('process')
                ->where($where1)
                ->find();

            /**
             * url反转义过程
             */
            $record_qian_process = str_replace(array("Dc1 =", "Dc2 =", "Dc =", "Dsc =", "Dpc =", "Dspc ="), array("\r\nDc1 =", "\r\nDc2 =", "\r\nDc =", "\r\nDsc =", "\r\nDpc =", "\r\nDspc ="), str_replace(array('\r\n','\t'), array("\r\n","\t"), urldecode($record_qian_process['process'])));

            $record_hou_process = str_replace(array("Dc1 =", "Dc2 =", "Dc =", "Dsc =", "Dpc =", "Dspc ="), array("\r\nDc1 =", "\r\nDc2 =", "\r\nDc =", "\r\nDsc =", "\r\nDpc =", "\r\nDspc ="), str_replace(array('\r\n','\t'), array("\r\n","\t"), urldecode($record_hou_process['process'])));

            $qianprocess .= "\r\n ---------------------TABLE---------------------- \r\n" . $record_qian_process;

            $houprocess .= "\r\n ---------------------TABLE---------------------- \r\n" . $record_hou_process;

            // p($resultmsg);die;
            //以舱区分数据（）
            /*            foreach ($resultmsg as $k => $v) {
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
                        }*/

            // 成功	1
            $assign = array(
                'qianprocess' => $qianprocess,
                'houprocess' => $houprocess,
            );

            // p($assign);exit;
            $this->assign($assign);
            $this->display();

        } else {
            $this->error('数据库连接错误');
        }
    }


    /**
     * ajax获取公司可操作的船列表
     */
    public function getFirmShip()
    {
        $ship = new \Common\Model\ShShipModel();
        $arr = $ship
            ->field('id,shipname')
            ->where("firmid='" . $_POST['firmid'] . "'")
            ->select();

        static $mod = "<select id='form-field-select-1' name='shipname' style='width:160px;'><option value=''>--选择船名--</option>";
        foreach ($arr as $key => $vo) {
            $mod .= "<option  value='" . $vo['shipname'] . "'>" . $vo['shipname'] . "</option>";
        }
        $mod .= "</select>";
        echo $mod;
    }


    /**
     * ajax 删除作业操作。
     */
    public function del_work()
    {
        if (IS_AJAX) {
            $resultid = trimall(I('post.resultid'));
            $work = new \Common\Model\ShResultModel();
            $where = array(
                'id' => $resultid,
                'del_sign' => 1
            );
            $resultCount = $work->editData($where, array('del_sign' => 2));
            if ($resultCount > 0) {
                //如果影响行数大于0
//                $resultmsg = $work
//                    ->where(array('id' => $resultid))
//                    ->find();
//                $user = new \Common\Model\UserModel();
//                $firmid = $user->getFieldById($resultmsg['uid'], 'firmid');

                //消除对应的作业计数
//                M('firm_historical_sum')->where(array('firmid' => $firmid))->setDec('num');
//                M('user_historical_sum')->where(array('userid' => $resultmsg['uid']))->setDec('num');
//                M('ship_historical_sum')->where(array('shipid' => $resultmsg['shipid']))->setDec('num');

                $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功'));
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该数据未找到或已被删除'));
            }
        }
    }


    /**
     * ajax 恢复作业操作。
     * 前提要求所属船没有被删除
     */
    public function recoverWork()
    {
        if (IS_AJAX) {
            $resultid = trimall(I('post.resultid'));
            $work = new \Common\Model\ShResultModel();
            $ship = new \Common\Model\ShShipModel();
            $shipid = $work->field('shipid')->where(array('id' => $resultid))->find();

            $shipcount = $ship->where(array('id' => $shipid['shipid'], 'del_sign' => 1))->count();

            if ($shipcount > 0) {
                $where = array(
                    'id' => $resultid,
                    'del_sign' => 2
                );
                $resultCount = $work->editData($where, array('del_sign' => 1));
                if ($resultCount > 0) {
                    //如果影响行数大于0
                    /*$resultmsg = $work
                        ->where(array('id' => $resultid))
                        ->find();
                    $user = new \Common\Model\UserModel();
                    $firmid = $user->getFieldById($resultmsg['uid'], 'firmid');

                    //恢复作业计数
                    M('firm_historical_sum')->where(array('firmid' => $firmid))->setInc('num');
                    M('user_historical_sum')->where(array('userid' => $resultmsg['uid']))->setInc('num');
                    M('ship_historical_sum')->where(array('shipid' => $resultmsg['shipid']))->setInc('num');*/

                    $this->ajaxReturn(array('code' => 1, 'msg' => '恢复成功'));
                } else {
                    $this->ajaxReturn(array('code' => 11, 'msg' => '该数据未找到或已被恢复,请勿重复恢复'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '恢复此作业前请先恢复该作业所属的船！'));
            }
        }
    }


    /**
     * ajax 彻底删除作业操作。
     */
    public function relDelWork()
    {
        if (IS_AJAX) {
            $resultid = trimall(I('post.resultid'));
            $work = new \Common\Model\ShResultModel();
            $where = array(
                'id' => $resultid,
                'del_sign' => 2
            );
            $resultCount = $work->where($where)->count();
            if ($resultCount > 0) {
                //如果该作业状态为已删除


                //删除作业总表信息
                $result1 = $work
                    ->where($where)
                    ->delete();


                if ($result1 !== false) {
                    $this->ajaxReturn(array('code' => 1, 'msg' => '彻底删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 2, 'msg' => '彻底删除作业时有部分数据删除失败，请联系技术', 'result1' => $result1));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该数据未找到或者不是删除状态，请确认后重试'));
            }
        }
    }
}
