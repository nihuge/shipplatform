<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;
use Think\Exception;

/**
 *    船舱管理
 * 2018.3.22
 * */
class CabinController extends AdminBaseController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\CabinModel();
    }

    /**
     * 舱列表
     */
    public function index()
    {
        $where[] = '1';
        if (I('get.shipid')) {
            $where['c.shipid'] = I('get.shipid');
        }

        $count = $this->db
            ->alias('c')
            ->field('s.shipname')
            ->where($where)
            ->count();
        $per = 24;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;

        $data = $this->db
            ->field('c.id,c.cabinname,c.altitudeheight,c.dialtitudeheight,c.bottom_volume,c.bottom_volume_di,c.pipe_line,c.shipid,s.shipname,s.suanfa,s.tankcapacityshipid,s.rongliang,s.rongliang_1,s.zx,s.zx_1,s.tripbystern,s.trimcorrection,s.trimcorrection1')
            ->alias('c')
            ->join('left join ship s on s.id=c.shipid')
            ->where($where)
            ->order('c.shipid desc,c.id asc')
            ->limit($begin, $per)
            ->select();
//        order by id DESC
        foreach ($data as $key => $value) {
            if ($value['suanfa'] == 'a') {
                $data[$key]['zx'] = "";
                $data[$key]['zx_1'] = "";
                $data[$key]['rongliang'] = "";
                $data[$key]['rongliang_1'] = "";
            } elseif ($value['suanfa'] == 'b') {
                $data[$key]['rongliang_1'] = "";
                $data[$key]['zx_1'] = "";
                $data[$key]['tankcapacityshipid'] = "";
            } elseif ($value['suanfa'] == 'c') {
                $data[$key]['tankcapacityshipid'] = "";
            } elseif ($value['suanfa'] == 'd') {
                $data[$key]['rongliang'] = "";
                $data[$key]['rongliang_1'] = "";
                $data[$key]['tankcapacityshipid'] = "";
            }

            //带纵倾刻度的容量表的前后数据
            if ($value['tankcapacityshipid'] != "") {
                try {
                    $table = M($value['tankcapacityshipid']);
                    $title = array('sounding' => "实高", 'ullage' => "空高");
                    $title = array_merge($title, json_decode($value['tripbystern'], true));
                    $last_array = $table->where(array('cabinid' => $value['id']))->order('id desc')->find();
                    $first_array = $table->where(array('cabinid' => $value['id']))->order('id asc')->find();
                    $tip_txt = "<table style=\'width:400px\'><thead>";
                    foreach ($title as $k1 => $v1) {
                        $tip_txt .= "<th>" . $v1 . "</th>";
                    }
                    $tip_txt .= "</thead><tbody><tr>";
                    $need_report = "<tr>";
                    $top_txt = "<tr>";
                    $bottom_txt = "<tr>";
                    foreach ($title as $k2 => $v2) {
                        $top_txt .= "<td>" . $first_array[$k2] . "</td>";
                        $bottom_txt .= "<td>" . $last_array[$k2] . "</td>";
                        $need_report .= "<td>....</td>";
                    }
                    $need_report .= "</tr>";
                    $top_txt .= "</tr>";
                    $bottom_txt .= "</tr>";
                    $tip_txt .= $top_txt . $need_report . $need_report . $need_report . $bottom_txt;
                    $tip_txt .= "</tbody></table>";
                    //获取总行数
                    $rows = $table->where(array('cabinid' => $value['id']))->count();
                    //获取总页数
                    $pages = ceil($rows / 50);
                    $tip_txt .= "<p style=\'text-align: center\'><span>- - - 共：" . $rows . "行," . $pages . "页 - - -</span></p>";
                } catch (Exception $e) {
                    $tip_txt = "数据表不存在或发生错误";
                }
                $data[$key]['tankcapacityshipid_tip'] = $tip_txt;
            }
            //容量纵修表数据
            if ($value['zx'] != "") {
                try {
                    $table = M($value['zx']);
                    $title = array('sounding' => "实高", 'ullage' => "空高");
                    $title = array_merge($title, json_decode($value['trimcorrection'], true));

                    $last_array = $table->where(array('cabinid' => $value['id']))->order('id desc')->find();
                    $first_array = $table->where(array('cabinid' => $value['id']))->order('id asc')->find();

                    $tip_txt = "<table style=\'width:400px\'><thead>";
                    foreach ($title as $k1 => $v1) {
                        $tip_txt .= "<th>" . $v1 . "</th>";
                    }
                    $tip_txt .= "</thead><tbody><tr>";
                    $need_report = "<tr>";
                    $top_txt = "<tr>";
                    $bottom_txt = "<tr>";
                    foreach ($title as $k2 => $v2) {
                        $top_txt .= "<td>" . $first_array[$k2] . "</td>";
                        $bottom_txt .= "<td>" . $last_array[$k2] . "</td>";
                        $need_report .= "<td>....</td>";
                    }
                    $need_report .= "</tr>";
                    $top_txt .= "</tr>";
                    $bottom_txt .= "</tr>";
                    $tip_txt .= $top_txt . $need_report . $need_report . $need_report . $bottom_txt;
                    $tip_txt .= "</tbody></table>";
                    //获取总行数
                    $rows = $table->where(array('cabinid' => $value['id']))->count();
                    //获取总页数
                    $pages = ceil($rows / 50);
                    $tip_txt .= "<p style=\'text-align: center\'><span>- - - 共：" . $rows . "行," . $pages . "页 - - -</span></p>";
                } catch (Exception $e) {
                    $tip_txt = "数据表不存在或发生错误";
                }

                $data[$key]['zx_tip'] = $tip_txt;
            }
            //底量纵修表前后数据
            if ($value['zx_1'] != "") {
                try {
                    $table = M($value['zx_1']);
                    $title = array('sounding' => "实高", 'ullage' => "空高");
                    $title = array_merge($title, json_decode($value['trimcorrection1'], true));
                    $last_array = $table->where(array('cabinid' => $value['id']))->order('id desc')->find();
                    $first_array = $table->where(array('cabinid' => $value['id']))->order('id asc')->find();
                    $tip_txt = "<table style=\'width:400px\'><thead>";
                    foreach ($title as $k1 => $v1) {
                        $tip_txt .= "<th>" . $v1 . "</th>";
                    }
                    $tip_txt .= "</thead><tbody><tr>";
                    $need_report = "<tr>";
                    $top_txt = "<tr>";
                    $bottom_txt = "<tr>";
                    foreach ($title as $k2 => $v2) {
                        $top_txt .= "<td>" . $first_array[$k2] . "</td>";
                        $bottom_txt .= "<td>" . $last_array[$k2] . "</td>";
                        $need_report .= "<td>....</td>";
                    }
                    $need_report .= "</tr>";
                    $top_txt .= "</tr>";
                    $bottom_txt .= "</tr>";
                    $tip_txt .= $top_txt . $need_report . $need_report . $need_report . $bottom_txt;
                    $tip_txt .= "</tbody></table>";
                    //获取总行数
                    $rows = $table->where(array('cabinid' => $value['id']))->count();
                    //获取总页数
                    $pages = ceil($rows / 50);
                    $tip_txt .= "<p style=\'text-align: center\'><span>- - - 共：" . $rows . "行," . $pages . "页 - - -</span></p>";
                } catch (Exception $e) {
                    $tip_txt = "数据表不存在或发生错误";
                }
                $data[$key]['zx_1_tip'] = $tip_txt;
            }
            //底量纵修表前后数据
            if ($value['rongliang'] != "") {
                try {
                    $table = M($value['rongliang']);
                    $title = array('sounding' => "实高", 'ullage' => "空高", "capacity" => "容量", "diff" => "厘米容量");
                    $last_array = $table->where(array('cabinid' => $value['id']))->order('id desc')->find();
                    $first_array = $table->where(array('cabinid' => $value['id']))->order('id asc')->find();
                    $tip_txt = "<table style=\'width:400px\'><thead>";
                    foreach ($title as $k1 => $v1) {
                        $tip_txt .= "<th>" . $v1 . "</th>";
                    }
                    $tip_txt .= "</thead><tbody><tr>";
                    $need_report = "<tr>";
                    $top_txt = "<tr>";
                    $bottom_txt = "<tr>";
                    foreach ($title as $k2 => $v2) {
                        $top_txt .= "<td>" . $first_array[$k2] . "</td>";
                        $bottom_txt .= "<td>" . $last_array[$k2] . "</td>";
                        $need_report .= "<td>....</td>";
                    }
                    $need_report .= "</tr>";
                    $top_txt .= "</tr>";
                    $bottom_txt .= "</tr>";
                    $tip_txt .= $top_txt . $need_report . $need_report . $need_report . $bottom_txt;
                    $tip_txt .= "</tbody></table>";
                    //获取总行数
                    $rows = $table->where(array('cabinid' => $value['id']))->count();
                    //获取总页数
                    $pages = ceil($rows / 50);
                    $tip_txt .= "<p style=\'text-align: center\'><span>- - - 共：" . $rows . "行," . $pages . "页 - - -</span></p>";
                } catch (Exception $e) {
                    $tip_txt = "数据表不存在或发生错误";
                }

                $data[$key]['rongliang_tip'] = $tip_txt;
            }
            //底量纵修表前后数据
            if ($value['rongliang_1'] != "") {
                try {
                    $table = M($value['rongliang_1']);
                    $title = array('sounding' => "实高", 'ullage' => "空高", "capacity" => "容量", "diff" => "厘米容量");
                    $last_array = $table->where(array('cabinid' => $value['id']))->order('id desc')->find();
                    $first_array = $table->where(array('cabinid' => $value['id']))->order('id asc')->find();
                    $tip_txt = "<table style=\'width:400px\'><thead>";
                    foreach ($title as $k1 => $v1) {
                        $tip_txt .= "<th>" . $v1 . "</th>";
                    }
                    $tip_txt .= "</thead><tbody><tr>";
                    $need_report = "<tr>";
                    $top_txt = "<tr>";
                    $bottom_txt = "<tr>";
                    foreach ($title as $k2 => $v2) {
                        $top_txt .= "<td>" . $first_array[$k2] . "</td>";
                        $bottom_txt .= "<td>" . $last_array[$k2] . "</td>";
                        $need_report .= "<td>....</td>";
                    }
                    $need_report .= "</tr>";
                    $top_txt .= "</tr>";
                    $bottom_txt .= "</tr>";
                    $tip_txt .= $top_txt . $need_report . $need_report . $need_report . $bottom_txt;
                    $tip_txt .= "</tbody></table>";
                    //获取总行数
                    $rows = $table->where(array('cabinid' => $value['id']))->count();
                    //获取总页数
                    $pages = ceil($rows / 50);
                    $tip_txt .= "<p style=\'text-align: center\'><span>- - - 共：" . $rows . "行," . $pages . "页 - - -</span></p>";
                } catch (Exception $e) {
                    $tip_txt = "数据表不存在或发生错误";
                }
                $data[$key]['rongliang_1_tip'] = $tip_txt;
            }
        }

        //获取船列表
        $ship = new \Common\Model\ShipModel();
        $shiplist = $ship
            ->field('id,shipname')
            ->order("shipname desc")
            ->select();
        $assign = array(
            'data' => $data,
            'page' => $page,
            'shiplist' => $shiplist
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 舱修改
     */
    public function edit()
    {
        if (IS_POST) {
            //判断同一条船不能有重复的舱名
            $where = array(
                'shipid' => I('post.shipid'),
                'cabinname' => I('post.cabinname'),
                'id' => array('NEQ', I('post.id'))
            );
            $count = $this->db->where($where)->count();
            if ($count > 0) {
                $this->error('该船已存在该舱名');
                exit;
            }
            $data = I('post.');
            //不允许更改船
            unset($data['shipid']);
            // 对数据进行验证
            if (!$this->db->create($data)) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $this->error($this->db->getError());
            } else {
                // 验证通过 可以进行其他数据操作
                $map = array(
                    'id' => $data['id']
                );

                unset($data['id']);
                $res = $this->db->editData($map, $data);
                if ($res !== false) {
                    $this->success('修改成功！', U('index', array("shipid" => intval(I('post.shipid')))));
                } else {
                    $this->error('修改失败！');
                }
            }
        } else {
            //获取ID获取容量的信息
            $msg = $this->db
                ->where(array('id' => I('get.id')))
                ->find();
            $ship = new \Common\Model\ShipModel();
            $shiplist = $ship->field('id,shipname')->select();
            $assign = array(
                'msg' => $msg,
                'shiplist' => $shiplist
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 舱新增
     */
    public function add()
    {
        if (IS_POST) {
            //判断同一条船不能有重复的舱名
            $data = array();
            $names = array();
            foreach (I('post.data') as $key => $value) {
                $where = array(
                    'shipid' => I('post.shipid'),
                    'cabinname' => $value['cabinname']
                );

                $count = $this->db->where($where)->count();
                if ($count > 0) {
                    $this->error('该船已存在该舱名');
                    exit;
                }
                $names[] = $value['cabinname'];
                $value['shipid'] = I('post.shipid');
                $data[] = $value;
            }

            // 判断提交的舱名是否有重复
            $repeat_arr = FetchRepeatMemberInArray($names);
            if ($repeat_arr) {
                $this->error('提交的舱名存在重复');
                exit;
            }
            M()->startTrans();
            foreach ($data as $key => $value) {
                // 对数据进行验证
                if (!$this->db->create($value)) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    $this->error($this->db->getError());
                } else {
                    // 验证通过 可以进行其他数据操作
                    $res = $this->db->addData($value);
                    if ($res) {

                    } else {
                        M()->rollback();
                        $this->error('新增失败！');
                        die;
                    }
                }
            }
            M()->commit();
            $this->success('新增成功！', U('index'));
        } else {
            // 获取船列表
            $ship = new \Common\Model\ShipModel();
            $shiplist = $ship
                ->field('id,shipname,cabinnum,suanfa')
                ->order('shipname asc')
                ->select();
            // 去除
            foreach ($shiplist as $key => $value) {
                $num = $this->db->where(array('shipid' => $value['id']))->count();
                if ($num == $value['cabinnum']) {
                    unset($shiplist[$key]);
                } else {
                    $shiplist[$key]['cabinnum'] = $value['cabinnum'] - $num;
                }
            }
            // p($shiplist);die;
            $assign = array(
                'shiplist' => $shiplist
            );
            $this->assign($assign);
            $this->display();
        }
    }

    public function match_cabin()
    {
        $orgin_txt_di = I('post.di_txt');
        $orgin_txt_rong = I('post.rong_txt');
        $shipid = I('post.shipid');
        $has_diliang = I('post.has_diliang');//是否有底量列
        $ship = new \Common\Model\ShipFormModel();
        $is_diliang = "";

        if ($orgin_txt_rong and $has_diliang and $shipid) {
            $shipinfo = $ship->field('is_diliang,cabinnum')->where(array('id' => $shipid))->find();
            if ($shipinfo['is_diliang'] == 1) {
                if (!$orgin_txt_di) $this->error("请上传底量书信息");
                $is_diliang = '1';
            }

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
            $re2 = '/((?:[左右]+\.[\d]+|[PS\.LO]{6})(?:[ PS\.\d]*?)) ([\d]+) ([\d]+)/m';
            //得到结果
            preg_match_all($re2, $height_txt_rong, $matches_height_data_rong, PREG_SET_ORDER, 0);

            $re3 = '/单位\(Unit\)：m3([\S\s]*?)总计/m';
            preg_match_all($re3, $orgin_txt_rong, $matches_pipe_rong, PREG_SET_ORDER, 0);

            //匹配到了以后，将数据的异常特征处理掉
            $pipe_txt_rong = $matches_pipe_rong[0][1];

            //去除左右污油舱的异常特征
            $pipe_txt_rong = preg_replace("/[左右]+污油舱 /", "", $pipe_txt_rong);
//        exit($height_txt);
            //去除P.SLOP中间有换行符的问题
            $pipe_txt_rong = preg_replace("/P\.[\r\n]+SLOP/", "P.SLOP", $pipe_txt_rong);
            //将表格中---的符号换成0.000
            $pipe_txt_rong = preg_replace("/\-+([ \r\n]+)/", "0.000$1", $pipe_txt_rong);
//        echo $pipe_txt_di;
            $re4 = '/((?:[左右]+\.[\d]+|[PS\.LO]{6}) ?(?:[PS][\.\d]{0,4})?) ([\d\. \-]+)/m';
            preg_match_all($re4, $pipe_txt_rong, $matches_pipe_data_rong, PREG_SET_ORDER, 0);

            if ($is_diliang == '1') {
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
                            'pipe_line' => $v3['pipe'],
                        );
                        array_push($res, $data);
                    }
                }
            }

            $form_html = "<tr><td rowspan=\"2\">舱名</td><td colspan=\"2\">基准高度(H)</td><td colspan=\"2\">底量(D)</td><td rowspan=\"2\">管线容量</td></tr><tr><td>容量表</td><td>底量表</td><td>容量表</td><td>底量表</td></tr>";
//            return $res;

            foreach ($res as $k4 => $v4) {
                if ($is_diliang == '1') {
                    // pipe_line
                    $form_html .= '<tr><td><input type="text" id="form-field-1" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][cabinname]"  value="' . $v4['cabinname'] . '" tabindex="' . ($shipinfo['cabinnum'] * 0 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][altitudeheight]" value="' . ($v4['altitudeheight'] / 1000) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 1 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" name="data[' . $k4 . '][dialtitudeheight]" value="' . ($v4['dialtitudeheight'] / 1000) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 2 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][bottom_volume]" value="' . $v4['bottom_volume'] . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 3 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" name="data[' . $k4 . '][bottom_volume_di]" value="' . $v4['bottom_volume_di'] . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 4 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][pipe_line]" value="' . $v4['pipe_line'] . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 5 + $k4) . '"/></td></tr>';
                } else {
                    // pipe_line
                    $form_html .= '<tr><td><input type="text" id="form-field-1" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][cabinname]" value="' . $v4['cabinname'] . '" tabindex="' . ($shipinfo['cabinnum'] * 0 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][altitudeheight]" value="' . ($v4['altitudeheight'] / 1000) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 1 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" name="data[' . $k4 . '][dialtitudeheight]" value="' . ($v4['dialtitudeheight'] / 1000) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 2 + $k4) . '" disabled="disabled"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][bottom_volume]" value="' . $v4['bottom_volume'] . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 3 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" name="data[' . $k4 . '][bottom_volume_di]" value="' . $v4['bottom_volume_di'] . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 4 + $k4) . '" disabled="disabled"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][pipe_line]" value="' . $v4['pipe_line'] . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 5 + $k4) . '"/></td></tr>';
                }
            }

            $form_html .= '<tr><td colspan=6 style="text-align: center;"><input type="submit" name="sub" value="提交" class="btn btn-primary" ></td></tr>';

            $this->ajaxReturn(array('state' => $ship->ERROR_CODE_COMMON['SUCCESS'], 'content' => $form_html, 'cabinnum' => count($res)));
        } else {
            $this->error("请上传容量书信息");
        }
    }


    /**
     * 批量修改舱信息
     */
    public function batch_edit()
    {
        $cabin = new \Common\Model\CabinModel();

        if (IS_POST) {
            $shipid = intval(I("post.shipid"));
            $datas = I('post.data');
            //事务开启
            M()->startTrans();
            //循环获取数据
            foreach ($datas as $key => $value) {
                //构建条件
                $where = array('id' => $value['id']);
                //构建修改数据
                $edit_data = array(
                    'cabinname' => $value['cabinname'],
                    'altitudeheight' => $value['altitudeheight'],
                    'dialtitudeheight' => $value['dialtitudeheight'],
                    'bottom_volume' => $value['bottom_volume'],
                    'bottom_volume_di' => $value['bottom_volume_di'],
                    'pipe_line' => $value['pipe_line'],
                );
                //更改数据，捕捉异常
                try {
                    if (false === $cabin->editData($where, $edit_data)) {
                        //更改如果没有生效，rollback
                        M()->rollback();
                        $this->error($value['cabinname'] . "，修改未生效，修改失败");
                    }
                } catch (\Exception $e) {
                    //更改如果发生异常，rollback
                    M()->rollback();
                    $this->error($value['cabinname'] . "，修改时异常，修改失败");
                }
            }
            //事务提交
            M()->commit();
            $this->success("修改成功");
        } else {
            $shipid = intval(I("get.shipid"));
            $ship = new \Common\Model\ShipFormModel();
            $ship_info = $ship->field('cabinnum,suanfa')->where(array('id' => $shipid))->find();
            $cabin_info = $cabin->where(array('shipid' => $shipid))->select();
            $assign = array(
                'list' => $cabin_info,
                'shipid' => $shipid,
                'ship_info' => $ship_info,
            );
            $this->assign($assign);
            $this->display();
        }
    }


    public function match_word_cabin()
    {
        $shipid = I('post.shipid');
        $ship = new \Common\Model\ShipFormModel();
        $is_diliang = "";

        if ($_FILES['crb']['tmp_name'] and $_FILES['sysm']['tmp_name'] and $shipid) {
            $shipinfo = $ship->field('is_diliang,cabinnum')->where(array('id' => $shipid))->find();
            $reg1 = '/舱\s*?内\s*?输\s*?油\s*?管\s*?系\s*?所\s*?含\s*?容\s*?积\s*?列\s*?表\s*?如\s*?下[（\(]+m3[\)）]+[：\:]+\s*?舱\s*?名\s([\S\s]*?)总\s*?计\s*?容\s*?量\s([\d\.\r\n]+)/m';
            $reg2 = '/\s*?证书编号\s*?(?:\:|：)\s*?([a-zA-Z0-9]+)\s*?船名\s*?(?:\:|：)\s*?(\S+)\s*?第\s*?(\d+)\s*?页\s*?舱名(?:\:|：)\s*?(\S+)\s*?\S*?\s*?基准高度\/REFERENCE\s*?HEIGHT\:\s*?([\d]{1,2}\.[\d]{0,3})\(m\)\s*?\*+\s*?纵\s*?倾\s*?值\/TRIM\s*?BY\s*?STERN\s*?测\s*?深\s*?空\s*?高\s*?\*+\s*?SOUNDING\s*?ULLAGE\s*?([ \t\-\.\d]+)\s*?(?:\(m\)\s+)+\*+\s+([0-9\.\- \r\n]+)/m';

//            if ($shipinfo['is_diliang'] == 1) {
//                if (!($_FILES['crb_di']['tmp_name'] and $_FILES['sysm_di']['tmp_name'])) $this->error("请上传底量书信息");
//                $sysm_di_orgin_txt = file_get_contents($_FILES['sysm_di']['tmp_name']);
//                $crb_di_orgin_txt = file_get_contents($_FILES['crb_di']['tmp_name']);
//
//
//
//
//
//                $is_diliang = '1';
//            }

            $sysm_orgin_txt = file_get_contents($_FILES['sysm']['tmp_name']);
//            exit($sysm_orgin_txt);
            $crb_orgin_txt = file_get_contents($_FILES['crb']['tmp_name']);

            //匹配第一段正则文本
            preg_match($reg1, $sysm_orgin_txt, $matche);

            $sysm_cabin_txt = $matche[1];
            $sysm_pipe_txt = $matche[2];
//            echo jsonreturn($matche);
            $sysm_cabin_arr = explode("\r", $sysm_cabin_txt);
            $sysm_pipe_arr = explode("\r", $sysm_pipe_txt);
            unset($sysm_cabin_arr[count($sysm_cabin_arr) - 1]);
            unset($sysm_pipe_arr[count($sysm_pipe_arr) - 1]);
            unset($sysm_pipe_arr[count($sysm_pipe_arr) - 1]);

//            echo json_encode($sysm_cabin_arr);

//            echo json_encode($sysm_pipe_arr);

            $pipe_arr = array();
            foreach ($sysm_cabin_arr as $key => $value) {
                $pipe_arr[] = array("cabin_name" => $value, "pipe" => $sysm_pipe_arr[$key]);
            }
//            echo json_encode($pipe_arr);
//            exit();


            $diliang = array();

            $last_diliang = array();
            preg_match_all($reg2, $crb_orgin_txt, $matches, PREG_SET_ORDER, 0);
//            $trim_kedu = json_decode($trim_kedu, true);
            foreach ($matches as $key => $value) {
                $zero_trim = -1;
                $data['cabin_name'] = preg_replace("/[左右]+污油舱 /", "", $value[4]);
                $data['altitudeheight'] = floatval($value[5]);


                //如果和上一页的舱不相同，则记录到底量列表内
                if ($last_diliang['cabin_name'] != $data['cabin_name'] && $key > 0) $diliang[] = $last_diliang;


                //开始分开吃水刻度
                $kedu_str = $this->removeExtraSpace($value[6]);
//            exit($kedu_str);
                $kedu = explode(' ', $kedu_str);
//            exit(jsonreturn($kedu));
//            exit;
                //获取纵倾为0的刻度
                foreach ($kedu as $key1 => $value1) {
                    if (floatval($value1) == 0) {
                        $zero_trim = $key1;
//                        echo $zero_trim;
//                        exit($zero_trim);
                    }
                }
                if ($zero_trim == -1) continue;

                $data['kedu'] = $kedu;
                //开始处理数据主体
                $data_row = explode("\r\n", $this->removeExtraSpace($value[7]));
                //获取每页最后一行的有效数据
                $last_data = $this->getValidData($data_row);
//                exit(jsonreturn($last_data));
                $data_cloumn = explode(" ", $last_data);
                //获取并且记录纵倾刻度在0位的容量
                $last_diliang = array("cabin_name" => $data['cabin_name'], "altitudeheight" => $data['altitudeheight'], "bottom_volume" => $data_cloumn[$zero_trim + 2]);
                if ($key == count($matches) - 1) {
                    $diliang[] = $last_diliang;
                }
            }



//        echo ajaxReturn($matches_height_data);
//        dump($matches_pipe_data_di);
//        exit;
//            echo jsonreturn($diliang);
//            echo jsonreturn($pipe_arr);
//            exit();
            $res = array();
            foreach ($diliang as $k2 => $v2) {
                foreach ($pipe_arr as $k3 => $v3) {
                    if ($v2['cabin_name'] == $v3['cabin_name']) {
                        $data = array(
                            'cabinname' => $v2['cabin_name'],
                            'altitudeheight' => $v2['altitudeheight'],
                            'dialtitudeheight' => 0,
                            'bottom_volume' => $v2['bottom_volume'],
                            'bottom_volume_di' => 0,
                            'pipe_line' => $v3['pipe'],
                        );
                        array_push($res, $data);
                    }
                }
            }

            $form_html = "<tr><td rowspan=\"2\">舱名</td><td colspan=\"2\">基准高度(H)</td><td colspan=\"2\">底量(D)</td><td rowspan=\"2\">管线容量</td></tr><tr><td>容量表</td><td>底量表</td><td>容量表</td><td>底量表</td></tr>";
//            return $res;

            foreach ($res as $k4 => $v4) {
                $form_html .= '<tr><td><input type="text" id="form-field-1" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][cabinname]" value="' . $v4['cabinname'] . '" tabindex="' . ($shipinfo['cabinnum'] * 0 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][altitudeheight]" value="' . floatval($v4['altitudeheight']) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 1 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" name="data[' . $k4 . '][dialtitudeheight]" value="' . floatval($v4['dialtitudeheight']) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 2 + $k4) . '" disabled="disabled"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][bottom_volume]" value="' . floatval($v4['bottom_volume']) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 3 + $k4) . '"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" name="data[' . $k4 . '][bottom_volume_di]" value="' . floatval($v4['bottom_volume_di']) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 4 + $k4) . '" disabled="disabled"/></td><td><input type="text" id="form-field-2" class="col-xs-15 col-sm-12" required name="data[' . $k4 . '][pipe_line]" value="' . floatval($v4['pipe_line']) . '" maxlength="5" tabindex="' . ($shipinfo['cabinnum'] * 5 + $k4) . '"/></td></tr>';
            }

            $form_html .= '<tr><td colspan=6 style="text-align: center;"><input type="submit" name="sub" value="提交" class="btn btn-primary" ></td></tr>';

            $this->ajaxReturn(array('state' => $ship->ERROR_CODE_COMMON['SUCCESS'], 'content' => $form_html, 'cabinnum' => count($res)));
        } else {
            $this->error("请上传容量书信息");
        }
    }

    function getValidData($data_row)
    {
        $last_data = $data_row[count($data_row) - 1];
        unset($data_row[count($data_row) - 1]);
        $qian = array(" ", "　", "   ", "    ", '-', '0', '.');
        $hou = array("", "", "", "", "", "", "");
        if (str_replace($qian, $hou, $last_data) == "") return $this->getValidData($data_row);
        return $last_data;
    }

    /**
     * 去除文本内多余空格，并且去除头部和结尾的空格
     * @param $txt
     * @return string
     */
    function removeExtraSpace($txt)
    {
        $txt1 = preg_replace("/^ {2,}/m", "", $txt);
        $txt2 = preg_replace("/(\d) {2,}/m", "$1 ", $txt1);
        $txt3 = preg_replace("/ {2,}$/m", "", $txt2);
        return $txt3;
    }

}