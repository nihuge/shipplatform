<?php

namespace Admin\Controller;

use Common\Controller\AdminBaseController;
use Think\Think;

/**
 * 数据导入
 */
class UploadController extends AdminBaseController
{
    /**
     * 数据导入
     */
    public function index()
    {
        if (IS_POST) {
            setcookie('upload_shipid', I('post.shipid'), time() + 3600 * 24 * 30 * 6);
            setcookie('upload_tname', I('post.tname'), time() + 3600 * 24 * 30 * 6);
            setcookie('upload_cabinid', I('post.cabinid'), time() + 3600 * 24 * 30 * 6);
            if (I('post.tname') != null) {
                if ($_FILES['file']['tmp_name']) {
                    //判断文件格式
                    $type = getFileExt($_FILES ['file'] ['name']);
                    if ($type != '.csv') {
                        $this->error('文件格式不正确，必须为CSV文件！');
                    }
                    header("Content-type:text/html;charset=gbk");
                    //读取CSV文件
                    $file = fopen($_FILES ['file'] ['tmp_name'], 'r');
                    while ($data = fgetcsv($file)) { //每次读取CSV里面的一行内容
                        $array[] = $data;
                    }
                    $array = array_values($array);
                    $array = eval('return ' . iconv('gbk', 'utf-8', var_export($array, true)) . ';');
                    static $total = 0;
                    $count1 = count($array);
                    $rr = I('post.r');
                    $model = M();
                    $model->startTrans();   //开启事物
                    /**
                     *    数据导入的类型
                     *    拆分a\b\c
                     *    a:容量（容量、底量）类型
                     *    b:容量类型
                     *    c:纵倾修正（容量、底量）类型
                     * */
                    $t_name = I('post.tname');
                    $qufen = substr($t_name, -1);    //获取数据导入的类型
                    $tname = substr($t_name, 0, -1);    //获取表名

                    $t = M("$tname");
                    foreach ($array as $tmp) {
                        switch ($qufen) {
                            case 'a':
                                $data1 = array(
                                    'sounding' => $tmp[0],
                                    'ullage' => $tmp[1],
                                    'capacity' => $tmp[2],
                                    'diff' => $tmp[3],
                                    'cabinid' => I('post.cabinid')
                                );
                                break;
                            case 'b':
                                $data1 = array(
                                    'sounding' => $tmp[0],
                                    'ullage' => $tmp[1],
                                    'tripbystern1' => $tmp[2],
                                    'tripbystern2' => $tmp[3],
                                    'tripbystern3' => $tmp[4],
                                    'tripbystern4' => $tmp[5],
                                    'tripbystern5' => $tmp[6],
                                    'tripbystern6' => $tmp[7],
                                    'tripbystern7' => $tmp[8],
                                    'cabinid' => I('post.cabinid')
                                );
                                break;
                            case 'c':
                                $data1 = array(
                                    'sounding' => $tmp[0],
                                    'ullage' => $tmp[1],
                                    'trimvalue1' => $tmp[2],
                                    'trimvalue2' => $tmp[3],
                                    'trimvalue3' => $tmp[4],
                                    'trimvalue4' => $tmp[5],
                                    'trimvalue5' => $tmp[6],
                                    'trimvalue6' => $tmp[7],
                                    'trimvalue7' => $tmp[8],
                                    'trimvalue8' => $tmp[9],
                                    'trimvalue9' => $tmp[10],
                                    'trimvalue10' => $tmp[11],
                                    'trimvalue11' => $tmp[12],
                                    'cabinid' => I('post.cabinid')
                                );
                                break;
                            default:
                                $data1 = array();
                                break;
                        }
                        if (empty($data1)) {
                            $this->error('选择的数据类型有误！');
                        }
                        foreach ($data1 as $k => $v) {
                            $datas[$k] = trim($v);
                        }
                        // writeLog($datas['sounding']);
                        $where = array(
                            'sounding' => $datas['sounding'],
                            'cabinid' => $datas['cabinid']
                        );
                        $count = $t->where($where)->count();
                        if ($count > 0 && $rr == 'y') {
                            //覆盖（修改）
                            $total++;
                            $t->where($where)->save($datas);
                        } elseif ($count > 0 && $rr == 'n') {
                            writeLog($count);
                            writeLog($t->getLastSql());
                            //不覆盖(跳过)
                            $model->rollback();
                            $this->error('表中已存在数据');
                            exit;
                        } elseif ($count == '0') {
                            $total++;
                            $t->add($datas);
                            writeLog($count);
                            writeLog($t->getLastSql());
                        }
                        $datas = array();
                    }
                    if ($count1 == $total) {
                        $model->commit();
                        $this->success('导入成功');
                        //每次导入成功以后更新一次船的有表无表状态
                        $ship = new \Common\Model\ShipFormModel();
                        $ship->updata_one_ship(intval(I('post.shipid')));
                    } else {
                        $model->rollback();
                        $this->error('拥有重复数据');
                    }

                } else {
                    $this->error("上传文件不存在！");
                }
            } else {
                $this->error("导入有误！(表名不为空)");
            }
        } else {
            //获取船列表
            $ship = new \Common\Model\ShipModel();
            $shiplist = $ship
                ->field('id,shipname')
                ->order('shipname asc')
                ->select();
            $this->assign('shiplist', $shiplist);
            $this->display();
        }
    }

