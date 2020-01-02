<?php

namespace Index\Controller;

use Common\Controller\IndexBaseController;

class CabinController extends IndexBaseController
{
    // 定义数据表
    private $db;

    // 构造函数 实例化ShipModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\CabinModel();
    }

    /**
     * 船舱
     */
    public function index()
    {
        $where[] = '1';
        $ship_id = I('get.shipid');
        if (I('get.shipid')) {
            $where['c.shipid'] = $ship_id;
        }

        $count = $this->db
            ->alias('c')
            ->field('s.shipname')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 20);

        $data = $this->db
            ->field('c.id,c.cabinname,c.altitudeheight,c.bottom_volume,c.pipe_line,c.shipid,s.shipname,s.tankcapacityshipid,s.rongliang,s.rongliang_1,s.zx,s.zx_1')
            ->alias('c')
            ->join('left join ship s on s.id=c.shipid')
            ->where($where)
            ->order('c.shipid asc,c.id asc')
            ->limit($page->firstRow, $page->listRows)
            ->select();

        //获取船列表
        $ship = new \Common\Model\ShipModel();
        $shiplist = $ship
            ->field('id,shipname')
            ->order("shipname desc")
            ->select();
        $assign = array(
            'data' => $data,
            'page' => $page->show(),
            'shiplist' => $shiplist
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 获取舱数据
     */
    public function cabinmsg()
    {
        // 船舱数据信息
        $cabinmsg = $this->db->where(array('id' => $_POST['id']))->find();

        $firm = new \Common\Model\FirmModel();
        $user = new \Common\Model\UserModel();
        $uid = $_SESSION['user_info']['id'];
        $usermsg = $user
            ->where(array('id' => $uid))
            ->find();
        $firmmsg = $firm
            ->where(array('id' => $usermsg['firmid']))
            ->find();
        $operation_jur = explode(',', $firmmsg['operation_jur']);
        $ship = new \Common\Model\ShipFormModel();
        $where = array(
            'id' => array('in', $operation_jur)
        );
        $shiplist = $ship
            ->where($where)
            ->order('id desc')
            ->select();
        $is_diliang = $ship->getFieldById($cabinmsg ['shipid'], 'is_diliang');
        if ($is_diliang == '1') {
            $dis = "";
        } else {
            $dis = "style='display: none;'";
        }


        $string = "<div class='bar'>修改船舱</div><div class='bar1'>基本信息</div><input type='hidden' name='cabinid' id='cabinid' value='" . $cabinmsg['id'] . "'><ul class='pass'><li><label>所属船舶</label><p><select name='shipidd' id='shipiddd'><option value=''>请选择所属船舶</option>";
        foreach ($shiplist as $k => $v) {
            if ($cabinmsg['shipid'] == $v['id']) {
                $select = 'selected';
            } else {
                $select = '';
            }

            $string .= "<option value='" . $v['id'] . "' " . $select . ">" . $v['shipname'] . "</option>";
        }

        $string .= "</select></p></li><li><label>舱&nbsp;名</label><p><input type='text' name='cabinname' placeholder='请输入舱名' class='i-box' id='cabinname' maxlength='12' value='" . $cabinmsg['cabinname'] . "'></p></li><li><label>管线数量</label><p><input type='text' name='pipe_line' placeholder='请输入管线数量' class='i-box' id='pipe_line' maxlength='5' value='" . $cabinmsg['pipe_line'] . "'></p></li></ul><div class='bar1'>容量表信息</div><ul class='pass'><li><label>基准高度</label><p><input type='text' name='altitudeheight' placeholder='请输入基准高度' class='i-box' id='altitudeheight' maxlength='5' value='" . $cabinmsg['altitudeheight'] . "'></p></li> <li><label>底&nbsp;量</label><p><input type='text' name='bottom_volume' placeholder='请输入底量' class='i-box' id='bottom_volume' maxlength='5' value='" . $cabinmsg['bottom_volume'] . "'></p></li></ul><div " . $dis . " id='hiden'><div class='bar1'>底量表信息</div><ul class='pass'><li><label>基准高度</label><p><input type='text' name='dialtitudeheight' placeholder='请输入基准高度' class='i-box' id='dialtitudeheight' maxlength='5' value='" . $cabinmsg['dialtitudeheight'] . "'></p></li> <li><label>底&nbsp;量</label><p><input type='text' name='bottom_volume_di' placeholder='请输入底量' class='i-box' id='bottom_volume_di' maxlength='5' value='" . $cabinmsg['bottom_volume_di'] . "'></p></li></ul></div><div class='bar'>";

        if ($ship->is_lock($cabinmsg['shipid'])) {
            $string .= <<<html
                        <div class="layui-upload" style="margin-left: 20px;text-align: left";>
              <button type="button" class="layui-btn" id="select_img">选择文件</button> 
              <blockquote class="layui-elem-quote layui-quote-nm" style="margin-top: 10px; text-align: left;">
                预览：
                <div class="layui-upload-list" id="view_img"></div>
             </blockquote>
            </div>
            <button style="visibility:hidden;" id="upload_img"></button>
            <script>
            layui.use('upload', function(){
                          var $ = layui.jquery
                          ,upload = layui.upload;
                          //多图片上传
                          img_btn_obj = upload.render({
                            elem: '#select_img'
                            ,auto: false
                            ,bindAction: '#upload_img'
                            ,multiple: true
                            ,exts:"jpg|png|gif|bmp|jpeg"
                            ,acceptMime:"images"
                            ,accept:"images"
                            ,choose: function(obj){
                              //记录选择了多少张图片
                              img_count = obj.upload.length;
                              //清空预览区
                              $('#view_img').html('');
                              //预读本地文件示例，不支持ie8
                              obj.preview(function(index, file, result){
                                $('#view_img').append('<img src="'+ result +'" alt="'+ file.name +'" class="layui-upload-img">')
                              });
                            }
                            ,allDone: function(obj){
                              //上传完毕
                              if(obj.aborted == 0){
                                close_loading();
                                layer.msg("提交到后台成功,请耐心等待后台复核", {icon: 1});
                                setTimeout(function () {
                                    location.reload();
                                }, 200000);
                              }
                            }
                          });
                        });
            </script>
            <input type='submit' value='取&nbsp;消' class='mmqx passbtn'>
            <input type='submit' onclick='addc()' value='提&nbsp;交' class='mmqd passbtn'>
            </div>
html;
        } else {
            $string .= "<input type='submit' value='取&nbsp;消' class='mmqx passbtn'><input type='submit' onclick='addc()' value='提&nbsp;交' class='mmqd passbtn'></div>";
        }


        echo ajaxReturn(array("state" => 1, 'message' => "成功", 'content' => $string));

    }

    /**
     * 舱修改
     */
    public function edit()
    {
        if (I('post.shipid') and I('post.id') and I('post.cabinname') and I('post.pipe_line') and I('post.altitudeheight') and I('post.bottom_volume')) {
            // 判断是否超过船舶限制舱总数
//			$ship = new \Common\Model\ShipModel();
//			$cabinnum = $ship->getFieldById(I('post.shipid'),'cabinnum');
//			$count = $this->db->where(array('shipid'=>I('post.shipid')))->count();
//			if ($count+1 > $cabinnum  ) {
//				echo ajaxReturn(array("state" => 2, 'message' => '超过该船限制总舱数'));
//			} else {
            //判断同一条船不能有重复的舱名

            $shipid = trimall(I('post.shipid'));
            $cabin_id = trimall(I('post.id'));
            $cabinname = trimall(I('post.cabinname'));
            $pipe_line = trimall(I('post.pipe_line'));

            $altitudeheight = trimall(I('post.altitudeheight'));
            $dialtitudeheight = trimall(I('post.dialtitudeheight'));

            $bottom_volume = trimall(I('post.bottom_volume'));
            $bottom_volume_di = trimall(I('post.bottom_volume_di'));


            $where = array(
                'shipid' => $shipid,
                'cabinname' => I('post.cabinname'),
                'id' => array('NEQ', I('post.id'))
            );
            $count = $this->db->where($where)->count();
            if ($count > 0) {
                echo ajaxReturn(array("state" => 2, 'message' => '该船已存在该舱名'));
            } else {
                $data = I('post.');
                // 对数据进行验证
                if (!$this->db->create($data)) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    echo ajaxReturn(array("state" => 2, 'message' => $this->db->getError()));
                } else {
                    $ship = new \Common\Model\ShipFormModel();
//                    $ship_id = $this->db->getFieldById('');
                    $where1 = array(
                        'id' => $shipid,
                    );
                    //获得算法
                    $shipmsg = $ship
                        ->field('suanfa,review')
                        ->where($where1)
                        ->find();
                    if ($ship->is_lock(I('post.shipid'))) {

                        M()->startTrans();
                        /**
                         * 由于舱审核信息挂载载船的审核信息上，所以要考虑几种情况
                         *
                         * 1、没有船审核记录，但是提交了舱审核记录 ： 建立一个新的船审核记录，除了必要信息，其他内容全部留空
                         * 2、有船审核记录，提交了舱审核记录 ： 获得主键ID，用于外键连接
                         */
                        $review_data = array(
                            'shipid' => $shipid,
                            'userid' => trimall($_SESSION['user_info']['id']),
                            'create_time' => time(),
                        );

                        //用于新建船审核记录的数据
                        $ship_review_data = $review_data;

                        $ship_review_map = array(
                            'shipid' => $shipid,
                            'status' => 1
                        );

                        /**
                         * 判断是否有船审核记录
                         */
                        $ship_review = M('ship_review');
                        $ship_review_count = $ship_review->field('id,data_status,cabin_picture,picture')->where($ship_review_map)->find();

                        /**
                         * 建立一个新的船审核记录或获得主键ID
                         */

                        if (count($ship_review_count) >= 1) {
                            if ($ship_review_count['data_status'] == 1 and $ship_review_count['picture'] == 1) {
                                //如果状态为已上传船信息没有上传舱新信息，且没有上传船修改照片，则改为只上传了舱信息
                                $ship_review_data['data_status'] = 3;
                                $result = $ship_review->where($ship_review_map)->save($ship_review_data);
                                if ($result !== false) {
                                    $result = $ship_review_count['id'];
                                }
                            } else {
                                $result = $ship_review_count['id'];
                            }
                        } else {
                            //新建,3为上传了舱审核没有船审核
                            $ship_review_data['data_status'] = 3;
                            $result = $ship_review->add($ship_review_data);
                        }

                        if ($result !== false) {

                            /**
                             * 复核授权机制判断
                             */
                            if (strtolower($shipmsg['suanfa']) == "c" || strtolower($shipmsg['suanfa']) == "d") {
                                $field = 'cabinname,altitudeheight,dialtitudeheight,bottom_volume,bottom_volume_di,pipe_line';
                                $value = array(
                                    'cabinname' => $cabinname,
                                    'altitudeheight' => $altitudeheight,
                                    'dialtitudeheight' => $dialtitudeheight,
                                    'bottom_volume' => $bottom_volume,
                                    'bottom_volume_di' => $bottom_volume_di,
                                    'pipe_line' => $pipe_line,
                                );
                            } else {
                                $field = 'cabinname,altitudeheight,bottom_volume,pipe_line';
                                $value = array(
                                    'cabinname' => $cabinname,
                                    'altitudeheight' => $altitudeheight,
                                    'bottom_volume' => $bottom_volume,
                                    'pipe_line' => $pipe_line,
                                );
                            }

                            $old_info = $this->db->field($field)->where(array('id' => $cabin_id, 'shipid' => $shipid))->find();
                            if (isset($old_info['cabinname'])) {
                                /**
                                 * 占位数组，防止重复提交时有些值没有被覆盖掉
                                 */
                                $tpl_data = array(
                                    'cabinname' => null,
                                    'altitudeheight' => null,
                                    'dialtitudeheight' => null,
                                    'bottom_volume' => null,
                                    'bottom_volume_di' => null,
                                    'pipe_line' => null,
                                );


                                //对比差异
                                $diff_info = array_diff_assoc($old_info, $value);

                                foreach ($diff_info as $key1 => $value1) {
                                    $diff_info[$key1] = $value[$key1];
                                }

                                if ($diff_info['cabinname'] !== false) {
                                    $cabin_review = M("cabin_review");
                                    //验证舱名是否和同一条船内已有的舱名重复
                                    $name_count = $this->db->where(array('id' => array('neq', $cabin_id), 'cabinname' => $diff_info['cabinname'], 'shipid' => $shipid))->count();
                                    //验证舱名是否和正在审核中相同船的其他舱名重复
                                    $review_name_count = $cabin_review
                                        ->alias('c')
                                        ->join('right join ship_review as s on c.review_id=s.id')
                                        ->where(array(
                                            's.status' => 1,
                                            's.shipid' => $shipid,
                                            'c.cabinname' => $diff_info['cabinname'],
                                            'c.cabinid' => array('neq', $cabin_id)
                                        ))->count();

                                    if ($name_count > 0 or $review_name_count > 0) {
                                        M()->rollback();
                                        //船舱已存在   2020
                                        exit(ajaxReturn(array("state" => 2020, 'message' => "船舱已存在")));

                                    }
                                }

                                //合并数组
                                $cabin_review_data = array_merge($tpl_data, $diff_info, $review_data);
                                //如果成功获取到了审核ID
                                $cabin_review_data['review_id'] = $result;
                                $cabin_review_data['cabinid'] = $cabin_id;
                                $review_map = array(
                                    'shipid' => $shipid,
                                    'review_id' => $result,
                                    'cabinid' => $cabin_id,
                                );

                                //获取舱审核的数量
                                $review_count = $cabin_review->where($review_map)->count();

                                if ($review_count >= 1) {
                                    //已存在则覆盖新的舱审核
                                    $cabin_result = $cabin_review->where($review_map)->save($cabin_review_data);
                                } else {
                                    //不存在则创建新的审核信息
                                    $cabin_result = $cabin_review->add($cabin_review_data);
                                }

                                if ($cabin_result === false) {
                                    //回滚
                                    M()->rollback();
                                    //修改失败,错误11
                                    exit(ajaxReturn(array("state" => 11, 'message' => "修改失败")));

                                }
                            } else {
                                //回滚
                                M()->rollback();
                                //未找到舱,错误2027
                                exit(ajaxReturn(array("state" => 2027, 'message' => "未找到舱")));

                            }

                            //提交并等待审核
                            M()->commit();
                            exit(ajaxReturn(array("state" => 200, 'review_id' => $result, 'message' => "提交成功，请等待复核")));

                        } else {
                            M()->rollback();
                            //修改失败,错误11
                            exit(ajaxReturn(array("state" => 11, 'message' => "修改失败")));
                        }
                    } else {
                        // 验证通过 可以进行其他数据操作
                        $map = array(
                            'id' => $data['id']
                        );
                        unset($data['id']);
                        $res = $this->db->editData($map, $data);
                        if ($res !== false) {
                            echo ajaxReturn(array("state" => 1, 'message' => "修改成功"));
                        } else {
                            echo ajaxReturn(array("state" => 2, 'message' => "修改失败"));
                        }
                    }
                }
            }
//			}
        } else {
            echo ajaxReturn(array("state" => 2, 'message' => "表单不能存在空值"));
        }
    }

    /**
     * 舱新增
     */
    public function add()
    {
        if (I('post.shipid') and I('post.cabinname') and I('post.pipe_line') and I('post.altitudeheight') and I('post.bottom_volume')) {
            $ship = new \Common\Model\ShipModel();
            $cabinnum = $ship->getFieldById(I('post.shipid'), 'cabinnum');
            $count1 = $this->db->where(array('shipid' => I('post.shipid')))->count();
            if ($count1 + 1 > $cabinnum) {
                echo ajaxReturn(array("state" => 2, 'message' => '超过该船限制总舱数'));
            } else {
                $ship_id = trimall(I('post.shipid'));
                //判断同一条船不能有重复的舱名
                $where = array(
                    'shipid' => $ship_id,
                    'cabinname' => I('post.cabinname')
                );

                $cabin = new \Common\Model\CabinModel();
                $count = $cabin->where($where)->count();
                if ($count > 0) {
                    echo ajaxReturn(array("state" => 2, 'message' => "该船已存在该舱名"));
                } else {
                    // 去除键值首位空格
                    $data = I('post.');
                    // 对数据进行验证
                    if (!$this->db->create($data)) {
                        // 如果创建失败 表示验证没有通过 输出错误提示信息
                        // $this->error($cabin->getError());
                        echo ajaxReturn(array("state" => 2, 'message' => $this->db->getError()));
                    } else {
                        // 验证通过 可以进行其他数据操作
                        $res = $cabin->addData($data);
                        if ($res !== false) {
                            //如果舱全部添加完则修改审核状态
                            if ($count1 + 1 == $cabinnum) {
                                //已审核过的不再审核
                                $ship_where = array(
                                    'id' => $ship_id,
                                    'review' => 1
                                );
                                $ship_data = array(
                                    'review' => 2
                                );

                                $res = $ship->editData($ship_where, $ship_data);
                                if ($res !== false) {
                                    echo ajaxReturn(array("state" => 1, 'message' => "新增成功"));
                                } else {
                                    echo ajaxReturn(array("state" => 1, 'message' => "新增成功，但复核状态异常"));
                                }
                            } else {
                                echo ajaxReturn(array("state" => 1, 'message' => "新增成功"));
                            }
                        } else {
                            echo ajaxReturn(array("state" => 2, 'message' => "新增失败"));
                        }
                    }
                }
            }
        } else {
            echo ajaxReturn(array("state" => 2, 'message' => "表单不能存在空值"));
        }
    }

    /**
     * 判断船舶是否有底量测量
     */
    public function ajax_diliang()
    {
        if (IS_AJAX) {
            $ship = new \Common\Model\ShipModel();
            $is_diliang = $ship->getFieldById($_POST ['shipid'], 'is_diliang');
            echo json_encode($is_diliang);
        } else {
            echo false;
        }
    }
}