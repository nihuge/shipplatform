<?php
namespace Index\Controller;
use Common\Controller\IndexBaseController;

class CabinController extends IndexBaseController 
{
	// 定义数据表
    private $db;

    // 构造函数 实例化ShipModel表
    public function __construct(){
        parent::__construct();
        $this->db = new \Common\Model\CabinModel();
    }

	/**
	 * 船舱
	 */
	public function index()
	{
	    $where[] = '1';
		if (I('get.shipid')) {
			$where['c.shipid'] = I('get.shipid');
		}

	    $count = $this->db
	    		->alias('c')
	    		->field('s.shipname')
	    		->where($where)
	    		->count();
	    // 分页
		$page=new \Org\Nx\Page($count,20);

	    $data = $this->db
	    	->field('c.id,c.cabinname,c.altitudeheight,c.bottom_volume,c.pipe_line,c.shipid,s.shipname,s.tankcapacityshipid,s.rongliang,s.rongliang_1,s.zx,s.zx_1')
	    	->alias('c')
	    	->join('left join ship s on s.id=c.shipid')
	    	->where($where)
	    	->order('c.shipid asc,c.id asc')
	    	->limit($page->firstRow,$page->listRows)
	    	->select();
	    //获取船列表
	    $ship = new \Common\Model\ShipModel();
	    $shiplist = $ship
	    			->field('id,shipname')
	    			->order("shipname desc")
	    			->select();
	    $assign=array(
            'data'=>$data,
            'page'=>$page->show(),
            'shiplist'=>$shiplist
        );
        $this->assign($assign);
	    $this->display();
	}

	/**
	 * 获取舱数据
	 */
	public function cabinmsg()
	{
	    // 船舱数据信息
    	$cabinmsg = $this->db->where(array('id'=>$_POST['id']))->find();

    	$firm = new \Common\Model\FirmModel();
    	$user = new \Common\Model\UserModel();
        $uid = $_SESSION['user_info']['id'];
		$usermsg = $user
					->where(array('id'=>$uid))
					->find();
    	$firmmsg = $firm
    					->where(array('id'=>$usermsg['firmid']))
    					->find();
    	$operation_jur = explode(',',$firmmsg['operation_jur']);
    	$ship = new \Common\Model\ShipModel();
    	$where = array(
	    		'id'	=>  array('in',$operation_jur)
	    		);
    	$shiplist = $ship
	    				->where($where)
	    	            ->order('id desc')
	    				->select();
	    $is_diliang = $ship->getFieldById($cabinmsg ['shipid'],'is_diliang');
	    if ($is_diliang == '1') {
	    	$dis = "";
	    } else {
	    	$dis = "style='display: none;'";
	    }
	    

    	$string = "<div class='bar'>修改船舱</div><div class='bar1'>基本信息</div><input type='hidden' name='cabinid' id='cabinid' value='".$cabinmsg['id']."'><ul class='pass'><li><label>所属船舶</label><p><select name='shipidd' id='shipiddd'><option value=''>请选择所属船舶</option>";
        foreach ($shiplist as $k => $v) {
        	if ($cabinmsg['shipid'] == $v['id']) {
        		$select = 'selected';
        	} else {
        		$select = '';
        	}
        	
        	$string .= "<option value='".$v['id']."' ".$select.">".$v['shipname']."</option>";
        }

        $string .= "</select></p></li><li><label>舱&nbsp;名</label><p><input type='text' name='cabinname' placeholder='请输入舱名' class='i-box' id='cabinname' maxlength='12' value='".$cabinmsg['cabinname']."'></p></li><li><label>管线数量</label><p><input type='text' name='pipe_line' placeholder='请输入管线数量' class='i-box' id='pipe_line' maxlength='5' value='".$cabinmsg['pipe_line']."'></p></li></ul><div class='bar1'>容量表信息</div><ul class='pass'><li><label>基准高度</label><p><input type='text' name='altitudeheight' placeholder='请输入基准高度' class='i-box' id='altitudeheight' maxlength='5' value='".$cabinmsg['altitudeheight']."'></p></li> <li><label>底&nbsp;量</label><p><input type='text' name='bottom_volume' placeholder='请输入底量' class='i-box' id='bottom_volume' maxlength='5' value='".$cabinmsg['bottom_volume']."'></p></li></ul><div ".$dis." id='hiden'><div class='bar1'>底量表信息</div><ul class='pass'><li><label>基准高度</label><p><input type='text' name='dialtitudeheight' placeholder='请输入基准高度' class='i-box' id='dialtitudeheight' maxlength='5' value='".$cabinmsg['dialtitudeheight']."'></p></li> <li><label>底&nbsp;量</label><p><input type='text' name='bottom_volume_di' placeholder='请输入底量' class='i-box' id='bottom_volume_di' maxlength='5' value='".$cabinmsg['bottom_volume_di']."'></p></li></ul></div><div class='bar'><input type='submit' value='取&nbsp;消' class='mmqx passbtn'><input type='submit' onclick='addc()' value='提&nbsp;交' class='mmqd passbtn'>  </div>";
        echo ajaxReturn(array("state" => 1, 'message' => "成功",'content'=>$string));
	}