    /**
     * ajax获取舱(下拉框)
     * @param int $shipid 船ID
     * @return string
     * */
    public function cabin_op()
    {
        if (IS_AJAX) {
            $cabin = new \Common\Model\CabinModel();
            $arr = $cabin
                ->field('id,cabinname')
                ->where(array('shipid' => $_POST ['shipid']))
                ->order('id asc')
                ->select();
            static $mod = "<option value=''>--选择舱--</option>";
            $cabinlist = array();
            foreach ($arr as $key => $vo) {
                $mod .= "<option  value='" . $vo ['id'] . "'>" . $vo['cabinname'] . "</option>";
                $cabinlist[] = $vo ['id'];
            }
            //根据船ID获取
            $ship = new \Common\Model\ShipModel();
            $msg = $ship
                ->field('rongliang,rongliang_1,tankcapacityshipid,zx,zx_1')
                ->where(array('id' => $_POST ['shipid']))
                ->find();
            $tname = '';
            $presence = '<table><tr><td colspan="2">已存在导入数据</td></tr>';
            //判断表是否存在并组装单选html
            /**
             *    连接a\b\c\d
             *    a:容量（容量）类型
             *    b:容量类型
             *    c:纵倾修正（容量、底量）类型
             *    c:容量（容量、底量）类型
             * */
            //容量
            if (!empty($msg['tankcapacityshipid'])) {
                $tname .= "&nbsp;<input type='radio' name='tname' value='" . $msg['tankcapacityshipid'] . "b' id='tc' checked><label for='tc'>容量</label><hr/>";
                $table = $msg['tankcapacityshipid'];
                $presence .= '<tr><td>容量</td>' . $this->is_like($table, $cabinlist);
            }
            //容量--容量
            if (!empty($msg['rongliang'])) {
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='" . $msg['rongliang'] . "a' id='rl'><label for='rl'>容量表</label>";
                $table = $msg['rongliang'];
                $presence .= '<tr><td>容量书容量表</td>' . $this->is_like($table, $cabinlist);
            }


            //容量--纵倾修正
            if (!empty($msg['zx'])) {
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='" . $msg['zx'] . "c' id='zx'><label for='zx'>纵倾修正表</label><hr/>";
                $table = $msg['zx'];
                $presence .= '<tr><td>容量书纵倾修正表</td>' . $this->is_like($table, $cabinlist);
            }

            //底量--容量
            if (!empty($msg['rongliang_1'])) {
                $tname .= "B书(底量书)：&nbsp;<input type='radio' name='tname' value='" . $msg['rongliang_1'] . "a' id='rl_1'><label for='rl_1'>容量表</label>";
                $table = $msg['rongliang_1'];
                $presence .= '<tr><td>底量书容量表</td>' . $this->is_like($table, $cabinlist);
            }

            //底量--纵倾修正
            if (!empty($msg['zx_1'])) {
                $tname .= "B书(底量书)：&nbsp;<input type='radio' name='tname' value='" . $msg['zx_1'] . "c' id='zx_1'><label for='zx_1'>纵倾修正表</label>";
                $table = $msg['zx_1'];
                $presence .= '<tr><td>底量书纵倾修正表</td>' . $this->is_like($table, $cabinlist);
            }

            $data = array(
                'sc' => $mod,
                'presence' => $presence,
                // 'rongliang_1'  => $msg['rongliang_1']
                'tname' => $tname
            );
            echo json_encode($data);
        } else {
            echo false;
        }
    }

    public function is_like($table, $cabinlist)
    {
        $table = M("$table");
        $a = $table->group('cabinid')->getField('cabinid', true);
        $like = array_intersect($cabinlist, $a);
        $presence = '<td>';
        $cabin = new \Common\Model\CabinModel();
        foreach ($like as $key => $value) {
            $cabinname = $cabin->getFieldById($value, 'cabinname');
            $presence .= $cabinname . ' , ';
        }
        $presence .= '</td></tr>';

        return $presence;
    }



    /*************************无表船部分*************************/


