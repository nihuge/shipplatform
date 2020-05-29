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
            $where['s.shipname'] = array('like', '%' . I('get.shipname') . '%');
        }

        //默认查找没被锁定的船
        $where['s.is_lock'] = 2;

        $ship = new \Common\Model\ShipFormModel();

        /**
         * 构建子查询sql,查询哪些船处于待审核状态
         *
         * 生成sql：(select id from ship where review = 1)
         **/
        $ship_id = $ship
            ->alias("s")
            ->field('s.id')
            ->join('left join cabin as c on c.shipid=s.id')
            ->where($where)
            ->group('s.id')
            ->having('count(c.id)>0')
            ->select();

        /*
         * 结合子查询查询作业次数小于2的待审核船
         *
         * 生成sql:select shipid FROM result WHERE shipid in((select id from ship where review = 1)) GROUP BY shipid HAVING count(1)<2
         */

//        try {
//            $ship_id = $ship->alias('s')
//                ->field('s.id')
//                ->join('left join result as r on s.id=r.shipid')
//                ->where(array('s.id' => array('exp', 'in (' . $sub_sql . ')')))
//                ->group('s.id')
//                ->having('count(r.shipid)<2')
//                ->select();
//        } catch (\Exception $e) {
//            $ship_id = array();
//        }

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
            $ship_id_arr[] = $value['id'];
        }
        if ($count > 0) {
            $data = $ship
                ->alias('s')
                ->field('s.id,s.shipname,s.number,s.cabinnum,f.firmname,s.del_sign')
                ->where(array('s.id' => array('in', $ship_id_arr)))
                ->join('left join firm f on f.id=s.firmid')
                ->order('s.id desc,f.firmname desc')
                ->limit($begin, $per)
                ->select();
        } else {
            $data = array();
        }

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
     * 新建油船审核详情页
     */
    public function create_ship($shipid)
    {
        $ship = new \Common\Model\ShipFormModel();
        $cabin = new \Common\Model\CabinModel();
        $ship_img = M("ship_img");

        //先获取船的APP新建信息
        $ship_msg = $ship
            ->alias('s')
            ->field('s.*,u.username')
            ->join('left join firm as f on f.id=s.firmid')
            ->join('left join user as u on u.id=s.uid')
            ->where(array('s.id' => $shipid))
            ->find();

        //然后获取所有的舱的新建信息
        $cabin_msg = $cabin
            ->where(array("shipid" => $shipid))
            ->select();

        //获取所有图片信息
        $img_msg = $ship_img->where(array('shipid' => $shipid))->select();

        // 获取所有公司列表
        $firm = new \Common\Model\FirmModel();
        $firmlist = $firm->field('id,firmname')->select();

        $cabin_img_count = 0;
        $ship_img_count = 0;
        foreach ($img_msg as $value) {
            if ($value['type'] == 2) {
                $cabin_img_count += 1;
            } else {
                $ship_img_count += 1;
            }
        }

        //渲染数据
        $this->assign('cabin_img_count', $cabin_img_count);
        $this->assign('ship_img_count', $ship_img_count);
        $this->assign('shipmsg', $ship_msg);
        $this->assign('cabinmsg', $cabin_msg);
        $this->assign('shipimg', $img_msg);
        $this->assign('firmlist', $firmlist);

        $this->display();
    }


    /**
     * ajax提交油船审核结果
     */
    public function create_ship_result()
    {
        if (IS_AJAX) {
            $ship = new \Common\Model\ShipFormModel();
            $shipid = I("post.shipid");
            $result = I("post.result");

            $map = array(
                'id' => $shipid
            );
            //更改审核状态
            $data = array('review' => $result);
            try {
                $edit_result = $ship->editData($map, $data);
            } catch (\Exception $e) {
                $this->ajaxReturn(array('code' => 3, 'msg' => $e->getMessage()));
            }
            if ($edit_result !== false) {
                $this->ajaxReturn(array('code' => 1, 'msg' => "审核成功"));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => "审核失败"));
            }
        }
        echo jsonreturn(array('code' => '500', 'msg' => '请勿非法调用本接口'));
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

//        if (I('get.review') != '') {
//            $where['review'] = trimall(I('get.review'));
//        } else {
//            //默认查找没被审核的船
//            $where['review'] = 1;
//        }

        $where['is_lock'] = 2;
        $sh_ship = new \Common\Model\ShShipModel();

        /**
         * 获取符合条件的油船新建审核数量
         */


        /*
         * 构建子查询sql,查询哪些船处于待审核状态
         *
         * 生成sql：(select id from ship where review = 1)
         */
        $sh_ship_id = $sh_ship->field('id')->where($where)->select();

        /*
         * 结合子查询查询作业次数小于2的待审核船
         *
         * 生成sql:select count(1) as a,shipid FROM result WHERE shipid in((select id from ship where review = 1)) GROUP BY shipid HAVING count(1)<2
         */