	/**
	 * 舱修改
	 */
	public function edit()
	{
		if(I('post.shipid') and I('post.id') and I('post.cabinname') and I('post.pipe_line') and I('post.altitudeheight') and I('post.bottom_volume')){
			// 判断是否超过船舶限制舱总数
//			$ship = new \Common\Model\ShipModel();
//			$cabinnum = $ship->getFieldById(I('post.shipid'),'cabinnum');
//			$count = $this->db->where(array('shipid'=>I('post.shipid')))->count();
//			if ($count+1 > $cabinnum  ) {
//				echo ajaxReturn(array("state" => 2, 'message' => '超过该船限制总舱数'));
//			} else {
		    	//判断同一条船不能有重复的舱名
		    	$where = array(
		    		'shipid'	=> I('post.shipid'),
		    		'cabinname' => I('post.cabinname'),
		    		'id'		=> array('NEQ',I('post.id'))
		    	);
		    	$count = $this->db->where($where)->count();
		    	if ($count>0) {
		    		echo ajaxReturn(array("state" => 2, 'message' => '该船已存在该舱名'));
		    	}else{
			    	$data = I('post.');
			        // 对数据进行验证
			    	if (!$this->db->create($data)){
					    // 如果创建失败 表示验证没有通过 输出错误提示信息
					    echo ajaxReturn(array("state" => 2, 'message' => $this->db->getError()));
					}else{
					    // 验证通过 可以进行其他数据操作
						$map = array(
							'id'	=>   $data['id']
							);
						unset($data['id']);
					    $res = $this->db->editData($map,$data);
					    if ($res !== false) {
					    	echo ajaxReturn(array("state" => 1, 'message' => "修改成功"));
					    } else {
					    	echo ajaxReturn(array("state" => 2, 'message' => "修改失败"));
					    }
					}	    		
		    	}				
//			}
	    }else{
			echo ajaxReturn(array("state" => 2, 'message' => "表单不能存在空值"));
        }
	}

	/**
	 * 舱新增
	 */
	public function add()
	{
	    if(I('post.shipid') and I('post.cabinname') and I('post.pipe_line') and I('post.altitudeheight') and I('post.bottom_volume')){
			$ship = new \Common\Model\ShipModel();
			$cabinnum = $ship->getFieldById(I('post.shipid'),'cabinnum');
            $count1 = $this->db->where(array('shipid' => I('post.shipid')))->count();
            if ($count1 + 1 > $cabinnum) {
                echo ajaxReturn(array("state" => 2, 'message' => '超过该船限制总舱数'));
            } else {
                //判断同一条船不能有重复的舱名
                $where = array(
                    'shipid' => I('post.shipid'),
                    'cabinname' => I('post.cabinname')
                );

                $cabin = new \Common\Model\CabinModel();
                $count = $cabin->where($where)->count();
                if ($count > 0) {
                    echo ajaxReturn(array("state" => 2, 'message' => "该船已存在该舱名"));
                } else {
                    // 去除键值首位空格
                    $data = I('post.');
                    // 对数据进行验证
                    if (!$this->db->create($data)) {
                        // 如果创建失败 表示验证没有通过 输出错误提示信息
                        // $this->error($cabin->getError());
                        echo ajaxReturn(array("state" => 2, 'message' => $this->db->getError()));
                    } else {
                        // 验证通过 可以进行其他数据操作
                        $res = $cabin->addData($data);
                        if ($res) {
                            echo ajaxReturn(array("state" => 1, 'message' => "新增成功"));
                        } else {
                            echo ajaxReturn(array("state" => 2, 'message' => "新增失败"));
                        }
                    }
                }
            }
		}else{
			echo ajaxReturn(array("state" => 2, 'message' => "表单不能存在空值"));
        }
	}

	/**
	 * 判断船舶是否有底量测量
	 */
	public function ajax_diliang()
	{
	    if (IS_AJAX) {
	    	$ship = new \Common\Model\ShipModel();
	    	$is_diliang = $ship->getFieldById($_POST ['shipid'],'is_diliang');
	    	echo json_encode($is_diliang);
	    } else {
			echo false;
		}
	}
}