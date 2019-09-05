<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

class ZongxiuController extends AdminBaseController 
{
	/**
	 * 5005纵情修正表列表
	 */
	public function zx()
	{
		$where[] = '1';
		if (I('get.cabinid')) {
			$where['z.cabinid'] = I('get.cabinid');
		}
	    $tname = I('get.tname');
	    $zx = M("$tname");

	    $count = $zx
	    	->alias('z')
	    	->field('z.*')
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

	    $data = $zx
	    	->alias('z')
	    	->field('z.*,c.cabinname')
	    	->join('left join cabin c on c.id = z.cabinid ')
	    	->where($where)
	    	->order('z.id asc')
	    	->limit($begin,$per)
	    	->select();
	   
	    //获取纵倾修正字段
	    $ship = new \Common\Model\ShipModel();
	    $map = array('id'=>I('get.shipid'));
	    $msg = $ship
	    		->field('trimcorrection,trimcorrection1,shipname,suanfa,zx_1')
	    		->where($map)
	    		->find();
	    $shu = json_decode($msg['trimcorrection'],true);
		if ($msg['zx_1'] == $tname) {
			if (!empty($msg['trimcorrection1'])) {
				$shu = json_decode($msg['trimcorrection1'],true);
			}
		}
	    $r = getpao($shu);
	    // p($r);die;
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
            'cname'=>$data[0]['cabinname']
        );
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 5005纵倾修正表修改
	 */
	public function edit()
	{
	    if (IS_POST) {
	    	$tname = I('post.tname');
	     	$z = M("$tname");
	     	// 去除键值首位空格
	     	$data = I('post.');
	     	unset($data['id']);
	        foreach ($data as $k => $v) {
	            $data[$k]=trim($v);
	        }
	        $map = array(
	        	'id'   =>  I('post.id')
	        );
	        $result=$z->where($map)->save($data);
	        if($result !== false){
	        	$this->success('修改成功');
	        }else{
	        	$this->error('修改失败');
	        }
	     } else {
	     	$tname = I('get.tname');
	     	$z = M("$tname");
	     	//获取ID获取容量的信息
	     	$msg = $z
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