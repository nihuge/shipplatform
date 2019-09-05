<?php
/*
 * 权限管理
 * 2018.4.24
 */

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

class AuthRuleController extends AdminBaseController
{
    /**
     * 权限列表
     * */
    public function index()
    {
        //权限树桩列表
        $authrule = new \Common\Model\AuthRuleModel();
        $data = $authrule->getauthrulelist();
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 权限新增
     * */
    public function add()
    {
        $authrule = new \Common\Model\AuthRuleModel();
        if (IS_POST) {
            $data = I('post.');
            if (!$authrule->create($data)) {
                //对data数据进行验证
                $this->error($authrule->getError());
            } else {
                $result = $authrule->addData($data);
                if ($result) {
                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
            }
        } else {
            //权限树桩列表
            $data = $authrule->getauthrulelist();
            $this->assign('data', $data);
            $this->display();
        }
    }

    /**
     * 权限修改
     * */
    public function edit()
    {
        $authrule = new \Common\Model\AuthRuleModel();
        if (IS_POST) {
            $map = array(
                'id' => I('post.id')
            );
            $data = I('post.');
            if (!$authrule->create($data)) {
                //对data数据进行验证
                $this->error($authrule->getError());
            } else {
                $result = $authrule->editData($map, $data);
                if ($result !== false) {

                    $this->success('修改成功');
                } else {
                    $this->error('修改失败');
                }
            }
        } else {
            //权限树桩列表
            $data = $authrule->getauthrulelist();
            //权限内容
            $where = array(
                'id' => $_GET['id']
            );
            $msg = $authrule
                ->where($where)
                ->find();
            $assign = array(
                'data' => $data,
                'msg' => $msg
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 权限删除
     * */
    public function del($id)
    {
        $authrule = new \Common\Model\AuthRuleModel();
        $map = array(
            'id' => $id
        );
        $res = $authrule->deleteData($map);
        if ($res !== false) {
            //成功 0
            echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
        } else {
            //权限还有子集权限，删除失败
            echo ajaxReturn(array("state" => 2, 'msg' => "权限还有子集权限，删除失败！"));
        }
    }
}