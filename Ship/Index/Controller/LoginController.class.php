<?php

namespace Index\Controller;

use Think\Controller;

/**
 * 登陆
 * */
class LoginController extends Controller
{
    /**
     * 登陆
     */
    public function login()
    {
        if (IS_POST) {
            $result = new \Common\Model\WorkModel();
            //判断用户名不能含有特殊字符
            $res_s = judgeOneString(I('post.title'));
            if ($res_s == true) {
                $this->error('数据不能含有特殊字符');
                exit;
            }
            if(!I('post.pwd')){
                $this->error('密码不能为空');
                exit;
            }
            $map = array(
                'u.title' => ":title",
                'u.phone' => ":title",
                '_logic' => "or",
            );
            //根据用户名与密码匹配查询
            $where = array(
                '_complex' => $map,
                'u.pwd' => ":pwd"
            );

            //根据用户名与密码匹配查询
            $bind = array(
                ':title' => I('post.title'),
                ':pwd' => encrypt(I('post.pwd'))
            );
            $user = new \Common\Model\UserModel();
            $arr = $user
                ->field('u.id,u.username,u.imei,u.firmid,u.pid,u.status,u.phone,f.firmname,f.firmtype,f.logo,u.reg_status')
                ->alias('u')
                ->where($where)
                ->bind($bind)
                ->join('left join firm f on f.id = u.firmid')
                ->find();

            if ($arr['id'] != '') {
                $ip = get_client_ip();
                $login_data = array(
                    'login_time'=>time(),
                    'login_ip'=>$ip,
                    'login_city'=>getCity($ip),
                );
                $user->editData(array('id'=>$arr['id']),$login_data);
                $user->setInc('login_num');

                //如果通过域名访问进来则去除最后一个开头的路径
                /*                if (is_Domain()) {
                                    $arr['logo'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $arr['logo']);
                                    \Think\Log::record("\r\n \r\n [startReplace] " . $arr['logo'] . "\r\n \r\n", "DEBUG", true);
                                }*/
                //判断用户状态、公司是否到期
                $msg = $user->is_judge($arr['id']);
                if ($msg['code'] == '1') {
                    $_SESSION['user_info'] = $arr;
                    // 自动评价
                    $arr1 = $result->automatic_evaluation();
                    $res = array('code' => $user->ERROR_CODE_COMMON['SUCCESS'], 'message' => '登陆成功', 'content' => $arr, 'reg_status' => $msg['reg_status']);
                    $this->ajaxReturn($res);
//                    $this->success('登陆成功', U('Index/index'));
                } else if ($msg['code'] == '1009') {
                    $_SESSION['user_info'] = $arr;
                    $res = array('code' => 1009, 'error' => '请先完善公司信息','content'=>$arr, 'reg_status' => $msg['reg_status']);
                    $this->ajaxReturn($res);
//                    $this->error('请先完善公司信息', '', false, $msg['code']);
                } else {
                    //状态/到期返回
                    $msg = "公司到期或者用户被禁止，请联系管理员!(" . $msg['code'] . ")";
                    $this->error($msg);
                }
            } else {
                //用户名或密码错误
                $this->error('用户名或密码错误!');
            }
        } else {
            $year = date('Y');
            $h = date('H');
            $mouth = date('m');
            $d = date('d');
            $assign = array(
                'year' => $year,
                'mouth' => $mouth,
                'd' => $d,
                'h' => $h,
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 注册
     */
    public function regist()
    {
        if (IS_POST) {
            $res = judgeOneString(I('post.title'));
            $res1 = judgeOneString(I('post.username'));
            $res2 = judgeOneString(I('post.phone'));
            $res3 = judgeOneString(I('post.code'));
            if ($res == true or $res1 == true or $res2 == true or $res3 == true) {
                $this->error('管理账号不能含有特殊字符');
                exit;
            }
            if(!(I('post.title') and I('post.username') and I('post.phone') and I('post.code') and I('post.pwd'))){
                $this->error('字段不能为空');
            }

            // 判断两个密码是否一致
            if (I('post.pwd') == I('post.pwd1')) {
                //判断验证码是否正确
                $sms = new \Common\Model\SmsVerifyCodeModel();
                $code_result = $sms->checkVerifyCode(I('post.phone'), I('post.code'));
                if ($code_result['code'] == 1) {
                    //判断账号不否存在
                    $user = new \Common\Model\UserModel();
                    $title = trimall(I('post.title'));
                    $count = $user->where(array('title' => $title))->count();
                    if ($count > 0) {
                        $this->error('管理账号已经存在！');
                        exit;
                    }
                    // 新增管理人员
                    $user_data = array(
                        'title' => I('post.title'),
                        'pwd' => encrypt(I('post.pwd')),
                        'firmid' => '',
                        'username' => I('post.username'),
                        'phone' => I('post.phone'),
                        'pid' => '0',
                        'reg_time' => time(),//注册时间
                    );
                    $uid = $user->addData($user_data);
                    if ($uid) {
                        $arr = $user
                            ->field('id,imei,firmid,pid,status,username,phone')
                            ->where(array('id' => $uid))
                            ->find();
                        $_SESSION['user_info'] = $arr;
                        $result = new \Common\Model\WorkModel();
                        // 自动评价
                        $arr1 = $result->automatic_evaluation();

                        $this->success('账号注册成功，请完善信息！', U('Login/perfect'));
                    } else {
                        $this->error('账号注册失败');
                    }
                } else {
                    $this->error("验证码错误");
                }
            } else {
                $this->error('两次密码不一致');
            }
        } else {
            $year = date('Y');
            $h = date('H');
            $mouth = date('m');
            $d = date('d');
            $assign = array(
                'year' => $year,
                'mouth' => $mouth,
                'd' => $d,
                'h' => $h,
            );
            // p($assign);die;
            $this->assign($assign);
            $this->display();
        }
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
            $code_result = array('code' => 4, 'error' => '参数不完整');
        }
        echo jsonreturn($code_result);
    }

    /**
     * 发送手机验证码
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function send_sms()
    {
//        die(date('Y-m-d H:i:s',time()));
        if (I('get.phone')) {
            $phone = I('get.phone');
            $sms = new \Common\Model\SmsVerifyCodeModel();
            $res = $sms->sendSms($phone);
            if ($res['code'] == 1) {
                $res['message'] = "发送成功";
            } else {
                $res['error'] = $sms->ERROR_CODE_USER_ZH[$res['code']];
            }
        } else {
            $res = array('code' => 4, 'error' => '参数不完整');
        }
        exit(json_encode($res));
    }


    function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
    {
        header('Content-Type:application/json; charset=utf-8');
//        parent::display($templateFile, $charset, $contentType, $content, $prefix); // TODO: Change the autogenerated stub
        exit(json_encode($this->get()));
    }

    function success($message = '', $jumpUrl = '', $ajax = false, $code = 1)
    {
        $this->dispatchJump($message, $code, $jumpUrl, $ajax);
    }

    function dispatchJump($message, $status = 1, $jumpUrl = '', $ajax = false)
    {
        if (true === $ajax || IS_AJAX) {// AJAX提交
            $data = is_array($ajax) ? $ajax : array();
            $data['info'] = $message;
            $data['code'] = $status;
            $data['url'] = $jumpUrl;
            $this->ajaxReturn($data);
        }
        if (is_int($ajax)) $this->assign('waitSecond', $ajax);
        if (!empty($jumpUrl)) $this->assign('jumpUrl', $jumpUrl);
        // 提示标题
        $this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if ($this->get('closeWin')) $this->assign('jumpUrl', 'javascript:window.close();');
        $this->assign('code', $status);   // 状态
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON', false);
        if ($status == 1) { //发送成功信息
            $this->assign('message', $message);// 提示信息
            // 成功操作后默认停留1秒
            if (!isset($this->waitSecond)) $this->assign('waitSecond', '1');
            // 默认操作成功自动返回操作前页面
            if (!isset($this->jumpUrl)) $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        } else {
            $this->assign('error', $message);// 提示信息
            //发生错误时候默认停留3秒
            if (!isset($this->waitSecond)) $this->assign('waitSecond', '3');
            // 默认发生错误的话自动返回上页
            if (!isset($this->jumpUrl)) $this->assign('jumpUrl', "javascript:history.back(-1);");
            $this->display(C('TMPL_ACTION_ERROR'));
            // 中止执行  避免出错后继续执行
            exit;
        }
    }

    function error($message = '', $jumpUrl = '', $ajax = false, $code = 25565)
    {
        $this->dispatchJump($message, $code, $jumpUrl, $ajax);
    }


    /**
     * 完善信息
     */
    public function perfect()
    {
        if (IS_POST) {
            if (!$_SESSION['user_info']['id']) {
                $this->error('请先登录系统！', 'Login/login');
            }
            if (I('post.firmname') and I('post.people') and I('post.phone')) {
                // 新增公司信息
                $data = I('post.');
                $logo = I('post.logo');
                unset($data['logo']);
                unset($data['photo']);
                unset($data['photo1']);
                unset($data['img']);
                // 判断提交的数据是否含有特殊字符
                $res = judgeTwoString($data);
                if ($res == false) {
                    $this->error('数据不能含有特殊字符');
                    exit;
                }
                if (!empty($logo)) {
                    $data['logo'] = $logo;
                }

                // 默认个性化字段
                $data['personality'] = json_encode(array(1, 2, 3, 4, 5, 6, 9));
                M()->startTrans();  // 开启事务
                // 到期时间默认10年
                $data['expire_time'] = strtotime("+10 year");
                //默认会员费
                $data['membertype'] = 1;
                //默认默认合同号，标注为首页端创建
                $data['number'] = "index_" . time();
                //默认给5条船的数量
                $data['limit'] = 30;
                $firm = new \Common\Model\FirmModel();
                // 对数据进行验证
                if (!$firm->create($data)) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    $this->error($firm->getError());
                } else {
                    // 验证通过 可以进行其他数据操作
                    $res = $firm->addData($data);
                    if ($res !== false) {
                        $where1 = array(
                            'id'=>$res
                        );
                        //查询公司当前权限信息
                        $firmtype = $firm
                            ->field('firm_jur')
                            ->where($where1)
                            ->find();
                        //添加该公司对应的公司权限
                        $firm_jur_arr = explode(',', $firmtype['firm_jur']);
                        $firm_jur_arr[] = $res;
                        $firm_jur_str = implode(',', $firm_jur_arr);
                        $data_f = array(
                            'firm_jur' => $firm_jur_str,
                        );
                        $res_f = $firm->editData($where1, $data_f);
                        if ($res_f !== false) {
                            // 添加公司历史数据汇总初步
                            $arr = array('firmid' => $res);
                            M('firm_historical_sum')->add($arr);
                            //更改用户信息
                            $da = array(
                                'firmid' => $res
                            );
                            $uid = $_SESSION['user_info']['id'];
                            $map = array(
                                'id' => $uid
                            );
                            $user = new \Common\Model\UserModel();
                            if (!$user->create($da)) {
                                M()->rollback();
                                //对data数据进行验证
                                $this->error($user->getError());
                            } else {
                                // 修改用户信息
                                $resu = $user->editData($map, $da);
                                if ($resu !== false) {
                                    M()->commit();
                                    $_SESSION['user_info']['firmid'] = $da['firmid'];

                                    // 添加公司历史数据汇总初步
                                    $arr = array('firmid' => $da['firmid']);
                                    M('firm_historical_sum')->add($arr);

                                    $this->success('完善信息成功', U('Index/index'));
                                } else {
                                    M()->rollback();
                                    $this->error('个人信息修改失败');
                                }
                            }
                        } else {
                            //修改失败,错误11
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                'massage' => '修改失败',
                            );
                        }

                    } else {
                        M()->rollback();
                        $this->error('公司信息新增失败');
                    }
                }
            } else {
                $this->error("参数不完整");
            }
        } else {
            $phone = $_SESSION['user_info']['phone'];
            $username = $_SESSION['user_info']['username'];
            $this->assign('phone', $phone);
            $this->assign('username', $username);
            $this->display();
        }
    }

