<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
/**
 * 消费记录管理
 */
class ConsumptionController extends HomeBaseController
{
	/**
	 * 消费记录列表
	 * */
	public function index()
	{
		$user=new \Common\Model\UserModel();
		$firmid = $user->getFieldById($_SESSION['uid'],'firmid');
	    $consump = new \Common\Model\ConsumptionModel();
		$where['c.firmid'] = $firmid;
	    $count = $consump
	    		->alias('c')
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
	    $data = $consump
	    			->alias('c')
	    			->field('u.username,f.firmname,c.*,r.personality')
	    			->where($where)
	    			->join('left join user u on u.id=c.uid')
	    			->join('left join firm f on f.id=c.firmid')
	    			->join('left join result r on r.id=c.resultid')
	    			->order('c.id desc')
	    			->limit($begin,$per)
	    			->select();
	    foreach ($data as $key => $value) {
	    	$personality = json_decode($value['personality'],true);
	    	$data[$key]['voyage'] = $personality['voyage'];
	    }

	    $assign=array(
            'data'=>$data,
            'page'=>$page,
        );
        $this->assign($assign);
	    $this->display();
	}
}