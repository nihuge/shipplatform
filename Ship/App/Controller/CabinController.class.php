<?php

namespace App\Controller;

use Common\Controller\AppBaseController;

/**
 * 船舱操作管理
 */
class CabinController extends AppBaseController
{

    // 定义数据表
    private $db;

    // 构造函数 实例化ShipModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\CabinModel();
    }

    /**
     * 获取船舶内对应的船舱列表
     */
    public function index()
    {
        if (I('post.uid') and I('post.imei') and I('post.shipid')) {
            $user = new \Common\Model\UserModel();
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $msg1 = $user->is_judges($uid, $imei);
            if ($msg1['code'] == '1') {
                $shipid = trimall(I('shipid'));
                $usermsg = $user
                    ->where(array('id' => $uid))
                    ->find();
                if ($usermsg !== false or $usermsg['firmid'] !== '') {
                    // 获取公司操作权限船舶
                    $firm = new \Common\Model\FirmModel();

                    $firmmsg = $firm
                        ->field('firmtype,operation_jur')
                        ->where(array('id' => $usermsg['firmid']))
                        ->find();
                    $operation_jur = explode(',', $firmmsg['operation_jur']);

                    //判断该公司有没有操作权限,判断该公司是不是检验公司
                    if ($firmmsg['firmtype'] == '1' and in_array($shipid, $operation_jur)) {
                        $ship = new \Common\Model\ShipModel();
                        //获得该船总共多少个船舱
                        $where1 = array(
                            'id' => $shipid,
                        );
                        $shipmsg = $ship
                            ->field('cabinnum,suanfa')
                            ->where($where1)
                            ->find();

                        $where = array(
                            'shipid' => $shipid,
                        );
                        //获得该船已经录入了多少个船舱
                        $count = $this->db->where($where)->count();
                        //获取船舱数据
                        $list = $this->db
                            ->where($where)
                            ->order('order_number asc,id asc')
                            ->select();
                        //如果录入的船舱不等于已经录完的船舱,那就算没有创建完船舱,就返回需要继续录入的数量
                        if ($shipmsg['cabinnum'] == $count) {
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                'content' => $list
                            );
                        } else {
                            //返回需要创建多少个船舱
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                'need_add' => $shipmsg['cabinnum'] - $count,
                                'suanfa' => $shipmsg['suanfa'],
                                'count' => $shipmsg['cabinnum'],
                                'content' => $list,
                            );
                        }
                    } else {
                        //公司权限不足，无法操作船 1016
                        $res = array(
                            'code' => $this->ERROR_CODE_USER['FIRM_NOT_ENOUGH'],
                        );
                    }
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
     * 新增船舱
     */
    public function addcabin()
    {
        if (I('post.uid') and I('post.imei') and I('post.shipid')) {
            $user = new \Common\Model\UserModel();
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $shipid = trimall(I('post.shipid'));
            $msg1 = $user->is_judges($uid, $imei);
            if ($msg1['code'] == '1') {
                // 获取公司操作权限船舶
                $firm = new \Common\Model\FirmModel();
                $firmmsg = $firm
                    ->field('firmtype,operation_jur')
                    ->where(array('id' => $msg1['content']))
                    ->find();
                $operation_jur = explode(',', $firmmsg['operation_jur']);

                //判断该公司有没有操作权限,判断该公司是不是检验公司
                if ($firmmsg['firmtype'] == '1' and in_array($shipid, $operation_jur)) {
                    $ship = new \Common\Model\ShipModel();
                    //获得该船总共多少个船舱
                    $where1 = array(
                        'id' => $shipid,
                    );
                    $shipmsg = $ship
                        ->field('cabinnum,suanfa')
                        ->where($where1)
                        ->find();
                    //获得提交过来的船舱数
                    $cabin_request_num = count(I('post.data'));

                    //获得该船已经录入了多少个船舱
                    $where = array(
                        'shipid' => $shipid,
                    );
                    $count = $this->db->where($where)->count();

                    if ($cabin_request_num == ($shipmsg['cabinnum'] - $count)) {
                        //判断同一条船不能有重复的舱名
                        $data = array();
                        $names = array();
                        foreach (I('post.data') as $key => $value) {
                            //判断用户录入舱时数据是否有错误,检测小数点数量
                            $num[] = substr_count($value['altitudeheight'], ".");
                            $num[] = substr_count($value['bottom_volume'], ".");
                            $num[] = substr_count($value['pipe_line'], ".");

                            if (strtolower($shipmsg['suanfa']) == "c" || strtolower($shipmsg['suanfa']) == "d") {
                                $num[] = substr_count($value['bottom_volume_di'], ".");
                                $num[] = substr_count($value['dialtitudeheight'], ".");
                            }

                            foreach ($num as $key2 => $value2) {
                                if ($value2 !== 1) {
                                    //小数点不管多了还是少了，都肯定数据格式有问题，直接报错返回7，数据格式有误
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_DATA']
                                    );
                                    exit(jsonreturn($res));
                                }
                            }

                            $where = array(
                                'shipid' => $shipid,
                                'cabinname' => $value['cabinname']
                            );

                            $count = $this->db->where($where)->count();
                            if ($count > 0) {
                                $res = array(
                                    'code' => $this->ERROR_CODE_RESULT['HAVE_CABIN']
                                );
                                exit(jsonreturn($res));
                            }
                            $names[] = $value['cabinname'];
                            $value['shipid'] = I('post.shipid');
                            $data[] = $value;
                        }

                        // 判断提交的舱名是否有重复
                        $repeat_arr = FetchRepeatMemberInArray($names);
                        if ($repeat_arr) {
                            $res = array(
                                'code' => $this->ERROR_CODE_RESULT['HAVE_CABIN']
                            );
                            exit(jsonreturn($res));
                        }
                        M()->startTrans();
                        foreach ($data as $key => $value) {
                            // 对数据进行验证
                            if (!$this->db->create($value)) {
                                M()->rollback();
                                // 如果创建失败 表示验证没有通过 输出错误提示信息.数据验证失败错误12
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                    'massage' => $this->db->getError(),
                                );
                                exit(jsonreturn($res));
                            } else {
                                try {
                                    // 验证通过 可以进行其他数据操作
                                    $res = $this->db->addData($value);
                                } catch (\Exception $e) {
                                    //如果捕捉到异常，直接rollback并返回错误,错误12
                                    M()->rollback();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                        'massage' => $e->getMessage(),
                                    );
                                    exit(jsonreturn($res));
                                }
                                if ($res) {

                                } else {
                                    M()->rollback();
                                    // 如果创建失败 表示验证没有通过 输出错误提示信息,数据验证失败错误12
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                        'massage' => $this->db->getError(),
                                    );
                                    exit(jsonreturn($res));
                                }
                            }
                        }

                        $ship_data = array(
                            'review' => 2
                        );
                        $ship->editData(array('id' => $shipid), $ship_data);

                        M()->commit();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        );
                    } else {
                        //传入的船舱数据数量不足，错误2021
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['NOT_ALL_CABIN'],
                        );
                    }
                } else {
                    //公司权限不足，无法操作船 1016
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['FIRM_NOT_ENOUGH'],
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
     * 船舱修改
     */
    public function editcabin()
    {
        if (I('post.uid') and I('post.imei') and I('post.shipid')) {
            $user = new \Common\Model\UserModel();
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $shipid = trimall(I('post.shipid'));

            $msg1 = $user->is_judges($uid, $imei);
            if ($msg1['code'] == '1') {
                // 获取公司操作权限船舶
                $firm = new \Common\Model\FirmModel();
                $firmmsg = $firm
                    ->field('firmtype,operation_jur')
                    ->where(array('id' => $msg1['content']))
                    ->find();
                $operation_jur = explode(',', $firmmsg['operation_jur']);

                //判断该公司有没有操作权限,判断该公司是不是检验公司
                if ($firmmsg['firmtype'] == '1' and in_array($shipid, $operation_jur)) {
                    $ship = new \Common\Model\ShipModel();
                    //获得该船总共多少个船舱
                    $where1 = array(
                        'id' => $shipid,
                    );
                    //获得算法
                    $shipmsg = $ship
                        ->field('suanfa,review')
                        ->where($where1)
                        ->find();

                    //判断同一条船不能有重复的舱名
                    $data = array();
                    $names = array();

                    foreach (I('post.data') as $key => $value1) {
                        $num = array();
                        //判断用户录入舱时数据是否有错误,检测小数点数量
                        $num[] = substr_count($value1['altitudeheight'], ".");
                        $num[] = substr_count($value1['bottom_volume'], ".");
                        $num[] = substr_count($value1['pipe_line'], ".");
                        if (strtolower($shipmsg['suanfa']) == "c" || strtolower($shipmsg['suanfa']) == "d") {
                            $num[] = substr_count($value1['bottom_volume_di'], ".");
                            $num[] = substr_count($value1['dialtitudeheight'], ".");
                        }
                        foreach ($num as $key2 => $value2) {
                            if ($value2 !== 1) {
                                //小数点不管多了还是少了，都肯定数据格式有问题，直接报错返回7，数据格式有误
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                                    'key' => $key2,
                                    'value' => $value2
                                );
                                exit(jsonreturn($res));
                            }
                        }
                        $names[] = $value1['cabinname'];
                        $data[] = $value1;
                    }

                    // 判断提交的舱名是否有重复
                    $repeat_arr = FetchRepeatMemberInArray($names);
                    if ($repeat_arr) {
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['HAVE_CABIN']
                        );
                        exit(jsonreturn($res));
                    }

                    /**
                     * 查找船的作业次数
                     */
                    $work = new \Common\Model\WorkModel();
                    $res_count = $work->where(array('shipid' => $shipid))->count();
                    if ($res_count > 1 or $shipmsg['review'] == 3) {
                        M()->startTrans();
                        /**
                         * 由于舱审核信息挂载载船的审核信息上，所以要考虑几种情况
                         *
                         * 1、没有船审核记录，但是提交了舱审核记录 ： 建立一个新的船审核记录，除了必要信息，其他内容全部留空
                         * 2、有船审核记录，提交了舱审核记录 ： 获得主键ID，用于外键连接
                         */
                        $review_data['shipid'] = $shipid;
                        $review_data['userid'] = $uid;
                        $review_data['create_time'] = time();

                        //用于新建船审核记录的数据
                        $ship_review_data = $review_data;

                        $ship_review_map = array(
                            'shipid' => $shipid,
                            'status' => 1
                        );

                        /**
                         * 判断是否有船审核记录
                         */
                        $ship_review = M('ship_review');
                        $ship_review_count = $ship_review->field('id,data_status,cabin_picture,picture')->where($ship_review_map)->find();

                        /**
                         * 建立一个新的船审核记录或获得主键ID
                         */

                        if (count($ship_review_count) >= 1) {
                            if ($ship_review_count['data_status'] == 1 and $ship_review_count['picture'] == 1) {
                                //如果状态为已上传船信息没有上传舱新信息，且没有上传船修改照片，则改为只上传了舱信息
                                $ship_review_data['data_status'] = 3;
                                $result = $ship_review->where($ship_review_map)->save($ship_review_data);
                                if ($result !== false) {
                                    $result = $ship_review_count['id'];
                                }
                            } else {
                                $result = $ship_review_count['id'];
                            }
                        } else {
                            //新建,3为上传了舱审核没有船审核
                            $ship_review_data['data_status'] = 3;
                            $result = $ship_review->add($ship_review_data);
                        }

                        if ($result !== false) {
                            foreach ($data as $key => $value) {
                                $cabin_id = $value['id'];
                                unset($value['id']);

                                /**
                                 * 复核授权机制判断
                                 */
                                if (strtolower($shipmsg['suanfa']) == "c" || strtolower($shipmsg['suanfa']) == "d") {
                                    $field = 'cabinname,altitudeheight,dialtitudeheight,bottom_volume,bottom_volume_di,pipe_line';
                                } else {
                                    $field = 'cabinname,altitudeheight,bottom_volume,pipe_line';
                                }

                                $old_info = $this->db->field($field)->where(array('id' => $cabin_id, 'shipid' => $shipid))->find();
                                if (isset($old_info['cabinname'])) {
                                    /**
                                     * 占位数组，防止重复提交时有些值没有被覆盖掉
                                     */
                                    $tpl_data = array(
                                        'cabinname' => null,
                                        'altitudeheight' => null,
                                        'dialtitudeheight' => null,
                                        'bottom_volume' => null,
                                        'bottom_volume_di' => null,
                                        'pipe_line' => null,
                                    );


                                    //对比差异
                                    $diff_info = array_diff_assoc($old_info, $value);

                                    foreach ($diff_info as $key1 => $value1) {
                                        $diff_info[$key1] = $value[$key1];
                                    }

                                    if ($diff_info['cabinname'] !== false) {
                                        $cabin_review = M("cabin_review");
                                        //验证舱名是否和同一条船内已有的舱名重复
                                        $name_count = $this->db->where(array('cabinname' => $diff_info['cabinname'], 'shipid' => $shipid))->count();
                                        //验证舱名是否和正在审核中相同船的其他舱名重复
                                        $review_name_count = $cabin_review
                                            ->alias('c')
                                            ->join('right join ship_review as s on c.review_id=s.id')
                                            ->where(array(
                                                's.status' => 1,
                                                's.shipid' => $shipid,
                                                'c.cabinname' => $diff_info['cabinname'],
                                                'c.cabinid' => array('neq', $cabin_id)
                                            ))->count();

                                        if ($name_count > 0 or $review_name_count > 0) {
                                            M()->rollback();
                                            //船舶已存在   2014
                                            $res = array(
                                                'code' => $this->ERROR_CODE_RESULT['HAVE_CABIN'],
                                                'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['HAVE_CABIN']]
                                            );
                                            exit(jsonreturn($res));
                                        }
                                    }

                                    //合并数组
                                    $cabin_review_data = array_merge($tpl_data, $diff_info, $review_data);
                                    //如果成功获取到了审核ID
                                    $cabin_review_data['review_id'] = $result;
                                    $cabin_review_data['cabinid'] = $cabin_id;
                                    $review_map = array(
                                        'shipid' => $shipid,
                                        'review_id' => $result,
                                        'cabinid' => $cabin_id,
                                    );

                                    //获取舱审核的数量
                                    $review_count = $cabin_review->where($review_map)->count();

                                    if ($review_count >= 1) {
                                        //已存在则覆盖新的舱审核
                                        $cabin_result = $cabin_review->where($review_map)->save($cabin_review_data);
                                    } else {
                                        //不存在则创建新的审核信息
                                        $cabin_result = $cabin_review->add($cabin_review_data);
                                    }

                                    if ($cabin_result === false) {
                                        //回滚
                                        M()->rollback();
                                        //修改失败,错误11
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                        );
                                        exit(jsonreturn($res));
                                    }
                                } else {
                                    //回滚
                                    M()->rollback();
                                    //未找到舱,错误2027
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['NOT_FIND_CABIN'],
                                    );
                                    exit(jsonreturn($res));
                                }
                            }
                            //提交并等待审核
                            M()->commit();
                            $res = array(
                                'code' => $this->ERROR_CODE_RESULT['WAIT_REVIEW'],
                                'review_id' => $result
                            );
                        } else {
                            M()->rollback();
                            //修改失败,错误11
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                            );
                            exit(jsonreturn($res));
                        }

                    } else {
                        M()->startTrans();
                        foreach ($data as $key => $value) {
                            //给出条件
                            $map = array(
                                'id' => $value['id']
                            );
                            unset($value['id']);
                            // 对数据进行验证
                            if (!$this->db->create($value)) {
                                // 如果修改失败 表示验证没有通过 输出错误提示信息.数据验证失败错误11
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                    'massage' => $key,
                                );
                                exit(jsonreturn($res));
                            } else {
                                unset($value['id']);
                                // 验证通过 可以进行其他数据操作
                                try {
                                    $res = $this->db->editData($map, $value);
                                } catch (\Exception $e) {
                                    //如果捕捉到异常，直接rollback并返回错误,错误12
                                    M()->rollback();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                        'massage' => $e->getMessage(),
                                    );
                                    exit(jsonreturn($res));
                                }
                                if ($res === false) {
                                    M()->rollback();
                                    // 如果创建失败 表示验证没有通过 输出错误提示信息,数据验证失败错误11
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                        'massage' => $res,
                                    );
                                    exit(jsonreturn($res));
                                }

                            }
                        }
                        M()->commit();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        );
                    }
                } else {
                    //公司权限不足，无法操作船 1016
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['FIRM_NOT_ENOUGH'],
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
}