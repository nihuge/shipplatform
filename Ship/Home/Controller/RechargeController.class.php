<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
/**
 * 充值管理 
 * */
class RechargeController extends HomeBaseController 
{
	/**
	 * 充值列表
	 * */
	public function index()
	{
		$user=new \Common\Model\UserModel();
		$firmid = $user->getFieldById($_SESSION['uid'],'firmid');

	    $recharge = new \Common\Model\RechargeModel();
		$where['r.firmid'] = $firmid;
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

	    $assign=array(
            'data'=>$data,
            'page'=>$page
        );
        // p($assign);die;
        $this->assign($assign);
	    $this->display();
	}
}