    /**
     * ajax图片上传
     * */
    public function check_firm_name()
    {
//        if (!$_SESSION['user_info']['id']) {
//            $this->error('请先登录系统！', 'Login/login');
//        }
        $firm_name = I('post.name');
        if($firm_name){
            $firm = new \Common\Model\FirmModel();
            $this->ajaxReturn($firm->check_name($firm_name));
        }else{
            $this->error('缺少公司名');
        }
    }

    /**
     * ajax图片上传
     * */
    public function upload_ajax()
    {
//        if (!$_SESSION['user_info']['id']) {
//            $this->error('请先登录系统！', 'Login/login');
//        }
        $base64_image_content = $_POST['image'];
        $res = upload_ajax($base64_image_content);
        $res['code'] = $res['status'];
        unset($res['status']);
        $this->ajaxReturn($res);
    }

    /**
     * 认领公司
     */
    public function claimed_firm()
    {
        if (!$_SESSION['user_info']['id']) {
            $this->error('请先登录系统！', 'Login/login');
        }
        $uid = $_SESSION['user_info']['id'];
        if (!(judgeOneString(I('post.shehuicode')) or judgeOneString(I('post.firmname'))) and (I('post.shehuicode') and I('post.firmname') and I('post.img'))) {
            $firmname = I('post.firmname');
            $shehuicode = I('post.shehuicode');
            $img = I('img');
            $firm = new \Common\Model\FirmModel();
            $res = $firm->claimed_firm($uid, $firmname, $shehuicode, $img);
        } else {
            $res = array(
                'code' => 5,
                'msg' => "参数缺失或含有非法字符",
            );
        }
        echo jsonreturn($res);
    }


