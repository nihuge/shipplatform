<?php
namespace Common\Model;
use Common\Model\BaseModel;

/*
 * 用户、群组关联管理类
 * 2018.4.24
 */
class AuthGroupAccessModel extends BaseModel
{
	/**
	 * 获取管理员权限列表
	 */
	public function getAllData(){
		$data=$this
			->field('u.*,aga.group_id,ag.title')
			->alias('aga')
			->join('__USER__ u ON aga.uid=u.id','RIGHT')
			->join('__AUTH_GROUP__ ag ON aga.group_id=ag.id','LEFT')
			->select();
		// 获取第一条数据
		$first=$data[0];
		$first['title']=array();
		$user_data[$first['id']]=$first;
		// 组合数组
		foreach ($data as $k => $v) {
			foreach ($user_data as $m => $n) {
				$uids=array_map(function($a){return $a['id'];}, $user_data);
				if (!in_array($v['id'], $uids)) {
					$v['title']=array();
					$user_data[$v['id']]=$v;
				}
			}
		}
		// 组合管理员title数组
		foreach ($user_data as $k => $v) {
			foreach ($data as $m => $n) {
				if ($n['id']==$k) {
					$user_data[$k]['title'][]=$n['title'];
				}
			}
			$user_data[$k]['title']=implode('、', $user_data[$k]['title']);
		}
		// 管理组title数组用顿号连接
		return $user_data;

	}

	/**
	 * 根据用户的ID获取用户组名称
	 * @param string $id 用户ID
	 * @return string grouptitle 用户组名称
	 * @
	 */
	public function getgrouptitle($id)
	{
		$data = $this
			->alias('ga')
			->field("g.title")
			->where("ga.uid='$id'")
			->join("left join auth_group g on g.id=ga.group_id")
			->select();
		if($data)
		{
			// 组合管理员title数组
			$grouptitle = "";
			foreach($data as $v)
			{
				$grouptitle .=  $v['title'].'、';
			}
			return substr($grouptitle,0,-3);
		}else{
			$grouptitle = "";
			return $grouptitle;
		}
	}

	/**
	* 根据用户组的ID获取所有的用户信息与数量
	* @param string $id 用户组ID
	* @return array 用户信息
	*/
	public function getuserlist($id)
	{
		$data = $this
			->field("a.title,a.name,a.id,a.phone")
			->alias('ga')
			->where("ga.group_id='$id'")
			->join("left join admin a on a.id=ga.uid")
			->order('a.id asc')
			->select();
		if($data)
		{
			return $data;
		}else{
			return false;
		}
	}
}