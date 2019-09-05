<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 权限管理类
 * 2018.4.24
 */
class AuthRuleModel extends BaseModel
{
	// 自动验证
    protected $_validate=array(
        array('name', '', '权限英文名称已经存在！', 1, 'unique', 3), // 新增修改时候验证name字段是否唯一
    );

	/*
	 * 获取权限树桩列表
	 * return array $privilegelist
	 */
	public function getauthrulelist()
	{
		$authrulelist = $this
		             ->getTreeData('tree','id','title');
		return $authrulelist;
	}

	/**
	 * 删除数据
	 * @param	array	$map	where语句数组形式
	 * @return	boolean			操作是否成功
	 */
	public function deleteData($map){
		$count=$this
			->where(array('pid'=>$map['id']))
			->count();
		if($count!=0){
			return false;
		}
		$this->where(array($map))->delete();
		return true;
	}
}