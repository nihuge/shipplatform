<?php

namespace Common\Model;

/**
 * 船Model
 * */
class ShipFormModel extends BaseModel
{
    /**
     * @var string
     * 绑定表ship
     */
    protected $tableName = 'ship';
    /**
     * 自动验证
     */
    protected $_validate = array(
        //array('shipname', '', '船名已经存在！', 1, 'unique', 3), // 新增修改时候验证shipname字段是否唯一
        array('shipname', '1,12', '船名长度不能超过12个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('shibie_num', '0,20', '识别号长度不能超过20个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('make', '0,25', '制造单位长度不能超过25个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('type', '0,8', '类型长度不能超过8个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('weight', '0,8', '吨位长度不能超过8个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('coefficient', '0,3', '膨胀倍数长度不能超过12个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('cabinnum', '0,2', '舱总数长度不能超过12个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        array('firmid', '/^[1-9]\d*$/', '公司id必须为自然数', 2, 'regex'),//值不为空即验证 必须为自然数
        array('cabinnum', '/^[1-9]\d*$/', '舱总数必须为自然数', 0, 'regex'),//值不为空即验证 必须为自然数
        array('coefficient', '/^[1-9]\d*$/', '膨胀倍数必须为自然数', 0, 'regex'),//值不为空即验证 必须为自然数
        array('suanfa', array('a', 'b', 'c', 'd'), '算法的范围不正确！', 0, 'in'), // 存在即验证 判断是否在一个范围内
        array('number', '1,50', '编号长度不能超过50个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        // 在一个范围之内
        array('is_guanxian', array('1', '2'), '是否包含管线的范围不正确！', 0, 'in'),
        array('is_diliang', array('1', '2'), '是否有底量测试的范围不正确！', 0, 'in'),
    );

    /**
     * 获取用户可以操作的船列表
     * @param string imei 标识
     * @param int uid 用户ID
     * @param int has_data 是否只获取有表船
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function shiplist($uid, $imei, $has_data = 0)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($uid, $imei);
        if ($msg1['code'] == '1') {
            $where = array(
                'id' => $uid
            );

            //获取用户的船舶列表id
            $usermsg = $user
                ->field('operation_jur')
                ->where($where)
                ->find();
            if ($usermsg !== false and !empty($usermsg['operation_jur'])) {
                $where_ship = array(
                    's.id' => array('IN', $usermsg['operation_jur']),
                    "s.del_sign" => 1
                );
                //是否只获取有表船，0，都获取，1，只获取有表船，2，只获取无表船
                if ($has_data == 1) {
                    $where_ship['s.data_ship'] = 'y';
                } elseif ($has_data == 2) {
                    $where_ship['s.data_ship'] = 'n';
                }
                $list = $this
                    ->alias('s')
                    ->field('s.id,s.data_ship,s.shipname,s.goodsname,s.expire_time,sl.table_accuracy as accuracy_sum,sl.accuracy_num')
                    ->join('left join ship_historical_sum as sl on sl.shipid=s.id')
                    ->where($where_ship)
                    ->select();
                if ($list !== false) {
                    //返回数组，用于排序
                    $retrun_list = array();

                    foreach ($list as $key => $value) {
                        $list[$key]['expire_time'] = date('Y年m月d日', $value['expire_time']);
                        if ($value['expire_time'] > time()) {
                            $list[$key]['expired'] = false;
                        } else {
                            $list[$key]['expired'] = true;
                        }
                        $accuracy_per = $value['accuracy_sum'] / ($value['accuracy_num'] > 0 ? $value['accuracy_num'] : 1) / 3 * 100;
                        $list[$key]['table_accuracy'] = $value['accuracy_sum'] / ($value['accuracy_num'] > 0 ? $value['accuracy_num'] : 1);
                        if ($value['accuracy_num'] == 0) {
                            $list[$key]['accuracy_title'] = "暂无评价";
                        } elseif ($accuracy_per < 50) {
                            $list[$key]['accuracy_title'] = "平均偏小";
                        } elseif ($accuracy_per == 50) {
                            $list[$key]['accuracy_title'] = "平均正常";
                        } elseif ($accuracy_per > 50) {
                            $list[$key]['accuracy_title'] = "平均偏大";
                        }

                        $list[$key]['pinyin'] = strtoupper(pinyin($value['shipname'], "one",'#','/[a-zA-Z]/'));
                        //赋值拼音全拼当做键给返回数组
                        $retrun_list[pinyin($value['shipname'])] = $list[$key];
                    }
                    //根据键正序排序
                    ksort($retrun_list, SORT_STRING);

                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => array_values($retrun_list)//只返回值，不返回键
                    );
                } else {
                    //数据库连接错误   3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                //该用户下面没有船  10
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['IS_NO_SHIP']
                );
            }
        } else {
            // 错误信息返回码
            $res = $msg1;
        }
        return $res;
    }

    /**
     * 获取用户可以查询的船列表
     * @param string imei 标识
     * @param int uid 用户ID
     * @param int has_data 是否只获取有表船
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function shipSearchList($uid, $imei,$has_data=0)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、是否到期、标识比对
        $msg1 = $user->is_judges($uid, $imei);
        if ($msg1['code'] == '1') {
            $where = array(
                'id' => $uid
            );
            //获取用户的船舶列表id
            $usermsg = $user
                ->field('search_jur')
                ->where($where)
                ->find();
            if ($usermsg !== false and !empty($usermsg['search_jur'])) {

                $where_ship = array(
                    'id' => array('IN', $usermsg['search_jur']),
                    "del_sign" => 1
                );

                if($has_data == 1){
                    $where_ship['data_ship'] = 'y';
                }elseif ($has_data == 2){
                    $where_ship['data_ship'] = 'n';
                }

                $list = $this
                    ->field('id,shipname,goodsname,expire_time')
                    ->where($where_ship)
                    ->select();
                if ($list !== false) {
                    foreach ($list as $key => $value) {
                        $list[$key]['expire_time'] = date('Y年m月d日', $value['expire_time']);
                        if ($value['expire_time'] > time()) {
                            $list[$key]['expired'] = false;
                        } else {
                            $list[$key]['expired'] = true;
                        }
                    }

                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $list
                    );
                } else {
                    //数据库连接错误   3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                //该用户下面没有船  10
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['IS_NO_SHIP']
                );
            }
        } else {
            // 错误信息返回码
            $res = $msg1;
        }
        return $res;
    }

    /**
     * 获取公司下的所有船列表
     * @param int $firmid 公司id
     * @param string $firmtype 公司类型检验、船舶
     * */
    public function getShipList($firmid, $firmtype)
    {
        $firm = new \Common\Model\FirmModel();
        // 根据公司类型区分获取的船数据
        if ($firmtype == '2') {
            // 获取该公司下的所有船
            $shiplist = $this
                ->field('id,shipname')
                ->where(array('firmid' => $firmid, "del_sign" => 1))
                ->select();
            $firmname = $firm->getFieldById($firmid, 'firmname');
            $res[] = array(
                'id' => $firmid,
                'firmname' => $firmname,
                'shiplist' => $shiplist
            );
        } elseif ($firmtype == '1') {
            // 获取所有的船舶 公司的船
            $where = array(
                'firmtype' => '2',
                "del_sign" => 1,
            );
            $res = $firm
                ->field('id,firmname')
                ->where($where)
                ->order('id asc')
                ->select();
            foreach ($res as $key => $value) {
                $res[$key]['shiplist'] = $this
                    ->field('id,shipname')
                    ->where(array('firmid' => $value['id'], "del_sign" => 1))
                    ->select();
                /*                foreach ($res[$key]['shiplist'] as $k=>$v){
                                    if($this->is_have_data($v['id'])=='y'){
                                        $res['dataship'][] = $v['shipname'];
                                    }else{
                                        $res['nodataship'][] = $v['shipname'];
                                    }
                                }*/
            }
        } else {
            $res = array();
        }
        return $res;
    }


    /**
     * 判断船是否有舱容数据
     * @param int $shipid 船id
     * @return array
     */
    public function is_have_data($shipid)
    {
        $msg = $this
            ->field('data_ship')
            ->where(array('id' => $shipid))
            ->find();
        return $msg['data_ship'];
    }

    /**
     * 判断船是否有舱容数据
     * @param int $shipid 船id
     * @return array
     */
    public function is_have_datas($shipid)
    {
        $msg = $this
            ->field('tankcapacityshipid,zx,rongliang,zx_1,rongliang_1,suanfa')
            ->where(array('id' => $shipid))
            ->find();
        // 根据算法判断舱容表是否有数据
        if ($msg['suanfa'] == 'a') {
            if (!empty($msg['tankcapacityshipid'])) {
                $tname = $msg['tankcapacityshipid'];
                // 查看表是否有数据
                $rong = M("$tname");
                $count = $rong->count();
                if ($count > 0) {
                    $res = 'y';
                } else {
                    $res = 'n';
                }
            } else {
                $res = 'n';
            }

        } elseif ($msg['suanfa'] == 'b') {
            if (!empty($msg['zx']) and !empty($msg['rongliang'])) {
                $tname1 = $msg['zx'];
                $tname2 = $msg['rongliang'];
                // 查看表是否有数据
                $zx = M("$tname1");
                $rong = M("$tname2");
                $count1 = $zx->count();
                $count2 = $rong->count();
                if ($count1 > 0 && $count2 > 0) {
                    $res = 'y';
                } else {
                    $res = 'n';
                }
            } else {
                $res = 'n';
            }
        } elseif ($msg['suanfa'] == 'c') {
            if (!empty($msg['zx_1']) and !empty($msg['rongliang_1'])) {
                $tname1 = $msg['zx_1'];
                $tname2 = $msg['rongliang_1'];
                // 查看表是否有数据
                $zx = M("$tname1");
                $rong = M("$tname2");
                $count1 = $zx->count();
                $count2 = $rong->count();
                if ($count1 > 0 && $count2 > 0) {
                    $res = 'y';
                } else {
                    $res = 'n';
                }
            } else {
                $res = 'n';
            }
        } else {
            $res = '';
        }
        return $res;
    }


    /**
     * 新版本判断船是否被锁定
     */
    public function is_lock($shipid)
    {
        $info = $this->field('is_lock')->where(array('id' => intval($shipid)))->find();
        if ($info['is_lock'] == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 旧版本判断船是否被锁定
     */
    public function is_locks($shipid)
    {
        $work = new \Common\Model\WorkModel();
        $res_count = $work->where(array('shipid' => $shipid))->count();
        if ($res_count > 1) {
            //如果被新建审核或者作业次数大于1，则状态为锁定
            return true;
        } else {
            $info = $this->field('review')->where(array('id' => $shipid))->find();
            if ($info['review'] == 3) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * 新增船
     * @param $data
     * @param string $type
     * @return array
     */
    public function addship($data, $type = "")
    {
        if (!$this->create($data)) {
            //数据格式有错  7
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                'msg' => $this->getError()
            );
        } else {
            // 判断用户是否是管理员
            $user = new \Common\Model\UserModel();
            $msg = $user
                ->alias('u')
                ->field('f.firm_jur,u.pid')
                ->join('left join firm f on f.id = u.firmid')
                ->where(array('u.id' => $data['uid']))
                ->find();
            if ($msg['pid'] == 0 and $msg !== false) {
                // 判断用户是否有权限新增该公司船
                $firm_jur = explode(',', $msg['firm_jur']);
                if (in_array($data['firmid'], $firm_jur)) {
                    // 判断船名是否存在
                    $count = $this->where(array('shipname' => $data['shipname']))->count();
                    if ($count == 0) {
                        // 获取用户所属公司ID
                        $fid = $user->getFieldById($data['uid'], 'firmid');
                        // 获取公司下面的操作船个数
                        $firm = new \Common\Model\FirmModel();
                        $operation_jur = $firm->getFieldById($fid, 'operation_jur');
                        if (empty($operation_jur)) {
                            $count = 0;
                        } else {
                            $count = count(explode(',', $operation_jur));
                        }

                        M()->startTrans();  //开启事物
                        // 判断是否有到期时间
                        if (!isset($data['expire_time'])) {
                            // 到期时间默认一周
                            $data['expire_time'] = strtotime("+1weeks", strtotime(date('Y-m-d H:i:s', time())));
                        }

                        $s = $this->addData($data);
                        if ($s !== false) {
                            // 新增船舶创建表、添加船舶历史数据汇总初步
                            $this->createtable($data['suanfa'], $data['shipname'], $s);


                            /*
                             * 新建船舶时自动添加一个对应的船舶账户
                             */
                            //判断公司有无管理员账户，如果没有管理员账户，则不创建
                            $user = new \Common\Model\UserModel();
                            $firm_admin = $user->field('id')->where(array('firmid' => $data['firmid'], 'pid' => 0))->find();
                            //管理员数等于1,开始创建账号,账号名为船名各字的首字母+船的全拼，如果超出字符限制，则裁剪至字符
                            if ($firm_admin['id'] > 0) {
                                $user_data = array(
                                    'title' => substr(pinyin($data['shipname'], 'first') . pinyin($data['shipname']), 0, $user->getUserMaxLength()),
                                    'username' => $data['shipname'],
                                    'pwd' => time(),//第一次的密码随机，如果用户需要登陆就去重置
                                    'firmid' => $data['firmid'],
                                    'pid' => $firm_admin['id'],
                                    'operation_jur' => array($s),//将这个船的权限加入到自己的账号中
                                    'look_other' => 2,//可以看所有公司的作业记录
                                );
                                //创建用户,不考虑是否创建成功，创建失败也不回档。如果创建失败自动评价时使用-1
                                $user->adddatas($user_data);
                            }


                            // 获取公司限制的个数
                            $limit = $firm->getFieldById($fid, 'limit');
                            // 判断公司的船个数是否超限制
                            $count = $count + 1;

                            if ($count > $limit) {
                                // 超过限额 新增船，不加权限
                                M()->commit();
                                $res = array(
                                    'code' => $this->ERROR_CODE_RESULT['EXCEED_NUM'],
                                    'error' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['EXCEED_NUM']]
                                );
                            } else {
                                // 修改公司的操作/查询权限 
                                $firmmsg = $firm->getFirmOperationSearch($fid);
                                $map1 = array(
                                    'id' => $fid
                                );
                                if (empty($firmmsg['operation_jur'][0])) {
                                    unset($firmmsg['operation_jur'][0]);
                                }
                                if (empty($firmmsg['search_jur'][0])) {
                                    unset($firmmsg['search_jur'][0]);
                                }
                                $firmmsg['operation_jur'][] = $s;
                                $firmmsg['search_jur'][] = $s;
                                $data1 = array(
                                    'operation_jur' => implode(',', $firmmsg['operation_jur']),
                                    'search_jur' => implode(',', $firmmsg['search_jur'])
                                );
                                $res1 = $firm->editData($map1, $data1);

                                // 修改用户的操作/查询权限
                                $usermsg = $user->getUserOperationSeach($data['uid']);
                                $map2 = array(
                                    'id' => $data['uid']
                                );
                                if (empty($usermsg['operation_jur_array'][0])) {
                                    unset($usermsg['operation_jur_array'][0]);
                                }
                                if (empty($usermsg['search_jur_array'][0])) {
                                    unset($usermsg['search_jur_array'][0]);
                                }
                                $usermsg['operation_jur_array'][] = $s;
                                $usermsg['search_jur_array'][] = $s;
                                $data2 = array(
                                    'operation_jur' => implode(',', $usermsg['operation_jur_array']),
                                    'search_jur' => implode(',', $usermsg['search_jur_array'])
                                );
                                $res2 = $user->editData($map2, $data2);


                                //复制管理员的权限给同公司的所有员工，只在APP端做此操作
                                if ($type == "APP") {
                                    //复制权限给同公司员工
                                    $map3 = array(
                                        'pid' => $data['uid'],
                                    );
                                    $data3 = $data2;

                                    $res3 = $user->editData($map3, $data3);
                                } else {
                                    $res3 = true;
                                }

                                if ($res1 !== false and $res2 !== false and $res3 !== false) {
                                    M()->commit();
                                    $content = array('is_have_data' => 'n', 'shipid' => $s);

                                    //成功   1
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['SUCCESS']],
                                        'content' => $content
                                    );
                                } else {
                                    M()->rollback();
                                    //数据库连接错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'error' => $this->ERROR_CODE_COMMON[$this->ERROR_CODE_COMMON['DB_ERROR']]
                                    );
                                }
                            }
                        } else {
                            M()->rollback();
                            //数据库连接错误   3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                'error' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DB_ERROR']]
                            );
                        }
                    } else {
                        //船舶已存在   2014
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['HAVE_SHIP'],
                            'error' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['HAVE_SHIP']]
                        );
                    }
                } else {
                    //用户对该公司没有操作权限   1014
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['USER_NOT_OPERATION_FIRM'],
                        'error' => $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_NOT_OPERATION_FIRM']]
                    );
                }
            } else {
                //用户不是管理员   1015
                $res = array(
                    'code' => $this->ERROR_CODE_USER['USER_NOT_ADMIN'],
                    'error' => $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_NOT_ADMIN']]
                );
            }
        }
        return $res;
    }