    /**
     *无表船上传界面
     */
    public function formless_upload()
    {
        if (IS_POST) {
            $cabinid = intval(I('post.cabinid'));
            $type = I('post.tname');
            $table_data = I('post.data');
            $work = new \Common\Model\WorkModel();
            $types = array(
                'rl' => array('table' => 'cumulative_capacity_data', 'book' => 1),
                'zx' => array('table' => 'cumulative_trim_data', 'book' => 1),
                'rl_1' => array('table' => 'cumulative_capacity_data', 'book' => 2),
                'zx_1' => array('table' => 'cumulative_trim_data', 'book' => 2),
            );
            $res = array();
            M()->startTrans();
            if ($type == "rl" or $type == "rl_1") {
                $row = count($table_data);
                for ($i = 0; $i < $row-1; $i++) {
                    $ins_data = array('book' => $types[$type]['book'], 'data_sources' => 2, 'cabinid' => $cabinid);
                    $ullage1 = "";
                    $capacity1 = "";
                    $ullage = $table_data[$i]['ullage'];
                    $capacity = $table_data[$i]['capacity'];
                    if ($ullage === "") continue;//如果当前行为空，跳过
                    if ($i + 1 < $row) {
                        $ullage1 = $table_data[$i + 1]['ullage'];
                        $capacity1 = $table_data[$i + 1]['capacity'];
                    }
                    unset($table_data[$i]['ullage']);
                    if ($ullage1 !== "") {
                        if ($ullage1 > $ullage) {
                            $ins_data['xiuullage1'] = $ullage;
                            $ins_data['xiuullage2'] = $ullage1;
                            $ins_data['capacity1'] = $capacity;
                            $ins_data['capacity2'] = $capacity1;
                        } else {
                            $ins_data['xiuullage1'] = $ullage1;
                            $ins_data['xiuullage2'] = $ullage;
                            $ins_data['capacity1'] = $capacity1;
                            $ins_data['capacity2'] = $capacity;
                        }
                    } else {
                        $ins_data['xiuullage1'] = $ullage;
                        $ins_data['xiuullage2'] = $ullage;
                        $ins_data['capacity1'] = $capacity;
                        $ins_data['capacity2'] = $capacity;
                    }
                    $res = $work->$types[$type]['table']($ins_data);
                    if ($res == 3) {
                        M()->rollback();
                        $this->error('数据插入失败，请检查数据是否正确');
                    }
//                    $res[] = $ins_data;
                }
            } else {
                $row = count($table_data);
                for ($i = 0; $i < $row-1; $i++) {
                    $ins_data = array('book' => $types[$type]['book'], 'data_sources' => 2, 'cabinid' => $cabinid);
                    $ullage1 = "";
//                $ullage = "";
                    $ullage = $table_data[$i]['ullage'];
                    if ($ullage === "") continue;//如果当前行为空，跳过
                    if ($i + 1 < $row) {
                        $ullage1 = $table_data[$i + 1]['ullage'];
                    }
                    unset($table_data[$i]['ullage']);
                    $column = count($table_data[$i]);
                    if ($ullage1 !== "") {
                        if ($ullage1 > $ullage) {
                            $ins_data['ullage1'] = $ullage;
                            $ins_data['ullage2'] = $ullage1;
                        } else {
                            $ins_data['ullage1'] = $ullage1;
                            $ins_data['ullage2'] = $ullage;
                        }
                        for ($ii = 0; $ii < $column; $ii++) {
                            $draft_arrs1 = array();
                            $draft_arrs3 = array();

                            $draft_arrs = array_slice($table_data[$i], $ii, 1, true);//保留键名
                            $draft_arrs2 = array_slice($table_data[$i + 1], $ii, 1, true);//保留键名

                            if ($ii + 1 < $column) {
                                $draft_arrs1 = array_slice($table_data[$i], $ii + 1, 1, true);//保留键名
                                $draft_arrs3 = array_slice($table_data[$i + 1], $ii + 1, 1, true);//保留键名
                            }

                            $draft_arr = each($draft_arrs);
                            $draft_arr2 = each($draft_arrs2);
                            if (count($draft_arrs1) > 0 and count($draft_arrs3) > 0) {
                                $draft_arr1 = each($draft_arrs1);
                                $draft_arr3 = each($draft_arrs3);
                                if ($draft_arr1[0] > $draft_arr[0]) {
                                    $ins_data['draft1'] = $draft_arr[0];
                                    $ins_data['draft2'] = $draft_arr1[0];
                                    $ins_data['value1'] = $draft_arr[1];
                                    $ins_data['value2'] = $draft_arr1[1];
                                    $ins_data['value3'] = $draft_arr2[1];
                                    $ins_data['value4'] = $draft_arr3[1];
                                } else {
                                    $ins_data['draft1'] = $draft_arr1[0];
                                    $ins_data['draft2'] = $draft_arr[0];
                                    $ins_data['value1'] = $draft_arr1[1];
                                    $ins_data['value2'] = $draft_arr[1];
                                    $ins_data['value3'] = $draft_arr3[1];
                                    $ins_data['value4'] = $draft_arr2[1];
                                }
                            } else {
                                $ins_data['draft1'] = $draft_arr[0];
                                $ins_data['draft2'] = $draft_arr[0];
                                $ins_data['value1'] = $draft_arr[1];
                                $ins_data['value2'] = $draft_arr[1];
                                $ins_data['value3'] = $draft_arr2[1];
                                $ins_data['value4'] = $draft_arr2[1];
                            }
                            //存入数据库
                            $res = $work->$types[$type]['table']($ins_data);
                            if ($res == 3) {
                                M()->rollback();
                                $this->error('数据插入失败，请检查数据是否正确');
                            }
                        }
                    } else {
                        $ins_data['ullage1'] = $ullage;
                        $ins_data['ullage2'] = $ullage;

                        for ($ii = 0; $ii < $column; $ii++) {
                            $draft_arrs1 = array();
                            $draft_arrs = array_slice($table_data[$i], $ii, 1, true);//保留键名
                            if ($ii + 1 < $column) {
                                $draft_arrs1 = array_slice($table_data[$i], $ii + 1, 1, true);//保留键名
                            }
                            $draft_arr = each($draft_arrs);
                            if (count($draft_arrs1) > 0) {
                                $draft_arr1 = each($draft_arrs1);
                                if ($draft_arr1[0] > $draft_arr[0]) {
                                    $ins_data['draft1'] = $draft_arr[0];
                                    $ins_data['draft2'] = $draft_arr1[0];
                                    $ins_data['value1'] = $draft_arr[1];
                                    $ins_data['value2'] = $draft_arr1[1];
                                    $ins_data['value3'] = $draft_arr[1];
                                    $ins_data['value4'] = $draft_arr1[1];
                                } else {
                                    $ins_data['draft1'] = $draft_arr1[0];
                                    $ins_data['draft2'] = $draft_arr[0];
                                    $ins_data['value1'] = $draft_arr1[1];
                                    $ins_data['value2'] = $draft_arr[1];
                                    $ins_data['value3'] = $draft_arr1[1];
                                    $ins_data['value4'] = $draft_arr[1];
                                }
                            } else {
                                $ins_data['draft1'] = $draft_arr[0];
                                $ins_data['draft2'] = $draft_arr[0];
                                $ins_data['value1'] = $draft_arr[1];
                                $ins_data['value2'] = $draft_arr[1];
                                $ins_data['value3'] = $draft_arr[1];
                                $ins_data['value4'] = $draft_arr[1];
                            }
//                            $res[] = $ins_data;
                            //存入数据库
                            $res = $work->$types[$type]['table']($ins_data);
                            if ($res === false) {
                                M()->rollback();
                                $this->error('数据插入失败，请检查数据是否正确');
                            }
                        }
                    }
//                    $res[] = $ins_data;
                }
            }
            M()->commit();//提交
            $this->success("导入成功");
//            exit(json_encode($res));
        } else {
            //获取船列表
            $ship = new \Common\Model\ShipModel();
            $shiplist = $ship
                ->field('id,shipname')
                ->order('shipname asc')
                ->select();
            $this->assign('shiplist', $shiplist);
            $this->display();
        }
    }