//        try {
//            $sh_ship_id = $sh_ship
//                ->alias('s')
//                ->field('s.id')
//                ->join('left join sh_result as r on s.id=r.shipid')
//                ->where(array('s.id' => array('exp', 'in (' . $sub_sql . ')')))
//                ->group('s.id')
//                ->having('count(r.shipid)<2')
//                ->select();
//        } catch (\Exception $e) {
//            $sh_ship_id = array();
//        }

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
            $ship_id_arr[] = $value['id'];
        }

        if ($count > 0) {
            $data = $sh_ship
                ->alias('s')
                ->field('s.id,s.shipname,s.number,s.cabinnum,f.firmname,s.del_sign')
                ->where(array('s.id' => array('in', $ship_id_arr)))
                ->join('left join firm f on f.id=s.firmid')
                ->order('s.id desc,f.firmname desc')
                ->limit($begin, $per)
                ->select();
        } else {
            $data = array();
        }
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
     * 新建散货船审核详情页
     */
    public function create_sh($shipid)
    {
        $ship = new \Common\Model\ShShipModel();
        $firm = new \Common\Model\FirmModel();
        $ship_img = M("sh_ship_img");

        //先获取船的APP新建信息
        $ship_msg = $ship
            ->alias('s')
            ->field('s.*,u.username')
            ->join('left join firm as f on f.id=s.firmid')
            ->join('left join user as u on u.id=s.uid')
            ->where(array('s.id' => $shipid))
            ->find();

        //获取所有图片信息
        $img_msg = $ship_img->where(array('shipid' => $shipid))->select();

        // 获取所有公司列表
        $firmlist = $firm->field('id,firmname')->select();

        //统计图片数量
        $img_count = count($img_msg);

        //渲染数据
        $this->assign('shipmsg', $ship_msg);
        $this->assign('shipimg', $img_msg);
        $this->assign('img_count', $img_count);
        $this->assign('firmlist', $firmlist);

        $this->display();
    }


    /**
     * ajax提交新建散货船审核结果
     */
    public function create_sh_result()
    {
        if (IS_AJAX) {
            $ship = new \Common\Model\ShShipModel();
            $shipid = I("post.shipid");
            $result = I("post.result");

            $map = array(
                'id' => $shipid
            );
            //更改审核状态
            $data = array('review' => $result);
            try {
                $edit_result = $ship->editData($map, $data);
            } catch (\Exception $e) {
                $edit_result = false;
                $this->ajaxReturn(array('code' => 3, 'msg' => $e->getMessage()));
            }
            if ($edit_result !== false) {
                $this->ajaxReturn(array('code' => 1, 'msg' => "审核成功"));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => "审核失败"));
            }
        }
        echo jsonreturn(array('code' => '500', 'msg' => '请勿非法调用本接口'));
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
            ->field("sr.id,sr.shipid,sr.create_time,sr.data_status,s.shipname,a.name,u.username")
            ->join("left join user u on u.id=sr.userid")
            ->join("left join admin a on a.id=sr.adminid")
            ->join("left join ship s on s.id=sr.shipid")
            ->where($where)
            ->limit($begin, $per)
            ->select();

        foreach ($data as $key => $value) {
            $data[$key]['create_time'] = date('Y-m-d H:i:s', $data[$key]['create_time']);
        }

        $assign = array(
            'data' => $data,
            'page' => $page,
        );
        $this->assign($assign);
        $this->display();
    }


    /**
     * 新建油船审核详情页
     */
    public function review_ship($shipid, $reviewid)
    {
        $ship = new \Common\Model\ShipFormModel();
        $firm = new \Common\Model\FirmModel();
        $cabin = new \Common\Model\CabinModel();
        $review = M('ship_review');
        $review_img = M("review_img");

        //先获取修改前船的APP修改信息
        $ship_front_msg = $ship
            ->field('shipname,cabinnum,coefficient,is_guanxian,is_diliang,suanfa,expire_time')
            ->where(array('id' => $shipid))
            ->find();

        //然后获取修改后船的APP修改信息
        $ship_after_msg = $review
            ->alias('sr')
            ->field('sr.*,u.username')
            ->join('left join user as u on u.id=sr.userid')
            ->where(array('sr.shipid' => $shipid, 'sr.id' => $reviewid))
            ->find();

        //然后获取修改前所有的舱的新建信息
        $cabin_front_msg = $cabin
            ->alias('c')
            ->field('c.*,cr.cabinid,cr.cabinname as newcabinname,cr.altitudeheight as newaltitudeheight,cr.dialtitudeheight as newdialtitudeheight,cr.bottom_volume as newbottom_volume,cr.bottom_volume_di as newbottom_volume_di,cr.pipe_line as newpipe_line,u.username')
            ->join('left join cabin_review as cr on cr.cabinid=c.id')
            ->join('left join user as u on u.id=cr.userid')
            ->where(array("c.shipid" => $shipid))
            ->select();

//        //然后获取修改后所有的舱的新建信息
//        $cabin_after_msg = $cabin_review
//            ->alias('cr')
//            ->field('c.*,u.username')
//            ->join('left join user as u on u.id=cr.userid')
//            ->where(array('cr.shipid' => $shipid, 'cr.review_id' => $reviewid))
//            ->select();

        //获取所有图片信息
        $img_msg = $review_img->where(array('ship_id' => $shipid, 'review_id' => $reviewid))->select();

        // 获取所有公司列表
        $firmlist = $firm->field('id,firmname')->select();

        $cabin_img_count = 0;
        $ship_img_count = 0;
        foreach ($img_msg as $value) {
            if ($value['type'] == 2) {
                $cabin_img_count += 1;
            } else {
                $ship_img_count += 1;
            }
        }

//        $cabin_after_count = count($cabin_after_msg);

        //渲染数据
        $this->assign('cabin_img_count', $cabin_img_count);
        $this->assign('ship_img_count', $ship_img_count);
        $this->assign('ship_front_msg', $ship_front_msg);
        $this->assign('ship_after_msg', $ship_after_msg);
        $this->assign('cabin_front_msg', $cabin_front_msg);
//        $this->assign('cabin_after_msg', $cabin_after_msg);
//        $this->assign('cabin_after_count', $cabin_after_count);
        $this->assign('shipimg', $img_msg);
        $this->assign('firmlist', $firmlist);

        $this->display();
    }


    /**
     * ajax提交修改油船船审核结果
     */
    public function review_ship_result($review_id, $shipid)
    {

        if (IS_AJAX) {
            //初始化模型
            $ship = new \Common\Model\ShipFormModel();
            $cabin = new \Common\Model\CabinModel();
            $review = M("ship_review");
            $cabin_review = M("cabin_review");

            //接收参数
            $shipid = I("post.shipid");
            $result = I("post.result");
            $remark = I("post.remark");

            $map = array(
                'id' => $review_id,
                'shipid' => $shipid
            );

            //更改审核状态
            $data = array(
                'status' => $result,
                'remark' => $remark
            );

            M()->startTrans();
//            $edit_result = false;
            try {
                if ($result == 3) {
                    //如果审核失败
                    $edit_result = $review->where($map)->save($data);
                } elseif ($result == 2) {
                    //如果审核通过,首先更改舱信息
                    $review_data_where = array(
                        'review_id' => $review_id,
                        'shipid' => $shipid
                    );


                    //遍历需要更改的舱信息
                    $cabin_review_data = $cabin_review
                        ->field('cabinid,shipid,cabinname,altitudeheight,dialtitudeheight,bottom_volume,bottom_volume_di,pipe_line')
                        ->where($review_data_where)
                        ->select();

                    //开始循环更改舱数据
                    foreach ($cabin_review_data as $key => $value) {
                        //初始化更改数据
                        $cabin_data = array();
                        //初始化更改条件
                        $cabin_review_result_map = array(
                            'shipid' => $shipid,
                            'id' => $value['cabinid'],
                        );
                        if ($value['cabinname'] !== null and $value['cabinname'] != "") {
                            $cabin_data['cabinname'] = $value['cabinname'];
                        }
                        if ($value['altitudeheight'] !== null and $value['altitudeheight'] != "") {
                            $cabin_data['altitudeheight'] = $value['altitudeheight'];
                        }
                        if ($value['dialtitudeheight'] !== null and $value['dialtitudeheight'] != "") {
                            $cabin_data['dialtitudeheight'] = $value['dialtitudeheight'];
                        }
                        if ($value['bottom_volume'] !== null and $value['bottom_volume'] != "") {
                            $cabin_data['bottom_volume'] = $value['bottom_volume'];
                        }
                        if ($value['bottom_volume_di'] !== null and $value['bottom_volume_di'] != "") {
                            $cabin_data['bottom_volume_di'] = $value['bottom_volume_di'];
                        }
                        if ($value['pipe_line'] !== null and $value['pipe_line'] != "") {
                            $cabin_data['pipe_line'] = $value['pipe_line'];
                        }

                        //如果该审核没有需要修改的数据，则跳过,防止数据错误
                        if (count($cabin_data) > 0) {
                            try {
                                $cabin_review_result = $cabin->editData($cabin_review_result_map, $cabin_data);
                            } catch (\Exception $e) {
                                M()->rollback();
                                $edit_result = false;
                                $this->ajaxReturn(array('code' => 4, 'msg' => "舱修改失败"));
                            }
                        } else {
                            $cabin_review_result = true;
                        }

                        if (!$cabin_review_result !== false) {
                            M()->rollback();
                            $edit_result = false;
                            $this->ajaxReturn(array('code' => 4, 'msg' => "舱修改失败" . $cabin->error));
                        }
                    }

                    $review_edit_where = array(
                        'id' => $review_id,
                        'shipid' => $shipid
                    );

                    //开始修改船数据.搜索需要修改的船数据
                    $ship_review_data = $review
                        ->field('shipname,cabinnum,coefficient,is_guanxian,is_diliang,suanfa,expire_time')
                        ->where($review_edit_where)
                        ->find();

                    //新的船数据
                    $ship_data = array();
                    //更改调教
                    $edit_result_map = array(
                        'id' => $shipid
                    );

                    if ($ship_review_data['shipname'] !== null and $ship_review_data['shipname'] != "") {
                        $ship_data['shipname'] = $ship_review_data['shipname'];
                    }
                    if ($ship_review_data['cabinnum'] !== null and $ship_review_data['cabinnum'] != "") {
                        $ship_data['cabinnum'] = $ship_review_data['cabinnum'];
                    }
                    if ($ship_review_data['coefficient'] !== null and $ship_review_data['coefficient'] != "") {
                        $ship_data['coefficient'] = $ship_review_data['coefficient'];
                    }
                    if ($ship_review_data['is_guanxian'] !== null and $ship_review_data['is_guanxian'] != "") {
                        $ship_data['is_guanxian'] = $ship_review_data['is_guanxian'];
                    }
                    if ($ship_review_data['is_diliang'] !== null and $ship_review_data['is_diliang'] != "") {
                        $ship_data['is_diliang'] = $ship_review_data['is_diliang'];
                    }
                    if ($ship_review_data['suanfa'] !== null and $ship_review_data['suanfa'] != "") {
                        $ship_data['suanfa'] = $ship_review_data['suanfa'];
                    }
                    if ($ship_review_data['expire_time'] !== null and $ship_review_data['expire_time'] != "") {
                        $ship_data['expire_time'] = $ship_review_data['expire_time'];
                    }


                    if (count($ship_data) > 0) {
                        $ship_result = $ship->editData($edit_result_map, $ship_data);
                    } else {
                        $ship_result = true;
                    }

                    if ($ship_result !== false) {
                        $edit_result = $review->where($map)->save($data);
                    } else {
                        M()->rollback();
                        $edit_result = false;
                        $this->ajaxReturn(array('code' => 6, 'msg' => "状态更改失败"));
                    }

                } else {
                    M()->rollback();
                    $edit_result = false;
                    $this->ajaxReturn(array('code' => 501, 'msg' => "非法调试"));
                }
            } catch (\Exception $e) {
                M()->rollback();
                $edit_result = false;
                $this->ajaxReturn(array('code' => 3, 'msg' => $e->getMessage()));
            }

            if ($edit_result !== false) {
                M()->commit();
                $this->ajaxReturn(array('code' => 1, 'msg' => "审核成功"));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 2, 'msg' => "审核失败"));
            }
        }
        echo jsonreturn(array('code' => 500, 'msg' => '请勿非法调用本接口'));
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
            ->field("sr.id,sr.shipid,sr.create_time,s.shipname,a.name,u.username")
            ->join("left join user u on u.id=sr.userid")
            ->join("left join admin a on a.id=sr.adminid")
            ->join("left join sh_ship s on s.id=sr.shipid")
            ->where($where)
            ->limit($begin, $per)
            ->select();

        foreach ($data as $key => $value) {
            $data[$key]['create_time'] = date('Y-m-d H:i:s', $data[$key]['create_time']);
        }

        $assign = array(
            'data' => $data,
            'page' => $page,
        );
        $this->assign($assign);
        $this->display();
    }


    /**
     * 修改散货船审核详情页
     */
    public function review_sh($shipid, $reviewid)
    {
        $ship = new \Common\Model\ShShipModel();
        $firm = new \Common\Model\FirmModel();
        $review = M('sh_review');
        $review_img = M("sh_review_img");

        //先获取修改前船的APP修改信息
        $ship_front_msg = $ship
            ->field('shipname,cabinnum,lbp,df,da,dm,ptwd,weight,expire_time')
            ->where(array('id' => $shipid))
            ->find();

        //然后获取修改后船的APP修改信息
        $ship_after_msg = $review
            ->alias('sr')
            ->field('sr.*,u.username')
            ->join('left join user as u on u.id=sr.userid')
            ->where(array('sr.shipid' => $shipid, 'sr.id' => $reviewid))
            ->find();

        //获取所有图片信息
        $img_msg = $review_img->where(array('review_id' => $reviewid))->select();

        // 获取所有公司列表
        $firmlist = $firm->field('id,firmname')->select();

        $img_count = count($img_msg);

//        $cabin_after_count = count($cabin_after_msg);

        //渲染数据
        $this->assign('img_count', $img_count);
        $this->assign('ship_front_msg', $ship_front_msg);
        $this->assign('ship_after_msg', $ship_after_msg);
        $this->assign('shipimg', $img_msg);
        $this->assign('firmlist', $firmlist);

        $this->display();
    }

    /**
     * ajax提交修改散货船审核结果
     */
    public function review_sh_result($review_id, $shipid)
    {

        if (IS_AJAX) {
            //初始化模型
            $ship = new \Common\Model\ShShipModel();
            $review = M("sh_review");

            //接收参数
            $result = I("post.result");
            $remark = I("post.remark");

            $map = array(
                'id' => $review_id,
                'shipid' => $shipid
            );

            //更改审核状态
            $data = array(
                'status' => $result,
                'remark' => $remark
            );

            M()->startTrans();
//            $edit_result = false;
            try {
                if ($result == 3) {
                    //如果审核失败
                    $edit_result = $review->where($map)->save($data);
                } elseif ($result == 2) {
                    //如果审核通过,更改船信息
                    $review_data_where = array(
                        'id' => $review_id,
                        'shipid' => $shipid
                    );

                    //开始修改船数据.搜索需要修改的船数据
                    $ship_review_data = $review
                        ->field('shipname,cabinnum,lbp,df,da,dm,ptwd,weight,expire_time')
                        ->where($review_data_where)
                        ->find();

                    //新的船数据
                    $ship_data = array();
                    //更改调教
                    $edit_result_map = array(
                        'id' => $shipid
                    );

                    if ($ship_review_data['shipname'] !== null and $ship_review_data['shipname'] != "") {
                        $ship_data['shipname'] = $ship_review_data['shipname'];
                    }
                    if ($ship_review_data['cabinnum'] !== null and $ship_review_data['cabinnum'] != "") {
                        $ship_data['cabinnum'] = $ship_review_data['cabinnum'];
                    }
                    if ($ship_review_data['lbp'] !== null and $ship_review_data['lbp'] != "") {
                        $ship_data['lbp'] = $ship_review_data['lbp'];
                    }
                    if ($ship_review_data['df'] !== null and $ship_review_data['df'] != "") {
                        $ship_data['df'] = $ship_review_data['df'];
                    }
                    if ($ship_review_data['da'] !== null and $ship_review_data['da'] != "") {
                        $ship_data['da'] = $ship_review_data['da'];
                    }
                    if ($ship_review_data['dm'] !== null and $ship_review_data['dm'] != "") {
                        $ship_data['dm'] = $ship_review_data['dm'];
                    }
                    if ($ship_review_data['ptwd'] !== null and $ship_review_data['ptwd'] != "") {
                        $ship_data['ptwd'] = $ship_review_data['ptwd'];
                    }
                    if ($ship_review_data['weight'] !== null and $ship_review_data['weight'] != "") {
                        $ship_data['weight'] = $ship_review_data['weight'];
                    }

                    if (count($ship_review_data) > 0) {
                        $ship_result = $ship->editData($edit_result_map, $ship_data);
                        if ($ship_result !== false) {
                            $edit_result = $review->where($map)->save($data);
                        } else {
                            M()->rollback();
                            $edit_result = false;
                            $this->ajaxReturn(array('code' => 6, 'msg' => "状态更改失败"));
                        }
                    } else {
                        $edit_result = true;
                    }
                } else {
                    M()->rollback();
                    $edit_result = false;
                    $this->ajaxReturn(array('code' => 501, 'msg' => "非法调试"));
                }
            } catch (\Exception $e) {
                M()->rollback();
                $edit_result = false;
                $this->ajaxReturn(array('code' => 3, 'msg' => "修改失败，原因:" . $e->getMessage()));
            }

            if ($edit_result !== false) {
                M()->commit();
                $this->ajaxReturn(array('code' => 1, 'msg' => "审核成功"));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 2, 'msg' => "审核失败"));
            }
        }
        echo jsonreturn(array('code' => 500, 'msg' => '请勿非法调用本接口'));
    }


    /**
     * 认领公司审核列表
     */
    public function claimed_firm_index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        if (I('get.firmname') != '') {
            $where['f.firmname'] = array('like', '%' . I('get.firmname') . '%');
        }

        if (I('get.status') != '') {
            $where['fr.result'] = trimall(I('get.status'));
        } else {
            //默认查找没被审核的公司
            $where['fr.result'] = 1;
        }

        //获取文件有上传的记录
        $where['fr.file_count'] = array('gt', 0);

        $firm_review = M("firm_review");

        $count = $firm_review
            ->alias("fr")
            ->join("left join user u on u.id=fr.uid")
            ->join("left join admin a on a.id=fr.adminid")
            ->join("left join firm f on f.id=fr.firmid")
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

        $data = $firm_review
            ->alias("fr")
            ->field("fr.id,fr.file_count,fr.time,f.firmname,a.name,u.username")
            ->join("left join user u on u.id=fr.uid")
            ->join("left join admin a on a.id=fr.adminid")
            ->join("left join firm f on f.id=fr.firmid")
            ->where($where)
            ->limit($begin, $per)
            ->select();

        //格式化日期
        foreach ($data as $key => $value) {
            $data[$key]['time'] = date('Y-m-d H:i:s', $data[$key]['time']);
        }

        $assign = array(
            'data' => $data,
            'page' => $page,
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 认领公司审核详情页
     */
    public function claimed_firm($claimed_id)
    {
        $firm = new \Common\Model\FirmModel();
        $firm_review = M('firm_review');
        $files_path = M("files_path");

        //获取舱容文件审核信息
        $firm_review_msg = $firm_review
            ->alias("fr")
            ->field("fr.id,fr.file_count,fr.time,fr.firmid,a.name,u.username,u.phone")
            ->join("left join user u on u.id=fr.uid")
            ->join("left join admin a on a.id=fr.adminid")
            ->where(array('fr.id' => $claimed_id))
            ->find();

        //公司审核信息。
        $firm_msg = $firm->field('firmname,claimed_img,claimed_code')->where(array('id' => $firm_review_msg['firmid']))->find();

        //获取所有文件
        $files_msg = $files_path->where(array('type_id' => $claimed_id, 'type' => 1))->select();
        foreach ($files_msg as $key => $value) {
            $files_msg[$key]['status'] = file_exists($value['path']) ? '文件存在' : '文件不存在';
            $files_msg[$key]['file_size'] = ($files_msg[$key]['status'] == '文件存在') ? (round(filesize($value['path']) / 1024 / 1024, 3) . 'Mb') : '0 Mb';
        }

        //渲染数据
        $this->assign('review_msg', $firm_review_msg);
        $this->assign('files_msg', $files_msg);
        $this->assign('firm_msg', $firm_msg);

        $this->display();
    }

    /**
     * 下载审核文件zip
     */
    public function down_claimed_zip($claimed_id)
    {
        $claimed_id = intval($claimed_id);
        $files_path = M('files_path');
        $firm_review = M('firm_review');
        $firm = new \Common\Model\FirmModel();
        $user = new \Common\Model\UserModel();
        $where = array(
            'type' => 1,
            'type_id' => $claimed_id,
        );

        // 压缩多个文件
        $fileList = $files_path->field('path,file_name')->where($where)->select();
        $firmname = $firm_review->alias('fr')->field('f.firmname,f.id,fr.uid')->join('left join firm f on f.id=fr.firmid')->where(array('fr.id' => $claimed_id))->find();

        $filename = "./Public/review_zip/" . md5($claimed_id) . ".zip"; // 压缩包所在的位置路径

        $zip = new \ZipArchive();
        $zip->open($filename, \ZipArchive::CREATE);   //打开压缩包
        foreach ($fileList as $file) {
            $zip->addFile($file['path'], basename($file['file_name']));   //向压缩包中添加文件
        }
        $zip->close();  //关闭压缩包
        M()->startTrans();
        //压缩成功以后，更改状态为正在审核
        $firm_data = array(
            'claimed' => 1
        );
        $result1 = $firm->editData(array('id' => $firmname['id']), $firm_data);
        $review_data = array(
            'result' => 2
        );
        $result2 = $firm_review->where(array('id' => $claimed_id))->save($review_data);


        if ($result2 !== false and $result1 !== false) {
            $fp = fopen($filename, "rb");
            $file_size = filesize($filename);//获取文件的字节
            //下载文件需要用到的头
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            Header("Accept-Length:" . $file_size);
            Header("Content-Disposition: attachment; filename=" . $firmname['firmname'] . "的认领审核文件.zip");
            $buffer = 4096; //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）,防止服务器崩溃
            $file_count = 0; //读取的总字节数
            //向浏览器返回数据 如果下载完成就停止输出，如果未下载完成就一直在输出。根据文件的字节大小判断是否下载完成
            while (!feof($fp) && $file_count < $file_size) {
                $file_con = fread($fp, $buffer);
                $file_count += $buffer;
                ob_clean();
                flush();
                echo $file_con;
            }
            fclose($fp);

            //下载完成后删除压缩包，临时文件夹
            if ($file_count >= $file_size) {
                M()->commit();
                unlink($filename);
            }
        } else {
            M()->rollback();
            unlink($filename);
            $this->error('审核状态无法改为审核中，请向网站维护人员展示下列错误<br>firm:' . $firm->getError() . "<br/>firm_review:" . $firm_review->getError());
        }
    }


    /**
     * 认领公司审核结果
     */
    public function claimed_firm_result($claimed_id)
    {
        if (IS_AJAX) {
            $firm = new \Common\Model\FirmModel();
            $user = new \Common\Model\UserModel();

            //初始化模型
            $review = M("firm_review");

            //接收参数
            $result = I("post.result");
            $remark = I("post.remark");

            $map = array(
                'id' => $claimed_id,
            );

            //更改审核状态
            $data = array(
                'result' => $result,
                'remark' => $remark,
                'adminid' => $_SESSION['adminuid'],
            );
            //搜索需要修改的船数据
            $map = array(
                'fr.id' => $claimed_id,
            );
            $ship_review_data = $review
                ->alias('fr')
                ->field('fr.uid,fr.firmid,f.claimed_img,f.claimed_code,f.shehuicode,f.img')
                ->join('left join firm f on f.id=fr.firmid')
                ->where($map)
                ->find();
            //开启事务
            M()->startTrans();
//            $edit_result = false;
            try {
                if ($result == 3) {
                    //如果审核失败,恢复公司未被认领的状态,清空社会信用代码
                    $reset_firm_data = array(
                        'claimed' => 0,
                        'claimed_img' => '',
                        'claimed_code' => '',
                    );
                    $reset_firm_result = $firm->editData(array('id' => $ship_review_data['firmid']), $reset_firm_data);
                    $reset_user_data = array(
                        'reg_status' => 3
                    );
                    $reset_user_result = $user->editData(array('id' => $ship_review_data['uid']), $reset_user_data);
                    if ($reset_firm_result !== false and $reset_user_result !== false) {
                        $edit_result = $review->where(array('id'=>$claimed_id))->save($data);
                    } else {
                        $edit_result = false;
                    }

                } elseif ($result == 4) {
                    //如果审核通过,更改用户和公司信息
                    $user = new \Common\Model\UserModel();


                    //修改船数据.
                    $ship_data = array();
                    //更改公司用户数据 1、将公司其他用户的管理员改为此用户
                    $edit_user_map = array(
                        'firmid' => $ship_review_data['firmid']
                    );

                    $edit_user_data = array(
                        'pid' => $ship_review_data['uid'],
                    );
                    $result1 = $user->editData($edit_user_map, $edit_user_data);

                    // 2、更改该用户的所属公司为此公司
                    $edit_user_map = array(
                        'id' => $ship_review_data['uid']
                    );
                    $edit_user_data = array(
                        'firmid' => $ship_review_data['firmid'],
                        'pid' => 0,
                        'reg_status' => 2,
                    );
                    $result2 = $user->editData($edit_user_map, $edit_user_data);


                    //更改公司数据
                    $edit_firm_map = array(
                        'id' => $ship_review_data['firmid']
                    );
                    //将新的社会代码图片和代码数据应用到公司，同时将老数据放到新的代码内
                    $edit_firm_data = array(
                        'img' => $ship_review_data['claimed_img'],
                        'shehuicode' => $ship_review_data['claimed_code'],
                        'claimed_code' => $ship_review_data['shehuicode'],
                        'claimed_img' => $ship_review_data['img'],
                        'claimed' => 2,//设置公司状态为已被认领
                    );
                    $result3 = $firm->editData($edit_firm_map, $edit_firm_data);


                    if ($result1 !== false and $result2 !== false and $result3 !== false) {
                        $edit_result = $review->where(array('id'=>$claimed_id))->save($data);
                    } else {
                        M()->rollback();
                        $edit_result = false;
                        $this->ajaxReturn(array('code' => 6, 'msg' => "状态更改失败"));
                    }

                } else {
                    M()->rollback();
                    $edit_result = false;
                    $this->ajaxReturn(array('code' => 501, 'msg' => "非法调试"));
                }
            } catch (\Exception $e) {
                M()->rollback();
                $edit_result = false;
                $this->ajaxReturn(array('code' => 3, 'msg' => "修改失败，原因:" . $e->getMessage()));
            }

            if ($edit_result !== false) {
                M()->commit();
                $this->ajaxReturn(array('code' => 1, 'msg' => "审核成功"));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 2, 'msg' => "审核失败"));
            }
        }
        echo jsonreturn(array('code' => 500, 'msg' => '请勿非法调用本接口'));
    }


    /**
     * 上传舱容表审核列表
     */
    public function table_review_index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        if (I('get.shipname') != '') {
            $where['tr.shipname'] = array('like', '%' . I('get.shipname') . '%');
        }

        if (I('get.status') != '') {
            $where['tr.result'] = trimall(I('get.status'));
        } else {
            //默认查找没被审核的公司
            $where['tr.result'] = 1;
        }

        //获取文件有上传的记录
        $where['tr.file_count'] = array('gt', 0);

        $table_review = M("table_review");

        $count = $table_review
            ->alias("tr")
            ->join("left join user u on u.id=tr.uid")
            ->join("left join admin a on a.id=tr.adminid")
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

        $data = $table_review
            ->alias("tr")
            ->field("tr.id,tr.file_count,tr.time,tr.type,tr.shipname,a.name,u.username")
            ->join("left join user u on u.id=tr.uid")
            ->join("left join admin a on a.id=tr.adminid")
            ->where($where)
            ->limit($begin, $per)
            ->select();

        //格式化日期
        foreach ($data as $key => $value) {
            $data[$key]['up_type'] = $value['type'] == 1 ? "完整上传" : "部分上传";
            $data[$key]['time'] = date('Y-m-d H:i:s', $data[$key]['time']);
        }

        $assign = array(
            'data' => $data,
            'page' => $page,
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 上传舱容表审核详情页
     */
    public function table_review($review_id)
    {
        $ship = new \Common\Model\ShipFormModel();
        $firm_review = M('table_review');
        $files_path = M("files_path");

        //获取舱容文件审核信息
        $table_review_msg = $firm_review
            ->alias("tr")
            ->field("tr.id,tr.file_count,tr.time,tr.shipname,tr.type,a.name,u.username,u.phone")
            ->join("left join user u on u.id=tr.uid")
            ->join("left join admin a on a.id=tr.adminid")
            ->where(array('tr.id' => $review_id))
            ->find();
        $ship_count = $ship->where(array('shipname' => $table_review_msg['shipname']))->count();

        $table_review_msg['up_type'] = $table_review_msg['type'] == 1 ? "完整上传" : "部分上传";
        $table_review_msg['isset'] = $ship_count > 0 ? "存在" : "不存在";
        //获取所有文件
        $files_msg = $files_path->where(array('type_id' => $review_id, 'type' => 2))->select();
        foreach ($files_msg as $key => $value) {
            $files_msg[$key]['status'] = file_exists($value['path']) ? '文件存在' : '文件不存在';
            $files_msg[$key]['file_size'] = ($files_msg[$key]['status'] == '文件存在') ? (round(filesize($value['path']) / 1024 / 1024, 3) . 'Mb') : '0Mb';
        }

        //渲染数据
        $this->assign('review_msg', $table_review_msg);
        $this->assign('files_msg', $files_msg);

        $this->display();
    }

    /**
     * 下载舱容表审核文件zip
     */
    public function down_table_review_zip($review_id)
    {
        $review_id = intval($review_id);
        $files_path = M('files_path');
        $table_review = M('table_review');
        $firm = new \Common\Model\FirmModel();
        $where = array(
            'type' => 2,
            'type_id' => $review_id,
        );

        // 压缩多个文件
        $fileList = $files_path->field('path,file_name')->where($where)->select();
        $shipname = $table_review->field('shipname')->where(array('id' => $review_id))->find();

        $filename = "./Public/review_zip/" . md5($review_id . '2') . ".zip"; // 压缩包所在的位置路径

        $zip = new \ZipArchive();
        $zip->open($filename, \ZipArchive::CREATE);   //打开压缩包
        foreach ($fileList as $file) {
            $zip->addFile($file['path'], basename($file['file_name']));   //向压缩包中添加文件
        }
        $zip->close();  //关闭压缩包
        //压缩成功以后，更改状态为正在审核
        $review_data = array(
            'result' => 2
        );

        $result = $table_review->where(array('id' => $review_id))->save($review_data);

        if ($result !== false) {
            $fp = fopen($filename, "rb");
            $file_size = filesize($filename);//获取文件的字节
            //下载文件需要用到的头
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            Header("Accept-Length:" . $file_size);
            Header("Content-Disposition: attachment; filename=" . $shipname['shipname'] . "的舱容表上传审核文件.zip");
            $buffer = 4096; //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）,防止服务器崩溃
            $file_count = 0; //读取的总字节数
            //向浏览器返回数据 如果下载完成就停止输出，如果未下载完成就一直在输出。根据文件的字节大小判断是否下载完成
            while (!feof($fp) && $file_count < $file_size) {
                $file_con = fread($fp, $buffer);
                $file_count += $buffer;
                ob_clean();
                flush();
                echo $file_con;
            }
            fclose($fp);

            //下载完成后删除压缩包，临时文件夹
            if ($file_count >= $file_size) {
                unlink($filename);
            }
        } else {
            $this->error('审核状态无法改为审核中，请向网站维护人员展示下列错误<br>table_review:' . $table_review->getError());
        }
    }


    /**
     * 上传舱容表审核结果
     */
    public function table_review_result($review_id)
    {
        if (IS_AJAX) {
            $firm = new \Common\Model\FirmModel();

            //初始化模型
            $review = M("table_review");

            //接收参数
            $result = I("post.result");
            $remark = I("post.remark");

            $map = array(
                'id' => $review_id,
            );

            //更改审核状态
            $data = array(
                'result' => $result,
                'remark' => $remark,
                'adminid' => $_SESSION['adminuid'],
            );

            //开启事务
            M()->startTrans();
//            $edit_result = false;
            try {
                if ($result == 3) {
                    //如果审核失败
                    $edit_result = $review->where($map)->save($data);
                } elseif ($result == 4) {
                    //审核成功，正在创建数据
                    $edit_result = $review->where($map)->save($data);
                } elseif ($result == 5) {
                    //审核成功，创建成功
                    $review_info = $review->field('shipname,uid')->where($map)->find();
                    //成功以后记得把此船权限加入至公司和用户权限
                    $ship = new \Common\Model\ShipFormModel();
                    $shipid = $ship->field('id')->where(array('shipname' => $review_info['name']))->find();
                    if (!empty($shipid)) {
                        //如果ID存在加入权限到用户
                        $user = new \Common\Model\UserModel();
                        $jur = $user->getUserOperationSeach($review_info['uid']);
                        if (!in_array($shipid['id'], $jur['operation_jur_array'])) {
                            $jur['operation_jur_array'][] = $shipid['id'];
                        }
                        if (!in_array($shipid['id'], $jur['search_jur_array'])) {
                            $jur['search_jur_array'][] = $shipid['id'];
                        }
                        $jur['operation_jur_array'] = json_encode($jur['operation_jur_array']);
                        $jur['search_jur_array'] = json_encode($jur['search_jur_array']);
                        $user_result = $user->editData(array('id' => $review_info['uid']), $jur);

                        if ($user_result !== false) {
                            $edit_result = $review->where($map)->save($data);
                        } else {
                            M()->rollback();
                            $this->error('用户权限更改失败');
                            $edit_result = false;
                        }
                    } else {
                        M()->rollback();
                        $this->error("船未被创建,请创建成功以后再审核");
                        $edit_result = false;
                    }

                } else {
                    M()->rollback();
                    $edit_result = false;
                    $this->ajaxReturn(array('code' => 501, 'msg' => "非法调试"));
                }
            } catch (\Exception $e) {
                M()->rollback();
                $edit_result = false;
                $this->ajaxReturn(array('code' => 3, 'msg' => "修改失败，原因:" . $e->getMessage()));
            }

            if ($edit_result !== false) {
                M()->commit();
                $this->ajaxReturn(array('code' => 1, 'msg' => "审核成功"));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 2, 'msg' => "审核失败"));
            }
        }
        echo jsonreturn(array('code' => 500, 'msg' => '请勿非法调用本接口'));
    }


    /**
     * 认领公司审核列表
     */
    public function legalize_firm_index()
    {
        $firm = new \Common\Model\FirmModel();
        $user = new \Common\Model\UserModel();
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        if (I('get.firmname') != '') {
            $where['firmname'] = array('like', '%' . I('get.firmname') . '%');
        }

        if (I('get.status') != '') {
            $where['claimed'] = trimall(I('get.status'));
        } else {
            //默认查找没被审核的公司
            $where['claimed'] = 0;
        }

        $where['legalize_img'] = array('neq',"");
        $where['legalize_code'] = array('neq',"");


        $count = $firm
            ->where($where)
            ->count();

        $per = 15;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;

        $data = $firm
            ->field("id,firmname,legalize_img")
            ->where($where)
            ->limit($begin, $per)
            ->select();



        //格式化日期
        foreach ($data as $key => $value) {
            $data[$key]['legalize_img'] = date('Y-m-d H:i:s', $data[$key]['legalize_img']);
            $user_info = $user->where(array('firmid'=>$value['id'],'pid'=>0))->find();
            $data[$key]['user'] = date('Y-m-d H:i:s', $data[$key]['legalize_img']);
        }

        $assign = array(
            'data' => $data,
            'page' => $page,
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 认领公司审核详情页
     */
    public function legalize_firm($claimed_id)
    {
        $firm = new \Common\Model\FirmModel();
        $firm_review = M('firm_review');
        $files_path = M("files_path");

        //获取舱容文件审核信息
        $firm_review_msg = $firm_review
            ->alias("fr")
            ->field("fr.id,fr.file_count,fr.time,fr.firmid,a.name,u.username,u.phone")
            ->join("left join user u on u.id=fr.uid")
            ->join("left join admin a on a.id=fr.adminid")
            ->where(array('fr.id' => $claimed_id))
            ->find();

        //公司审核信息。
        $firm_msg = $firm->field('firmname,claimed_img,claimed_code')->where(array('id' => $firm_review_msg['firmid']))->find();

        //获取所有文件
        $files_msg = $files_path->where(array('type_id' => $claimed_id, 'type' => 1))->select();
        foreach ($files_msg as $key => $value) {
            $files_msg[$key]['status'] = file_exists($value['path']) ? '文件存在' : '文件不存在';
            $files_msg[$key]['file_size'] = ($files_msg[$key]['status'] == '文件存在') ? (round(filesize($value['path']) / 1024 / 1024, 3) . 'Mb') : '0 Mb';
        }

        //渲染数据
        $this->assign('review_msg', $firm_review_msg);
        $this->assign('files_msg', $files_msg);
        $this->assign('firm_msg', $firm_msg);

        $this->display();
    }

//    /**
//     * 下载审核文件zip
//     */
//    public function down_legalize_zip($claimed_id)
//    {
//        $claimed_id = intval($claimed_id);
//        $files_path = M('files_path');
//        $firm_review = M('firm_review');
//        $firm = new \Common\Model\FirmModel();
//        $user = new \Common\Model\UserModel();
//        $where = array(
//            'type' => 1,
//            'type_id' => $claimed_id,
//        );
//
//        // 压缩多个文件
//        $fileList = $files_path->field('path,file_name')->where($where)->select();
//        $firmname = $firm_review->alias('fr')->field('f.firmname,f.id,fr.uid')->join('left join firm f on f.id=fr.firmid')->where(array('fr.id' => $claimed_id))->find();
//
//        $filename = "./Public/review_zip/" . md5($claimed_id) . ".zip"; // 压缩包所在的位置路径
//
//        $zip = new \ZipArchive();
//        $zip->open($filename, \ZipArchive::CREATE);   //打开压缩包
//        foreach ($fileList as $file) {
//            $zip->addFile($file['path'], basename($file['file_name']));   //向压缩包中添加文件
//        }
//        $zip->close();  //关闭压缩包
//        M()->startTrans();
//        //压缩成功以后，更改状态为正在审核
//        $firm_data = array(
//            'claimed' => 1
//        );
//        $result1 = $firm->editData(array('id' => $firmname['id']), $firm_data);
//        $review_data = array(
//            'result' => 2
//        );
//        $result2 = $firm_review->where(array('id' => $claimed_id))->save($review_data);
//
//
//        if ($result2 !== false and $result1 !== false) {
//            $fp = fopen($filename, "rb");
//            $file_size = filesize($filename);//获取文件的字节
//            //下载文件需要用到的头
//            Header("Content-type: application/octet-stream");
//            Header("Accept-Ranges: bytes");
//            header("Content-Type: application/zip"); //zip格式的
//            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
//            Header("Accept-Length:" . $file_size);
//            Header("Content-Disposition: attachment; filename=" . $firmname['firmname'] . "的认领审核文件.zip");
//            $buffer = 4096; //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）,防止服务器崩溃
//            $file_count = 0; //读取的总字节数
//            //向浏览器返回数据 如果下载完成就停止输出，如果未下载完成就一直在输出。根据文件的字节大小判断是否下载完成
//            while (!feof($fp) && $file_count < $file_size) {
//                $file_con = fread($fp, $buffer);
//                $file_count += $buffer;
//                ob_clean();
//                flush();
//                echo $file_con;
//            }
//            fclose($fp);
//
//            //下载完成后删除压缩包，临时文件夹
//            if ($file_count >= $file_size) {
//                M()->commit();
//                unlink($filename);
//            }
//        } else {
//            M()->rollback();
//            unlink($filename);
//            $this->error('审核状态无法改为审核中，请向网站维护人员展示下列错误<br>firm:' . $firm->getError() . "<br/>firm_review:" . $firm_review->getError());
//        }
//    }


    /**
     * 认领公司审核结果
     */
    public function legalize_firm_result($claimed_id)
    {
        if (IS_AJAX) {
            $firm = new \Common\Model\FirmModel();
            $user = new \Common\Model\UserModel();

            //初始化模型
            $review = M("firm_review");

            //接收参数
            $result = I("post.result");
            $remark = I("post.remark");

            $map = array(
                'id' => $claimed_id,
            );

            //更改审核状态
            $data = array(
                'result' => $result,
                'remark' => $remark,
                'adminid' => $_SESSION['adminuid'],
            );
            //搜索需要修改的船数据
            $map = array(
                'fr.id' => $claimed_id,
            );
            $ship_review_data = $review
                ->alias('fr')
                ->field('fr.uid,fr.firmid,f.claimed_img,f.claimed_code,f.shehuicode,f.img')
                ->join('left join firm f on f.id=fr.firmid')
                ->where($map)
                ->find();
            //开启事务
            M()->startTrans();
//            $edit_result = false;
            try {
                if ($result == 3) {
                    //如果审核失败,恢复公司未被认领的状态,清空社会信用代码
                    $reset_firm_data = array(
                        'claimed' => 0,
                        'claimed_img' => '',
                        'claimed_code' => '',
                    );
                    $reset_firm_result = $firm->editData(array('id' => $ship_review_data['firmid']), $reset_firm_data);
                    $reset_user_data = array(
                        'reg_status' => 3
                    );
                    $reset_user_result = $user->editData(array('id' => $ship_review_data['uid']), $reset_user_data);
                    if ($reset_firm_result !== false and $reset_user_result !== false) {
                        $edit_result = $review->where(array('id'=>$claimed_id))->save($data);
                    } else {
                        $edit_result = false;
                    }

                } elseif ($result == 4) {
                    //如果审核通过,更改用户和公司信息
                    $user = new \Common\Model\UserModel();


                    //修改船数据.
                    $ship_data = array();
                    //更改公司用户数据 1、将公司其他用户的管理员改为此用户
                    $edit_user_map = array(
                        'firmid' => $ship_review_data['firmid']
                    );

                    $edit_user_data = array(
                        'pid' => $ship_review_data['uid'],
                    );
                    $result1 = $user->editData($edit_user_map, $edit_user_data);

                    // 2、更改该用户的所属公司为此公司
                    $edit_user_map = array(
                        'id' => $ship_review_data['uid']
                    );
                    $edit_user_data = array(
                        'firmid' => $ship_review_data['firmid'],
                        'pid' => 0,
                        'reg_status' => 2,
                    );
                    $result2 = $user->editData($edit_user_map, $edit_user_data);


                    //更改公司数据
                    $edit_firm_map = array(
                        'id' => $ship_review_data['firmid']
                    );
                    //将新的社会代码图片和代码数据应用到公司，同时将老数据放到新的代码内
                    $edit_firm_data = array(
                        'img' => $ship_review_data['claimed_img'],
                        'shehuicode' => $ship_review_data['claimed_code'],
                        'claimed_code' => $ship_review_data['shehuicode'],
                        'claimed_img' => $ship_review_data['img'],
                        'claimed' => 2,//设置公司状态为已被认领
                    );
                    $result3 = $firm->editData($edit_firm_map, $edit_firm_data);


                    if ($result1 !== false and $result2 !== false and $result3 !== false) {
                        $edit_result = $review->where(array('id'=>$claimed_id))->save($data);
                    } else {
                        M()->rollback();
                        $edit_result = false;
                        $this->ajaxReturn(array('code' => 6, 'msg' => "状态更改失败"));
                    }

                } else {
                    M()->rollback();
                    $edit_result = false;
                    $this->ajaxReturn(array('code' => 501, 'msg' => "非法调试"));
                }
            } catch (\Exception $e) {
                M()->rollback();
                $edit_result = false;
                $this->ajaxReturn(array('code' => 3, 'msg' => "修改失败，原因:" . $e->getMessage()));
            }

            if ($edit_result !== false) {
                M()->commit();
                $this->ajaxReturn(array('code' => 1, 'msg' => "审核成功"));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 2, 'msg' => "审核失败"));
            }
        }
        echo jsonreturn(array('code' => 500, 'msg' => '请勿非法调用本接口'));
    }

}