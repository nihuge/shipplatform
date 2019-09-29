<?php

namespace Common\Model;

use Common\Model\BaseModel;

/**
 * 船Model
 * */
class ShipModel extends BaseModel
{
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
        array('suanfa', array('a', 'b', 'c'), '算法的范围不正确！', 0, 'in'), // 存在即验证 判断是否在一个范围内
        array('number', '1,50', '编号长度不能超过50个字符', 0, 'length'),//存在即验证 长度不能超过12个字符
        // 在一个范围之内
        array('is_guanxian', array('1', '2'), '是否包含管线的范围不正确！', 0, 'in'),
        array('is_diliang', array('1', '2'), '是否有底量测试的范围不正确！', 0, 'in'),
    );

    /**
     * 获取用户可以操作的船列表
     * @param string imei 标识
     * @param int uid 用户ID
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function shiplist($uid, $imei)
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
                $list = $this
                    ->field('id,shipname,goodsname')
                    ->where(array('id' => array('IN', $usermsg['operation_jur']), "del_sign" => 1))
                    ->select();
                if ($list !== false) {
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
            // 获取所有的船舶公司的船
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
                        if ($s) {
                            // 新增船舶创建表、添加船舶历史数据汇总初步
                            $this->createtable($data['suanfa'], $data['shipname'], $s);

                            // 获取公司限制的个数
                            $limit = $firm->getFieldById($fid, 'limit');
                            // 判断公司的船个数是否超限制
                            $count = $count + 1;
                            if ($count > $limit) {
                                // 超过限额 新增船，不加权限
                                M()->commit();
                                $res = array(
                                    'code' => $this->ERROR_CODE_RESULT['EXCEED_NUM'],
                                    'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['EXCEED_NUM']]
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
                                        'msg' => $this->ERROR_CODE_COMMON[$this->ERROR_CODE_COMMON['DB_ERROR']]
                                    );
                                }
                            }
                        } else {
                            M()->rollback();
                            //数据库连接错误   3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                'msg' => $this->ERROR_CODE_COMMON_ZH[$this->ERROR_CODE_COMMON['DB_ERROR']]
                            );
                        }
                    } else {
                        //船舶已存在   2014
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['HAVE_SHIP'],
                            'msg' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['HAVE_SHIP']]
                        );
                    }
                } else {
                    //用户对该公司没有操作权限   1014
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['USER_NOT_OPERATION_FIRM'],
                        'msg' => $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_NOT_OPERATION_FIRM']]
                    );
                }
            } else {
                //用户不是管理员   1015
                $res = array(
                    'code' => $this->ERROR_CODE_USER['USER_NOT_ADMIN'],
                    'msg' => $this->ERROR_CODE_USER_ZH[$this->ERROR_CODE_USER['USER_NOT_ADMIN']]
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
}