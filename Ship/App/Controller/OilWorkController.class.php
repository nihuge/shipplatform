<?php

namespace App\Controller;

use Common\Controller\AppBaseController;

/**
 * 作业指令管理
 */
class OilWorkController extends AppBaseController
{
    public function __construct()
    {
        parent::__construct();
        \Think\Log::record("\r\n \r\n [ OilWork!!! ] This process is transferred to the OilWork controller! \r\n \r\n ", "DEBUG", true);
    }

    /**
     * 作业指令列表（查询结果合并）
     * @param int uid 用户ID
     * @param string shipname 船名
     * @param string voyage 航次
     * @param string starttime 起始时间
     * @param string endtime 结束时间
     * @param string locationname 作业地点
     * @param string imei 标识
     * @return array
     * @return @param code 返回码
     * @return @param content 内容、说明
     */
    public function resultlist()
    {
        if (I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            $uid = I('post.uid');
            //判断用户状态、公司状态、标识比对
            $msg = $user->is_judges($uid, I('post.imei'));
            if ($msg['code'] == '1') {
                $where = '1 and r.del_sign=1 and (r.oil_type>1 and r.oil_type<=4)';
                // 根据用户id获取可以查询的船列表
                $msg = $user->getUserOperationSeach($uid);

                if (I('post.search') != null) {
                    // 查询指令列表
                    if (I('post.shipname')) {
                        $shipname = trimall(I('post.shipname'));
                    }
                    $where .= " and s.shipname like '%" . $shipname . "%'";

                    if ($msg['search_jur'] == '') {
                        // 查询权限为空时，查看所有操作权限之内的作业
                        if ($msg['operation_jur'] == '') {
                            $operation_jur = "-1";
                        } else {
                            $operation_jur = $msg['operation_jur'];
                        }
                        $where .= " and r.uid ='$uid' and s.id in (" . $operation_jur . ")";
                    } else {
                        $where .= " and s.id in (" . $msg['search_jur'] . ")";
                    }

                    // 获取登陆用户的所属公司ID
                    $firmid = $user->getFieldById($uid, 'firmid');
                    if ($msg['look_other'] == '1') {
                        $where .= " and u.firmid=$firmid";
                    } elseif ($msg['look_other'] == '3') {
                        $where .= " and u.id=$uid";
                    }

                } else {
                    // 作业指令列表
                    if ($msg['operation_jur'] == '') {
                        $operation_jur = "-1";
                    } else {
                        $operation_jur = $msg['operation_jur'];
                    }
                    $where .= " and r.uid ='$uid' ";
                    $where .= " and r.shipid in (" . $operation_jur . ")";
                }


                // 条件---航次
                if (I('post.voyage')) {
                    $voyage = trimall(I('post.voyage'));
                    // $where .= " and r.voyage = '$voyage'";
                    $where .= " and r.personality like  '" . '%"voyage":"' . $voyage . '%\'';
                }
                // 条件---作业地点
                if (I('post.locationname')) {
                    $locationname = trimall(I('post.locationname'));
                    $where .= " and r.personality like  '" . '%"locationname":"' . $locationname . '%\'';
                }
                // 条件---开始时间
                if (I('post.starttime')) {
                    $starttime = strtotime(I('post.starttime'));
                    $where .= " and r.time >= $starttime";
                }
                //条件---结束时间
                if (I('post.endtime')) {
                    $endtime = strtotime(I('post.endtime'));
                    $where .= " and r.time <= $endtime";
                }
                //条件---作业前后
                if (I('post.solt')) {
                    $w_solt = trimall(I('post.solt'));
                    $where .= " and r.solt = '$w_solt'";
                }



                $result = new \Common\Model\OilWorkModel();
                //计算个数
                $count = $result
                    ->alias('r')
                    ->join('left join ship s on r.shipid=s.id')
                    ->join('left join user u on r.uid = u.id')
                    ->join('left join firm f on f.id = u.firmid')
                    ->where($where)
                    ->count();
                $per = 5;
                if ($_POST['p']) {
                    $p = $_POST['p'];
                } else {
                    $p = 1;
                }
                //分页
                $page = fenye($count, $per);
                $begin = ($p - 1) * $per;
                //查询作业列表
                $list = $result
                    ->field('r.id,r.uid,r.shipid,r.weight,r.solt,r.remark,r.personality,r.finish_sign,r.grade1,r.grade2,s.shipname,u.username,s.is_guanxian,s.is_diliang,s.suanfa,f.firmtype')
                    ->alias('r')
                    ->join('left join ship s on r.shipid=s.id')
                    ->join('left join user u on r.uid = u.id')
                    ->join('left join firm f on f.id = u.firmid')
                    ->where($where)
                    ->order('r.id desc')
                    ->limit($begin, $per)
                    ->select();
                // 获取当前登陆用户的公司类型
                $a = $user
                    ->field('f.firmtype,f.id')
                    ->alias('u')
                    ->join('left join firm f on u.firmid = f.id')
                    ->where(array('u.id' => $uid))
                    ->find();

                if ($list !== false) {
                    // 舱ID列表
                    $resultlist = new \Common\Model\ResultlistModel();
                    $ship = new \Common\Model\ShipFormModel();
                    foreach ($list as $key => $v) {
                        $where1 = array('resultid' => $v['id']);
                        $list[$key]['list'] = $resultlist
                            ->field('cabinid')
                            ->where($where1)
                            ->select();
                        // 已作业舱的总数
                        $list[$key]['nums'] = count($list[$key]['list']);
                        $list[$key]['personality'] = json_decode($v['personality'], true);
                        // 判断船是否有数据 y:有；n:没有 为空是没有算法
                        $list[$key]['is_have_data'] = $ship->is_have_data($v['shipid']);

                        // 根据作业人公司类型判断这条作业是否可以评价
                        // 判断作业是否完成----电子签证
                        $coun = M('electronic_visa')
                            ->where(array('resultid' => $v['id']))
                            ->count();
                        if ($coun > 0) {
                            if ($v['firmtype'] == 2) {
                                $list[$key]['is_coun'] = '1';
                            } else {

                                // 船舶所属公司
                                $rfirmid = $ship->getFieldById($v['shipid'], 'firmid');
                                if ($v['uid'] == $uid) {
                                    // 检验公司评价
                                    if ($v['grade1'] != 0) {
                                        $list[$key]['is_coun'] = '4';
                                    } else {
                                        $list[$key]['is_coun'] = '2';
                                    }
                                } elseif ($rfirmid == $a['id']) {
                                    // 船舶公司评价
                                    if ($v['grade2'] != 0) {
                                        $list[$key]['is_coun'] = '4';
                                    } else {
                                        $list[$key]['is_coun'] = '2';
                                    }
                                } else {
                                    $list[$key]['is_coun'] = '1';
                                }
                            }
                        } else {
                            $list[$key]['is_coun'] = '3';
                        }
                    }
                    //成功	1
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $list
                    );
                } else {
                    //数据库连接错误	3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                // 返回错误返回码
                $res = $msg;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 新增作业
     * @param int uid 用户ID
     * @param int shipid 船ID
     * @param string voyage 航次
     * @param string locationname 作业地点
     * @param string start 起运港
     * @param string objective 目的港
     * @param string goodsname 货名
     * @param string transport 运单量
     * @param string imei 标识
     * @param string shipper 发货方
     * @param string feedershipname 海船船名
     * @param string number 编号
     * @param string wharf 海船装运码头
     * @param string volume 海船发货量
     * @param string inspection 海船商检量
     * @param string sumload 总装载量
     * @return array
     * @return @param code 返回码
     * @return @param resultid 说明、内容
     */
    public function addresult()
    {
        if (I('post.uid') and I('post.shipid') and I('post.imei') and I('post.voyage') and I('post.oil_type')) {
            $oil_type = I('post.oil_type');
            //不同的油品类型不可以互相使用接口,报错2043
            if($oil_type<2 || $oil_type>4) exit(jsonreturn(array('code'=>$this->ERROR_CODE_RESULT['OIL_TYPE_ERROR'])));
            $user = new \Common\Model\UserModel();
            //判断用户状态、公司状态、标识比对
            $msg = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $v = I('post.voyage');
                $voyage = '"voyage":"' . $v . '"';
                $where = array(
                    'shipid' => I('post.shipid'),
                    'personality' => array('like', '%' . $voyage . '%')
                );
                $res = $result
                    ->where($where)
                    ->count();
                if ($res < '1') {
                    $data = I('post.');
                    $data['time'] = time();
                    //添加数据
                    $res = $result->addResult($data, I('post.uid'));
                } else {
                    //重复数据   2003
                    $res = array(
                        'code' => $this->ERROR_CODE_RESULT['IS_REPEAT']
                    );
                }
            } else {
                //返回错误返回码
                $res = $msg;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }


    /**
     * 修改作业
     * @param int uid 用户ID
     * @param int shipid 船ID
     * @param string voyage 航次
     * @param string locationname 作业地点
     * @param string start 起运港
     * @param string objective 目的港
     * @param string goodsname 货名
     * @param string transport 运单量
     * @param string imei 标识
     * @param string shipper 发货方
     * @param string feedershipname 海船船名
     * @param string number 编号
     * @param string wharf 海船装运码头
     * @param string volume 海船发货量
     * @param string inspection 海船商检量
     * @param string sumload 总装载量
     * @return array
     * @return @param code 返回码
     * @return @param resultid 说明、内容
     */
    public function later_edit_result()
    {
        if (I('post.uid') and I('post.resultid') and I('post.imei') and I('post.start') != null and I('post.objective') != null) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、公司状态、标识比对
            $msg = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $data = I('post.');
                //添加数据
                $res = $result->laterEditResult($data);
            } else {
                //返回错误返回码
                $res = $msg;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 修改个性化字段信息
     * @param int uid 用户ID
     * @param int shipid 船ID
     * @param string voyage 航次
     * @param string locationname 作业地点
     * @param string start 起运港
     * @param string objective 目的港
     * @param string goodsname 货名
     * @param string transport 运单量
     * @param string imei 标识
     * @param string shipper 发货方
     * @param string feedershipname 海船船名
     * @param string number 编号
     * @param string wharf 海船装运码头
     * @param string volume 海船发货量
     * @param string inspection 海船商检量
     * @param string sumload 总装载量
     * @return array
     * @return @param code 返回码
     * @return @param resultid 说明、内容
     */
    public function get_personality_info()
    {
        if (I('post.uid') and I('post.resultid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、公司状态、标识比对
            $msg = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $where = array(
                    'r.id' => ':id',
                    'r.uid' => ':uid'
                );
                $bind = array(
                    ":uid" => intval(I('post.uid')),
                    ':id' => intval(I('post.resultid')),
                );
                $res_person = $result
                    ->alias('r')
                    ->field('r.personality,s.shipname')
                    ->join('left join ship as s on s.id=r.shipid')
                    ->where($where)
                    ->bind($bind)
                    ->find();
                $res_person['personality'] = json_decode($res_person['personality'], true);
                $firm = new \Common\Model\FirmModel();
                $firmmsg = $firm
                    ->field('personality')
                    ->where(array('id' => $msg['content']))
                    ->find();
                if ($firmmsg !== false and !empty($firmmsg['personality'])) {
                    //获取用户公司的自定义字段，如果存在没填写的字段则不允许打印pdf
                    $person = new \Common\Model\PersonalityModel();
                    $where = array(
                        'id' => array('in', json_decode($firmmsg['personality'], true))
                    );
                    $person_arr = $person->field('title,name')->where($where)->select();
                    foreach ($person_arr as $key => $value) {
                        //判断是否存在,如果其中有空值报错个性化字段残缺，2030
                        if (!empty($res_person['personality'][$value['name']]) and $res_person['personality'][$value['name']] != "") {
                            $person_arr[$key]['value'] = $res_person['personality'][$value['name']];
                        } else {
                            $person_arr[$key]['value'] = "";
                        }
                    }

                    array_unshift($person_arr, array("title" => "船名", 'name' => 'shipname', "value" => $res_person['shipname']));

                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $person_arr
                    );
                } else {
                    //该作业所属公司没有pdf文件模板  2006
                    $res = array(
                        'code' => $this->ERROR_CODE_RESULT['FIRM_NOT_PDF']
                    );
                }
            } else {
                //返回错误返回码
                $res = $msg;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }


    /**
     * 修改作业
     * @param int uid 用户ID
     * @param int shipid 船ID
     * @param int resultid 计量ID
     * @param string voyage 航次
     * @param string locationname 作业地点
     * @param string start 起运港
     * @param string objective 目的港
     * @param string goodsname 货名
     * @param string transport 运单量
     * @param string imei 标识
     * @param string shipper 发货方
     * @param string feedershipname 海船船名
     * @param string number 编号
     * @param string wharf 海船装运码头
     * @param string volume 海船发货量
     * @param string inspection 海船商检量
     * @param string sumload 总装载量
     * @return array
     * @return @param code 返回码
     */
    public function editresult()
    {
        if (I('post.uid') and I('post.shipid') and I('post.imei') and I('post.resultid')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、公司状态、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $data = I('post.');
                $res = $result->editResult($data);
            } else {
                //错误信息
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 水尺录入(修改)
     * @param int uid 用户ID
     * @param int resultid 计量ID
     * @param string imei 标识
     * @param float forntleft 前左
     * @param float forntright 前右
     * @param float centerleft 中左
     * @param float centerright 中右
     * @param float afterleft 后左
     * @param float afterright 后右
     * @param int solt 作业前/后状态 1：前 2：后
     * @param string temperature 温度
     * @param array firstfiles 首吃水图片
     * @param array tailfiles 尾吃水图片
     * @param float(9,4) density 密度
     * @return @param array
     * @return @param code 返回码
     */
    public function fornt()
    {
        //判断前左、右是否有
        if (I('post.forntleft') != null or I('post.forntright') != null) {
            if (I('post.afterleft') != null or I('post.afterright') != null) {
                if (I('post.uid') and I('post.resultid') and I('post.solt') and I('post.imei') and I('post.imei')!==null) {
                    $result = new \Common\Model\OilWorkModel();
                    $data = I('post.');
                    unset($data['temperature']);
                    $validata_result = validata_range($data);
                    if($validata_result['error']){
                        //提交的值超出约定范围，报错2044
                        M()->rollback();
                        exit(jsonreturn(array('code'=>$this->ERROR_CODE_RESULT['OUT_OF_RANGE'],'msg'=>"提交的信息中存在超出范围的值，请检查",'key'=>$validata_result['key'])));
                    }
                    $data['temperature'] = I('post.temperature');

                    /*
                     *   限制空高和温度的精度，要求符合2012-12-12发布的国家出入境检验检
                     * 疫行业标准，若标准更新，请以新标准为准
                     * 船舶水尺：0.01
                     */
                    $data['forntleft'] = round($data['forntleft'], 2);
                    $data['forntright'] = round($data['forntright'], 2);
                    $data['afterleft'] = round($data['afterleft'], 2);
                    $data['afterright'] = round($data['afterright'], 2);

                    $result_info = $result->field('del_sign,finish_sign')->where(array('id' => intval(I('post.resultid'))))->find();
                    //如果作业被删除了，不可以操作 2033
                    if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
                    //如果作业结束了，不可以操作 2034
                    if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));

                    $res = $result->forntOperation($data);
                } else {
                    //参数不正确，参数缺失    4
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
                    );
                }
            } else {
                //参数不正确，参数缺失    4
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
                );
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 照片文件上传
     * @param int result_id 作业ID
     * @param files 文件
     */
    public function upload()
    {
        // if (empty($_FILES)) {
        //     // 没有上传文件  10
        //     $res = array (
        //           "code" => $this->ERROR_CODE_COMMON['NO_FILE']
        //       );
        // } else {
        //     $uploaddir = './Upload/result/'.date('Y-m-d').'/';

        //     if(!is_dir($uploaddir)){
        //         mkdir($uploaddir,0777,true);
        //     }
        //     static $success = 0;
        //     static $failure = 0;
        //     $files = array();
        //     foreach ($_FILES as $key => $value){
        //         //循环遍历数据
        //         $tmp = $value['name'];//获取上传文件名
        //         $tmpName = $value['tmp_name'];//临时文件路径
        //         //上传的文件会被保存到php临时目录，调用函数将文件复制到指定目录
        //         $dir=$uploaddir. date('YmdHis') . '_' . $tmp;
        //         if (move_uploaded_file($tmpName,$dir)) {
        //             $files[] = array(
        //                 'name'  =>  $value['name'],
        //                 'filename'=>  $dir
        //                 );
        //             $success++;
        //         } else {
        //             $failure++;
        //         }
        //     }
        //     if (count($_FILES) == $success) {
        //         //成功 1
        //         $res = array(
        //             'code'   => $this->ERROR_CODE_COMMON['SUCCESS'],
        //             'content'=> $files
        //         );
        //     }else{
        //         // 上传失败  9
        //         $res = array(
        //             'code'   => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
        //         );
        //     }
        // }

        // base64拍照
        if (I('post.file')) {
            $picture = I('post.file');
            $path_s = './Upload/result/' . date('Y-m-d') . '/';
            $empty_img = array();
            foreach ($picture as $e) {
                // writeLog('base64图片：'.$e);
                // 上传一张图片
                $res_s = base64_upload($e, $path_s);
                // writeLog('base64图片：'.implode('---', $res_s));
                if ($res_s ['code'] != 0) {
                    // 上传失败  9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                    );
                    // 需要删除已上传的照片
                    foreach ($empty_img as $k => $v) {
                        @unlink($path_s . $v);
                    }
                    echo jsonreturn($res);
                    exit ();
                } else {
                    // 文件名称
                    $name = explode('.', $res_s['name']);
                    // 上传成功的图片
                    $empty_img [] = array('name' => $name['1'], 'filename' => $res_s['file']);
                }
                $res_s = '';
            }
            //成功 1
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                'content' => $empty_img
            );
        } else {
            //参数不正确，参数缺失    5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 水尺查询
     * @param string resultid 计量ID
     * @param string imei 标识
     * @param int uid 用户ID
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function forntsearch()
    {
        if (I('post.resultid') and I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $res = $result->forntsearch(I('post.resultid'));
            } else {
                // 错误信息
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 新水尺查询
     * @param string resultid 计量ID
     * @param string imei 标识
     * @param int uid 用户ID
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function Newforntsearch()
    {
        if (I('post.resultid') and I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $res = $result->forntsearch1(I('post.resultid'));
            } else {
                // 错误信息
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 查看作业详情
     * @param string resultid 计量ID
     * @param string imei 标识
     * @param int uid 用户ID
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function resultsearch()
    {
        #todo 针对油船修改
        if (I('post.resultid') and I('post.uid') and I('post.imei')) {
            $result = new \Common\Model\OilWorkModel();
            $res = $result->resultsearch(I('post.resultid'), I('post.uid'), I('post.imei'));
            foreach ($res['resultmsg'] as $k=>$v){
                foreach ($v as $key=>$value){
                    $res['resultmsg'][$k][$key]['standardcapacity'] = $value['new_standardcapacity'];
                }
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 生成pdf
     * @param int resultid 计量ID
     * @param int uid 用户ID
     * @param string imei 标识
     * @return array
     * @return @param code 返回码
     * @return @param filename 文件名
     */
    public function pdf()
    {
        #todo 针对油船修改
        if (I('post.resultid') and I('post.uid') and I('post.imei')) {
            //获取数据
            $result = new \Common\Model\OilWorkModel();
            $arr = $result->resultsearch(I('post.resultid'), I('post.uid'), I('post.imei'));
            if ($arr['code'] == '1') {
                // 获取公司pdf方法名
                $firm = new \Common\Model\FirmModel();
                $firmmsg = $firm
                    ->alias('f')
                    ->field('f.pdf,f.personality')
                    ->join('left join user u on u.firmid = f.id')
                    ->where(array('u.id' => I('post.uid')))
                    ->find();
                if ($firmmsg !== false and !empty($firmmsg['pdf']) and !empty($firmmsg['personality'])) {
                    //获取用户公司的自定义字段，如果存在没填写的字段则不允许打印pdf
                    $person = new \Common\Model\PersonalityModel();
                    $where = array(
                        'id' => array('in', json_decode($firmmsg['personality'], true))
                    );
                    $person_arr = $person->field('name')->where($where)->select();
                    foreach ($person_arr as $key => $value) {
                        //判断是否存在,如果其中有空值报错个性化字段残缺，2030
                        if (empty($arr['personality'][$value['name']]) or $arr['personality'][$value['name']] == "") exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['PERSON_INCOMPLETE'])));
                    }
                    //引入了https，做https协议的适配
                    $is_https = I('post.minipost');
                    if ($is_https) {
                        $uid = I('post.uid');
                        $resultid = I('post.resultid');
                        $filepath = "miniprogram/" . $uid . "/";
                        $PDFname = $resultid . ".pdf";
                        //如果是https，则返回全部的
                        $filename = pdf($arr, $firmmsg, "", $filepath, $PDFname);//生成PDF文件
                        if ($filename != '') {
                            $filename = '/Public/pdf/' . $filepath . $PDFname;
                        }
                    } else {
                        $filename = pdf($arr, $firmmsg);//生成PDF文件
                    }

                    if ($filename != '') {
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'filename' => $filename
                        );
                    } else {
                        //pdf文件失败 2005
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['NOT_FILE']
                        );
                    }
                } else {
                    //该作业所属公司没有pdf文件模板  2006
                    $res = array(
                        'code' => $this->ERROR_CODE_RESULT['FIRM_NOT_PDF']
                    );
                }
            } else {
                $res = $arr;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 获取用户可以操作的船列表
     * @param int uid 用户ID
     * @param string imei 标识
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     * @
     */
    public function shiplist()
    {
        if (I('post.uid') and I('post.imei')) {
            $ship = new \Common\Model\ShipFormModel();
            //只获取有表船
            $res = $ship->shiplist(I('post.uid'), I('post.imei'),1);
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 获取用户可以查询的船列表
     * @param int uid 用户ID
     * @param string imei 标识
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     * @
     */
    public function shipSearchList()
    {
        if (I('post.uid') and I('post.imei')) {
            $ship = new \Common\Model\ShipFormModel();
            $res = $ship->shipSearchList(I('post.uid'), I('post.imei'),1);
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 获取船的舱列表
     * @param int uid 用户ID
     * @param int shipid 船ID
     * @param varchar imei 标识
     */
    public function cabinlist()
    {
        if (I('post.uid') and I('post.imei') and I('post.shipid')) {
            $cabin = new \Common\Model\CabinModel();
            $res = $cabin->cabinlist(I('post.'));
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 获取版本号
     * @param int uid 用户ID
     * @param string imei 标识
     * @param string editionnum 版本号
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function get_config()
    {
        if (I('post.uid') and I('post.imei') and I('post.editionnum') !== null) {
            $user = new \Common\Model\UserModel();
            $uid = I('post.uid');
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges($uid, I('post.imei'));
            if ($msg1['code'] == '1') {
                $config = M('config');
                $where = array(
                    'editionnum' => trimall(I('post.editionnum'))
                );
                $msg = $config
                    ->where($where)
                    ->order('id desc')
                    ->find();
                if (empty($msg) || $msg == false) {
                    //数据库连接错误   3
                    $res = array(
                        'code' => 3
                    );
                } else {
                    $res = array(
                        'code' => '1',
                        'content' => $msg
                    );
                }
            } else {
                //未到期/状态禁止/标识错误
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 修改作业指令备注
     * @param int uid 用户ID
     * @param string imei 标识
     * @param int resultid 计量ID
     * @param string remark 备注
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function editRemark()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')) {
            $user = new \Common\Model\UserModel();
            $uid = I('post.uid');
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges($uid, I('post.imei'));
            if ($msg1['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $data = array(
                    'remark' => I('post.remark')
                );
                $map = array(
                    'id' => I('post.resultid')
                );
                $msg = $result->editData($map, $data);
                if ($msg !== false) {
                    //成功 1
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                    );
                } else {
                    //数据库连接错误   3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                //未到期/状态禁止/标识错误
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }


    /**
     * 判断是否有统计
     *
     * @param int uid 用户ID
     * @param string imei 标识
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function is_statistics()
    {
        if (I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $r = $user
                    ->alias('u')
                    ->field('f.is_statistics')
                    ->join('left join firm f on f.id = u.firmid')
                    ->where(array('u.id' => I('post.uid')))
                    ->find();
                if ($r !== false and !empty($r)) {
                    //成功 1
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $r['is_statistics']
                    );
                } else {
                    //其他错误 2
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
                        'content' => '1'
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 新增季度统计数据
     * @param int uid 用户ID
     * @param string imei 标识
     * @param int time 时间
     * @param string shipname 船名
     * @param float pretend 装载
     * @param float discharge 卸载
     * @param float deliver 发货量
     * @param float status 盈亏
     * @param string voyage 航次
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     * */
    public function statistics()
    {
        #TODO 是否需要针对油船修改，如果需要修改则修改
        if (I('post.uid') and I('post.time') and I('post.shipname') !== null and I('post.pretend') !== null and I('post.discharge') !== null and I('post.deliver') !== null and I('post.status') !== null and I('post.imei') and I('post.voyage') !== null) {
            $user = new \Common\Model\UserModel();
            $uid = I('post.uid');
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges($uid, I('post.imei'));
            if ($msg1['code'] == '1') {
                $data = array(
                    'time' => strtotime(I('post.time')),
                    'shipname' => I('post.shipname'),
                    'pretend' => I('post.pretend'),
                    'discharge' => I('post.discharge'),
                    'deliver' => I('post.deliver'),
                    'status' => I('post.status'),
                    'voyage' => I('post.voyage'),
                    'firmid' => $msg1['content']
                );

                //添加数据
                $statistics = new \Common\Model\StatisticsModel();
                // 对数据进行验证
                if (!$statistics->create($data)) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    // $this->error($statistics->getError());
                    //数据库连接错误   3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                } else {
                    // 验证通过 可以进行其他数据操作
                    $res = $statistics->addData($data);
                    if ($res !== false) {
                        //成功 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                        );
                    } else {
                        //数据库连接错误   3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                        );
                    }
                }
            } else {
                // 错误信息
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 统计查询
     * @param int uid 用户ID
     * @param string imei 标识
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     * */
    public function searchcount()
    {
        if (I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            $uid = I('post.uid');
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges($uid, I('post.imei'));
            if ($msg1['code'] == '1') {
                $where = "1 and firmid='" . $msg1['content'] . "'";
                // 条件---开始时间
                if (I('post.starttime')) {
                    $starttime = strtotime(I('post.starttime'));
                    $where .= " and time >= $starttime";
                }
                //条件---结束时间
                if (I('post.endtime')) {
                    $endtime = strtotime(I('post.endtime'));
                    $where .= " and time <= $endtime";

                }
                $result = new \Common\Model\OilWorkModel();
                $statistics = new \Common\Model\StatisticsModel();
                // 获取数据
                $list = $statistics
                    ->field('*')
                    ->where($where)
                    ->select();
                // 计算合计
                $sum = $statistics
                    ->field('sum(pretend) as sumpretend,sum(discharge) as sumdischarge,sum(deliver) as sumdeliver,sum(status) as sumstatus')
                    ->where($where)
                    ->select();
                if ($list !== false) {
                    // 数据处理
                    $list = dateRemoveZero($list);
                    $sum = dateRemoveZero($sum);
                    // $res = $list;
                    //计算船次
                    $sum[0]['countsum'] = count($list);
                    $filename = countpdf($list, $sum);
                    if ($filename != '') {
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'filename' => $filename
                        );
                    } else {
                        //pdf文件失败 2005
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['NOT_FILE']
                        );
                    }
                } else {
                    //数据库连接错误   3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            } else {
                //未到期/状态禁止/标识错误
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 计算
     * @param int cabinid 舱ID
     * @param int uid 用户ID
     * @param int resultid 计量ID
     * @param float sounding 实高
     * @param float ullage 空高
     * @param varchar temperature 温度
     * @param int solt 1:作业前；2:作业后
     * @param varchar imei 标识
     * @param int shipid 船ID
     * @param float altitudeheight 基准高度
     * @param qufen diliang:底量计算 rongliang:容量计算
     * @param int quantity 1：计算底量；2：不计算底量
     * @param int is_pipeline 是否包含管线 1：是；2：否
     * @param varchar soundingfile 实高图片
     * @param varchar ullagefile 空高图片
     * @param varchar temperaturefile 温度图片
     * @return @param array
     * @return @param code
     */
    public function reckon()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt')
            and I('post.shipid') and I('post.cabinid') and I('post.altitudeheight')
            and I('post.qufen') and I('post.quantity') and I('post.is_work')
            and I('post.is_pipeline') and I('post.water_sounding')!==null and I('post.ullage')!==null and I('post.ob_temperature')!==null and I('post.ob_density')!==null) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $result = new \Common\Model\OilWorkModel();
                $result_info = $result->field('del_sign,finish_sign')->where(array('id' => intval(I('post.resultid'))))->find();
                //如果作业被删除了，不可以操作 2033
                if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
                //如果作业结束了，不可以操作 2034
                if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));

                $data = I('post.');
                // 安卓端基准高度在计算底量书底量计算时提交错误
                $ship = new \Common\Model\ShipFormModel();
                $suanfa = $ship
                    // ->where(array('id'=>$data['shipid']))
                    ->getFieldById($data['shipid'], 'suanfa');
                $cabin = new \Common\Model\CabinModel();
                if ($data['qufen'] == 'diliang' && ($suanfa == 'c' || $suanfa == 'd')) {
                    $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'dialtitudeheight');
                } else {
                    $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'altitudeheight');
                }
                /*
                 *   限制空高和温度的精度，要求符合2012-12-12发布的国家出入境检验检
                 * 疫行业标准，若标准更新，请以新标准为准
                 * 空高、液深限制：0.001
                 * 温度限制：0.1
                */
                $data['ullage'] = round($data['ullage'], 3);
                $data['sounding'] = round($data['sounding'], 3);
                $data['water_ullage'] = round($data['water_ullage'], 3);
                $data['temperature'] = round($data['temperature'], 1);
                //根据作业状态、作业ID、舱id判断作业是否重复
                $resultlist = new \Common\Model\ResultlistModel();
                $where3 = array(
                    'solt' => I('post.solt'),
                    'cabinid' => I('post.cabinid'),
                    'resultid' => I('post.resultid'),
                );
                $r = $resultlist
                    ->where($where3)
                    ->count();
                if ($r > 0 and I('post.is_fugai') == 'N') {
                    //作业重复 2003
                    $res = array(
                        'code' => $this->ERROR_CODE_RESULT['IS_REPEAT']
                    );
                } else {
                    // 允许重复，判断本次是否作业计算
                    if (I('post.is_work') == '1') {
                        // 本次作业，计算数据
                        // 允许覆盖
                        if (I('post.solt') == '2') {
                            //如果是舱作业后数据，判断该舱是否有作业前数据

                            $where = array(
                                'solt' => '1',
                                'cabinid' => I('post.cabinid'),
                                'resultid' => I('post.resultid')
                            );
                            $arr = $resultlist
                                ->where($where)
                                ->count();
                            if ($arr != 1) {
                                //没有作业前数据 2008
                                $res = array(
                                    'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
                                );
                            } else {
                                //判断空高是否在基准高度与0之内
                                if ($data['ullage'] >= 0 and I('post.ullage') <= $data['altitudeheight'] and $data['water_ullage'] >= 0 ) {
                                    $res = $result->reckon($data);
                                } else {
                                    //空高有误 2009
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                                    );
                                }
                            }
                        } else {
                            //判断空高是否在基准高度与0之内
                            if (I('post.ullage') >= 0 and I('post.ullage') <= $data['altitudeheight']) {
                                $res = $result->reckon($data);
                            } else {
                                //空高有误 2009
                                $res = array(
                                    'code' => $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                                );
                            }
                        }
                    } else {
                        // 本次不作业，不传空高、实高、温度值；标志为2时，将作业前的数据作为本次作业数据
                        // 获取舱作业前的数据
                        $map = array(
                            'solt' => '1',
                            'cabinid' => I('post.cabinid'),
                            'resultid' => I('post.resultid')
                        );
                        $countqian = $resultlist
                            ->where($map)
                            ->count();
                        if ($countqian !== '1') {
                            //没有作业前数据 2008
                            $res = array(
                                'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
                            );
                        } else {
                            $list = $resultlist
                                ->where($map)
                                ->find();
                            // 作业数据
                            $data = array(
                                'sounding' => $list['sounding'],
                                'ullage' => $list['ullage'],
                                'listcorrenction' => $list['listcorrenction'],
                                'time' => $list['time'],
                                'temperature' => $list['temperature'],
                                'solt' => '2',
                                'resultid' => $list['resultid'],
                                'cabinid' => $list['cabinid'],
                                'standardcapacity' => $list['standardcapacity'],
                                'volume' => $list['volume'],
                                'expand' => $list['expand'],
                                'correntkong' => $list['correntkong'],
                                'cabinweight' => $list['cabinweight'],
                                'is_work' => '2'
                            );
                            // 根据计量ID获取密度，
                            $msg = $result
                                ->field('houdensity')
                                ->where(array('id' => $data['resultid']))
                                ->find();
                            if ($msg == false || empty($msg)) {
                                //数据库连接错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                                );
                            } else {
                                $midu = $msg['houdensity'];
                                // 计算舱作业几条数据
                                $map1 = array(
                                    'solt' => '2',
                                    'cabinid' => I('post.cabinid'),
                                    'resultid' => I('post.resultid')
                                );
                                $nums = $resultlist->where($map1)->count();
                                $trans = M();
                                $trans->startTrans();   // 开启事务
                                if ($nums == '1') {
                                    // 获取舱作业ID
                                    $listid = $resultlist->where($map1)->getField('id');
                                    //修改数据
                                    $resultlist->editData($map1, $data);
                                } else {
                                    //新增数据
                                    $listid = $resultlist->add($data);
                                }
                                // 计算所有舱作业后总标准容量
                                $wheres1 = array(
                                    'resultid' => $data['resultid'],
                                    'solt' => '2'
                                );
                                $allweight = $resultlist
                                    ->field("sum(standardcapacity) as sums")
                                    ->where($wheres1)
                                    ->select();
                                //根据总标准容量*密度得到作业前/后总的货重
                                $total = round($allweight[0]['sums'] * ($midu - 0.0011), 3);

                                // 作业后（需要计算总货重）
                                // 修改作业后总货重、总容量
                                $hou = array(
                                    'houweight' => round($allweight[0]['sums'], 3),
                                    'houtotal' => $total
                                );
                                $r = $result->where(array('id' => $data['resultid']))->save($hou);
                                if ($r !== false) {
                                    // 获取作业前、后的总货重
                                    $sunmmsg = $result
                                        ->field('qiantotal,houtotal')
                                        ->where(array('id' => $data['resultid']))
                                        ->find();
                                    // 计算总容量 后-前
                                    $weight = round(($sunmmsg['houtotal'] - $sunmmsg['qiantotal']), 3);
                                    // 修改总货重
                                    $res1 = $result
                                        ->where(array('id' => $data['resultid']))
                                        ->save(array('weight' => $weight));
                                    if ($res1 !== false) {
                                        $trans->commit();
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        );
                                        // 获取作业前的照片
                                        $files = M('resultlist_img')
                                            ->where(array('resultlist_id' => $list['id']))
                                            ->select();
                                        if (!empty($files)) {
                                            foreach ($$files as $key => $value) {
                                                $filedata[] = array(
                                                    'img' => $value['img'],
                                                    'types' => $value['types'],
                                                    'resultlist_id' => $listid

                                                );
                                            }
                                            M('resultlist_img')->addAll($filedata);
                                        }
                                    } else {
                                        $trans->rollback();
                                        //其它错误  2
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                        );
                                    }
                                } else {
                                    $trans->rollback();
                                    //其它错误  2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                                    );
                                }
                                $data['is_pipeline'] = I('post.is_pipeline');
                            }
                        }
                    }
                    // 计算成功记录数据
                    if ($res['code'] == '1') {
                        // 判断本次是否作业，不作业时获取作业前的数据照片

                        //判断数据是否已记录
                        $map = array(
                            'solt' => I('post.solt'),
                            'cabinid' => I('post.cabinid'),
                            'resultid' => I('post.resultid'),
                            'is_work' => I('post.is_work')
                        );

                        $num = M('resultrecord')->where($map)->count();
                        if ($num > 0) {
                            M('resultrecord')->where($map)->save($data);
                        } else {
                            M('resultrecord')->add($data);
                        }
                    }
                }
            } else {
                // 未到期/状态禁止/标识错误
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

//    /**
//     * 记录测量数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param float sounding 实高
//     * @param float ullage 空高
//     * @param varchar temperature 温度
//     * @param int solt 1:作业前；2:作业后
//     * @param varchar imei 标识
//     * @param int shipid 船ID
//     * @param float altitudeheight 基准高度
//     * @param qufen diliang:底量计算 rongliang:容量计算
//     * @param int quantity 1：计算底量；2：不计算底量
//     * @param int is_pipeline 是否有管线 1：有；2：没有；
//     * @param varcher is_fugai 是否覆盖  Y:覆盖；N：不覆盖
//     * @return @param array
//     * @return @param code
//     * */
//    public function measure()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid')
//            and I('post.solt') and I('post.shipid') and I('post.cabinid')
//            and I('post.sounding') !== '' and I('post.ullage') !== ''
//            and I('post.temperature') !== '' and I('post.altitudeheight') !== ''
//            and I('post.qufen') and I('post.quantity') and I('post.is_pipeline')
//            and I('is_work')) {
//            $user = new \Common\Model\UserModel();
//            $uid = I('post.uid');
//            // 判断用户状态、是否到期、标识比对
//            $msg1 = $user->is_judges($uid, I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $result = new \Common\Model\OilWorkModel();
//                $result_info = $result->field('del_sign,finish_sign,qianchi,houchi')->where(array('id' => intval(I('post.resultid'))))->find();
//                //如果作业被删除了，不可以操作 2033
//                if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
//                //如果作业结束了，不可以操作 2034
//                if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));
//
//                //初始化记录录入过程
//                $process = array();
//                $ship = new \Common\Model\ShipFormModel();
//                $resultrecord = M('resultrecord');
//
//                $shipmsg = $ship
//                    ->field('suanfa')
//                    ->where(array('id' => I('post.shipid')))
//                    ->find();
//                $data = I('post.');
//
//                $cabin = new \Common\Model\CabinModel();
//                // 安卓端基准高度在计算底量书底量计算时提交错误
//                if ($data['qufen'] == 'diliang' && ($shipmsg['suanfa'] == 'c' || $shipmsg['suanfa'] == 'd')) {
//                    $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'dialtitudeheight');
//                } else {
//                    $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'altitudeheight');
//                }
//
//                /*
//                 *   限制空高和温度的精度，要求符合2012-12-12发布的国家出入境检验检
//                 * 疫行业标准，若标准更新，请以新标准为准
//                 * 空高、液深限制：0.001
//                 * 温度限制：0.1
//                */
//                $data['ullage'] = round($data['ullage'], 3);
//                $data['sounding'] = round($data['sounding'], 3);
//                $data['temperature'] = round($data['temperature'], 1);
//
//                //判断空高是否在基准高度与0之内
//                if ($data['ullage'] < 0 or $data['ullage'] > $data['altitudeheight']) {
//                    //空高有误 2009
//                    $res = array(
//                        'code' => $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
//                    );
//                    M()->rollback();
//                    exit(jsonreturn($res));
//                }
//
//                // 查找数据条件
//                $where = array(
//                    'resultid' => $data['resultid'],
//                    'cabinid' => $data['cabinid'],
//                    'solt' => $data['solt'],
//                );
//
//                //获取原来的计算过程,没有就初始化
//                $old_process = $resultrecord->field('process')->where($where)->find();
//                if ($old_process !== false) {
//                    $process = json_decode($old_process['process'], true);
//                    if ($process == null) {
//                        $process = array();
//                    }
//                }
//
//                $bilge_stock = '';
//                $pipeline_stock = '';
//                $soltType = '';
//
//                //将某些变量格式化，方便读取计算过程,格式化是否有底量
//                if ($data['quantity'] == "1") {
//                    $bilge_stock = 'true';
//                } else {
//                    $bilge_stock = 'false';
//                }
//
//                //格式化是否有管线容量
//                if ($data['is_pipeline'] == "1") {
//                    $pipeline_stock = 'true';
//                } else {
//                    $pipeline_stock = 'false';
//                }
//
//                //格式化作业状态
//                if ($data['solt'] == "1") {
//                    $soltType = '作业前';
//                } else {
//                    $soltType = '作业后';
//                }
//
////                $process .= "Received meansure_value:\r\n\tullage=" . $data['ullage'] . ", sounding=" . $data['sounding'] . ", cabin_temperature=" . $data['temperature'] . ", soltType=," . $soltType . "\r\n\taltitudeheight=" . $data['altitudeheight'] . ", table_used=" . $data['qufen'] . ", bilge_stock=" . $bilge_stock . ", pipeline_stock=" . $pipeline_stock . ",\r\n";
//                $process['ullage'] = $data['ullage'];
//                $process['sounding'] = $data['sounding'];
//                $process['Cabin_temperature'] = $data['temperature'];
//                $process['method'] = $soltType;
//                $process['altitudeheight'] = $data['altitudeheight'];
//                $process['table_used'] = $data['qufen'];
//                $process['bilge_stock'] = $bilge_stock;
//                $process['pipeline_stock'] = $pipeline_stock;
//
//
////                // 判断数据是否存在
////                $where = array(
////                    'resultid' => $data['resultid'],
////                    'cabinid' => $data['cabinid'],
////                    'solt' => $data['solt'],
////                );
//
//                // 获取作业记录数据个数
//                $rrecord = $resultrecord
//                    ->where($where)
//                    ->count();
//                if ($rrecord > 0 and I('post.is_fugai') == 'N') {
//                    // 作业记录存在且不覆盖
//                    // 作业重复 2003
//                    $res = array(
//                        'code' => $this->ERROR_CODE_RESULT['IS_REPEAT']
//                    );
//                } elseif ($rrecord > 0 and I('post.is_fugai') == 'Y') {
//                    // 作业数据记录存在并且覆盖数据
//                    // 允许覆盖
//                    if (I('post.is_work') == '2') {
//                        $resultlist = new \Common\Model\ResultlistModel();
//                        $map = array(
//                            'solt' => '1',
//                            'cabinid' => I('post.cabinid'),
//                            'resultid' => I('post.resultid')
//                        );
//                        $countqian = $resultlist
//                            ->where($map)
//                            ->count();
//                        if ($countqian !== '1') {
//                            //没有作业前数据 2008
//                            $res = array(
//                                'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
//                            );
//                        } else {
//                            $list = $resultlist
//                                ->where($map)
//                                ->find();
//                            // 作业数据
//                            $data = array(
//                                'sounding' => $list['sounding'],
//                                'ullage' => $list['ullage'],
//                                'listcorrenction' => $list['listcorrenction'],
//                                'time' => $list['time'],
//                                'temperature' => $list['temperature'],
//                                'solt' => '2',
//                                'resultid' => $list['resultid'],
//                                'cabinid' => $list['cabinid'],
//                                'standardcapacity' => $list['standardcapacity'],
//                                'volume' => $list['volume'],
//                                'expand' => $list['expand'],
//                                'correntkong' => $list['correntkong'],
//                                'cabinweight' => $list['cabinweight'],
//                                'is_work' => '2'
//                            );
//                            // 根据计量ID获取密度，
//                            $msg = $result
//                                ->field('houdensity')
//                                ->where(array('id' => $data['resultid']))
//                                ->find();
//                            if ($msg == false || empty($msg)) {
//                                //数据库连接错误   3
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
//                                );
//                            } else {
//                                $midu = $msg['houdensity'];
//                                // 计算舱作业几条数据
//                                $map1 = array(
//                                    'solt' => '2',
//                                    'cabinid' => I('post.cabinid'),
//                                    'resultid' => I('post.resultid')
//                                );
//                                $nums = $resultlist->where($map1)->count();
//                                $trans = M();
//                                $trans->startTrans();   // 开启事务
//                                if ($nums == '1') {
//                                    // 获取舱作业ID
//                                    $listid = $resultlist->where($map1)->getField('id');
//                                    //修改数据
//                                    $resultlist->editData($map1, $data);
//                                } else {
//                                    //新增数据
//                                    $listid = $resultlist->add($data);
//                                }
//                                // 计算所有舱作业后总标准容量
//                                $wheres1 = array(
//                                    'resultid' => $data['resultid'],
//                                    'solt' => '2'
//                                );
//                                $allweight = $resultlist
//                                    ->field("sum(standardcapacity) as sums")
//                                    ->where($wheres1)
//                                    ->select();
//                                //根据总标准容量*密度得到作业前/后总的货重
//                                $total = round($allweight[0]['sums'] * ($midu - 0.0011), 3);
//
//                                // 作业后（需要计算总货重）
//                                // 修改作业后总货重、总容量
//                                $hou = array(
//                                    'houweight' => round($allweight[0]['sums'], 3),
//                                    'houtotal' => $total
//                                );
//                                $r = $result->where(array('id' => $data['resultid']))->save($hou);
//                                if ($r !== false) {
//                                    // 获取作业前、后的总货重
//                                    $sunmmsg = $result
//                                        ->field('qiantotal,houtotal')
//                                        ->where(array('id' => $data['resultid']))
//                                        ->find();
//                                    // 计算总容量 后-前
//                                    $weight = round(($sunmmsg['houtotal'] - $sunmmsg['qiantotal']), 3);
//                                    // 修改总货重
//                                    $res1 = $result
//                                        ->where(array('id' => $data['resultid']))
//                                        ->save(array('weight' => $weight));
//                                    if ($res1 !== false) {
//                                        $trans->commit();
//                                        //不作业查询累积数据
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                        );
//                                        // 获取作业前的照片
//                                        $files = M('resultlist_img')
//                                            ->where(array('resultlist_id' => $list['id']))
//                                            ->select();
//                                        if (!empty($files)) {
//                                            foreach ($files as $key => $value) {
//                                                $filedata[] = array(
//                                                    'img' => $value['img'],
//                                                    'types' => $value['types'],
//                                                    'resultlist_id' => $listid
//
//                                                );
//                                            }
//                                            M('resultlist_img')->addAll($filedata);
//                                        }
//                                    } else {
//                                        $trans->rollback();
//                                        //其它错误  2
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                        );
//                                    }
//                                } else {
//                                    $trans->rollback();
//                                    //其它错误  2
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                    );
//                                }
//                                $data['is_pipeline'] = I('post.is_pipeline');
//                            }
//
//                            // 计算成功记录数据
//                            if ($res['code'] == '1') {
//                                // 判断本次是否作业，不作业时获取作业前的数据照片
//                                //判断数据是否已记录
//                                $map = array(
//                                    'solt' => '1',
//                                    'cabinid' => I('post.cabinid'),
//                                    'resultid' => I('post.resultid')
//                                );
//
//                                $resultrecord = M('resultrecord');
//                                $num = $resultrecord->where($map)->count();
//                                if ($num > 0) {
//
//                                    $datar = $resultrecord->where($map)->find();
//                                    $datar['solt'] = '2';
//                                    $datar['process'] = urlencode("is_work=2 then \r\n 作业后数据等于作业前数据");
//                                    unset($datar['id']);
//                                    $datar['listcorrection'] = "";
//
//                                    $map1 = array(
//                                        'solt' => '2',
//                                        'cabinid' => I('post.cabinid'),
//                                        'resultid' => I('post.resultid')
//                                    );
//
//                                    $num1 = $resultrecord->where($map1)->count();
//                                    if ($num1 > 0) {
//                                        $resultrecord->where($map1)->save($datar);
//                                    } else {
//                                        $resultrecord->add($datar);
//                                    }
//                                } else {
//                                    //如果不作业就不可以没有作业前数据 2008
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
//                                    );
//                                }
//                            }
//                        }
//                    } else {
//                        if (I('post.solt') == '2') {
//                            //如果是舱作业后数据，判断该舱是否有作业前数据
//                            $where = array(
//                                'solt' => '1',
//                                'cabinid' => I('post.cabinid'),
//                                'resultid' => I('post.resultid')
//                            );
//                            $resultlist = new \Common\Model\ResultlistModel();
//                            $arr = $resultlist
//                                ->where($where)
//                                ->count();
//
//                            if ($arr != 1) {
//                                //没有作业前数据 2008
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
//                                );
//                            } else {
//                                /*
//                                 * 查询累积数据
//                                 */
//                                $trim_data = $result->get_cumulative_trim_data(I('post.cabinid'), $data['ullage'], $result_info['houchi'], $data['qufen']);
//                                //如果查询到数据，合并需要存入的累积数据，等待下一次查询
//                                if (false !== $trim_data) {
//                                    $data = array_merge($trim_data, $data);
//                                }
//
//                                $data['houprocess'] = json_encode($process);
//                                //作业后数据修改
//                                $where['solt'] = '2';
//                                $id = $resultrecord
//                                    ->where($where)
//                                    ->save($data);
//
//                                if ($id !== false) {
//                                    $resultdata = array(
//                                        'ullage' => $data['ullage'],
//                                        'sounding' => $data['sounding'],
//                                        'temperature' => $data['temperature'],
//                                        'is_work' => 1
//                                    );
//                                    $resultr = $resultlist->editData($where, $resultdata);
//                                    if ($resultr !== false) {
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                            'suanfa' => $shipmsg['suanfa']
//                                        );
//                                    } else {
//                                        //其他错误
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                            'sign' => 6,
//                                        );
//                                    }
//                                    /*$res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                        'suanfa' => $shipmsg['suanfa']
//                                    );*/
//                                } else {
//                                    //其他错误
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                        'sign' => 1,
//                                    );
//                                }
//                            }
//                        } else {
//
//                            /*
//                             * 查询累积数据
//                             */
//                            $trim_data = $result->get_cumulative_trim_data(I('post.cabinid'), $data['ullage'], $result_info['houchi'], $data['qufen']);
//                            //如果查询到数据，合并需要存入的累积数据，等待下一次查询
//                            if (false !== $trim_data) {
//                                $data = array_merge($trim_data, $data);
//                            }
//
//                            $data['qianprocess'] = json_encode($process);
//                            // 修改作业前数据
//                            $id = $resultrecord
//                                ->where($where)
//                                ->save($data);
//                            if ($id !== false) {
//                                $resultdata = array(
//                                    'ullage' => $data['ullage'],
//                                    'sounding' => $data['sounding'],
//                                    'temperature' => $data['temperature'],
//                                    'is_work' => 1
//                                );
//
//                                $resultlist = new \Common\Model\ResultlistModel();
//                                $resultr = $resultlist->editData($where, $resultdata);
//                                if ($resultr !== false) {
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                        'suanfa' => $shipmsg['suanfa']
//                                    );
//                                } else {
//                                    //其他错误
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                        'sign' => 6,
//                                    );
//                                }
////                                $res = array(
////                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
////                                    'suanfa' => $shipmsg['suanfa']
////                                );
//                            } else {
//                                //其他错误
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                    'sign' => 2,
//                                );
//                            }
//                        }
//                    }
//                } elseif ($rrecord == 0) {
//                    if (I('post.is_work') == '2') {
//                        $resultlist = new \Common\Model\ResultlistModel();
//                        $result = new \Common\Model\OilWorkModel();
//                        $map = array(
//                            'solt' => '1',
//                            'cabinid' => I('post.cabinid'),
//                            'resultid' => I('post.resultid')
//                        );
//                        $countqian = $resultlist
//                            ->where($map)
//                            ->count();
//                        if ($countqian !== '1') {
//                            //没有作业前数据 2008
//                            $res = array(
//                                'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
//                            );
//                        } else {
//                            $list = $resultlist
//                                ->where($map)
//                                ->find();
//                            // 作业数据
//                            $data = array(
//                                'sounding' => $list['sounding'],
//                                'ullage' => $list['ullage'],
//                                'listcorrenction' => $list['listcorrenction'],
//                                'time' => $list['time'],
//                                'temperature' => $list['temperature'],
//                                'solt' => '2',
//                                'resultid' => $list['resultid'],
//                                'cabinid' => $list['cabinid'],
//                                'standardcapacity' => $list['standardcapacity'],
//                                'volume' => $list['volume'],
//                                'expand' => $list['expand'],
//                                'correntkong' => $list['correntkong'],
//                                'cabinweight' => $list['cabinweight'],
//                                'is_work' => '2'
//                            );
//                            // 根据计量ID获取密度，
//                            $msg = $result
//                                ->field('houdensity')
//                                ->where(array('id' => $data['resultid']))
//                                ->find();
//                            if ($msg == false || empty($msg)) {
//                                //数据库连接错误   3
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
//                                );
//                            } else {
//                                $midu = $msg['houdensity'];
//                                // 计算舱作业几条数据
//                                $map1 = array(
//                                    'solt' => '2',
//                                    'cabinid' => I('post.cabinid'),
//                                    'resultid' => I('post.resultid')
//                                );
//                                $nums = $resultlist->where($map1)->count();
//                                $trans = M();
//                                $trans->startTrans();   // 开启事务
//                                if ($nums == '1') {
//                                    // 获取舱作业ID
//                                    $listid = $resultlist->where($map1)->getField('id');
//                                    //修改数据
//                                    $resultlist->editData($map1, $data);
//                                } else {
//                                    //新增数据
//                                    $listid = $resultlist->add($data);
//                                }
//                                // 计算所有舱作业后总标准容量
//                                $wheres1 = array(
//                                    'resultid' => $data['resultid'],
//                                    'solt' => '2'
//                                );
//                                $allweight = $resultlist
//                                    ->field("sum(standardcapacity) as sums")
//                                    ->where($wheres1)
//                                    ->select();
//                                //根据总标准容量*密度得到作业前/后总的货重
//                                $total = round($allweight[0]['sums'] * ($midu - 0.0011), 3);
//
//                                // 作业后（需要计算总货重）
//                                // 修改作业后总货重、总容量
//                                $hou = array(
//                                    'houweight' => round($allweight[0]['sums'], 3),
//                                    'houtotal' => $total
//                                );
//                                $r = $result->where(array('id' => $data['resultid']))->save($hou);
//                                if ($r !== false) {
//                                    // 获取作业前、后的总货重
//                                    $sunmmsg = $result
//                                        ->field('qiantotal,houtotal')
//                                        ->where(array('id' => $data['resultid']))
//                                        ->find();
//                                    // 计算总容量 后-前
//                                    $weight = round(($sunmmsg['houtotal'] - $sunmmsg['qiantotal']), 3);
//                                    // 修改总货重
//                                    $res1 = $result
//                                        ->where(array('id' => $data['resultid']))
//                                        ->save(array('weight' => $weight));
//                                    if ($res1 !== false) {
//                                        $trans->commit();
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                        );
//                                        // 获取作业前的照片
//                                        $files = M('resultlist_img')
//                                            ->where(array('resultlist_id' => $list['id']))
//                                            ->select();
//                                        if (!empty($files)) {
//                                            foreach ($$files as $key => $value) {
//                                                $filedata[] = array(
//                                                    'img' => $value['img'],
//                                                    'types' => $value['types'],
//                                                    'resultlist_id' => $listid
//
//                                                );
//                                            }
//                                            M('resultlist_img')->addAll($filedata);
//                                        }
//                                    } else {
//                                        $trans->rollback();
//                                        //其它错误  2
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                        );
//                                    }
//                                } else {
//                                    $trans->rollback();
//                                    //其它错误  2
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                    );
//                                }
//                                $data['is_pipeline'] = I('post.is_pipeline');
//                            }
//
//                            // 计算成功记录数据
//                            if ($res['code'] == '1') {
//                                // 判断本次是否作业，不作业时获取作业前的数据照片
//
//                                //判断数据是否已记录
//                                $map = array(
//                                    'solt' => '1',
//                                    'cabinid' => I('post.cabinid'),
//                                    'resultid' => I('post.resultid')
//                                );
//
//                                $resultrecord = M('resultrecord');
//                                $num = $resultrecord->where($map)->count();
//                                if ($num > 0) {
//
//                                    $datar = $resultrecord->where($map)->find();
//                                    $datar['solt'] = '2';
//                                    $datar['process'] = urlencode("is_work=2 then \r\n 作业后数据等于作业前数据");
//                                    unset($datar['id']);
//                                    $datar['listcorrection'] = "";
//
//                                    $map1 = array(
//                                        'solt' => '2',
//                                        'cabinid' => I('post.cabinid'),
//                                        'resultid' => I('post.resultid')
//                                    );
//
//                                    $num1 = $resultrecord->where($map1)->count();
//                                    if ($num1 > 0) {
//                                        $resultrecord->where($map1)->save($datar);
//                                    } else {
//                                        $resultrecord->add($datar);
//                                    }
//                                } else {
//                                    //如果不作业就不可以没有作业前数据 2008
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
//                                    );
//                                }
//                            }
//                        }
//                    } else {
//
//                        /*
//                         * 查询累积数据
//                         */
//                        $trim_data = $result->get_cumulative_trim_data(I('post.cabinid'), $data['ullage'], $result_info['houchi'], $data['qufen']);
//                        //如果查询到数据，合并需要存入的累积数据，等待下一次查询
//                        if (false !== $trim_data) {
//                            $data = array_merge($trim_data, $data);
//                        }
//
//
//                        $data['qianprocess'] = json_encode($process);
//                        // 没有记录作业数据，新增作业记录数据
//                        $id = $resultrecord
//                            ->add($data);
//                        if ($id !== false) {
//                            $resultdata = array(
//                                'ullage' => $data['ullage'],
//                                'sounding' => $data['sounding'],
//                                'temperature' => $data['temperature'],
//                                'is_work' => 1
//                            );
//
//                            $resultlist = new \Common\Model\ResultlistModel();
//                            $resultr = $resultlist->editData($where, $resultdata);
//
//                            if ($resultr !== false) {
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                    'suanfa' => $shipmsg['suanfa']
//                                );
//                            } else {
//                                //其他错误
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                    'sign' => 6,
//                                );
//                            }
//
//                            /*$res = array(
//                                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                'suanfa' => $shipmsg['suanfa']
//                            );*/
//
//                        } else {
//                            //其他错误 2
//                            $res = array(
//                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                'sign' => 3,
//                            );
//                        }
//                    }
//                } else {
//                    //其他错误  2
//                    $res = array(
//                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                        'sign' => 4,
//                    );
//                }
//            } else {
//                //未到期/状态禁止/标识错误
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失	4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//    /**
//     * 录入书本数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param int shipid 船ID
//     * @param int solt 1:作业前；2:作业后
//     * @param string imei 标识
//     * @param float ullage1 空高1
//     * @param float ullage2 空高2
//     * @param float draft1 吃水差1
//     * @param float draft2 吃水差2
//     * @param float value1 值1
//     * @param float value2 值2
//     * @param float value3 值3
//     * @param float value4 值4
//     * @return @param code
//     * @return @param suanfa 算法
//     * @return @param correntkong 修正后空高
//     * */
//    public function bookdata()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt') and I('post.shipid') and I('post.cabinid') and I('post.ullage1') !== '' and I('post.draft1') !== '' and I('post.value1') !== '') {
//            $user = new \Common\Model\UserModel();
//            //判断用户状态、是否到期、标识比对
//            $msg1 = $user->is_judges(intval(I('post.uid')), I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $result = new \Common\Model\OilWorkModel();
//                $data = I('post.');
//                $result_info = $result->field('del_sign,finish_sign')->where(array('id' => intval(I('post.resultid'))))->find();
//                //如果作业被删除了，不可以操作 2033
//                if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
//                //如果作业结束了，不可以操作 2034
//                if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));
//
//                if ($data['draft2'] == "") {
//                    //如果刻度2没有填写，但是填写了刻度2所在列的值，说明是不对的,报错4，参数缺失
//                    if ($data['value2'] != "" or $data['value4'] != "") exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'])));
//                } else {
//                    //如果刻度2写了，但是没填写刻度2所在列的值，说明是不对的,报错4，参数缺失
//                    if ($data['value2'] == "" or ($data['value4'] == "" and $data['ullage2'] != "")) exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'])));
//                }
//
//                $res = $result->reckon1($data);
//            } else {
//                //未到期/状态禁止/标识错误
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//    /**
//     * 获取书本数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param int shipid 船ID
//     * @param int solt 1:作业前；2:作业后
//     * @param string imei 标
//     * @return @param code
//     * @return @param suanfa 算法
//     * @return @param correntkong 修正后空高
//     * */
//    public function getBookData()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt') and I('post.shipid') and I('post.cabinid')) {
//            $user = new \Common\Model\UserModel();
//            $uid = I('post.uid');
//            // 判断用户状态、是否到期、标识比对
//            $msg1 = $user->is_judges($uid, I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $result = new \Common\Model\OilWorkModel();
//                $res = $result->getBookData(I('post.resultid'), I('post.shipid'), I('post.cabinid'), I('post.solt'));
//            } else {
//                // 错误信息返回码
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//    /**
//     * 录入书本容量数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param int shipid 船ID
//     * @param int solt 1:作业前；2:作业后
//     * @param varchar imei 标识
//     * @param correntkong 修正后空高
//     * @param float ullage1 空高1
//     * @param float ullage2 空高2
//     * @param float capacity1 值1
//     * @param float capacity2 值2
//     * @return @param code
//     * */
//    public function getCapacityData()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt') and I('post.shipid') and I('post.cabinid')) {
//            $user = new \Common\Model\UserModel();
//            $uid = I('post.uid');
//            // 判断用户状态、是否到期、标识比对
//            $msg1 = $user->is_judges($uid, I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $result = new \Common\Model\OilWorkModel();
//                $res = $result->getCapacityData(I('post.resultid'), I('post.shipid'), I('post.cabinid'), I('post.solt'));
//            } else {
//                // 错误信息返回码
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//
//    /**
//     * 录入书本容量数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param int shipid 船ID
//     * @param int solt 1:作业前；2:作业后
//     * @param varchar imei 标识
//     * @param correntkong 修正后空高
//     * @param float ullage1 空高1
//     * @param float ullage2 空高2
//     * @param float capacity1 值1
//     * @param float capacity2 值2
//     * @return @param code
//     * */
//    public function capacitydata()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt') and I('post.shipid') and I('post.cabinid') and I('post.ullage1') !== '' and I('post.capacity1') !== '') {
//
//            $result = new \Common\Model\OilWorkModel();
//            $result_info = $result->field('del_sign,finish_sign')->where(array('id' => intval(I('post.resultid'))))->find();
//            //如果作业被删除了，不可以操作 2033
//            if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
//            //如果作业结束了，不可以操作 2034
//            if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));
//
//            $res = $result->capacityreckon(I('post.'));
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }

//    /**
//     * 根据用户ID获取可以操作的船所属公司
//     * @param int uid 用户ID
//     * @param string imei 标识
//     * @return @param array
//     * @return @param code 返回码
//     * @return @param content 说明、内容
//     */
//    public function getUserFirmList()
//    {
//        if (I('post.uid') and I('post.imei')) {
//            $user = new \Common\Model\UserModel();
//            //判断用户状态、是否到期、标识比对
//            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
//            if ($msg1['code'] == '1') {
//                // $ship = new \Common\Model\ShipFormModel();
//                // $res = $ship->shipfirm(I('post.uid'));
//                $msg = $user
//                    ->alias('u')
//                    ->field('u.id,u.imei,u.firmid,f.firmtype')
//                    ->where(array('u.id' => intval(I('post.uid'))))
//                    ->join('left join firm f on f.id=u.firmid')
//                    ->find();
//                $firm = new \Common\Model\FirmModel();
//                if ($msg['firmtype'] == '1') {
//                    // 检验公司获取所有的船公司
//                    $list = $firm->field('id as firmid,firmname')->where(array('firmtype' => '2'))->select();
//                } else {
//                    // 船舶公司获取本公司
//                    $list = $firm->field('id as firmid,firmname')->where(array('id' => $msg['firmid']))->select();
//                }
//                $res = array(
//                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                    'content' => $list
//                );
//            } else {
//                // 错误信息返回码
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//    /**
//     * 新增船
//     * @param int uid 用户id
//     * @param int firmid 公司id
//     * @param int is_guanxian 是否包含管线
//     * @param int is_diliang 是否有底量测试
//     * @param string imei 标识
//     * @param string shipname 船名
//     * @param string suanfa 算法
//     * @param int cabinnum 舱总数
//     * @return array
//     * @return array code 返回码
//     */
//    public function addship()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.firmid') and I('post.is_guanxian') and I('post.is_diliang') and I('post.shipname') and I('post.suanfa')) {
//            $ship = new \Common\Model\ShipFormModel();
//            $data = I('post.');
//            if (I('post.is_diliang') == '1' and I('post.suanfa') == "b") {
//                $data['suanfa'] = 'c';
//            } elseif (I('post.is_diliang') == '1' and I('post.suanfa') == "a") {
//                $data['suanfa'] = 'd';
//            }
//            $res = $ship->addship($data);
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }

//    /**
//     * 新增舱
//     * @param int uid 用户id
//     * @param string imei 标识
//     * @param int shipid 船ID
//     * @param string cabinname 舱名称
//     * @param float altitudeheight 基准高度
//     * @param float dialtitudeheight 底量基准高度
//     * @param float bottom_volume 容量底量
//     * @param float bottom_volume_di 底量底量
//     * @param float pipe_line 管线容量
//     * @return array
//     * @return array code 返回码
//     */
//    public function addcabin()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.cabinname') and I('post.shipid') and I('post.altitudeheight') and I('post.bottom_volume') and I('post.pipe_line')) {
//            $user = new \Common\Model\UserModel();
//            //判断用户状态、是否到期、标识比对
//            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
//            if ($msg1['code'] == '1') {
//                //判断同一条船不能有重复的舱名
//                $where = array(
//                    'shipid' => I('post.shipid'),
//                    'cabinname' => I('post.cabinname')
//                );
//                $cabin = new \Common\Model\CabinModel();
//                $count = $cabin->where($where)->count();
//                if ($count > 0) {
//                    // 重复数据 2003
//                    $res = array(
//                        'code' => $this->ERROR_CODE_RESULT['IS_REPEAT']
//                    );
//                } else {
//                    // 判断舱数限制
//                    $ship = new \Common\Model\ShipFormModel();
//                    $shipmsg = $ship
//                        ->field('cabinnum')
//                        ->where(array('id' => I('post.shipid')))
//                        ->find();
//                    $cabinsum = $cabin->where(array('shipid' => I('post.shipid')))->count();
//                    if ($shipmsg['cabinnum'] > $cabinsum) {
//                        // 去除键值首位空格
//                        $data = I('post.');
//                        // 对数据进行验证
//                        if (!$cabin->create($data)) {
//                            // 如果创建失败 表示验证没有通过 输出错误提示信息
//                            // $this->error($cabin->getError());
//                            // 数据格式有错 7
//                            $res = array(
//                                'code' => $this->ERROR_CODE_COMMON['ERROR_DATA']
//                            );
//                        } else {
//                            // 验证通过 可以进行其他数据操作
//                            $res1 = $cabin->addData($data);
//                            if ($res1) {
//                                //成功 1
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS']
//                                );
//                            } else {
//                                // 数据库操作错误  3
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
//                                );
//                            }
//                        }
//                    } else {
//                        // 超过船舶限制舱数量  2013
//                        $res = array(
//                            'code' => $this->ERROR_CODE_RESULT['CABIN_EXCEED_NUM']
//                        );
//                    }
//                }
//            } else {
//                // 错误信息返回码
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }

    /**
     * 获取个性化字段
     * @param int uid 用户id
     * @param string imei 标识
     * @param int firmid 船ID
     * @return array
     * @return array code 返回码
     */
    public function getpersonality()
    {
        if (I('post.uid') and I('post.imei') and I('post.firmid')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $firm = new \Common\Model\FirmModel();
                $personality_id = $firm->getFieldById(I('post.firmid'), 'personality');
                $personality_id = json_decode($personality_id, true);
                $data = array();
                $data['num'] = count($personality_id);
                $person = new \Common\Model\PersonalityModel();
                foreach ($personality_id as $key => $value) {
                    $data['list'][] = $person
                        ->field('name,title')
                        ->where(array('id' => $value))
                        ->find();
                }
//                $data['list'][] = array('name'=>'oil_type','title'=>"油品类型");
                //成功 1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $data
                );
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 判断船舱容表是否到期
     * @param int uid 用户id
     * @param string imei 标识
     * @param int shipid 船ID
     * @return array
     * @return array code
     */
    public function judge_time()
    {
        if (I('post.uid') and I('post.imei') and I('post.shipid')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $ship = new \Common\Model\ShipFormModel();
                $expire_time = $ship->getFieldById(I('post.shipid'), 'expire_time');
                if ($expire_time > time()) {
                    //成功 1
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                    );
                } else {
                    //船舶舱容表已到期 2015
                    $res = array(
                        'code' => $this->ERROR_CODE_RESULT['EXPIRETIME_TIME_RONG']
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

//    /**
//     * 电子签证
//     * @param string imei 标识
//     * @param int shipid 船ID
//     * @return array
//     * @return array code
//     */
//    public function electronic_visa()
//    {
//        if (I('post.resultid') and I('post.img') and I('post.uid') and I('post.imei')) {
//            //判断用户状态、是否到期、标识比对
//            $user = new \Common\Model\UserModel();
//            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
//            if ($msg1['code'] == '1') {
//                // 电子签证照片
//                if (I('post.img')) {
//                    $result_id = intval(I('post.resultid'));
//                    $uid = intval(I('post.uid'));
//                    $result = new \Common\Model\OilWorkModel();
//                    $result_info = $result->field("uid,shipid,del_sign")->where(array('id' => $result_id))->find();
//                    if ($result_info['del_sign'] == 2) {
//                        //软删除的作业不可以被操作
//                        $res = array(
//                            'code' => $this->ERROR_CODE_RESULT['RESULT_DELETED']
//                        );
//                    } else {
//                        //判断是否是创建人操作的签名
//                        if ($uid == $result_info['uid']) {
//                            // 上传签证
//                            $path_h = "./Upload/img/" . date('Y-m-d', time()) . '/';
//                            $res_h = base64_upload(I('post.img'), $path_h);
//                            if ($res_h ['code'] != 0) {
//                                //图片上传失败
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR']
//                                );
//                            } else {
//                                M()->startTrans();
//
//                                // 判断电子签证是否存在
//                                $count = M('electronic_visa')
//                                    ->where(array('resultid' => $result_id))
//                                    ->count();
//                                if ($count >= 1) {
//                                    // 电子签证已存在。删除原先数据
//                                    M('electronic_visa')
//                                        ->where(array('resultid' => $result_id))->delete();
//                                    unlink($count['img']);
//                                } else {
//                                    $count_where = array(
//                                        'uid' => $uid,
//                                        'finish_sign' => 1,
//                                        'count_sign' => 0,
//                                        'del_sign' => 1,//不统计已删除的作业
//                                        'id' => array('LT', $result_id),
//                                    );
//                                    //查找该用户未被统计的记录
//                                    $pre_result_status = $result
//                                        ->field('id,finish_sign,shipid')
//                                        ->where($count_where)
//                                        ->order('id desc')
//                                        ->select();
//                                    $resultlist = new \Common\Model\ResultlistModel();
//                                    $cabin = new \Common\Model\CabinModel();
//                                    $evaluate = M('evaluation');
//                                    //逐个统计
//                                    foreach ($pre_result_status as $k => $v) {
//                                        //开始统计作业的经验底量
//                                        $bottom_list = $resultlist->get_base_volume_list($v['id']);
//
//                                        $map = array('result_id' => $v['id']);
//                                        // 获取作业的舱容表准确度评价
//                                        $table_accuracy = $evaluate->field('table_accuracy')->where($map)->find();
//
//                                        //计入统计
//                                        if ($table_accuracy['table_accuracy'] > 0) {
//                                            M('ship_historical_sum')->where(array('shipid' => $v['shipid']))->setInc('table_accuracy', $table_accuracy['table_accuracy']);
//                                            M('ship_historical_sum')->where(array('shipid' => $v['shipid']))->setInc('accuracy_num');
//                                        }
//
//                                        foreach ($bottom_list as $value) {
//                                            $cabin->where(array('id' => $value['cabinid']))->setInc('base_volume', $value['standardcapacity']);
//                                            $cabin->where(array('id' => $value['cabinid']))->setInc('base_count');
//                                        }
//                                        $pre_count_result = $result->editData(array('id' => $v['id']), array('count_sign' => 1));
//                                        //如果更改失败,回档，删除上传的图片，报错数据库错误 3
//                                        if ($pre_count_result === false) {
//                                            M()->rollback();
//                                            unlink($res_h ['file']);
//                                            exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'])));
//                                        }
//                                    }
//
//                                    //添加当前作业结束标志
//                                    $finish_result = $result->editData(array('id' => $result_id), array('finish_sign' => 1));
//                                    //如果更改失败,回档，删除上传的图片，报错数据库错误 3
//                                    if ($finish_result === false) {
//                                        M()->rollback();
//                                        unlink($res_h ['file']);
//                                        exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'])));
//                                    }
//
//
//                                }
//                                $img = $res_h ['file'];
//                                // 新增电子签证
//                                $data = array(
//                                    'resultid' => intval(I('post.resultid')),
//                                    'img' => $img,
//                                );
//                                $arr = M('electronic_visa')->add($data);
//                                if ($arr) {
//                                    // 作业数据汇总
//                                    $result = new \Common\Model\OilWorkModel();
//                                    $res1 = $result->weight($result_id);
//                                    if ($res1['code'] == '1') {
//                                        M()->commit();
//                                        //成功 1
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS']
//                                        );
//                                    } else {
//                                        M()->rollback();
//                                        // 其它错误  2
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
//                                        );
//                                    }
//                                } else {
//                                    M()->rollback();
//                                    //上传失败 1
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR']
//                                    );
//                                }
//                            }
//                        } else {
//                            //不允许其他人操作 2024
//                            $res = array(
//                                'code' => $this->ERROR_CODE_RESULT['OTHERS_OPERATE']
//                            );
//                        }
//                    }
//                } else {
//                    // 电子签证不能为空
//                    $res = array(
//                        'code' => $this->ERROR_CODE_RESULT['NEED_IMG']
//                    );
//                }
//            } else {
//                // 错误信息返回码
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
//                'data' => I('post.')
//            );
//        }
//
//        echo jsonreturn($res);
//    }


    public
    function test_count()
    {
        $resultlist = new \Common\Model\ResultlistModel();
        die(jsonreturn($resultlist->get_base_volume_list(I('post.id'))));
    }

    /**
     * 获取作业评价
     * @param int uid 用户id
     * @param string imei 标识
     * @param int resultid 作业ID
     * @return array
     * @return array code
     * @return array content 双方评价内容
     * @return array coun
     */
    public
    function getEvaluate()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')) {
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                // 判断作业是否完成----电子签证
                $coun = M('electronic_visa')
                    ->where(array('resultid' => intval(I('post.resultid'))))
                    ->count();
                if ($coun > 0) {
                    // 获取作业的数据：操作人、作业ID、登录人的公司类型、作业的船舶ID
                    //获取水尺数据
                    $where = array(
                        'r.id' => I('post.resultid')
                    );
//                    $result = new \Common\Model\OilWorkModel();
                    $evaluate = M("evaluation");
                    //查询作业列表
                    $list = $evaluate
                        ->field('e.result_id as id,e.uid,e.ship_id as shipid,f.firmtype as ffirmtype,e.grade1,e.grade2,e.evaluate1,e.evaluate2')
                        ->alias('e')
                        ->join('left join ship s on e.ship_id=s.id')
                        ->join('left join user u on e.uid = u.id')
                        ->join('left join firm f on u.firmid = f.id')
                        ->where($where)
                        ->find();
                    // 获取当前登陆用户的公司类型
                    $a = $user
                        ->field('f.firmtype')
                        ->alias('u')
                        ->join('left join firm f on u.firmid = f.id')
                        ->where(array('u.id' => intval(I('post.uid'))))
                        ->find();
                    $list['firmtype'] = $a['firmtype'];

                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $list,
                        'coun' => $coun
                    );
                } else {
                    // 错误信息返回码
                    $res = $msg1;
                }
            } else {
                // 作业尚未完成，不可以评价  2019
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['NOT_EVAL']
                );
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 作业评价
     * @param int uid 用户id
     * @param string imei 标识
     * @param int id 作业ID
     * @param int shipid 船舶ID
     * @param int grade 分数
     * @param int firmtype 公司类型
     * @param int content 评价内容
     * @param int operater 作业操作人
     * @return array
     * @return array code
     */
    public
    function evaluate()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.grade') and I('post.measure') and I('post.security')) {
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                // 判断是否打分
                if (intval(I('post.grade')) == 0) {
                    $this->error('请评分！');
                } else {
                    $id = intval(I('post.resultid'));
                    $result = new \Common\Model\OilWorkModel();
                    $result_info = $result->field('shipid,del_sign')->where(array('id' => $id))->find();
                    //如果作业被删除了，不可以操作 2033
                    if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
                    //过滤传入值
                    $operater = intval(I('post.operater'));
                    $shipid = $result_info['shipid'];
                    $grade = intval(I('post.grade', 5));
//                    $firmtype = intval(I('post.firmtype'));
                    $content = I('post.content', '');
                    $firm = new \Common\Model\FirmModel();
                    $firmtype = $firm->getFieldById($msg1['content'], 'firmtype');
                    $measure = I('post.measure', 3); //计量规范，默认好评
                    $security = I('post.security', 3); //安全规范，默认好评

                    //构建参数传输给模型
                    $data = array(
                        'uid' => $operater,
                        'id' => $id,
                        'shipid' => $shipid,
                        'grade' => $grade,
                        'content' => $content,
                        'operater' => I('post.uid'),
                        'firmtype' => $firmtype,
                        'measure' => $measure,
                        'security' => $security,
                    );

                    $res = $result->evaluate($data);

                }
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 获取调整用的舱详细信息
     */
    public
    function adjust_cabin_list()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')) {
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $work = new \Common\Model\OilWorkModel();
                //成功 1
                $res = $work->get_cabins_weight(trimall(I('post.resultid')));
                $res['code'] = 1;
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 调整舱信息
     */
    /*public function adjust_cabin()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')
            and I('post.cabinid') and I('post.shipid') and I('post.ullage')
            and I('post.solt') and I('post.temperature')) {
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $data = I('post.');
                if (judgeTwoString($data)) {

                    $work = new \Common\Model\OilWorkModel();
                    $msg = $work->adjust_cabin($data);
                    if ($msg['code'] == 1) {
                        //成功 1
                        $res = $work->get_cabins_weight(trimall(I('post.resultid')));
                        $res['code'] = 1;
                        $res['remark'] = 'adjust';
                    } else {
                        $res = $msg;
                    }
                } else {
                    //不可以出现特殊字符，错误5
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL']
                    );
                }
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }*/

    /**
     * 批量调整有表船舱信息
     */
    public function adjust_cabins()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')
            and I('post.shipid') and I('post.solt') and I('post.data') and I('post.reason')) {

            $result_id = intval(I('post.resultid'));
            $reason = intval(I('post.reason'));
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $work = new \Common\Model\OilWorkModel();
                $result_info = $work->field('del_sign,finish_sign')->where(array('id' => $result_id))->find();
                //如果作业被删除了，不可以操作 2033
                if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
                //如果作业结束了，不可以操作 2034
                if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));

                $other = I('post.');
                /*and I('post.ullage')
                 and I('post.temperature') and I('post.cabinid')*/
                unset($other['data']);
                $data = I('post.data');

                //不能有特殊字符
                if (judgeTwoString($data) and judgeTwoString($other)) {

                    //判断是有表船还是无表船
                    $ship = new \Common\Model\ShipFormModel();
                    $is_have_data = $ship->is_have_data(I('post.shipid'));

                    if ($is_have_data !== 'y') {
                        $res['code'] = $this->ERROR_CODE_RESULT['OIL_TYPE_ERROR'];
                        $res['msg'] = "油船作业暂不支持调整无表船";
//
//                        $need_adjust = array();
//                        //开启事务
//                        M()->startTrans();
//                        //提交调整的舱大于0才记录原重量和舱容反馈
//                        if (count($data) > 0) {
//                            $weights = $work->field('old_weight,weight')->where(array('id' => $result_id))->find();
//                            $weight_data = array(
//                                'adjust_reason' => $reason
//                            );
//                            //判断是否存在原先总重，如果不存在则保存当前总重量
//                            if ($weights['old_weight'] == 0) {
//                                $weight_data['old_weight'] = $weights['weight'];
//                            }
//                            $result = $work->editData(array('id' => $result_id), $weight_data);
//                            //如果选择了舱容表偏大偏小，计入评价表内的舱容反馈，等待统计
//                            if ($reason > 0 and $reason <= 3) {
//                                //防止错选，可以更正为0
//                                if ($reason == 3) {
//                                    $reason = 0;
//                                }
//                                $evaluate = M('evaluation');
//                                $evaluate->where(array('result_id' => $result_id))->save(array('table_accuracy' => $reason));
//                            }
//
//                            //如果保存失败则rollback并且返回数据库错误 3
//                            if ($result === false) {
//                                M()->rollback();
//                                exit(jsonreturn(array(
//                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
//                                )));
//                            }
//                        }
//
//                        foreach ($data as $key => $value) {
//                            $value['resultid'] = $other['resultid'];
//                            $value['solt'] = $other['solt'];
//                            $value['shipid'] = $other['shipid'];
//                            if (isset($value['cabinid']) and $value['cabinid'] !== ''
//                                and isset($value['ullage']) and $value['ullage'] !== ''
//                                and isset($value['temperature']) and $value['temperature'] !== '') {
//                                /*
//                                 * 空高、液深精度限制：0.001
//                                 * 温度精度限制：0.1
//                                 */
//                                $value['ullage'] = round($value['ullage'], 3);
//                                $value['sounding'] = round($value['sounding'], 3);
//                                $value['temperature'] = round($value['temperature'], 1);
//
////                            exit(jsonreturn($value));
//                                $msg = $work->adjust_nodata_cabin($value);
//                                if ($msg['code'] != 1) {
//                                    //如果发生错误，回档期间发生的所有修改并且退出
//                                    M()->rollback();
//                                    $res = $msg;
//                                    exit(jsonreturn($res));
//                                } else {
//                                    if ($msg['adjust']) {
//                                        $need_adjust[] = $value['cabinid'];
//                                    }
//                                }
//                            } else {
//                                //参数不全 4
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//                                );
//                                exit(jsonreturn($res));
//                            }
//                        }
//
//                        //提交事务
//                        M()->commit();
//
//                        //成功 1
//                        $res = $work->get_cabins_weight(trimall(I('post.resultid')));
//                        $res['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
//                        $res['adjustlist'] = $need_adjust;
//                        $res['remark'] = 'adjust';
                    } else {
                        $res['remark'] = 'adjust';
                        //开启事务
                        M()->startTrans();
                        //提交调整的舱大于0才记录原重量和舱容反馈
                        if (count($data) > 0) {
                            $weights = $work->field('old_weight,weight')->where(array('id' => $result_id))->find();
                            $weight_data = array(
                                'adjust_reason' => $reason
                            );
                            //判断是否存在原先总重，如果不存在则保存当前总重量
                            if ($weights['old_weight'] == 0) {
                                $weight_data['old_weight'] = $weights['weight'];
                            }
                            $result = $work->editData(array('id' => $result_id), $weight_data);
                            //如果选择了舱容表偏大偏小，计入评价表内的舱容反馈，等待统计
                            if ($reason > 0 and $reason < 3) {
                                $evaluate = M('evaluation');
                                $evaluate->where(array('result_id' => $result_id))->save(array('table_accuracy' => $reason));
                            }

                            //如果保存失败则rollback并且返回数据库错误 3
                            if ($result === false) {
                                M()->rollback();
                                exit(jsonreturn(array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                                )));
                            }
                        }
                        foreach ($data as $key => $value) {
                            $value['resultid'] = $other['resultid'];
                            $value['solt'] = $other['solt'];
                            $value['shipid'] = $other['shipid'];
                            if (isset($value['cabinid']) and $value['cabinid'] !== ''
                                and isset($value['ullage']) and $value['ullage'] !== ''
                                and isset($value['temperature']) and $value['temperature'] !== ''
                                and isset($value['water_sounding']) and $value['water_sounding'] !== ''
                                and isset($value['ob_temperature']) and $value['ob_temperature'] !== ''
                                and isset($value['ob_density']) and $value['ob_density'] !== ''
                            ) {
                                /*
                                 * 空高、液深限制：0.001
                                 * 温度限制：0.1
                                 */
                                $value['ullage'] = round($value['ullage'], 3);
                                $value['sounding'] = round($value['sounding'], 3);
                                $value['temperature'] = round($value['temperature'], 1);
                                $msg = $work->adjust_cabin($value, 'P');
                                if ($msg['code'] != 1) {
                                    //如果发生错误，回档期间发生的所有修改并且退出
                                    M()->rollback();
                                    $res = $msg;
                                    exit(jsonreturn($res));
                                }
                            } else {
                                //参数不全 4
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
                                );
                                exit(jsonreturn($res));
                            }
                        }
                        //提交事务
                        M()->commit();
                        //成功 1
                        $res = $work->get_cabins_weight(trimall(I('post.resultid')));
                        $res['code'] = 1;
                        $res['remark'] = 'adjust';


                    }
                } else {
                    //不可以出现特殊字符，错误5
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL']
                    );
                }
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 批量计算
     * @param int cabinid 舱ID
     * @param int uid 用户ID
     * @param int resultid 计量ID
     * @param float sounding 实高
     * @param float ullage 空高
     * @param varchar temperature 温度
     * @param int solt 1:作业前；2:作业后
     * @param varchar imei 标识
     * @param int shipid 船ID
     * @param float altitudeheight 基准高度
     * @param string qufen diliang:底量计算 rongliang:容量计算
     * @param int quantity 1：计算底量；2：不计算底量
     * @param int is_pipeline 是否包含管线 1：是；2：否
     * @param varchar soundingfile 实高图片
     * @param varchar ullagefile 空高图片
     * @param varchar temperaturefile 温度图片
     * @return @param array
     * @return @param code
     */
    public
    function batch_reckon()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt')
            and I('post.shipid') and I('post.qufen') and I('post.is_fugai')
            and I('post.is_pipeline') and I('post.quantity') and I('post.data')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $datas = I('post.data');
                $ship = new \Common\Model\ShipFormModel();
                $result = new \Common\Model\OilWorkModel();
                $resultlist = new \Common\Model\ResultlistModel();
                $cabin = new \Common\Model\CabinModel();
                $result_info = $result->field('del_sign,finish_sign')->where(array('id' => intval(I('post.resultid'))))->find();
                //如果作业被删除了，不可以操作 2033
                if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
                //如果作业结束了，不可以操作 2034
                if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));


                /*and I('post.cabinid') and I('post.altitudeheight')
                and I('post.qufen') and I('post.quantity')
                and I('post.is_pipeline')*/

                M()->startTrans();    //开启事物

                /*
                 * 检查是否有被删除的舱，如果有，服务器也删除
                 */
                $list_where = array('resultid' => intval(I('post.resultid')), 'solt' => intval(I('post.solt')));
                $old_cabins_data = $resultlist->field('cabinid')->where($list_where)->select();
                //需要删除的舱数据列表
                $n_del_list = array();
                $up_list = array();

                //对比舱，判断哪些需要删除
                foreach ($datas as $key_1 => $data_1) {
                    $up_list[] = $data_1['cabinid'];
                }

                foreach ($old_cabins_data as $key_2 => $data_2) {
                    if (!in_array($data_2['cabinid'], $up_list)) {
                        $n_del_list[] = $data_2['cabinid'];
                    }
                }

                if (count($n_del_list) > 0) {
                    $list_where['cabinid'] = array('in', $n_del_list);
                    $del_list_result = $resultlist->where($list_where)->delete();
                    $del_record_result = M('resultrecord')->where($list_where)->delete();
                    if ($del_list_result === false or $del_record_result === false) {
                        //如果删除失败了，回档并且返回数据库错误 3
                        M()->rollback();
                        exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'])));
                    }
                    //先不提交，其他情况如果出现问题则全部回档。由最后的一个步骤提交
                }


                foreach ($datas as $key => $data) {
                    $data['uid'] = intval(I('post.uid'));
                    $data['imei'] = I('post.imei');
                    $data['resultid'] = intval(I('post.resultid'));
                    $data['solt'] = intval(I('post.solt'));
                    $data['shipid'] = intval(I('post.shipid'));
                    $data['qufen'] = I('post.qufen');
                    $data['is_fugai'] = I('post.is_fugai');
                    $data['is_pipeline'] = I('post.is_pipeline');
                    $data['quantity'] = I('post.quantity');
                    $data['is_work'] = 1;

                    $cabin_name = "";

                    $cabin_name = $cabin->getFieldById($data['cabinid'], 'cabinname');

                    //检查参数是否缺失
                    if (!isset($data['ullage']) or $data['ullage'] === ''
                        or !isset($data['cabinid']) or $data['cabinid'] === ''
                        or !isset($data['water_sounding']) or $data['water_sounding'] === ''
                        or !isset($data['altitudeheight']) or $data['altitudeheight'] === ''
                        or !isset($data['temperature']) or $data['temperature'] === ''
                        or !isset($data['sounding']) or $data['sounding'] === ''
                        or !isset($data['ob_temperature']) or $data['ob_temperature'] === ''
                        or !isset($data['ob_density']) or $data['ob_density'] === ''
                    ) {
                        //缺少数据，报错 4
                        M()->rollback();
                        exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], 'cabinname' => $cabin_name)));
                    }



                    // 安卓端基准高度在计算底量书底量计算时提交错误
                    $suanfa = $ship
                        // ->where(array('id'=>$data['shipid']))
                        ->getFieldById($data['shipid'], 'suanfa');
                    if ($data['qufen'] == 'diliang' && ($suanfa == 'c' || $suanfa == 'd')) {
                        $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'dialtitudeheight');

                    } else {
                        $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'altitudeheight');
                    }


                    /*
                     * 空高、液深限制：0.001
                     * 温度限制：0.1
                     */
                    $data['ullage'] = round($data['ullage'], 3);
                    $data['water_sounding'] = round($data['water_sounding'], 3);
                    $data['water_ullage'] = round($data['altitudeheight'] - $data['water_sounding'], 3);
                    $data['sounding'] = round($data['altitudeheight'] - $data['ullage'], 3);
                    $data['temperature'] = round($data['temperature'], 1);

                    //根据作业状态、作业ID、舱id判断作业是否重复
                    $where3 = array(
                        'solt' => $data['solt'],
                        'cabinid' => $data['cabinid'],
                        'resultid' => $data['resultid'],
                    );
                    $r = $resultlist
                        ->where($where3)
                        ->count();
                    if ($r > 0 and I('post.is_fugai') == 'N') {
                        //作业重复 2003
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['IS_REPEAT']
                        );
                        M()->rollback();
                        $res['cabinname'] = $cabin_name;

                        exit(jsonreturn($res));
                    } else {
                        // 允许重复
                        if (I('post.solt') == '2') {
                            //如果是舱作业后数据，判断该舱是否有作业前数据
                            $where = array(
                                'solt' => '1',
                                'cabinid' => $data['cabinid'],
                                'resultid' => $data['resultid']
                            );
                            $arr = $resultlist
                                ->where($where)
                                ->count();
                            if ($arr != 1) {
                                //没有作业前数据 2008
                                $res = array(
                                    'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
                                );
                                M()->rollback();
                                $res['cabinname'] = $cabin_name;
                                exit(jsonreturn($res));
                            } else {
                                //判断空高是否在基准高度与0之内
                                if ($data['ullage'] >= 0 and $data['ullage'] <= $data['altitudeheight'] and $data['water_ullage'] >= 0) {
                                    $res = $result->reckon($data);
                                } else {
                                    //空高有误 2009
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                                    );
                                    M()->rollback();
                                    $res['cabinname'] = $cabin_name;
                                    exit(jsonreturn($res));
                                }
                            }
                        } else {
                            //判断空高是否在基准高度与0之内
                            if ($data['ullage'] >= 0 and $data['ullage'] <= $data['altitudeheight']) {
                                $res = $result->reckon($data);
                            } else {
                                //空高有误 2009
                                $res = array(
                                    'code' => $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
                                );
                                M()->rollback();
                                $res['cabinname'] = $cabin_name;

                                exit(jsonreturn($res));
                            }
                        }

                        // 计算成功记录数据
                        if ($res['code'] == '1') {
                            //判断数据是否已记录
                            $map = array(
                                'solt' => $data['solt'],
                                'cabinid' => $data['cabinid'],
                                'resultid' => $data['resultid'],
                                'is_work' => 1,
                            );

                            $num = M('resultrecord')->where($map)->count();
                            if ($num > 0) {
                                M('resultrecord')->where($map)->save($data);
                            } else {
                                M('resultrecord')->add($data);
                            }
                        }
                    }
                }
            } else {
                // 未到期/状态禁止/标识错误
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	5
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }


