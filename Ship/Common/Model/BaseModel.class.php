<?php
namespace Common\Model;
use Think\Model;

/**
 * 基础model
 * 2018.4.24
 * */
class BaseModel extends Model
{
	public $ERROR_CODE_COMMON =array();         // 公共返回码
    public $ERROR_CODE_COMMON_ZH =array();      // 公共返回码中文描述
    public $ERROR_CODE_USER =array();           // 用户相关返回码
    public $ERROR_CODE_USER_ZH =array();        // 用户相关返回码中文描述
    public $ERROR_CODE_RESULT =array();         // 作业相关返回码
    public $ERROR_CODE_RESULT_ZH =array();      // 作业相关返回码中文描述

    /**
     * 初始化方法
     */
    public function _initialize(){
        // 返回码配置
        $this->ERROR_CODE_COMMON = json_decode(error_code_common,true);
        $this->ERROR_CODE_COMMON_ZH = json_decode(error_code_common_zh,true);
        $this->ERROR_CODE_USER = json_decode(error_code_user,true);
        $this->ERROR_CODE_USER_ZH = json_decode(error_code_user_zh,true);
        $this->ERROR_CODE_RESULT = json_decode(error_code_result,true);
        $this->ERROR_CODE_RESULT_ZH = json_decode(error_code_result_zh,true);
    }

	/**
     * 添加数据
     * @param  array $data  添加的数据
     * @return int          新增的数据id
     */
    public function addData($data){
        // 去除键值首尾的空格
        foreach ($data as $k => $v) {
            $data[$k]=trimall($v);
        }
        $id=$this->add($data);
        return $id;
    }

    /**
     * 修改数据
     * @param   array   $map    where语句数组形式
     * @param   array   $data   数据
     * @return  boolean         操作是否成功
     */
    public function editData($map,$data){
        // 去除键值首位空格
        foreach ($data as $k => $v) {
            $data[$k]=trimall($v);
        }
        $result=$this->where($map)->save($data);
        return $result;
    }

    /**
     * 删除数据
     * @param   array   $map    where语句数组形式
     * @return  boolean         操作是否成功
     */
    public function deleteData($map){
        if (empty($map)) {
            die('where为空的危险操作');
        }
        $result=$this->where($map)->delete();
        return $result;
    }
    
    /**
     * 获取全部数据
     * @param  string $type  tree获取树形结构 level获取层级结构
     * @param  string $order 排序方式   
     * @return array         结构数据
     */
    public function getTreeData($type='tree',$order='',$name='name',$child='id',$parent='pid'){
        // 判断是否需要排序
        if(empty($order)){
            $data=$this->select();
        }else{
            $data=$this->order($order.' is null,'.$order)->select();
        }
        // 获取树形或者结构数据
        if($type=='tree'){
            $data=\Org\Nx\Data::tree($data,$name,$child,$parent);
        }elseif($type="level"){
            $data=\Org\Nx\Data::channelLevel($data,0,'&nbsp;',$child);
        }
        return $data;
    }

    /*
     * 判断数据是否存在
     * @param string $name
     * return array $privilegelist
     */
    public function valiname($name,$tbzname)
    {
        $count = $this
              ->where("$tbzname='$name'")
              ->count();
        if($count!=0){
            return false;
        }else{
            return true;
        }
    }
    
    /**
     * 数据排序
     * @param  array $data   数据源
     * @param  string $id    主键
     * @param  string $order 排序字段   
     * @return boolean       操作是否成功
     */
    public function orderData($data,$id='id',$order='order_number'){
        foreach ($data as $k => $v) {
            $v=empty($v) ? null : $v;
            $this->where(array($id=>$k))->save(array($order=>$v));
        }
        return true;
    }
}