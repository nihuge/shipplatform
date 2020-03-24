<?php

namespace App\Controller;

use Common\Controller\AppBaseController;

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
     * 修改密码
     * @param int uid 用户ID
     * @param string oldpwd 旧密码
     * @param string newpwd 新密码
     * @param string repeatpwd 确认密码
     * @param string imei 标识
     * @return array
     * @return @param code:返回码
     * @return @param content:内容、说明
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
                $code_result = $sms->checkVerifyCode($phone,$code);
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
                }else{
                    $res =$code_result;
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
    public function check_verify_code(){
        if(I('post.phone') and I('post.code')){
            //判断验证码是否正确
            $sms = new \Common\Model\SmsVerifyCodeModel();
            $code_result = $sms->checkVerifyCode(I('post.phone'), I('post.code'));
        }else{
            $code_result = array('code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR']);
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
    public function reset_status(){
        if(I('post.uid')){
            $user = new \Common\Model\UserModel();
            $res = $user->reset_status(intval(I('post.uid')));
        }else{
            $res = array(
                'code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

}