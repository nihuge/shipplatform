<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

class RongController extends AdminBaseController 
{
	/**
	 * 容量表列表
	 */
	public function rong()
	{
		$where[] = '1';
		if (I('get.cabinid')) {
			$where['r.cabinid'] = I('get.cabinid');
		}
	    $tname = I('get.tname');
	    $rong = M("$tname");

	    $count = $rong
	    	->field('r.*')
	    	->alias('r')
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

	    $data = $rong
	    	->alias('r')
	    	->field('r.*,c.cabinname,s.shipname')
	    	->join('left join cabin c on c.id = r.cabinid ')
	    	->join('left join ship s on s.id = c.shipid ')
	    	->where($where)
	    	->order('r.id asc')
	    	->limit($begin,$per)
	    	->select();

	    //根据舱ID获取船的舱列表
	    $cabin = new \Common\Model\CabinModel();
	    $cabinlist = $cabin->getcabinlist($data[0]['cabinid']);

	    $assign=array(
            'data'=>$data,
            'page'=>$page,
            'tname'=>$tname,
            'name'=>$data[0]['shipname'],
            'cabinlist'=>$cabinlist,
            'cname'=>$data[0]['cabinname']
        );
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 容量表修改
	 */
	public function edit()
	{
		
	    if (IS_POST) {
	    	$tname = I('post.tname');
	     	$rong = M("$tname");
	     	// 去除键值首位空格
	     	$data = I('post.');

	        foreach ($data as $k => $v) {
	            $data[$k]=trimall($v);
	        }
	        $map = array(
	        	'id'   =>  I('post.id')
	        );
	        $result=$rong->where($map)->save($data);
	        if($result !== false){
	        	$this->success('修改成功');
	        }else{
	        	$this->error('修改失败');
	        }
	     } else {
	     	//获取ID获取容量的信息
	     	$tname = I('get.tname');
	     	$rong = M("$tname");
	     	$msg = $rong
	     			->where(array('id'=>I('get.id')))
	     			->find();
	     	//根据舱id获取船id，在获取船的舱列表
	     	$cabin = new \Common\Model\CabinModel();

	     	$cabinlist = $cabin->getcabinlist($msg['cabinid']);
	     	$assign=array(
	            'msg'=>$msg,
	            'cabinlist'=>$cabinlist,
	            'tname'  => $tname
	        );
	        $this->assign($assign);
	     	$this->display();
	     }
	}
}