    /**
     * 无表船 ajax获取舱(下拉框)
     * @param int $shipid 船ID
     * @return string
     * */
    public function formless_cabin_op()
    {
        if (IS_AJAX) {
            //根据船ID获取
            $ship = new \Common\Model\ShipModel();
            $msg = $ship
                ->field('rongliang,rongliang_1,tankcapacityshipid,zx,zx_1,suanfa,tripbystern,trimcorrection1,trimcorrection')
                ->where(array('id' => I('shipid')))
                ->find();
            $kedu_bool = true;
            if ($msg['suanfa'] == 'a' and json_decode($msg['tripbystern']) == null) {
                $kedu_bool = false;
            }

            if ($msg['suanfa'] == 'b' and json_decode($msg['trimcorrection']) == null) {
                $kedu_bool = false;
            }

            if ($msg['suanfa'] == 'c' and (json_decode($msg['trimcorrection']) == null or json_decode($msg['trimcorrection1']) == null)) {
                $kedu_bool = false;
            }

            if ($msg['suanfa'] == 'd' and (json_decode($msg['trimcorrection']) == null or json_decode($msg['trimcorrection1']) == null)) {
                $kedu_bool = false;
            }

            if ($kedu_bool === false) exit(json_encode(array('code' => 4, 'msg' => '请完善纵倾值刻度后再手动添加记录', 'url' => U('Ship/edit', array('id' => I('shipid'))))));

            $cabin = new \Common\Model\CabinModel();
            $arr = $cabin
                ->field('id,cabinname')
                ->where(array('shipid' => $_POST ['shipid']))
                ->order('id asc')
                ->select();
            static $mod = "<option value=''>--选择舱--</option>";
            $cabinlist = array();
            foreach ($arr as $key => $vo) {
                $mod .= "<option  value='" . $vo ['id'] . "'>" . $vo['cabinname'] . "</option>";
                $cabinlist[] = $vo ['id'];
            }

            $tname = '';
            $presence = '<table><tr><td colspan="2">已存在导入数据</td></tr>';
            //判断表是否存在并组装单选html
            /**
             *    连接a\b\c\d
             *    a:容量（容量）类型
             *    b:纵倾修正(容量)类型
             *    c:纵倾修正（容量、底量）类型
             *    d:容量（容量、底量）类型
             * */
            //容量
            if ($msg['suanfa'] == 'a') {
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='zx' id='zx'><label for='zx'>纵倾修正表</label>";
            } elseif ($msg['suanfa'] == 'b') {
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='rl' id='rl'><label for='rl'>容量表</label>";
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='zx' id='zx'><label for='zx'>纵倾修正表</label>";
            } elseif ($msg['suanfa'] == 'c') {
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='rl' id='rl'><label for='rl'>容量表</label>";
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='zx' id='zx'><label for='zx'>纵倾修正表</label><hr/>";
                $tname .= "B书(底量书)：&nbsp;<input type='radio' name='tname' value='rl_1' id='rl_1'><label for='rl_1'>容量表</label>";
                $tname .= "B书(底量书)：&nbsp;<input type='radio' name='tname' value='zx_1' id='zx_1'><label for='zx_1'>纵倾修正表</label>";
            } elseif ($msg['suanfa'] == 'd') {
                $tname .= "V书(容量书)：&nbsp;<input type='radio' name='tname' value='zx' id='zx'><label for='zx'>纵倾修正表</label><hr/>";
                $tname .= "B书(底量书)：&nbsp;<input type='radio' name='tname' value='zx_1' id='zx_1'><label for='zx_1'>纵倾修正表</label>";
            }

            $data = array(
                'code' => 0,
                'sc' => $mod,
//                'presence' => $presence,
                // 'rongliang_1'  => $msg['rongliang_1']
                'tname' => $tname,
            );
            echo json_encode($data);
        } else {
            echo false;
        }
    }

    public function get_cum_table()
    {
        if (IS_AJAX) {
            $shipid = intval(I('post.shipid'));
//            $cabinid = intval(I('post.cabinid'));
            $type = I('post.tname');
            $ship = new \Common\Model\ShipFormModel();
            /*            $types = array(
                            'rl'=>array('table'=>'cumulative_capacity_data','book'=>1),
                            'zx'=>array('table'=>'cumulative_trim_data','book'=>1),
                            'rl_1'=>array('table'=>'cumulative_capacity_data','book'=>2),
                            'zx_1'=>array('table'=>'cumulative_trim_data','book'=>2),
                        );*/
//            $types = array(
//                'rl'=>array('field'=>'cumulative_capacity_data'),
//                'zx'=>array('field'=>'cumulative_trim_data'),
//                'rl_1'=>array('field'=>'cumulative_capacity_data'),
//                'zx_1'=>array('field'=>'cumulative_trim_data'),
//            );
            $cum_data = $ship->field('tripbystern,trimcorrection,trimcorrection1,suanfa')->where(array('id' => $shipid))->find();
            $tvalue = "";
            if ($type == "rl" or $type == "rl_1") {
                $tvalue = "<thead><th>空高</th><th>容量</th></thead>";
                for ($i = 0; $i < 50; $i++) {
                    $tvalue .= "<tr><td><input style='border:0;background-color:transparent;' name='data[" . $i . "][ullage]'></td><td><input style='border:0;background-color:transparent;' name='data[" . $i . "][capacity]'></td></tr>";
                }
            } else {
                $kedu = array();
                if ($cum_data['suanfa'] == 'a' and $type = 'rl') $kedu = json_decode($cum_data['tripbystern']);
                if ($cum_data['suanfa'] == 'b' and $type = 'rl') $kedu = json_decode($cum_data['trimcorrection']);
                if ($cum_data['suanfa'] == 'c') {
                    if ($type == 'zx') {
                        $kedu = json_decode($cum_data['trimcorrection']);
                    } elseif ($type == 'zx_1') {
                        $kedu = json_decode($cum_data['trimcorrection1']);
                    }
                }
                if ($cum_data['suanfa'] == 'd') {
                    if ($type == 'zx') {
                        $kedu = json_decode($cum_data['trimcorrection']);
                    } elseif ($type == 'zx_1') {
                        $kedu = json_decode($cum_data['trimcorrection1']);
                    }
                }
                $tvalue = "<thead><th>空高</th>";
                foreach ($kedu as $key => $value) {
                    $tvalue .= "<th>纵倾值" . $value . "/m</th>";
                }
                $tvalue .= "</thead>";
                $column = count($kedu);
                for ($i = 0; $i < 50; $i++) {
                    $tvalue .= "<tr><td><input style='border:0;background-color:transparent;' name='data[" . $i . "][ullage]'></td>";
                    foreach ($kedu as $v) {
                        $tvalue .= "<td><input style='border:0;background-color:transparent;' name='data[" . $i . "][" . $v . "]'></td>";
                    }
                    $tvalue .= "</tr>";
                }
            }

            $res = array(
                'code' => 0,
                'tvalue' => $tvalue
            );
            echo json_encode($res);

        } else {
            echo false;
        }
    }

