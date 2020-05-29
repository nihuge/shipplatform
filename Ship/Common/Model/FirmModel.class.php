<?php

namespace Common\Model;

/**
 * 公司管理Model
 * */
class FirmModel extends BaseModel
{
    /**
     * 自动验证
     */
    protected $_validate = array(
        array('firmname', '', '公司名已经存在！', 0, 'unique', 3),
        // 不能为空
        array('firmname', 'require', '公司名称不能为空', 0),// 必须验证 不能为空
//        array('expire_time', 'require', '到期时间不能为空', 0),// 必须验证 不能为空
        array('people', 'require', '联系人不能为空', 0),// 必须验证 不能为空
        array('phone', 'require', '联系电话不能为空', 0),// 必须验证 不能为空
        array('membertype', 'require', '会员标准不能为空', 0),// 必须验证 不能为空
        array('creditline', 'require', '信用额度不能为空', 0),// 必须验证 不能为空
        array('service', 'require', '服务费标准不能为空', 0),// 必须验证 不能为空
        array('balance', 'require', '账户余额不能为空', 0),// 必须验证 不能为空
        array('limit', 'require', '船舶限制个数不能为空', 0),// 必须验证 不能为空
        array('number', 'require', '合同编号不能为空', 0),// 必须验证 不能为空
        // 在一个范围之内
        // array('type',array('月付','季付','年付'),'支付类型的范围不正确！',0,'in'), // 当值不为空的时候判断是否在一个范围内
        array('membertype', array('1', '2'), '会员费标准的范围不正确！', 0, 'in'), // 必须验证 判断是否在一个范围内
        array('firmtype', array('1', '2'), '公司类型的范围不正确！', 0, 'in'), // 必须验证 判断是否在一个范围内
        array('claimed', array('1', '2'), '认领状态不正确！', 0, 'in'), // 必须验证 判断是否在一个范围内
        // 长度判断
        array('expire_time', '1,11', '到期时间长度不能超过11个字符', 0, 'length'),// 必须验证
        array('people', '1,10', '联系人长度不能超过10个字符', 0, 'length'),//必须验证
        array('phone', '1,16', '联系电话长度不能超过16个字符', 0, 'length'),//必须验证
        array('firmname', '1,30', '公司名称长度不能超过30个字符', 0, 'length'),//必须验证
        array('creditline', '1,8', '信用额度长度不能超过8个字符', 0, 'length'),// 必须验证
        array('service', '1,4', '服务费标准长度不能超过4个字符', 0, 'length'),//必须验证
        array('balance', '1,11', '账户余额长度不能超过11个字符', 0, 'length'),//必须验证
        array('limit', '1,5', '船舶限制长度不能超过5个字符', 0, 'length'),//必须验证
        array('number', '1,50', '合同编号长度不能超过50个字符', 0, 'length'),//必须验证
        array('operation_jur', '0,500', '操作权限长度不能超过500个字符', 0, 'length'),//存在字段验证、
        array('location', '0,45', '公司地点长度不能超过45个字符', 0, 'length'),//存在字段验证、
        array('content', '0,500', '公司简介长度不能超过500个字符', 0, 'length'),//存在字段验证、
        array('personality', '0,500', '个性化长度不能超过500个字符', 0, 'length'),//存在字段验证、
        // 判断是否为整数
        array('creditline', 'integer', '信用额度不是整数', 0),
        array('service', 'integer', '服务费标准不是整数', 0),
        array('balance', 'integer', '账户余额不是整数', 0),
        array('limit', 'integer', '船舶限制个数不是整数', 0),
        array('claimed', 'integer', '认领状态不是整数', 0),
    );

