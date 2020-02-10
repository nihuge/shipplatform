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
            //判断用户名不能含有特殊字符
            $res_s = judgeOneString(I('post.title'));
            if ($res_s == true) {
                $this->error('数据不能含有特殊字符');
                exit;
            }
            //根据用户名与密码匹配查询
            $where = array(
                'u.title' => ":title",
                'u.pwd' => ":pwd"
            );

            //根据用户名与密码匹配查询
            $bind = array(
                ':title' => I('post.title'),
                ':pwd' => encrypt(I('post.pwd'))
            );
            $user = new \Common\Model\UserModel();
            $arr = $user
                ->field('u.id,u.username,u.imei,u.firmid,u.pid,u.status,u.phone,f.firmname,f.logo')
                ->alias('u')
                ->where($where)
                ->bind($bind)
                ->join('left join firm f on f.id = u.firmid')
                ->find();
            //如果通过域名访问进来则去除最后一个开头的路径
            if (is_Domain()) {
                $arr['logo'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $arr['logo']);
                \Think\Log::record("\r\n \r\n [startReplace] " . $arr['logo'] . "\r\n \r\n", "DEBUG", true);
            }

            if ($arr != '') {
                //判断用户状态、公司是否到期
                $msg = $user->is_judge($arr['id']);
                if ($msg['code'] == '1') {
                    $_SESSION['user_info'] = $arr;
                    // 自动评价
                    $arr1 = $this->automatic_evaluation();
                    $this->success('登陆成功', U('Index/index'));
                } else if ($msg['code'] == '1009') {
                    $_SESSION['user_info'] = $arr;
                    $this->redirect('Index/perfect');
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
            if ($res == true) {
                $this->error('管理账号不能含有特殊字符');
                exit;
            }

            // 判断两个密码是否一致
            if (I('post.pwd') == I('post.pwd1')) {
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
                    'pid' => '0'
                );
                $uid = $user->addData($user_data);
                if ($uid) {
                    $arr = $user
                        ->field('id,imei,firmid,pid,status')
                        ->where($where)
                        ->find();
                    $_SESSION['user_info'] = $arr;

                    // 自动评价
                    $arr1 = $this->automatic_evaluation();

                    $this->success('账号注册成功，请完善信息！', U('Login/perfect'));
                } else {
                    $this->error('账号注册失败');
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
     * 完善信息
     */
    public function perfect()
    {
        if (IS_POST) {
            // 新增公司信息
            $data = I('post.');
            $logo = I('post.logo');
            $img = I('post.img');
            unset($data['logo']);
            unset($data['username']);
            unset($data['phone1']);
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
            if (!empty($img)) {
                $data['img'] = $img;
            }
            // 默认个性化字段
            $data['personality'] = json_encode(array(1, 2, 3, 4, 5, 6, 9));
            M()->startTrans();  // 开启事务
            // 到期时间默认一周
            $data['expire_time'] = strtotime("+1weeks", strtotime(date('Y-m-d H:i:s', time())));
            $firm = new \Common\Model\FirmModel();
            // 对数据进行验证
            if (!$firm->create($data)) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $this->error($firm->getError());
            } else {
                // 验证通过 可以进行其他数据操作
                $res = $firm->addData($data);
                if ($res !== false) {
                    $da = array(
                        'username' => I('post.username'),
                        'phone' => I('post.phone1'),
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
                            $_SESSION['user_info']['username'] = $da['username'];
                            $_SESSION['user_info']['phone'] = $da['phone'];
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
                    M()->rollback();
                    $this->error('公司信息新增失败');
                }
            }
        } else {
            $this->display();
        }
    }


    /**
     * ajax图片上传
     * */
    public function upload_ajax()
    {
        if (IS_AJAX) {
            $base64_image_content = $_POST['image'];
            $res = upload_ajax($base64_image_content);
            $this->ajaxReturn(json_encode($res));
        }
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
}