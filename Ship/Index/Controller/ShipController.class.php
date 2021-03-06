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
            ->alias('u')
            ->field('u.*,f.firm_jur')
            ->join('left join firm f on f.id = u.firmid')
            ->where(array('u.id' => $uid))
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
                'del_sign' => 1
            );
//            $count = $this->db->where($where)->count();
//            // 分页
//            $page = new \Org\Nx\Page($count, 20);

            $list = $this->db
                ->where($where)
//                ->limit($page->firstRow, $page->listRows)
                ->order('id desc')
                ->select();

            $shiplist = $this->db
                ->field('id,shipname')
                ->where($where)
                ->order('id desc')
                ->select();

            //获取正在审核状态和拒绝状态的船
            $ship_review = M("ship_review");

            $where_review = array(
                '_string' => '(status=1 or status=3) AND ((data_status = 1 and picture=2) or (data_status=2 AND picture=2 and cabin_picture=2) or (data_status=3 AND cabin_picture=2)) AND id in(SELECT max( id ) FROM ship_review GROUP BY shipid)'
            );

            $review_list = $ship_review
                ->field('shipid,status,remark')
                ->where($where_review)
                ->select();

            //匹配船使用优化写法
            $keyArr = array();
            $valArr = array();
            foreach ($review_list as $k => $v) {
                array_push($keyArr, $v['shipid']);
                array_push($valArr, array('status' => $v['status'], 'remark' => $v['remark']));
            }

            $newArr = array_combine($keyArr, $valArr); //将两个数组合并为一个，1参数为健，2参数为值，两个数组长度必须相等
            //大数组匹配小数组
            foreach ($list as $k1 => $v1) {
                if (array_key_exists($v1['id'], $newArr)) {
                    $list[$k1]['status'] = $newArr[$v1['id']]['status'];
                    if ($newArr[$v1['id']]['status'] == 3) {
                        $list[$k1]['remark'] = $newArr[$v1['id']]['remark'];
                    }
                } else {
                    $list[$k1]['status'] = "";
                }
                $list[$k1]['is_lock'] = $this->db->is_lock($v1['id']);
            }


            if ($firmmsg['firmtype'] == '1') {
                // 检验公司获取所有的船公司
                $firmlist = $firm->field('id,firmname')->where(array('firmtype' => '2','id'=>array('in',explode(',',$usermsg['firm_jur']))))->select();
            } else {
                // 船舶公司获取本公司
                $firmlist = $firm->field('id,firmname')->where(array('id' => $usermsg['firmid']))->select();
            }

            $assign = array(
                'list' => $list,
                'shiplist' => $shiplist,
                'firmlist' => $firmlist,
//                'page' => $page->show()
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
                echo ajaxReturn(array("code" => 1, 'shipid' => $res['content']['shipid'], 'message' => "成功"));
            } else {
                echo ajaxReturn(array("code" => $res['code'], 'message' => $res['msg']));
            }
        } else {
            echo ajaxReturn(array("code" => 2, 'message' => "表单不能存在空值"));
        }
    }

