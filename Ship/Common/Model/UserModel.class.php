<?php

namespace Common\Model;

use Common\Model\BaseModel;

/**
 * 用户model
 * */
class UserModel extends BaseModel
{
    /**
     * 自动验证
     */
    protected $_validate = array(
        array('title', '', '账号已经存在！', 0, 'unique', 3),
        array('title', '1,15', '账号长度不能超过15个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('username', '1,15', '用户名长度不能超过15个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('phone', '0,16', '联系电话长度不能超过16个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('firmid', 'require', '公司名称不能为空', 0),//存在即验证 不能为空
        array('title', 'require', '账号不能为空', 0),//存在即验证 不能为空
        array('operation_jur', '0,255', '操作权限长度不能超过255个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('search_jur', '0,255', '查询权限长度不能超过255个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
    );

    /*
     * 获取用户树桩列表
     * return array $getuserlist
     */
    public function getuserlist($begin, $per, $where)
    {
        // 用户列表
        $data = $this
            ->where($where)
            ->order('id is null,id')
            ->limit($begin, $per)
            ->select();
        $userlist = \Org\Nx\Data::tree($data, 'title', 'id', 'pid');

        // 获取用户所属公司
        $firm = new \Common\Model\FirmModel();
        foreach ($userlist as $key => $value) {
            $userlist[$key]['firmname'] = $firm->getFieldById($value['firmid'], 'firmname');
        }
        return $userlist;
    }

    /**
     * 判断用户状态、标识是否正确、公司状态
     * @param int uid 用户ID
     * @param string imei 标识
     * @return array
     * @return @param code 返回码
     * */
    public function is_judges($uid, $imei)
    {
        if (I('post.miniprogram') or I('get.miniprogram')) {
            $miniprogram = trimall(I('post.miniprogram') ? I('post.miniprogram') : I('get.miniprogram'));
            if ($miniprogram == "cb") {
                $field = 'status,cbimei as imei,firmid';
            } elseif ($miniprogram == "yc") {
                $field = 'status,imei,firmid';
            } elseif ($miniprogram == "sh") {
                $field = 'status,shimei as imei,firmid';
            } else {
                $field = 'status,imei,firmid';
            }
        } else {
            $field = 'status,imei,firmid';
        }

        //判断标识是否一致
        $umsg = $this
            ->field($field)
            ->where(array('id' => $uid))
            ->find();
        if ($umsg['imei'] == $imei) {
            // 判断公司状态
            $firm = new \Common\Model\FirmModel();
            $a = $firm->is_status($umsg['firmid']);
            if ($a['code'] == '1') {
                if ($umsg['status'] == '1') {
                    //成功 1
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $umsg['firmid']
                    );
                } else {
                    //该用户被冻结    1004
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['USER_FROZEN']
                    );
                }
            } else {
                $res = $a;
            }
        } else {
            //标识错误  1007
            $res = array(
                'code' => $this->ERROR_CODE_USER['USER_IMEI_ERROR']
            );
        }
        return $res;
    }

    // /**
    //  * 判断用户是否有权限对该公司进行操作
    //  * @param int uid 用户ID
    //  * @param int firmid 公司ID
    //  * @return array
    //  */
    // public function is_userfirm($uid,$firmid)
    // {
    //     $msg = $this
    //         ->alias('u')
    //         ->field('f.firm_jur')
    //         ->join('left join firm f on f.id = u.firmid')
    //         ->where(array('u.id'=>$uid))
    //         ->find();
    //     $firm_jur = explode(',',$msg['firm_jur']);    
    //     if (in_array($firmid,$firm_jur)) 
    //     {
    //         $res = true;
    //     } else {
    //         $res = false;
    //     }
    //     return $res;
    // }

    /**
     * 判断用户状态、公司状态
     * @param int uid 用户ID
     * @return array
     * @return @param code 返回码
     * */
    public function is_judge($uid)
    {
        //判断标识是否一致
        $umsg = $this
            ->field('status,firmid')
            ->where(array('id' => $uid))
            ->find();
        // 判断公司状态
        $firm = new \Common\Model\FirmModel();
        $a = $firm->is_status($umsg['firmid']);
        if ($a['code'] == '1') {
            if ($umsg['status'] == '1') {
                //成功 1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $umsg['firmid']
                );
            } else {
                //该用户被冻结    1004
                $res = array(
                    'code' => $this->ERROR_CODE_USER['USER_FROZEN']
                );
            }
        } else {
            $res = $a;
        }
        return $res;
    }

