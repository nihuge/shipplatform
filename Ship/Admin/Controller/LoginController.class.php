<?php

namespace Admin\Controller;

use Think\Controller;

/**
 * 登陆
 * */
class LoginController extends Controller
{
    /**
     * 用户登陆
     */
    public function login()
    {
        if (I('post.title') and I('post.pwd') and I('post.verify')) {
            // 验证码验证
            if (check_verify(I('post.verify'))) {
                //判断用户名不能含有特殊字符
                $res_s = judgeOneString(I('post.title'));
                if ($res_s == true) {
                    $this->error('数据不能含有特殊字符');
                    exit;
                }
                $admin = new \Common\Model\AdminModel();
                $res = $admin->login(I('post.title'), I('post.pwd'));
                if ($res['code'] == '1') {
                    session('adminuid', $res['content']);
                    $this->success('登陆成功', U('Index/index'));
                } else {
                    $this->error($res['msg']);
                }

            } else {
                $this->error('验证码输入错误', U('Admin/Login/login'));
            }
        } else {
            $this->display();
        }
    }

    /**
     * 生成验证码
     */
    public function show_verify()
    {
        show_verify();
    }
}