    /**
     * 新增船舶创建表
     * 添加船舶历史数据汇总初步
     */
    public function createtable($suanfa, $shipname, $shipid, $kedu = array(), $kedu1 = array())
    {
        // 根据算法自动创建需要的表
        if ($suanfa == 'a') {
            $tablename = 'tankcapacityzi' . time() . chr(rand(97, 122));
            $cou = 1;
            $str = '';
            $tripbystern = array();
            foreach ($kedu as $key => $value) {
                $str .= "`tripbystern" . $cou . "` float(6,3) DEFAULT NULL COMMENT '纵倾值" . $value . "/m',";
                $tripbystern['tripbystern' . $cou] = $value;
                $cou++;
            }
            // 创建一个容量表
            $sql = <<<sql
CREATE TABLE `${tablename}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(6,3) DEFAULT NULL COMMENT '实高',
  `ullage` float(6,3) DEFAULT NULL COMMENT '空高',
    ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}纵倾表';
sql;
            M()->execute($sql);
            if (!empty($tripbystern)) {
                $tripbystern = json_encode($tripbystern, JSON_UNESCAPED_UNICODE);
            } else {
                $tripbystern = '';
            }
            $datas = array(
                'tankcapacityshipid' => $tablename,
                'tripbystern' => $tripbystern
            );
        } else if ($suanfa == 'b') {
            $rongname = 'tankcapacityzi' . time() . chr(rand(97, 122));
            $zxname = 'trimcorrectionzi' . time() . chr(rand(97, 122));
            $hxname = 'tablelistcorrectionzi' . time() . chr(rand(97, 122));
            // 创建一个容量表一个纵倾表
            $sql1 = <<<sql
CREATE TABLE `${rongname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(7,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(7,3) DEFAULT NULL COMMENT '空高/m',
  `capacity` float(7,3) DEFAULT NULL COMMENT '容量',
  `diff` float(7,3) DEFAULT NULL COMMENT '厘米容量',
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  COMMENT='${shipname}容量表';
sql;
            M()->execute($sql1);
            // 确定刻度
            $cou = 1;
            $str = '';
            $trimcorrection = array();
            foreach ($kedu as $key => $value) {
                $str .= "`trimvalue" . $cou . "` int(11) DEFAULT NULL COMMENT '纵倾值" . $value . "/m',";
                $trimcorrection['trimvalue' . $cou] = $value;
                $cou++;
            }
            $sql2 = <<<sql
CREATE TABLE `${zxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}纵倾表';
sql;
            M()->execute($sql2);
            if (!empty($trimcorrection)) {
                $trimcorrection = json_encode($trimcorrection, JSON_UNESCAPED_UNICODE);
            } else {
                $trimcorrection = '';
            }

            $datas = array(
                'rongliang' => $rongname,
                'zx' => $zxname,
                'trimcorrection' => $trimcorrection
            );
        } else if ($suanfa == 'c') {
            // 确定刻度
            $cou = 1;
            $str = '';
            $trimcorrection = array();
            foreach ($kedu as $key => $value) {
                $str .= "`trimvalue" . $cou . "` int(11) DEFAULT NULL COMMENT '纵倾值" . $value . "/m',";
                $trimcorrection['trimvalue' . $cou] = $value;
                $cou++;
            }

            //如果没有底量纵倾刻度就复制容量的
            if (empty($kedu1)) {
                $kedu1 = $kedu;
            }

            $cou = 1;
            $str1 = '';
            $trimcorrection1 = array();
            foreach ($kedu1 as $key1 => $value1) {
                $str1 .= "`trimvalue" . $cou . "` int(11) DEFAULT NULL COMMENT '纵倾值" . $value1 . "/m',";
                $trimcorrection1['trimvalue' . $cou] = $value1;
                $cou++;
            }

            $time = time() . chr(rand(97, 122));
            $rongname = 'tankcapacityzi' . $time . '_1';
            $zxname = 'trimcorrectionzi' . $time . '_1';
            $hxname = 'tablelistcorrectionzi' . $time . '_1';
            // 创建一个容量表一个纵倾表
            $sql1 = <<<sql
CREATE TABLE `${rongname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(7,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(7,3) DEFAULT NULL COMMENT '空高/m',
  `capacity` float(7,3) DEFAULT NULL COMMENT '容量',
  `diff` float(7,3) DEFAULT NULL COMMENT '厘米容量',
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  COMMENT='${shipname}容量表';
sql;
            M()->execute($sql1);
            $sql2 = <<<sql
CREATE TABLE `${zxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}纵倾表';
sql;
            M()->execute($sql2);
            $rongname1 = 'tankcapacityzi' . $time . '_2';
            $zxname1 = 'trimcorrectionzi' . $time . '_2';
            $hxname = 'tablelistcorrectionzi' . $time . '_2';

            // 创建一个容量表一个纵倾表
            $sql3 = <<<sql
CREATE TABLE `${rongname1}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(7,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(7,3) DEFAULT NULL COMMENT '空高/m',
  `capacity` float(7,3) DEFAULT NULL COMMENT '容量',
  `diff` float(7,3) DEFAULT NULL COMMENT '厘米容量',
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  COMMENT='${shipname}容量表';
sql;
            M()->execute($sql3);
            $sql4 = <<<sql
CREATE TABLE `${zxname1}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str1}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}纵倾表';
sql;
            M()->execute($sql4);

            if (!empty($trimcorrection)) {
                $trimcorrection = json_encode($trimcorrection, JSON_UNESCAPED_UNICODE);
            } else {
                $trimcorrection = '';
            }

            if (!empty($trimcorrection1)) {
                $trimcorrection1 = json_encode($trimcorrection1, JSON_UNESCAPED_UNICODE);
            } else {
                $trimcorrection1 = '';
            }


            $datas = array(
                'rongliang' => $rongname,
                'rongliang_1' => $rongname1,
                'zx' => $zxname,
                'zx_1' => $zxname1,
                'trimcorrection' => $trimcorrection,
                'trimcorrection1' => $trimcorrection1,
            );
        } else if ($suanfa == 'd') {
            // 确定刻度
            $cou = 1;
            $str = '';
            $trimcorrection = array();
            foreach ($kedu as $key => $value) {
                $str .= "`trimvalue" . $cou . "` int(11) DEFAULT NULL COMMENT '纵倾值" . $value . "/m',";
                $trimcorrection['trimvalue' . $cou] = $value;
                $cou++;
            }

            //如果没有底量纵倾刻度就复制容量的
            if (empty($kedu1)) {
                $kedu1 = $kedu;
            }

            $cou = 1;
            $str1 = '';
            $trimcorrection1 = array();
            foreach ($kedu1 as $key1 => $value1) {
                $str1 .= "`trimvalue" . $cou . "` int(11) DEFAULT NULL COMMENT '纵倾值" . $value1 . "/m',";
                $trimcorrection1['trimvalue' . $cou] = $value1;
                $cou++;
            }

            $time = time() . chr(rand(97, 122));
            $zxname = 'trimcorrectionzi' . $time . '_1';
            // 创建一个纵倾修正容量表
            $sql2 = <<<sql
CREATE TABLE `${zxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}纵倾表';
sql;
            M()->execute($sql2);
            $zxname1 = 'trimcorrectionzi' . $time . '_2';
            // 创建一个纵倾容量表
            $sql4 = <<<sql
CREATE TABLE `${zxname1}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str1}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}纵倾表';
sql;
            M()->execute($sql4);

            if (!empty($trimcorrection)) {
                $trimcorrection = json_encode($trimcorrection, JSON_UNESCAPED_UNICODE);
            } else {
                $trimcorrection = '';
            }

            if (!empty($trimcorrection1)) {
                $trimcorrection1 = json_encode($trimcorrection1, JSON_UNESCAPED_UNICODE);
            } else {
                $trimcorrection1 = '';
            }


            $datas = array(
                'zx' => $zxname,
                'zx_1' => $zxname1,
                'trimcorrection' => $trimcorrection,
                'trimcorrection1' => $trimcorrection1,
            );
        }


        $map = array(
            'id' => $shipid
        );
        $this->editData($map, $datas);


        // 添加船舶历史数据汇总初步
        $arr = array('shipid' => $shipid);
        // 判断船历史统计数据是否存在
        $cc = M('ship_historical_sum')->where($arr)->count();
        if ($cc > 0) {

        } else {
            M('ship_historical_sum')->add($arr);
        }
        return 1;
    }

    /**
     * 获取船的舱容表偏大偏小信息
     */
    public function get_ship_table_accuracy($shipid)
    {
        $ship_history = M("ship_historical_sum");
        $histtory_res = $ship_history->field('table_accuracy as accuracy_sum,accuracy_num')->where(array('shipid' => $shipid))->count();
        //舱容表偏大偏小的平均值
        $histtory_res['table_accuracy'] = $histtory_res['accuracy_sum'] / ($histtory_res['accuracy_num'] > 0 ? $histtory_res['accuracy_num'] : 1);
        //舱容表偏大偏小的评价
        $histtory_res['table_message'] = $histtory_res['table_accuracy'] > 0 ? ($histtory_res['table_accuracy'] < 1.5 ? "偏小" : ($histtory_res['table_accuracy'] == 1.5 ? "正常" : "偏大")) : "无反馈";
        return $histtory_res;
    }

    /**
     * 获取船舶的自动创建的账户，如果没有则返回-1
     */
    public function get_ship_auto_account($ship_id)
    {
        $user = new UserModel();
        $ship_info = $this->field('firmid,shipname')->where(array('id' => intval($ship_id)))->find();
        $where = array(
            'title' => substr(pinyin($ship_info['shipname'], 'first') . pinyin($ship_info['shipname']), 0, $user->getUserMaxLength()),
            'name' => $ship_info['shipname'],
            'firmid' => $ship_info['firmid'],
        );
        $user_info = $user->field('id,username')->where($where)->find();
        //如果找不到就返回-1
        if ($user_info['id'] == 0 or $user_info['id'] == null) {
            $user_info = array(
                'id' => -1,
                'username' => -1,
            );
        }
        return $user_info;
    }

    /**
     * 上传舱容表
     */
    public function up_table($uid, $type, $shipname)
    {
        M()->startTrans();
        $ship_review = M('table_review');
        $data = array(
            'uid' => $uid,
            'type' => $type,
            'shipname' => $shipname,
            'time' => time(),
        );
        $id = $ship_review->add($data);
        if ($id === false) {
            M()->rollback();
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
            );
        } else {
            M()->commit();
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                'review_id' => $id,
            );
        }
        return $res;
    }

    /**
     * 更新所有船舶的有表无表状态
     */
    public function updata_data_ship()
    {
        /**
         * 获取公司下的所有船列表
         * */
        M()->startTrans();
        $res = $this
            ->field('id')
            ->select();
        foreach ($res as $k => $v) {
            $data_ship = $this->is_have_datas($v['id']);
            $data_ship = $data_ship == "" ? "n" : $data_ship;
            $data = array(
                'data_ship' => $data_ship
            );
            $map = array(
                'id' => $v['id']
            );
            $res[$v['id']] = $data;
            $result = $this->editData($map, $data);
            if ($result === false) {
                M()->rollback();
                //数据库错误
                return array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'], 'error' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DB_ERROR']]);
            }
        }
        M()->commit();
        $res['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
        return $res;
    }

    //更新某条船的有表无表状态
    public function updata_one_ship($shipid)
    {
        $data_ship = $this->is_have_datas($shipid);
        $data_ship = $data_ship == "" ? "n" : $data_ship;
        $data = array(
            'data_ship' => $data_ship
        );
        $map = array(
            'id' => $shipid
        );
        $res[$shipid] = $data;
        $result = $this->editData($map, $data);
        if ($result === false) {
            M()->rollback();
            //数据库错误
            return array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'], 'error' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DB_ERROR']]);
        } else {
            M()->commit();
            $res['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
            return $res;
        }
    }

    public function updata_lock_ships()
    {
        $shipid_arr = $this->getField('id', true);
        //更新所有船的锁定状态
        foreach ($shipid_arr as $value) {
            $this->updata_lock_ship($value);
        }
    }

    public function updata_lock_ship($shipid)
    {
        //根据作业量来更新一下船的锁状态
        /**
         * 查找船的作业次数
         */
        $work = new \Common\Model\WorkModel();
        $result_count = $work->where(array('shipid' => intval($shipid)))->count();
//        $result_count = M('ship_historical_sum')->field('num')->where(array('shipid' => intval($shipid)))->find();
        if ($result_count['num'] >= 2) {
            $ship_data = array(
                'is_lock' => 1
            );
            $this->editData(array('id' => intval($shipid)), $ship_data);
        }
    }
}