    /**
     * 用户登陆
     * @param string title 账号
     * @param string pwd 密码
     * @param string imei 标识
     * @return array
     * @return @param code:返回码
     * @return @param content:内容、说明
     * */
    public function login($title, $pwd, $imei)
    {
        $where = array(
            'title' => $title,
            'pwd' => encrypt($pwd)
        );
        $arr = $this
            ->alias('u')
            ->join('left join firm f on f.id = u.firmid')
//            ->field('u.status,u.imei,u.firmid,f.firmtype')
            ->field('u.id,u.title,u.username,u.status,u.firmid,f.firmtype,u.pid')
            ->where($where)
            ->find();
        if ($arr != '') {
            // 判断用户状态
            if ($arr['status'] == '1') {
                // 判断公司状态
                $firm = new \Common\Model\FirmModel();
                $firmStatus = $firm->is_status($arr['firmid']);
                if ($firmStatus['code'] == '1') {
                    //修改标识数据
                    $map = array('id' => $arr['id']);

                    /**
                     * 判断是否是小程序
                     */
                    if (I('post.miniprogram')) {
                        $miniprogram = trimall(I('post.miniprogram'));
                        if ($miniprogram == "cb") {
                            $data = array('cbimei' => $imei);
                        } elseif ($miniprogram == "yc") {
                            $data = array('imei' => $imei);
                        } elseif ($miniprogram == "sh") {
                            $data = array('shimei' => $imei);
                        } else {
                            $data = array('imei' => $imei);
                        }
                    } else {
                        $data = array('imei' => $imei);
                    }

                    if ($this->editData($map, $data) !== false) {
                        //成功 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'content' => $arr
                        );
                    } else {
                        // 数据库操作错误  3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                        );
                    }
                } else {
                    // 返回错误
                    $res = $firmStatus;
                }
            } else {
                // 用户已被禁止    1004
                $res = array(
                    'code' => $this->ERROR_CODE_USER['USER_FROZEN']
                );
            }
        } else {
            //用户名或密码错误  1001
            $res = array(
                'code' => $this->ERROR_CODE_USER['USER_NOT_EXIST']
            );
        }
        return $res;
    }

    /**
     * 用户修改密码
     * @param int uid 用户ID
     * @param string oldpwd 原密码
     * @param string newpwd 新密码
     * @param string repeatpwd 重复新密码
     * @param string imei 标识
     * @return array
     * @return @param code:返回码
     */
    public function changePwd($uid, $oldpwd, $newpwd, $repeatpwd, $imei)
    {
        if ($newpwd === $repeatpwd) {
            //判断用户状态、标识比对、公司状态
            $msg1 = $this->is_judges($uid, $imei);
            if ($msg1['code'] == '1') {
                // 检验原密码是否正确 
                $msg = $this
                    ->field('pwd')
                    ->where(array('id' => $uid))
                    ->find();
                if ($msg != '' and $msg !== false) {
                    //判断原始密码对否正确
                    $pwdold = encrypt($oldpwd);
                    if ($pwdold == $msg['pwd']) {
                        $newpwd = trimall($newpwd);
                        //修改密码
                        $data = array(
                            'pwd' => encrypt($newpwd)
                        );
                        $res1 = $this->where(array('id' => $uid))->save($data);
                        if ($res1 !== false) {
                            // 成功    1
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                            );
                        } else {
                            // 数据库操作错误  3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                            );
                        }
                    } else {
                        // 原始密码不正确  1003
                        $res = array(
                            'code' => $this->ERROR_CODE_USER['USER_ORIGINALPASSWORD_ERROR']
                        );
                    }
                } else {
                    // 该用户不存在  1006
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['USER_IS_NOT']
                    );
                }
            } else {
                //未到期/状态禁止
                $res = $msg1;
            }
        } else {
            // 两次密码不相同 1002
            $res = array(
                'code' => $this->ERROR_CODE_USER['USER_PASSWORD_NOT_MATCH']
            );
        }
        return $res;
    }

    /**
     * 获取用户的操作权限、查询权限
     * @param int $uid 用户ID
     * @return array res
     * @return array res operation_jur 操作权限
     * @return array res search_jur 查询权限
     * */
    public function getUserOperationSeach($uid)
    {
        $usermsg = $this
            ->field('id,operation_jur,search_jur,look_other,firmid')
            ->where(array('id' => $uid))
            ->find();
        $usermsg['operation_jur_array'] = explode(',', $usermsg['operation_jur']);
        $usermsg['search_jur_array'] = explode(',', $usermsg['search_jur']);
        $usermsg['look_other'] = $usermsg['look_other'];
        return $usermsg;
    }

    /**
     * 新增用户信息
     */
    public function adddatas($data)
    {
        $pwd = "000000";
        $data['pwd'] = encrypt($pwd);   //加密
        // 判断是否提交操作权限，查询权限在新增的时候与操作权限一样
        if ($data['operation_jur']) {
            // 将数组转换字符串
            $operation_jur = implode(',', $data['operation_jur']);
            $data['operation_jur'] = $operation_jur;
            $data['search_jur'] = $operation_jur;
        } else {
            // 没有传值
            $data['operation_jur'] = '';
        }

        if (!$this->create($data)) {
            //对data数据进行验证
            //数据格式有错  7
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                'msg' => $this->getError()
            );
        } else {
            $userid = $this->addData($data);
            if ($userid) {
                // 添加用户历史数据汇总初步
                $arr = array('userid' => $userid);
                M('user_historical_sum')->add($arr);

                //成功   1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']]
                );
            } else {
                //数据库连接错误   3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                    'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DB_ERROR']]
                );
            }
        }
        return $res;
    }
}