<?php
// 应用入口文件

// 设置页面编码
header("Content-type:text/html;charset=utf-8");
//设置时区
date_default_timezone_set('Asia/Shanghai');
// 允许APP端AJAX跨域请求
header("Access-Control-Allow-Origin: *");
// 引入返回码配置文件
require_once './Public/inc/code.config.php';

// 检测PHP环境
if (version_compare(PHP_VERSION, '5.3.0', '<')) die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', True);

// 定义应用目录
define('APP_PATH', './Ship/');

// 定义缓存目录
define('RUNTIME_PATH', './Runtime/');


define('BIND_MODULE', 'App');

//file_put_contents("log.txt",json_encode($_GET),FILE_APPEND);

/**
 * +----------------------------------------------------------------------
 * | 此处判断提交过来的用户是否是三通公司的用户，如果不是三通公司则跳转到平台
 * | 业务控制器。
 * |
 * | 如果给三通公司新增操作员请在sanTongUid数组内增加该操作员的UID
 * |
 * |
 * | 还要判断传输过来的业务类型，油船用OilWork控制器
 * | $oc_type
 * | 1：沥青
 * | 2：原油
 * | 3：石油产品
 * | 4：润滑油
 * |
 * +----------------------------------------------------------------------
 * |                                            ——马金虎
 * +----------------------------------------------------------------------
 */

$sanTongUid = array(1, 2, 8);
if (!in_array($_POST['uid'], $sanTongUid)) {
    /**
     * $oc_type
     * 1:沥青
     * 2：原油
     * 3：石油产品
     * 4：润滑油
     */
    $oc_type = isset($_GET['oc_type'])?$_GET['oc_type']:1;
    $oc_type = $oc_type<1?1:$oc_type;
//    exit($oc_type);
    if($oc_type ==2){
        if(strtolower($_GET['c']) == "result"||strtolower($_GET['c']) == "work"){
            define('BIND_CONTROLLER', 'OilWork');
        }
    }elseif($oc_type ==1){
        if (strtolower($_GET['c']) == "result") {
            define('BIND_CONTROLLER', 'Work');
        }
    }
}


// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';