<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
/**
 * 作业列表
 * 2017.12.6
 * */
class SearchController extends HomeBaseController
{
	/**
	 * 列表
	 */
	public function index()
	{
		$result = new \Common\Model\ResultModel();
		$user = new \Common\Model\UserModel();
		$uid = $_SESSION['uid'];
		// 根据用户id获取可以查询的船列表
		$msg = $user->getUserOperationSeach($uid);
		$firmid = $user->getFieldById($uid,'firmid');
		if ($msg['search_jur'] == '') {
            // 查询权限为空时，查看所有操作权限之内的作业
            if ($msg['operation_jur'] == '') {
                $operation_jur = "all";
            }else{
                $operation_jur = $msg['operation_jur'];
            }
			$where = " r.uid ='$uid' and r.shipid in (".$operation_jur.")";

        }else{
            $where = " r.shipid in (".$msg['search_jur'].")";
        }

        if ($msg['look_other'] == '1') {
        	$where .= " and u.firmid=$firmid";
        }
		
		if(I('post.shipid')){
			if ($msg['search_jur']!=='') {
				// 判断提交的船是否在权限之内
				if (!in_array(I('post.shipid'),$msg['search_jur_array'])) {
					$this->error('该船不在查询范围之内！！');
					die;
				}
			}
			$shipid = trimall(I('post.shipid'));
			$where .= " and r.shipid=$shipid";
		}
		if(I('post.voyage')){
			$voyage = trimall(I('post.voyage'));
			// $where .= " and r.voyage=$voyage";
			$where.=" and r.personality like  '".'%"voyage":"%'. $voyage .'%\'';
		}
		if(I('post.locationname')){
			$locationname = trimall(I('post.locationname'));
			// $where .= " and r.locationname = $locationname";
			$where.=" and r.personality like  '".'%"locationname":"%'. $locationname .'%\'';
		}
		if(I('post.goodsname')){
			$goodsname = trimall(I('post.goodsname'));
			// $where .= " and r.goodsname=$goodsname";
			$where.=" and r.personality like  '".'%"goodsname":"%'. $goodsname .'%\'';
		}
		if(I('post.start')){
			$start = trimall(I('post.start'));
			// $where .= " and r.start = $start";
			$where.=" and r.personality like  '".'%"start":"%'. $start .'%\'';
		}
		if(I('post.objective')){
			$objective =trimall( I('post.objective'));
			// $where .= " and r.objective = $objective";
			$where.=" and r.personality like  '".'%"objective":"%'. $objective .'%\'';
		}
		if (I('post.time')) {
			$time = explode(' - ',I('post.time'));
			$starttime = strtotime($time[0]);
			$endtime   = strtotime($time[1]);
			$where .= " and r.time >= $starttime and r.time <= $endtime";
		}
    	// 获取船列表
    	$ship = new \Common\Model\ShipModel();
    	$shiplist = $ship
    			->field('id,shipname')
    			->order('shipname asc')
    			->select();

    	//获取数据列表
    	$count = $result
    			->alias('r')
    			->join('left join user u on u.id=r.uid')
	    		->join('left join firm f on f.id = u.firmid')
    			->where($where)
    			->count();
    	// 数据区分
	    $per = 24;
		if(isset($_GET['p']))
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
	    	->field('r.personality,r.weight,s.shipname,r.id,f.id as firmid,f.firmtype')
	    	->join('left join ship s on s.id=r.shipid')
	    	->join('left join user u on u.id=r.uid')
	    	->join('left join firm f on f.id = u.firmid')
	    	->where($where)
	    	->limit($begin,$per)
			->order('r.id desc')
	    	->select();
	    foreach ($list as $key => $value) {
	    	$list[$key]['personality'] = json_decode($value['personality'],true);
	    	if ($value['firmid'] == $firmid) {
	    		$list[$key]['colors'] = 'black';
	    	}else{
	    		$list[$key]['colors'] = 'red';
	    	}
	    }
    	$assign = array(
    		'shiplist'   =>  $shiplist,
    		'list'		 =>  $list,
            'page'		 =>  $page,
    	);
    	$this->assign($assign);
		$this->display();
	}

