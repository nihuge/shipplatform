<?php

namespace App\Controller;

use Common\Controller\AppBaseController;

/**
 * 作业指令管理
 */
class ShResultController extends AppBaseController
{
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
                // 根据用户id获取可以查询的船列表
                $msg = $user->getUserOperationSeach($uid);
                $where = '1';

                if (I('post.search') != null) {
                    // 查询指令列表
                    if (I('post.shipname')) {
                        $shipname = trimall(I('post.shipname'));
                    }
                    $where .= " and s.shipname like '%" . $shipname . "%'";

                    if ($msg['sh_search_jur'] == '') {
                        // 查询权限为空时，查看所有操作权限之内的作业
                        if ($msg['sh_operation_jur'] == '') {
                            $operation_jur = "-1";
                        } else {
                            $operation_jur = $msg['sh_operation_jur'];
                        }
                        $where .= " and r.uid ='$uid' and s.id in (" . $operation_jur . ")";
                    } else {
                        $where .= " and s.id in (" . $msg['sh_search_jur'] . ")";
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
                    if ($msg['sh_operation_jur'] == '') {
                        $operation_jur = "-1";
                    } else {
                        $operation_jur = $msg['sh_operation_jur'];
                    }
                    $where .= " and r.uid ='$uid' ";
                    $where .= " and r.shipid in (" . $operation_jur . ")";
                }

                // 条件---航次
                if (I('post.voyage') != null) {
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

                $result = new \Common\Model\ShResultModel();
                //计算个数
                $count = $result
                    ->alias('r')
                    ->join('left join sh_ship s on r.shipid=s.id')
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
                    ->field('r.id,r.uid,r.shipid,r.weight,r.solt,r.remark,r.personality,s.shipname,u.username,f.firmtype,r.qian_d_m,r.hou_d_m,r.qian_dspc,r.hou_dspc,r.qian_constant,r.hou_constant,r.finish,r.finish_time')
                    ->alias('r')
                    ->join('left join sh_ship s on r.shipid=s.id')
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
                    $resultlist = new \Common\Model\ShResultlistModel();
                    $ship = new \Common\Model\ShShipModel();
                    foreach ($list as $key => $v) {
                        $where1 = array('resultid' => $v['id']);
                        $list[$key]['list'] = $resultlist
                            ->field('cabinname')
                            ->where($where1)
                            ->select();
                        // 已作业舱的总数
                        $list[$key]['nums'] = count($list[$key]['list']);
                        $list[$key]['personality'] = json_decode($v['personality'], true);

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
        if (I('post.uid') and I('post.shipid') and I('post.voyage') !== null and I('post.start') !== null and I('post.objective') !== null and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、公司状态、标识比对
            $msg = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg['code'] == '1') {
                $result = new \Common\Model\ShResultModel();
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
     * 完成作业
     * @param int uid 用户ID
     * @param string imei 标识
     * @param int resultid 发货方
     * @return array
     * @return @param code 返回码
     * @return @param resultid 说明、内容
     */
    public function finish_result()
    {
        if (I('post.uid') and I('post.resultid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、公司状态、标识比对
            $msg = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg['code'] == '1') {
                $result = new \Common\Model\ShResultModel();
                //如果作业已经结束则报错作业已完成 2029
                if($result->checkFinish(I('post.resultid'))) exit(jsonreturn(array($this->ERROR_CODE_RESULT['WORK_COMPLETE'])));
                $res = $result->finishResult(I('post.resultid'),I('post.uid'));
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
                $result = new \Common\Model\ShResultModel();
                //如果作业已经结束则报错作业已完成 2029
                if($result->checkFinish(I('post.resultid'))) exit(jsonreturn(array($this->ERROR_CODE_RESULT['WORK_COMPLETE'])));
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
     * @param float(9,6) density 密度
     * @return @param array
     * @return @param code 返回码
     */
    /*    public function fornt()
        {
            //判断前左、右是否有
            if (I('post.forntleft') != null and I('post.forntright') != null
                and I('post.afterleft') != null and I('post.afterright') != null
                and I('post.centerleft') != null and I('post.centerright') != null
                and I('post.uid') and I('post.resultid') and I('post.solt') and I('post.imei') and I('post.pwd')) {

                $result = new \Common\Model\ShResultModel();
                $data = I('post.');
                $res = $result->forntOperation($data);

            } else {
                //参数不正确，参数缺失    4
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
                );
            }
            echo jsonreturn($res);
        }*/

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
     * @param float(9,6) density 密度
     * @return @param array
     * @return @param code 返回码
     */
    public function NewFornt()
    {
        //判断前左、右是否有
        if (I('post.forntleft') != null and I('post.forntright') != null
            and I('post.afterleft') != null and I('post.afterright') != null
            and I('post.centerleft') != null and I('post.centerright') != null
            and I('post.uid') and I('post.resultid') and I('post.solt') and I('post.imei') and I('post.pwd')) {

            $result = new \Common\Model\ShResultModel();
            //如果作业已经结束则报错作业已完成 2029
            if($result->checkFinish(I('post.resultid'))) exit(jsonreturn(array($this->ERROR_CODE_RESULT['WORK_COMPLETE'])));

            $data = I('post.');
            $res = $result->forntOperation1($data);

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
    #todo 照片上传
    /*public function upload()
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
    }*/

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
                $result = new \Common\Model\ShResultModel();

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
        if (I('post.resultid') and I('post.uid') and I('post.imei')) {
            $result = new \Common\Model\ShResultModel();
            $res = $result->resultsearch(I('post.resultid'), I('post.uid'), I('post.imei'));
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
        if (I('post.resultid') and I('post.uid') and I('post.imei')) {
            //获取数据
            $result = new \Common\Model\ShResultModel();
            $arr = $result->resultsearch(I('post.resultid'), I('post.uid'), I('post.imei'));
            $arr['content']['verify'] = shGetReportErCode($arr['content']['id'], $arr['content']['uid']);
            if ($arr['code'] == '1') {
//                // 获取公司pdf方法名
//                $firm = new \Common\Model\FirmModel();
//                $firmmsg = $firm
//                    ->alias('f')
//                    ->field('f.pdf,f.personality')
//                    ->join('left join user u on u.firmid = f.id')
//                    ->where(array('u.id' => I('post.uid')))
//                    ->find();
//                if ($firmmsg !== false and !empty($firmmsg['pdf'])) {
//
//                    //引入了https，做https协议的适配
//                    $is_https = I('post.minipost');
//                    if ($is_https) {
//                        $uid = I('post.uid');
//                        $resultid = I('post.resultid');
//                        $filepath = "miniprogram/" . $uid . "/";
//                        $PDFname = $resultid . ".pdf";
//                        //如果是https，则返回全部的
                $filename = $result->pdf($arr, I('post.resultid'), I('post.uid'));//生成PDF文件
//
//                        if ($filename != '') {
//                            $filename = '/Public/pdf/' . $filepath . $PDFname;
//                        }
//
//                    } else {
//                        $filename = pdf($arr, $firmmsg);//生成PDF文件
//                    }
//
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
//                } else {
//                    //该作业所属公司没有pdf文件模板  2006
//                    $res = array(
//                        'code' => $this->ERROR_CODE_RESULT['FIRM_NOT_PDF']
//                    );
//                }

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
            $ship = new \Common\Model\ShShipModel();
            $result = $ship->shiplist(I('post.uid'), I('post.imei'));
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                'content' => $result
            );
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
        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt')) {
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $resultid = trimall(I('post.resultid'));
            $solt = trimall(I('post.solt'));

            $user = new \Common\Model\UserModel();
            $msg = $user->is_judges($uid, $imei);

            if ($msg['code'] == 1) {
                $resultrecord = M('sh_resultlist');
                $where = array(
                    'resultid' => $resultid,
                    'solt' => $solt
                );
                $result = $resultrecord->where($where)->select();
                if ($result !== false) {
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $result
                    );
                } else {
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }
            } else {
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
                $result = new \Common\Model\ShResultModel();
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
                $result = new \Common\Model\ResultModel();
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
     * 记录测量数据
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
     * @param int is_pipeline 是否有管线 1：有；2：没有；
     * @param varcher is_fugai 是否覆盖  Y:覆盖；N：不覆盖
     * @return @param array
     * @return @param code
     * */
    public function measure()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')
            and I('post.solt') and I('post.shipid') and I('post.cabininfo')) {
            $user = new \Common\Model\UserModel();
            $uid = I('post.uid');
            // 判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges($uid, I('post.imei'));
            if ($msg1['code'] == '1') {
                $sh_result = new \Common\Model\ShResultModel();
                $sh_ship = new \Common\Model\ShShipModel();
                //如果作业已经结束则报错作业已完成 2029
                if($sh_result->checkFinish(I('post.resultid'))) exit(jsonreturn(array($this->ERROR_CODE_RESULT['WORK_COMPLETE'])));

                $solt = I('post.solt');
                $shipid = I('post.shipid');
                $resultid = trimall(I('post.resultid'));
                $ship_weight = $sh_ship->getFieldById($shipid, 'weight');//获取船舶自重

                if ($solt == '1') {
                    $process = json_decode($sh_result->getFieldById($resultid, "qianprocess"), true);
                } elseif ($solt == '2') {
                    $process = json_decode($sh_result->getFieldById($resultid, "houprocess"), true);
                } else {
                    //其他错误 4
                    return array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                    );
                }

                if ($process == null) {
                    $process = array();
                }

                $cabin_info = I('post.cabininfo');
                $resultlist = new \Common\Model\ShResultlistModel();

                $process['cabin'] = array();

                M()->startTrans();
                foreach ($cabin_info as $key => $value) {

                    $where_c = array(
                        'resultid' => $resultid,
                        'solt' => $solt,
                        'cabinname' => $value['cabinname'],
                    );


                    $value['solt'] = $solt;
                    $value['shipid'] = $shipid;
                    $value['resultid'] = $resultid;


                    $value['volume'] = round((float)$value['volume'], 5);
                    $value['density'] = round((float)$value['density'], 5);
                    $value['weight'] = round((float)$value['volume'] * (float)$value['density'], 5);
//                    $process .= $value['cabinname'] . ": \r\n volume=" . $value['volume'] . ",density=" . $value['density'] . ",weight=" . $value['volume'] . "*" . $value['density'] . "=" . $value['weight'] . " \r\n";
                    $process['cabin'][] = array(
                        'cabinname' => $value['cabinname'],
                        'volume' => $value['volume'],
                        'density' => $value['density'],
                        'sounding' => $value['sounding'],
                        'weight' => $value['weight']
                    );


                    if ($value['weight'] !== null and $value['volume'] !== null and $value['density'] !== null and $value['sounding'] !== null) {
                        // 对数据进行验证
                        if (!$resultlist->create($value)) {
                            M()->rollback();
                            // 如果创建失败 表示验证没有通过 输出错误提示信息.数据验证失败错误12
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                'massage' => $resultlist->getError(),
                            );
                            exit(jsonreturn($res));
                        } else {
                            $cnum = $resultlist->where($where_c)->count();
                            // 验证通过 可以进行其他数据操作,如果有相同的舱名则覆盖
                            if ($cnum > 0) {
                                $resl = $resultlist->where($where_c)->save($value);
                            } else {
                                $resl = $resultlist->add($value);
                            }
                            if ($resl === false) {
                                M()->rollback();
                                // 如果创建失败 表示验证没有通过 输出错误提示信息,数据验证失败错误12
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                    'massage' => $resultlist->getError(),
                                );
                                exit(jsonreturn($res));
                            }
                        }
                    } else {
                        M()->rollback();
                        // 如果创建失败 表示验证没有通过 输出错误提示信息,数据验证失败错误12
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                            'value' => $value,
                        );
                        exit(jsonreturn($res));
                    }
                }


                $wherelist = array(
                    'resultid' => $resultid,
                    'solt' => $solt,
                );

                $total_weight = $resultlist->field('sum(weight) as t_weight')->where($wherelist)->find();

                $result = new \Common\Model\ShResultModel();
                #todo 检测计算中需要用到的数据是否缺失，缺失则返回步骤错位
                if ($solt == "1") {
                    $result_msg = $result
                        ->field('qian_dspc,qian_fwater_weight,qian_sewage_weight,qian_fuel_weight,qian_other_weight,qian_constant,hou_constant')
                        ->where(array('id' => $resultid))
                        ->find();
                    $data_r = array(
                        'qian_constant' => round((float)$result_msg['qian_dspc'] - (float)$total_weight['t_weight'] - (float)$result_msg['qian_fwater_weight'] - (float)$result_msg['qian_sewage_weight'] - (float)$result_msg['qian_fuel_weight'] - (float)$result_msg['qian_other_weight'] - $ship_weight, 5),
                    );
                    $process['t_weight'] = (float)$total_weight['t_weight'];
                    $process['ship_weight'] = $ship_weight;
                    $process['constant'] = (float)$data_r['qian_constant'];

                    if ($result_msg['hou_constant'] > 0) {
                        if ($result_msg['qian_constant'] >= $result_msg['hou_constant']) {
                            $data_r['weight'] = round((float)$result_msg['qian_constant'] - (float)$result_msg['hou_constant'], 5);
                            $process['heavier'] = 'q';
                        } else {
                            $data_r['weight'] = round((float)$result_msg['hou_constant'] - (float)$result_msg['qian_constant'], 5);
                            $process['heavier'] = 'h';
                        }
                        $process['weight'] = (float)$data_r['weight'];
                    }
                    $data_r['qianprocess'] = json_encode($process);

                    $resr = $result->editData(array('id' => $resultid), $data_r);

                    if ($resr === false) {
                        M()->rollback();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                            'msg' => $result->getDbError(),
                        );
                        exit(json_encode($res));
                    } else {
                        M()->commit();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'constant' => $data_r['qian_constant'],
                            'weight' => isset($data_r['weight']) ? $data_r['weight'] : "",
                        );
                    }
                } elseif ($solt == "2") {
                    $result_msg = $result->field('hou_dspc,hou_fwater_weight,hou_sewage_weight,hou_fuel_weight,hou_other_weight,qian_constant,hou_constant')->where(array('id' => $resultid))->find();
                    $data_r = array(
                        'hou_constant' => round((float)$result_msg['hou_dspc'] - (float)$total_weight['t_weight'] - (float)$result_msg['hou_fwater_weight'] - (float)$result_msg['hou_sewage_weight'] - (float)$result_msg['hou_fuel_weight'] - (float)$result_msg['hou_other_weight'] - $ship_weight, 5),
                    );
                    $process['t_weight'] = (float)$total_weight['t_weight'];
                    $process['ship_weight'] = $ship_weight;
                    $process['constant'] = (float)$data_r['qian_constant'];
                    if ($result_msg['qian_constant'] >= $result_msg['hou_constant']) {
                        $data_r['weight'] = round((float)$result_msg['qian_constant'] - (float)$result_msg['hou_constant'], 5);
                        $process['heavier'] = 'q';
                    } else {
                        $data_r['weight'] = round((float)$result_msg['hou_constant'] - (float)$result_msg['qian_constant'], 5);
                        $process['heavier'] = 'h';
                    }
                    $process['weight'] = (float)$data_r['weight'];

                    $data_r['houprocess'] = json_encode($process);
                    $resr = $result->editData(array('id' => $resultid), $data_r);
                    if ($resr === false) {
                        M()->rollback();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                            'msg' => $result->getDbError(),
                        );
                        exit(json_encode($res));
                    } else {
                        M()->commit();
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'constant' => $data_r['hou_constant'],
                            'weight' => isset($data_r['weight']) ? $data_r['weight'] : "",
                        );
                    }
                } else {
                    M()->rollback();
                    //参数不正确，参数缺失	4
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
                    );
                    exit(json_encode($res));
                }


                /*                //初始化记录录入过程
                                $process = "";
                                $ship = new \Common\Model\ShipModel();
                                $shipmsg = $ship
                                    ->field('suanfa')
                                    ->where(array('id' => I('post.shipid')))
                                    ->find();
                                $data = I('post.');

                                // 安卓端基准高度在计算底量书底量计算时提交错误
                                if ($data['qufen'] == 'diliang' && $shipmsg['suanfa'] == 'c') {
                                    $cabin = new \Common\Model\CabinModel();
                                    $data['altitudeheight'] = $cabin->getFieldById($data['cabinid'], 'dialtitudeheight');
                                }

                                $bilge_stock = '';
                                $pipeline_stock = '';
                                $soltType = '';

                                //将某些变量格式化，方便读取计算过程,格式化是否有底量
                                if ($data['quantity'] == "1") {
                                    $bilge_stock = 'true';
                                } else {
                                    $bilge_stock = 'false';
                                }

                                //格式化是否有管线容量
                                if ($data['is_pipeline'] == "1") {
                                    $pipeline_stock = 'true';
                                } else {
                                    $pipeline_stock = 'false';
                                }

                                //格式化作业状态
                                if ($data['solt'] == "1") {
                                    $soltType = '作业前';
                                } else {
                                    $soltType = '作业后';
                                }

                                $process .= "Received meansure_value:\r\n\tullage=" . $data['ullage'] . ", sounding=" . $data['sounding'] . ", cabin_temperature=" . $data['temperature'] . ", soltType=," . $soltType . "\r\n\taltitudeheight=" . $data['altitudeheight'] . ", table_used=" . $data['qufen'] . ", bilge_stock=" . $bilge_stock . ", pipeline_stock=" . $pipeline_stock . ",\r\n";


                                // 判断数据是否存在
                                $where = array(
                                    'resultid' => $data['resultid'],
                                    'cabinid' => $data['cabinid'],
                                    'solt' => $data['solt']
                                );

                                $resultrecord = M('resultrecord');
                                // 获取作业记录数据个数
                                $rrecord = $resultrecord
                                    ->where($where)
                                    ->count();
                                if ($rrecord > 0 and I('post.is_fugai') == 'N') {
                                    // 作业记录存在且不覆盖
                                    // 作业重复 2003
                                    $res = array(
                                        'code' => $this->ERROR_CODE_RESULT['IS_REPEAT']
                                    );
                                } elseif ($rrecord > 0 and I('post.is_fugai') == 'Y') {
                                    // 作业数据记录存在并且覆盖数据
                                    // 允许覆盖
                                    if (I('post.solt') == '2') {
                                        //如果是舱作业后数据，判断该舱是否有作业前数据
                                        $where = array(
                                            'solt' => '1',
                                            'cabinid' => I('post.cabinid'),
                                            'resultid' => I('post.resultid')
                                        );
                                        $resultlist = new \Common\Model\ResultlistModel();
                                        $arr = $resultlist
                                            ->where($where)
                                            ->count();
                                        if ($arr != 1) {
                                            //没有作业前数据 2008
                                            $res = array(
                                                'code' => $this->ERROR_CODE_RESULT['NO_QIAN_CABIN']
                                            );
                                        } else {
                                            $data['houprocess'] = urlencode($process);
                                            //作业后数据修改
                                            $id = $resultrecord
                                                ->where($where)
                                                ->save($data);
                                            if ($id !== false) {
                                                $res = array(
                                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                                    'suanfa' => $shipmsg['suanfa']
                                                );
                                            } else {
                                                //其他错误
                                                $res = array(
                                                    'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                                );
                                            }
                                        }
                                    } else {
                                        $data['qianprocess'] = urlencode($process);
                                        // 修改作业前数据
                                        $id = $resultrecord
                                            ->where($where)
                                            ->save($data);
                                        if ($id !== false) {
                                            $res = array(
                                                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                                'suanfa' => $shipmsg['suanfa']
                                            );
                                        } else {
                                            //其他错误
                                            $res = array(
                                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                            );
                                        }
                                    }
                                } elseif ($rrecord == 0) {
                                    $data['qianprocess'] = urlencode($process);
                                    // 没有记录作业数据，新增作业记录数据
                                    $id = $resultrecord
                                        ->add($data);
                                    if ($id !== false) {
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                            'suanfa' => $shipmsg['suanfa']
                                        );
                                    } else {
                                        //其他错误 2
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                        );
                                    }
                                } else {
                                    //其他错误  2
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                    );
                                }*/
            } else {
                //未到期/状态禁止/标识错误
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
     * 录入排水量表数据
     *
     */
    public function NewBookData()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')
            and I('post.solt') and I('post.shipid')
            and I('post.d_up') !== null and I('post.d_down') !== null
            and I('post.tpc_up') !== null and I('post.tpc_down') !== null
            and I('post.ds_up') !== null and I('post.ds_down') !== null
            and I('post.lca_up') !== null and I('post.lca_down') !== null
            and I('post.mtc_up') !== null and I('post.mtc_down') !== null) {
            $result = new \Common\Model\ShResultModel();
            //如果作业已经结束则报错作业已完成 2029
            if($result->checkFinish(I('post.resultid'))) exit(jsonreturn(array($this->ERROR_CODE_RESULT['WORK_COMPLETE'])));

            $res = $result->reckon2(I('post.'));
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }


    /**
     * 根据用户ID获取可以操作的船所属公司
     * @param int uid 用户ID
     * @param string imei 标识
     * @return @param array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function getUserFirmList()
    {
        if (I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                // $ship = new \Common\Model\ShipModel();
                // $res = $ship->shipfirm(I('post.uid'));
                $msg = $user
                    ->alias('u')
                    ->field('u.id,u.imei,u.firmid,f.firmtype')
                    ->where(array('u.id' => I('post.uid')))
                    ->join('left join firm f on f.id=u.firmid')
                    ->find();
                $firm = new \Common\Model\FirmModel();
                if ($msg['firmtype'] == '1') {
                    // 检验公司获取所有的船公司
                    $list = $firm->field('id as firmid,firmname')->where(array('firmtype' => '2'))->select();
                } else {
                    // 船舶公司获取本公司
                    $list = $firm->field('id as firmid,firmname')->where(array('id' => $msg['firmid']))->select();
                }
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $list
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
//                $data['num'] = count($personality_id);
                $person = new \Common\Model\PersonalityModel();
                foreach ($personality_id as $key => $value) {
                    $data = array(
                        "num" => 8,
                        "list" => array(
                            array(
                                "name" => "voyage",
                                "title" => "指令航次"
                            ),
                            array(
                                "name" => "locationname",
                                "title" => "作业地点"
                            ),
                            array(
                                "name" => "start",
                                "title" => "起运港口"
                            ),
                            array(
                                "name" => "objective",
                                "title" => "目的港口"
                            ),
                            array(
                                "name" => "goodsname",
                                "title" => "货物名称"
                            ),
                            array(
                                "name" => "transport",
                                "title" => "运单量"
                            ),
                            array(
                                "name" => "number",
                                "title" => "作业编号"
                            ),
                            array(
                                "name" => "agent",
                                "title" => "委托方"
                            ),
                        ),
                    );
//                    $data['list'][] = $person
//                        ->field('name,title')
//                        ->where(array('id' => $value))
//                        ->find();
                }
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
                $ship = new \Common\Model\ShShipModel();
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


    /**
     * 获取排水表数据
     */
    public function gettable()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid') and I('post.solt')) {
            $user = new \Common\Model\UserModel();
            //判断用户状态、是否到期、标识比对
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                $resultRecord = M('sh_resultrecord');

                $where = array(
                    'resultid' => trimall(I('post.resultid')),
                    'solt' => trimall(I('post.solt'))
                );

                $recordlist = $resultRecord->field('d_up,d_down,tpc_up,tpc_down,ds_up,ds_down,lca_up,lca_down,xf_up,xf_down,mtc_up,mtc_down,ptwd,solt,shipid,resultid')->where($where)->find();
                //成功 1
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'recordlist' => $recordlist
                );


//                $expire_time = $ship->getFieldById(I('post.shipid'), 'expire_time');
//                if ($expire_time > time()) {
//                    //成功 1
//                    $res = array(
//                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
//                    );
//                } else {
//                    //船舶舱容表已到期 2015
//                    $res = array(
//                        'code' => $this->ERROR_CODE_RESULT['EXPIRETIME_TIME_RONG']
//                    );
//                }
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
     * 电子签证
     * @param string imei 标识
     * @param int shipid 船ID
     * @return array
     * @return array code
     */
    #todo 获取电子签名
    /*public function electronic_visa()
    {
        if (I('post.resultid') and I('post.img')) {
            // 电子签证照片
            if (I('post.img')) {
                // 上传签证
                $path_h = "./Upload/img/" . date('Y-m-d', time()) . '/';
                $res_h = base64_upload(I('post.img'), $path_h);
                if ($res_h ['code'] != 0) {
                    //图片上传失败
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR']
                    );
                } else {
                    M()->startTrans();
                    // 判断电子签证是否存在
                    $count = M('electronic_visa')
                        ->where(array('resultid' => I('post.resultid')))
                        ->find();
                    if (!empty($count)) {
                        // 电子签证已存在。删除原先数据
                        M('electronic_visa')
                            ->where(array('resultid' => I('post.resultid')))->delete();
                        unlink($count['img']);
                    }
                    $img = $res_h ['file'];
                    // 新增电子签证
                    $data = array(
                        'resultid' => I('post.resultid'),
                        'img' => $img,
                    );
                    $arr = M('electronic_visa')->add($data);
                    if ($arr) {
                        // 作业数据汇总
                        $result = new \Common\Model\ResultModel();
                        $res1 = $result->weight(I('post.resultid'));
                        if ($res1['code'] == '1') {
                            M()->commit();
                            //成功 1
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                            );
                        } else {
                            M()->rollback();
                            // 其它错误  2
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                            );
                        }
                    } else {
                        M()->rollback();
                        //上传失败 1
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR']
                        );
                    }

                }
            } else {
                // 电子签证不能为空
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['NEED_IMG']
                );
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
     * 获取作业评价
     * @param int uid 用户id
     * @param string imei 标识
     * @param int resultid 作业ID
     * @return array
     * @return array code
     * @return array content 双方评价内容
     * @return array coun
     */
    #todo 获取作业评价
    /*public function getEvaluate()
    {
        if (I('post.uid') and I('post.imei') and I('post.resultid')) {
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                // 判断作业是否完成----电子签证
                $coun = M('electronic_visa')
                    ->where(array('resultid' => I('post.resultid')))
                    ->count();
                if ($coun > 0) {
                    // 获取作业的数据：操作人、作业ID、登录人的公司类型、作业的船舶ID
                    //获取水尺数据
                    $where = array(
                        'r.id' => I('post.resultid')
                    );
                    $result = new \Common\Model\ResultModel();
                    //查询作业列表
                    $list = $result
                        ->field('r.id,r.uid,r.shipid,f.firmtype as ffirmtype,r.grade1,r.grade2,r.evaluate1,r.evaluate2')
                        ->alias('r')
                        ->join('left join ship s on r.shipid=s.id')
                        ->join('left join user u on r.uid = u.id')
                        ->join('left join firm f on u.firmid = f.id')
                        ->where($where)
                        ->find();
                    // 获取当前登陆用户的公司类型
                    $a = $user
                        ->field('f.firmtype')
                        ->alias('u')
                        ->join('left join firm f on u.firmid = f.id')
                        ->where(array('u.id' => I('post.uid')))
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
    }*/

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
    #todo 评价作业功能
    /*public function evaluate()
    {
        if (I('post.uid') and I('post.imei') and I('post.id') and I('post.shipid') and I('post.grade') and I('post.firmtype') and I('post.content')) {
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg1['code'] == '1') {
                // 判断是否打分
                if (I('post.grade') == 0) {
                    $this->error('请评分！');
                } else {
                    $data = array(
                        'uid' => I('post.operater'),
                        'id' => I('post.id'),
                        'shipid' => I('post.shipid'),
                        'grade' => I('post.grade'),
                        'firmtype' => I('post.firmtype'),
                        'content' => I('post.content'),
                        'operater' => I('post.uid')
                    );
                    $result = new \Common\Model\ResultModel();
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
    }*/
}