//    /**
//     * 记录测量数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param float sounding 实高
//     * @param float ullage 空高
//     * @param varchar temperature 温度
//     * @param int solt 1:作业前；2:作业后
//     * @param varchar imei 标识
//     * @param int shipid 船ID
//     * @param float altitudeheight 基准高度
//     * @param qufen diliang:底量计算 rongliang:容量计算
//     * @param int quantity 1：计算底量；2：不计算底量
//     * @param int is_pipeline 是否有管线 1：有；2：没有；
//     * @param varcher is_fugai 是否覆盖  Y:覆盖；N：不覆盖
//     * @return @param array
//     * @return @param code
//     * */
//    public
//    function batch_measure()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid')
//            and I('post.solt') and I('post.shipid') and I('post.qufen')
//            and I('post.quantity') and I('post.is_pipeline') and I('post.is_fugai')) {
//
//            $user = new \Common\Model\UserModel();
//            $uid = I('post.uid');
//            // 判断用户状态、是否到期、标识比对
//            $msg1 = $user->is_judges($uid, I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $ship = new \Common\Model\ShipFormModel();
//                $cabin = new \Common\Model\CabinModel();
//                $resultlist = new \Common\Model\ResultlistModel();
//                $resultrecord = M('resultrecord');
//                $result = new \Common\Model\OilWorkModel();
//                $result_info = $result->field('del_sign,finish_sign,qianchi,houchi')->where(array('id' => intval(I('post.resultid'))))->find();
//                //如果作业被删除了，不可以操作 2033
//                if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
//                //如果作业结束了，不可以操作 2034
//                if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));
//
//
//                $datas = I('post.data');
//
//                $shipmsg = $ship
//                    ->field('suanfa')
//                    ->where(array('id' => I('post.shipid')))
//                    ->find();
//
//                /*and I('post.cabinid')
//                and I('post.sounding') !== null and I('post.ullage') !== null
//                and I('post.temperature') !== null and I('post.altitudeheight') !== null*/
//
//                M()->startTrans();
//
//                /*
//                 * 检查是否有被删除的舱，如果有，服务器也删除
//                 */
//                $list_where = array('resultid' => intval(I('post.resultid')), 'solt' => intval(I('post.solt')));
//                $old_cabins_data = $resultlist->field('cabinid')->where($list_where)->select();
//                //需要删除的舱数据列表
//                $n_del_list = array();
//                $up_list = array();
//
//                //对比舱，判断哪些需要删除
//                foreach ($datas as $key_1 => $data_1) {
//                    $up_list[] = $data_1['cabinid'];
//                }
//
//                foreach ($old_cabins_data as $key_2 => $data_2) {
//                    if (!in_array($data_2['cabinid'], $up_list)) {
//                        $n_del_list[] = $data_2['cabinid'];
//                    }
//                }
//                //如果有需要删除的舱
//                if (count($n_del_list) > 0) {
//                    $list_where['cabinid'] = array('in', $n_del_list);
//                    $del_list_result = $resultlist->where($list_where)->delete();
//                    $del_record_result = M('resultrecord')->where($list_where)->delete();
//                    if ($del_list_result === false or $del_record_result === false) {
//                        //如果删除失败了，回档并且返回数据库错误 3
//                        M()->rollback();
//                        exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'])));
//                    }
//                    //先不提交，其他情况如果出现问题则全部回档。由最后的一个步骤提交
//                }
//
//
//                foreach ($datas as $key => $data) {
//                    //初始化记录录入过程
//                    $process = array();
//                    $cabin_name = "";
//
//                    //赋值通用数据
//                    $data['resultid'] = intval(I('post.resultid'));
//                    $data['solt'] = intval(I('post.solt'));
//                    $data['shipid'] = intval(I('post.shipid'));
//                    $data['qufen'] = I('post.qufen');
//                    $data['quantity'] = I('post.quantity');
//                    $data['is_pipeline'] = intval(I('post.is_pipeline'));
//                    $data['is_fugai'] = I('post.is_fugai');
//                    $data['is_work'] = 1;
//
//                    $cabin_name = $cabin->getFieldById($data['cabinid'], 'cabinname');
//
//                    //检查参数是否缺失
//                    if (!isset($data['ullage']) or $data['ullage'] === ""
//                        or !isset($data['cabinid']) or $data['cabinid'] === ""
//                        or !isset($data['altitudeheight']) or $data['altitudeheight'] === ""
//                        or !isset($data['temperature']) or $data['temperature'] === ""
//                        or !isset($data['sounding']) or $data['sounding'] === ""
//                    ) {
//                        M()->rollback();
//                        exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], 'cabinname' => $cabin_name)));
//                    }
//
//                    /*
//                     * 空高、液深限制：0.001
//                     * 温度限制：0.1
//                     */
//                    $data['ullage'] = round($data['ullage'], 3);
//                    $data['sounding'] = round($data['sounding'], 3);
//                    $data['temperature'] = round($data['temperature'], 1);
//
//
//                    // 安卓端基准高度在计算底量书底量计算时提交错误
//                    if ($data['qufen'] == 'diliang' && ($shipmsg['suanfa'] == 'c' || $shipmsg['suanfa'] == 'd')) {
//                        $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'dialtitudeheight');
//                    } else {
//                        $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'altitudeheight');
//                    }
//                    //判断空高是否在基准高度与0之内
//                    if ($data['ullage'] < 0 or $data['ullage'] > $data['altitudeheight']) {
//                        //空高有误 2009
//                        $res = array(
//                            'code' => $this->ERROR_CODE_RESULT['ULLAGE_ISNOT']
//                        );
//                        M()->rollback();
//                        $res['cabinname'] = $cabin_name;
//                        exit(jsonreturn($res));
//                    }
//
//                    // 查找数据条件
//                    $where = array(
//                        'resultid' => $data['resultid'],
//                        'cabinid' => $data['cabinid'],
//                        'solt' => $data['solt'],
//                    );
//
//                    //获取原来的计算过程,没有就初始化
//                    $old_process = $resultrecord->field('process')->where($where)->find();
//                    if ($old_process !== false) {
//                        $process = json_decode($old_process['process'], true);
//                        if ($process == null) {
//                            $process = array();
//                        }
//                    }
//
//                    $bilge_stock = '';
//                    $pipeline_stock = '';
//                    $soltType = '';
//
//                    //将某些变量格式化，方便读取计算过程,格式化是否有底量
//                    if ($data['quantity'] == "1") {
//                        $bilge_stock = 'true';
//                    } else {
//                        $bilge_stock = 'false';
//                    }
//
//                    //格式化是否有管线容量
//                    if ($data['is_pipeline'] == "1") {
//                        $pipeline_stock = 'true';
//                    } else {
//                        $pipeline_stock = 'false';
//                    }
//
//                    //格式化作业状态
//                    if ($data['solt'] == "1") {
//                        $soltType = '作业前';
//                    } else {
//                        $soltType = '作业后';
//                    }
//
////                    $process .= "Received meansure_value:\r\n\tullage=" . $data['ullage'] . ", sounding=" . $data['sounding'] . ", cabin_temperature=" . $data['temperature'] . ", soltType=," . $soltType . "\r\n\taltitudeheight=" . $data['altitudeheight'] . ", table_used=" . $data['qufen'] . ", bilge_stock=" . $bilge_stock . ", pipeline_stock=" . $pipeline_stock . ",\r\n";
//                    $process['ullage'] = $data['ullage'];
//                    $process['sounding'] = $data['sounding'];
//                    $process['Cabin_temperature'] = $data['temperature'];
//                    $process['method'] = $soltType;
//                    $process['altitudeheight'] = $data['altitudeheight'];
//                    $process['table_used'] = $data['qufen'];
//                    $process['bilge_stock'] = $bilge_stock;
//                    $process['pipeline_stock'] = $pipeline_stock;
//
//
//                    // 获取作业记录数据个数
//                    $rrecord = $resultrecord
//                        ->where($where)
//                        ->count();
//
//                    $rlist = $resultlist->where($where)->count();
//                    if ($rrecord > 0 and I('post.is_fugai') == 'N') {
//                        // 作业记录存在且不覆盖
//                        // 作业重复 2003
//                        $res = array(
//                            'code' => $this->ERROR_CODE_RESULT['IS_REPEAT']
//                        );
//                        M()->rollback();
//                        $res['cabinname'] = $cabin_name;
//
//                        exit(jsonreturn($res));
//                    } elseif ($rrecord > 0 and I('post.is_fugai') == 'Y') {
//                        // 作业数据记录存在并且覆盖数据
//                        // 允许覆盖
//                        if (I('post.solt') == '2') {
//                            //如果是舱作业后数据，判断该舱是否有作业前数据
//                            $where = array(
//                                'solt' => '1',
//                                'cabinid' => $data['cabinid'],
//                                'resultid' => $data['resultid']
//                            );
//
//                            $arr = $resultlist
//                                ->where($where)
//                                ->count();
//
//                            if ($arr != 1) {
//                                //没有作业前数据 2008
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
//                                );
//                                M()->rollback();
//                                $res['cabinname'] = $cabin_name;
//
//                                exit(jsonreturn($res));
//                            } else {
//                                /*
//                                 * 查询累积数据
//                                 */
//                                $trim_data = $result->get_cumulative_trim_data($data['cabinid'], $data['ullage'], $result_info['houchi'], $data['qufen']);
//                                //如果查询到数据，合并需要存入的累积数据，等待下一次查询
//                                if (false !== $trim_data) {
//                                    $data = array_merge($trim_data, $data);
//                                }
//                                $data['process'] = json_encode($process);
//                                //作业后数据修改
//                                $where['solt'] = '2';
//                                $id = $resultrecord
//                                    ->where($where)
//                                    ->save($data);
//
//
//                                if ($id !== false) {
//                                    $resultdata = array(
//                                        'ullage' => $data['ullage'],
//                                        'sounding' => $data['sounding'],
//                                        'temperature' => $data['temperature'],
//                                        'is_work' => 1
//                                    );
//
//                                    if ($rlist > 0) {
//                                        $resultr = $resultlist->editData($where, $resultdata);
//                                    } else {
//                                        $resultdata['resultid'] = $data['resultid'];
//                                        $resultdata['cabinid'] = $data['cabinid'];
//                                        $resultdata['solt'] = $data['solt'];
//
//                                        $resultr = $resultlist->addData($resultdata);
//                                    }
//
//                                    if ($resultr === false) {
//                                        //其他错误
//                                        $res = array(
//                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                            'sign' => 6,
//                                        );
//                                        M()->rollback();
//                                        $res['cabinname'] = $cabin_name;
//
//                                        exit(jsonreturn($res));
//                                    }
//                                    /*$res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                        'suanfa' => $shipmsg['suanfa']
//                                    );*/
//                                } else {
//                                    //其他错误
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                        'sign' => 1,
//                                    );
//                                    M()->rollback();
//                                    $res['cabinname'] = $cabin_name;
//
//                                    exit(jsonreturn($res));
//                                }
//                            }
//                        } else {
//                            /*
//                             * 查询累积数据
//                             */
//                            $trim_data = $result->get_cumulative_trim_data($data['cabinid'], $data['ullage'], $result_info['qianchi'], $data['qufen']);
//                            //如果查询到数据，合并需要存入的累积数据，等待下一次查询
//                            if (false !== $trim_data) {
//                                $data = array_merge($trim_data, $data);
//                            }
//                            $data['process'] = json_encode($process);
//                            // 修改作业前数据
//                            $id = $resultrecord
//                                ->where($where)
//                                ->save($data);
//                            if ($id !== false) {
//                                $resultdata = array(
//                                    'ullage' => $data['ullage'],
//                                    'sounding' => $data['sounding'],
//                                    'temperature' => $data['temperature'],
//                                    'is_work' => 1
//                                );
//
//                                if ($rlist > 0) {
//                                    $resultr = $resultlist->editData($where, $resultdata);
//                                } else {
//
//                                    $resultdata['resultid'] = $data['resultid'];
//                                    $resultdata['cabinid'] = $data['cabinid'];
//                                    $resultdata['solt'] = $data['solt'];
//
//                                    $resultr = $resultlist->addData($resultdata);
//                                }
//
//
//                                if ($resultr === false) {
//                                    //其他错误
//                                    $res = array(
//                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                        'sign' => 6,
//                                    );
//                                    M()->rollback();
//                                    $res['cabinname'] = $cabin_name;
//
//                                    exit(jsonreturn($res));
//                                }
////                                $res = array(
////                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
////                                    'suanfa' => $shipmsg['suanfa']
////                                );
//                            } else {
//                                //其他错误
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                    'sign' => 2,
//                                );
//                                M()->rollback();
//                                $res['cabinname'] = $cabin_name;
//
//                                exit(jsonreturn($res));
//                            }
//                        }
//
//                    } elseif ($rrecord == 0) {
//                        /*
//                         * 查询累积数据
//                         */
//                        $trim_data = $result->get_cumulative_trim_data($data['cabinid'], $data['ullage'], $result_info['qianchi'], $data['qufen']);
//                        //如果查询到数据，合并需要存入的累积数据，等待下一次查询
//                        if (false !== $trim_data) {
//                            $data = array_merge($trim_data, $data);
//                        }
//                        $data['process'] = json_encode($process);
//                        // 没有记录作业数据，新增作业记录数据
//                        $id = $resultrecord
//                            ->add($data);
//                        if ($id !== false) {
//                            $resultdata = array(
//                                'ullage' => $data['ullage'],
//                                'sounding' => $data['sounding'],
//                                'temperature' => $data['temperature'],
//                                'is_work' => 1
//                            );
//
//                            if ($rlist > 0) {
//                                $resultr = $resultlist->editData($where, $resultdata);
//                            } else {
//
//                                $resultdata['resultid'] = $data['resultid'];
//                                $resultdata['cabinid'] = $data['cabinid'];
//                                $resultdata['solt'] = $data['solt'];
//
//                                $resultr = $resultlist->addData($resultdata);
//                            }
//
//                            if ($resultr === false) {
//                                //其他错误
//                                $res = array(
//                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                    'sign' => 6,
//                                );
//                                M()->rollback();
//                                $res['cabinname'] = $cabin_name;
//
//                                exit(jsonreturn($res));
//                            }
//
//                            /*$res = array(
//                                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                                'suanfa' => $shipmsg['suanfa']
//                            );*/
//
//                        } else {
//                            //其他错误 2
//                            $res = array(
//                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                                'sign' => 3,
//                            );
//                            M()->rollback();
//                            $res['cabinname'] = $cabin_name;
//
//                            exit(jsonreturn($res));
//                        }
//
//                    } else {
//                        //其他错误  2
//                        $res = array(
//                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
//                            'sign' => 4,
//                        );
//                        M()->rollback();
//                        $res['cabinname'] = $cabin_name;
//
//                        exit(jsonreturn($res));
//                    }
//                }
//
//                M()->commit();
//                $res = array(
//                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                    'suanfa' => $shipmsg['suanfa']
//                );
//
//
//            } else {
//                //未到期/状态禁止/标识错误
//                $res = $msg1;
//            }
//        } else {
////            \Think\Log::record(json_encode(I("post."),true), "DEBUG", true);
//
//            //参数不正确，参数缺失	4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//
//    /**
//     * 批量录入书本数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param int shipid 船ID
//     * @param int solt 1:作业前；2:作业后
//     * @param string imei 标识
//     * @param float ullage1 空高1
//     * @param float ullage2 空高2
//     * @param float draft1 吃水差1
//     * @param float draft2 吃水差2
//     * @param float value1 值1
//     * @param float value2 值2
//     * @param float value3 值3
//     * @param float value4 值4
//     * @return @param code
//     * @return @param suanfa 算法
//     * @return @param correntkong 修正后空高
//     * */
//    public
//    function batch_bookdata()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt') and I('post.shipid') and I('post.data')) {
//            $result = new \Common\Model\OilWorkModel();
//            $cabin = new \Common\Model\CabinModel();
//
//            $result_info = $result->field('del_sign,finish_sign')->where(array('id' => intval(I('post.resultid'))))->find();
//            //如果作业被删除了，不可以操作 2033
//            if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
//            //如果作业结束了，不可以操作 2034
//            if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));
//
//            $uid = I('post.uid');
//            $imei = I('post.imei');
//            $resultid = I('post.resultid');
//            $solt = I('post.solt');
//            $shipid = I('post.shipid');
//            $datas = I('post.data');
//
//            M()->startTrans();
//            //初始化修正后空高
//            $correntKong = array();
//            //吃水差刻度是否有过值的记录
//            $draftFlag = true;
//            //数组第一个key
//            $firstKey = -1;
//
//            foreach ($datas as $key => $data) {
//                $cabin_name = $cabin->getFieldById($data['cabinid'], 'cabinname');
//
//                /**
//                 * 该部分验证刻度2的数据是否是全空或者全满
//                 */
//                if ($data['draft2'] == "") {
//                    //如果刻度2没有填写，但是填写了刻度2所在列的值，说明是不对的,报错4，参数缺失
//                    if ($data['value2'] != "") exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "cabinname" => $cabin_name, "index" => $key, "type" => "value2")));
//                    if ($data['value4'] != "") exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "cabinname" => $cabin_name, "index" => $key, "type" => "value4")));
//                    if ($firstKey == -1) {
//                        $draftFlag = false;
//                        $firstKey = $key;
//                    } else {
//                        //如果第一个舱已经有了数据，后面的舱没有数据，说明数据没有全空或者全满，报错4，参数缺失
//                        if ($draftFlag === true) exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "cabinname" => $cabin_name, "index" => $key, "type" => "draft2")));
//                    }
//                } else {
//                    //如果刻度2写了，但是没填写刻度2所在列的值，说明是不对的,报错4，参数缺失
//                    if ($data['value2'] == "") exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "cabinname" => $cabin_name, "index" => $key, "type" => "value2")));
//                    if ($data['value4'] == "" and $data['ullage2'] != "") {
//                        exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "cabinname" => $cabin_name, "index" => $key, "type" => "value4")));
//                    }
//                    if ($firstKey == -1) {
//                        $draftFlag = true;
//                        $firstKey = $key;
//                    } else {
//                        //如果第一个舱没有数据，后面的舱却有了数据，说明数据没有全空或者全满，报错4，参数缺失
//                        if ($draftFlag === false) exit(jsonreturn(array("code" => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'], "cabinname" => $cabin_name, "index" => $firstKey, "type" => "draft2")));
//                    }
//                }
//
//
//                //允许出现部分数据不填写，校验后不填写的数据自动补全
//                if ($data['cabinid'] and $data['ullage1'] !== '' and $data['draft1'] !== '' and $data['value1'] !== '') {
//                    $data['resultid'] = $resultid;
//                    $data['uid'] = $uid;
//                    $data['imei'] = $imei;
//                    $data['solt'] = $solt;
//                    $data['shipid'] = $shipid;
//                    $res = $result->reckon1($data, 'b');
//
//                    if ($res['code'] != 1) {
//                        M()->rollback();
//                        $res['cabinname'] = $cabin_name;
//                        $res['index'] = $key;
//                        exit(jsonreturn($res));
//                    } else {
//                        $correntKong[] = array('cabinid' => $data['cabinid'], 'correntkong' => $res['correntkong']);
//                    }
//
//                } else {
//                    M()->rollback();
//                    //参数不正确，参数缺失    4
//                    $res = array(
//                        'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//                    );
//                    $res['cabinname'] = $cabin_name;
//
//                    exit(jsonreturn($res));
//                }
//            }
//
//            M()->commit();
//            $res = array(
//                'correntkong' => $correntKong,
//                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
//                'suanfa' => $res['suanfa'],
//            );
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//
//    /**
//     * 批量录入书本容量数据
//     * @param int cabinid 舱ID
//     * @param int uid 用户ID
//     * @param int resultid 计量ID
//     * @param int shipid 船ID
//     * @param int solt 1:作业前；2:作业后
//     * @param varchar imei 标识
//     * @param correntkong 修正后空高
//     * @param float ullage1 空高1
//     * @param float ullage2 空高2
//     * @param float capacity1 值1
//     * @param float capacity2 值2
//     * @return @param code
//     * */
//    public
//    function batch_capacitydata()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt') and I('post.shipid') and I('post.data')) {
//            $result = new \Common\Model\OilWorkModel();
//            $cabin = new \Common\Model\CabinModel();
//            $result_info = $result->field('del_sign,finish_sign')->where(array('id' => intval(I('post.resultid'))))->find();
//            //如果作业被删除了，不可以操作 2033
//            if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
//            //如果作业结束了，不可以操作 2034
//            if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));
//
//            $uid = I('post.uid');
//            $imei = I('post.imei');
//            $resultid = I('post.resultid');
//            $solt = I('post.solt');
//            $shipid = I('post.shipid');
//            $datas = I('post.data');
//            M()->startTrans();
//
//            foreach ($datas as $key => $data) {
//
//                $cabin_name = $cabin->getFieldById($data['cabinid'], 'cabinname');
//                if ($data['cabinid'] and $data['ullage1'] !== '' and $data['capacity1'] !== '') {
//                    $data['resultid'] = $resultid;
//                    $data['uid'] = $uid;
//                    $data['imei'] = $imei;
//                    $data['solt'] = $solt;
//                    $data['shipid'] = $shipid;
//
//
//                    $res = $result->capacityreckon($data, 'b');
//
//                    if ($res['code'] != 1) {
//                        M()->rollback();
//                        $res['cabinname'] = $cabin_name;
//                        $res['index'] = $key;
//                        exit(jsonreturn($res));
//                    }
//                } else {
//                    M()->rollback();
//                    //参数不正确，参数缺失    4
//                    $res = array(
//                        'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//                    );
//                    $res['cabinname'] = $cabin_name;
//                    $res['index'] = $key;
//                    exit(jsonreturn($res));
//                }
//            }
//            M()->commit();
//
//        } else {
//            //参数不正确，参数缺失    4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }

