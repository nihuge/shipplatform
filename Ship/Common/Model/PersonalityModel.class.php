<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 个性化管理Model
 * */
class PersonalityModel extends BaseModel
{
	/**
	 * 自动验证
	 */ 
    protected $_validate=array(
        array('name', '', '个性化英文名称已经存在！', 1, 'unique', 3), 
        // 不能为空
        array('name','require','个性化英文名称不能为空',0),// 必须验证 不能为空
        array('title','require','个性化中文名称不能为空',0),// 必须验证 不能为空
        // 长度判断
        array('name','1,25','个性化英文名称长度不能超过25个字符',0,'length'),// 必须验证
        array('title','1,25','个性化中文名称长度不能超过25个字符',0,'length'),//必须验证 
        // 特殊字符
        array('name','is_preg_match','个性化英文名称不能含有特殊字符',0,'function'),//存在即验证
        array('title','is_preg_match','个性化中文名称不能含有特殊字符',0,'function'),//存在即验证

    );  
}