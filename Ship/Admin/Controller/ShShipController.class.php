<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;

/**
 * 散货船舶管理
 * */
class ShShipController extends AdminBaseController
{
    /**
     * 船列表
     */
    public function index()
    {
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString(I('get.'));
        if ($res == false) {
            $this->error('数据不能含有特殊字符');
            exit;
        }

        $ship = new \Common\Model\ShShipModel();
        $where = array('1');
        if (I('get.firmid') != '') {
            $where['firmid'] = I('get.firmid');
        }
        if (I('get.shipname') != '') {
            $where['shipname'] = array('like', '%' . I('get.shipname') . '%');
        }
        if (I('get.del_sign') != '') {
            $where['s.del_sign'] = trimall(I('get.del_sign'));
        } else {
            //默认查找没被删除的船
            $where['s.del_sign'] = 1;
        }

        $count = $ship
            ->alias('s')
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

        $data = $ship
            ->alias('s')
            ->field('s.id,s.shipname,s.number,s.data_ship,s.cabinnum,f.firmname,s.del_sign')
            ->where($where)
            ->join('left join firm f on f.id=s.firmid')
            ->order('s.id desc,f.firmname desc')
            ->limit($begin, $per)
            ->select();

        // 获取所有公司列表
        $firm = new \Common\Model\FirmModel();
        $firmlist = $firm->field('id,firmname')->select();

        $assign = array(
            'data' => $data,
            'page' => $page,
            'firmlist' => $firmlist,
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 新增船
     * */
    public function add()
    {
        if (IS_POST) {
            $data = I('post.');
            unset($data['img']);
            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                $this->error('数据不能含有特殊字符');
                exit;
            }

            $data['img'] = I('post.img');
            $data['expire_time'] = strtotime(I('post.expire_time'));
            $ship = new \Common\Model\ShShipModel();
            // 判断船舶是否存在
            $count = $ship->where(array('shipname' => $data['shipname']))->count();
            if ($count == 0) {
                if (!$ship->create($data)) {
                    //对data数据进行验证
                    $this->error($ship->getError());
                } else {
                    $res = $ship->addData($data);
                    if ($res !== false) {
                        // 新增船舶创建表、添加船舶历史数据汇总初步
                        $this->success('添加成功', U('index'));
                    } else {
                        $this->error('添加失败');
                    }

                }
            } else {
                $this->error('添加失败，船舶名已存在');
            }
        } else {
            // 获取船舶公司列表
            $firm = new \Common\Model\FirmModel();
            $where = array(
                'firmtype' => array('eq', '2'),
                'del_sign' => 1,
            );
            $firmlist = $firm
                ->field('id,firmname')
                ->where($where)
                ->order('id asc')
                ->select();
            $assign = array(
                'firmlist' => $firmlist
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 修改船信息
     */
    public function edit()
    {
        $ship = new \Common\Model\ShShipModel();
        if (IS_POST) {
            $data = I('post.');

            //不验证图片路径的特殊字符，因为路径本身含有
            unset($data['img']);

            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                $this->error('数据不能含有特殊字符','',500);
                exit;
            }

            $data['img'] = I('post.img');
            // p($data);die;
            $data['expire_time'] = strtotime(I('post.expire_time'));
            // 判断船舶是否存在
            $count = $ship
                ->where(array('shipname' => $data['shipname'], 'id' => array('neq', $data['id'])))
                ->count();
            if ($count == 0) {
                $map = array(
                    'id' => $data['id']
                );
                if (!$ship->create($data)) {
                    //对data数据进行验证
                    $this->error($ship->getError());
                } else {
                    $result = $ship->editData($map, $data);
                    if ($result !== false) {
                        $this->success('修改成功');
                    } else {
                        $this->error('修改失败');
                    }
                }
            } else {
                $this->error('添加失败，船舶名已存在');
            }
        } else {
            // 获取公司列表
            $firm = new \Common\Model\FirmModel();
            $firmlist = $firm
                ->field('id,firmname')
                ->where(array('firmtype' => array('eq', '2')))
                ->order('id asc')
                ->select();
            //船信息
            $shipmsg = $ship
                ->where(array('id' => I('get.id')))
                ->find();
            $assign = array(
                'shipmsg' => $shipmsg,
                'firmlist' => $firmlist,
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /**
     * 软删除船舶操作
     * 不是真的删除，只是增加删除标记，并且不会在正常业务中出现此船
     */
    public function del_ship()
    {
        if (IS_AJAX) {
            $shipid = trimall(I('post.shipid'));
            $ship = new \Common\Model\ShShipModel();
            $work = new \Common\Model\ShResultModel();

            //查找有没有关于这个船的作业
            $workCount = $work->where(array('shipid' => $shipid, 'del_sign' => 1))->count();

            if ($workCount <= 0 and $workCount !== false) {
                //如果这个船没有作业，则可以删除
                $where = array(
                    'id' => $shipid,
                    'del_sign' => 1
                );

                $resultCount = $ship->editData($where, array('del_sign' => 2));

                if ($resultCount > 0) {
                    //如果影响行数大于0
                    $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 11, 'msg' => '该船未找到或已被删除'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该船还有作业未被删除，请删除作业后重新尝试'));
            }
        }
    }


    /**
     * 恢复船舶操作
     * 恢复船舶，前提要求公司没有被删除
     */
    public function recoverShip()
    {
        if (IS_AJAX) {
            $shipid = trimall(I('post.shipid'));
            $ship = new \Common\Model\ShShipModel();
            $firm = new \Common\Model\FirmModel();

            //查找这个船属公司是否被删除
            $firmid = $ship->field('firmid')->where(array('shipid' => $shipid))->find();
            $firmCount = $firm->where(array('id' => $firmid['firmid'], 'del_sign' => 1))->count();

            if ($firmCount > 0) {
                //如果这个船属公司没被删除
                $where = array(
                    'id' => $shipid,
                    'del_sign' => 2
                );
                $resultCount = $ship->editData($where, array('del_sign' => 1));
                if ($resultCount > 0) {
                    //如果影响行数大于0
                    $this->ajaxReturn(array('code' => 1, 'msg' => '恢复成功'));
                } else {
                    $this->ajaxReturn(array('code' => 11, 'msg' => '该船未找到或已被恢复'));
                }
            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该船所属公司未被恢复，请恢复公司后重试'));
            }
        }
    }

    /**
     * 真删除船舶
     * 真正删除，数据无法恢复，除非备份。
     * 删除前检测是否存在该船下未被真正删除的作业，如果存在则不允许删除
     */
    public function relDelShip()
    {
        if (IS_AJAX) {
            $shipid = trimall(I('post.shipid'));
            $ship = new \Common\Model\ShShipModel();
            $work = new \Common\Model\ShResultModel();

            //查找有没有关于这个船的作业
            $workCount = $work->where(array('shipid' => $shipid))->count();

            if ($workCount !== false and $workCount <= 0) {
                //如果这个船没有作业，则可以删除
                $where = array(
                    'id' => $shipid,
                );

                //删除船信息
                $shipDelResult = $ship->where($where)->delete();

                if ($shipDelResult !== false) {
                    //如果没有删除失败
                    $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功'));
                } else {
                    $this->ajaxReturn(array('code' => 2, 'msg' => '彻底删除船时有部分数据删除失败,请联系技术人员'));
                }

            } else {
                $this->ajaxReturn(array('code' => 11, 'msg' => '该船下有作业未被彻底删除，请将该船下的所有作业彻底删除后重试'));
            }
        }
    }
}