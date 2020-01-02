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

        if (I('get.review') != '') {
            $where['s.review'] = trimall(I('get.review'));
        } else {
            //默认查找没被审核的船
            $where['s.review'] = 2;
        }

        $ship = new \Common\Model\ShipFormModel();
        $work = new \Common\Model\WorkModel();

        /**
         * 构建子查询sql,查询哪些船处于待审核状态
         *
         * 生成sql：(select id from ship where review = 1)
         **/
        $sub_sql = $ship
            ->alias("s")
            ->field('s.id')
            ->join('left join cabin as c on c.shipid=s.id')
            ->where($where)
            ->group('s.id')
            ->having('count(c.id)>0')
            ->buildSql();

        /*
         * 结合子查询查询作业次数小于2的待审核船
         *
         * 生成sql:select shipid FROM result WHERE shipid in((select id from ship where review = 1)) GROUP BY shipid HAVING count(1)<2
         */

        try {
            $ship_id = $ship->alias('s')
                ->field('s.id')
                ->join('left join result as r on s.id=r.shipid')
                ->where(array('s.id' => array('exp', 'in (' . $sub_sql . ')')))
                ->group('s.id')
                ->having('count(r.shipid)<2')
                ->select();
        } catch (\Exception $e) {
            $ship_id = array();
        }

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

        if (I('get.review') != '') {
            $where['review'] = trimall(I('get.review'));
        } else {
            //默认查找没被审核的船
            $where['review'] = 1;
        }

        $sh_ship = new \Common\Model\ShShipModel();

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
        try {
            $sh_ship_id = $sh_ship
                ->alias('s')
                ->field('s.id')
                ->join('left join sh_result as r on s.id=r.shipid')
                ->where(array('s.id' => array('exp', 'in (' . $sub_sql . ')')))
                ->group('s.id')
                ->having('count(r.shipid)<2')
                ->select();
        } catch (\Exception $e) {
            $sh_ship_id = array();
        }

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
}