    /**
     * 通过TXT上传舱容表
     * @param $shipid
     * @param $qufen
     */
    public function up_txt()
    {
//        $shipid,$qufen,$trim_kedu
        if(IS_POST){
            if ($_FILES['file']['tmp_name'] and I('post.shipid')) {
//            $file_path = "./Upload/txt/rongliang.txt";
                $file_path = $_FILES['file']['tmp_name'];
                $shipid = intval(I('post.shipid'));
                $qufen = I('post.qufen');

                $cabin = new \Common\Model\CabinModel();
                $ship = new \Common\Model\ShipFormModel();
                $cabins_info = $cabin->field('id,cabinname')->where(array('shipid' => $shipid))->select();
//                $ship_info = $ship->field('suanfa,is_diliang,tripbystern,trimcorrection,trimcorrection1,tankcapacityshipid,rongliang,zx,rongliang_1,zx_1')->where(array('id' => $shipid))->find();

                $list_data = $this->read_list_data($file_path,$cabins_info);
                $ship_info = $this->auto_create_list_table($shipid,$list_data['list_kedu'],$qufen);


                switch ($ship_info['suanfa']) {
                    case 'a':
                        $trim_kedu = $ship_info['tripbystern'];
                        $table_name_1 = $ship_info['tankcapacityshipid'];
                        $table_name_2 = "";
                        break;
                    case 'b':
                        $trim_kedu = $ship_info['trimcorrection'];
                        $table_name_1 = $ship_info['zx'];
                        $table_name_2 = $ship_info['rongliang'];
                        $table_name_3 = $ship_info['zx'];
                        break;
                    case 'c':
                        if ($qufen == 'diliang') {
                            $trim_kedu = $ship_info['trimcorrection1'];
                            $table_name_1 = $ship_info['zx_1'];
                            $table_name_2 = $ship_info['rongliang_1'];
                        } else {
                            $trim_kedu = $ship_info['trimcorrection'];
                            $table_name_1 = $ship_info['zx'];
                            $table_name_2 = $ship_info['rongliang'];
                        }
                        break;
                    case 'd':
                        if ($qufen == 'diliang') {
                            $trim_kedu = $ship_info['trimcorrection1'];
                            $table_name_1 = $ship_info['zx_1'];
                            $table_name_2 = "";
                        } else {
                            $trim_kedu = $ship_info['trimcorrection'];
                            $table_name_1 = $ship_info['zx'];
                            $table_name_2 = "";
                        }
                        break;
                }
//        echo "kedu:".$trim_kedu . "   table1：" .$table1. " table2：".$table2." <br/>";
                //        array_merge_recursive($a, $b);
                M()->startTrans();
                if ($table_name_1 != "") {
                    $table1 = M($table_name_1);
                    $trim = $this->read_trim_data($trim_kedu, $file_path, $cabins_info);
//                        exit(json_encode($trim));
                    foreach ($trim as $value) {
                        if ($table1->addAll($value['tirm_data']) === false) {
                            M()->rollback();
                            $this->error("数据库1插入错误".json_encode($value['tirm_data']));
                        };
                    }
                }

                if ($table_name_2 != "") {
                    $table2 = M($table_name_2);
                    $ca = $this->read_ca_data($file_path, $cabins_info);
//                    exit(json_encode($ca));
                    foreach ($ca as $value1) {
//                        exit($table2->fetchSql(true)->add($value1['ca_data'][0]));
                        if ($table2->addAll($value1['ca_data']) === false) {
                            M()->rollback();
                            $this->error("数据库2插入错误".$table2->getDbError());
                        };
                    }
                }

                if ($table_name_3 != "") {
                    $table3 = M($table_name_3);
                    foreach ($list_data['data'] as $value1) {
                        //横倾修正表，录入时不报错,防止影响正常的纵倾修正表录入业务
                        @$table3->addAll($value1['list_data']);
                    }
                }
                M()->commit();
                $ship->updata_one_ship($shipid);
                $this->success('导入成功');
            }else{
                $this->error("请上传文件或者选择表单内的选项");
            }
        }else{
            //获取船列表
            $ship = new \Common\Model\ShipModel();
            $shiplist = $ship
                ->field('id,shipname,data_ship,suanfa')
                ->order('shipname asc')
                ->select();
            $this->assign('shiplist', $shiplist);
            $this->display();
        }
    }

