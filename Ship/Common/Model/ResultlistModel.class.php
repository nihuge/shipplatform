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

    /**
     * 获取作业中各舱的经验底量
     */
    public function get_base_volume_list($resultid){

        //获取配置项内的底量判断阈值,默认0.2
        $threshold = C('BASE_JUDGMENT_CRITERIA',null,0.2);
        $count_where = array(
            'resultid'=>$resultid,
            'is_work'=>array(array('NEQ',2),array('EXP','is null'),'or'),//不统计不作业的舱数据
            'ullage'=>array('ELT',$threshold),//只获取空高低于一定阈值的舱数据，算作底量
        );

        return  $this
            ->field('ullage,standardcapacity,cabinid,solt')
            ->where($count_where)
            ->select();
    }

    /**
     * 加入统计
     */


}