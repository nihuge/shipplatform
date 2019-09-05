<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

/**
 * 公司管理
 * 2018.4.25
 */
class FirmController extends AdminBaseController
{
    /**
     * 公司列表
     * */
    public function index()
    {
        $firm = new \Common\Model\FirmModel();

        if (I('get.del_sign') != '') {
            $where['del_sign'] = trimall(I('get.del_sign'));
        } else {
            //默认查找没被删除的船
            $where['del_sign'] = 1;
        }


        $count = $firm
            ->where($where)
            ->count();
        $per = 30;
        if ($_GET['p']) {
            $p = $_GET['p'];
        } else {
            $p = 1;
        }
        //分页
        $page = fenye($count, $per);
        $begin = ($p - 1) * $per;


        $data = $firm
            ->field('*')
            ->where($where)
            ->order('id asc')
            ->limit($begin, $per)
            ->select();
        $assign = array(
            'data' => $data,
            'page' => $page
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 新增公司
     * */
    public function add()
    {
        if (IS_POST) {
            $data = I('post.');
            $logo = I('post.logo');
            $img = I('post.img');
            $image = I('post.image');
            unset($data['logo']);
            unset($data['photo']);

            unset($data['photo1']);
            unset($data['img']);

            unset($data['photo2']);
            unset($data['image']);

            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                $this->error('数据不能含有特殊字符');
                exit;
            }
            if (!empty($logo)) {
                $data['logo'] = $logo;
            }
            if (!empty($img)) {
                $data['img'] = $img;
            }
            if (!empty($image)) {
                $data['image'] = $image;
            }
            $data['expire_time'] = strtotime($data['expire_time']);

            // 默认个性化字段
            $data['personality'] = json_encode(array(1, 2, 3, 4, 5, 6, 9));

            $firm = new \Common\Model\FirmModel();
            // 对数据进行验证
            if (!$firm->create($data)) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $this->error($firm->getError());
            } else {
                // 验证通过 可以进行其他数据操作
                $res = $firm->addData($data);
                if ($res) {
                    // 添加公司历史数据汇总初步
                    $arr = array('firmid' => $res);
                    M('firm_historical_sum')->add($arr);

                    $this->success('新增成功！', U('index'));
                } else {
                    $this->error('新增失败！');
                }
            }
        } else {
            $this->display();
        }
    }

