<?php

namespace App\Controller;

use Common\Controller\AppBaseController;

//use function AlibabaCloud\Client\json;

/**
 * 用户管理类
 */
class UserController extends AppBaseController
{
    /**
     * 用户登陆
     * @param string title 账号
     * @param string pwd 密码
     * @param string imei 标识
     * @return array
     * @return @param code:返回码
     * @return @param content:内容、说明
     * */
    public function login()
    {
        if (I('post.title') and I('post.pwd') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //登陆操作
            $res = $user->login(I('post.title'), I('post.pwd'), I('post.imei'));
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 获取用户统计信息
     */
    public function get_user_count()
    {
        #todo 支持某段时间的统计，比如最近15天，最近1个月，最近3个月的统计情况,目前只有全部的统计
        if (I('post.uid') and I('post.imei')) {
            $uid = intval(I('post.uid'));
            $imei = I('post.imei');
            $user = new \Common\Model\UserModel();
            $msg = $user->is_judges($uid, $imei);
            if ($msg['code'] == 1) {
                if (I('post.days')) {
                    $days = intval(I('post.days'));
                    $time = strtotime("-" . $days . " day");
                    $evaluation = M("evaluation");
                    //获取船舶公司的相关评价
                    $where = array(
                        'time2' => array("gt", $time),
                        'uid' => $uid,
                    );

//                    $datas = $evaluation->where($where)->fetchSql(true)->select();
                    $datas = $evaluation->where($where)->select();
                    //缺省作业统计查询条件
                    $result_where = array(
                        'time' => array("gt", $time),
                    );

                    $count_data = array(
                        'num' => 0,                   //总作业数量
                        'weight' => 0,                //总作业吨数
                        'grade' => 0,                 //评价等级总和
                        'grade_num' => 0,             //评价次数
                        'measure_standard' => 0,      //计量规范总分
                        'measure_num' => 0,           //计量规范次数
                        'security' => 0,              //安全规范总分
                        'security_num' => 0,          //安全规范评价次数
                    );

                    foreach ($datas as $key1 => $value1) {
                        //统计评价等级
                        if ($value1['grade2'] > 0) {
                            $count_data['grade'] += $value1['grade2'];
                            $count_data['grade_num'] += 1;
                        }

                        //统计计量规范分
                        if ($value1['measure_standard2'] > 0) {
                            $count_data['measure_standard'] += $value1['measure_standard2'];
                            $count_data['measure_num'] += 1;
                        }

                        //统计安全规范分
                        if ($value1['security2'] > 0) {
                            $count_data['security'] += $value1['security2'];
                            $count_data['security_num'] += 1;
                        }
                    }
                    //统计总作业次数
                    $result_where['uid'] = $uid;

                    $result = new \Common\Model\ResultModel();
                    $count_data['num'] = $result->where($result_where)->count();
                    $result_weight = $result->field('sum(weight) as s_weight')->where($result_where)->find();
                    $count_data['weight'] = $result_weight['s_weight'];
                    $count_data['uid'] = $uid;
                    $count_data['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
                    exit(jsonreturn($count_data));
                } else {
                    $user_count = M('user_historical_sum');
                    $count_data = $user_count->where(array('userid' => $uid))->find();
                    $count_data['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
                    exit(json_encode($count_data));
                }
            }else{
                $res = $msg;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 修改用户信息
     */
    public function edit()
    {
        if (I('get.username') and I('get.uid') and I('get.imei')) {
            $uid = I('get.uid');
            $imei = I('get.imei');
            $username = I('get.username');
            $user = new \Common\Model\UserModel();
            $msg = $user->is_judges($uid, $imei);
            if (judgeOneString($username)) {
                //如果用户名有特殊字符，报错 5
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL'],
                );
            } else {
                if ($msg['code'] == 1) {
                    vendor("Nx.FileUpload");
                    $user = new \Common\Model\UserModel();
                    $user_map = array(
                        'id' => $uid
                    );
                    $user_data = array(
                        'username' => $username,
                    );
                    M()->startTrans();
                    $edit_res = $user->editData($user_map, $user_data);
                    if ($edit_res !== false) {
                        M()->commit();
                        //成功 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                        );
                    } else {
                        M()->rollback();
                        //数据库错误 3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                        );
                    }
                } else {
                    $res = $msg;
                }
            }
        } else {
            //缺少参数
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 修改头像
     */
    public function up_avatar()
    {
        if (I('get.uid') and I('get.imei')) {
            $uid = I('get.uid');
            $imei = I('get.imei');
            $user = new \Common\Model\UserModel();
            $msg = $user->is_judges($uid, $imei);
            if ($msg['code'] == 1) {
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    $res = $Upload->uploadFile($file_info[0], './Upload/avatar');
                    if ($res['mes'] == "上传成功") {
                        //将上传的图片路径放入数据库
                        $img = $res['dest'];
                        $user = new \Common\Model\UserModel();
                        $user_map = array(
                            'id' => $uid
                        );
                        $user_data = array(
                            'avatar' => $img,
                        );
                        M()->startTrans();
                        $edit_res = $user->editData($user_map, $user_data);
                        if ($edit_res !== false) {
                            M()->commit();
                            //成功 1
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                'path'=>$img
                            );
                        } else {
                            M()->rollback();
                            //数据库错误 3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                            );
                        }
//                        $res = $firm->legalize_firm($uid, $code, $img);
                    } else {
                        //上传图片失败，返回报错原因 9
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                            'error' => $res['mes'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }
            } else {
                $res = $msg;
            }
        } else {
            //缺少参数
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 修改密码
     * @param int    $uid 用户ID
     * @param string $oldpwd 旧密码
     * @param string $newpwd 新密码
     * @param string $repeatpwd 确认密码
     * @param string $imei 标识
     * @return array
     * @return @param $code:返回码
     * @return @param $content:内容、说明
     * */
    public function changepwd()
    {
        if (I('post.oldpwd') and I('post.newpwd') and I('post.repeatpwd') and I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            // $res = I('post.');
            //修改密码操作
            $res = $user->changePwd(I('post.uid'), I('post.oldpwd'), I('post.newpwd'), I('post.repeatpwd'), I('post.imei'));
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 用户注册
     * @param string title 账号
     * @param string pwd 密码
     * @return array
     * @return @param code:返回码
     * @return @param content:内容、说明
     * */
    public function register()
    {
        if (I('post.title') and I('post.username') and I('post.pwd') and I('post.confirm_pwd') and I('post.phone') and I('post.imei') and I('post.code')) {
            if (I('post.pwd') == I('post.confirm_pwd')) {
                $phone = I('post.phone');
                $code = I('post.code');//短信验证码
                //判断验证码是否正确
                $sms = new \Common\Model\SmsVerifyCodeModel();
                $code_result = $sms->checkVerifyCode($phone, $code);
                if ($code_result['code'] == 1) {
                    $user = new \Common\Model\UserModel();
                    //登陆操作
                    $user_data = array(
                        'title' => I('post.title'),
                        'pwd' => I('post.pwd'),
                        'phone' => I('post.phone'),
                        'username' => I('post.username'),
                        'pid' => 0,
                        'look_other' => 1,
                        'firmid' => 0,
                    );
                    if (judgeTwoString($user_data)) {
                        $res = $user->adddatas($user_data);
                        if ($res['code'] == 1) {
                            //登陆操作
                            $res = $user->login(I('post.title'), I('post.pwd'), I('post.imei'));
                        }
                    } else {
                        //不能含有特殊字符 5
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL']
                        );
                    }
                } else {
                    $res = $code_result;
                }
            } else {
                //两次密码不相同 1002
                $res = array(
                    'code' => $this->ERROR_CODE_USER['USER_PASSWORD_NOT_MATCH']
                );
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
     * 检查手机验证码
     */
    public function check_verify_code()
    {
        if (I('post.phone') and I('post.code')) {
            //判断验证码是否正确
            $sms = new \Common\Model\SmsVerifyCodeModel();
            $code_result = $sms->checkVerifyCode(I('post.phone'), I('post.code'));
        } else {
            $code_result = array('code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']);
        }
        echo jsonreturn($code_result);
    }

    /**
     * 发送手机验证码
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function send_sms()
    {
        if (I('post.phone')) {
            $phone = I('post.phone');
            $sms = new \Common\Model\SmsVerifyCodeModel();
            $res = $sms->sendSms($phone);
        } else {
            $res = array('code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']);
        }
        exit(jsonreturn($res));
    }

    /**
     * 重置注册通知
     */
    public function reset_status()
    {
        if (I('post.uid')) {
            $user = new \Common\Model\UserModel();
            $res = $user->reset_status(intval(I('post.uid')));
        } else {
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

}