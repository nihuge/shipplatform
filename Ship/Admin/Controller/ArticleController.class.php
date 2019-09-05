<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
/**
 * 资讯管理
 * */
class ArticleController extends AdminBaseController 
{
	// 定义数据表
    private $db;

    // 构造函数 实例化ArticleModel表
    public function __construct(){
        parent::__construct();
        $this->db = new \Common\Model\ArticleModel();
    }

	//文章列表
    public function index(){
    	if($_GET['p'])
		{
			$p=$_GET['p'];
		}else {
			$p=1;
		}

        $data=$this->db->getPageData('all',$p);
        $this->assign('data',$data['data']);
        $this->assign('page',$data['page']);
        $this->display();
    }

    // 添加文章
    public function add(){
        if(IS_POST){
            if($aid=$this->db->addData()){
                $this->success('文章添加成功',U('Article/index'));
            }else{
                $this->error($this->db->getError());
            }
        }else{
            $this->display();
        }
    }

    // 修改文章
    public function edit(){
        if(IS_POST){
            if($this->db->editData()){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $aid=I('get.aid');
            $data=$this->db->getDataByAid($aid);
            $this->assign('data',$data);
            $this->display();
        }
    }

    // 彻底删除
    public function del(){
        if($this->db->deleteData()){
            echo ajaxReturn(array("state" => 1, 'msg' => "成功"));
        }else{
            echo ajaxReturn(array("state" => 2, 'msg' => "失败"));
        }
    }
}