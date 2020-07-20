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
        $per = $count;

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
                    $adminJurs = $user->field('operation_jur,search_jur,sh_operation_jur,sh_search_jur')->where($where)->find();

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
//            $user = new \Common\Model\UserModel();
            $firmmsg = $firm->getFirmOperationSearch(I('get.firmid'));
//            $pid =
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
                ->field('id,title,username,phone,firmid')
                ->where(array('id' => I('get.id')))
                ->find();
            if ($usermsg !== false and !empty($usermsg)) {
                // 根据firmid获取公司操作权限
                $firm = new \Common\Model\FirmModel();
//                $firmmsg = $firm->getFirmOperationSearch(I('get.firmid'));
                $firmmsg = $firm->getFieldById(I('get.firmid'), 'firmname');
                $assign = array(
                    'usermsg' => $usermsg,
                    'firmmsg' => $firmmsg,
                );
                $this->assign($assign);
                $this->display();
            } else {
                $this->error('获取数据失败！');
            }

        }
    }


    /**
     * 修改用户操作权限
     * */
    public
    function edit_msg()
    {
        $user = new \Common\Model\UserModel();
        if (IS_POST) {
            $data = array();
            // 判断是否提交操作权限，查询权限在新增的时候与操作权限一样
            if (I('post.operation_jur')) {
                // 将数组转换字符串
                $operation_jur = implode(',', I('post.operation_jur'));
                $data['operation_jur'] = $operation_jur;
            } else {
                // 没有传值
                $data['operation_jur'] = '';
            }

            // 判断是否提交操作权限，查询权限在新增的时候与操作权限一样
            if (I('post.sh_operation_jur')) {
                // 将数组转换字符串
                $sh_operation_jur = implode(',', I('post.sh_operation_jur'));
                $data['sh_operation_jur'] = $sh_operation_jur;
            } else {
                // 没有传值
                $data['sh_operation_jur'] = '';
            }

            $map = array(
                'id' => trimall(I('post.id'))
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
                ->field('id,firmid,operation_jur,sh_operation_jur')
                ->where(array('id' => I('get.id')))
                ->find();
            if ($usermsg !== false and !empty($usermsg)) {
                // 根据firmid获取公司操作权限
                $firm = new \Common\Model\FirmModel();
                $firmmsg = $firm->getFirmOperationSearch($usermsg['firmid']);

                // 获取公司下操作的船信息
                $ship = new \Common\Model\ShipModel();
                $where = array(
                    'id' => array('in', $firmmsg['operation_jur'])
                );

                $shiplist = $ship->field('id,shipname')->where($where)->select();

                // 获取公司下操作的船信息
                $sh_ship = new \Common\Model\ShShipModel();

                $sh_where = array(
                    'id' => array('in', $firmmsg['sh_operation_jur'])
                );

                $sh_shiplist = $sh_ship->field('id,shipname')->where($sh_where)->select();

                $jurlist = explode(',', $usermsg['operation_jur']);
                $sh_jurlist = explode(',', $usermsg['sh_operation_jur']);

//                $assign = array(
//                    'usermsg' => $usermsg,
//                    'firmmsg' => $firmmsg,
//                    'shiplist' => $shiplist,
//                );
                $txt = "<form action='" . U("User/edit_msg") . "' method='post'><input type='hidden' name='id' value='" . $usermsg['id'] . "'>";
                $txt .= <<<table
<table id="sample-table-1" class="table table-striped table-bordered table-hover" style="width:85%;margin:15px auto;text-align: center;">
                        <tbody><tr>
                            <td colspan="3">油船操作权限</td>
                            </tr><tr>
table;
                $ship_count = count($shiplist);
                if ($ship_count == 0) $txt .= "<td id=\"ajaxship\" colspan='3'>公司暂无液货船操作权限，请点击<a href='" . U('firm/configOperator', array('id' => $usermsg['firmid'], 'firmtype' => $firmmsg['firmtype'])) . "' style='color: #0d7bdc'>这里</a>配置权限</td>";

                foreach ($shiplist as $key => $value) {
                    $checked = "";
                    if (in_array($value['id'], $jurlist)) {
                        $checked = "checked";
                    }
                    $txt .= "<td id=\"ajaxship\"><label>
                                    <input class=\"ace ace-checkbox-2\" type=\"checkbox\" name=\"operation_jur[]\" " . $checked . " value=\"" . $value['id'] . "\">
                                    <span class=\"lbl\"> " . $value['shipname'] . "</span> 
                                </label></td>";
                    if ($key + 1 == $ship_count && ($key + 1) % 3 != 0) {
                        $col_num = 3 - ($key + 1) % 3;
                        $txt .= "<td colspan='" . $col_num . "'></td>";
                    }
                    if (($key + 1) % 3 == 0 && $key + 1 < $ship_count) {
                        $txt .= "</tr><tr>";
                    }
                }

                $txt .= <<<shtable
                            </tr>
                            <tr>
                            <td colspan="3">散货操作权限</td>
                            </tr><tr>
shtable;
                $sh_ship_count = count($sh_shiplist);
                if ($sh_ship_count == 0) $txt .= "<td id=\"ajaxship\" colspan='3'>公司暂无散货船操作权限，请点击<a href='" . U('firm/configOperator', array('id' => $usermsg['firmid'], 'firmtype' => $firmmsg['firmtype'])) . "' style='color: #0d7bdc'>这里</a>配置权限</td>";
                foreach ($sh_shiplist as $key1 => $value1) {
                    $sh_checked = "";
                    if (in_array($value1['id'], $sh_jurlist)) {
                        $sh_checked = "checked";
                    }

                    $txt .= "<td id=\"ajaxship\"><label>
                                    <input class=\"ace ace-checkbox-2\" type=\"checkbox\" name=\"sh_operation_jur[]\" " . $sh_checked . " value=\"" . $value1['id'] . "\">
                                    <span class=\"lbl\"> " . $value1['shipname'] . "</span> 
                                </label></td>";
                    if ($key1 + 1 == $sh_ship_count && ($key1 + 1) % 3 != 0) {
                        $sh_col_num = 3 - ($key1 + 1) % 3;
                        $txt .= "<td colspan='" . $sh_col_num . "'></td>";
                    }
                    if (($key1 + 1) % 3 == 0 && $key1 + 1 < $sh_ship_count) {
                        $txt .= "</tr><tr>";
                    }
                }

                $txt .= <<<endhtml
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: center;">
                                <input type="submit" name="sub" value="提交" class="btn btn-primary">
                            </td>
                        </tr>
                    </tbody></table>
endhtml;
                $this->ajaxReturn(array('state' => 1, 'message' => "成功", 'content' => $txt));
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
                'id' => intval(I('post.id'))
            );

            if (I('post.search_jur')) {
                // 将数组转换字符串
                $search_jur = implode(',', I('post.search_jur'));
                $data['search_jur'] = $search_jur;
            } else {
                $data['search_jur'] = '';
            }

            if (I('post.sh_search_jur')) {
                // 将数组转换字符串
                $sh_search_jur = implode(',', I('post.sh_search_jur'));
                $data['sh_search_jur'] = $sh_search_jur;
            } else {
                $data['sh_search_jur'] = '';
            }

            if (!$user->create($data)) {
                //对data数据进行验证
                $this->error($user->getError());
            } else {
                // 修改用户查询条件
                $resu = $user->editData($map, $data);
                if ($resu !== false) {
                    $this->success('修改用户查询条件成功!', U('index',array('firmid'=>$user->getUserFirm(I('post.id')))));
                } else {
                    $this->error("修改用户查询条件失败！");
                }
            }
        } else {
            $where = array(
                'id' => intval(I('get.id'))
            );
            $data = $user
                ->field('id,sh_search_jur,search_jur,look_other')
                ->where($where)
                ->find();
            if ($data !== false and !empty($data)) {
                // 获取公司列表及公司名下所有船列表
                $firm = new \Common\Model\FirmModel();
                $firmlist = $firm->getFirmShip();
                // 字符串转换数组
                $data['search_jur'] = explode(',', $data['search_jur']);
                $data['sh_search_jur'] = explode(',', $data['sh_search_jur']);
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
        } else {
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
     * 删除用户信息
     * */
    public function del()
    {
//        $firm = new \Common\Model\FirmModel();
        $user = new \Common\Model\UserModel();
        if (!(intval(I('get.id')))) $this->error("参数缺失");
//        if (!(I('get.phone'))) $this->error("请输入手机号");
        $user_id = intval(I('get.id'));
        $phone = I('get.phone');
        //获取用户信息
        $usermsg = $user
            ->field('id,title,username,phone,firmid,status,pid')
            ->where(array('id' => $user_id))
            ->find();
        //获取用户所属公司下有多少名其他员工
//            $usercount = $user->where(array('firmid' => $usermsg['firmid']))->count();

//            $firm_status = $firm->field('del_sign')->where(array('id' => $usermsg['firmid']))->find();

        //管理员不可以被删除
        if ($usermsg['pid'] == 0) $this->ajaxReturn(array("code" => 3, 'error' => "不可以删除公司管理员"));
        //正常状态不可以被删除
        if ($usermsg['status'] != 2) $this->ajaxReturn(array("code" => 3, 'error' => "只允许删除被冻结的用户"));
        //防止删错用户
        if ($usermsg['phone'] != $phone) $this->ajaxReturn(array("code" => 4, 'error' => "手机号码不匹配，请检查是否删错用户"));
        M()->commit();
        $res_u = $user->where(array('id' => $user_id))->delete();
        if ($res_u !== false) {
            M()->commit();
            $this->success('删除成功');
        } else {
            M()->rollback();
            $this->error('删除失败，数据库错误 ' . $user->getDbError());
        }
        M()->rollback();
        $this->error("未知错误");
    }

    /**
     * 更换公司管理员
     * */
    public function change_admin()
    {
//        $firm = new \Common\Model\FirmModel();
        $user = new \Common\Model\UserModel();
        if (!(intval(I('get.id')))) $this->error("参数缺失");
//        if (!(I('get.phone'))) $this->error("请输入手机号");
        $user_id = intval(I('get.id'));
        //获取用户信息
        $usermsg = $user
            ->field('id,firmid,status,pid')
            ->where(array('id' => $user_id))
            ->find();
        //获取用户所属公司下有多少名其他员工
//            $usercount = $user->where(array('firmid' => $usermsg['firmid']))->count();

//            $firm_status = $firm->field('del_sign')->where(array('id' => $usermsg['firmid']))->find();

        //管理员不可以更换给自己
        if ($usermsg['pid'] == 0) $this->ajaxReturn(array("code" => 3, 'error' => "当前用户已经是管理员，无法更换"));
        //冻结的用户不可以被更改
//        if ($usermsg['status'] != 2) $this->ajaxReturn(array("code" => 3, 'error' => "被冻结的用户不可以被更换"));
        //开始事务
        M()->commit();
        //先将被选择的用户改为管理员
        $res_u = $user->editData(array('id' => $user_id), array('pid' => 0));
        //然后将公司其他人的所属管理员改为被选择的用户
        $res_f = $user->editData(array('id' => array('neq', $user_id), 'firmid' => $usermsg['firmid']), array('pid' => $user_id));
        if ($res_u !== false and $res_f !== false) {
            M()->commit();
            $this->success('更换成功');
        } else {
            M()->rollback();
            $this->error('更换失败，数据库错误，请联系管理员查看日志，最后一次错误信息：' . $user->getDbError());
        }
        M()->rollback();
        $this->error("未知错误");
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

    /**
     * 更改查询限制
     * */
    public
    function change_look_other($userId)
    {
        $id = intval($userId);//接受id
        $user = new \Common\Model\UserModel();
        if (IS_POST) {
            $map = array(
                'id' => $id
            );
            $data = array('look_other' => intval(I('post.look_other')));
            if (!$user->create($data)) {
                //对data数据进行验证
                $this->error($user->getError());
            } else {
                // 修改用户查询条件
                $resu = $user->editData($map, $data);
                if ($resu !== false) {
                    $this->success("修改成功", U("index"));
                } else {
                    $this->error("修改失败");
                }
            }
        } else {
            $where = array(
                'id' => $id
            );

            $data = $user
                ->field('username,look_other')
                ->where($where)
                ->find();

            $this->ajaxReturn(array(
                'state' => 1,
                'look_other' => $data['look_other'],
                'username' => $data['username'],
            ));
        }
    }
}