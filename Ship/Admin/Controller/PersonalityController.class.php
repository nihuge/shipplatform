<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
/**
 * 个性化字段管理
 * 2018.5.14
 */
class PersonalityController extends AdminBaseController
{
	// 定义数据
    private $db;

    public function __construct(){
        parent::__construct();
        $this->db = new \Common\Model\PersonalityModel();
    }
	/**
	 * 个性胡列表
	 */
	public function index()
	{
		$count = $this->db->count();
		$per = 24;
		if(isset($_GET['p']))
		{
			$p=$_GET['p'];
		}else {
			$p=1;
		}
		//分页
	    $page = fenye($count,$per);
	    $begin=($p-1)*$per;
	    $data = $this->db->limit($begin,$per)->select();
	    $assign = array(
	    	'data'  =>  $data,
	    	'page'	=>  $page
	    	);
	    $this->assign($assign);
	    $this->display(); 
	}

	/**
	 * 新增
	 */
	public function add()
	{
	    $data=I('post.');
		// 对数据进行验证
    	if (!$this->db->create($data))
    	{
		    // 如果创建失败 表示验证没有通过 输出错误提示信息
		    $this->error($this->db->getError());
		}else{
			$result = $this->db->addData($data);
			if ($result) {
				$this->success('添加成功',U('Personality/index'));
			}else{
				$this->error('添加失败');
			}
		}

	}

	/**
	 * 修改
	 */
	public function edit()
	{
	    $data = I('post.');
	    if (!$this->db->create($data))
    	{
		    // 如果创建失败 表示验证没有通过 输出错误提示信息
		    $this->error($this->db->getError());
		}else{
			$map = array(
				'id'	=>	$data['id']
				);
			$result = $this->db->editData($map,$data);
			if ($result) {
				$this->success('修改成功',U('Personality/index'));
			}else{
				$this->error('修改失败');
			}
		}
	}

}