<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 统计model
 * */
class StatisticsModel extends BaseModel
{
	/**
	 * 自动验证
	 */ 
    protected $_validate=array(
        array('shipname','require','船名称不能为空',self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('pretend','require','装载不能为空',self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('discharge','require','卸载不能为空',self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('deliver','require','发货量不能为空',self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('status','require','盈亏不能为空',self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('voyage','require','航次不能为空',self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('shipname','1,12','船名称长度不能超过12个字符',0,'length'),//存在即验证 长度不能超过12个字符
        array('pretend','1,50','装载长度不能超过50个字符',0,'length'),//存在即验证 长度不能超过50个字符
        array('discharge','1,50','卸载长度不能超过50个字符',0,'length'),//存在即验证 长度不能超过50个字符
        array('deliver','1,50','发货量长度不能超过50个字符',0,'length'),//存在即验证 长度不能超过50个字符
        array('status','1,50','盈亏长度不能超过50个字符',0,'length'),//存在即验证 长度不能超过50个字符
        array('voyage','1,50','航次长度不能超过20个字符',0,'length'),//存在即验证 长度不能超过50个字符
    );
}