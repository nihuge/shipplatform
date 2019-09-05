<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class UserController extends HomeBaseController 
{
	/**
	 * 人员管理
	 */
	public function index()
	{
	    $user = new \Common\Model\UserModel();
	    $where = array('u.pid'=>$_SESSION['uid']);
	    $count = $user
	    			->alias('u')
	    			->where($where)
	    			->count();
	    $per =24;
	    
		if($_GET['p'])
		{
			$p=$_GET['p'];
		}else {
			$p=1;
		}
		//分页
	    $page = fenye($count,$per);
	    $begin=($p-1)*$per;
	    $data = $user
	    		->alias('u')
	    		->field('u.*,f.firmname')
	    		->where($where)
	    		->join('left join firm f on f.id =u.firmid')
	    		->select();
	    $assign=array(
            'data'=>$data,
            'page'=>$page
        );
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 修改用户信息
	 * */
	public function edit()
	{
		$user = new \Common\Model\UserModel();
		if(IS_POST)
		{
			// 判断提交的数据是否含有特殊字符
    		$res = judgeOneString( I('post.title') );
    		if ($res == true) {
    			$this->error('账号不能含有特殊字符');
    			exit;
    		}

			$data=I('post.');
			// 判断是否提交操作权限，查询权限在新增的时候与操作权限一样
			if (I('post.operation_jur')) {
				// 将数组转换字符串
				$operation_jur = implode(',',I('post.operation_jur'));
				$data['operation_jur'] = $operation_jur;
			}else{
				// 没有传值
				$data['operation_jur'] = '';
			}
		    $map = array(
				'id'  => $data['id']
			);
			if(!$user->create($data)){
				//对data数据进行验证
				$this->error($user->getError());
			}else{
        		// 修改用户信息
	            $resu = $user->editData($map,$data);
	            if($resu !== false ){
					$this->success('修改用户信息成功!');
	            }else{
	            	$this->error("修改用户信息失败！");
	            }
			}
		}else{
			//获取用户信息
			$usermsg = $user
						->field('id,title,username,phone,firmid,operation_jur')
						->where(array('id'=>I('get.id')))
						->find();
			if ($usermsg !== false and !empty($usermsg)) {
				// 根据firmid获取公司操作权限
				$firm = new \Common\Model\FirmModel();
				$firmmsg = $firm->getFirmOperationSearch(I('get.firmid'));
						
				// 获取公司下操作的船信息
				$ship = new \Common\Model\ShipModel();
				$where = array(
					'id'=>array('in',$firmmsg['operation_jur'])
					);
				$shiplist = $ship->field('id,shipname')->where($where)->select();

				$operation_jur = explode(',',$usermsg['operation_jur']);

				$assign = array(
					'usermsg'	=>	$usermsg,
					'firmmsg'	=>   $firmmsg,
					'shiplist'	=>   $shiplist,
					'operation_jur'=>$operation_jur
				);
				$this->assign($assign);
				$this->display();
			} else {
				$this->error('获取数据失败！');
			}
			
		}
	}

	/**
	 * 新增用户
	 * */
	public function add()
	{
		$user = new \Common\Model\UserModel();
		if(IS_POST)
		{
			// 判断提交的数据是否含有特殊字符
    		$res = judgeOneString( I('post.title') );
    		if ($res == true) {
    			$this->error('账号不能含有特殊字符');
    			exit;
    		}
			
			$data=I('post.');
			$user = new \Common\Model\UserModel();
			$res = $user->adddatas($data);
			if ($res['code'] == '1') {
				$this->success('新增用户成功!');
			} else {
				$this->error('新增失败');
			}
		}else{
			$firmid = $user->getFieldById($_SESSION['uid'],'firmid');
			// 根据firmid获取公司操作权限
			$firm = new \Common\Model\FirmModel();
			$firmmsg = $firm->getFirmOperationSearch($firmid);
					
			// 获取公司下操作的船信息
			$ship = new \Common\Model\ShipModel();
			$where = array(
				'id'=>array('in',$firmmsg['operation_jur'])
				);
			$shiplist = $ship->field('id,shipname')->where($where)->select();

			$assign = array(
				'firmmsg'	=>   $firmmsg,
				'shiplist'	=>   $shiplist,
				'id'	=>   $_SESSION['uid']
				);
			$this->assign($assign);
			$this->display();
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
			'id'   => intval($_POST['id'])
		);
		//验证通过 可以对数据进行操作
		$res=$user->editData($map,$data);
		if($res !== false)
		{
			//成功 
			echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
		}else {
			//改变用户状态失败
			echo ajaxReturn(array("state" => 2, 'msg' => "改变用户状态失败"));
		}
	}

	/**
	 * 重置密码
	 * */
	public function resetpwd()
	{
		$id = intval($_POST['id']);//接受id
		$user = new \Common\Model\UserModel();
		$pwd = "000000";
		$pwd = encrypt($pwd);	//加密
		$data=array(
				'pwd'=>$pwd,
		);
		$map = array(
			'id'   =>   $id
		);
		$res = $user->editData($map,$data);
		if( $res !== FALSE)
		{
			//成功 
			echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
		}else {
			//重置密码失败
			echo ajaxReturn(array("state" => 2, 'msg' => "重置密码失败"));
		}
	}

	/**
	 * 配置查询条件
	 * */
	public function configSearch()
	{
		$user = new \Common\Model\UserModel();
	    if (IS_POST) {
	    	$map = array(
				'id'  => I('post.id')
			);
			if (I('post.search_jur')) {
				// 将数组转换字符串
				$search_jur = implode(',',I('post.search_jur'));
				$data['search_jur'] = $search_jur;
			}else{
				$data['search_jur'] = '';
			}

			if(!$user->create($data)){
				//对data数据进行验证
				$this->error($user->getError());
			}else{
        		// 修改用户查询条件
	            $resu = $user->editData($map,$data);
	            if($resu !== false ){
					$this->success('修改用户查询条件成功!');
	            }else{
	            	$this->error("修改用户查询条件失败！");
	            }
			}
	    } else {
	    	$where = array(
	    		'u.id'	=>	I('get.id')
	    		);
	    	$data = $user
	    			->alias('u')
	    			->field('u.id,u.search_jur,u.firmid,f.operation_jur')
	    			->join('left join firm f on f.id = u.firmid')
	    			->where($where)
	    			->find();
	    	if ($data !== false and !empty($data)) {
	    		// 获取公司列表及公司名下能操作的船列表
				$ship = new \Common\Model\ShipModel();
				$operation_jur = explode(',',$data['operation_jur']);
				$shiplist = $ship
								->alias('s')
								->field('s.id,s.shipname,s.firmid,f.firmname')
								->join('left join firm f on f.id = s.firmid')
								->where(array('s.id'=>array('in',$operation_jur)))
								->select();
			    // 组装数据
				$firmlist = array();
				foreach ($shiplist as $key => $value) {
					$firmlist[$value['firmid']]['firmname'] = $value['firmname'];
					$firmlist[$value['firmid']]['shiplist'][] = array('id'=>$value['id'],'shipname'=>$value['shipname']);
				}

				// 字符串转换数组
	    		$data['search_jur'] = explode(',',$data['search_jur']);
	    		$assign = array(
	    			'data'	=>  $data,
	    			'firmlist'=>$firmlist
	    			);
	    		$this->assign($assign);
	    		$this->display();
	    	} else {
	    		$this->error('获取数据有误！');
	    	}
	    }
	    
	}

	/**
	 * 修改公司资料
	 */
	public function firmmsg(){
		if (IS_POST) {
			$data = array(
				'id'	=>	I('post.id'),
				'firmname'	=>	I('post.firmname'),
				'location'	=>	I('post.location'),
				'content'	=>	I('post.content'),
				'people'	=>	I('post.people'),
				'phone'		=>	I('post.phone')
				);
	    	// 判断提交的数据是否含有特殊字符
    		$res = judgeTwoString($data);
    		if ($res == false) {
    			$this->error('数据不能含有特殊字符');
    			exit;
    		}
    		$logo = I('post.logo');
    		if (!empty($logo)) {
    			$data['logo'] = I('post.logo');
    		}
    		$firm = new \Common\Model\FirmModel();
	    	// 对数据进行验证
	    	if (!$firm->create($data)){
			    // 如果创建失败 表示验证没有通过 输出错误提示信息
			    $this->error($firm->getError());
			}else{
			    // 验证通过 可以进行其他数据操作
				$map = array(
					'id'	=>   $data['id']
					);
			    $res = $firm->editData($map,$data);
			    if ($res !== false) {
			     	$this->success('修改成功！');
			    } else {
			     	$this->error('修改失败！');
			    }
			}
		} else {
			$uid = $_SESSION['uid'];
			// 获取公司ID
			$user = new \Common\Model\UserModel();
			$data = $user
					->field('f.id,f.firmname,f.people,f.phone,f.location,f.content,f.logo')
					->alias('u')
					->join('left join firm f on f.id = u.firmid')
					->where(array('u.id'=>$uid))
					->find();
			$assign=array(
			    'data'=>$data
			    );
			$this->assign($assign);
			$this->display();
		}
	}

	/**
	 * 头像上传
	 */
	public function upload_ajax(){
	    if(IS_AJAX){
        	$base64_image_content = $_POST['image'];
        	$res = upload_ajax($base64_image_content);
            $this->ajaxReturn(json_encode($res));    
        }
	}
}