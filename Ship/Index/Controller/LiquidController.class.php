<?php

namespace Index\Controller;

use Common\Controller\IndexBaseController;

/**
 * 液货船系统管理
 */
class LiquidController extends IndexBaseController
{
    // 定义数据表
    private $db;

    // 构造函数 实例化ResultModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\WorkModel();
    }

    /**
     * 列表
     */
    public function index()
    {
        $user = new \Common\Model\UserModel();
        $uid = $_SESSION['user_info']['id'];
        // 根据用户id获取用户的操作权限、查询权限
        $msg = $user->getUserOperationSeach($uid);

        // $firmid = $user->getFieldById($uid,'firmid');

        if ($msg['search_jur'] == '') {
            // 查询权限为空时，查看所有操作权限之内的作业
            $where = " r.uid ='$uid'";

            if ($msg['operation_jur'] == '') {
                $operation_jur = "all";
            } else {
                $operation_jur = $msg['operation_jur'];
                $where .= " and r.shipid in (" . $operation_jur . ")";
            }
        } else {
            $where = " r.shipid in (" . $msg['search_jur'] . ")";
        }

        if ($msg['look_other'] == '1') {
            $where .= " and u.firmid='" . $msg['firmid'] . "'";
        }

        if (I('post.shipid')) {
            if ($msg['search_jur'] !== '') {
                // 判断提交的船是否在权限之内
                if (!in_array(I('post.shipid'), $msg['search_jur_array'])) {
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
//        $page = new \Org\Nx\Page($count, 20);

        $list = $this->db
            ->field('r.personality,r.time,r.weight,s.shipname,r.id,f.id as firmid,f.firmtype,r.uid,r.grade1,r.grade2,r.shipid')
            ->alias('r')
            ->join('left join ship s on s.id=r.shipid')
            ->join('left join user u on u.id=r.uid')
            ->join('left join firm f on f.id = u.firmid')
            ->where($where)
//            ->limit($page->firstRow, $page->listRows)
            ->order('r.id desc')
            ->select();

        // 获取当前登陆用户的公司类型
        $a = $user
            ->field('f.firmtype,f.id')
            ->alias('u')
            ->join('left join firm f on u.firmid = f.id')
            ->where(array('u.id' => $uid))
            ->find();

        $ship = new \Common\Model\ShipModel();
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
            ->where(array('id' => array('IN', $msg['operation_jur_array']), 'del_sign' => 1))
            ->order('shipname asc')
            ->select();

        // 获取公司个性化字段
        $firm = new \Common\Model\FirmModel();
        $personality_id = $firm->getFieldById($msg['firmid'], 'personality');
        $personality_id = json_decode($personality_id, true);
        $personalitylist = array();
        $person = new \Common\Model\PersonalityModel();
        foreach ($personality_id as $key => $value) {
            $personalitylist[] = $person
                ->field('id,name,title')
                ->where(array('id' => $value))
                ->find();
        }

        $assign = array(
            'shiplist' => $shiplist,
            'listship' => $listship,
            'list' => $list,
            'personalitylist' => $personalitylist,
//            'page' => $page->show()
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 新增作业
     */
    public function addresult()
    {
        if (I('post.shipid') and I('post.voyage')) {
            //添加数据
            $data = I('post.');
            $data['time'] = time();
            $data['uid'] = $_SESSION['user_info']['id'];
            // 验证通过 可以进行其他数据操作
            $res = $this->db->addResult($data, $_SESSION['user_info']['id']);
            if ($res['code'] == '1') {
                echo ajaxReturn(array("code" => 1, 'message' => "成功"));
            } else {
                echo ajaxReturn(array("code" => $res['code'], 'error' => $res['msg']));
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'error' => "船名为必填项"));
        }
    }

//    /**
//     * 判断作业是否开始,没开始返回作业内容
//     */
//    public function is_start()
//    {
//        //判断指令有没有作业，
//        $rl = new \Common\Model\ResultlistModel();
//        $re = $rl->where(array('resultid' => I('post.resultid')))->count();
//        if ($re > 0) {
//            echo ajaxReturn(array("state" => 2, 'message' => "该指令已作业，不能修改!"));
//        } else {
//            //获取用户的船舶列表id
//            $user = new \Common\Model\UserModel();
//            $usermsg = $user->getUserOperationSeach($_SESSION['user_info']['id']);
//
//            $ship = new \Common\Model\ShipModel();
//            $shiplist = $ship
//                ->field('id,shipname')
//                ->where(array('id' => array('IN', $usermsg['operation_jur_array'])))
//                ->order('shipname asc')
//                ->select();
//
//            //获取作业信息
//            $msg = $this->db
//                ->where(array('id' => I('post.resultid')))->find();
//            $personalitymsg = json_decode($msg['personality'], true);
//
//            // 获取公司个性化字段
//            $firm = new \Common\Model\FirmModel();
//            $personality_id = $firm->getFieldById($usermsg['firmid'], 'personality');
//            $personality_id = json_decode($personality_id, true);
//            $personalitylist = array();
//            $person = new \Common\Model\PersonalityModel();
//            foreach ($personality_id as $key => $value) {
//                $personalitylist[] = $person
//                    ->field('name,title')
//                    ->where(array('id' => $value))
//                    ->find();
//            }
//            $string = "<input type='hidden' name='id' id='id1' value='" . I('post.resultid') . "'><li><label>船名：</label><p><select name='shipid' id='shipid1' class=''><option value=''>请选择船名</option>";
//            foreach ($shiplist as $key => $value) {
//                if ($value['id'] == $msg['shipid']) {
//                    $select = "selected";
//                } else {
//                    $select = '';
//                }
//                $string .= "<option value='" . $value['id'] . "' " . $select . ">" . $value['shipname'] . "</option>";
//            }
//            $string .= "</select></p></li>";
//
//            foreach ($personalitylist as $key => $v) {
//                $string .= "<li><label>" . $v['title'] . "：</label><p><input type='text'  type='text' name='" . $v['name'] . "' placeholder='请输入" . $v['title'] . "' class='i-box' id='" . $v['name'] . "1' value='" . $personalitymsg[$v['name']] . "'/></p></li>";
//                // $string .= "<input type='text' name='".."' placeholder='请输入"$v['title']"'    ";
//            }
//            echo ajaxReturn(array("state" => 1, 'message' => "成功", 'content' => $string));
//        }
//    }

    /**
     * 判断作业是否开始,没开始返回作业内容
     */
    public function is_start()
    {
        //判断指令有没有作业，
        $rl = new \Common\Model\ResultlistModel();
        $re = $rl->where(array('resultid' => I('post.resultid')))->count();
        if ($re > 0) {
            echo ajaxReturn(array("code" => 2, 'message' => "该指令已作业，不能修改!"));
        } else {
            //获取用户的船舶列表id
            $user = new \Common\Model\UserModel();
            $usermsg = $user->getUserOperationSeach($_SESSION['user_info']['id']);

            $ship = new \Common\Model\ShipModel();
            $shiplist = $ship
                ->field('id,shipname')
                ->where(array('id' => array('IN', $usermsg['operation_jur_array'])))
                ->order('shipname asc')
                ->select();

            //获取作业信息
            $msg = $this->db
                ->where(array('id' => I('post.resultid')))->find();
            $personalitymsg = json_decode($msg['personality'], true);

            // 获取公司个性化字段
            $firm = new \Common\Model\FirmModel();
            $personality_id = $firm->getFieldById($usermsg['firmid'], 'personality');
            $personality_id = json_decode($personality_id, true);
            $personalitylist = array();
            $person = new \Common\Model\PersonalityModel();
            foreach ($personality_id as $key => $value) {
                $personalitylist[] = $person
                    ->field('name,title')
                    ->where(array('id' => $value))
                    ->find();
            }
//            foreach ($shiplist as $key => $value) {
//                if ($value['id'] == $msg['shipid']) {
//                    $select = "selected";
//                } else {
//                    $select = '';
//                }
//            }

//            foreach ($personalitylist as $key => $v) {
//                $string .= "<li><label>" . $v['title'] . "：</label><p><input type='text'  type='text' name='" . $v['name'] . "' placeholder='请输入" . $v['title'] . "' class='i-box' id='" . $v['name'] . "1' value='" . $personalitymsg[$v['name']] . "'/></p></li>";
                // $string .= "<input type='text' name='".."' placeholder='请输入"$v['title']"'    ";
//            }
            $assign = array(
                'personalitymsg' =>$personalitymsg,
                'personalitylist' =>$personalitylist,
                'shiplist' =>$shiplist,
            );
            echo ajaxReturn(array("code" => 1, 'message' => "成功", 'content' => $assign));
        }
    }

    /**
     * 修改作业提交
     */
    public function editresult()
    {
        if (I('post.shipid') and I('post.id') and I('post.voyage')) {
            //添加数据
            $data = I('post.');
            $data['resultid'] = I('post.id');
            $res = $this->db->editResult($data);
            if ($res['code'] == '1') {
                echo ajaxReturn(array("code" => 1, 'message' => "成功"));
            } else {
                echo ajaxReturn(array("code" => $res['code'], 'message' => $res['msg']));
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'message' => "船名为必填项"));
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
                ->field('r.*,s.shipname,u.username,s.goodsname goodname,f.firmtype as ffirmtype,e.img as eimg')
                ->join('left join ship s on r.shipid=s.id')
                ->join('left join user u on r.uid = u.id')
                ->join('left join firm f on u.firmid = f.id')
                ->join('left join electronic_visa e on e.resultid = r.id')
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
            // 获取船驳所有舱列表
            $cabin = new \Common\Model\CabinModel();
            $cabinlist = $cabin
                ->field('id,cabinname')
                ->where(array('shipid' => $list['shipid']))
                ->order('order_number asc')
                ->select();

            $qian = array();
            $hou = array();
            $resultlist = new \Common\Model\ResultlistModel();
            foreach ($cabinlist as $key => $value) {
                $data = $resultlist
                    ->where(array('resultid' => $list['id'], 'cabinid' => $value['id']))
                    ->select();

                if (empty($data)) {
                    $qian[] = array(
                        'cabinname' => $value['cabinname'],
                        'sounding' => '',
                        'ullage' => '',
                        'listcorrection' => '',
                        'temperature' => '',
                        'standardcapacity' => '',
                        'volume' => '',
                        'expand' => '',
                        'correntkong' => '',
                        'cabinweight' => '',
                        'ullageimg' => array(),
                        'soundingimg' => array(),
                        'temperatureimg' => array()
                    );
                    $hou[] = array(
                        'cabinname' => $value['cabinname'],
                        'sounding' => '',
                        'ullage' => '',
                        'listcorrection' => '',
                        'temperature' => '',
                        'standardcapacity' => '',
                        'volume' => '',
                        'expand' => '',
                        'correntkong' => '',
                        'cabinweight' => '',
                        'ullageimg' => array(),
                        'soundingimg' => array(),
                        'temperatureimg' => array()
                    );
                } else if (count($data) == '1') {
                    // 一条作业数据只会是作业前数据
                    // 循环获取舱列表数据
                    foreach ($data as $k => $v) {
                        // 获取作业照片
                        $listimg = M('resultlist_img')
                            ->where(array('resultlist_id' => $v['id']))
                            ->select();
                        $ullageimg = array();
                        $soundingimg = array();
                        $temperatureimg = array();
                        if (!empty($listimg)) {
                            foreach ($listimg as $ke => $valu) {
                                if ($valu['types'] == '1') {
                                    $ullageimg[] = $valu['img'];
                                } else if ($valu['types'] == '2') {
                                    $soundingimg[] = $valu['img'];
                                } else if ($valu['types'] == '3') {
                                    $temperatureimg[] = $valu['img'];
                                }
                            }
                        }
                    }

                    $qian[] = array(
                        'cabinname' => $value['cabinname'],
                        'sounding' => $v['sounding'],
                        'ullage' => $v['ullage'],
                        'listcorrection' => $v['listcorrection'],
                        'temperature' => $v['temperature'],
                        'standardcapacity' => $v['standardcapacity'],
                        'volume' => $v['volume'],
                        'expand' => $v['expand'],
                        'correntkong' => $v['correntkong'],
                        'cabinweight' => $v['cabinweight'],
                        'ullageimg' => $ullageimg,
                        'soundingimg' => $soundingimg,
                        'temperatureimg' => $temperatureimg
                    );

                    $hou[] = array(
                        'cabinname' => $value['cabinname'],
                        'sounding' => '',
                        'ullage' => '',
                        'listcorrection' => '',
                        'temperature' => '',
                        'standardcapacity' => '',
                        'volume' => '',
                        'expand' => '',
                        'correntkong' => '',
                        'cabinweight' => '',
                        'ullageimg' => array(),
                        'soundingimg' => array(),
                        'temperatureimg' => array()
                    );
                } else if (count($data) == '2') {
                    // 循环获取舱列表数据
                    foreach ($data as $k => $v) {
                        // 获取作业照片
                        $listimg = M('resultlist_img')
                            ->where(array('resultlist_id' => $v['id']))
                            ->select();

                        $ullageimg = array();
                        $soundingimg = array();
                        $temperatureimg = array();
                        if (!empty($listimg)) {
                            foreach ($listimg as $ke => $valu) {
                                if ($valu['types'] == '1') {
                                    $ullageimg[] = $valu['img'];
                                } else if ($valu['types'] == '2') {
                                    $soundingimg[] = $valu['img'];
                                } else if ($valu['types'] == '3') {
                                    $temperatureimg[] = $valu['img'];
                                }
                            }
                        }
                        if ($v['solt'] == '1') {
                            $qian[] = array(
                                'cabinname' => $value['cabinname'],
                                'sounding' => $v['sounding'],
                                'ullage' => $v['ullage'],
                                'listcorrection' => $v['listcorrection'],
                                'temperature' => $v['temperature'],
                                'standardcapacity' => $v['standardcapacity'],
                                'volume' => $v['volume'],
                                'expand' => $v['expand'],
                                'correntkong' => $v['correntkong'],
                                'cabinweight' => $v['cabinweight'],
                                'ullageimg' => $ullageimg,
                                'soundingimg' => $soundingimg,
                                'temperatureimg' => $temperatureimg
                            );
                        } else {
                            $hou[] = array(
                                'cabinname' => $value['cabinname'],
                                'sounding' => $v['sounding'],
                                'ullage' => $v['ullage'],
                                'listcorrection' => $v['listcorrection'],
                                'temperature' => $v['temperature'],
                                'standardcapacity' => $v['standardcapacity'],
                                'volume' => $v['volume'],
                                'expand' => $v['expand'],
                                'correntkong' => $v['correntkong'],
                                'cabinweight' => $v['cabinweight'],
                                'ullageimg' => $ullageimg,
                                'soundingimg' => $soundingimg,
                                'temperatureimg' => $temperatureimg
                            );
                        }


                    }
                }
            }

            $uid = $_SESSION['user_info']['id'];
            $where1 = array('re.resultid' => $list['id']);

            $resultmsg = $resultlist
                ->alias('re')
                ->field('re.*,c.cabinname')
                ->join('left join cabin c on c.id = re.cabinid')
                ->where($where1)
                ->order('re.solt asc,re.cabinid asc')
                ->select();
            //以舱区分数据（）
            foreach ($resultmsg as $k => $v) {
                $result[$v['cabinid']][] = $v;
            }
            // 获取水尺数据
            $shuichi = M('forntrecord')->where(array('resultid' => $list['id']))->select();
            foreach ($shuichi as $key => $value) {
                $aa = array('forntleft' => $value['forntleft'], 'afterleft' => $value['afterleft']);
                if ($value['solt'] == '1') {
                    $list['qian'] = $aa;
                } else {
                    $list['hou'] = $aa;
                }
            }

            // 获取水尺照片
            $data = M('fornt_img')
                ->where(array('result_id' => $list['id']))
                ->select();
            if (empty($data)) {
                $list['firstfiles1'] = array();
                $list['tailfiles1'] = array();
                $list['firstfiles2'] = array();
                $list['tailfiles2'] = array();
            } else {
                foreach ($data as $key => $value) {
                    if ($value['solt'] == '1') {
                        if ($value['types'] == '1') {
                            $list['firstfiles1'][] = $value['img'];
                        } else {
                            $list['tailfiles1'][] = $value['img'];
                        }
                    } else {
                        if ($value['types'] == '1') {
                            $list['firstfiles2'][] = $value['img'];
                        } else {
                            $list['tailfiles2'][] = $value['img'];
                        }
                    }


                }
                if (empty($list['firstfiles1'])) {
                    $list['firstfiles1'] = array();
                }
                if (empty($list['tailfiles1'])) {
                    $list['tailfiles1'] = array();
                }
                if (empty($list['firstfiles2'])) {
                    $list['firstfiles2'] = array();
                }
                if (empty($list['tailfiles2'])) {
                    $list['tailfiles2'] = array();
                }
            }
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
    public function baobiao()
    {
        if (IS_GET) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            //获取水尺数据
            $where = array(
                'r.id' => I('get.resultid')
            );
            //查询作业列表
            $list = $this->db
                ->alias('r')
                ->field('r.*,s.shipname,s.is_guanxian,s.suanfa,u.username,r.qianchi,r.houchi,s.goodsname goodname,f.firmtype as ffirmtype,e.img as eimg,s.number as ship_number')
                ->join('left join ship s on r.shipid=s.id')
                ->join('left join user u on r.uid = u.id')
                ->join('left join firm f on u.firmid = f.id')
                ->join('left join electronic_visa e on e.resultid = r.id')
                ->where($where)
                ->find();
            $list['verify'] = getReportErCode($list['id'], $list['uid']);
            // 获取当前登陆用户的公司类型
            $uid = $_SESSION['user_info']['id'];
            $map = array(
                'u.id' => $uid
            );
            $a = $user
                ->field('f.firmtype')
                ->alias('u')
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