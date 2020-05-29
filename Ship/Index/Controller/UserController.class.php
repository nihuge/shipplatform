<?php

namespace Index\Controller;

use Common\Controller\IndexBaseController;

class UserController extends IndexBaseController
{
    // 定义数据表
    private $db;

    // 构造函数 实例化UserModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\UserModel();
    }

    /**
     * 人员管理
     */
    public function index()
    {
        $where = array('u.pid' => $_SESSION['user_info']['id']);
        $count = $this->db
            ->alias('u')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 20);

        $data = $this->db
            ->alias('u')
            ->field('u.*,f.firmname')
            ->join('left join firm f on f.id =u.firmid')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();

        $firmid = $this->db->getFieldById($_SESSION['user_info']['id'], 'firmid');
        // 根据firmid获取公司操作权限
        $firm = new \Common\Model\FirmModel();
        $firmmsg = $firm->getFirmOperationSearch($firmid);

        // 获取公司下操作的船信息
        $ship = new \Common\Model\ShipModel();
        $where = array(
            'id' => array('in', $firmmsg['operation_jur'])
        );
        $shiplist = $ship->field('id,shipname')->where($where)->select();

        $assign = array(
            'data' => $data,
            'shiplist' => $shiplist,
            'page' => $page->show()
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 完善个人信息
     * */
    public function editinfo()
    {
        if (I('post.username') and I('post.phone')) {
            $data['username'] = I('post.username');
            $data['phone'] = I('post.phone');

            $uid = $_SESSION['user_info']['id'];
            $map = array(
                'id' => $uid
            );
            if (!$this->db->create($data)) {
                //对data数据进行验证
                echo ajaxReturn(array("code" => 2, 'error' => $this->db->getError()));
            } else {
                // 判断用户是否有公司
                $firmid = $this->db->getFieldById($uid, 'firmid');
                if ($firmid == '') {
                    echo ajaxReturn(array("code" => 2, 'error' => "请完善信息"));
                } else {
                    // 修改用户信息
                    $resu = $this->db->editData($map, $data);
                    if ($resu !== false) {
                        $_SESSION['user_info']['username'] = $data['username'];
                        $_SESSION['user_info']['phone'] = $data['phone'];
                        echo ajaxReturn(array("code" => 1, 'message' => "修改个人信息成功"));
                    } else {
                        echo ajaxReturn(array("code" => 2, 'error' => "修改个人信息失败"));
                    }
                }
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'error' => "表单不能有空值"));
        }
    }

    /**
     * 修改密码
     * */
    public function changepwd()
    {
        if (I('post.oldpwd') and I('post.newpwd') and I('post.repeatpwd')) {
            $oldpwd = I('post.oldpwd');
            $newpwd = I('post.newpwd');
            $repeatpwd = I('post.repeatpwd');
            //判断新密码与重置密码是否一样
            if ($newpwd == $repeatpwd) {
                $uid = $_SESSION['user_info']['id'];
                //判断并进行修改密码
                // 检验原密码是否正确 
                $msg = $this->db
                    ->field('pwd')
                    ->where(array('id' => $uid))
                    ->find();
                if ($msg != '') {
                    //判断原始密码对否正确
                    $pwdold = encrypt($oldpwd);
                    if ($pwdold == $msg['pwd']) {
                        $newpwd = trim($newpwd);
                        //修改密码
                        $data = array(
                            'pwd' => encrypt($newpwd)
                        );
                        $res1 = $this->db->where(array('id' => $uid))->save($data);
                        if ($res1 !== false) {
                            echo ajaxReturn(array("code" => 1, 'message' => "修改密码成功"));
                        } else {
                            echo ajaxReturn(array("code" => 2, 'error' => "数据库操作错误"));
                        }
                    } else {
                        echo ajaxReturn(array("code" => 2, 'error' => "原始密码不正确"));
                    }
                } else {
                    echo ajaxReturn(array("code" => 2, 'error' => "该用户不存在"));
                }
            } else {
                echo ajaxReturn(array("code" => 2, 'error' => "新密码与确认密码不一致"));
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'error' => "不能有空值"));
        }
    }

    /**
     * 新增用户
     * */
    public function add()
    {
        if (I('post.title')) {
            // 判断提交的数据是否含有特殊字符
            $res = judgeOneString(I('post.title'));
            if ($res == true) {
                $this->error('账号不能含有特殊字符');
                exit;
            }

            $data = I('post.');
            $data['firmid'] = $firmid = $this->db->getFieldById($_SESSION['user_info']['id'], 'firmid');;
            $res = $this->db->adddatas($data);
            if ($res['code'] == '1') {
                echo ajaxReturn(array("code" => 1, 'message' => "成功"));
            } else {
                echo ajaxReturn(array("code" => $res['code'], 'error' => $res['msg']));
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'error' => "账号为必填项"));
        }
    }

    /**
     * 获取人员信息，组装html
     */
    public function usermsg()
    {
        $data = I('post.');
        $usermsg = $this->db
            ->field('id,title,username,phone,firmid,operation_jur')
            ->where(array('id' => $data['id']))
            ->find();
        // 根据firmid获取公司操作权限
        $firm = new \Common\Model\FirmModel();
        $firmmsg = $firm->getFirmOperationSearch($usermsg['firmid']);

        // 获取公司下操作的船信息
        $ship = new \Common\Model\ShipModel();
        $where = array(
            'id' => array('in', $firmmsg['operation_jur'])
        );
        $shiplist = $ship->field('id,shipname')->where($where)->select();

        $operation_jur = explode(',', $usermsg['operation_jur']);

        $string = array(
            'id' => $data['id'],
            'usermsg' => $usermsg,
            'shiplist' => $shiplist,
            'user_operation_jur' => $operation_jur,
        );
        echo ajaxReturn(array("code" => 1, 'message' => "成功", 'content' => $string));
    }

    /**
     * 获取人员信息，组装html
     */
    public function usermsgs()
    {
        $data = I('post.');
        $usermsg = $this->db
            ->field('id,title,username,phone,firmid,operation_jur')
            ->where(array('id' => $data['id']))
            ->find();
        // 根据firmid获取公司操作权限
        $firm = new \Common\Model\FirmModel();
        $firmmsg = $firm->getFirmOperationSearch($usermsg['firmid']);

        // 获取公司下操作的船信息
        $ship = new \Common\Model\ShipModel();
        $where = array(
            's.id' => array('in', $firmmsg['operation_jur'])
        );
        $shiplist = $ship->alias('s')->field('s.id,s.shipname,s.firmid,f.firmname')->join('left join firm as f on f.id=s.firmid')->where($where)->select();

        $operation_jur = explode(',', $usermsg['operation_jur']);
        $list = array();
        //根据公司分组
        foreach ($shiplist as $key=>$value){
            if (empty($list[$value['firmid']])) $list[$value['firmid']] = array('firm' => $value['firmname'],'firmid'=>$value['firmid'],'fields'=>array('shipname','id','selected'));
            $list[$value['firmid']]['list'][]= array($value['shipname'],$value['id'],in_array($value['id'],$operation_jur));
        }


        foreach ($list as $k1=>$v1){
            $list[$k1]['selected'] = true;
            foreach ($v1['list'] as $k2=>$v2){
                if(!$v2[2]){
                    $list[$k1]['selected'] = false;
                    break;
                }
            }
        }



        $string = array(
            'id' => $data['id'],
            'usermsg' => $usermsg,
            'shiplist' => $list,
            'user_operation_jur' => $operation_jur,
        );
        echo ajaxReturn(array("code" => 1, 'message' => "成功", 'content' => $string));
    }

    /**
     * 修改用户信息
     * */
    public function edit()
    {
        if (I('post.id')) {
            $data = I('post.');
            unset($data['operation_jur']);
            unset($data['search_jur']);
//            // 判断是否提交操作权限，查询权限在新增的时候与操作权限一样
//            if (I('post.operation_jur')) {
//                // 将数组转换字符串
//                $operation_jur = implode(',', I('post.operation_jur'));
//                $data['operation_jur'] = $operation_jur;
//            } else {
//                // 没有传值
//                $data['operation_jur'] = '';
//            }
            $map = array(
                'id' => $data['id']
            );
            if (!$this->db->create($data)) {
                //对data数据进行验证
                echo ajaxReturn(array("code" => $this->ERROR_CODE_COMMON['DB_ERROR'], 'error' => $this->db->getError()));
            } else {
                // 修改用户信息
                $resu = $this->db->editData($map, $data);
                if ($resu !== false) {
                    echo ajaxReturn(array("code" => 1, 'message' => "成功"));
                } else {
                    echo ajaxReturn(array("code" => $this->ERROR_CODE_COMMON['DB_ERROR'], 'error' => $this->db->getDbError()));
                }
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'error' => "修改失败"));
        }
    }

    /**
     * 改变用户状态
     * */
    public function changestatus()
    {
        $user = new \Common\Model\UserModel();
        $data = array(
            'status' => $_POST['status']
        );
        $map = array(
            'id' => intval($_POST['id'])
        );
        //验证通过 可以对数据进行操作
        $res = $user->editData($map, $data);
        if ($res !== false) {
            //成功
            echo ajaxReturn(array("code" => 1, 'msg' => "成功"));
        } else {
            //改变用户状态失败
            echo ajaxReturn(array("code" => 2, 'error' => "改变用户状态失败"));
        }
    }

