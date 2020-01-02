<?php

namespace Index\Controller;

use Common\Controller\IndexBaseController;

/**
 * 散货船系统管理
 */
class BulkController extends IndexBaseController
{
    // 定义数据表
    private $db;

    // 构造函数 实例化ResultModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\ShResultModel();
    }

    /**
     * 列表
     */
    public function index()
    {
        $user = new \Common\Model\UserModel();
        $uid = $_SESSION['user_info']['id'];
        $where = 1;
        // 根据用户id获取用户的操作权限、查询权限
        $msg = $user->getUserOperationSeach($uid);

        $firmid = $user->getFieldById($uid, 'firmid');

        if ($msg['sh_search_jur'] == '') {
            $where = " r.uid ='$uid'";
            // 查询权限为空时，查看所有操作权限之内的作业
            if ($msg['sh_operation_jur'] == '') {
                $operation_jur = "";
            } else {
                $operation_jur = $msg['sh_operation_jur'];
                $where .= " and r.shipid in (" . $operation_jur . ")";
            }

        } else {
            $where = " r.shipid in (" . $msg['sh_search_jur'] . ")";
        }

        if ($msg['look_other'] == '1') {
            $where .= " and u.firmid='" . $msg['firmid'] . "'";
        }elseif ($msg['look_other'] == '3') {
            $where .= " and u.id=$uid";
        }

        if (I('post.shipid')) {
            if ($msg['sh_search_jur'] !== '') {
                // 判断提交的船是否在权限之内
                if (!in_array(I('post.shipid'), $msg['sh_search_jur_array'])) {
                    $this->error('该船不在查询范围之内！！');
                    die;
                }
            }
            $shipid = trimall(I('post.shipid'));
            $where .= " and r.shipid=$shipid";
        }

        if (I('post.voyage')) {
            $voyage = trimall(I('post.voyage'));
            // $where .= " and r.voyage=$voyage";
            $where .= " and r.personality like  '" . '%"voyage":"%' . $voyage . '%\'';
        }
        if (I('post.locationname')) {
            $locationname = trimall(I('post.locationname'));
            // $where .= " and r.locationname = $locationname";
            $where .= " and r.personality like  '" . '%"locationname":"%' . $locationname . '%\'';
        }
        if (I('post.goodsname')) {
            $goodsname = trimall(I('post.goodsname'));
            // $where .= " and r.goodsname=$goodsname";
            $where .= " and r.personality like  '" . '%"goodsname":"%' . $goodsname . '%\'';
        }
        if (I('post.start')) {
            $start = trimall(I('post.start'));
            // $where .= " and r.start = $start";
            $where .= " and r.personality like  '" . '%"start":"%' . $start . '%\'';
        }
        if (I('post.objective')) {
            $objective = trimall(I('post.objective'));
            // $where .= " and r.objective = $objective";
            $where .= " and r.personality like  '" . '%"objective":"%' . $objective . '%\'';
        }
        if (I('post.time')) {
            $time = explode(' - ', I('post.time'));
            $starttime = strtotime($time[0]);
            $endtime = strtotime($time[1]);
            $where .= " and r.time >= $starttime and r.time <= $endtime";
        }
        $where .= " and r.del_sign = 1";

        //获取数据列表
        $count = $this->db
            ->alias('r')
            ->join('left join user u on u.id=r.uid')
            ->join('left join firm f on f.id = u.firmid')
            ->where($where)
            ->count();

        // 分页
        $page = new \Org\Nx\Page($count, 20);

        $list = $this->db
            ->field('r.personality,r.time,r.weight,s.shipname,r.id,f.id as firmid,f.firmtype,r.uid,r.shipid')
            ->alias('r')
            ->join('left join sh_ship s on s.id=r.shipid')
            ->join('left join user u on u.id=r.uid')
            ->join('left join firm f on f.id = u.firmid')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order('r.id desc')
            ->select();

        // 获取当前登陆用户的公司类型
        $a = $user
            ->field('f.firmtype,f.id')
            ->alias('u')
            ->join('left join firm f on u.firmid = f.id')
            ->where(array('u.id' => $uid))
            ->find();

        $ship = new \Common\Model\ShShipModel();
        foreach ($list as $key => $value) {
            $list[$key]['personality'] = json_decode($value['personality'], true);
            $list[$key]['time'] = date("Y-m-d H:i:s", $value['time']);
            // 根据作业人公司类型判断这条作业是否可以评价
            if ($value['firmtype'] == 2) {
                $list[$key]['is_coun'] = 'N';
            } else {
                // 判断作业是否完成----电子签证
                $coun = M('electronic_visa')
                    ->where(array('resultid' => $value['id']))
                    ->count();
                if ($coun > 0) {
                    // 船舶所属公司
                    $rfirmid = $ship->getFieldById($value['shipid'], 'firmid');
                    if ($value['uid'] == $uid) {
                        $list[$key]['is_coun'] = 'Y';
                    } elseif ($rfirmid == $a['id']) {
                        $list[$key]['is_coun'] = 'Y';
                    } else {
                        $list[$key]['is_coun'] = 'N';
                    }
                } else {
                    $list[$key]['is_coun'] = 'N';
                }
            }

            // 区别是否是自己的作业
            if ($value['uid'] == $uid) {
                // 是自己作业，可以修改、可以评价、可以查看详情
                $list[$key]['is_edit'] = '1';
            } else {
                // 不是自己作业，不可以修改，不可以评价，只可以查看详情
                $list[$key]['is_edit'] = '0';
            }
        }

        // 获取平台船列表
        $shiplist = $ship
            ->field('id,shipname')
            ->where(array('del_sign' => 1))
            ->order('shipname asc')
            ->select();

        // 获取用户操作权限--船
        $listship = $ship
            ->field('id,shipname')
            ->where(array('id' => array('IN', $msg['sh_operation_jur_array']), 'del_sign' => 1))
//            ->where(array('del_sign' => 1))
            ->order('shipname asc')
            ->select();

//         获取公司个性化字段
//        $firm = new \Common\Model\FirmModel();
//        $personality_id = $firm->getFieldById($msg['firmid'], 'personality');
//        $personality_id = $firm->getFieldById($firmid, 'personality');
//        $personality_id = json_decode($personality_id, true);
//        $personalitylist = array();
//        $person = new \Common\Model\PersonalityModel();
//        foreach ($personality_id as $key => $value) {
//            $personalitylist[] = $person
//                ->field('id,name,title')
//                ->where(array('id' => $value))
//                ->find();
//        }
        $personalitylist = array(
            array(
                "name" => "voyage",
                "title" => "指令航次"
            ),
            array(
                "name" => "locationname",
                "title" => "作业地点"
            ),
            array(
                "name" => "start",
                "title" => "起运港口"
            ),
            array(
                "name" => "objective",
                "title" => "目的港口"
            ),
            array(
                "name" => "goodsname",
                "title" => "货物名称"
            ),
            array(
                "name" => "transport",
                "title" => "运单量"
            ),
            array(
                "name" => "number",
                "title" => "作业编号"
            ),
            array(
                "name" => "agent",
                "title" => "委托方"
            ),
        );

        $assign = array(
            'shiplist' => $shiplist,
            'listship' => $listship,
            'list' => $list,
            'personalitylist' => $personalitylist,
            'page' => $page->show()
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 新增作业
     */
    public function addresult()
    {
        if (I('post.shipid')) {
            //添加数据
            $data = I('post.');
            $data['time'] = time();
            $data['uid'] = $_SESSION['user_info']['id'];
            // 验证通过 可以进行其他数据操作
            $res = $this->db->addResult($data, $_SESSION['user_info']['id']);
            if ($res['code'] == '1') {
                echo ajaxReturn(array("state" => 1, 'message' => "成功"));
            } else {
                echo ajaxReturn(array("state" => $res['code'], 'message' => $res['msg']));
            }
        } else {
            echo ajaxReturn(array("state" => 2, 'message' => "船名为必填项"));
        }
    }

    /**
     * 判断作业是否开始,没开始返回作业内容
     */
    public function is_start()
    {
        //获取用户的船舶列表id
        $user = new \Common\Model\UserModel();
//            $usermsg = $user->getUserOperationSeach($_SESSION['user_info']['id']);
//        $uid = $_SESSION['user_info']['id'];
//        $firmid = $user->getFieldById($uid, 'firmid');

        $ship = new \Common\Model\ShShipModel();
        $shiplist = $ship
            ->field('id,shipname')
            ->order('shipname asc')
            ->select();

        //获取作业信息
        $msg = $this->db
            ->where(array('id' => I('post.resultid')))->find();
        $personalitymsg = json_decode($msg['personality'], true);

        // 获取公司个性化字段
//            $firm = new \Common\Model\FirmModel();
//            $personality_id = $firm->getFieldById($firmid, 'personality');
//            $personality_id = json_decode($personality_id, true);
        $personalitylist = array();
//            $person = new \Common\Model\PersonalityModel();
//            foreach ($personality_id as $key => $value) {
//                $personalitylist[] = $person
//                    ->field('name,title')
//                    ->where(array('id' => $value))
//                    ->find();
//            }
        #todo 水尺计量的个性化字段是固定的，如果更改请同时更改此段代码
        $personalitylist = array(
            array(
                "name" => "voyage",
                "title" => "指令航次"
            ),
            array(
                "name" => "locationname",
                "title" => "作业地点"
            ),
            array(
                "name" => "start",
                "title" => "起运港口"
            ),
            array(
                "name" => "objective",
                "title" => "目的港口"
            ),
            array(
                "name" => "goodsname",
                "title" => "货物名称"
            ),
            array(
                "name" => "transport",
                "title" => "运单量"
            ),
            array(
                "name" => "number",
                "title" => "作业编号"
            ),
            array(
                "name" => "agent",
                "title" => "委托方"
            ),
        );
        $string = "";
        //判断指令有没有作业，
        $rl = M("sh_resultrecord");
        $re = $rl->where(array('resultid' => I('post.resultid')))->count();
        if ($re > 0) {
            $string .= "<p style='text-align: center;color: red;font-weight: bolder;background-color: #f2f9ff;'>该作业已经开始检验，更改航次信息可能导致和检验报告不一致，平台不承担任何责任，请谨慎更改</p>";
        }
        $string .= "<input type='hidden' name='id' id='id1' value='" . I('post.resultid') . "'><li><label>船名：</label><p><select name='shipid' id='shipid1' class=''><option value=''>请选择船名</option>";
        foreach ($shiplist as $key => $value) {
            if ($value['id'] == $msg['shipid']) {
                $select = "selected";
            } else {
                $select = '';
            }
            $string .= "<option value='" . $value['id'] . "' " . $select . ">" . $value['shipname'] . "</option>";
        }
        $string .= "</select></p></li>";

        foreach ($personalitylist as $key => $v) {
            $string .= "<li><label>" . $v['title'] . "：</label><p><input type='text'  type='text' name='" . $v['name'] . "' placeholder='请输入" . $v['title'] . "' class='i-box' id='" . $v['name'] . "1' value='" . $personalitymsg[$v['name']] . "'/></p></li>";
            // $string .= "<input type='text' name='".."' placeholder='请输入"$v['title']"'    ";
        }
        echo ajaxReturn(array("state" => 1, 'message' => "成功", 'content' => $string));
//        }
    }

    /**
     * 修改作业提交
     */
    public function editresult()
    {
        if (I('post.shipid')) {
            //添加数据
            $data = I('post.');
            $data['resultid'] = intval(I('post.id'));
            //如果作业已经结束则报错作业已完成 2029
            if($this->db->checkFinish($data['resultid'])) echo ajaxReturn(array("state" => 2029, 'message' => "作业已被结束"));
            $res = $this->db->editResult($data);
            if ($res['code'] == '1') {
                echo ajaxReturn(array("state" => 1, 'message' => "成功"));
            } else {
                echo ajaxReturn(array("state" => $res['code'], 'message' => $res['msg']));
            }
        } else {
            echo ajaxReturn(array("state" => 2, 'message' => "船名为必填项"));
        }
    }

    /**
     * 查看作业详情
     */
    public function msg()
    {
        if (IS_GET) {
            $user = new \Common\Model\UserModel();
            //获取水尺数据
            $where = array(
                'r.id' => I('get.resultid')
            );
            //查询作业数据
            $list = $this->db
                ->alias('r')
                ->field('r.*,s.ptwd,s.shipname,u.username,s.goodsname goodname,f.firmtype as ffirmtype')
                ->join('left join sh_ship s on r.shipid=s.id')
                ->join('left join user u on r.uid = u.id')
                ->join('left join firm f on u.firmid = f.id')
                ->where($where)
                ->find();

            // 个性化信息
            $personality = json_decode($list['personality'], true);
            $persona = new \Common\Model\PersonalityModel();
            $personality_array = array();
            // 检索个性化名称
            foreach ($personality as $key => $value) {
                $title = $persona->getFieldByName($key, 'title');
                $personality_array[] = array(
                    'name' => $key,
                    'title' => $title,
                    'value' => $value
                );
            }

            $qian = array();
            $hou = array();
            $resultlist = new \Common\Model\ShResultlistModel();
            $resultrecord = M("sh_resultrecord");
            $fontrecord = M('sh_forntrecord');


            $data_where = array('resultid' => $list['id'], 'solt' => 1);
            //获取压载水数据 Ballast water
            $qian['bw'] = $resultlist
                ->field(true)
                ->where($data_where)
                ->select();

            //获取排水量表数据
            $qian['table'] = $resultrecord
                ->field(true)
                ->where($data_where)
                ->find();

            // 获取水尺数据
            $qian['fornt'] = $fontrecord
                ->field(true)
                ->where($data_where)
                ->find();


            //获取作业后的数据
            $data_where['solt'] = 2;
            //获取压载水数据 Ballast water
            $hou['bw'] = $resultlist
                ->field(true)
                ->where($data_where)
                ->select();

            //获取排水量表数据
            $hou['table'] = $resultrecord
                ->field(true)
                ->where($data_where)
                ->find();
            // 获取水尺数据
            $hou['fornt'] = $fontrecord->field(true)->where($data_where)->find();

            $assign = array(
                'content' => $list,
                'qian' => $qian,
                'hou' => $hou,
                'personality' => $personality_array
            );

            $this->assign($assign);
            $this->display();
        } else {
            $this->error('未知错误');
        }
    }

    /**
     * 报表
     * */
    public function baobiao($resultid)
    {
        if (IS_GET) {
            $work = new \Common\Model\ShResultModel();
            $uid = $_SESSION['user_info']['id'];

            //获取水尺数据
            $where = array(
                'r.id' => $resultid,
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
            $list['verify'] = shGetReportErCode($list['id'], $list['uid']);

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

            $this->display();
        } else {
            $this->error('未知错误');
        }
    }

    /**
     * 评价界面
     */
    public function evaluate()
    {
        $uid = $_SESSION['user_info']['id'];
        if (IS_POST) {
            // 判断是否打分
            $grade = I('post.grade');
            if (empty($grade)) {
                $this->error('请评分！');
            }
            $data = array(
                'uid' => I('post.uid'),
                'id' => I('post.id'),
                'shipid' => I('post.shipid'),
                'grade' => I('post.grade'),
                'firmtype' => I('post.firmtype'),
                'content' => I('post.content'),
                'operater' => $uid
            );
            $res = $this->db->evaluate($data);
            if ($res['code'] == '1') {
                $this->success('评价成功');
            } else {
                $this->error($res['msg']);
            }
        } else {
            // 判断作业是否完成----电子签证
            $coun = M('electronic_visa')
                ->where(array('resultid' => I('get.resultid')))
                ->count();
            if ($coun > 0) {
                // 获取作业的数据：操作人、作业ID、登录人的公司类型、作业的船舶ID
                $user = new \Common\Model\UserModel();
                //获取水尺数据
                $where = array(
                    'r.id' => I('get.resultid')
                );
                //查询作业列表
                $list = $this->db
                    ->field('r.id,r.uid,r.shipid,f.firmtype as ffirmtype,r.grade1,r.grade2,r.evaluate1,r.evaluate2')
                    ->alias('r')
                    ->join('left join ship s on r.shipid=s.id')
                    ->join('left join user u on r.uid = u.id')
                    ->join('left join firm f on u.firmid = f.id')
                    ->where($where)
                    ->find();
                // 获取当前登陆用户的公司类型
                $a = $user
                    ->field('f.firmtype')
                    ->alias('u')
                    ->join('left join firm f on u.firmid = f.id')
                    ->where(array('u.id' => $uid))
                    ->find();
                $list['firmtype'] = $a['firmtype'];
                $assign = array(
                    'content' => $list,
                    'coun' => $coun
                );
                $this->assign($assign);
                $this->display();
            } else {
                $this->error('作业尚未完成，不可以评价', U('index'));
            }
        }
    }
}