<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
/**
 * 充值管理 
 * 2018.4.27
 * */
class RechargeController extends AdminBaseController 
{
	/**
	 * 充值列表
	 * */
	public function index()
	{
	    $recharge = new \Common\Model\RechargeModel();
	    $where[] = '1';
		if (I('get.firmid')) {
			$where['r.firmid'] = trimall(I('get.firmid'));
		}
		if (I('get.number')) {
			$where['r.number'] = trimall(I('get.number'));
		}
	    $count = $recharge
	    		->alias('r')
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
	    $data = $recharge
	    			->alias('r')
	    			->field('r.*')
	    			->where($where)
	    			->order('r.id')
	    			->limit($begin,$per)
	    			->select();
	    //获取公司列表
	    $firm = new \Common\Model\FirmModel();
	    $firmlist = $firm
	    			->field('id,firmname')
	    			->order("firmname desc")
	    			->select();
	    $assign=array(
            'data'=>$data,
            'page'=>$page,
            'firmlist'=>$firmlist
        );
        // p($assign);die;
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 线下充值
	 * */
	public function add()
	{
	    if (IS_POST) {
	    	$recharge = new \Common\Model\RechargeModel();
	    	// $data = I('post.');
	    	// 自动充值单号 三个是随机生成的字母+年月日时分秒+（1~10000）随机数
	    	$number = chr(rand(97,122)).chr(rand(97,122)).chr(rand(97,122)).date('YmdHis').rand(1,10000);

	    	$data = array(
   				'number' 	=>  	$number,
   				'firmid' 	=>  	I('post.firmid'),
   				'uid' 		=>  	$_SESSION['adminuid'],
   				'money' 	=>  	I('post.money'),
   				'time'	 	=>  	time(),
   				'ordertime' =>  	time(),
   				'status' 	=>  	1,
   				'channel' 	=>  	I('post.channel'),
   				'source' 	=>  	'管理',
   				'remark' 	=>  	I('post.remark'),
   				);
	    	$res = $recharge->xxRecharge($data);
	    	if ($res['code'] == '1') {
	    		$this->success('充值成功',U('index'));
	    	} else {
	    		$this->error('充值失败,原因是：'.$res['msg']);
	    	}
	    	
	    } else {
	    	$this->error('操作失败');
	    }
	    
	}
}