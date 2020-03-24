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
}