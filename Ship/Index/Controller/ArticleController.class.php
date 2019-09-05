<?php
namespace Index\Controller;
use Think\Controller;

class ArticleController extends Controller 
{
    // 定义数据表
    private $db;

    // 构造函数 实例化ArticleModel表
    public function __construct(){
        parent::__construct();
        $this->db = new \Common\Model\ArticleModel();
    }

    /**
     * 查看资讯信息
     */
    public function msg(){
        $aid=I('get.aid');
        $data=$this->db->getDataByAid($aid,1);
        $this->assign('data',$data);
        $this->display();
    }
}