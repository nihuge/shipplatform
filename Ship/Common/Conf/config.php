<?php
return array(
	//'配置项'=>'配置值'
	//*************************************附加设置***********************************
   
    'IMAGE_TITLE_ALT_WORD'      =>  '计量平台',                 //图片默认title和alt //
    'SHOW_PAGE_TRACE'        =>  false,                           // 是否显示调试面板
    'LOG_RECORD'             =>   true,                           //开启了日志记录
    'TMPL_PARSE_STRING'      => array(                // 定义常用路径
        '__PUBLIC__'           => __ROOT__.'/Public',
        '__ADMIN_CSS__'        => __ROOT__.'/Public/Admin/css',
        '__ADMIN_JS__'         => __ROOT__.'/Public/Admin/js',
        '__ADMIN_IMAGES__'     => __ROOT__.'/Public/Admin/images',
        '__ADMIN_FONTS__'      => __ROOT__.'/Public/Admin/fonts',
        '__ADMIN_AVATARS__'    => __ROOT__.'/Public/Admin/avatars',
        '__ADMIN_STATIC__'     => __ROOT__.'/Public/Admin/static',
        // '__HOME_CSS__'         => __ROOT__.'/Public/home/css',
        // '__HOME_JS__'          => __ROOT__.'/Public/home/js',
        // '__HOME_IMG__'         => __ROOT__.'/Public/home/img',
        '__INDEX_CSS__'              =>  __ROOT__.trim(TMPL_PATH,'.').'Index/Public/css',
        '__INDEX_JS__'               =>  __ROOT__.trim(TMPL_PATH,'.').'Index/Public/js',
        '__INDEX_IMAGE__'            =>  __ROOT__.trim(TMPL_PATH,'.').'Index/Public/image',
        '__INDEX_STATIC__'            =>  __ROOT__.trim(TMPL_PATH,'.').'Index/Public/static',
    ),
    //**************************URL设置***********************************
    //'MODULE_ALLOW_LIST'      =>  array('Index','Admin'), //允许访问列表
    'URL_HTML_SUFFIX'        =>  '',  // URL伪静态后缀设置
    'URL_MODEL'              =>  3,   //启用rewrite 
    'URL_CASE_INSENSITIVE'   =>   true,                           //url不区分大小写
    //**************************数据库设置***********************************
    'URL_MODEL' =>3,
        'DB_TYPE'   =>'mysqli',
        'DB_HOST'   =>'127.0.0.1',
//        'DB_NAME'   =>'shipplatform2',  // 数据库名
        'DB_NAME'   =>'shiplatform_online',  // 数据库名
        'DB_USER'   =>'root',  //账号
        'DB_PWD'    =>'root', //密码
        // 'DB_NAME'   =>'shipplatform',  // 数据库名
        // 'DB_USER'   =>'ship_platform',  //账号
        // 'DB_PWD'    =>'ship_user_2018', //密码
        'DB_PORT'   =>'3306',
        'DB_CHARSET' => 'utf8',
        'DB_PREFIX' =>'', //数据表前缀
        'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志
        
    /***********************页面设置****************************/
    //自定义success和error的提示页面模板
    'TMPL_ACTION_SUCCESS'=>'./tpl/default/Index/Public/dis_success.html', 
    'TMPL_ACTION_ERROR'=>'./tpl/default/Index/Public/dis_error.html',

    /**************************计量设置*******************************/
    'BASE_JUDGMENT_CRITERIA'=>0.2    //系统判断底量的阈值标准
);