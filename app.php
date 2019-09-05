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
 * +----------------------------------------------------------------------
 * |                                            ——马金虎
 * +----------------------------------------------------------------------
 */

$sanTongUid = array(1, 2, 8);
if (strtolower($_GET['c']) == "result") {
    if (!in_array($_POST['uid'], $sanTongUid)) {
        define('BIND_CONTROLLER', 'Work');
    }
}

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';