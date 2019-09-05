<?php
namespace Index\Controller;
use Common\Controller\IndexBaseController;
/**
 * 消费记录管理
 */
class ConsumptionController extends IndexBaseController
{
	/**
	 * 消费记录列表
	 * */
	public function index()
	{
		$user=new \Common\Model\UserModel();
		$firmid = $user->getFieldById($_SESSION['user_info']['id'],'firmid');
	    $consump = new \Common\Model\ConsumptionModel();
		$where['c.firmid'] = $firmid;
	    $count = $consump
	    		->alias('c')
	    		->where($where)
	    		->count();
	    // 分页
		$page=new \Org\Nx\Page($count,20);

	    $data = $consump
	    			->alias('c')
	    			->field('u.username,f.firmname,c.*,r.personality,s.shipname')
	    			->where($where)
	    			->join('left join user u on u.id=c.uid')
	    			->join('left join firm f on f.id=c.firmid')
	    			->join('left join result r on r.id=c.resultid')
	    			->join('left join ship s on s.id=r.shipid')
	    			->order('c.id desc')
	    		    ->limit($page->firstRow,$page->listRows)
	    		    ->order('id desc')
	    			->select();
	    foreach ($data as $key => $value) {
	    	$personality = json_decode($value['personality'],true);
	    	$data[$key]['voyage'] = $personality['voyage'];
	    }

	    $assign=array(
            'data'=>$data,
            'page'=>$page->show()
        );
        $this->assign($assign);
	    $this->display();
	}
}