    /**
     * 公司修改
     * */
    public function edit()
    {
        $firm = new \Common\Model\FirmModel();
        if (IS_POST) {
            $data = I('post.');
            $logo = I('post.logo');
            $img = I('post.img');
            $image = I('post.image');

            unset($data['logo']);
            unset($data['photo']);

            unset($data['photo1']);
            unset($data['img']);

            unset($data['photo2']);
            unset($data['image']);
            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                $this->error('数据不能含有特殊字符');
                exit;
            }
            if (!empty($logo)) {
                $data['logo'] = $logo;
            }
            if (!empty($img)) {
                $data['img'] = $img;
            }
            if (!empty($image)) {
                $data['image'] = $image;
            }
            $data['expire_time'] = strtotime($data['expire_time']);
            // 对数据进行验证
            if (!$firm->create($data)) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $this->error($firm->getError());
            } else {
                // 验证通过 可以进行其他数据操作
                $map = array(
                    'id' => $data['id']
                );
                unset($data['id']);    // 删除id
                $res = $firm->editData($map, $data);
                if ($res !== false) {
                    $this->success('修改成功！', U('index'));
                } else {
                    $this->error('修改失败！');
                }
            }
        } else {
            // 获取数据
            $where = array(
                'id' => I('get.id')
            );
            $data = $firm
                ->where($where)
                ->find();
            if (!empty($data) and $data !== false) {
                $assign = array(
                    'data' => $data
                );
                $this->assign($assign);
                $this->display();
            } else {
                $this->error('获取数据失败！');
            }
        }
    }

    /**
     * 获取管理员/新增管理员
     * */
    public function adminmsg()
    {
        $user = new \Common\Model\UserModel();
        if (IS_POST) {
            // 判断提交的数据是否含有特殊字符
            $res = judgeOneString(I('post.title'));
            if ($res == true) {
                $this->error('账号不能含有特殊字符');
                exit;
            }

            $pwd = "000000";
            $data = I('post.');
            $data['pwd'] = encrypt($pwd);    //加密
            if (!$user->create($data)) {
                //对data数据进行验证
                $this->error($user->getError());
            } else {
                //区分新增还是修改
                if (empty($data['id'])) {
                    $res = $user->addData($data);
                    if ($res) {
                        $this->success('新增管理用户成功');
                    } else {
                        $this->error('新增管理用户失败');
                    }
                } else {
                    $map = array(
                        'id' => $data['id']
                    );
                    $res = $user->editData($map, $data);
                    if ($res !== false) {
                        $this->success('修改管理用户成功');
                    } else {
                        $this->error('修改管理用户失败');
                    }
                }
            }
        } else {
            $firm = new \Common\Model\FirmModel();
            $firmid = intval($_GET['id']);

            $map = array(
                'firmid' => $firmid,
                'pid' => '0'
            );
            $usermsg = $user
                ->field('id,title,username,phone')
                ->where($map)
                ->find();
            if ($usermsg !== false) {
                // 获取公司名
                $firmname = $firm->getFieldById($firmid, 'firmname');
                $assign = array(
                    'id' => $firmid,
                    'firmname' => $firmname,
                    'usermsg' => $usermsg
                );
                $this->assign($assign);
                // p($assign);die;
                $this->display();
            } else {
                $this->error('操作失败');
            }

        }
    }

    /**
     * 公司配置操作权限
     * */
    public function configOperator()
    {
        $firm = new \Common\Model\FirmModel();
        if (IS_POST) {
            // 判断提交的操作权限是否超出限制
            $msg = $firm
                ->field('limit,search_jur')
                ->where(array('id' => I('post.id')))
                ->find();
            $operation_jur = I('post.operation_jur');
            if (!empty($operation_jur) and isset($operation_jur)) {
                if ($msg['limit'] < count(I('post.operation_jur'))) {
                    $this->error("超出公司限制船舶数");
                    exit;
                }
            }
            // 判断查询条件是否为空，为空时，操作权限就是查询权限
            $data = I('post.');
            $operation_jur = implode(',', I('post.operation_jur'));
            $data['operation_jur'] = $operation_jur;
            $data['firm_jur'] = implode(',', I('post.firm_jur'));

            if (empty($msg['search_jur'])) {
                $data['search_jur'] = $operation_jur;
            }

            $map = array(
                'id' => $data['id']
            );
            if (!$firm->create($data)) {
                //对data数据进行验证
                $this->error($firm->getError());
            } else {
                // 修改用户信息
                $resu = $firm->editData($map, $data);
                if ($resu !== false) {
                    //修改公司权限同时也给管理员相应的权限
                    $userDo = new \Common\Model\UserModel();
                    $firm_admin_id = $userDo->field('id')->where(array('firmid' => $data['id'], 'pid' => 0))->find();
                    $admin_map = array(
                        'id' => $firm_admin_id['id'],
                    );
                    $admin_data = array(
                        'operation_jur' => $operation_jur,
                    );
                    $admin_result = $userDo->editData($admin_map, $admin_data);
                    if ($admin_result !== false) {
                        //$this->success('修改操作权限成功!', U('User/index', array('firmid' => $data['id'])),$mixed=30);
                        $this->success('修改操作权限成功!', U('User/index', array('firmid' => $data['id'])));
                    } else {
                        $this->error("公司权限修改成功，管理员权限修改失败！", U('User/index', array('firmid' => $data['id'])), $mixed = 10);
                    }
                } else {
                    $this->error("修改操作权限失败！");
                }
            }
        } else {
            // 获取公司列表及公司名下所有船列表
            $ship = new \Common\Model\ShipModel();
            $firmShipList = $ship->getShipList(I('get.id'), I('get.firmtype'));
            // 获取公司操作权限
            $data = $firm->getFirmOperationSearch(I('get.id'));

            $assign = array(
                'data' => $data,
                'firmtype' => I('get.firmtype'),
                'firmShipList' => $firmShipList
            );
            $this->assign($assign);
            $this->display();
        }
    }


    /**
     * 配置查询条件
     * */
    public function configSearch()
    {
        $firm = new \Common\Model\FirmModel();
        if (IS_POST) {
            $map = array(
                'id' => I('post.id')
            );
            if (I('post.search_jur')) {
                // 将数组转换字符串
                $search_jur = implode(',', I('post.search_jur'));
                $data['search_jur'] = $search_jur;
            } else {
                $data['search_jur'] = '';
            }

            if (!$firm->create($data)) {
                //对data数据进行验证
                $this->error($firm->getError());
            } else {
                // 修改用户查询条件
                $resu = $firm->editData($map, $data);
                if ($resu !== false) {
                    $this->success('修改用户查询条件成功!', U('index'));
                } else {
                    $this->error("修改用户查询条件失败！");
                }
            }
        } else {
            // 获取公司列表及公司名下所有船列表
            $ship = new \Common\Model\ShipModel();
            $firmShipList = $ship->getShipList(I('get.id'), I('get.firmtype'));
            // 获取公司操作权限
            $data = $firm->getFirmOperationSearch(I('get.id'));
            $assign = array(
                'data' => $data,
                'firmtype' => I('get.firmtype'),
                'firmShipList' => $firmShipList
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 个性化字段新增
     */
    public function addpersonality()
    {
        $firm = new \Common\Model\FirmModel();
        $personality = new \Common\Model\PersonalityModel();
        if (IS_POST) {
            $arr = I('post.');
            // p($arr);die;
            // 提交的数据处理   删除值为空的数据，删除键名为personality的值
            foreach ($arr as $key => $value) {
                if ($value == null || $key == 'personality' || $key == 'sub' || $key == 'id') {
                    unset($arr[$key]);
                }
            }
            if (empty($arr)) {
                $personality = '';
            } else {
                // 升 排序
                asort($arr);
                // 获取排序后的个性化ID
                $b = array_keys($arr);
                $personality = json_encode($b);
            }
            $data = array(
                'personality' => $personality
            );
            $map = array('id' => I('post.id'));

            if (!$firm->create($data)) {
                //对data数据进行验证
                $this->error($firm->getError());
            } else {
                $res = $firm->editData($map, $data);
                if ($res !== false) {
                    $this->success('成功', U('addpersonality', array('id' => I('post.id'))));
                } else {
                    $this->error('失败', U('addpersonality', array('id' => I('post.id'))));
                }
            }
        } else {
            $perlist = $personality->select();
            // 字段列表
            $personality = $firm->getFieldById(I('get.id'), 'personality');
            $data = json_decode($personality, true);

            $assign = array(
                'perlist' => $perlist,
                'data' => $data,
                'id' => I('get.id')
            );
            // p($assign);die;
            $this->assign($assign);
            $this->display();
        }

    }


    /**
     * ajax图片上传
     * */
    public function upload_ajax()
    {
        if (IS_AJAX) {
            $base64_image_content = $_POST['image'];
            $res = upload_ajax($base64_image_content);
            $this->ajaxReturn(json_encode($res));
        }
    }


    /**
     * 软删除公司操作
     * 不是真的删除，只是增加删除标记，并且不会在正常业务中出现
     */
    public function del_firm()
    {
        if (IS_AJAX) {
            $firmid = trimall(I('post.firmid'));
            $ship = new \Common\Model\ShipModel();
            $firm = new \Common\Model\FirmModel();

            //查找有没有关于这个船的作业
            $shipCount = $ship->where(array('firmid' => $firmid, 'del_sign' => 1))->count();

            if ($shipCount <= 0 and $shipCount !== false) {
                //如果这个船没有作业，则可以删除
                $where = array(
                    'id' => $firmid,
                    'del_sign' => 1
                );
                $resultCount = $firm->editData($where, array('del_sign' => 2));

                //冻结该公司下所有用户
                $user = new \Common\Model\UserModel();
                $userForzeResult = $user->editData(array(
                    'firmid' => $firmid
                ), array('status' => 2));

                if ($resultCount > 0 and $userForzeResult !== false) {
                    //如果影响行数大于0
                    $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 11, 'msg' => '该公司未找到或已被删除'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该公司还有船未被删除，请删除船后重新尝试'));
            }
        }
    }


    /**
     * 恢复公司操作
     * 恢复公司
     */
    public function recoverFirm()
    {
        if (IS_AJAX) {
            $firmid = trimall(I('post.firmid'));
            $firm = new \Common\Model\FirmModel();
            //如果这个船属公司没被删除
            $where = array(
                'id' => $firmid,
                'del_sign' => 2
            );
            $resultCount = $firm->editData($where, array('del_sign' => 1));
            if ($resultCount > 0) {
                //如果影响行数大于0
                $this->ajaxReturn(array('code' => 1, 'msg' => '恢复成功'));
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该公司未找到或已被恢复'));
            }
        }
    }

    /**
     * 真删除公司
     * 真正删除，数据无法恢复，除非备份。
     * 删除前检测是否存在该公司下未被真正删除的船，如果存在则不允许删除
     */
    public function relDelFirm()
    {
        if (IS_AJAX) {
            $firmid = trimall(I('post.firmid'));
            $ship = new \Common\Model\ShipModel();
            $firm = new \Common\Model\FirmModel();

            //查找有没有关于这个公司的船
            $workCount = $ship->where(array('firmid' => $firmid))->count();

            if ($workCount !== false and $workCount <= 0) {
                //如果这个船没有作业，则可以删除
                $where = array(
                    'id' => $firmid,
                );

                //删除公司信息
                $firmDelResult = $firm->where($where)->delete();

                //删除公司所属员工
                $user = new \Common\Model\UserModel();
                $userDelResult = $user->where(array('firmid' => $firmid))->delete();

                if ($firmDelResult !== false and $userDelResult !== false) {
                    //如果没有删除失败
                    $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 2, 'msg' => '彻底删除公司失败,请联系技术人员'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该公司下有船未被彻底删除，请将该公司的所有船彻底删除后重试'));
            }
        }
    }
}