//    /**
//     * 船舶数据
//     */
//    public function shipmsg()
//    {
//        $user = new \Common\Model\UserModel();
//        $msg = $user
//            ->alias('u')
//            ->field('u.id,u.imei,u.firmid,f.firmtype')
//            ->where(array('u.id' => $_SESSION['user_info']['id']))
//            ->join('left join firm f on f.id=u.firmid')
//            ->find();
//        $firm = new \Common\Model\FirmModel();
//        if ($msg['firmtype'] == '1') {
//            // 检验公司获取所有的船公司
//            $list = $firm->field('id,firmname')->where(array('firmtype' => '2'))->select();
//        } else {
//            // 船舶公司获取本公司
//            $list = $firm->field('id,firmname')->where(array('id' => $msg['firmid']))->select();
//        }
//        // 船舶信息
//        $shipmsg = $this->db->where(array('id' => trimall($_POST['id'])))->find();
//        $string = "<div class='bar'>修改船舶</div><div class='bar1'>船舶信息</div><ul class='pass'><li><label>船舶公司：</label><p><input type='hidden' name='shipid' id='shipid' value='" . $shipmsg['id'] . "'><select name='firmid' id='firmid1' class='><option value='>请选择公司</option>";
//        foreach ($list as $key => $v) {
//            if ($v['id'] == $shipmsg['firmid']) {
//                $select = "selected";
//            } else {
//                $select = '';
//            }
//            $string .= '<option value="' . $v['id'] . '" ' . $select . '>' . $v['firmname'] . '</option>';
//        }
//
//        $string .= "</select></p></li><li><label>船&nbsp;名</label><p><input type='text' name='shipname' placeholder='请输入船名' class='i-box' id='shipname1' maxlength='12' value='" . $shipmsg['shipname'] . "'></p></li><li><label>膨胀倍数</label><p><input type='text' name='coefficient' placeholder='请输入膨胀倍数' class='i-box' id='coefficient1' maxlength='3' value='" . $shipmsg['coefficient'] . "'><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='b()'></p></li><li><label>舱&nbsp;总&nbsp;数</label><p><input type='text' name='cabinnum' placeholder='请输入舱总数' class='i-box' id='cabinnum1' maxlength='2' value='" . $shipmsg['cabinnum'] . "'><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '船舶总共有多少舱' . '"' . ")'></p></li></ul><div class='bar1'>舱容表信息</div><ul class='pass'><li><label>管线容量</label><p><div class='radios'><label><input type='radio' name='is_guanxian1' value='1'  class='regular-checkbox' " . (($shipmsg['is_guanxian'] == '1') ? 'checked' : '') . ">&nbsp;&nbsp;包含</label><label><input type='radio' name='is_guanxian1' value='2'  class='regular-checkbox' " . (($shipmsg['is_guanxian'] == '2') ? 'checked' : '') . ">&nbsp;&nbsp;未包含</label></div><img src='./tpl/default/Index/Public/image/question.png' onclick='a(" . '"' . '舱容表所列容积值是否包含管线容量' . '"' . ")'></p></li><li><label>底量测量孔</label><p><div class='radios'><label><input type='radio' name='is_diliang1' value='1'  class='regular-checkbox' " . (($shipmsg['is_diliang'] == '1') ? 'checked' : '') . ">&nbsp;&nbsp;有</label><label><input type='radio' name='is_diliang1' value='2' class='regular-checkbox' " . (($shipmsg['is_diliang'] == '2') ? 'checked' : '') . ">&nbsp;&nbsp;无</label></div><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '部分船舶每个舱有底量和装货容量两个测量孔，相应地有两本舱容表' . '"' . ")'></p></li><li><label>纵横倾修正表</label><p><select name='suanfa' id='suanfa1' class=''><option value='a'  " . (($shipmsg['suanfa'] == 'a' or $shipmsg['suanfa'] == 'd') ? 'selected' : '') . ">无</option><option value='b'  " . (($shipmsg['suanfa'] == 'b' or $shipmsg['suanfa'] == 'c') ? 'selected' : '') . ">有</option></select><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '请查阅检定证书目录确认是否有纵倾、横倾修正表' . '"' . ")''></p></li><li><label>舱容表有效期</label><p><input type='text' class='i-box' id='dateinput1' name='expire_time1' value='" . date('Y-m-d', $shipmsg['expire_time']) . "' name='expire_time'><img src='./tpl/default/Index/Public/image/question.png' class='wenimg' onclick='a(" . '"' . '查看有效文案底部有效期' . '"' . ")''></p></li></ul><div class='bar'>";
//
//        //如果船舶被锁住,则需要上传图片
//        if ($this->db->is_lock(trimall(I('post.id')))) {
//            $string .= <<<html
//            <div class="layui-upload" style="margin-left: 20px;text-align: left";>
//              <button type="button" class="layui-btn" id="select_img">选择文件</button>
//              <blockquote class="layui-elem-quote layui-quote-nm" style="margin-top: 10px; text-align: left;">
//                预览：
//                <div class="layui-upload-list" id="view_img"></div>
//             </blockquote>
//            </div>
//            <button style="visibility:hidden;" id="upload_img"></button>
//            <input type='submit' value='取&nbsp;消' class='mmqx passbtn'>
//            <input type='submit' onclick='editr()'  value='提交复核' class='mmqd passbtn'></div>
//            <script>
//            layui.use('upload', function(){
//                          var $ = layui.jquery
//                          ,upload = layui.upload;
//                          //多图片上传
//                          img_btn_obj = upload.render({
//                            elem: '#select_img'
//                            ,auto: false
//                            ,bindAction: '#upload_img'
//                            ,multiple: true
//                            ,exts:"jpg|png|gif|bmp|jpeg"
//                            ,acceptMime:"images"
//                            ,accept:"images"
//                            ,choose: function(obj){
//                              //记录选择了多少张图片
//                              img_count = obj.upload.length;
//                              //清空预览区
//                              $('#view_img').html('');
//                              //预读本地文件示例，不支持ie8
//                              obj.preview(function(index, file, result){
//                                $('#view_img').append('<img src="'+ result +'" alt="'+ file.name +'" class="layui-upload-img">')
//                              });
//                            }
//                            ,allDone: function(obj){
//                              //上传完毕
//                              if(obj.aborted == 0){
//                                close_loading();
//                                layer.msg("提交到后台成功,请耐心等待后台复核", {icon: 1});
//                                setTimeout(function () {
//                                    location.reload();
//                                }, 2000);
//                              }
//                            }
//                          });
//                        });
//            </script>
//html;
//        } else {
//            $string .= "<input type='submit' value='取&nbsp;消' class='mmqx passbtn'><input type='submit' onclick='editr()'  value='提&nbsp;交' class='mmqd passbtn'></div>";
//        }
//
//        //加入时间选择框的js
//        $string .= <<<script
//        <script>
//            laydate.render({
//                elem: '#dateinput1' //指定元素
//                ,theme: 'grid' //主题
//                ,format: 'yyyy-MM-dd' //自定义格式
//                ,min: 0
//            });
//        </script>
//script;
//        echo ajaxReturn(array("state" => 1, 'message' => "成功", 'content' => $string, 'shipmsg' => $shipmsg));
//    }

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
        $shipmsg = $this->db->where(array('id' => trimall($_POST['id'])))->find();
        echo ajaxReturn(array("code" => 1, 'message' => "成功", 'list' => $list, 'shipmsg' => $shipmsg,'is_lock'=>$this->db->is_lock(trimall(I('post.id')))));
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
            echo ajaxReturn(array("code" => 2, 'message' => "数据不能含有特殊字符"));
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

            /**
             * 查找船的作业次数
             */
