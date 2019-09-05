<?php
/*
 * 用户组管理
 * 2018.4.24
 */
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

class AuthGroupController extends AdminBaseController
{
	/**
	 * 用户组列表
	 * */
	public function index()
	{
		$authgroup = new \Common\Model\AuthGroupModel();
		$list = $authgroup->getauthgrouplist();
		$this->assign('list',$list);
		//总数
        $count = count($list);
        $this->assign('count',$count);
		$this->display();
	}

	/**
	 * 用户组新增
	 * */
	public function add()
	{
		if(IS_POST)
		{
			$authgroup = new \Common\Model\AuthGroupModel();
			$data=I('post.');
			if(!$authgroup->create($data)){
            	//对data数据进行验证
    			$this->error($authgroup->getError());
			}else{
		        $result=$authgroup->addData($data);
		        if ($result) {
		            $this->success('添加成功');
		        }else{
		            $this->error('添加失败');
		        }
		    }
		}else{
			$this->display();
		}
	}

	/**
	 * 用户组修改
	 * */
	public function edit()
	{
		$authgroup = new \Common\Model\AuthGroupModel();
		if(IS_POST)
		{
			$map = array(
				'id' => I('post.id')
			);
			$data = I('post.');
	        //判断用户组名称是否存在
			if(!$authgroup->create($data)){
            	//对data数据进行验证
    			$this->error($authgroup->getError());
			}else{
		    	$result=$authgroup->editData($map,$data);
		        if ($result !== false) {
		            $this->success('修改成功');
		        }else{
		            $this->error('修改失败');
		        }
		    }
		}else{
			$msg=$authgroup->where("id='".$_GET['id']."'")->find();
	        $this->assign('msg',$msg);
			$this->display();
		}
	}

	/**
	 * 用户组删除
	 * */
	public function del()
	{
		$id = intval($_POST['id']);//接受id
		//判断该用户组是否有用户
		$access = new \Common\Model\AuthGroupAccessModel();
		$count = $access->where("group_id='$id'")->count();
		if($count!=0){
			//该用户组下有用户存在，不好删除！  
			echo ajaxReturn(array("state" => 1, 'msg' => "该用户组下有用户存在，不好删除！"));
			exit;
		}
		$authgroup = new \Common\Model\AuthGroupModel();
		$res = $authgroup->where("id='$id'")->delete();
		if($res!==false)
		{
			//成功 0
			echo ajaxReturn(array("state" => 0, 'msg' => "成功"));
		}else {
			//用户组删除失败！
			echo ajaxReturn(array("state" => 1, 'msg' => "用户组删除失败！"));
		}
	}

	//*****************权限-用户组*****************
	/**
     * 分配权限
     */
    public function rule_group()
    {
    	$authgroup = new \Common\Model\AuthGroupModel();
        if(IS_POST){
            $data=I('post.');
            $map=array(
                'id'=>$data['id']
                );
            $data['rules']=implode(',', $data['rule_ids']);
            //判断用户组名称是否存在
			if(!$authgroup->create($data)){
            	//对data数据进行验证
    			$this->error($authgroup->getError());
			}else{
	            $result=$authgroup->editData($map,$data);
	            if ($result !== false) {
	                $this->success('分配权限成功');
	            }else{
	                $this->error('分配权限失败');
	            }				
			}
        }else{
            $id=I('get.id');
            // 获取用户组数据
            $group_data=$authgroup->where(array('id'=>$id))->find();
            $group_data['rules']=explode(',', $group_data['rules']);
            // 获取规则数据
            $authrule = new \Common\Model\AuthRuleModel();
            $rule_data=$authrule->getTreeData('level','id','title');
            $assign=array(
                'group_data'=>$group_data,
                'rule_data'=>$rule_data
                );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 查看用户组下的用户列表
     * */
    public function look_user($id)
    {
    	$access = new \Common\Model\AuthGroupAccessModel();
    	$data = $access->getuserlist($id);
    	if($data == false)
    	{
    		$data = array();
    	}
    	$assign=array(
                'data'=>$data
                );
        $this->assign($assign);
    	$this->display();
    }
}