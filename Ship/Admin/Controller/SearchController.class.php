<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

class SearchController extends AdminBaseController
{
    /**
     * 查询
     */

    public function index()
    {
        $result = new \Common\Model\ResultModel();
        $ship = new \Common\Model\ShipModel();
        $where = '1';
        if (I('get.firmid')) {
            $firmid = trimall(I('get.firmid'));
            $where .= " and c.firmid=$firmid";
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
            ->join('left join consumption c on r.id = c.resultid')
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
            ->join('left join ship s on s.id = r.shipid')
            ->join('left join consumption c on r.id = c.resultid')
            ->join('left join firm f on f.id = c.firmid')
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
    public function msg()
    {
        $res = new \Common\Model\ResultModel();
        $user = new \Common\Model\UserModel();
        //获取水尺数据
        $where = array(
            'r.id' => I('get.resultid')
        );
        //查询作业列表
        $list = $res
            ->alias('r')
            ->field('r.*,s.shipname,u.username,r.qianchi,r.houchi,s.goodsname goodname')
            ->join('left join ship s on r.shipid=s.id')
            ->join('left join user u on r.uid = u.id')
            ->where($where)
            ->find();
        if ($msg !== false) {
            $where1 = array('re.resultid' => $list['id']);
            $resultlist = new \Common\Model\ResultlistModel();
            $resultmsg = $resultlist
                ->alias('re')
                ->field('re.*,c.cabinname')
                ->join('left join cabin c on c.id = re.cabinid')
                ->where($where1)
                ->order('re.solt asc,re.cabinid asc')
                ->select();
            // p($resultmsg);die;
            //以舱区分数据（）
            foreach ($resultmsg as $k => $v) {
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
            // 成功	1
            $assign = array(
                'content' => $list,
                'result' => $result,
                'starttime' => $starttime,
                'endtime' => $endtime,
                'personality' => $personality
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
        $ship = new \Common\Model\ShipModel();
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
            $work = new \Common\Model\WorkModel();
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
            $work = new \Common\Model\WorkModel();
            $ship = new \Common\Model\ShipModel();
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
            $work = new \Common\Model\WorkModel();
            $where = array(
                'id' => $resultid,
                'del_sign' => 2
            );
            $resultCount = $work->where($where)->count();
            if ($resultCount > 0) {
                //如果该作业状态为已删除

                $resultlist = new \Common\Model\ResultlistModel();
                $forntrecord = M("forntrecord");
                $resultrecord = M("resultrecord");
                $forntImg = M("fornt_img");

                $where1 = array(
                    'resultid' => $resultid
                );

                $where2 = array(
                    'result_id' => $resultid
                );

                //删除作业总表信息
                $result1 = $work
                    ->where($where)
                    ->delete();

                //删除作业舱测量数据和图片数据信息
                $result_msg = $resultlist
                    ->field("id")
                    ->where($where1)
                    ->select();

                if (count($result_msg) > 0) {
                    $listids = "in(";
                    foreach ($result_msg as $key => $value) {
                        $listids .= $value['id'] . ",";
                    }
                    $listids = substr($listids, 0, strlen($listids) - 1);
                    $listids .= ")";

                    $result2 = $resultlist->where("id " . $listids)->delete();
                    $result6 = M("resultlist_img")->where("resultlist_id " . $listids)->delete();
                } else {
                    $result2 = 1;
                    $result6 = 1;
                }

                //删除作业计量数据信息
                $result3 = $resultrecord
                    ->where($where1)
                    ->delete();

                //删除作业水尺测量数据信息
                $result4 = $forntrecord
                    ->where($where1)
                    ->delete();

                //删除作业水尺测量图片数据信息
                $result5 = $forntImg
                    ->where($where2)
                    ->delete();

                if ($result6 !== false and $result5 !== false and $result4 !== false and $result3 !== false
                    and $result2 !== false and $result1 !== false) {
                    $this->ajaxReturn(array('code' => 1, 'msg' => '彻底删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 2, 'msg' => '彻底删除作业时有部分数据删除失败，请联系技术', 'result1' => $result1, 'result2' => $result2,
                        'result3' => $result3, 'result4' => $result4, 'result5' => $result5, 'result6' => $result6));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该数据未找到或者不是删除状态，请确认后重试'));
            }
        }
    }


    /**
     * 获取计算过程
     */
    public function process($resultid)
    {
        $res = new \Common\Model\ResultModel();
        $res_list = new \Common\Model\ResultlistModel();
        $res_record = M('resultrecord');
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
                'r.resultid' => $resultid,
                'r.solt' => 1
            );


            $list_qian_process = $res_list
                ->alias("r")
                ->field('r.process,c.cabinname')
                ->join("left join cabin as c on c.id=r.cabinid")
                ->where($where1)
                ->select();

            //获取作业前排水表计算过程
            $record_qian_process = $res_record
                ->alias("r")
                ->field('r.process,c.cabinname')
                ->join("left join cabin as c on c.id=r.cabinid")
                ->where($where1)
                ->select();

            $where1['r.solt'] = 2;

            $list_hou_process = $res_list
                ->alias("r")
                ->field('r.process,c.cabinname')
                ->join("left join cabin as c on c.id=r.cabinid")
                ->where($where1)
                ->select();

            //获取作业后排水表计算过程
            $record_hou_process = $res_record
                ->alias("r")
                ->field('r.process,c.cabinname')
                ->join("left join cabin as c on c.id=r.cabinid")
                ->where($where1)
                ->select();

            /**
             * url反转义过程
             */

            $list_qian = "";
            $list_hou = "";

            foreach ($list_qian_process as $key => $value) {
                if ($value['process'] != "") {
                    $list_qian .= "\r\n\r\n ----------<strong>" . $value['cabinname'] . "</strong>---------------- \r\n" . str_replace(array('\r\n', '\t'), array("\r\n", "\t"), urldecode($value['process']));
                }
            }

            foreach ($list_hou_process as $key => $value) {
                if ($value['process'] != "") {
                    $list_hou .= "\r\n\r\n ----------<strong>" . $value['cabinname'] . "</strong>---------------- \r\n" . str_replace(array('\r\n', '\t'), array("\r\n", "\t"), urldecode($value['process']));
                }
            }

            $qian_record = "";
            $hou_record = "";
            foreach ($record_qian_process as $key1 => $value1) {
                if ($value1['process'] != "") {
                    $qian_record .= "\r\n\r\n ---------------------<strong>" . $value1['cabinname'] . "</strong>---------------------- \r\n" . str_replace(array('\r\n', '\t'), array("\r\n", "\t"), urldecode($value1['process']));
                }
            }
            foreach ($record_hou_process as $key1 => $value1) {
                if ($value1['process'] != "") {
                    $hou_record .= "\r\n\r\n ---------------------<strong>" . $value1['cabinname'] . "</strong>---------------------- \r\n" . str_replace(array('\r\n', '\t'), array("\r\n", "\t"), urldecode($value1['process']));
                }
            }

            $qianprocess .= $list_qian . $qian_record;

            $houprocess .= $list_hou . $hou_record;

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
}