//            $work = new \Common\Model\WorkModel();
//            $res_count = $work->where(array('shipid' => $data['id']))->count();

            $old_info = $this->db->field('is_lock,shipname,cabinnum,coefficient,is_guanxian,is_diliang,suanfa,expire_time,review')->where($map)->find();

            //验证船名是否和已有的船名重复
            $name_count = $this->db->where(array('shipname' => $data['shipname'], 'id' => array('neq', $data['id'])))->count();

            if ($name_count > 0) {
                //船舶已存在   2014
                exit(ajaxReturn(array("code" => 5, 'message' => "船舶名称已存在")));
            }

            if ($data['cabinnum'] < $old_info['cabinnum']) {
                //不可以减少舱总数，2026
                exit(ajaxReturn(array("code" => 4, 'message' => "舱总数不可以被减少")));
            }

            if ($old_info['is_lock'] == 1) {

                //开始对比数据差异，获取更改的数据
                unset($old_info['review']);

                //占位数组，防止重复提交时有些值没有被覆盖掉
                $tpl_data = array(
                    'shipname' => null,
                    'cabinnum' => null,
                    'coefficient' => null,
                    'is_guanxian' => null,
                    'is_diliang' => null,
                    'suanfa' => null,
                    'expire_time' => null,
                );

                //对比差异
                $diff_info = array_diff_assoc($old_info, $data);

                //新值赋值
                foreach ($diff_info as $key => $value) {
                    $diff_info[$key] = $data[$key];
                }

                $ship_review = M("ship_review");
                if ($diff_info['shipname'] !== null) {
                    //验证船名是否和正在审核中其他船的船名重复
                    $review_name_count = $ship_review->where(array(
                        'shipname' => $diff_info['shipname'],
                        'shipid' => array('neq', $data['id']),
                        'status' => 1
                    ))->count();

                    if ($review_name_count > 0) {
                        //船舶已存在   2014
                        exit(ajaxReturn(array("code" => 5, 'message' => "船舶名称已存在")));
                    }
                }

                $review_data = array_merge($tpl_data, $diff_info);

                $review_data['shipid'] = $data['id'];
                $review_data['userid'] = $_SESSION['user_info']['id'];
                $review_data['create_time'] = time();

                $review_map = array(
                    'shipid' => $data['id'],
                    'status' => 1
                );

                /**
                 * 重复上传会覆盖。以最新的为准
                 */
                M()->startTrans();
                $review_count = $ship_review->where($review_map)->count();
                if ($review_count >= 1) {
                    //修改
                    $result = $ship_review->where($review_map)->save($review_data);
                    //修改时获取主键ID
                    if ($result !== false) {
                        $id = $ship_review->field('id,data_status,cabin_picture,picture')->where($review_map)->find();
                        $result = (int)$id['id'];
                        if ($id['data_status'] == 3 and $id['cabin_picture'] == 1) {
                            //如果状态是上传舱信息但没有舱照片则改为只上传了船信息
                            $status_data = array(
                                'data_status' => 1
                            );
                            $status_result = $ship_review->where($review_map)->save($status_data);
                            if ($status_result === false) {
                                M()->rollback();
                                //修改失败,错误11
                                exit(ajaxReturn(array("code" => 3, 'message' => "提交复核请求失败")));
                            }
                        }
                    }
                } else {
                    //新建
                    $result = $ship_review->add($review_data);
                }
                if ($result !== false) {
                    M()->commit();
                    //等待审核
                    echo ajaxReturn(array("code" => 200, 'review_id' => $result, 'message' => "复核请求成功，请上传图片"));
//                                    echo ajaxReturn($res);
                } else {
                    M()->rollback();
                    //修改失败,错误11
                    echo ajaxReturn(array("code" => 3, 'message' => "提交复核请求失败"));
                }
            } else {
                $result = $this->db->editData($map, $data);
                if ($result !== false) {
                    //成功
                    echo ajaxReturn(array("code" => 1, 'message' => "修改成功"));
                } else {
                    echo ajaxReturn(array("code" => 2, 'message' => "修改失败"));

                }
            }
        }




//        if (!$this->db->create($data)) {
//                //对data数据进行验证
//                echo ajaxReturn(array("state" => 2, 'message' => $this->db->getError()));
//            } else {
//                $result = $this->db->editData($map, $data);
//                if ($result !== false) {
//                    echo ajaxReturn(array("state" => 1, 'message' => "修改成功"));
//                } else {
//                    echo ajaxReturn(array("state" => 2, 'message' => "修改失败"));
//                }
//            }
//        }

    }

    /**
     * 上传舱容表
     */
    public function table_review(){
        if (I('post.shipname') and I('post.type')) {
            $uid = $_SESSION['user_info']['id'];
            $shipname = trimall(I('post.shipname'));
            $type = intval(trimall(I('post.type')));
            $user = new \Common\Model\UserModel();
            $msg =$user->is_judge($uid);
            if($msg['code'] == 1){
                $res = $this->db->up_table($uid,$type,$shipname);
            }else{
                $res = $msg;
            }
        }else{
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        echo jsonreturn($res);
    }

}