    /**
     * 获取公司列表及公司名下所有船列表
     * @return array
     * */
    public function getFirmShip()
    {
        $firmlist = $this
            ->field('id,firmname')
            ->order('id asc')
            ->select();
        $ship = new \Common\Model\ShipModel();
        $sh_ship = new \Common\Model\ShShipModel();
        foreach ($firmlist as $key => $value) {
            $firmlist[$key]['shiplist'] = $ship
                ->field('id,shipname')
                ->where(array('firmid' => $value['id']))
                ->select();
            $firmlist[$key]['sh_shiplist'] = $sh_ship
                ->field('id,shipname')
                ->where(array('firmid' => $value['id']))
                ->select();
        }
        return $firmlist;
    }

    /**
     * 获取公司列表及公司名下所有散货船列表
     * @return array
     * */
//    public function getFirmShShip()
//    {
//        $firmlist = $this
//            ->field('id,firmname')
//            ->order('id asc')
//            ->select();
//        $ship = new \Common\Model\ShShipModel();
//        foreach ($firmlist as $key => $value) {
//            $firmlist[$key]['shiplist'] = $ship
//                ->field('id,shipname')
//                ->where(array('firmid'=>$value['id']))
//                ->select();
//        }
//        return $firmlist;
//    }

    /**
     * 判断公司状态
     * @param int firmid 公司ID
     * @return array
     * @return @param code 返回码
     * */
    public function is_status($firmid)
    {
        $status = $this
            ->field('expire_time,creditline,balance,membertype')
            ->where(array('id' => intval($firmid)))
            ->find();
        if (!empty($status)) {
            // 根据公司会员费标准判断
            if ($status['membertype'] == '1') {
//                // 判断公司是否到期
//                if ($status['expire_time'] > time()) {
                //成功 1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                );
//                } else {
//                    // 公司已到期 1005
//                    $res = array(
//                        'code' => $this->ERROR_CODE_USER['FIRM_EXPIRE']
//                    );
//                }

            } elseif ($status['membertype'] == '2') {
                $num = $status['creditline'] + $status['balance'];
                // 判断公司余额是否有余额
                if ($num > 0) {
                    //成功 1
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                    );
                } else {
                    // 公司余额不足 1011
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['MONEY_NOT_ENOUGH']
                    );
                }

            } else {
                // 公司会员费标准有误   1010
                $res = array(
                    'code' => $this->ERROR_CODE_USER['ERROR_MEMBERTYPE']
                );
            }
        } else {
            // 公司不存在   1009
            $res = array(
                'code' => $this->ERROR_CODE_USER['NOT_FIRM']
            );
        }
        return $res;
    }

    /**
     * 获取公司下操作权限、查询权限、公司权限
     * @param int firmid 公司ID
     * @return array
     * return array(操作权限数组格式、查询权限数组格式)
     * */
    public function getFirmOperationSearch($firmid)
    {
        $firmmsg = $this
            ->field('id,firmname,operation_jur,search_jur,firmtype,firm_jur,sh_operation_jur')
            ->where(array('id' => $firmid))
            ->find();
        $firmmsg['operation_jur'] = explode(',', $firmmsg['operation_jur']);
        $firmmsg['sh_operation_jur'] = explode(',', $firmmsg['sh_operation_jur']);
        $firmmsg['search_jur'] = explode(',', $firmmsg['search_jur']);
        $firmmsg['firm_jur'] = explode(',', $firmmsg['firm_jur']);
        return $firmmsg;
    }

    /**
     * 获取公司下的所有船列表
     * @param int $firmid 公司id
     * @param string $firmtype 公司类型检验、船舶
     * */
    public function getShipList($firmid, $firmtype)
    {
        $ship = new \Common\Model\ShipModel();
        $sh_ship = new \Common\Model\ShShipModel();
        $res = array();
        if ($this->where(array('id' => $firmid, 'firmtype' => $firmtype))->count() < 1) return false;

        // 根据公司类型区分获取的船数据
        if ($firmtype == '2') {
            // 获取该公司下的所有液货船
            $shiplist = $ship
                ->field('id,shipname,data_ship')
                ->where(array('firmid' => $firmid, "del_sign" => 1))
                ->select();

            // 获取该公司下的所有散货船
            $sh_shiplist = $sh_ship
                ->field('id,shipname')
                ->where(array('firmid' => $firmid, "del_sign" => 1))
                ->select();

            $firmname = $this->getFieldById($firmid, 'firmname');

            $res[] = array(
                'id' => $firmid,
                'firmname' => $firmname,
                'shiplist' => $shiplist,
                'sh_shiplist' => $sh_shiplist
            );
        } elseif ($firmtype == '1') {
            // 获取所有的船舶公司的船
            $where = array(
                'firmtype' => '2',
                "del_sign" => 1,
            );
            $res = $this
                ->field('id,firmname')
                ->where($where)
                ->order('id asc')
                ->select();
            foreach ($res as $key => $value) {
                $res[$key]['shiplist'] = $ship
                    ->field('id,shipname,data_ship')
                    ->where(array('firmid' => $value['id'], "del_sign" => 1))
                    ->select();

                $res[$key]['sh_shiplist'] = $sh_ship
                    ->field('id,shipname')
                    ->where(array('firmid' => $value['id'], "del_sign" => 1))
                    ->select();
            }
        }

        return $res;
    }

    /**
     * 获取用户可以操作的船所属公司列表
     * @param int uid 用户ID
     * @return array
     * return array
     */
    public function userOperationFirm($uid)
    {
        $user = new \Common\Model\UserModel();

        $where = array(
            'id' => $uid
        );
        //获取用户的船舶列表id
        $usermsg = $user
            ->field('firmid')
            ->where($where)
            ->find();
        if ($usermsg !== false and !empty($usermsg['firmid'])) {
            $firm_jur = $this
                ->field('firm_jur')
                ->where(array('id' => $usermsg['firmid']))
                ->find();
            $firm_jur = explode(',', $firm_jur['firm_jur']);
            $list = $this
                ->field('id,firmname')
                ->where(array('id' => array('in', $firm_jur)))
                ->select();
            if ($list !== false) {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $list
                );
            } else {
                //数据库连接错误   3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                );
            }
        } else {
            //用户没有归公司  2007
            $res = array(
                'code' => $this->ERROR_CODE_USER['USER_NOT_FIRM']
            );
        }
        return $res;
    }

    /**
     * 检查公司名称是否可注册
     */
    public function check_name($firm_name)
    {
        $user = new \Common\Model\UserModel();
        $map = array(
            'firmname' => $firm_name
        );
        $firm_msg = $this->field('id,firmtype,claimed,del_sign')->where($map)->find();
        if (empty($firm_msg)) return array('code' => $this->ERROR_CODE_COMMON['SUCCESS']);
        if ($firm_msg['firmtype'] == 1) {
            $admin = $user->field('username')->where(array('firmid' => $firm_msg['id'], 'pid' => 0))->find();
            return array('code' => $this->ERROR_CODE_RESULT['FIRM_EXISTS'], 'need_claimed' => 0, 'admin' => $admin['username']);
        }
        if ($firm_msg['firmtype'] == 2) {
            if ($firm_msg['claimed'] == 1) {
                return array('code' => $this->ERROR_CODE_RESULT['FIRM_CLAIMING']);
            } elseif ($firm_msg['claimed'] == 2) {
                $admin = $user->field('username')->where(array('firmid' => $firm_msg['id'], 'pid' => 0))->find();
                return array('code' => $this->ERROR_CODE_RESULT['FIRM_EXISTS'], 'need_claimed' => 0, 'admin' => $admin['username']);
            } elseif ($firm_msg['claimed'] == 0) {
                return array('code' => $this->ERROR_CODE_RESULT['FIRM_EXISTS'], 'need_claimed' => 1);
            }
        }
        return array('code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']);
    }


    /**
     * 认领公司接口
     */
    public function claimed_firm($uid, $firm_name, $shehuicode, $img)
    {
        //开始事务
        M()->startTrans();
        //判断用户是否重复上传审核
        $firm_review = M('firm_review');
        $count_map = array(
            'uid' => $uid,
            'result' => array('neq', 3),//除了审核失败的，不允许再次提交审核
        );
        $count = $firm_review->where($count_map)->count();
        if ($count > 0) {
            // 用户不可以创建多个审核 2042
            $res = array(
                'code' => $this->ERROR_CODE_RESULT['CANNOT_CREATE_MULTIPLE_REVIEWS'],
            );
        } else {
            if ($count !== false) {
                #todo 完善代码，社会信用代码和图片不要放在firm表，放在firm_review表，这样的话可以多个用户同时申请一个公司
                $map = array(
                    'firmname' => $firm_name,
                );
                $firm_claimed_status = $this->field('claimed,claimed_code')->where($map)->find();
                if ($firm_claimed_status['claimed'] == 0 and $firm_claimed_status['claimed_code'] == "") {
                    $data = array(
                        'claimed_code' => $shehuicode,
                        'claimed_img' => $img,
                        'time' => time(),
                    );

                    //将提交的社会信用代码放入
                    $result = $this->editData($map, $data);
                    //如果放入失败，回档
                    if ($result === false) {
                        //回档
                        M()->rollback();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                        );
                    } else {
                        $frim_id = $this->getFieldByFirmname($firm_name, 'id');
                        $review_data = array(
                            'uid' => $uid,
                            'firmid' => $frim_id,
                            'time' => time(),
                        );
                        $review_id = $firm_review->add($review_data);
                        if ($review_id !== false) {
                            $user = new \Common\Model\UserModel();
                            $user_data = array(
                                'reg_status' => 1
                            );
                            $result3 = $user->editData(array('id' => $uid), $user_data);
                            if ($result3 !== false) {
                                M()->commit();
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                    'review_id' => $review_id
                                );
                            } else {
                                M()->rollback();
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                                );
                            }
                        } else {
                            M()->rollback();
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                            );
                        }
                    }
                } else {
                    M()->rollback();
                    //公司正在认领或者已被认领无法继续 2038
                    $res = array('code' => $this->ERROR_CODE_RESULT['FIRM_CLAIMING']);
                }
            } else {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                );
            }
        }

        return $res;
    }


    /**
     * 认证公司，认证后的公司无法被认领
     */
    public function legalize_firm($uid, $shehuicode, $img)
    {
        //开始事务
        M()->startTrans();
        $user = new \Common\Model\UserModel();
        $user_msg = $user->field('firmid,pid')->where(array('id'=>$uid))->find();
        $firm_id = $user_msg['firmid'];

        #todo 完善代码，社会信用代码和图片不要放在firm表，放在firm_review表，这样的话可以多个用户同时申请一个公司
        $map = array(
            'id' => $firm_id,
        );

        $firm_claimed_status = $this->field('legalize_img,legalize_code,claimed')->where($map)->find();
        if ($firm_claimed_status['legalize_img'] == "" and $firm_claimed_status['legalize_code'] == "" and $firm_claimed_status['claimed']==0) {
            $data = array(
                'legalize_code' => $shehuicode,
                'legalize_img' => $img,
                'claimed' => 1,
                'legalize_time' => time(),
            );

            //将提交的社会信用代码放入
            $result = $this->editData($map, $data);
            //如果放入失败，回档
            if ($result === false) {
                //回档
                M()->rollback();
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                    'error' => $this->getDbError()
                );
            } else {
                M()->commit();
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'firm_id' => $firm_id
                );
            }
        } else {
            M()->rollback();
            //公司正在认领或者已被认领无法继续 2038
            $res = array('code' => $this->ERROR_CODE_RESULT['FIRM_CLAIMING']);
        }
        return $res;
    }
}