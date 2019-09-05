<?php
namespace Common\Model;
use Common\Model\BaseModel;
// use Think\Model;

/**
 * 管理model
 * 2018.4.24
 * */
class AdminModel extends BaseModel
{
	/**
	 * 自动验证
	 */ 
    protected $_validate=array(
        array('title','','账号已经存在！',1,'unique',3),
        array('name','1,15','用户名长度不能超过15个字符',0,'length'),//存在即验证 长度不能超过12个字符
        array('title','1,15','用户名长度不能超过15个字符',0,'length'),//存在即验证 长度不能超过12个字符
        array('phone','1,14','联系电话长度不能超过14个字符',0,'length'),//存在即验证 长度不能超过12个字符
        array('title','require','账号不能为空',0),//存在即验证 不能为空
        array('name','require','用户名不能为空',0),//存在即验证 不能为空
        array('phone','require','电话不能为空',0),//存在即验证 不能为空
    );

    /**
     * 管理员登陆
     * */
    public function login($title,$pwd)
    {
        //根据用户名与密码匹配查询
        $where = array(
                'title'    =>   $title,
                'pwd'      =>   encrypt($pwd)
        );
        $arr = $this
                ->field('id,status')
                ->where($where)
                ->find();
        if($arr != '')
        {
            // 判断用户状态
            if($arr['status'] == '1')
            {
                //成功 1
                $res = array(
                        'code'  =>   $this->ERROR_CODE_COMMON['SUCCESS'],
                        'msg'   =>   $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']],
                        'content'=>  $arr['id']
                );
            }else{
                // 该用户被冻结    1004
                $res = array(
                        'code'  =>   $this->ERROR_CODE_USER['USER_FROZEN'],
                        'msg'   =>   $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_FROZEN']]
                );
            }
        }else{
            // 用户名或密码错误  1001
            $res = array(
                    'code'  =>   $this->ERROR_CODE_USER['USER_NOT_EXIST'],
                    'msg'   =>   $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_NOT_EXIST']]

            );
        }
        return $res;
    }

    /**
     * 判断管理状态
     * @param int id 管理ID
     * @return array
     * @return @param code 返回码
     * */
    public function is_judge($id)
    {
        // 判断标识是否一致
        $msg = $this   
                ->field('status')
                ->where(array('id'=>$id))
                ->find();
        if($msg['status'] == '1')
        {
            // 成功 1
            $res = array(
                    'code'  =>   $this->ERROR_CODE_COMMON['SUCCESS'],
                    'msg'   =>   $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']]
            );
        }else{
            // 该用户被冻结    1004
            $res = array(
                    'code'  =>   $this->ERROR_CODE_USER['USER_FROZEN'],
                    'msg'   =>   $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_FROZEN']]
            );
        }
        return $res;
    }
}