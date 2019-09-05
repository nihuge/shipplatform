<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 消费Model
 * */
class ConsumptionModel extends BaseModel
{
	/**
	 * 自动验证
	 */ 
    protected $_validate=array(
    	array('number','','充值单号已经存在！',1,'unique',3),
        // 不能为空
        array('firmname','require','公司名称不能为空',0),// 存在验证 不能为空
        array('number','require','充值单号不能为空',0),// 存在验证 不能为空
        array('firmid','require','公司ID不能为空',0),// 存在验证 不能为空
        array('balance','require','余额不能为空',0),// 存在验证 不能为空
        array('username','require','操作人名不能为空',0),// 存在验证 不能为空
        array('uid','require','操作人ID不能为空',0),// 存在验证 不能为空
        array('time','require','扣费时间不能为空',0),// 存在验证 不能为空
        array('contractnumber','require','合同编号不能为空',0),// 存在验证 不能为空
        // 长度判断
        array('firmname','1,30','公司名称长度不能超过30个字符',0,'length'),// 存在验证
        array('username','1,11','操作人名长度不能超过11个字符',0,'length'),// 存在验证
        array('number','1,39','充值单号长度不能超过39个字符',0,'length'),// 存在验证
        array('time','1,11','时间长度不能超过11个字符',0,'length'),
        array('contractnumber','1,25','合同编号长度不能超过25个字符',0,'length'),
        // 判断是否为正整数
        array('firmid','number','公司ID不是正整数',0),
        array('uid','number','用户ID不是正整数',0),
    );

	/**
	 * 判断用户是否存在，公司是否存在，公司余额是否够使用，
	 * @param int $uid 用户ID
	 * @param int $firmid 公司ID
	 * @return array 
	 * @return arary code 返回码
	 * @return array content 返回数据  
	 */
	public function is_status($uid,$firmid)
	{
		// 判断用户是否存在
	    $user = new \Common\Model\UserModel();
       	$users = $user->valiname($uid,'id');
       	if ($users === false) 
       	{
       		//判断公司是否存在、公司余额是否够使用
       		// 判断公司是否存在
	       	$firm = new \Common\Model\FirmModel();
	       	$firms = $firm->valiname($firmid,'id');
	       	if ($firms === false) 
	       	{
	       		$firmmsg = $firm
							->field('firmname,balance,creditline,service,number')
							->where(array('id'=>$firmid))
							->find();
				// 判断余额是否够
				// 余额+信用额
				$sum = $firmmsg['balance']+$firmmsg['creditline'];
				// 判断额度是否小于等于0或者额度小于消费标准
				if ($sum > '0' and $sum>=$firmmsg['service']) {
					$data = $firmmsg;
					// 获取扣费后余额（余额-扣费标准）
					$data['deductions'] = $firmmsg['balance']-$firmmsg['service'];
					// 获取用户名
					$data['username'] = $user->getFieldById($uid,'username');
					// 成功 1
	       			$res = array(
	       				'code'   => $this->ERROR_CODE_COMMON['SUCCESS'],
	       				'msg'   => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']],
	       				'content'=> $data
	       			);
	       			// $log = implode(',',$res['content']);
	       			// writeLog($log);
				} else {
					//公司余额不足	1011
			    	$res = array(
						'code'   => $this->ERROR_CODE_USER['MONEY_NOT_ENOUGH'],
						'msg'   => $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['MONEY_NOT_ENOUGH']],
					);
				}
				
	       	} else {
       			//公司不存在	1009
		    	$res = array(
					'code'   => $this->ERROR_CODE_USER['NOT_FIRM'],
					'msg'   => $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['NOT_FIRM']],
				);
	       	}
       	} else {
   			//用户不存在	1006
	    	$res = array(
				'code'   => $this->ERROR_CODE_USER['USER_IS_NOT'],
				'msg'   => $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_IS_NOT']],
			);
   			
   		}
   		return $res;
	}

	/**
	 * 扣费操作
	 * @param int uid 用户ID
	 * @param int firmid 公司ID
     * @param int resultid 作业ID
	 * @return array
	 * @return array code 返回码
	 * */
	public function buckleMoney($uid='',$firmid='',$resultid='')
	{
	    if (!empty($uid) or !empty($firmid) and !empty($resultid)) {
	    	// 判断用户是否存在，公司是否存在，公司余额是否够使用
	    	$r = $this->is_status($uid,$firmid);
	    	if ($r['code'] == '1') {
	    		// 自动生成单号（三位随机字母+年月日时分秒+【1~10000】随机数+一位随机字母）
	    		$number = chr(rand(97,122)).chr(rand(97,122)).chr(rand(97,122)).date('YmdHis').rand(1,10000).chr(rand(97,122));
	    		// 整理数据
	    		$data = array(
	    			'number'	=>	$number,
	    			'resultid'	=>	$resultid,
	    			'firmid'	=>	$firmid,
	    			'firmname'	=>	$r['content']['firmname'],
	    			'servicemoney'	=>	$r['content']['service'],
	    			'contractnumber'	=>	$r['content']['number'],
	    			'balance'	=>	$r['content']['deductions'],
	    			'username'	=>	$r['content']['username'],
	    			'uid'		=>	$uid,
	    			'time'		=>	time()
	    		);
	    		if (!$this->create($data)){
				    // 如果创建失败 表示验证没有通过 输出错误提示信息
				    // $this->error($cabin->getError());
				    // 数据格式有错 7
	   				$res = array(
						'code'   => $this->ERROR_CODE_COMMON['ERROR_DATA'],
						'msg'   => $this->getError(),
					);
				}else{
					// 新增充值记录
		   			M()->startTrans();
		   			$r = $this->addData($data);
		   			if ($r !==false) {
		   				// 修改公司余额
		   				$firm = new \Common\Model\FirmModel();
		   				$d = array(
		   					'balance' => $data['balance']
		   				);
		   				$map = array('id'=>$data['firmid']);
		   				$re = $firm->editData($map,$d);
		   				if ($re!==false and $r !==false) {
		   					M()->commit();
		   					// 成功 1
			       			$res = array(
			       				'code'   => $this->ERROR_CODE_COMMON['SUCCESS'],
			       				'msg'   => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']],
			       			);
		   				} else {
		   					M()->rollback();
		   					// 充值失败 6
			   				$res = array(
								'code'   => $this->ERROR_CODE_COMMON['RECHARGE_FAIL'],
								'msg'   => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['RECHARGE_FAIL']],
							);
		   				}
		   			} else {
		   				M()->rollback();
		   				// 扣费失败 8
		   				$res = array(
							'code'   => $this->ERROR_CODE_COMMON['DEDUCTIONS_FAIL'],
							'msg'   => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DEDUCTIONS_FAIL']],
						);
		   			}
				}
	    	} else {
	    		$res = $r;
	    	}
	    	
	    } else {
	    	//参数不正确，参数缺失	4
 	    	$res = array(
 	    		'code'  =>  $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
 	    	);
	    }
	    return $res;
	}
}