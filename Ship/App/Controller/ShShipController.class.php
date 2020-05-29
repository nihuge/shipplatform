<?php

namespace App\Controller;

use Common\Controller\AppBaseController;

/**
 * 船舱操作管理
 */
class ShShipController extends AppBaseController
{

    // 定义数据表
    private $db;

    // 构造函数 实例化ShipModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\ShShipModel();
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
                        ->field('firmtype,sh_operation_jur')
                        ->where(array('id' => $usermsg['firmid']))
                        ->find();
                    $operation_jur = explode(',', $firmmsg['sh_operation_jur']);

                    $where = array('del_sign' => 1, 'id' => array('in', $operation_jur));

                    if (trimall(I('post.firmid'))) {
                        $where['firmid'] = trimall(I('post.firmid'));
                    }

                    if (trimall(I('post.shipname'))) {
                        $where['shipname'] = array('like', '%' . trimall(I('post.shipname')) . '%');
                    }

                    if (trimall(I('post.shipid'))) {
                        $shipid = trimall(I('post.shipid'));
                        $where = array();
                        $where['id'] = $shipid;
                    }

                    $list = $this->db
                        ->field('id,shipname,cabinnum,number,weight,goodsname,DF,DA,DM,0 + CAST(lbp as char) as lbp,firmid,expire_time,img,ptwd')
                        ->where($where)
                        ->order('id desc')
                        ->select();


                    //获取正在审核状态和拒绝状态的船
                    $ship_review = M("sh_review");
                    //取最新状态
                    $where_review = array(
                        '_string' => '(status=1 or status=3) AND picture=2 AND id in(SELECT max( id ) FROM sh_review GROUP BY shipid)'
                    );

                    $review_list = $ship_review
                        ->field('shipid,status,remark')
                        ->where($where_review)
                        ->select();

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
                        $firmlist = $firm->field('id,firmname')->where(array('firmtype' => '2'))->select();
                    } else {
                        // 船舶公司获取本公司
                        $firmlist = $firm->field('id,firmname')->where(array('id' => $usermsg['firmid']))->select();
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
        if (I('post.firmid') and I('post.shipname') and I('post.lbp') != null
            and I('post.df') != null and I('post.da') != null
            and I('post.dm') != null and I('post.uid') and I('post.cabinnum') != null
            and I('post.imei') and I('post.expire_time') and I('post.ptwd')) {
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
                    $data['expire_time'] = strtotime(I('post.expire_time'));
                    $res_s = $this->db->addship($data, 'APP');
                    if ($res_s['code'] == 1) {
                        //添加船成功，将此船的操作和查看权限加给检验公司和管理员
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
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
                            /**
                             * 查找船的作业次数
                             */
                            $work = new \Common\Model\ShResultModel();
                            $res_count = $work->where(array('shipid' => $data['id']))->count();

                            //去除多余的0，防止验证差异时出错
                            $old_info = $this->db->field('is_lock,shipname,cabinnum,0+cast(lbp as char) as lbp,0+cast(df as char) as df,0+cast(da as char) as da,0+cast(dm as char) as dm,weight,0+cast(ptwd as char) as ptwd,expire_time,review')->where($map)->find();


                            if ($old_info['is_lock'] == 1) {

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
                                    'lbp' => null,
                                    'df' => null,
                                    'da' => null,
                                    'dm' => null,
                                    'weight' => null,
                                    'ptwd' => null,
                                    'expire_time' => null,
                                );
                                //对比差异
                                $diff_info = array_diff_assoc($old_info, $data);

                                //新值赋值
                                foreach ($diff_info as $key => $value) {
                                    $diff_info[$key] = $data[$key];
                                }

                                $sh_review = M("sh_review");
                                if ($diff_info['shipname'] !== null) {
                                    //验证船名是否和已有的船名重复
                                    $name_count = $this->db->where(array('shipname' => $diff_info['shipname']))->count();
                                    //验证船名是否和正在审核中其他船的船名重复
                                    $review_name_count = $sh_review->where(array(
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
                                $review_count = $sh_review->where($review_map)->count();
                                if ($review_count >= 1) {
                                    //修改
                                    $result = $sh_review->where($review_map)->save($review_data);
                                    //修改时获取主键ID
                                    if ($result !== false) {
                                        $id = $sh_review->field('id')->where($review_map)->find();
                                        $result = (int)$id['id'];
                                    }
                                } else {
                                    //新建
                                    $result = $sh_review->add($review_data);
                                }
                                if ($result !== false) {
                                    //等待审核
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['WAIT_REVIEW'],
                                        'review_id' => $result
                                    );
//                                    echo ajaxReturn($res);
                                } else {
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
                                    echo ajaxReturn($res);
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


    /**
     * 追加复核船信息通知
     */
    public function add_edit_review_notice(){
        if (I('post.uid') and I('post.imei') and I('post.review_id')){
            $uid = intval(trimall(I('uid')));
            $imei = trimall(I('imei'));
            $review_id = intval(trimall(I('post.review_id')));

            $user = new \Common\Model\UserModel();
            $msg =$user->is_judges($uid,$imei);
            if($msg['code'] == 1){
                $user_info =$user->getUserOpenId($uid);
                $where = array(
                    'id'=>$review_id
                );
                $data = array(
                    'open_id'=>$user_info['open_id']
                );
                $sh_review = M("sh_review");
                $result = $sh_review->where($where)->save($data);
                if($result !== false){
                    $res = array(
                        'code'=>$this->ERROR_CODE_COMMON['SUCCESS']
                    );
                }else{
                    $res = array(
                        'code'=>$this->ERROR_CODE_COMMON['DB_ERROR'],
                        'error'=>$sh_review->getDbError(),
                    );
                }
            }else{
                $res = $msg;
            }
        }else{
            //缺少参数
            $res = array(
                'code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
            );
        }
        exit(jsonreturn($res));
    }
}