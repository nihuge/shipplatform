<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
/**
 * 消费记录管理
 * 2018.4.27
 */
class ConsumptionController extends AdminBaseController
{
	/**
	 * 消费记录列表
	 * */
	public function index()
	{
	    $consump = new \Common\Model\ConsumptionModel();
	    $where[] = '1';
		if (I('get.firmid')) {
			$where['c.firmid'] = trimall(I('get.firmid'));
		}
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
	    			->field('u.username,f.firmname,c.*')
	    			->where($where)
	    			->join('left join user u on u.id=c.uid')
	    			->join('left join firm f on f.id=c.firmid')
	    			->order('c.id desc')
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
        $this->assign($assign);
	    $this->display();
	}
}