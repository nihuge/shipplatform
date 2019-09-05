<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class CabinController extends HomeBaseController 
{
	/**
	 * 船舱
	 */
	public function index()
	{
	    $where[] = '1';
		if (I('get.shipid')) {
			$where['c.shipid'] = I('get.shipid');
		}
	    $cabin = new \Common\Model\CabinModel();
	    $count = $cabin
	    		->alias('c')
	    		->field('s.shipname')
	    		->where($where)
	    		->count();
	    $per = 24;
		if($_GET['p'])
		{
			$p=$_GET['p'];
		}else {
			$p=1;
		}
		//分页
	    $page = fenye($count,$per);
	    $begin=($p-1)*$per;

	    $data = $cabin
	    	->field('c.id,c.cabinname,c.altitudeheight,c.bottom_volume,c.pipe_line,c.shipid,s.shipname,s.tankcapacityshipid,s.rongliang,s.rongliang_1,s.zx,s.zx_1')
	    	->alias('c')
	    	->join('left join ship s on s.id=c.shipid')
	    	->where($where)
	    	->order('c.shipid asc,c.id asc')
	    	->limit($begin,$per)
	    	->select();
	    //获取船列表
	    $ship = new \Common\Model\ShipModel();
	    $shiplist = $ship
	    			->field('id,shipname')
	    			->order("shipname desc")
	    			->select();
	    $assign=array(
            'data'=>$data,
            'page'=>$page,
            'shiplist'=>$shiplist
        );
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 舱修改
	 */
	public function edit()
	{
		$cabin = new \Common\Model\CabinModel();
	    if (IS_POST) {
	    	//判断同一条船不能有重复的舱名
	    	$where = array(
	    		'shipid'	=> I('post.shipid'),
	    		'cabinname' => I('post.cabinname'),
	    		'id'		=> array('NEQ',I('post.id'))
	    	);
	    	$count = $cabin->where($where)->count();
	    	if ($count>0) {
	    		$this->error('该船已存在该舱名');
	    		exit;
	    	}
	    	$data = I('post.');
	        // 对数据进行验证
	    	if (!$cabin->create($data)){
			    // 如果创建失败 表示验证没有通过 输出错误提示信息
			    $this->error($cabin->getError());
			}else{
			    // 验证通过 可以进行其他数据操作
				$map = array(
					'id'	=>   $data['id']
					);
				unset($data['id']);
			    $res = $cabin->editData($map,$data);
			    if ($res !== false) {
			     	$this->success('修改成功！');
			    } else {
			     	$this->error('修改失败！');
			    }
			}
	    } else {
	     	//获取ID获取容量的信息
	     	$msg = $cabin
	     			->where(array('id'=>I('get.id')))
	     			->find();
	     	$ship = new \Common\Model\ShipModel();
	     	$shiplist = $ship->field('id,shipname')->select();
	     	$assign=array(
	            'msg'=>$msg,
	            'shiplist'=>$shiplist
	        );
	        $this->assign($assign);
	     	$this->display();
	    }
	}

	/**
	 * 舱新增
	 */
	public function add()
	{
	    if (IS_POST) {
	    	//判断同一条船不能有重复的舱名
	    	$where = array(
	    		'shipid'	=> I('post.shipid'),
	    		'cabinname' => I('post.cabinname')
	    	);
	    	$cabin = new \Common\Model\CabinModel();
	    	$count = $cabin->where($where)->count();
	    	if ($count>0) {
	    		$this->error('该船已存在该舱名');
	    		exit;
	    	}
	     	// 去除键值首位空格
	     	$data = I('post.');
	        // 对数据进行验证
	    	if (!$cabin->create($data)){
			     // 如果创建失败 表示验证没有通过 输出错误提示信息
			     $this->error($cabin->getError());
			}else{
			     // 验证通过 可以进行其他数据操作
			    $res = $cabin->addData($data);
			    if ($res) {
			     	$this->success('新增成功！');
			    } else {
			     	$this->error('新增失败！');
			    }
			}
	    } else {
	    	$user = new \Common\Model\UserModel();
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
	     	$assign=array(
	            'shiplist'=>$shiplist
	        );
	        $this->assign($assign);
	     	$this->display();
	    }
	}

	/**
	 * 判断船舶是否有底量测量
	 */
	public function ajax_diliang()
	{
	    if (IS_AJAX) {
	    	$ship = new \Common\Model\ShipModel();
	    	$is_diliang = $ship->getFieldById($_POST ['shipid'],'is_diliang');
	    	echo json_encode($is_diliang);
	    } else {
			echo false;
		}
	}
}