<?php
namespace Index\Controller;
use Common\Controller\IndexBaseController;
/**
 * 充值管理 
 * */
class RechargeController extends IndexBaseController 
{
	/**
	 * 充值列表
	 * */
	public function index()
	{
		$user=new \Common\Model\UserModel();
		$firmid = $user->getFieldById($_SESSION['user_info']['id'],'firmid');

	    $recharge = new \Common\Model\RechargeModel();
		$where['r.firmid'] = $firmid;
	    $count = $recharge
	    		->alias('r')
	    		->where($where)
	    		->count();
	    // 分页
		$page=new \Org\Nx\Page($count,20);

	    $data = $recharge
	    			->alias('r')
	    			->field('r.*')
	    			->where($where)
	    			->order('r.id')
	    		    ->limit($page->firstRow,$page->listRows)
	    		    ->order('id desc')
	    			->select();

	    $assign=array(
            'data'=>$data,
            'page'=>$page->show()
        );
        // p($assign);die;
        $this->assign($assign);
	    $this->display();
	}
}