<?php
namespace Home\Controller;

use Common\Controller\HomeBaseController;
class IndexController extends HomeBaseController 
{
    /**
     * 首页
     * */
    public function index()
    {
        $this->display();
    }

    /**
	 * 退出登录
	 * */
	public function loginout()
	{
		$_SESSION ['uid'] = null;
		$this->redirect ( 'Login/login' );
	}

    /**
     * 修改密码
     * */
    public function changepwd()
    {
        if(I('post.oldpwd') and I('post.newpwd') and I('post.repeatpwd')){
            $user = new \Common\Model\UserModel();
            $oldpwd = I('post.oldpwd');
            $newpwd = I('post.newpwd');
            $repeatpwd = I('post.repeatpwd');
            //判断新密码与重置密码是否一样
            if($newpwd == $repeatpwd){
                $uid = $_SESSION['uid'];
                //判断并进行修改密码
                // 检验原密码是否正确 
                $msg=$user
                    ->field('pwd')
                    ->where( array('id'=>$uid) )
                    ->find();
                if($msg != '')
                {
                    //判断原始密码对否正确
                    $pwdold=encrypt($oldpwd);
                    if($pwdold == $msg['pwd'])
                    {
                        $newpwd = trim($newpwd);
                        //修改密码
                        $data=array(
                                'pwd' => encrypt($newpwd)
                        );
                        $res1=$user->where(array('id'=>$uid))->save($data);
                        if($res1 !== false)
                        {
                            // 成功    1
                            echo '<script>alert("修改密码成功!");top.location.reload(true);window.close();</script>';
                        }else {
                            // 数据库操作错误  3
                            $this->error('数据库操作错误');
                        }
                    }else{
                        // 原始密码不正确  1003
                        $this->error('原始密码不正确');
                    }
                }else {
                    // 该用户不存在  1006
                    $this->error('该用户不存在');
                }
            }else{
                $this->error('新密码与确认密码不一致');
            }
        }else{
            $this->display();
        }
    }
}