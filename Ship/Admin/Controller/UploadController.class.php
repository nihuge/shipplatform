<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
/**
 * 数据导入
 */
class UploadController extends AdminBaseController
{
	/**
	 * 数据导入
	 */
	public function index()
	{
		if (IS_POST) {
            setcookie('upload_shipid',I('post.shipid'),time()+3600*24*30*6);
            setcookie('upload_tname',I('post.tname'),time()+3600*24*30*6);
            setcookie('upload_cabinid',I('post.cabinid'),time()+3600*24*30*6);
            if (I('post.tname') != null)
			{
				if($_FILES['file']['tmp_name'])
				{
					//判断文件格式
					$type=getFileExt($_FILES ['file'] ['name']);
					if($type!='.csv')
					{
						$this->error('文件格式不正确，必须为CSV文件！');
					}
					header("Content-type:text/html;charset=gbk");
					//读取CSV文件
					$file = fopen($_FILES ['file'] ['tmp_name'],'r');
					while ($data = fgetcsv($file))
					{ //每次读取CSV里面的一行内容
						$array[] = $data;
					}
					$array=array_values($array);
					$array = eval('return '.iconv('gbk','utf-8',var_export($array,true)).';');
					static $total = 0;
					$count1 = count($array);
					$rr = I('post.r');
					$model = M();  
					$model->startTrans();   //开启事物
					/**
					 *	数据导入的类型
					 *	拆分a\b\c
					 *	a:容量（容量、底量）类型
					 *	b:容量类型
					 *	c:纵倾修正（容量、底量）类型
					 * */
					$t_name = I('post.tname');
					$qufen = substr($t_name,-1);	//获取数据导入的类型
					$tname = substr($t_name,0,-1);	//获取表名

		     		$t = M("$tname");
					foreach ($array as  $tmp)
					{
						switch ($qufen) {
							case 'a':
								$data1 = array(
									'sounding'   	=>    $tmp[0],
									'ullage'		=>    $tmp[1],
									'capacity'	 	=>    $tmp[2],
									'diff'   		=>    $tmp[3],
									'cabinid'	    =>    I('post.cabinid')
								);
								break;
							case 'b':
								$data1 = array(
									'sounding'   	 =>    $tmp[0],
									'ullage'		 =>    $tmp[1],
									'tripbystern1'	 =>    $tmp[2],
									'tripbystern2'   =>    $tmp[3],
									'tripbystern3'   =>    $tmp[4],
									'tripbystern4'   =>    $tmp[5],
									'tripbystern5'   =>    $tmp[6],
									'tripbystern6'	 =>    $tmp[7],
									'tripbystern7'	 =>    $tmp[8],
									'cabinid'	  	 =>    I('post.cabinid')
								);
								break;
							case 'c':
								$data1 = array(
									'sounding'   			=>    $tmp[0],
									'ullage'		  		=>    $tmp[1],
									'trimvalue1'	 		=>    $tmp[2],
									'trimvalue2'	 		=>    $tmp[3],
									'trimvalue3'	 		=>    $tmp[4],
									'trimvalue4'	 		=>    $tmp[5],
									'trimvalue5'	 		=>    $tmp[6],
									'trimvalue6'	 		=>    $tmp[7],
									'trimvalue7'	 		=>    $tmp[8],
									'trimvalue8'	 		=>    $tmp[9],
									'trimvalue9'	 		=>    $tmp[10],
									'trimvalue10'	 		=>    $tmp[11],
									'trimvalue11'	 		=>    $tmp[12],
									'cabinid'	  			=>    I('post.cabinid')
								);
								break;
							default:
								$data1 = array();
								break;
						}
						if (empty($data1)) {
							$this->error('选择的数据类型有误！');
						} 
				        foreach ($data1 as $k => $v) {
				            $datas[$k]=trim($v);
				        }
				        // writeLog($datas['sounding']);
				        $where = array(
							'sounding'    =>    $datas['sounding'],
							'cabinid'	  =>    $datas['cabinid']
						);
						$count = $t->where($where)->count();
						if ($count > 0 && $rr == 'y') {
							//覆盖（修改）
							$total++;
							$t->where($where)->save($datas);
						} elseif ($count > 0 && $rr == 'n') {
							writeLog($count);
                            writeLog($t->getLastSql());
							//不覆盖(跳过)
							$model->rollback();
							$this->error('表中已存在数据');
							exit;
						}elseif($count == '0'){
							$total++;
							$t->add($datas);
							writeLog($count);
							writeLog($t->getLastSql());
						}
						$datas = array();
					} 
					if ($count1 == $total) {
						$model->commit();
						$this->success('导入成功');
					} else {
						$model->rollback();
						$this->error('拥有重复数据');
					}
				
				}else{
					$this->error("上传文件不存在！");
				}
			} else {
				$this->error("导入有误！(表名不为空)");
			}
		} else {
			//获取船列表
			$ship = new \Common\Model\ShipModel();
			$shiplist = $ship
					->field('id,shipname')
					->order('shipname asc')
					->select();
			$this->assign('shiplist',$shiplist);
			$this->display();
		}
	}