    /**
     * 退出
     */
    public function loginout()
    {
        unset($_SESSION['user_info']);
        $this->success('退出成功', U('Login/login'));
    }

    /**
     * 自动评价机制
     */
    public function automatic_evaluation()
    {
        #todo 加入评价系统
        // 获取所有已签字、未评价的作业
        $visa = M('electronic_visa');
        $where_1['r.grade1'] = 0;
        $where_2['r.grade2'] = 0;
        $where_main['_complex'] = array(
            $where_1,
            $where_2,
            '_logic' => 'or'
        );
        $rlist = $visa
            ->field('r.id,r.time,r.grade1,r.grade2,r.uid,r.shipid')
            ->alias('e')
            ->where($where_main)
            ->join('left join result r on r.id = e.resultid')
            ->select();
        $result = new \Common\Model\ResultModel();
        $res = array('code' => 1);
        foreach ($rlist as $key => $value) {
            // 获取当前作业新建时间的后10天
            $time = strtotime('+10 day', $value['time']);
            $nowtime = time();
            if ($time < $nowtime) {
                // 检验
                $data1 = array(
                    'uid' => $value['uid'],
                    'id' => $value['id'],
                    'shipid' => $value['shipid'],
                    'grade' => 5,
                    'firmtype' => 1,
                    'content' => '默认好评',
                    'operater' => $value['uid']
                );
                // 船舶
                $data2 = array(
                    'uid' => $value['uid'],
                    'id' => $value['id'],
                    'shipid' => $value['shipid'],
                    'grade' => 5,
                    'firmtype' => 2,
                    'content' => '默认好评',
                    'operater' => -1
                );

                // 当前时间大于作业后0天，启动自动评价
                if ($value['grade1'] == '0' && $value['grade2'] == '0') {
                    // 两边 都没有评价
                    // 检验
                    $res1 = $result->evaluate($data1);
                    if ($res1['code'] != '1') {
                        break;//终止循环  
                    }

                    // 船舶
                    $res2 = $result->evaluate($data2);
                    if ($res2['code'] != '1') {
                        break;//终止循环  
                    }
                } else if ($value['grade1'] != '0' && $value['grade2'] == '0') {
                    // 船驳公司评价
                    $res = $result->evaluate($data2);
                    if ($res['code'] != '1') {
                        break;//终止循环  
                    }
                } else if ($value['grade1'] == '0' && $value['grade2'] != '0') {
                    // 检验公司评价
                    $data = $data1;
                    $res = $result->evaluate($data1);
                    if ($res['code'] != '1') {
                        break;//终止循环  
                    }
                }

            }
        }

        return $res;
    }

    /**
     * 重置注册通知
     */
    public function reset_status(){
        if ($_SESSION['user_info']['id']) {
            $uid = $_SESSION['user_info']['id'];
            $user = new \Common\Model\UserModel();
            $res = $user->reset_status(intval($uid));
        }else{
            $res = array(
                'code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }
}