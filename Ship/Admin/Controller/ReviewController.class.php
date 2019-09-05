<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

/**
 * 船舶管理
 * */
class ReviewController extends AdminBaseController
{
    /**
     * 新建船审核列表
     */
    public function create_ship_index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        if (I('get.shipname') != '') {
            $where['shipname'] = array('like', '%' . I('get.shipname') . '%');
        }

        if (I('get.review') != '') {
            $where['review'] = trimall(I('get.review'));
        } else {
            //默认查找没被审核的船
            $where['review'] = 2;
        }


        $ship = new \Common\Model\ShipFormModel();
        $work = new \Common\Model\WorkModel();

        /**
         * 获取符合条件的油船新建审核数量
         */


        /**
         * 构建子查询sql,查询哪些船处于待审核状态
         *
         * 生成sql：(select id from ship where review = 1)
         **/
        $sub_sql = $ship->field('id')->where($where)->buildSql();

        /*
         * 结合子查询查询作业次数小于2的待审核船
         *
         * 生成sql:select count(1) as a,shipid FROM result WHERE shipid in((select id from ship where review = 1)) GROUP BY shipid HAVING count(1)<2
         */
        $ship_id = $work->field('shipid')->where(array('shipid' => array('exp', 'in (' . $sub_sql . ')')))->group('shipid')->having('count(1)<2')->select();


        $count = count($ship_id);
        $per = 30;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;

        $ship_id_arr = array();
        foreach ($ship_id as $value) {
            $ship_id_arr[] = $value['shipid'];
        }

        $data = $ship
            ->alias('s')
            ->field('s.id,s.shipname,s.number,s.cabinnum,f.firmname,s.del_sign')
            ->where(array('s.id' => array('in', $ship_id_arr)))
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
     * 新建散货船审核列表
     */
    public function create_sh_index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        if (I('get.shipname') != '') {
            $where['shipname'] = array('like', '%' . I('get.shipname') . '%');
        }

        if (I('get.review') != '') {
            $where['review'] = trimall(I('get.review'));
        } else {
            //默认查找没被审核的船
            $where['review'] = 1;
        }


        $sh_ship = new \Common\Model\ShShipModel();
        $sh_result = new \Common\Model\ShResultModel();

        /**
         * 获取符合条件的油船新建审核数量
         */


        /*
         * 构建子查询sql,查询哪些船处于待审核状态
         *
         * 生成sql：(select id from ship where review = 1)
         */
        $sub_sql = $sh_ship->field('id')->where($where)->buildSql();

        /*
         * 结合子查询查询作业次数小于2的待审核船
         *
         * 生成sql:select count(1) as a,shipid FROM result WHERE shipid in((select id from ship where review = 1)) GROUP BY shipid HAVING count(1)<2
         */
        $sh_ship_id = $sh_result->field('shipid')->where(array('shipid' => array('exp', 'in (' . $sub_sql . ')')))->group('shipid')->having('count(1)<2')->select();


        $count = count($sh_ship_id);
        $per = 30;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;

        $ship_id_arr = array();
        foreach ($sh_ship_id as $value) {
            $ship_id_arr[] = $value['shipid'];
        }

        $data = $sh_ship
            ->alias('s')
            ->field('s.id,s.shipname,s.number,s.cabinnum,f.firmname,s.del_sign')
            ->where(array('s.id' => array('in', $ship_id_arr)))
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
     * 修改散货船审核列表
     */
    public function review_ship_index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        if (I('get.shipname') != '') {
            $where['s.shipname'] = array('like', '%' . I('get.shipname') . '%');
        }

        if (I('get.status') != '') {
            $where['sr.status'] = trimall(I('get.status'));
        } else {
            //默认查找没被审核的船
            $where['sr.status'] = 1;
        }

        //获取油船的符合条件的审核数量
        $where['_string'] = '((sr.data_status = 1 and sr.picture=2) or (sr.data_status=2 AND sr.picture=2 and sr.cabin_picture=2) or (sr.data_status=3 AND sr.cabin_picture=2))';

        $ship_review = M("ship_review");

        $count = $ship_review
            ->alias("sr")
            ->join("left join user u on u.id=sr.userid")
            ->join("left join admin a on a.id=sr.adminid")
            ->join("left join ship s on s.id=sr.shipid")
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


        $data = $ship_review
            ->alias("sr")
            ->field("sr.id,sr.create_time,sr.data_status,s.shipname,a.name,u.username")
            ->join("left join user u on u.id=sr.userid")
            ->join("left join admin a on a.id=sr.adminid")
            ->join("left join ship s on s.id=sr.shipid")
            ->where($where)
            ->limit($begin, $per)
            ->select();

        foreach ($data as $key => $value) {
            $data[$key]['create_time'] = date('Y-m-d H:i:s',$data[$key]['create_time']);
        }

        $assign = array(
            'data' => $data,
            'page' => $page,
        );
        $this->assign($assign);
        $this->display();
    }



    /**
     * 修改散货船审核列表
     */
    public function review_sh_index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        if (I('get.shipname') != '') {
            $where['s.shipname'] = array('like', '%' . I('get.shipname') . '%');
        }

        if (I('get.status') != '') {
            $where['sr.status'] = trimall(I('get.status'));
        } else {
            //默认查找没被审核的船
            $where['sr.status'] = 1;
        }

        //获取油船的符合条件的审核数量
        $where['_string'] = 'sr.picture=2';

        $sh_ship_review = M("sh_review");

        $count = $sh_ship_review
            ->alias("sr")
            ->join("left join user u on u.id=sr.userid")
            ->join("left join admin a on a.id=sr.adminid")
            ->join("left join sh_ship s on s.id=sr.shipid")
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


        $data = $sh_ship_review
            ->alias("sr")
            ->field("sr.id,sr.create_time,s.shipname,a.name,u.username")
            ->join("left join user u on u.id=sr.userid")
            ->join("left join admin a on a.id=sr.adminid")
            ->join("left join sh_ship s on s.id=sr.shipid")
            ->where($where)
            ->limit($begin, $per)
            ->select();

        foreach ($data as $key => $value) {
            $data[$key]['create_time'] = date('Y-m-d H:i:s',$data[$key]['create_time']);
        }

        $assign = array(
            'data' => $data,
            'page' => $page,
        );
        $this->assign($assign);
        $this->display();
    }



















    /**
     * 新增船
     * */
    public function add()
    {
        if (IS_POST) {
            $data = I('post.');
            unset($data['img']);
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
            if (strtolower($data['suanfa']) == "c") {
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
            $ship = new \Common\Model\ShipModel();
            // 判断船舶是否存在
            $count = $ship->where(array('shipname' => $data['shipname']))->count();
            if ($count == 0) {
                if (!$ship->create($data)) {
                    //对data数据进行验证
                    $this->error($ship->getError());
                } else {
                    // 判断算法得出是否有底量测试
                    if ($data['suanfa'] == 'c') {
                        $data['is_diliang'] = '1';
                    } else {
                        $data['is_diliang'] = '2';
                    }

                    $res = $ship->addData($data);
                    if ($res) {
                        // 新增船舶创建表、添加船舶历史数据汇总初步
                        $ship->createtable($data['suanfa'], $data['shipname'], $res, $data['kedu'], $data['kedu1']);
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
        $ship = new \Common\Model\ShipModel();
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

            //根据算法处理油船的底量纵倾刻度，如果算法C且刻度为空，则复制容量书的纵倾刻度
            if ($data['suanfa'] == 'c') {
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
                            if (strtolower($data['suanfa']) == 'c') {
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
}