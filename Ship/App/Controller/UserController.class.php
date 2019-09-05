<?php
namespace App\Controller;
use Common\Controller\AppBaseController;

/**
 * 用户管理类
 */
class UserController extends AppBaseController
{
	/**
	 * 用户登陆
	 * @param string title 账号
	 * @param string pwd 密码
	 * @param string imei 标识
	 * @return array
	 * @return @param code:返回码
	 * @return @param content:内容、说明
	 * */
	public function login()
	{
		if(I('post.title') and I('post.pwd') and I('post.imei'))
		{
			$user = new \Common\Model\UserModel();
			//登陆操作
			$res = $user->login(I('post.title'), I('post.pwd'),I('post.imei'));
		}else{
			//参数不正确，参数缺失	5
			$res = array(
					'code'   => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
			);
		}
		echo jsonreturn($res);
	}

	/**
	 * 修改密码
	 * @param int uid 用户ID
	 * @param string oldpwd 旧密码
	 * @param string newpwd 新密码
	 * @param string repeatpwd 确认密码
	 * @param string imei 标识
	 * @return array
	 * @return @param code:返回码
	 * @return @param content:内容、说明
	 * */
	public function changepwd()
	{
	    if( I('post.oldpwd') and I('post.newpwd') and I('post.repeatpwd') and I('post.uid') and I('post.imei'))
	    {
	    	$user = new \Common\Model\UserModel();
	    	// $res = I('post.');
	    	//修改密码操作
	    	$res = $user->changePwd(I('post.uid'),I('post.oldpwd'),I('post.newpwd'),I('post.repeatpwd'),I('post.imei'));
	  	}else{
	  		//参数不正确，参数缺失	5
			$res = array(
					'code'   => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
			);
	    }
	    echo jsonreturn($res);
	}
}