<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

class TripbysternController extends AdminBaseController 
{
	/**
	 * 纵倾表列表
	 */
	public function zong()
	{
		$where[] = '1';
		if (I('get.cabinid')) {
			$where['t.cabinid'] = I('get.cabinid');
		}

	    $tname = I('get.tname');
	    $zong = M("$tname");
	    $count = $zong
	    	->alias('t')
	    	->field('t.*')
	    	->where($where)
	    	->count();
	    $per = 50;
		if($_GET['p'])
		{
			$p=$_GET['p'];
		}else {
			$p=1;
		}
		//分页
	    $page = fenye($count,$per);
	    $begin=($p-1)*$per;

	    $data = $zong
	    	->alias('t')
	    	->field('t.*,c.cabinname,c.shipid')
	    	->join('left join cabin c on c.id = t.cabinid')
	    	->where($where)
	    	->order('id asc')
	    	->limit($begin,$per)
	    	->select();

	    //获取纵倾字段
	    $ship = new \Common\Model\ShipModel();
	    $msg = $ship
	    		->field('tripbystern,shipname')
	    		->where(array('id'=>I('get.shipid')))
	    		->find();
	    $shu = json_decode($msg['tripbystern'],true);
	    $r = getpao($shu);
	    
	    //根据舱ID获取船的舱列表
	    $cabin = new \Common\Model\CabinModel();
	    $cabinlist = $cabin->getcabinlist($data[0]['cabinid']);

	    $assign=array(
            'data'=>$data,
            'page'=>$page,
            'tname'=>$tname,
            'name'=>$msg['shipname'],
            'r'=>$r,
            'cabinlist'=>$cabinlist,
            'cname'  =>  $data[0]['cabinname']
        );
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 修改纵倾表
	 */
	public function edit()
	{
	    if (IS_POST) {
	    	$tname = I('post.tname');
	     	$t = M("$tname");
	     	// 去除键值首位空格
	     	$data = I('post.');
	        $map = array(
	        	'id'   =>  I('post.id')
	        );
	        $result=$t->editData($map,$data);
	        if($result !== false){
	        	$this->success('修改成功');
	        }else{
	        	$this->error('修改失败');
	        }
	     } else {
	     	$tname = I('get.tname');
	     	$t = M("$tname");
	     	//根据ID获取纵倾的信息
	     	$msg = $t
	     			->where(array('id'=>I('get.id')))
	     			->find();
	     	//根据舱id获取船id，在获取船的舱列表
	     	$cabin = new \Common\Model\CabinModel();
	     	$cabinlist = $cabin->getcabinlist($msg['cabinid']);
	     	$assign=array(
	            'msg'=>$msg,
	            'cabinlist'=>$cabinlist,
	            'tname' =>$tname
	        );
	        $this->assign($assign);
	     	$this->display();
	     }
	}
}