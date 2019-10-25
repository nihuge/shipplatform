<?php

namespace Index\Controller;

use Common\Controller\IndexBaseController;

/**
 * 船舶管理
 */
class ShipController extends IndexBaseController
{
    // 定义数据表
    private $db;

    // 构造函数 实例化ShipModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\ShipFormModel();
    }

    /**
     * 船舶列表
     */
    public function index()
    {
        $user = new \Common\Model\UserModel();
        $uid = $_SESSION['user_info']['id'];
        $usermsg = $user
            ->where(array('id' => $uid))
            ->find();
        if ($usermsg !== false or $usermsg['firmid'] !== '') {
            // 获取公司操作权限船舶
            $firm = new \Common\Model\FirmModel();
            $firmmsg = $firm
                ->where(array('id' => $usermsg['firmid']))
                ->find();
            $operation_jur = explode(',', $firmmsg['operation_jur']);
            $where = array(
                'id' => array('in', $operation_jur),
//                'del_sign' => 1
            );
            $count = $this->db->where($where)->count();
            // 分页
            $page = new \Org\Nx\Page($count, 20);

            $list = $this->db
                ->where($where)
                ->limit($page->firstRow, $page->listRows)
                ->order('id desc')
                ->select();
            $shiplist = $this->db
                ->where($where)
                ->order('id desc')
                ->select();
            if ($firmmsg['firmtype'] == '1') {
                // 检验公司获取所有的船公司
                $firmlist = $firm->field('id,firmname')->where(array('firmtype' => '2'))->select();
            } else {
                // 船舶公司获取本公司
                $firmlist = $firm->field('id,firmname')->where(array('id' => $usermsg['firmid']))->select();
            }

            $assign = array(
                'list' => $list,
                'shiplist' => $shiplist,
                'firmlist' => $firmlist,
                'page' => $page->show()
            );
            $this->assign($assign);
            $this->display();
        } else {
            $this->error('没有所属公司或者数据错误');
        }
    }

    /**
     * 新增船舶
     */
    public function addship()
    {
        if (I('post.firmid') and I('post.shipname') and I('post.coefficient') and I('post.cabinnum') and I('post.is_guanxian') and I('post.is_diliang') and I('post.suanfa')) {
            //添加数据
            $data = I('post.');
            $data['uid'] = $_SESSION['user_info']['id'];

            // 判断是否有底量测量空，有底量测量孔和纵倾修正值就算法字段为:c，没有纵倾修正表有底量测量孔算法为D
            if ($data['is_diliang'] == '1' && $data['suanfa'] == 'b') {
                $data['suanfa'] = 'c';
            } elseif ($data['is_diliang'] == '1' && $data['suanfa'] == 'a') {
                $data['suanfa'] = 'd';
            }

            $data['expire_time'] = strtotime(I('post.expire_time'));
            $res = $this->db->addship($data);
            if ($res['code'] == '1') {
                echo ajaxReturn(array("state" => 1, 'message' => "成功"));
            } else {
                echo ajaxReturn(array("state" => $res['code'], 'message' => $res['msg']));
            }
        } else {
            echo ajaxReturn(array("state" => 2, 'message' => "表单不能存在空值"));
        }
    }

    /**
     * 船舶数据
     */
    public function shipmsg()
    {
        $user = new \Common\Model\UserModel();
        $msg = $user
            ->alias('u')
            ->field('u.id,u.imei,u.firmid,f.firmtype')
            ->where(array('u.id' => $_SESSION['user_info']['id']))
            ->join('left join firm f on f.id=u.firmid')
            ->find();
        $firm = new \Common\Model\FirmModel();
        if ($msg['firmtype'] == '1') {
            // 检验公司获取所有的船公司
            $list = $firm->field('id,firmname')->where(array('firmtype' => '2'))->select();
        } else {
            // 船舶公司获取本公司
            $list = $firm->field('id,firmname')->where(array('id' => $msg['firmid']))->select();
        }
        // 船舶信息
        $shipmsg = $this->db->where(array('id' => $_POST['id']))->find();
        $string = "<div class='bar'>修改船舶</div><div class='bar1'>船舶信息</div><ul class='pass'><li><label>船舶公司：</label><p><input type='hidden' name='shipid' id='shipid' value='" . $shipmsg['id'] . "'><select name='firmid' id='firmid1' class='><option value='>请选择公司</option>";
        foreach ($list as $key => $v) {
            if ($v['id'] == $shipmsg['firmid']) {
                $select = "selected";
            } else {
                $select = '';
            }
            $string .= '<option value="' . $v['id'] . '" ' . $select . '>' . $v['firmname'] . '</option>';
        }

        $string .= "</select></p></li><li><label>船&nbsp;名</label><p><input type='text' name='shipname' placeholder='请输入船名' class='i-box' id='shipname1' maxlength='12' value='" . $shipmsg['shipname'] . "'></p></li><li><label>膨胀倍数</label><p><input type='text' name='coefficient' placeholder='请输入膨胀倍数' class='i-box' id='coefficient1' maxlength='3' value='" . $shipmsg['coefficient'] . "'><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='b()'></p></li><li><label>舱&nbsp;总&nbsp;数</label><p><input type='text' name='cabinnum' placeholder='请输入舱总数' class='i-box' id='cabinnum1' maxlength='2' value='" . $shipmsg['cabinnum'] . "'><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '船舶总共有多少舱' . '"' . ")'></p></li></ul><div class='bar1'>舱容表信息</div><ul class='pass'><li><label>管线容量</label><p><div class='radios'><label><input type='radio' name='is_guanxian1' value='1'  class='regular-checkbox' " . (($shipmsg['is_guanxian'] == '1') ? 'checked' : '') . ">&nbsp;&nbsp;包含</label><label><input type='radio' name='is_guanxian1' value='2'  class='regular-checkbox' " . (($shipmsg['is_guanxian'] == '2') ? 'checked' : '') . ">&nbsp;&nbsp;未包含</label></div><img src='./tpl/default/Index/Public/image/question.png' onclick='a(" . '"' . '舱容表所列容积值是否包含管线容量' . '"' . ")'></p></li><li><label>底量测量孔</label><p><div class='radios'><label><input type='radio' name='is_diliang1' value='1'  class='regular-checkbox' " . (($shipmsg['is_diliang'] == '1') ? 'checked' : '') . ">&nbsp;&nbsp;有</label><label><input type='radio' name='is_diliang1' value='2' class='regular-checkbox' " . (($shipmsg['is_diliang'] == '2') ? 'checked' : '') . ">&nbsp;&nbsp;无</label></div><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '部分船舶每个舱有底量和装货容量两个测量孔，相应地有两本舱容表' . '"' . ")'></p></li><li><label>纵横倾修正表</label><p><select name='suanfa' id='suanfa1' class=''><option value='a'  " . (($shipmsg['suanfa'] == 'a' or $shipmsg['suanfa'] == 'd') ? 'selected' : '') . ">无</option><option value='b'  " . (($shipmsg['suanfa'] == 'b' or $shipmsg['suanfa'] == 'c') ? 'selected' : '') . ">有</option></select><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '请查阅检定证书目录确认是否有纵倾、横倾修正表' . '"' . ")''></p></li><li><label>舱容表有效期</label><p><input type='text' class='i-box' id='dateinput1' name='expire_time1' value='" . date('Y-m-d', $shipmsg['expire_time']) . "' name='expire_time'><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '查看有效文案底部有效期' . '"' . ")''></p></li></ul><div class='bar'><input type='submit' value='取&nbsp;消' class='mmqx passbtn'><input type='submit' onclick='editr()'  value='提&nbsp;交' class='mmqd passbtn'></div>";
        //加入时间选择框的js
        $string .= <<<script
        <script>
            laydate.render({
                elem: '#dateinput1' //指定元素
                ,theme: 'grid' //主题
                ,format: 'yyyy-MM-dd' //自定义格式
                ,min: 0
            });
        </script>
