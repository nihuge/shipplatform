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
    public function msg()
    {
        $res = new \Common\Model\ShResultModel();
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
            $record_qian_process = str_replace(array("Dc1 =","Dc2 =", "Dc =", "Dsc =", "Dpc =", "Dspc ="), array("\r\nDc1 =","\r\nDc2 =", "\r\nDc =", "\r\nDsc =", "\r\nDpc =", "\r\nDspc ="), str_replace('\r\n', "\r\n", urldecode($record_qian_process['process'])));

            $record_hou_process = str_replace(array("Dc1 =","Dc2 =", "Dc =", "Dsc =", "Dpc =", "Dspc ="), array("\r\nDc1 =","\r\nDc2 =", "\r\nDc =", "\r\nDsc =", "\r\nDpc =", "\r\nDspc ="), str_replace('\r\n', "\r\n", urldecode($record_hou_process['process'])));

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
