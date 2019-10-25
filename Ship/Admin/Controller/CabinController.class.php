<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
/**
 *	船舱管理 
 * 2018.3.22
 * */
class CabinController extends AdminBaseController 
{
	private $db;
	public function __construct(){
		parent::__construct();
		$this->db = new \Common\Model\CabinModel();
	}

	/**
	 * 舱列表
	 */
	public function index()
	{
		$where[] = '1';
		if (I('get.shipid')) {
			$where['c.shipid'] = I('get.shipid');
		}
	 
	    $count = $this->db
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

	    $data = $this->db
	    	->field('c.id,c.cabinname,c.altitudeheight,c.bottom_volume,c.pipe_line,c.shipid,s.shipname,s.tankcapacityshipid,s.rongliang,s.rongliang_1,s.zx,s.zx_1')
	    	->alias('c')
	    	->join('left join ship s on s.id=c.shipid')
	    	->where($where)
	    	->order('c.shipid desc,c.id asc')
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
	    if (IS_POST) {
	    	//判断同一条船不能有重复的舱名
	    	$where = array(
	    		'shipid'	=> I('post.shipid'),
	    		'cabinname' => I('post.cabinname'),
	    		'id'		=> array('NEQ',I('post.id'))
	    	);
	    	$count = $this->db->where($where)->count();
	    	if ($count>0) {
	    		$this->error('该船已存在该舱名');
	    		exit;
	    	}
	    	$data = I('post.');
	        // 对数据进行验证
	    	if (!$this->db->create($data)){
			    // 如果创建失败 表示验证没有通过 输出错误提示信息
			    $this->error($this->db->getError());
			}else{
			    // 验证通过 可以进行其他数据操作
				$map = array(
					'id'	=>   $data['id']
					);
				unset($data['id']);
			    $res = $this->db->editData($map,$data);
			    if ($res !== false) {
			     	$this->success('修改成功！',U('index'));
			    } else {
			     	$this->error('修改失败！');
			    }
			}
	    } else {
	     	//获取ID获取容量的信息
	     	$msg = $this->db
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
	    	$data = array();
	    	$names = array();
	    	foreach (I('post.data') as $key => $value) {
		    	$where = array(
		    		'shipid'	=> I('post.shipid'),
		    		'cabinname' => $value['cabinname']
		    	);
		  
		    	$count = $this->db->where($where)->count();
		    	if ($count>0) {
		    		$this->error('该船已存在该舱名');
		    		exit;
		    	}	  
		    	$names[] = $value['cabinname'];
		    	$value['shipid'] = I('post.shipid');
		    	$data[] = $value;
	    	}

	    	// 判断提交的舱名是否有重复
			$repeat_arr = FetchRepeatMemberInArray ( $names );
			if($repeat_arr){
			  	$this->error('提交的舱名存在重复');
		    	exit;
			}
			M()->startTrans();
			foreach ($data as $key => $value) {
		        // 对数据进行验证
		    	if (!$this->db->create($value)){
				     // 如果创建失败 表示验证没有通过 输出错误提示信息
				     $this->error($this->db->getError());
				}else{
				     // 验证通过 可以进行其他数据操作
				    $res = $this->db->addData($value);
				    if ($res) {
				     	
				    } else {
				    	M()->rollback();
				     	$this->error('新增失败！');
				     	die;
				    }
				}				
			}
			M()->commit();
			$this->success('新增成功！',U('index'));
	    } else {
	    	// 获取船列表
	     	$ship = new \Common\Model\ShipModel();
	     	$shiplist = $ship
	     				->field('id,shipname,cabinnum,suanfa')
	     				->order('shipname asc')
	     				->select();
			// 去除 
	     	foreach ($shiplist as $key => $value) {  		
	     		$num = $this->db->where(array('shipid'=>$value['id']))->count();
	     		if ($num == $value['cabinnum']) {
	     			unset($shiplist[$key]);
	     		}else{
	     			$shiplist[$key]['cabinnum'] = $value['cabinnum']-$num;
	     		}
	     	}
	     	// p($shiplist);die;
	     	$assign=array(
	            'shiplist'=>$shiplist
	        );
	        $this->assign($assign);
	     	$this->display();
	    }
	}
}