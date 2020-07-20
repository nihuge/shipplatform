<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);


// 允许APP端AJAX跨域请求
$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';

$allowOrigin = array(
    'https://www.ciplat.com',
    'https://api.ciplat.com',
    'https://www.newcip.com',
    'https://wxship.xzitc.com',
    'https://wxshiptest.xzitc.com',
    'http://10.0.2.33:8080',
);

if (in_array($origin, $allowOrigin)) {
    header("Access-Control-Allow-Origin:".$origin);
}

header("Access-Control-Allow-credentials: true");
header("Access-Control-Allow-Headers: content-type,x-requested-with,Authorization, x-ui-request,lang,Access-Control-Allow-Origin,Access-Control-Allow-credentials");

// 定义应用目录
define('APP_PATH','./Ship/');

// 引入返回码配置文件
require_once './Public/inc/code.config.php';

// 定义缓存目录
define('RUNTIME_PATH','./Runtime/');

// 定义模板主题
define("DEFAULT_THEME","default");

// 定义模板文件默认目录
define("TMPL_PATH","./tpl/".DEFAULT_THEME."/");

//定义访问的模块
define("BIND_MODULE", "Index");

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单