    /**
     * 正则匹配文本内的纵倾修正表数据并返回
     * @param $trim_kedu
     * @param $file_path
     * @return array
     */
    function read_trim_data($trim_kedu, $file_path, $cabins_info)
    {
        //        https://regex101.com/r/ozVP4k/2 纵倾修正表正则视图

        $orgin_txt = file_get_contents($file_path);
        $orgin_txt = preg_replace("/[\r\n]{2}/", "\r\n", $orgin_txt);

//        dump($orgin_txt);
//        $re = '/[\-\ ]+\s?Page\s([\d]+)[\-\ ]+\s+有效期至([\S]+)\s*?纵 倾 修 正 表\s*?实 高\s*?Sounding\s*?\(m\)\s*?空 高 纵倾值\（艉吃水\－艏吃水\）\s*?Ullage\s*?\(m\)\s*?Trim\[draft aft\(stern\)\- draft forward\(bow\)\]\s*?\[([^\]]+)\] Trim Correction Table ([\S]+) ([A-Za-z0-9]+)\s*?[\(m\) ]+\s*?([\-\.m 0-9]+)\s*?[\* \r\n]+([0-9\.\- \r\n]+)有效期/m';
        $re = '/[\-\ ]+\s?Page\s([\d]+)[\-\ ]+\s+有效期至([\S]+)\s*?纵 倾 修 正 表\s*?实 高\s*?Sounding\s*?\(m\)\s*?空 高 纵倾值\（艉吃水\－艏吃水\）\s*?Ullage\s*?\(m\)\s*?Trim\[draft aft\(stern\)\- draft forward\(bow\)\]\s*?\[([^\]]+)\] Trim Correction Table ([\S]+) ([A-Za-z0-9]+)\s*?[\(m\) ]+\s*?([\-\.m 0-9]+)\s*?[\* \r\n]+[\s\S]*?([0-9\.\- \r\n]+)[\s\S]*?有效期/m';
        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
//        exit(jsonreturn($matches));
        $res = array();
        $trim_kedu = json_decode($trim_kedu, true);
        foreach ($matches as $key => $value) {
            $data = array();
            //处理页数编号等信息
//            echo "第".$value[1]."页 ， 有效期：".$value[2]."， 舱号：".$value[3].", 船名：".$value[4]."，书编号：".$value[5]."<br/>";
            $data['page'] = $value[1];
            $data['expire'] = $value[2];
            $data['cabin_name'] = preg_replace("/[左右]+污油舱 /", "", $value[3]);
            $data['ship_name'] = $value[4];
            $data['book_number'] = $value[5];
            $cabin_id = 0;
            foreach ($cabins_info as $v11) {
                if ( trimall($data['cabin_name']) == trimall($v11['cabinname'])){
                    $cabin_id = $v11['id'];
                }
            }
            if ($cabin_id == 0) continue;
            //开始分开吃水刻度
            $kedu = explode('m ', $value[6]);
            $kedu[count($kedu) - 1] = str_replace("m", "", $kedu[count($kedu) - 1]);
//            print_r($kedu);
            $data['kedu'] = $kedu;
//            echo "<table style='text-align: center' border='1px solid'>";
//            echo "<thead><th>实高</th><th>空高</th>";
//            foreach ($kedu as $k=>$v){
//                echo "<th>".$v."</th>";
//            }
//            echo "</thead>";
//            echo "<tbody>";
            $data_row = explode("\r\n", $value[7]);
//            array_pop($data_row);
            $data['tirm_data'] = array();
            foreach ($data_row as $k1 => $v1) {
                $qian = array(" ", "　","   ", "    ",'-','0','.',"\r","\n");
                $hou = array("", "", "", "", "","","","","");
                if(str_replace($qian, $hou, $v1)=="") continue;
                $data_cloumn = explode(" ", $v1);
                $data_cloumn[count($data_cloumn) - 1] = preg_replace("/[\r\n]+/", "", $data_cloumn[count($data_cloumn) - 1]);

                $td = array('sounding' => preg_replace("/\s+/", "", $data_cloumn[0]), 'ullage' => preg_replace("/\s+/", "", $data_cloumn[1]), 'cabinid' => $cabin_id);
                $i = 0;
//                print_r($trim_kedu);
                foreach ($trim_kedu as $k2 => $v2) {
                    $td["$k2"] =$data_cloumn[$i + 2];
                    $i++;
                }

                array_push($data['tirm_data'], $td);
//                foreach ($data_cloumn as $k2=>$v2){
//                    echo "<td>".$v2."</td>";
//                }
//                echo "</tr>";
            }
            array_push($res, $data);
//            echo "</tbody>";
//            echo "</table>";
//            $ullage =
//            echo "<br/>";
        }
        return $res;
//        exit(json_encode($res));
    }