	/**
	 * 作业详情
	 * */
	public function searchmsg()
	{
		if(IS_GET){
			$res = new \Common\Model\ResultModel();
			$user = new \Common\Model\UserModel();
		    //判断用户状态、是否到期、标识比对
			//获取水尺数据
			$where =  array(
				'r.id'   =>   I('get.resultid')
				);
 	    	//查询作业列表
 	    	$list = $res
 	    			->alias('r')
 	    			->field('r.*,s.shipname,u.username,r.qianchi,r.houchi,s.goodsname goodname,f.firmtype as ffirmtype')
 	    			->join('left join ship s on r.shipid=s.id')
 	    			->join('left join user u on r.uid = u.id')
 	    			->join('left join firm f on u.firmid = f.id')
 	    			->where($where)
 	    			->find();
 	    	// 获取当前登陆用户的公司类型
 	    	$uid = $_SESSION['uid'];
 	    	$a = $user
 	    			->field('f.firmtype')
 	    			->alias('u')
 	    			->join('left join firm f on u.firmid = f.id')
 	    			->where(array('u.id'=>$uid))
 	    			->find();
 	    	$list['firmtype'] = $a['firmtype'];
			if($msg !== false)
			{
 	    		$where1 = array('re.resultid'=>$list['id']);
 	    		$resultlist = new \Common\Model\ResultlistModel();
 	    		$resultmsg = $resultlist
 	    							->alias('re')
 	    							->field('re.*,c.cabinname')
 	    							->join('left join cabin c on c.id = re.cabinid')
		 	    					->where($where1)
		 	    					->order('re.solt asc,re.cabinid asc')
		 	    					->select();
		 	    //以舱区分数据（）
		 	    foreach($resultmsg as $k=>$v){
    				$result[$v['cabinid']][]    =   $v;
				}
				// 个性化信息
				$personality = json_decode($list['personality'],true);
			    if (!empty($resultmsg)) {
			    	//取出舱详情最后一个元素时间
			    	$start = end($resultmsg);
			    	$starttime = date("Y-m-d H:i",$start['time']);
			    	//取出舱详情第一个元素时间
				    $end = reset($resultmsg);
				    $endtime = date("Y-m-d H:i",$end['time']);
			    }else{
			    	$starttime = '';
			    	$endtime   = '';
			    }
			    // 获取公司模板文件名
			    $map = array(
			    	'u.id'=>$_SESSION['uid']
			    	);
			    $ship = new \Common\Model\ShipModel();
			    $msg = $user
			    			->alias('u')
			    			->field('u.firmid,f.firmname,f.pdf')
			    			->join('left join firm f on f.id = u.firmid')
			    			->where($map)
			    			->find();

			    // 判断作业是否完成----电子签证
			    $coun = M('electronic_visa')
			    		->where(array('resultid'=>$list['id']))
			    		->count();

			    // 判断作业属于哪个类型的公司
			    
			    if ($msg['pdf'] == 'null' or empty($msg['pdf'])) {
			    	$pdf = 'ceshipdf';
			    } else {
			    	$pdf = $msg['pdf'];
			    }
 	    		$assign = array(
					'content'   => $list,
					'result'	=> $result,
					'starttime' => $starttime,
					'endtime'   => $endtime,
					'personality'=>$personality,
					'coun'=>$coun
				);
				// p($assign);die;
				$this->assign($assign);
				$this->display($pdf);
			}else{
 	    		$this->error('数据库连接错误');
			}
		}else{
			$this->error('未知错误');
		}
	}

	/**
	 * 评价
	 */
	public function evaluate(){
		// 判断是否打分
		$grade = I('post.grade');
		if ( empty( $grade ) ) {
			$this->error('请评分！');
		}
	    $result = new \Common\Model\ResultModel();
	    $data = array(
	    	'uid'	=> I('post.uid'),
	    	'id'	=> I('post.id'),
	    	'shipid'	=> I('post.shipid'),
	    	'grade'		=> I('post.grade'),
	    	'firmtype'	=> I('post.firmtype'),
	    	'content'	=> I('post.content'),
	    	'operater'	=> $_SESSION['uid']
	    	);
	    $res = $result->evaluate($data);
	    if ($res['code'] == '1') {
	    	$this->success('评价成功');
	    } else {
	    	$this->error($res['msg']);
	    }
	    
	}
}