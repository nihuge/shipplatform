<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 登陆
 * */
class LoginController extends Controller
{
	/**
	 * 登陆
	 */
	public function login()
	{
	    if(IS_POST)
	    {
	    	//判断用户名不能含有特殊字符
			$res_s = judgeOneString( I('post.title') );
			if ($res_s == true) {
    			$this->error('数据不能含有特殊字符');
    			exit;
    		}
			//根据用户名与密码匹配查询
        	$where = array(
					'title'    =>   I('post.title'),
					'pwd'      =>   encrypt(I('post.pwd'))
			);
			$user = new \Common\Model\UserModel();
			$arr = $user
					->field('id,imei,firmid,pid,status')
					->where($where)
					->find();
			if($arr != '')
			{
				//判断用户状态、公司是否到期
				$msg = $user->is_judge($arr['id']);
				if($msg['code'] == '1')
				{
					session('uid',$arr['id']);
					session('pid',$arr['pid']);
					// session('imei',$arr['imei']);
					$this->success('登陆成功',U('Index/index'));
				}else{
					//状态/到期返回
					$msg = "公司到期或者用户被禁止，请联系管理员!(".$msg['code'].")";
					$this->error($msg);
					// p($msg);
				}
			}else{
				//用户名或密码错误
				$this->error('用户名或密码错误!');
			}
	    }else{
			$this->display();
	    }
	}

	/**
	 * 注册
	 */
	public function regist()
	{
	    if (IS_POST) {
	    	// P(I('post.'));
	    	// 判断提交的字符是否有特殊字符
    		$res = judgeOneString( I('post.firmname') );
    		if ($res == true) {
    			$this->error('公司名称不能含有特殊字符');
    			exit;
    		}
    		$res = judgeOneString( I('post.people') );
    		if ($res == true) {
    			$this->error('联系人不能含有特殊字符');
    			exit;
    		}
    		$res = judgeOneString( I('post.phone') );
    		if ($res == true) {
    			$this->error('联系电话不能含有特殊字符');
    			exit;
    		}
    		$res = judgeOneString( I('post.title') );
    		if ($res == true) {
    			$this->error('管理账号不能含有特殊字符');
    			exit;
    		}
    		// 判断公司名称是否存在
	    	$firm = new \Common\Model\FirmModel();
	    	$firmname = trimall(I('post.firmname'));
	    	$count = $firm->where(array('firmname'=>$firmname))->count();
	    	if ($count > 0) {
	    		$this->error('公司名称已经存在！');
	    		exit;
	    	}
    		//判断账号不否存在
	    	$user = new \Common\Model\UserModel();
	    	$title = trimall(I('post.title'));
			$count = $user->where(array('title'=>$title))->count();
	    	if ($count > 0) {
	    		$this->error('管理账号已经存在！');
	    		exit;
	    	}
	    	M()->startTrans();   // 开启事务
	    	// 新增公司
	    	$firm_data = array(
	    		'firmname'		=>  $firmname,
	    		'firmtype'		=>  I('post.firmtype'),
	    		'people'		=>  I('post.people'),
	    		'phone'			=>  I('post.phone'),
	    		'expire_time'	=>	strtotime("+1 week"),
	    		'membertype'	=>  '1',
	    		'creditline'	=>  '0',
	    		'service'		=>  '0',
	    		'balance'		=>  '1',
	    		'limit'			=>  '6',
	    		'number'		=>	'试用',
	    		);
	    	$firmid = $firm->addData($firm_data);
	    	// 修改公司操作的公司权限
	    	$map = array('id'=>$firmid);
	    	$data = array('firm_jur'=>$firmid);
	    	$res = $firm->editData($map,$data);

	    	// 新增管理人员
	    	$user_data = array(
	    		'title'			=>	I('post.title'),
	    		'username'		=>	I('post.people'),
	    		'pwd'   		=>	encrypt(I('post.pwd')),
	    		'firmid'		=>	$firmid,
	    		'phone'			=>  I('post.phone'),
	    		'pid'			=>  '0'
	    		);
	    	$uid = $user->addData($user_data);
	    	if ($firmid !==false and $uid!==false and $res !==false) {
	    		M()->commit();
	    		$this->success('注册成功',U('login'));
	    	} else {
	    		M()->rollback();
	    		$this->error('注册失败');
	    	}
	    	

	    } else {
	    	// echo "<script>alert('新增公司仅有7天的试用期，试用期间不限制次数，如有合作的机会请联系客服！')</script>";
	    	 $this->display();
	    }
	    
	   
	}
	
}