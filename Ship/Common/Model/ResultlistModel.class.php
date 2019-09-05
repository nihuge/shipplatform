<?php
namespace Common\Model;
use Common\Model\BaseModel;
/**
 * 舱计算数据
 */
class ResultlistModel extends BaseModel
{
	/**
     * 获取列表
     * */
    public function getlist($resultid){
        $list = $this
            ->field('r.*,c.cabinname')
            ->alias('r')
            ->join('left join cabin c on c.id = r.cabinid')
            ->where(array('resultid'=>$resultid))
            ->select();
        $result = '';
        foreach ($list as $k => $v) {
            $result[$v['cabinid']][]    =   $v;
        }
        return $result;
    }
}