//    /**
//     * 改变用户状态
//     * */
//    public function reset_status($uid)
//    {
//        $user = new \Common\Model\UserModel();
//        $data = array(
//            'reg_status' => 0
//        );
//        $map = array(
//            'id' => intval($uid)
//        );
//        //验证通过 可以对数据进行操作
//        $res = $user->editData($map, $data);
//        if ($res !== false) {
//            //成功
////            return array("code" => $this->, 'msg' => "成功");
//        } else {
//            //改变用户状态失败
//            echo ajaxReturn(array("code" => 2, 'msg' => "改变用户状态失败"));
//        }
//    }

    /**
     * 重置密码
     * */
    public function resetpwd()
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
            echo ajaxReturn(array("code" => 1, 'msg' => "成功"));
        } else {
            //重置密码失败
            echo ajaxReturn(array("code" => 2, 'error' => "重置密码失败"));
        }
    }

    /**
     * 获取查询条件
     * */
    public function configSearch()
    {
        if (I('post.id')) {
            $where = array(
                'u.id' => I('post.id')
            );
            $data = $this->db
                ->alias('u')
                ->field('u.id,u.search_jur,u.firmid,f.operation_jur')
                ->join('left join firm f on f.id = u.firmid')
                ->where($where)
                ->find();
            if ($data !== false and !empty($data)) {
                // 获取公司列表及公司名下能操作的船列表
                $ship = new \Common\Model\ShipModel();
                $operation_jur = explode(',', $data['operation_jur']);
                $shiplist = $ship
                    ->alias('s')
                    ->field('s.id,s.shipname,s.firmid,f.firmname')
                    ->join('left join firm f on f.id = s.firmid')
                    ->where(array('s.id' => array('in', $operation_jur)))
                    ->select();
                // 组装数据
                $firmlist = array();
                foreach ($shiplist as $key => $value) {
                    $firmlist[$value['firmid']]['firmname'] = $value['firmname'];
                    $firmlist[$value['firmid']]['shiplist'][] = array('id' => $value['id'], 'shipname' => $value['shipname']);
                }

                // 字符串转换数组
                $data['search_jur'] = explode(',', $data['search_jur']);
                $string = "<input type='hidden' name='iduser' id='iduser' value='" . I('post.id') . "'>";

                foreach ($firmlist as $k => $v) {
                    $string .= "<div class='bar1'>" . $v['firmname'] . "</div><ul class='pass22'>";
                    foreach ($v['shiplist'] as $key => $value) {
                        $string .= "
	                    <li><p><label><input type='checkbox' name='search_jur' value='" . $value['id'] . "' class='regular-checkbox' " . (in_array($value['id'], $data['search_jur']) ? 'checked' : '') . ">&nbsp;&nbsp;" . $value['shipname'] . "</label></p></li>";
                    }
                    $string .= "</ul>";
                }
                echo ajaxReturn(array("code" => 1, 'message' => "成功", 'content' => $string));
            } else {
                echo ajaxReturn(array("code" => 2, 'error' => "获取数据有误！"));
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'error' => "表单不能存在空值"));
        }
    }


    /**
     * 获取查询条件
     * */
    public function configSearchs()
    {
        if (I('post.id')) {
            $where = array(
                'u.id' => I('post.id')
            );
            $data = $this->db
                ->alias('u')
                ->field('u.id,u.search_jur,u.firmid,f.operation_jur')
                ->join('left join firm f on f.id = u.firmid')
                ->where($where)
                ->find();
            if ($data !== false and !empty($data)) {
                // 获取公司列表及公司名下能操作的船列表
                $ship = new \Common\Model\ShipModel();
                $operation_jur = explode(',', $data['operation_jur']);
                $shiplist = $ship
                    ->alias('s')
                    ->field('s.id,s.shipname,s.firmid,f.firmname')
                    ->join('left join firm f on f.id = s.firmid')
                    ->where(array('s.id' => array('in', $operation_jur)))
                    ->select();

                $data['search_jur'] = explode(',', $data['search_jur']);

                // 组装数据
                $firmlist = array();
                foreach ($shiplist as $key => $value) {
                    $firmlist[$value['firmid']]['firmname'] = $value['firmname'];
                    $firmlist[$value['firmid']]['shiplist'][] = array('id' => $value['id'], 'shipname' => $value['shipname'],'selected'=>in_array($value['id'],$data['search_jur']));
                }

                foreach ($firmlist as $k1=>$v1){
                    $firmlist[$k1]['selected'] = true;
                    foreach ($v1['shiplist'] as $k2=>$v2){
                        if(!$v2['selected']){
                            $firmlist[$k1]['selected'] = false;
                            break;
                        }
                    }
                }

                // 字符串转换数组
//                $string = "<input type='hidden' name='iduser' id='iduser' value='" . I('post.id') . "'>";
//
//                foreach ($firmlist as $k => $v) {
//                    $string .= "<div class='bar1'>" . $v['firmname'] . "</div><ul class='pass22'>";
//                    foreach ($v['shiplist'] as $key => $value) {
//                        $string .= "
//	                    <li><p><label><input type='checkbox' name='search_jur' value='" . $value['id'] . "' class='regular-checkbox' " . (in_array($value['id'], $data['search_jur']) ? 'checked' : '') . ">&nbsp;&nbsp;" . $value['shipname'] . "</label></p></li>";
//                    }
//                    $string .= "</ul>";
//                }
                echo ajaxReturn(array("code" => 1, 'message' => "成功", 'data' => array('firmlist'=>$firmlist,'search_jur'=>$data['search_jur'])));
            } else {
                echo ajaxReturn(array("code" => 2, 'error' => "获取数据有误！"));
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'error' => "表单不能存在空值"));
        }
    }

    /**
     * 配置查询权限
     */
    public function searchconfig()
    {
        $map = array(
            'id' => I('post.id')
        );
        if (I('post.search_jur')) {
            // 将数组转换字符串
            $search_jur = implode(',', I('post.search_jur'));
            $data['search_jur'] = $search_jur;
        } else {
            $data['search_jur'] = '';
        }

        if (!$this->db->create($data)) {
            //对data数据进行验证
            echo ajaxReturn(array("code" => 2, 'error' => $this->db->getError()));
        } else {
            // 修改用户查询条件
            $resu = $this->db->editData($map, $data);
            if ($resu !== false) {
                echo ajaxReturn(array("code" => 1, 'message' => "修改用户查询条件成功"));
            } else {
                echo ajaxReturn(array("code" => 2, 'error' => "修改用户查询条件失败！"));
            }
        }
    }

    /**
     * 配置查询权限
     */
    public function operationconfig()
    {
        $map = array(
            'id' => I('post.id')
        );
        if (I('post.operation_jur')) {
            // 将数组转换字符串
            $search_jur = implode(',', I('post.operation_jur'));
            $data['operation_jur'] = $search_jur;
        } else {
            $data['operation_jur'] = '';
        }

        if (!$this->db->create($data)) {
            //对data数据进行验证
            echo ajaxReturn(array("code" => 2, 'error' => $this->db->getError()));
        } else {
            // 修改用户查询条件
            $resu = $this->db->editData($map, $data);
            if ($resu !== false) {
                echo ajaxReturn(array("code" => 1, 'message' => "修改用户查询条件成功"));
            } else {
                echo ajaxReturn(array("code" => 2, 'error' => "修改用户查询条件失败！"));
            }
        }
    }
}