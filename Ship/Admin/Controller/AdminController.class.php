<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

/**
 * 管理员管理
 * 2018.4.23
 * */
class AdminController extends AdminBaseController
{
    /**
     * 列表
     * */
    public function index()
    {
        $admin = new \Common\Model\AdminModel();
        $count = $admin
            ->count();
        // 分页显示输出   传入总记录数和每页显示的记录数(20)
        $per = 20;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;

        $list = $admin
            ->alias('a')
            ->field("a.*")
            ->limit($begin, $per)
            ->select();
        //获取用户的用户组名称
        $access = new \Common\Model\AuthGroupAccessModel();
        foreach ($list as $key => $v) {
            $list[$key]['grouptitle'] = $access->getgrouptitle($v['id']);
        }

        $assign = array(
            'page' => $page,
            'list' => $list
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 新增用户
     * */
    public function add()
    {
        $authgroup = new \Common\Model\AuthGroupModel();
        if (IS_POST) {
            // 判断提交的数据是否含有特殊字符
            $res = judgeOneString(I('post.title'));
            if ($res == true) {
                $this->error('账号不能含有特殊字符');
                exit;
            }
            $data = I('post.');
            $data['pwd'] = encrypt('000000');
            $admin = new \Common\Model\AdminModel();
            if (!$admin->create($data)) {
                //对data数据进行验证
                $this->error($admin->getError());
            } else {
                // 开启事物
                M()->startTrans();
                // 插入数据
                $res = $admin->add($data);
                if (!empty($data['group_id'])) {

                    // 判断用户提交的用户角色是否存在
                    foreach ($data['group_id'] as $key => $value) {
                        $re = $authgroup
                            ->where(array('id' => $value))
                            ->count();
                        if ($re === '0') {
                            M()->rollback();
                            $this->error('有不存在的用户角色');
                            exit;
                        }
                    }
                    // 插入角色数据
                    $access = new \Common\Model\AuthGroupAccessModel();
                    static $i = 0;
                    foreach ($data['group_id'] as $key => $value) {
                        $group = array(
                            'uid' => $res,
                            'group_id' => $value
                        );
                        $result = $access->addData($group);
                        if ($result !== false) {
                            $i++;
                        } else {
                            M()->rollback();
                            $this->error('用户新增失败');
                            exit;
                        }
                    }
                    $groupCount = count($data['group_id']);
                    if ($res !== false and $i == $groupCount) {
                        M()->commit();
                        $this->success('新增用户成功!', U('index'));
                    } else {
                        M()->rollback();
                        $this->error('新增失败');
                    }
                } else {
                    M()->commit();
                    $this->success('新增用户成功!', U('index'));
                }
            }
        } else {
            // 获取所有角色
            $grouplist = $authgroup->getauthgrouplist();
            $this->assign('grouplist', $grouplist);
            $this->display();
        }
    }

    /**
     * 修改用户信息
     * */
    public function edit()
    {
        $admin = new \Common\Model\AdminModel();
        $authgroup = new \Common\Model\AuthGroupModel();
        $access = new \Common\Model\AuthGroupAccessModel();
        if (IS_POST) {
            // 判断提交的数据是否含有特殊字符
            $res = judgeOneString(I('post.title'));
            if ($res == true) {
                $this->error('账号不能含有特殊字符');
                exit;
            }

            $data = I('post.');
            $map = array(
                'id' => $data['id']
            );
            if (!$admin->create($data)) {
                //对data数据进行验证
                $this->error($admin->getError());
            } else {
                // 开启事物
                M()->startTrans();
                // 修改用户信息
                $res = $admin->editData($map, $data);
                $r = $access->where(array('uid' => $data['id']))->delete();
                if ($res !== false and $r !== false) {
                    if (!empty($data['group_id'])) {
                        // 判断用户提交的用户角色是否存在
                        foreach ($data['group_id'] as $key => $value) {
                            $re = $authgroup
                                ->where(array('id' => $value))
                                ->count();
                            if ($re == '0') {
                                M()->rollback();
                                $this->error('有不存在的用户角色');
                                exit;
                            }
                        }
                        // 插入角色数据
                        static $i = 0;
                        foreach ($data['group_id'] as $key => $value) {
                            $group = array(
                                'uid' => $data['id'],
                                'group_id' => $value
                            );
                            $result = $access->addData($group);
                            if ($result !== false) {
                                $i++;
                            } else {
                                M()->rollback();
                                $this->error('修改用户失败');
                                exit;
                            }
                        }
                        $groupCount = count($data['group_id']);
                        if ($res !== false and $i == $groupCount) {
                            M()->commit();
                            $this->success('修改用户成功!', U('index'));
                        } else {
                            M()->rollback();
                            $this->error('修改用户失败');
                        }
                    } else {
                        M()->commit();
                        $this->success('修改用户成功!', U('index'));
                    }
                } else {
                    M()->rollback();
                    $this->error("修改用户信息失败！");
                }
            }
        } else {
            // 获取用户信息
            $msg = $admin
                ->field('id,title,name,phone')
                ->where(array('id' => I('get.id')))
                ->find();
            if ($msg !== false and !empty($msg)) {
                // 获取所有角色
                $grouplist = $authgroup->getauthgrouplist();

                // 获取用户与角色列表
                $accesslist = $access
                    ->where(array('uid' => $msg['id']))
                    ->getField('group_id', true);;

                $assign = array(
                    'msg' => $msg,
                    'accesslist' => $accesslist,
                    'grouplist' => $grouplist
                );
                $this->assign($assign);
                $this->display();
            } else {
                $this->error('获取数据失败！');
            }

        }
    }

    /**
     * 重置密码
     * */
    public function resetpwd()
    {
        $id = intval($_POST['id']);//接受id
        $admin = new \Common\Model\AdminModel();
        $pwd = "000000";
        $pwd = encrypt($pwd);    //加密
        $data = array(
            'pwd' => $pwd,
        );
        $map = array(
            'id' => $id
        );
        $res = $admin->editData($map, $data);
        if ($res !== FALSE) {
            //成功
            echo ajaxReturn(array("state" => 1, 'msg' => "成功 "));
        } else {
            //重置密码失败！
            echo ajaxReturn(array("state" => 2, 'msg' => "重置密码失败"));
        }
    }

    /**
     * 改变管理状态
     * */
    public function changestatus()
    {
        $admin = new \Common\Model\AdminModel();
        $data = array(
            'status' => $_POST['status']
        );
        $map = array(
            'id' => intval($_POST['id'])
        );
        $res = $admin->editData($map, $data);
        if ($res !== false) {
            //成功
            echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
        } else {
            //改变用户状态失败！ 209
            echo ajaxReturn(array("state" => 2, 'msg' => "改变用户状态失败"));
        }
    }

    /**
     * 删除管理
     * */
    public function del()
    {
        $admin = new \Common\Model\AdminModel();
        $access = new \Common\Model\AuthGroupAccessModel();
        M()->startTrans();
        $map = array(
            'id' => intval($_POST['id'])
        );
        $res = $admin->deleteData($map);
        $map1 = array(
            'uid' => intval($_POST['id'])
        );
        $r = $access->deleteData($map1);
        if ($res !== false and $r !== false) {
            M()->commit();
            //成功
            echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
        } else {
            M()->rollback();
            //改变用户状态失败
            echo ajaxReturn(array("state" => 2, 'msg' => "改变用户状态失败"));
        }
    }


    /**
     * 修改管理员自己的密码
     * */
    public function changepwd()
    {
        $id = intval($_POST['id']);//接受id
        $selfid = $_SESSION['adminuid'];
        $old_pwd = I('post.old_pwd');
        $new_pwd = I('post.new_pwd');
        $new_pwd2 = I('post.new_pwd2');
        if ($id != $selfid) {
            $this->error('非法请求');
        }

        if ($new_pwd != $new_pwd2) {
            exit(ajaxReturn(array('state' => 51, '' => '密码不一致')));
        }

        $admin = new \Common\Model\AdminModel();

        //根据用户名与密码匹配查询
        $where = array(
            'id' => $selfid,
            'pwd' => encrypt($old_pwd)
        );
        $arr = $admin
            ->field('status')
            ->where($where)
            ->find();
        if ($arr != '') {
            // 判断用户状态
            if ($arr['status'] == '1') {
                //成功 1
                $pwd = encrypt($new_pwd);    //加密
                $data = array(
                    'pwd' => $pwd,
                );
                $map = array(
                    'id' => $id
                );
                $result = $admin->editData($map, $data);
                if ($result !== FALSE) {
                    $res = array("state" => 1, 'msg' => "成功");
                } else {
                    //重置密码失败
                    $res = array("state" => 2, 'msg' => "重置密码失败");
                }
            } else {
                // 该用户被冻结    1004
                $res = array(
                    'code' => 10,
                    'msg' => "冻结用户无法操作"
                );
            }
        } else {
            // 用户名或密码错误  1001
            $res = array(
                'code' => 50,
                'msg' => "原密码错误，请检查!"
            );
        }
        exit(ajaxReturn($res));
    }
}