    /**
     * 正则匹配文本内的容量表数据并返回
     * @param $file_path
     */
    function read_ca_data($file_path, $cabins_info)
    {
//        https://regex101.com/r/y0rt5d/4 容量表正则视图
        $orgin_txt = file_get_contents($file_path);
        $orgin_txt = preg_replace("/[\r\n]{2}/", "\r\n", $orgin_txt);
//        exit($orgin_txt);
        //        dump($orgin_txt);
//        $re = '/[\-]+\s+Page\s+([\d]+)[\-]+\s+有效期至([\S]+)\s*?容 量 表\s*?[\S\s]*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?[\S\s]*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+([\d\. \r\n]+)/m';
//        $re = '/[\-]{23}\s+Page\s+([\d]+)[\-]{23}\s+有效期至([\S]+)\s*?容 量 表\s*?[\S\s]*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?[\S\s]*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+((?:[\d\. \-]+[\r\n]+)+)/m';
//        $re = '/[\-]{23}\s+Page\s+([\d]+)[\-]{23}\s+有效期至([\S]+)\s*?容 量 表\s*?[\S\s]*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?[\S\s]*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+(?:\d+\.\d{3} \d+\.\d{3} [\D]+ [\D]+[\r\n]+)*?((?:\d+\.\d{3} \d+\.\d{3} \d+\.\d{3} [\d\.\-]+[\r\n]+)+)/m';
        $re = '/[\-]{23}\s+Page\s+([\d]+)[\-]{23}\s+有效期至([\S]+)\s*?容 量 表\s*?[\S\s]*?\[([\S \.]+)\] Tank Capacity Table ([\S]+)\s*?([a-zA-Z0-9]+)\s*?[\S\s]*?有效期至([\S]*?)！ Valid until ([\S ,\.]+)\s*?\- [\d]+ \-[\* \r\n]+(?:\d+\.\d{3} \d+\.\d{3} [\D]+ [\D]+[\r\n]+)*?((?:\d+\.\d{3} \d+\.\d{3} \d+\.\d{3} [\d\.\-]+|[\r\n]+)+)/m';
        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
//        exit(jsonreturn($matches));

        $res = array();
        foreach ($matches as $key => $value) {
            $data = array();
            //处理页数编号等信息
//            echo "第" . $value[1] . "页 ， 有效期：" . $value[2] . "， 舱号：" . $value[3] . ", 船名：" . $value[4] . "，书编号：" . $value[5] . "<br/>";
            $data['page'] = $value[1];
            $data['expire'] = $value[2];
            $data['cabin_name'] = $value[3];
            $data['ship_name'] = $value[4];
            $data['book_number'] = $value[5];
            $cabin_id = 0;
            foreach ($cabins_info as $v11) {
                if (trimall($data['cabin_name']) == trimall($v11['cabinname'])) {
                    $cabin_id = $v11['id'];
                }
            }
            if ($cabin_id == 0) continue;
//            echo "<table style='text-align: center' border='1px solid'>";
//            echo "<thead><th>实高</th><th>空高</th><th>容量</th><th>厘米容量</th>";
//            foreach ($kedu as $k=>$v){
//                echo "<th>".$v."</th>";
//            }
//            echo "</thead>";
//            echo "<tbody>";
            $data_row = explode("\r\n", $value[8]);
//            array_pop($data_row);
            $data['ca_data'] = array();
//            array_pop($data_row);
            foreach ($data_row as $k1 => $v1) {
                $qian = array(" ", "　","   ", "    ",'-','0','.',"\r","\n");
                $hou = array("", "", "", "", "","","","","");
                if(str_replace($qian, $hou, $v1)=="") continue;
                $data_cloumn = explode(" ", $v1);
                $data_cloumn[count($data_cloumn) - 1] = str_replace("\r", "", $data_cloumn[count($data_cloumn) - 1]);

                $td = array('sounding' => preg_replace("/\s+/", "", $data_cloumn[0]), 'ullage' =>  preg_replace("/\s+/", "", $data_cloumn[1]), 'capacity' =>  preg_replace("/\s+/", "", $data_cloumn[2]), 'diff' =>  preg_replace("/\s+/", "", preg_replace("/\-+/", "0.000",$data_cloumn[3])), 'cabinid' => $cabin_id);

//                print_r($trim_kedu);
//                foreach ($trim_kedu as $k2 => $v2) {
//                    $td["$k2"] = $data_cloumn[$i+2];
//                    $i++;
//                }
//                echo "<tr>";
                array_push($data['ca_data'], $td);
//                foreach ($data_cloumn as $k2 => $v2) {
//                    echo "<td>" . $v2 . "</td>";
//                }
//                echo "</tr>";
            }
            array_push($res, $data);
//            echo "</tbody>";
//            echo "</table>";
//            $ullage =
//            echo "<br/>";
        }
//        exit(json_encode($res));
        return $res;
    }