//    /**
//     * 批量获取无表船 纵倾修正表数据
//     * @param int uid 用户ID
//     * @param string imei 用户ID
//     */
//    public
//    function get_book_datas()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt')) {
//            //判断用户状态、是否到期、标识比对
//            $user = new \Common\Model\UserModel();
//            $work = new \Common\Model\OilWorkModel();
//            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $res = array();
//                $res['msg'] = $work->get_book_data(I('post.resultid'), I('post.solt'));
//                $res['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
//                $res['chishui'] = $res['msg']['chishui'];
//                $res['suanfa'] = $res['msg']['suanfa'];
//                unset($res['msg']['chishui']);
//                unset($res['msg']['suanfa']);
//            } else {
//                //未到期/状态禁止/标识错误
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失	4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }

//
//    /**
//     * 批量获取无表船 容量表数据
//     */
//    public
//    function get_capacity_datas()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt')) {
//            //判断用户状态、是否到期、标识比对
//            $user = new \Common\Model\UserModel();
//            $work = new \Common\Model\OilWorkModel();
//            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $res = array();
//                $res['msg'] = $work->get_capacity_data(I('post.resultid'), I('post.solt'));
//                $res['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
//            } else {
//                //未到期/状态禁止/标识错误
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失	4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
//
//    /**
//     * 批量获取无表船 容量表数据
//     */
//    public
//    function get_ship_base_info()
//    {
//        if (I('post.uid') and I('post.imei') and I('post.shipid')) {
//            //判断用户状态、是否到期、标识比对
//            $user = new \Common\Model\UserModel();
//            $ship = new \Common\Model\ShipFormModel();
//            $cabin = new \Common\Model\CabinModel();
//            $shipid = intval(I('post.shipid'));
//            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
//            if ($msg1['code'] == '1') {
//                $res = array();
//                $res['table_accuracy'] = $ship->get_ship_table_accuracy($shipid);
//                $res['base_volume'] = $cabin->get_cabins_base_volume($shipid);
//                $res['code'] = $this->ERROR_CODE_COMMON['SUCCESS'];
//            } else {
//                //未到期/状态禁止/标识错误
//                $res = $msg1;
//            }
//        } else {
//            //参数不正确，参数缺失	4
//            $res = array(
//                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
//            );
//        }
//        echo jsonreturn($res);
//    }
}