script;
        echo ajaxReturn(array("state" => 1, 'message' => "成功", 'content' => $string, 'shipmsg' => $shipmsg));
    }

    /**
     * 船驳修改
     */
    public function editship()
    {
        $data = I('post.');
        // 判断提交的数据是否含有特殊字符
        $res = judgeTwoString($data);
        if ($res == false) {
            echo ajaxReturn(array("state" => 2, 'message' => "数据不能含有特殊字符"));
        } else {

            // 判断是否有底量测量空，有底量测量孔和纵倾修正值就算法字段为:c，没有纵倾修正表有底量测量孔算法为D
            if ($data['is_diliang'] == '1' && $data['suanfa'] == 'b') {
                $data['suanfa'] = 'c';
            } elseif ($data['is_diliang'] == '1' && $data['suanfa'] == 'a') {
                $data['suanfa'] = 'd';
            }

            $data['expire_time'] = strtotime(I('post.expire_time'));
            $map = array(
                'id' => $data['id']
            );
            if (!$this->db->create($data)) {
                //对data数据进行验证
                echo ajaxReturn(array("state" => 2, 'message' => $this->db->getError()));
            } else {
                $result = $this->db->editData($map, $data);
                if ($result !== false) {
                    echo ajaxReturn(array("state" => 1, 'message' => "修改成功"));
                } else {
                    echo ajaxReturn(array("state" => 2, 'message' => "修改失败"));
                }
            }
        }

    }
}