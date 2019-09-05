<?php
namespace Common\Model;
use Common\Model\BaseModel;

/*
 * 用户组管理类
 * 2018.4.24
 */
class AuthGroupModel extends BaseModel
{
	// 自动验证
    protected $_validate=array(
        array('title','require','用户组名不能为空！',self::EXISTS_VALIDATE),          //存在即验证，不能为空
        array('title','','用户组已经存在！',1,'unique',3),
        array('rules','0,990','权限长度不能超过990个字符',0,'length'),//存在即验证 长度不能超过12个字符
    );

	/*
	 * 获取用户组列表
	 * return array $privilegelist
	 */
	public function getauthgrouplist()
	{
		$authgrouplist = $this
		             ->order('id asc')->select();
		return $authgrouplist;
	}

	/**
	 * 判断用户组是否存在
	 * @param array $data 
	 * @return bool true/false
	 */
	public function vailgroup($data)
	{
		$title = trim($data);
		$title_arr = explode('、',$title);
		foreach($title_arr as $v)
		{
			$res  = $this->where("title='$v'")->count();
			if($res == 0)
			{
				return false;
				exit;
			}
		}
		return true;
	}
}