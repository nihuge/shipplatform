<?php


namespace Common\Model;

use Common\Model\BaseModel;

class AmendModel extends BaseModel
{
    // 自动验证
    protected $_validate=array(
        array('type','require','改动类型必填'),
        array('activity','require','动作必填'),
        array('table_name','require','表名必填'),
        array('uid','require','用户名必填'),
        array('date','require','修改时间必填'),
    );



    /**
     * 以array1为准自动对比两个数组相同键值部分的值有哪些不同，并且返回不同的差异数组
     * @param $array1 新数组
     * @param $array2 老数组
     * @return array  返回的差异数组
     */
    public function change_diff($array1,$array2){
        $array1_count = count($array1);
        $array2_count = count($array2);
        $array3 = array();
        if($array1_count <= $array2_count){
            foreach ($array1 as $k=>$value){
                if(isset($array2[$k])){
                    $array3[$k] = $array2[$k];
                }
            }
            $diff_arr = array_diff_assoc($array1, $array3);
        }else{
            foreach ($array2 as $k=>$value){
                if(isset($array1[$k])) {
                    $array3[$k] = $array1[$k];
                }
            }
            $diff_arr = array_diff_assoc($array3,$array2);
        }
        return $diff_arr;
    }
}