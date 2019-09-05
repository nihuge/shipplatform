<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 充值Model
 * */
class RechargeModel extends BaseModel
{
	/**
	 * 自动验证
	 */ 
    protected $_validate=array(
    	array('number','','充值单号已经存在！',1,'unique',3),
        // 不能为空
        array('firmname','require','公司名称不能为空',0),// 必须验证 不能为空
        array('number','require','充值单号不能为空',0),// 必须验证 不能为空
        array('firmid','require','公司ID不能为空',0),// 必须验证 不能为空
        array('money','require','充值金额不能为空',0),// 必须验证 不能为空
        array('channel','require','充值渠道不能为空',0),// 必须验证 不能为空
        array('balance','require','余额不能为空',0),// 必须验证 不能为空
        array('status','require','支付状态不能为空',0),// 必须验证 不能为空
        array('username','require','操作人名不能为空',0),// 必须验证 不能为空
        array('uid','require','操作人ID不能为空',0),// 必须验证 不能为空
        array('ordertime','require','下单时间不能为空',0),// 必须验证 不能为空
        array('time','require','充值时间不能为空',0),// 必须验证 不能为空
        // 在一个范围之内
        array('status',array('1','2','3'),'状态的范围不正确！',0,'in'), // 必须验证 判断是否在一个范围内
        // 长度判断
        array('firmname','1,30','公司名称长度不能超过30个字符',0,'length'),// 必须验证
        array('username','1,11','操作人名长度不能超过11个字符',0,'length'),// 必须验证
        array('number','1,39','充值单号长度不能超过39个字符',0,'length'),// 必须验证
        array('expire_time','1,11','到期时间长度不能超过11个字符',0,'length'),// 必须验证
        // 判断是否为正整数
        array('firmid','number','公司ID不是正整数',0),
        array('uid','number','用户ID不是正整数',0),
        array('money','number','充值金额不是正整数',0),
    );

    /**
     * 判断公司是否存在、人员是否存在、订单编号是否存在
	  * @param int uid 用户ID
    * @param int firmid 公司ID
    * @param string number 订单号
	  * @param string source 来源
	  * @return array res
	  * @return array code 返回码
	  * @return array content 返回数据
     * */
    public function is_status($uid,$firmid,$number,$source)
    {
       	// 判断公司是否存在
       	$firm = new \Common\Model\FirmModel();
       	$firms = $firm->valiname($firmid,'id');
       	if ($firms === false) {
       		// 根据来源判断操作人是否存在
       		if ($source == '管理') {
       			$admin = new \Common\Model\AdminModel();
       			$users = $admin->valiname($uid,'id');
       		} else {
       			$user = new \Common\Model\UserModel();
       			$users = $user->valiname($uid,'id');
       		}
       		
       		if ($users === false) {
       			// 判断订单编号是否存在
       			$msg = $this->valiname($number,'number');
       			if ($msg === false) {
       				// 充值单号已存在 1012
       				$res = array(
						'code'   => $this->ERROR_CODE_USER['NUMBER_IS_EXISTENCE']
					);
       			} else {
       				// 获取公司名、用户名
       				// $firmname = $firm->getFieldById($firmid,'firmname');
       				$firmmsg = $firm
       								->field('firmname,balance')
       								->where(array('id'=>$firmid))
       								->find();
       				if ($source == '管理') {
       					$username = $admin->getFieldById($uid,'name');
       				}else{
						$username = $user->getFieldById($uid,'username');
       				}
       				
	       			// 返回的数据：用户名、公司名
	       			$data = array(
	       				'username'	=>   $username,
	       				'firmname'	=>   $firmmsg['firmname'],
	       				'balance'	=>   $firmmsg['balance']
	       			);
	       			// 成功 1
	       			$res = array(
	       				'code'   => $this->ERROR_CODE_COMMON['SUCCESS'],
	       				'content'=> $data
	       			);
       			}
       		} else {
       			//用户不存在	1006
		    	$res = array(
					'code'   => $this->ERROR_CODE_USER['USER_IS_NOT']
				);
       			
       		}
       	} else {
       		//公司不存在	1009
	    	$res = array(
				'code'   => $this->ERROR_CODE_USER['NOT_FIRM']
			);
       	}
       	return $res;
    } 	

    /**
     * 线下充值
     * @param array data 提交的数据

     * */
   public function xxRecharge($data)
   {
        // 判断公司是否存在、人员是否存在、订单编号是否存在
   		$msg = $this->is_status($data['uid'],$data['firmid'],$data['number'],$data['source']);
   		if ($msg['code'] == '1') {
   			$data['firmname'] = $msg['content']['firmname'];
   			$data['username'] = $msg['content']['username'];
   			$data['balance'] = $msg['content']['balance'];
   			// 判断数据格式
   			// 对数据进行验证
	    	if (!$this->create($data)){
			    // 如果创建失败 表示验证没有通过 输出错误提示信息
			    // $this->error();
			    // 数据格式有错 7
   				$res = array(
					'code'   => $this->ERROR_CODE_COMMON['ERROR_DATA'],
          'msg'    => $this->getError()
				);
			}else{
				// 新增充值记录
	   			M()->startTrans();
	   			$r = $this->addData($data);
	   			if ($r !==false) {
	   				// 修改公司余额
	   				$firm = new \Common\Model\FirmModel();
	   				$balance = $data['balance']+$data['money'];	//修改的金额（原始余额+充值金额）
	   				$d = array(
	   					'balance' => $balance
	   				);
	   				$map = array('id'=>$data['firmid']);
	   				$re = $firm->editData($map,$d);
	   				if ($re!==false and $r !==false) {
	   					M()->commit();
	   					// 成功 1
		       			$res = array(
		       				'code'   => $this->ERROR_CODE_COMMON['SUCCESS'],
		       				'content'=> $data
		       			);
	   				} else {
	   					M()->rollback();
	   					// 充值失败 6
		   				$res = array(
							'code'   => $this->ERROR_CODE_COMMON['RECHARGE_FAIL']
						);
	   				}
	   			} else {
	   				M()->rollback();
	   				// 充值失败 6
	   				$res = array(
						'code'   => $this->ERROR_CODE_COMMON['RECHARGE_FAIL']
					);
	   			}
			}
   		} else {
   			// 返回错误信息
   			$res = $msg;
   		}
   		
   		return $res;
   }   
}