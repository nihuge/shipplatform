<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

/**
 * 船舶管理
 * */
class ShipController extends AdminBaseController
{
    /**
     * 船列表
     */
    public function index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        $ship = new \Common\Model\ShipModel();
        $where = array('1');
        if (I('get.firmid') != '') {
            $where['firmid'] = I('get.firmid');
        }
        if (I('get.shipname') != '') {
            $where['shipname'] = array('like', '%' . I('get.shipname') . '%');
        }
        if (I('get.del_sign') != '') {
            $where['s.del_sign'] = trimall(I('get.del_sign'));
        } else {
            //默认查找没被删除的船
            $where['s.del_sign'] = 1;
        }
        //筛选有表船和无表船
        if (I('get.is_have_data') != '' and count(I('get.is_have_data')) == 1) {
            $where['data_ship'] = trimall(I('get.is_have_data'));
        }

        $count = $ship
            ->alias('s')
            ->where($where)
            ->count();
        $per = 30;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;

        $data = $ship
            ->alias('s')
            ->field('s.id,s.is_lock,s.shipname,s.number,s.cabinnum,s.tankcapacityshipid,s.rongliang,s.zx,s.rongliang_1,s.zx_1,f.firmname,s.del_sign,s.data_ship')
            ->where($where)
            ->join('left join firm f on f.id=s.firmid')
            ->order('s.id desc,f.firmname desc')
            ->limit($begin, $per)
            ->select();

        // 获取所有公司列表
        $firm = new \Common\Model\FirmModel();
        $firmlist = $firm->field('id,firmname')->select();

