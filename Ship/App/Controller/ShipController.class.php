<?php

namespace App\Controller;

use Common\Controller\AppBaseController;

/**
 * 船舱操作管理
 */
class ShipController extends AppBaseController
{

    // 定义数据表
    private $db;

    // 构造函数 实例化ShipModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\ShipFormModel();
    }

    /**
     * 获取该用户有权限操作的船舶列表
     */
    public function index()
    {
        if (I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $msg1 = $user->is_judges($uid, $imei);
            if ($msg1['code'] == '1') {
                $usermsg = $user
                    ->where(array('id' => $uid))
                    ->find();
                if ($usermsg !== false or $usermsg['firmid'] !== '') {
                    // 获取公司操作权限船舶
                    $firm = new \Common\Model\FirmModel();
                    $firmmsg = $firm
                        ->where(array('id' => $usermsg['firmid']))
                        ->find();
                    $operation_jur = explode(',', $firmmsg['operation_jur']);
                    $where = array(
                        'id' => array('in', $operation_jur),
                        'del_sign' => 1
                    );

                    if (trimall(I('post.firmid'))) {
                        $where['firmid'] = trimall(I('post.firmid'));
                    }

                    if (trimall(I('post.shipname'))) {
                        $where['shipname'] = array('like', '%' . trimall(I('post.shipname')) . '%');
                    }

                    if (trimall(I('post.shipid'))) {
                        $shipid = trimall(I('post.shipid'));
                        if (in_array($shipid, $operation_jur)) {
                            $where = array();
                            $where['id'] = $shipid;
                        } else {
                            $res = array(
                                'code' => $this->ERROR_CODE_USER['FIRM_NOT_ENOUGH'],
                            );
                            exit(jsonreturn($res));
                        }
                    }

                    $list = $this->db
                        ->field('id,shipname,coefficient,cabinnum,number,is_guanxian,is_diliang,suanfa,goodsname,firmid,expire_time,img')
                        ->where($where)
                        ->order('id desc')
                        ->select();


                    //获取正在审核状态和拒绝状态的船
                    $ship_review = M("ship_review");

                    $where_review = array(
                        '_string' => '(status=1 or status=3) AND ((data_status = 1 and picture=2) or (data_status=2 AND picture=2 and cabin_picture=2) or (data_status=3 AND cabin_picture=2)) AND id in(SELECT max( id ) FROM ship_review GROUP BY shipid)'
                    );

                    $review_list = $ship_review
                        ->field('shipid,status,remark')
                        ->where($where_review)
                        ->select();

                    //匹配船
//                    foreach ($review_list as $key1 => $value1) {
//                        foreach ($list as $key2 => $value2) {
//                            if ($value2['id'] == $value1['shipid'] && $value1['shipid'] != "") {
//
//                            }
//                        }
//                    }

                    //匹配船使用优化写法
                    $keyArr = array();
                    $valArr = array();
                    foreach ($review_list as $k => $v) {
                        array_push($keyArr, $v['shipid']);
                        array_push($valArr, array('status' => $v['status'], 'remark' => $v['remark']));
                    }

                    $newArr = array_combine($keyArr, $valArr); //将两个数组合并为一个，1参数为健，2参数为值，两个数组长度必须相等
                    foreach ($list as $k1 => $v1) {
                        if (array_key_exists($v1['id'], $newArr)) {
                            $list[$k1]['status'] = $newArr[$v1['id']]['status'];
                            if ($newArr[$v1['id']]['status'] == 3) {
                                $list[$k1]['remark'] = $newArr[$v1['id']]['remark'];
                            }
                        } else {
                            $list[$k1]['status'] = "";
                        }
                    }


                    if ($firmmsg['firmtype'] == '1') {
                        // 检验公司获取所有的船公司
                        $firmlist = $firm->field('id,firmname')->where(array('firmtype' => '2', 'del_sign' => 1))->select();
                    } else {
                        // 船舶公司获取本公司
                        $firmlist = $firm->field('id,firmname')->where(array('id' => $usermsg['firmid'], 'del_sign' => 1))->select();
                    }
                    foreach ($list as $key1 => $value1) {
                        $list[$key1]['expire_time'] = date('Y-m-d', $value1['expire_time']);
                    }

                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => array(
                            'list' => $list,
                            'firmlist' => $firmlist,
                        ),
                    );
                } else {
                    //用户没有所属船公司，错误1013
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['USER_NOT_FIRM'],
                    );
                }
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 新增船舶
     */
    public function addship()
    {
        if (I('post.firmid') and I('post.shipname') and I('post.coefficient') and I('post.cabinnum') and I('post.is_guanxian') and I('post.is_diliang') and I('post.suanfa') and I('post.uid') and I('post.imei')) {
            //添加数据
            $data = I('post.');
            $data['uid'] = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges($data['uid'], $imei);
            if ($msg1['code'] == '1') {
                unset($data['imei']);
                $res = judgeTwoString($data);
                if ($res == false) {
                    //错误5，不能含有特殊字符
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL'],
                    );
                } else {
                    // 判断是否有底量测量孔， 有底量测量孔并且有纵倾修正表的话，算法为:c,没有纵倾修正表为D
                    if ($data['is_diliang'] == '1' and $data['suanfa'] == 'b') {
                        $data['suanfa'] = 'c';
                    } elseif ($data['is_diliang'] == '1' and $data['suanfa'] == 'a') {
                        $data['suanfa'] = 'd';
                    }

                    $data['expire_time'] = strtotime(I('post.expire_time'));
                    $res_s = $this->db->addship($data, 'APP');
                    if ($res_s['code'] == 1) {
                        //添加船成功，将此船的操作和查看权限加给检验公司和管理员。
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'ship_id' => $res_s['content']['shipid']
                        );
                    } else {
                        $res = array(
                            'code' => $res_s['code'],
                        );
                    }
                }
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }


    /**
     * 船驳修改
     */
    public function editship()
    {
        $data = I('post.');
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString($data);
        if ($res == false) {
            //错误5，不能含有特殊字符
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL'],
            );
        } else {
            if (I('post.uid') and I('post.imei') and I('post.id')) {
                $user = new \Common\Model\UserModel();
                $msg1 = $user->is_judges($data['uid'], $data['imei']);
                if ($msg1['code'] == '1') {
                    //去除标识
                    unset($data['imei']);
                    $data['expire_time'] = strtotime(I('post.expire_time'));
                    $map = array(
                        'id' => $data['id']
                    );

                    $count = $this->db->where($map)->count();
                    if ($count == 1) {
                        //对data数据进行验证
                        if (!$this->db->create($data)) {
                            //数据库错误，错误3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                            );
//                        echo ajaxReturn(array("state" => 2, 'message' => $this->db->getError()));
                        } else {

                            // 判断是否有底量测量孔，有底量测量孔并且有纵倾修正表的话，算法为:c,没有的话算法为：d
                            if ($data['is_diliang'] == '1' and $data['suanfa'] == 'b') {
                                $data['suanfa'] = 'c';
                            } elseif ($data['is_diliang'] == '1' and $data['suanfa'] == 'a') {
                                $data['suanfa'] = 'd';
                            }

                            /**
                             * 查找船的作业次数
                             */
                            $work = new \Common\Model\WorkModel();
                            $res_count = $work->where(array('shipid' => $data['id']))->count();

                            $old_info = $this->db->field('shipname,cabinnum,coefficient,is_guanxian,is_diliang,suanfa,expire_time,review')->where($map)->find();


                            if ($res_count > 1 or $old_info['review'] == 3) {

                                /**
                                 * 开始对比数据差异，获取更改的数据
                                 */
                                unset($old_info['review']);

                                /**
                                 * 占位数组，防止重复提交时有些值没有被覆盖掉
                                 */
                                $tpl_data = array(
                                    'shipname' => null,
                                    'cabinnum' => null,
                                    'coefficient' => null,
                                    'is_guanxian' => null,
                                    'is_diliang' => null,
                                    'suanfa' => null,
                                    'expire_time' => null,
                                );
                                //对比差异
                                $diff_info = array_diff_assoc($old_info, $data);
                                //新值赋值
                                foreach ($diff_info as $key => $value) {
                                    $diff_info[$key] = $data[$key];
                                }

                                $ship_review = M("ship_review");
                                if ($diff_info['shipname'] !== null) {
                                    //验证船名是否和已有的船名重复
                                    $name_count = $this->db->where(array('shipname' => $diff_info['shipname']))->count();
                                    //验证船名是否和正在审核中其他船的船名重复
                                    $review_name_count = $ship_review->where(array(
                                        'shipname' => $diff_info['shipname'],
                                        'shipid' => array('neq', $data['id']),
                                        'status' => 1
                                    ))->count();

                                    if ($name_count > 0 or $review_name_count > 0) {
                                        //船舶已存在   2014
                                        $res = array(
                                            'code' => $this->ERROR_CODE_RESULT['HAVE_SHIP'],
                                            'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['HAVE_SHIP']]
                                        );
                                        exit(jsonreturn($res));
                                    }
                                }


                                if ($diff_info['cabinnum'] !== null) {
                                    if ($diff_info['cabinnum'] >= $old_info['cabinnum']) {
                                        //不可以减少舱总数，2026
                                        $res = array(
                                            'code' => $this->ERROR_CODE_RESULT['CAN_NOT_REDUCE_CABIN_NUM'],
                                            'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['CAN_NOT_REDUCE_CABIN_NUM']]
                                        );
                                        exit(jsonreturn($res));
                                    }
                                }


                                $review_data = array_merge($tpl_data, $diff_info);


                                $review_data['shipid'] = $data['id'];
                                $review_data['userid'] = I("post.uid");
                                $review_data['create_time'] = time();

                                $review_map = array(
                                    'shipid' => $data['id'],
                                    'status' => 1
                                );

                                /**
                                 * 重复上传会覆盖。以最新的为准
                                 */
                                M()->startTrans();
                                $review_count = $ship_review->where($review_map)->count();
                                if ($review_count >= 1) {
                                    //修改
                                    $result = $ship_review->where($review_map)->save($review_data);
                                    //修改时获取主键ID
                                    if ($result !== false) {
                                        $id = $ship_review->field('id,data_status,cabin_picture,picture')->where($review_map)->find();
                                        $result = (int)$id['id'];
                                        if ($id['data_status'] == 3 and $id['cabin_picture'] == 1) {
                                            //如果状态是上传舱信息但没有舱照片则改为只上传了船信息
                                            $status_data = array(
                                                'data_status' => 1
                                            );
                                            $status_result = $ship_review->where($review_map)->save($status_data);
                                            if ($status_result === false) {
                                                M()->rollback();
                                                //修改失败,错误11
                                                $res = array(
                                                    'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                                );
                                                exit(jsonreturn($res));
                                            }
                                        }
                                    }
                                } else {
                                    //新建
                                    $result = $ship_review->add($review_data);
                                }
                                if ($result !== false) {
                                    M()->commit();
                                    //等待审核
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['WAIT_REVIEW'],
                                        'review_id' => $result
                                    );
//                                    echo ajaxReturn($res);
                                } else {
                                    M()->rollback();
                                    //修改失败,错误11
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                    );
                                }
                            } else {
                                $result = $this->db->editData($map, $data);
                                if ($result !== false) {
                                    //成功
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                    );
//                                    echo ajaxReturn($res);
                                } else {
                                    //修改失败,错误11
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                    );
//                            echo ajaxReturn(array("state" => 2, 'message' => "修改失败"));
                                }
                            }
                        }

                    } else {
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['NOT_SHIP'],
                        );
                    }
                } else {
                    // 错误信息返回码
                    $res = $msg1;
                }
            } else {
                //参数不正确，参数缺失	4
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
                );
            }
        }
        echo jsonreturn($res);
    }
}