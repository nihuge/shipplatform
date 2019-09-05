<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
/**
 * 首页
 * 2018.4.24
 * */
class IndexController extends AdminBaseController 
{
	/**
	 * 首页
	 * */
    public function index(){
    	/**
         * 系统信息
         * */
        $server_info = array(
                '系统类型及版本号'=>php_uname(),     //系统类型及版本号
                'system'=>php_uname('s'),        //只获取系统类型
                '操作系统'=>PHP_OS,
                '服务器语言'=>$_SERVER['HTTP_ACCEPT_LANGUAGE'],
                '服务器Web端口'=>$_SERVER['SERVER_PORT'],
                'server_software'=>$_SERVER["SERVER_SOFTWARE"],  //运行环境
                'MYSQL版本' => mysql_get_client_info(),
                'sapi'=>php_sapi_name(),    //PHP运行方式
                'upload_max_filesize'=>ini_get('upload_max_filesize'),  //上传附件限制
                'max_execution_time'=>ini_get('max_execution_time').'秒',    //执行时间限制
                'server_time'=>date("Y年n月j日 H:i:s"),  //服务器时间
                'domain'=>$_SERVER['SERVER_NAME'].' [ '.gethostbyname($_SERVER['SERVER_NAME']).' ]', //服务器域名/IP
                'disk'=>round((disk_free_space(".")/(1024*1024)),2).'M',  //磁盘剩余空间
                'register_globals'=>get_cfg_var("register_globals")=="1" ? "ON" : "OFF",
                'magic_quotes_gpc'=>(1===get_magic_quotes_gpc())?'YES':'NO',
                'magic_quotes_runtime'=>(1===get_magic_quotes_runtime())?'YES':'NO',
        );
        $this->assign('info',$server_info);

        $this->display();
    }

    /**
     * 清理缓存
     * */
    public function cleancache()
    {
    	$dirName='./Runtime/';
    	delDirAndFile($dirName);
    	echo '1';
    }

    /**
     * 退出登录
     * */
    public function loginout()
    {
        $_SESSION ['adminuid'] = null;
        $this->redirect ( 'Login/login' );
    }
}