<?php

namespace APP\Controller;

use Common\Controller\AppBaseController;

/**
 * 公司管理
 * 2018.4.25
 */
class FirmController extends AppBaseController
{
    /**
     * 公司列表
     * */
    public function index()
    {
        if (I('post.uid') and I('post.imei')) {
            $user = new \Common\Model\UserModel();
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $msg1 = $user->is_judges($uid, $imei);
            if ($msg1['code'] == '1') {
                $firm = new \Common\Model\FirmModel();
                //获得该用户所属公司的操作权限
                $where1 = array(
                    'id' => $msg1['content'],
                );

                $firm_jur = $firm
                    ->field('firm_jur,firmtype')
                    ->where($where1)
                    ->find();

                if ($firm_jur['firmtype'] == '1') {
                    $firm_jur_arr = explode(',', $firm_jur['firm_jur']);
                    $where = array(
                        'id' => array('in', $firm_jur_arr),
                        'del_sign' => 1
                    );
                    $data = $firm
                        ->field('*')
                        ->where($where)
                        ->order('id asc')
                        ->select();
                    foreach ($data as $key => $value) {
                        $data[$key]['pinyin'] = pinyin($value['firmname'], 'one');
                    }
                    //成功
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'content' => $data,
                    );
                } else {
                    //公司权限不足，无法查看公司 1016
                    $res = array(
                        'code' => $this->ERROR_CODE_USER['FIRM_NOT_ENOUGH'],
                    );
                }
            } else {
                // 错误信息返回码
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
     * 新增公司
     * */
    public
    function add()
    {
        if (I('post.uid') and I('post.imei') and I('post.firmname')
            and I('post.people') and I('post.phone')) {
            $data['firmname'] = I('post.firmname');
            $data['people'] = I('post.people');
            $data['phone'] = I('post.phone');

            $res = judgeTwoString($data);
            if ($res == false) {
                //错误5，不能含有特殊字符
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL'],
                );
            } else {
                //判断用户是否合法
                $user = new \Common\Model\UserModel();
                $uid = trimall(I('post.uid'));
                $imei = trimall(I('post.imei'));
                $msg1 = $user->is_judges($uid, $imei);
                if ($msg1['code'] == '1') {
                    $data['expire_time'] = strtotime('+10 year');
//                    die(date('Y-m-d H:i:s',$data['expire_time']));
                    // 默认个性化字段
                    $data['personality'] = json_encode(array(1, 2, 3, 4, 5, 6, 9));
                    // 默认会员收费类型，会员费，1
                    $data['membertype'] = 1;
                    //默认船类型，船公司，2
                    $data['firmtype'] = 2;
                    //默认默认合同号，标注为小程序创建
                    $data['number'] = "miniapp" . time();
                    //默认可以建30条船
                    $data['limit'] = 30;
                    //去除无用字段
                    unset($data['uid']);
                    unset($data['imei']);

                    $firm = new \Common\Model\FirmModel();
                    //获得该用户所属公司的操作权限
                    $where1 = array(
                        'id' => $msg1['content']
                    );
                    //查询公司类型
                    $firmtype = $firm
                        ->field('firm_jur,firmtype')
                        ->where($where1)
                        ->find();
                    //如果公司不是检验公司，不给创建公司
                    if ($firmtype['firmtype'] != '2') {
                        // 对数据进行验证
                        if (!$firm->create($data)) {
                            //添加失败,错误12
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                'massage' => $firm->getError(),
                            );
                        } else {
                            // 验证通过 可以进行其他数据操作
                            $res = $firm->addData($data);
                            if ($res) {
                                //添加该公司对应的公司权限
                                $firm_jur_arr = explode(',', $firmtype['firm_jur']);
                                $firm_jur_arr[] = $res;
                                $firm_jur_str = implode(',', $firm_jur_arr);
                                $data_f = array(
                                    'firm_jur' => $firm_jur_str,
                                );
                                $res_f = $firm->editData($where1, $data_f);
                                if ($res_f !== false) {
                                    // 添加公司历史数据汇总初步
                                    $arr = array('firmid' => $res);
                                    M('firm_historical_sum')->add($arr);
                                    //修改成功
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                    );
                                } else {
                                    //修改失败,错误11
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                        'massage' => '修改失败',
                                    );
                                }
                            } else {
                                //添加失败,错误12
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['ADD_DATA_FALL'],
                                    'massage' => $firm->getError(),
                                );
                            }
                        }
                    } else {
                        //公司权限不足，无法创建新公司 1016
                        $res = array(
                            'code' => $this->ERROR_CODE_USER['FIRM_NOT_ENOUGH'],
                        );
                    }
                } else {
                    //用户验证错误
                    $res = $msg1;
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
     * 公司修改
     * */
    public function edit()
    {
        $firm = new \Common\Model\FirmModel();
        $user = new \Common\Model\UserModel();
        if (I('post.uid') and I('post.imei')) {
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $msg1 = $user->is_judges($uid, $imei);
            if ($msg1['code'] == '1') {
                if (I('post.firmname') and I('post.people') and I('post.phone') and I('firmid')) {
                    $data['firmname'] = I('post.firmname');
                    $data['people'] = I('post.people');
                    $data['phone'] = I('post.phone');

                    $firmid = I('post.firmid');

                    // 判断提交的数据是否含有特殊字符
                    $res = judgeTwoString($data);
                    if ($res == false) {
                        //错误5，不能含有特殊字符
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL'],
                        );
                    } else {
                        $firm = new \Common\Model\FirmModel();
                        //获得该用户所属公司的操作权限
                        $where1 = array(
                            'id' => $msg1['content']
                        );
                        //查询公司类型
                        $firmtype = $firm
                            ->field('firm_jur,firmtype')
                            ->where($where1)
                            ->find();

                        //如果公司不是检验公司或者没有该公司权限，不给修改公司
                        $firm_jur_arr = explode(',', $firmtype['firm_jur']);
                        if ($firmtype['firmtype'] != '2' and in_array($firmid, $firm_jur_arr)) {
                            // 对数据进行验证
                            if (!$firm->create($data)) {
                                //修改失败,错误11
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                    'massage' => $firm->getError(),
                                );
                            } else {
                                // 验证通过 可以进行其他数据操作
                                $map = array(
                                    'id' => $firmid
                                );
                                $res_f = $firm->editData($map, $data);
                                if ($res_f !== false) {
                                    //修改成功
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                    );
                                } else {
                                    //修改失败,错误11
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                                        'massage' => '修改失败',
                                    );
                                }
                            }
                        } else {
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['FIRM_NOT_ENOUGH']
                            );
                        }
                    }
                } else {
                    // 获取数据
                    $where = array(
                        'id' => I('post.firmid')
                    );
                    $data = $firm
                        ->where($where)
                        ->find();
                    if (!empty($data) and $data !== false) {
                        //成功
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'content' => $data,
                        );
                    } else {
                        //错误1009,公司不存在
                        $res = array(
                            'code' => $this->ERROR_CODE_USER['NOT_FIRM'],
                        );
                    }
                }
            } else {
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
     * 完善信息
     */
    public function perfect()
    {
        if (I('post.uid') and I('post.imei') and I('post.firmname') and I('post.firmtype') and I('post.people') and I('post.phone')) {
            // 新增公司信息
            $data = I('post.');
            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                //不能含有特殊字符	5
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['NOT_SPECIAL']
                );

                exit(jsonreturn($res));
            }
            if (!empty($img)) {
                $data['img'] = $img;
            }
            // 默认个性化字段
            $data['personality'] = json_encode(array(1, 2, 3, 4, 5, 6, 9));
            M()->startTrans();  // 开启事务
            // 到期时间默认一周
            $data['expire_time'] = strtotime("+10 year");
            $data['membertype'] = 1;
            //默认默认合同号，标注为小程序创建
            $data['number'] = "miniapp_perfect_" . time();
            //默认可以建30条船
            $data['limit'] = 30;
//            $data['people'] = "未填写";
//            $data['phone'] = "未填写";
            $firm = new \Common\Model\FirmModel();
            // 对数据进行验证
            if (!$firm->create($data)) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                //数据库错误	3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                );
            } else {
                // 验证通过 可以进行其他数据操作
                $res = $firm->addData($data);
                if ($res !== false) {
                    $where1 = array(
                        'id' => $res
                    );
                    //查询公司当前权限信息
                    $firmtype = $firm
                        ->field('firm_jur')
                        ->where($where1)
                        ->find();
                    //添加该公司对应的公司权限
                    $firm_jur_arr = explode(',', $firmtype['firm_jur']);
                    $firm_jur_arr[] = $res;
                    $firm_jur_str = implode(',', $firm_jur_arr);
                    $data_f = array(
                        'firm_jur' => $firm_jur_str,
                    );
                    $res_f = $firm->editData($where1, $data_f);
                    if ($res_f !== false) {
                        // 添加公司历史数据汇总初步
                        $arr = array('firmid' => $res);
                        M('firm_historical_sum')->add($arr);
                        //开始修改用户信息
                        $da = array(
                            'firmid' => $res
                        );
                        $uid = I('post.uid');
                        $map = array(
                            'id' => $uid
                        );

                        $user = new \Common\Model\UserModel();
                        if (!$user->create($da)) {
                            M()->rollback();
                            //数据库错误	3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                'msg' => $user->getError()
                            );
                        } else {
                            // 修改用户信息
                            $resu = $user->editData($map, $da);
                            if ($resu !== false) {
                                M()->commit();
                                // 添加公司历史数据汇总初步
                                $arr = array('firmid' => $da['firmid']);
                                M('firm_historical_sum')->add($arr);
                                //设置用户状态为已读
//                            $user

                                //成功
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                    'content' => array(
                                        'firmid' => $da['firmid'],
                                        'firmtype' => I('post.firmtype'),
                                    )
                                );
                            } else {
                                M()->rollback();
                                //数据库错误	3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $user->getDbError(),
                                );
                            }
                        }
                    } else {
                        //修改失败,错误11
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['EDIT_FALL'],
                            'massage' => '修改失败',
                        );
                    }

                } else {
                    M()->rollback();
                    //数据库错误	3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                    );
                }
            }
        } else {
            //缺少参数 4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 检查公司名
     */
    public function check_name()
    {
        if (I('post.uid') and I('post.imei') and I('post.name')) {
            //判断用户是否合法
            $user = new \Common\Model\UserModel();
            $uid = trimall(I('post.uid'));
            $imei = trimall(I('post.imei'));
            $msg1 = $user->is_judges($uid, $imei);
            if ($msg1['code'] == $this->ERROR_CODE_USER['NOT_FIRM']) {
                $firm = new \Common\Model\FirmModel();
                $res = $firm->check_name(I('post.name'));
            } elseif ($msg1['code'] == '1') {
                $res = array('code' => $this->ERROR_CODE_RESULT['STATUS_CANNOT_NORMAL']);
            } else {
                //用户验证错误
                $res = $msg1;
            }
        } else {
            //缺少参数 4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
            );
        }
        echo jsonreturn($res);
    }

    /**
     * 认领公司
     */
    public function claimed_firm()
    {
        if (I('post.shehuicode') and I('post.firmname') and I('post.uid') and I('post.imei') and I('post.img')) {
            $user = new \Common\Model\UserModel();
            $msg = $user->is_judges(I('post.uid'), I('post.imei'));
            if ($msg['code'] == $this->ERROR_CODE_USER['NOT_FIRM']) {
                $firmname = I('post.firmname');
                $shehuicode = I('post.shehuicode');
                $fileDir = "./Upload/firm/" . date("Y-m-d") . "/";     //==>定义上传路径
                if (!is_dir($fileDir)) {
                    @mkdir($fileDir, 0777, true);                  //==>图片读写权限，一般都是最大：0777
                }
                $img = base64_upload(I('img'), $fileDir);
                if ($img['code'] == 0) {
                    $uid = I('post.uid');
                    $firm = new \Common\Model\FirmModel();
                    $res = $firm->claimed_firm($uid, $firmname, $shehuicode, $img['file']);
                } else {
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                        'error' => $img['msg']
                    );
                }
            } elseif ($msg['code'] == '1') {
                $res = array('code' => $this->ERROR_CODE_RESULT['STATUS_CANNOT_NORMAL']);
            } else {
                //用户验证错误
                $res = $msg;
            }
        } else {
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
                'msg' => "参数缺失",
            );
        }
        echo jsonreturn($res);
    }


    /**
     * 追加复核船信息通知
     */
    public function add_firm_review_notice()
    {
        if (I('post.uid') and I('post.imei') and I('post.review_id')) {
            $uid = intval(trimall(I('uid')));
            $imei = trimall(I('imei'));
            $review_id = intval(trimall(I('post.review_id')));

            $user = new \Common\Model\UserModel();
            $msg = $user->is_judges($uid, $imei);
            if ($msg['code'] == 1) {
                $user_info = $user->getUserOpenId($uid);
                $where = array(
                    'id' => $review_id
                );
                $data = array(
                    'open_id' => $user_info['open_id']
                );
                $firm_review = M("firm_review");
                $result = $firm_review->where($where)->save($data);
                if ($result !== false) {
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                    );
                } else {
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                        'error' => $firm_review->getDbError(),
                    );
                }
            } else {
                $res = $msg;
            }
        } else {
            //缺少参数
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 认证公司，认证后的公司无法被认领
     */
    public function legalize_firm()
    {
        $uid = I('post.uid');
        $imei = I('post.imei');
        $code = I('post.code');
//        $img = I('post.img');

        $user = new \Common\Model\UserModel();
        $msg = $user->is_judges($uid, $imei);
        if ($uid and $imei and $code) {
            if ($msg['code'] == 1) {
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断是不是公司管理员只有管理员能够认正
                    if ($user->checkAdmin($uid, $msg['content'])) {
                        $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        if ($res['mes'] == "上传成功") {
                            //将上传的图片路径放入数据库
                            $img = $res['dest'];
                            $firm = new \Common\Model\FirmModel();
                            $res = $firm->legalize_firm($uid, $code, $img);
                        }else{
                            //上传图片失败，返回报错原因 9
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                'error' => $res['mes'],
                            );
                        }
                    } else {
                        //用户不是管理员，无权限认证 1015
                        $res = array(
                            'code' => $this->ERROR_CODE_USER['USER_NOT_ADMIN']
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'error' => "需要上传一个图片"
                    );
                }
            } else {
                $res = $msg;
            }
        } else {
            //缺少参数
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
            );
        }

        //如果发生错误记录到日志
        if ($res['code'] != $this->ERROR_CODE_COMMON['SUCCESS']) {
            \Think\Log::record("\r\n \r\n [ request!!! ]  uid:$uid\r\n  imei:$imei\r\n  code:$code\r\n img:$img\r\n \r\n ", "DEBUG", true);
        }
        exit(jsonreturn($res));
    }
}