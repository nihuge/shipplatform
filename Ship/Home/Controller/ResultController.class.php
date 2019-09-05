 <?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
/**
 * 作业列表
 * 2017.12.6
 * */
class ResultController extends HomeBaseController
{
	/**
	 * 指令列表
	 */
	public function index()
	{
	    $result = new \Common\Model\ResultModel();
	    $count = $result
	    		->where(array('uid'=>$_SESSION['uid']))
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
	    $list = $result
	    	->alias('r')
	    	->field('r.*,s.shipname')
	    	->join('left join ship s on s.id=r.shipid')
	    	->where(array('uid'=>$_SESSION['uid']))
	    	->limit($begin,$per)
			->order('r.id desc')
	    	->select();
	    foreach ($list as $key => $value) {
	    	$list[$key]['personality'] = json_decode($value['personality'],true);
	    }
	    $assign=array(
            'list'=>$list,
            'page'=>$page,
        );
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 新增作业
	 */
	public function add()
	{
	    if(IS_POST){
	    	//判断相同船是否有相同的航次
	    	$result = new \Common\Model\ResultModel();
    		//添加数据
    		$data = I('post.');
    		$data['time'] = time();
    		$data['uid'] = $_SESSION['uid'];
		     // 验证通过 可以进行其他数据操作
		    $res = $result->addResult($data,$_SESSION['uid']);
		    if ($res['code'] == '1') {
		     	$this->success('新增成功！');
		    } else {
		     	$this->error('新增失败！'.$res['code']);
		    }

	    }else{
	    	$ship = new \Common\Model\ShipModel();
	    	$where = array(
				'id'   =>  $_SESSION['uid']
			);
			//获取用户的船舶列表id
			$user = new \Common\Model\UserModel();
			$usermsg = $user
					->field('operation_jur,firmid')
					->where($where)
					->find();
		
			if ($usermsg !== false and !empty($usermsg['operation_jur'])) {
				$shiplist = explode(',',$usermsg['operation_jur']);	
				$list = $ship
						->field('id,shipname')
						->where(array('id'=>array('IN',$shiplist)))
						->order('shipname asc')
						->select();
				
				if($list !== false){
					// 获取公司个性化字段
					$firm = new \Common\Model\FirmModel();
					$personality_id = $firm->getFieldById($usermsg['firmid'],'personality');
	                $personality_id = json_decode($personality_id,true);
	                $personalitylist = array();
	                $person = new \Common\Model\PersonalityModel();
	                foreach ($personality_id as $key => $value) {
	                    $personalitylist[] = $person
	                                            ->field('id,name,title')
	                                            ->where(array('id'=>$value))
	                                            ->find();
	                }
					$this->assign('shiplist',$list);
					$this->assign('personalitylist',$personalitylist);
					// p($personalitylist);die;
				}
			} else {
				//该用户下面没有船	10
 	    		echo '<script>alert("该用户下面没有船!");top.location.reload(true);window.close();</script>';
 	    		exit;
			}
		    $this->display();
	    }
	}

	/**
	 * 修改作业
	 */
	public function edit()
	{
    	$result = new \Common\Model\ResultModel();
	    if(IS_POST){
	    	$data = I('post.');
	    	$data['resultid'] = I('post.id');
	    	$res = $result->editResult($data);
		    if ($res['code'] == '1') {
		     	$this->success('修改成功！');
		    } else {
		     	$this->error('修改失败！'.$res['code']);
		    }
	    }else{
	    	//判断指令有没有作业，
	    	$rl = new \Common\Model\ResultlistModel();
	    	$re = $rl->where(array('resultid'=>I('get.id')))->count();
	    	if($re > 0){
	    		echo '<script>alert("该指令已作业，不能修改!");top.location.reload(true);window.close();</script>';
 	    		exit;
	    	}
	    	$ship = new \Common\Model\ShipModel();
	    	$where = array(
				'id'   =>  $_SESSION['uid']
			);
			//获取用户的船舶列表id
			$user = new \Common\Model\UserModel();
			$usermsg = $user
					->field('operation_jur,firmid')
					->where($where)
					->find();
		
			$slist = explode(',',$usermsg['operation_jur']);	
			$shiplist = $ship
					->field('id,shipname')
					->where(array('id'=>array('IN',$slist)))
					->order('shipname asc')
					->select();
	    	//获取作业信息
	    	$msg = $result
	    		->where(array('id'=>I('get.id')))->find();
	    	$personalitymsg = json_decode($msg['personality'],true);
	    	// 获取公司个性化字段
			$firm = new \Common\Model\FirmModel();
			$personality_id = $firm->getFieldById($usermsg['firmid'],'personality');
            $personality_id = json_decode($personality_id,true);
            $personalitylist = array();
            $person = new \Common\Model\PersonalityModel();
            foreach ($personality_id as $key => $value) {
                $personalitylist[] = $person
                                        ->field('name,title')
                                        ->where(array('id'=>$value))
                                        ->find();
            }
	    	$assign = array(
	    		'shiplist'   =>  $shiplist,
	    		'personalitylist'   =>  $personalitylist,
	    		'personalitymsg'   =>  $personalitymsg,
	    		'msg'		 =>  $msg
	    	);
	    	$this->assign($assign);
		    $this->display();
	    }
	}

	/**
	 * 判断舱容表是否到期
	 */
	public function judge_time()
    {
    	if (IS_AJAX) {
    		    $ship = new \Common\Model\ShipModel();
                $expire_time = $ship->getFieldById(I('post.shipid'),'expire_time');
                if ($expire_time > time()) {
                    $data = array(
		    			'code'	=>  '1',
		    			'msg'	=>  '成功'
		    		);
		    		$this->ajaxReturn($data);
                } else {
                    $data = array(
		    			'code'	=>  '2',
		    			'msg'	=>  '该船舱容表已过期，请更新后再作业。'
		    		);
		    		$this->ajaxReturn($data);
                }
    	} else {
    		$data = array(
    			'code'	=>  '2',
    			'msg'	=>  '信息有误'
    		);
    		$this->ajaxReturn($data);
    	}
    }

    
}