<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class ShipController extends HomeBaseController 
{
	/**
	 * 公司船舶列表
	 */
	public function index()
	{
		$user = new \Common\Model\UserModel();
		$usermsg = $user
					->where(array('id'=>$_SESSION['uid']))
					->find();
	    if ($usermsg !== false or $usermsg['firmid'] !== '') {
	    	// 获取公司操作权限船舶
	    	$firm = new \Common\Model\FirmModel();
	    	$firmmsg = $firm
	    					->where(array('id'=>$usermsg['firmid']))
	    					->find();
	    	$operation_jur = explode(',',$firmmsg['operation_jur']);
	    	$ship = new \Common\Model\ShipModel();
	    	$where = array(
	    		'id'	=>  array('in',$operation_jur)
	    		);
	    	$per = 24;
			if($_GET['p'])
			{
				$p=$_GET['p'];
			}else {
				$p=1;
			}
			$count = $ship->where($where)->count();
			//分页
		    $page = fenye($count,$per);
		    $begin=($p-1)*$per;
	    	$list = $ship
	    				->where($where)
	    				->limit($begin,$per)
	    				->select();
	    	$assign = array(
	    		'list'	=>	$list,
	    		'page'	=>	$page
	    		);
	    	$this->assign($assign);
 			$this->display();
	    } else {
	    	$this->error('没有所属公司或者数据错误');
	    }
	}

	/**
	 * 新增船舶
	 */
	public function add()
	{
		$ship = new \Common\Model\ShipModel();
	    if (IS_POST) {
	    	$data = I('post.');
	    	$data['uid'] = $_SESSION['uid'];
	    	// 判断是否有底量测量空，有底量测量孔就算法字段为:c
	    	if ($data['is_diliang'] == '1') {
	    		$data['suanfa'] = 'c';
	    	}
	    	$res = $ship->addship($data);
	    	if ($res['code'] == '1') {
	    		$this->success('新增成功');
	    	} else {
	    		$this->error(''.$res['msg'].'');
	    		// $this->error('新增失败('.$res['code'].')');
	    	}
	    	
	    } else {
	    	$user = new \Common\Model\UserModel();
	    	$msg = $user
	    					->alias('u')
	    					->field('u.id,u.imei,u.firmid,f.firmtype')
	    					->where(array('u.id'=>$_SESSION['uid']))
	    					->join('left join firm f on f.id=u.firmid')
	    					->find();
	    	$firm = new \Common\Model\FirmModel();
	    	if ($msg['firmtype'] == '1') {
	    		// 检验公司获取所有的船公司
	    		$list = $firm->field('id,firmname')->where(array('firmtype'=>'2'))->select();
	    	} else {
	    		// 船舶公司获取本公司
	    		$list =$firm->field('id,firmname')->where(array('id'=>$msg['firmid']))->select();
	    	}
	    	$this->assign('list',$list);
	    	$this->display();
	    }
	}

	/**
	 * 修改船舶信息
	 */
	public function edit()
	{
		$ship = new \Common\Model\ShipModel();
	    if (IS_POST) {
	    	$data = I('post.');
	     	// 判断提交的数据是否含有特殊字符
    		$res = judgeTwoString($data);
    		if ($res == false) {
    			$this->error('数据不能含有特殊字符');
    			exit;
    		}
	        $map = array(
	        	'id'   =>  $data['id']
	        );
	        if(!$ship->create($data)){
				//对data数据进行验证
				$this->error($ship->getError());
			}else{
		        $result=$ship->editData($map,$data);
		        if($result !== false){
		        	$this->success('修改成功');
		        }else{
		        	$this->error('修改失败');
		        }
			}
	    } else {
	    	$user = new \Common\Model\UserModel();
	    	$msg = $user
	    					->alias('u')
	    					->field('u.id,u.imei,u.firmid,f.firmtype')
	    					->where(array('u.id'=>$_SESSION['uid']))
	    					->join('left join firm f on f.id=u.firmid')
	    					->find();
	    	$firm = new \Common\Model\FirmModel();
	    	if ($msg['firmtype'] == '1') {
	    		// 检验公司获取所有的船公司
	    		$list = $firm->field('id,firmname')->where(array('firmtype'=>'2'))->select();
	    	} else {
	    		// 船舶公司获取本公司
	    		$list =$firm->field('id,firmname')->where(array('id'=>$msg['firmid']))->select();
	    	}
	    	// 船舶信息
	    	$shipmsg = $ship->where(array('id'=>$_GET['id']))->find();
	    	$this->assign('list',$list);
	    	$this->assign('shipmsg',$shipmsg);
	    	$this->display();
	    }
	    
	}
}