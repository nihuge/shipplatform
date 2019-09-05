<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

/**
 *    用户管理
 * */
class UserController extends AdminBaseController
{
    /**
     * 用户列表
     * */
    public function index()
    {
        $user = new \Common\Model\UserModel();
        $where = array('1');
        if (I('get.firmid') != '') {
            $where['firmid'] = I('get.firmid');
        }
        $count = $user
            ->where($where)
            ->count();
        $per = 30;

        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;
        $data = $user->getuserlist($begin, $per, $where);
        // 获取所有公司列表
        $firm = new \Common\Model\FirmModel();
        $firmlist = $firm->field('id,firmname')->select();
        $assign = array(
            'data' => $data,
            'firmlist' => $firmlist,
            'page' => $page
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 复制管理员权限给操作员
     * */
    public function copyJur()
    {
        if (I('get.id')) {
            $res = judgeOneString(I('get.id'));
            if ($res == true) {
                $this->error('不能有特殊字符');
                exit;
            } else {
                $id = I('get.id');
                $user = new \Common\Model\UserModel();
                if (IS_POST) {
                    //获得管理员的权限
                    $where = array('id' => $id);
                    $operatorId = I('post.operator');
                    $adminJurs = $user->field('operation_jur,search_jur')->where($where)->find();

                    if (!$user->create($adminJurs)) {
                        //对data数据进行验证
                        $this->ajaxReturn(array('state' => 50, 'msg' => $user->getError()));
                    } else {
                        $operators = implode(',', $operatorId);
                        $map = array(
                            'id' => array("in", $operators),
                        );
                        // 修改用户信息
                        $resu = $user->editData($map, $adminJurs);
                        if ($resu !== false) {
                            $this->ajaxReturn(array('state' => 1, 'msg' => "复制成功"));
                        } else {
                            $this->ajaxReturn(array('state' => 5, 'msg' => "复制失败"));
                        }

                    }
                } else {
                    $where = array('pid' => $id);
                    $operator = $user->field('id,username')->where($where)->select();
                    $this->ajaxReturn($operator);
                }
            }
        } else {
            $this->error('缺值访问');
        }

    }

    /**
     * 新增用户
     * */
    public
    function add()
    {
        if (IS_POST) {
            // 判断提交的数据是否含有特殊字符
            $res = judgeOneString(I('post.title'));
            if ($res == true) {
                $this->error('账号不能含有特殊字符');
                exit;
            }

            $data = I('post.');
            $user = new \Common\Model\UserModel();
            $res = $user->adddatas($data);
            if ($res['code'] == '1') {
                $this->success('新增用户成功!', U('index'));
            } else {
                $this->error('新增失败');
            }

        } else {
            // 根据firmid获取公司操作权限
            $firm = new \Common\Model\FirmModel();
            $firmmsg = $firm->getFirmOperationSearch(I('get.firmid'));

            // 获取公司下操作的船信息
            $ship = new \Common\Model\ShipModel();
            $where = array(
                'id' => array('in', $firmmsg['operation_jur'])
            );
            $shiplist = $ship->field('id,shipname')->where($where)->select();

            $assign = array(
                'firmmsg' => $firmmsg,
                'shiplist' => $shiplist,
                'id' => I('get.id')
            );

            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 修改用户信息
     * */
    public
    function edit()
    {
        $user = new \Common\Model\UserModel();
        if (IS_POST) {
            // 判断提交的数据是否含有特殊字符
            $res = judgeOneString(I('post.title'));
            if ($res == true) {
                $this->error('账号不能含有特殊字符');
                exit;
            }

            $data = I('post.');
            // 判断是否提交操作权限，查询权限在新增的时候与操作权限一样
            if (I('post.operation_jur')) {
                // 将数组转换字符串
                $operation_jur = implode(',', I('post.operation_jur'));
                $data['operation_jur'] = $operation_jur;
            } else {
                // 没有传值
                $data['operation_jur'] = '';
            }
            $map = array(
                'id' => $data['id']
            );
            if (!$user->create($data)) {
                //对data数据进行验证
                $this->error($user->getError());
            } else {
                // 修改用户信息
                $resu = $user->editData($map, $data);
                if ($resu !== false) {
                    $this->success('修改用户信息成功!', U('index'));
                } else {
                    $this->error("修改用户信息失败！");
                }
            }
        } else {
            //获取用户信息
            $usermsg = $user
                ->field('id,title,username,phone,firmid,operation_jur')
                ->where(array('id' => I('get.id')))
                ->find();
            if ($usermsg !== false and !empty($usermsg)) {
                // 根据firmid获取公司操作权限
                $firm = new \Common\Model\FirmModel();
                $firmmsg = $firm->getFirmOperationSearch(I('get.firmid'));

                // 获取公司下操作的船信息
                $ship = new \Common\Model\ShipModel();
                $where = array(
                    'id' => array('in', $firmmsg['operation_jur'])
                );
                $shiplist = $ship->field('id,shipname')->where($where)->select();

                $operation_jur = explode(',', $usermsg['operation_jur']);

                $assign = array(
                    'usermsg' => $usermsg,
                    'firmmsg' => $firmmsg,
                    'shiplist' => $shiplist,
                    'operation_jur' => $operation_jur
                );
                $this->assign($assign);
                $this->display();
            } else {
                $this->error('获取数据失败！');
            }

        }
    }

    /**
     * 配置查询条件
     * */
    public
    function configSearch()
    {
        $user = new \Common\Model\UserModel();
        if (IS_POST) {
            $map = array(
                'id' => I('post.id')
            );
            $data = array('look_other' => I('post.look_other'));
            if (I('post.search_jur')) {
                // 将数组转换字符串
                $search_jur = implode(',', I('post.search_jur'));
                $data['search_jur'] = $search_jur;
            } else {
                $data['search_jur'] = '';
            }

            if (!$user->create($data)) {
                //对data数据进行验证
                $this->error($user->getError());
            } else {
                // 修改用户查询条件
                $resu = $user->editData($map, $data);
                if ($resu !== false) {
                    $this->success('修改用户查询条件成功!', U('index'));
                } else {
                    $this->error("修改用户查询条件失败！");
                }
            }
        } else {
            $where = array(
                'id' => I('get.id')
            );
            $data = $user
                ->field('id,search_jur,look_other')
                ->where($where)
                ->find();
            if ($data !== false and !empty($data)) {
                // 获取公司列表及公司名下所有船列表
                $firm = new \Common\Model\FirmModel();
                $firmlist = $firm->getFirmShip();
                // 字符串转换数组
                $data['search_jur'] = explode(',', $data['search_jur']);
                $assign = array(
                    'data' => $data,
                    'firmlist' => $firmlist
                );
                $this->assign($assign);
                $this->display();
            } else {
                $this->error('获取数据有误！');
            }
        }

    }

    /**
     * 改变用户状态
     * */
    public function changestatus()
    {
        $user = new \Common\Model\UserModel();
        $firm = new \Common\Model\FirmModel();

        $data = array(
            'status' => $_POST['status']
        );
        $map = array(
            'id' => intval($_POST['id'])
        );

        $firm_id = $user->field('firmid')->where($map)->find();
        $firm_status = $firm->field('del_sign')->where(array('id' => $firm_id['firmid']))->find();


        if ($firm_status['del_sign'] == 2 and $data['status'] == 1) {
            //如果公司被软删除，并且要进行解冻操作时，阻止操作
            $this->ajaxReturn(array("state" => 3, 'msg' => "公司被删除，无法解冻，请恢复公司后再解冻"));
        }else{
            //验证通过 可以对数据进行操作
            $res = $user->editData($map, $data);
            if ($res !== false) {
                //成功
                echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
            } else {
                //改变用户状态失败
                echo ajaxReturn(array("state" => 2, 'msg' => "改变用户状态失败"));
            }
        }
    }

    /**
     * 重置密码
     * */
    public
    function resetpwd()
    {
        $id = intval($_POST['id']);//接受id
        $user = new \Common\Model\UserModel();
        $pwd = "000000";
        $pwd = encrypt($pwd);    //加密
        $data = array(
            'pwd' => $pwd,
        );
        $map = array(
            'id' => $id
        );
        $res = $user->editData($map, $data);
        if ($res !== FALSE) {
            //成功
            echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
        } else {
            //重置密码失败
            echo ajaxReturn(array("state" => 2, 'msg' => "重置密码失败"));
        }
    }


}