    /**
     * 匹配横倾修正表
     */
//    public function read_list_data(){
    function read_list_data($file_path,$cabins_info){
//        $orgin_txt = file_get_contents($file_path);
        $orgin_txt = file_get_contents("./Upload/txt/dayang28_rong.txt");
        $re = '/\-{23}\s*?Page\s*?(\d+)\-{23}\s*?有效期至[\d年月日]+\s*?横 倾 修 正 表[\s\S]*?\[([左右]+\.\d+\s?(?:[PS]+\.\d+))\]\s*?LIST CORRECTION TABLE\s*?[\S]+\s*?[A-Za-z0-9]+[\s\S]*?左 倾 List to Port 右 倾 List to Starb\'d \*\s*?[\*\s]+((?:[\d°\.]+\s*?\(mm\)\s+)+)[\S\s]*?([0-9\.\- \r\n]+)[\s\S]*?有效期/m';
        preg_match_all($re, $orgin_txt, $matches, PREG_SET_ORDER, 0);
//        exit(json_encode($matches));
        $res = array('data'=>array());
        $list_kedu_arr = explode("\r\n", preg_replace("/[\r\n]{2,6}/m", "\r\n", $matches[0][3]));
        $list_kedu = array();
        $kedu_num = 1;
        $pre_kedu = 180;//是否需要变成负数
        for ($i = 0; $i < count($list_kedu_arr); $i += 2) {
            if ($list_kedu_arr[$i] != "") {
                $listValue = preg_replace("/°/", "", $list_kedu_arr[$i]);
                if ($pre_kedu - $listValue > 0) $listValue *= -1;//如果上一个数减当前数是正数则变为负数
//                echo $pre_kedu."<br/>";
//                echo $listValue;
                $list_kedu['listvalue' . $kedu_num] = $listValue;
                $pre_kedu = abs($listValue);//赋值上一个数
                $kedu_num++;
            }
        }
        $res['list_kedu'] = $list_kedu;

//        exit(jsonreturn($list_kedu));
//        $res['list_kedu'] = $list_kedu;
        foreach ($matches as $key => $value) {
            $data = array();
            //处理页数编号等信息
//            echo "第".$value[1]."页 ， 有效期：".$value[2]."， 舱号：".$value[3].", 船名：".$value[4]."，书编号：".$value[5]."<br/>";
            $data['page'] = $value[1];
            $data['cabin_name'] = preg_replace("/[左右]+污油舱 /", "", $value[2]);
            $cabin_id = 0;
            foreach ($cabins_info as $v11) {
                if (trimall($data['cabin_name']) == $v11['cabinname']) {
                    $cabin_id = $v11['id'];
                }
            }
            if ($cabin_id == 0) continue;


            $data_row = explode("\r\n", $value[4]);
//            array_pop($data_row);
            $data['list_data'] = array();
            foreach ($data_row as $k1 => $v1) {
                $qian = array(" ", "　", "   ", "    ", '-', '0', '.');
                $hou = array("", "", "", "", "", "", "");
                if (str_replace($qian, $hou, $v1) == "") continue;
                $data_cloumn = explode(" ", $v1);
                $data_cloumn[count($data_cloumn) - 1] = str_replace("\r", "", $data_cloumn[count($data_cloumn) - 1]);

                $td = array('sounding' => $data_cloumn[0], 'ullage' => $data_cloumn[1], 'cabinid' => $cabin_id);
                $i = 0;
//                print_r($trim_kedu);
                foreach ($list_kedu as $k2 => $v2) {
                    $td["$k2"] = $data_cloumn[$i + 2];
                    $i++;
                }
                array_push($data['list_data'], $td);
//                foreach ($data_cloumn as $k2=>$v2){
//                    echo "<td>".$v2."</td>";
//                }
//                echo "</tr>";
            }
            array_push($res['data'], $data);
//            echo "</tbody>";
//            echo "</table>";
//            $ullage =
//            echo "<br/>";
        }
        return $res;
    }

    function auto_create_list_table($shipid, $kedu, $qufen)
    {
        $ship = new \Common\Model\ShipFormModel();
        $where = array('id' => $shipid);
        $ship_info = $ship->field('shipname,suanfa,heelingcorrection,heelingcorrection1,hx,hx_1')->where($where)->find();
//        $isTable = M()->query('SHOW TABLES LIKE "user"');
//        if($isTable){
//            echo '表存在';
//        }else{
//            echo '表不存在';
//        }
        if ($ship_info['suanfa'] == 'b' and $ship_info['hx'] == "") {
            $hxname = 'tablelistcorrectionzi' . time() . chr(rand(97, 122));
            $shipname = $ship_info['name'];
            // 确定刻度

            $str = '';
            foreach ($kedu as $key => $value) {
                $str .= "`" . $key . "` int(11) DEFAULT NULL COMMENT '横倾值" . $value . "°',";
            }

            $sql1 = <<<sql
CREATE TABLE `${hxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}横倾表';
sql;
            M()->execute($sql1);
            if (!empty($kedu)) {
                $kedu = json_encode($kedu, JSON_UNESCAPED_UNICODE);
            } else {
                $kedu = '';
            }

            $datas = array(
                'hx' => $hxname,
                'heelingcorrection' => $kedu
            );

        }elseif ($ship_info['suanfa'] == 'c'){
            $datas = array();
            $time = time() . chr(rand(97, 122));
            if($ship_info['hx'] == ""){
                $hxname = 'tablelistcorrectionzi' . $time."_1";
                $shipname = $ship_info['name'];
                // 确定刻度

                $str = '';
                foreach ($kedu as $key => $value) {
                    $str .= "`" . $key . "` int(11) DEFAULT NULL COMMENT '横倾值" . $value . "°',";
                }
                $sql1 = <<<sql
CREATE TABLE `${hxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}横倾表';
sql;
                M()->execute($sql1);
                if (!empty($kedu)) {
                    $kedu_str = json_encode($kedu, JSON_UNESCAPED_UNICODE);
                } else {
                    $kedu_str = '';
                }

                $datas['hx'] =  $hxname;
                $datas['heelingcorrection'] = $kedu_str;
            }


            if($ship_info['hx_1'] == ""){
                $hxname = 'tablelistcorrectionzi' . $time."_2";
                $shipname = $ship_info['name'];
                // 确定刻度

                $str = '';
                foreach ($kedu as $key => $value) {
                    $str .= "`" . $key . "` int(11) DEFAULT NULL COMMENT '横倾值" . $value . "°',";
                }
                $sql1 = <<<sql
CREATE TABLE `${hxname}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounding` float(5,3) DEFAULT NULL COMMENT '测深/m',
  `ullage` float(5,3) DEFAULT NULL COMMENT '空高/m',
  ${str}
  `cabinid` int(11) DEFAULT NULL COMMENT '舱ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='${shipname}横倾表';
sql;
                M()->execute($sql1);
                if (!empty($kedu)) {
                    $kedu_str = json_encode($kedu, JSON_UNESCAPED_UNICODE);
                } else {
                    $kedu_str = '';
                }

                $datas['hx_1'] =  $hxname;
                $datas['heelingcorrection1'] = $kedu_str;
            }
        }
        $ship->editData($where, $datas);
        return $ship->field('suanfa,is_diliang,tripbystern,trimcorrection,trimcorrection1,heelingcorrection,heelingcorrection1,tankcapacityshipid,rongliang,zx,hx,rongliang_1,zx_1,hx_1')->where(array('id' => $shipid))->find();
    }

}