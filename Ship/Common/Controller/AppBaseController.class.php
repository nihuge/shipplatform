<?php
namespace Common\Controller;

/**
 * 基类Controller
 */
class AppBaseController extends BaseController
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
    	parent::_initialize();
        // 返回码配置
        $this->ERROR_CODE_COMMON = json_decode(error_code_common,true);
        $this->ERROR_CODE_COMMON_ZH = json_decode(error_code_common_zh,true);
        $this->ERROR_CODE_USER = json_decode(error_code_user,true);
        $this->ERROR_CODE_USER_ZH = json_decode(error_code_user_zh,true);
        $this->ERROR_CODE_RESULT = json_decode(error_code_result,true);
        $this->ERROR_CODE_RESULT_ZH = json_decode(error_code_result_zh,true);

        /**
         * 设置转发路由，不同的oc_type字段引导到不同的控制器
         */
        $oc_type = I("param.oc_type",1);//获取oc_type，默认1
        
        switch ($oc_type){
            case 1:
                break;
            case 2:
        }
    }


}