        $assign = array(
            'data' => $data,
            'page' => $page,
            'firmlist' => $firmlist,
        );
        $this->assign($assign);
        $this->display();
    }


    /**
     * 正则匹配船信息
     */
    public function match_ship_info()
    {
//            $orgin_txt_rong = I('post.rong_txt');
//            $orgin_txt_di = I('post.di_txt');
        $is_diliang = I('post.is_diliang');//是否有底量书
        $diliang_number = I('post.diliang_number');//底量书编号

        $firm = new \Common\Model\FirmModel();
        if ($_FILES['file']['tmp_name'] and $is_diliang) {
            /*
             * 第一部分，匹配获取页数，船名，证书编号，目录，有效期
             */
            $orgin_txt = file_get_contents($_FILES['file']['tmp_name']);
            $re = "/\-{23}\s*?Page\s*?(\d+)\s*?\-{23}\s*?目 录\s*?CONTENTS\s*?([\S]+) ([A-Za-z0-9]+)\s*?(1[\S\s]*?)有效期至([0-9\-年月日]+)/m";
            preg_match($re, $orgin_txt, $matche);
//                echo jsonreturn($matche);
//                exit($orgin_txt);


            //船名
            $shipname = $matche[2];
            //证书编号
            $number = $matche[3];

            //有效期
//                $expire_time = date('Y-m-d',date_parse_from_format('Y年m月d日',$matche[5]));
            $expire_time = date_parse_from_format('Y年m月d日', $matche[5]);
            $expire_time = $expire_time["year"] . '-' . $expire_time["month"] . '-' . $expire_time["day"];
//                exit($matche[5]." ".$expire_time);


            /*
             * 第二部分，匹配页数，证书编号，舱数，船名，所属公司，船舶类型，制造单位
             */
            $re1 = '/\-{23}\s*?Page\s*?(\d+)\s*?\-{23}\s*?国 家 船 舶 舱 容 积 计 量 站\s*?检　 定 　证 　书[\S\s]*?检 定 日 期 有 效 期 至\s*?([a-zA-Z0-9]+)\s*?(\d+)\s*?(\S+)\s*?[\S\s]*?(\S+公司)\s*?\S+\s*?(\S+)[\S\s]*?(\S+公司)\S*?[\S\s]*?有效期至/m';
            preg_match($re1, $orgin_txt, $matche1);/*

                /*
                 *  匹配船舶识别号
                 */
            $re8 = '/船舶识别号 ([A-Za-z0-9]+)/m';
            preg_match($re8, $orgin_txt, $matche8);


            //舱总数
            $cabinnum = $matche1[3];
            //船舶识别号
            $shibie_num = $matche8[1];
            //公司名
            $firm_name = $matche1[5];
            //船舶类型
            $type = $matche1[6];
            //制造单位
            $make = $matche1[7];
//                exit($cabinnum);
//                dump($matche1);


            /*
             * 匹配舱材料膨胀倍数
             */
            $re2 = "/Vt ＝ Vb × \[1\+(\d+)α\(t - 20\)\]/m";
            preg_match($re2, $orgin_txt, $matche2);

            $coefficient = $matche2[1];

            /*
             * 匹配舱材料的线膨胀系数
             */
            $re3 = "/α=([\.\d]+)\/℃。/m";
            preg_match($re3, $orgin_txt, $matche3);

            $a = $matche3[1];

            /*
             * 验证判断，判断是否存在此字样，如果存在则管线不包含，否则包含管线
             */
            $re4 = "/舱容表所示容量不包括管线的容量/";
            if (preg_match($re4, $orgin_txt)) {
                $is_guanxian = '2';
            } else {
                $is_guanxian = '1';
            }

            $re5 = "/横倾修正表 List Correction Table/m";

            if (preg_match($re5, $orgin_txt)) {
                if ($is_diliang == '1') {
                    $suanfa = 'c';
                    $number .= "-" . $diliang_number;
                } else {
                    $suanfa = 'b';
                }
            } else {
                if ($is_diliang == '1') {
                    $suanfa = 'a';
                    $number .= "-" . $diliang_number;
                } else {
                    $suanfa = 'd';
                }
            }

            $re6 = "/\[[左右]+\.\d+\s?(?:[PS]+\.\d+)\]\s*?Trim Correction Table\s*?\S+\s*?\S+\s*?(?:\(mm\)\s)+\s*?((?:\-?\d\.\dm ?)+)/m";
            preg_match($re6, $orgin_txt, $matche4);
            $kedu_str = $matche4[1];
            $kedu_arr = explode('m ', $kedu_str);
            $kedu_arr[count($kedu_arr) - 1] = str_replace("m", "", $kedu_arr[count($kedu_arr) - 1]);

            $space = 15 - count($kedu_arr);
            //用于占位
            for ($i = 0; $i < $space; $i++) {
                $kedu_arr[] = "";
            }
            //判断有没有这个公司

            $firm_where = array(
                'firmname' => $firm_name
            );
            $firm_count = $firm->where($firm_where)->count();

            $ship_info = array(
                'shipname' => $shipname,
                'shibie_num' => $shibie_num,
                'number' => $number,
                'expire_time' => $expire_time,
                'cabinnum' => $cabinnum,
                'firmname' => $firm_name,
                'type' => $type,
                'make' => $make,
                'coefficient' => $coefficient,
                'is_guanxian' => $is_guanxian,
                'suanfa' => $suanfa,
                'a' => $a,
                'weight' => "无",
                'goodsname' => "无",
                'kedu' => $kedu_arr,
                'kedu1' => $kedu_arr,
            );

            $res = array(
                'state' => $firm->ERROR_CODE_COMMON['SUCCESS'],
                'firm_count' => $firm_count,
                'ship_info' => $ship_info
            );

        } else {
            $res = array(
                'state' => $firm->ERROR_CODE_COMMON['PARAMETER_ERROR'],
                'error' => "参数缺少",
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 新增船
     * */
    public function add()
    {
        if (IS_POST) {
            $data = I('post.');
            unset($data['img']);
            unset($data['photo1']);
            foreach ($data['kedu'] as $key => $value) {
                if ($value == '') {
                    unset($data['kedu'][$key]);
                }
            }

            // 判断是否存在底量表纵倾刻度值
            if (isset($data['kedu1'])) {
                foreach ($data['kedu1'] as $key => $value) {
                    if ($value == '') {
                        unset($data['kedu1'][$key]);
                    }
                }
            } else {
                $data['kedu1'] = array();
            }

            //C算法如果不提交底量纵倾刻度就复制容量的
            if (strtolower($data['suanfa']) == "c" or strtolower($data['suanfa']) == "d") {
                if (empty($data['kedu1'])) {
                    $data['kedu1'] = $data['kedu'];
                }
            }

            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                $this->error('数据不能含有特殊字符');
                exit;
            }

            $data['img'] = I('post.img');
            $data['expire_time'] = strtotime(I('post.expire_time'));
            $ship = new \Common\Model\ShipFormModel();
            // 判断 船舶是否存在
            $count = $ship->where(array('shipname' => $data['shipname']))->count();
            if ($count == 0) {
                if (!$ship->create($data)) {
                    //对data数据进行验证
                    $this->error($ship->getError());
                } else {
                    // 判断算法得出是否有底量测量孔
                    if ($data['suanfa'] == 'c' or $data['suanfa'] == 'd') {
                        $data['is_diliang'] = '1';
                    } else {
                        $data['is_diliang'] = '2';
                    }


                    $res = $ship->addData($data);
                    //如果船舶创建成功
                    if ($res !== false) {
                        // 新增船舶创建表、添加船舶历史数据汇总初步
                        $ship->createtable($data['suanfa'], $data['shipname'], $res, $data['kedu'], $data['kedu1']);
                        //判断公司有无管理员账户，如果没有管理员账户，则不创建
                        $user = new \Common\Model\UserModel();
                        $firm_admin = $user->field('id')->where(array('firmid' => $data['firmid'], 'pid' => 0))->find();
                        //管理员数等于1,开始创建账号,账号名为船名各字的首字母+船的全拼，如果超出字符限制，则裁剪至字符
                        if ($firm_admin['id'] > 0) {
                            $user_data = array(
                                'title' => substr(pinyin($data['shipname'], 'first') . pinyin($data['shipname']), 0, $user->getUserMaxLength()),
                                'username' => $data['shipname'],
                                'pwd' => time(),//第一次的密码随机，如果用户需要登陆直接重置就好了
                                'firmid' => $data['firmid'],
                                'pid' => $firm_admin['id'],
                                'operation_jur' => array($res),//将这个船的权限加入到自己的账号中
                                'look_other' => 2,//可以看所有公司的作业记录
                            );
                            //创建用户,不考虑是否创建成功，创建失败也不回档。如果创建失败自动评价时使用-1
                            $user->adddatas($user_data);
                        }
                        $this->success('添加成功', U('index'));
                    } else {
                        $this->error('添加失败');
                    }
                }
            } else {
                $this->error('添加失败，船舶名已存在');
            }
        } else {
            // 获取船舶公司列表
            $firm = new \Common\Model\FirmModel();
            $where = array(
                'firmtype' => array('eq', '2'),
                'del_sign' => 1,
            );
            $firmlist = $firm
                ->field('id,firmname')
                ->where($where)
                ->order('id asc')
                ->select();
            $assign = array(
                'firmlist' => $firmlist
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 修改船信息
     */
    public function edit()
    {
        $ship = new \Common\Model\ShipFormModel();
        if (IS_POST) {
            $data = I('post.');
            foreach ($data['kedu'] as $key => $value) {
                if ($value == '') {
                    unset($data['kedu'][$key]);
                }
            }

            // 判断是否存在底量表纵倾刻度值
            if (isset($data['kedu1'])) {
                foreach ($data['kedu1'] as $key => $value) {
                    if ($value == '') {
                        unset($data['kedu1'][$key]);
                    }
                }
            } else {
                $data['kedu1'] = array();
            }
            unset($data['img']);
            unset($data['photo1']);
            // 根据算法判断刻度是填的哪个，与原先的作对比
            $cou = 1;
            $kedu = array();
            if ($data['suanfa'] == 'a') {
                foreach ($data['kedu'] as $key => $value) {
                    $kedu['tripbystern' . $cou] = $value;
                    $cou++;
                }
            } else {
                foreach ($data['kedu'] as $key => $value) {
                    $kedu['trimvalue' . $cou] = $value;
                    $cou++;
                }
            }

            $kedu = json_encode($kedu, JSON_UNESCAPED_UNICODE);

            $cou = 1;
            // 判断底量表纵倾刻度值
            foreach ($data['kedu1'] as $key => $value) {
                $kedu1['trimvalue' . $cou] = $value;
                $cou++;
            }

            //根据算法处理油船的底量纵倾刻度，如果算法C或D且刻度为空，则复制容量书的纵倾刻度
            if ($data['suanfa'] == 'c' or $data['suanfa'] == 'd') {
                if (empty($kedu1)) {
                    $kedu1 = $kedu;
                } else {
                    $kedu1 = json_encode($kedu1, JSON_UNESCAPED_UNICODE);
                }
            } else {
                if (empty($kedu1)) {
                    $kedu1 = "";
                } else {
                    $kedu1 = json_encode($kedu1, JSON_UNESCAPED_UNICODE);
                }
            }

            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                $this->error('数据不能含有特殊字符');
                exit;
            }


            $data['img'] = I('post.img');

            // p($data);die;
            $data['expire_time'] = strtotime(I('post.expire_time'));
            // 判断船舶是否存在
            $count = $ship
                ->where(array('shipname' => $data['shipname'], 'id' => array('neq', $data['id'])))
                ->count();
            if ($count == 0) {
                $map = array(
                    'id' => $data['id']
                );
                if (!$ship->create($data)) {
                    //对data数据进行验证
                    $this->error($ship->getError());
                } else {
                    // 判断提交的刻度值是否与原先一致
                    $msg = $ship->where(array('id' => $data['id']))->find();
                    $Model = M();

                    if (!empty($msg['tripbystern'])) {
                        // 纵倾值比较
                        if ($msg['tripbystern'] != $kedu) {
                            // 删除表
                            if (!empty($msg['tankcapacityshipid'])) {
                                $sql = "drop table `" . $msg['tankcapacityshipid'] . "`";
                                $Model->execute($sql);
                            }
                            // 新增船舶创建表、添加船舶历史数据汇总初步
                            $ship->createtable($data['suanfa'], $data['shipname'], $data['id'], $data['kedu']);
                        }
                        $data['tripbystern'] = $kedu;
                    } else {
                        // 纵倾值修正比较
                        if ($msg['trimcorrection'] != $kedu) {
                            // 删除表
                            if (!empty($msg['rongliang'])) {
                                $sql1 = "drop table `" . $msg['rongliang'] . "`";
                                $Model->execute($sql1);
                            }

                            if (!empty($msg['zx'])) {
                                $sql2 = "drop table `" . $msg['zx'] . "`";
                                $Model->execute($sql2);
                            }

                            if (!empty($msg['rongliang_1'])) {
                                $sql3 = "drop table `" . $msg['rongliang_1'] . "`";
                                $Model->execute($sql3);
                            }

                            if (!empty($msg['zx_1'])) {
                                $sql4 = "drop table `" . $msg['zx_1'] . "`";
                                $Model->execute($sql4);
                            }


                            // 新增船舶创建表、添加船舶历史数据汇总初步
                            if (strtolower($data['suanfa']) == 'c' or strtolower($data['suanfa']) == 'd') {
                                $ship->createtable($data['suanfa'], $data['shipname'], $data['id'], $data['kedu'], $data['kedu1']);
                            } else {
                                $ship->createtable($data['suanfa'], $data['shipname'], $data['id'], $data['kedu']);
                            }
                        }
                        $data['trimcorrection'] = $kedu;
                        $data['trimcorrection1'] = $kedu1;
                    }


                    // 判断算法得出是否有底量测试
                    if ($data['suanfa'] == 'c') {
                        $data['is_diliang'] = '1';
                    } else {
                        $data['is_diliang'] = '2';
                    }
                    $result = $ship->editData($map, $data);
                    if ($result !== false) {
                        $this->success('修改成功');
                    } else {
                        $this->error('修改失败');
                    }
                }
            } else {
                $this->error('添加失败，船舶名已存在');
            }
        } else {
            // 获取公司列表
            $firm = new \Common\Model\FirmModel();
            $firmlist = $firm
                ->field('id,firmname')
                ->where(array('firmtype' => array('eq', '2')))
                ->order('id asc')
                ->select();
            //船信息
            $shipmsg = $ship
                ->where(array('id' => I('get.id')))
                ->find();
            if (is_Domain()) {
                $shipmsg['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $shipmsg['img']);
            }
            if (!empty($shipmsg['tripbystern'])) {
                $kedu = json_decode($shipmsg['tripbystern'], true);
            } else {
                $kedu = json_decode($shipmsg['trimcorrection'], true);
            }
            $kedu = array_values($kedu);

            if (!empty($shipmsg['tripbystern'])) {
                $kedu1 = json_decode($shipmsg['tripbystern1'], true);
            } else {
                $kedu1 = json_decode($shipmsg['trimcorrection1'], true);
            }
            $kedu1 = array_values($kedu1);
            $assign = array(
                'shipmsg' => $shipmsg,
                'firmlist' => $firmlist,
                'kedu' => $kedu,
                'kedu1' => $kedu1,
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 排序
     */
    public function sort()
    {
        $cabin = new \Common\Model\CabinModel();
        if (IS_POST) {
            $data = I('post.');
            $result = $cabin->orderData($data);
            if ($result) {
                $this->success('排序成功');
            } else {
                $this->error('排序失败');
            }
        } else {
            // 获取船驳所有舱名
            $data = $cabin
                ->field('c.id,c.cabinname,s.shipname,c.order_number')
                ->alias('c')
                ->join('left join ship s on s.id = c.shipid')
                ->where(array('c.shipid' => I('get.id')))
                ->order('c.order_number asc,c.id asc')
                ->select();

            $assign = array(
                'data' => $data
            );
            $this->assign($assign);
            $this->display();
        }
    }


    /**
     * 核对纵倾修正表
     */
    public function check_table($shipid)
    {
        $ship = new \Common\Model\ShipFormModel();
        $cabin = new \Common\Model\CabinModel();

        $msg = $ship
            ->field('tripbystern,trimcorrection,trimcorrection1,shipname,suanfa,rongliang,rongliang_1,tankcapacityshipid,zx,zx_1')
            ->where(array('id' => $shipid))
            ->find();

        $cabin_list = $cabin->where(array('shipid' => intval($shipid)))->select();

        $list_data = array('suanfa' => $msg['suanfa'], 'shipname' => $msg['shipname'],'cabin_tree'=>$cabin_list);

        if ($msg['suanfa'] == "a") {
            $list_data['rongliang'] = array('rongliang'=>array(),'kedu'=>json_decode($msg['tripbystern'],true));
            foreach ($cabin_list as $key => $value) {
                $m = M($msg['tankcapacityshipid']);
                $msg1 = $m->where(array('cabinid' => $value['id']))->select();
                $data = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg1,
                );
                array_push($list_data['rongliang']['rongliang'],$data);
            }
        }elseif ($msg['suanfa'] == "b"){
            $list_data['rongliang'] = array('rongliang'=>array(),'zx'=>array(),'kedu'=>json_decode($msg['trimcorrection'],true));
            foreach ($cabin_list as $key => $value) {
                $m1 = M($msg['rongliang']);
                $msg1 = $m1->where(array('cabinid' => $value['id']))->select();
                $data = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg1,
                );
                array_push($list_data['rongliang']['rongliang'],$data);

                $m2 = M($msg['zx']);
                $msg2 = $m2->where(array('cabinid' => $value['id']))->select();
                $data1 = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg2,
                );
                array_push($list_data['rongliang']['zx'],$data1);
            }
        }elseif ($msg['suanfa'] == "c"){
            $list_data['rongliang'] = array('rongliang'=>array(),'zx'=>array(),'kedu'=>json_decode($msg['trimcorrection'],true));
            $list_data['diliang'] = array('rongliang'=>array(),'zx'=>array(),'kedu1'=>json_decode($msg['trimcorrection1'],true));
            foreach ($cabin_list as $key => $value) {
                $m1 = M($msg['rongliang']);
                $msg1 = $m1->where(array('cabinid' => $value['id']))->select();
                $data = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg1,
                );
                array_push($list_data['rongliang']['rongliang'],$data);

                $m2 = M($msg['zx']);
                $msg2 = $m2->where(array('cabinid' => $value['id']))->select();
                $data1 = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg2,
                );
                array_push($list_data['rongliang']['zx'],$data1);

                $m3 = M($msg['rongliang_1']);
                $msg3 = $m3->where(array('cabinid' => $value['id']))->select();
                $data3 = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg3,
                );
                array_push($list_data['diliang']['rongliang'],$data3);

                $m4 = M($msg['zx_1']);
                $msg4 = $m4->where(array('cabinid' => $value['id']))->select();
                $data4 = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg4,
                );
                array_push($list_data['diliang']['zx'],$data4);
            }
        }elseif ($msg['suanfa'] == "d"){
            $list_data['rongliang'] = array('zx'=>array(),'kedu'=>json_decode($msg['trimcorrection'],true));
            $list_data['diliang'] = array('zx'=>array(),'kedu1'=>json_decode($msg['trimcorrection1'],true));
            foreach ($cabin_list as $key => $value) {
                $m2 = M($msg['zx']);
                $msg2 = $m2->where(array('cabinid' => $value['id']))->select();
                $data1 = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg2,
                );
                array_push($list_data['rongliang']['zx'],$data1);

                $m4 = M($msg['zx_1']);
                $msg4 = $m4->where(array('cabinid' => $value['id']))->select();
                $data4 = array(
                    'cabinname' => $value['cabinname'],
                    'cabinid' => $value['id'],
                    'list' => $msg4,
                );
                array_push($list_data['diliang']['zx'],$data4);
            }
        }

//        exit(json_encode($list_data));

        $this->assign($list_data);
        $this->display();
    }


    /**
     * 软删除船舶操作
     * 不是真的删除，只是增加删除标记，并且不会在正常业务中出现此船
     */
    public function del_ship()
    {
        if (IS_AJAX) {
            $shipid = trimall(I('post.shipid'));
            $ship = new \Common\Model\ShipModel();
            $work = new \Common\Model\WorkModel();

            //查找有没有关于这个船的作业
            $workCount = $work->where(array('shipid' => $shipid, 'del_sign' => 1))->count();

            if ($workCount <= 0 and $workCount !== false) {
                //如果这个船没有作业，则可以删除
                $where = array(
                    'id' => $shipid,
                    'del_sign' => 1
                );

                $resultCount = $ship->editData($where, array('del_sign' => 2));

                if ($resultCount > 0) {
                    //如果影响行数大于0
                    $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 11, 'msg' => '该船未找到或已被删除'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该船还有作业未被删除，请删除作业后重新尝试'));
            }
        }
    }


    /**
     * 恢复船舶操作
     * 恢复船舶，前提要求公司没有被删除
     */
    public function recoverShip()
    {
        if (IS_AJAX) {
            $shipid = trimall(I('post.shipid'));
            $ship = new \Common\Model\ShipModel();
            $firm = new \Common\Model\FirmModel();

            //查找这个船属公司是否被删除
            $firmid = $ship->field('firmid')->where(array('shipid' => $shipid))->find();
            $firmCount = $firm->where(array('id' => $firmid['firmid'], 'del_sign' => 1))->count();

            if ($firmCount > 0) {
                //如果这个船属公司没被删除
                $where = array(
                    'id' => $shipid,
                    'del_sign' => 2
                );
                $resultCount = $ship->editData($where, array('del_sign' => 1));
                if ($resultCount > 0) {
                    //如果影响行数大于0
                    $this->ajaxReturn(array('code' => 1, 'msg' => '恢复成功'));
                } else {
                    $this->ajaxReturn(array('code' => 11, 'msg' => '该船未找到或已被恢复'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该船所属公司未被恢复，请恢复公司后重试'));
            }
        }
    }

    /**
     * 真删除船舶
     * 真正删除，数据无法恢复，除非备份。
     * 删除前检测是否存在该船下未被真正删除的作业，如果存在则不允许删除
     */
    public function relDelShip()
    {
        if (IS_AJAX) {
            $shipid = trimall(I('post.shipid'));
            $ship = new \Common\Model\ShipModel();
            $work = new \Common\Model\WorkModel();

            //查找有没有关于这个船的作业
            $workCount = $work->where(array('shipid' => $shipid))->count();

            if ($workCount !== false and $workCount <= 0) {
                //如果这个船没有作业，则可以删除
                $where = array(
                    'id' => $shipid,
                );
                $msg = $ship->where($where)->find();

                //删除船信息
                $shipDelResult = $ship->where($where)->delete();

                //删除对应的船舱信息
                $cabin = new \Common\Model\CabinModel();
                $cabinDelResult = $cabin->where(array('shipid' => $shipid))->delete();

                //删除对应的带纵倾修正刻度的舱容表
                $Model = M();
                if (!empty($msg['tankcapacityshipid'])) {
                    $sql = "drop table `" . $msg['tankcapacityshipid'] . "`";
                    @$Model->execute($sql);
                }

                //删除对应舱容表
                if (!empty($msg['rongliang'])) {
                    $sql = "drop table `" . $msg['rongliang'] . "`";
                    @$Model->execute($sql);
                }

                //删除对应的纵倾修正表
                if (!empty($msg['zx'])) {
                    $sql = "drop table `" . $msg['zx'] . "`";
                    @$Model->execute($sql);
                }

                //删除对应的底量书容量表
                if (!empty($msg['rongliang_1'])) {
                    $sql = "drop table `" . $msg['rongliang_1'] . "`";
                    @$Model->execute($sql);
                }

                //删除对应的底量书纵倾修正表
                if (!empty($msg['zx_1'])) {
                    $sql = "drop table `" . $msg['zx_1'] . "`";
                    @$Model->execute($sql);
                }

                if ($cabinDelResult !== false and $shipDelResult !== false) {
                    //如果没有删除失败
                    $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 2, 'msg' => '彻底删除船时有部分数据删除失败,请联系技术人员'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该船下有作业未被彻底删除，请将该船下的所有作业彻底删除后重试'));
            }
        }
    }


    //反转锁的状态，原来是有锁的变成无锁，原来是无锁的，变成有锁
    public function reverse_lock(){
        $where = array('id'=>intval(I('post.shipid')));
        $ship = new \Common\Model\ShipFormModel();
        $old_lock = $ship->field('is_lock')->where($where)->find();
        if($old_lock['is_lock'] == 1){
            $data = array(
                'is_lock'=>2
            );
        }else{
            $data = array(
                'is_lock'=>1
            );
        }
        $result = $ship->editData($where,$data);
        if($result !== false){
            $res = array(
                'code'=>$ship->ERROR_CODE_COMMON['SUCCESS'],
            );
        }else{
            $res = array(
                'code'=>$ship->ERROR_CODE_COMMON['DB_ERROR'],
                'error'=>$ship->getDbError()
            );
        }
        $this->ajaxReturn($res);
    }
}