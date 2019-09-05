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
            and I('post.people') and I('post.phone') and I('post.expire_time')) {
            $data['firmname'] = I('post.firmname');
            $data['people'] = I('post.people');
            $data['phone'] = I('post.phone');
            $data['expire_time'] = I('post.expire_time');

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
                    $data['expire_time'] = strtotime($data['expire_time']);
                    // 默认个性化字段
                    $data['personality'] = json_encode(array(1, 2, 3, 4, 5, 6, 9));
                    // 默认会员收费类型，会员费，1
                    $data['membertype'] = 1;
                    //默认船类型，船公司，2
                    $data['firmtype'] = 2;
                    //默认默认合同号，标注为小程序创建
                    $data['number'] = "miniapp".time();
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
                if (I('post.firmname') and I('post.people') and I('post.phone') and I('post.expire_time') and I('firmid')) {
                    $data['firmname'] = I('post.firmname');
                    $data['people'] = I('post.people');
                    $data['phone'] = I('post.phone');
                    $data['expire_time'] = I('post.expire_time');

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
                            $data['expire_time'] = strtotime($data['expire_time']);
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
}