	/**
	 * ajax获取舱(下拉框)
	 * @param int $shipid 船ID
	 * @return string 
	 * */
	public function cabin_op()
	{
		if (IS_AJAX) {
			$cabin = new \Common\Model\CabinModel();
			$arr = $cabin
					->field('id,cabinname')
					->where(array('shipid'=>$_POST ['shipid']))
					->order('id asc')
					->select();
			static $mod = "<option value=''>--选择舱--</option>";
			$cabinlist = array();
			foreach ( $arr as $key => $vo ) {
				$mod .= "<option  value='" . $vo ['id'] . "'>" . $vo['cabinname']."</option>";
				$cabinlist[] = $vo ['id'];
			}
			//根据船ID获取
			$ship = new \Common\Model\ShipModel();
			$msg =$ship
					->field('rongliang,rongliang_1,tankcapacityshipid,zx,zx_1')
					->where(array('id'=>$_POST ['shipid']))
					->find();
			$tname = '';
			$presence = '<table><tr><td colspan="2">已存在导入数据</td></tr>';
			//判断表是否存在并组装单选html
			/**
			 *	连接a\b\c
			 *	a:容量（容量、底量）类型
			 *	b:容量类型
			 *	c:纵倾修正（容量、底量）类型
			 * */
			//容量
			if (!empty($msg['tankcapacityshipid'])) {
				$tname .= "&nbsp;<input type='radio' name='tname' value='".$msg['tankcapacityshipid']."b' id='tc' checked><label for='tc'>容量</label><hr/>";
				$table = $msg['tankcapacityshipid'];
				$presence .= '<tr><td>容量</td>' . $this->is_like($table,$cabinlist);
			}
			//容量--容量 
			if (!empty($msg['rongliang'])) {
				$tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='".$msg['rongliang']."a' id='rl'><label for='rl'>容量表</label>";
				$table = $msg['rongliang'];
				$presence .= '<tr><td>容量书容量表</td>' . $this->is_like($table,$cabinlist);
			}
			//容量--纵倾修正
			if (!empty($msg['zx'])) {
				$tname .= "&nbsp;<input type='radio' name='tname' value='".$msg['zx']."c' id='zx'><label for='zx'>纵倾修正表</label><hr/>";
				$table = $msg['zx'];
				$presence .= '<tr><td>容量书纵倾修正表</td>' . $this->is_like($table,$cabinlist);
			}
			//底量--容量
			if (!empty($msg['rongliang_1'])) {
				$tname .= "B书(底量书)：&nbsp;<input type='radio' name='tname' value='".$msg['rongliang_1']."a' id='rl_1'><label for='rl_1'>容量表</label>";
				$table = $msg['rongliang_1'];
				$presence .= '<tr><td>底量书容量表</td>' . $this->is_like($table,$cabinlist);
			}
			//底量--纵倾修正
			if (!empty($msg['zx_1'])) {
				$tname .= "&nbsp;<input type='radio' name='tname' value='".$msg['zx_1']."c' id='zx_1'><label for='zx_1'>纵倾修正表</label>";
				$table = $msg['zx_1'];
				$presence .= '<tr><td>底量书纵倾修正表</td>' . $this->is_like($table,$cabinlist);
			}
			$data = array(
				'sc'	       =>  $mod,
				'presence'  => $presence,
				// 'rongliang_1'  => $msg['rongliang_1']
				'tname'			=> $tname
			);
			echo json_encode($data);
		} else {
			echo false;
		}
	}

	public function is_like($table,$cabinlist){
		$table = M("$table");
		$a = $table->group('cabinid')->getField('cabinid',true);
		$like = array_intersect($cabinlist,$a);
		$presence = '<td>';
		$cabin = new \Common\Model\CabinModel();
		foreach ($like as $key => $value) {
			$cabinname = $cabin->getFieldById($value,'cabinname');
			$presence .= $cabinname.' , ';
		}
		$presence .= '</td></tr>';

		return $presence;
	}

}