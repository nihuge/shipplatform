<?php
// 设置页面编码
header("Content-type:text/html;charset=utf-8");
//设置时区
date_default_timezone_set('Asia/Shanghai');
// 允许APP端AJAX跨域请求
header("Access-Control-Allow-Origin: *");

class ReckonCargo
{
    public $remark = '';//类解说
    public $function_remark = '';//方法解说
    public $getTrim = 0;//纵倾值
    public $TrimFixValue = 0;

    /**
     * 根据给定参数算出舱内货物重量
     * @param string $suanfa ,船舱算法,A或者B或者C
     * @param int/float $chishui 艉吃水-艏吃水的差 单位米
     * @param int/float $zongxiu A算法不传。纵修插值，单位米，毫米单位请提前÷1000
     * @param array $rongliang 容量数据数组，如果是A算法，请传入带有纵倾刻度的容量表
     * @param float $midu 当前货物密度
     * @param int $shiyanwendu 测量货物密度的实验室温度是多少，单位为℃
     * @param int $wendu 现在的货物温度
     * @param int $pzbeishu ,膨胀系数算法中的倍数参数，一般为3
     * @param float/int $konggao ,当前舱空高 单位米
     * @param float/int $jizhungao ,当前舱基准高 单位米
     * @param boolean $diliang ,当前舱的舱底容量，没有底部测量孔时需要填
     * @param float/int $you_diliang ,当前舱有多少底量，如果不计算底量，请传0
     * @param boolean $han_guanxian ,舱容量所示容量是否包括管线容量
     * @param float/int $you_guanxian ,管线内容量有多少
     * @return array 返回结果
     */
    public function getWeight($suanfa, $chishui, $zongxiu, $rongliang, $midu, $shiyanwendu, $wendu, $pzbeishu, $konggao, $jizhungao, $diliang, $you_diliang, $han_guanxian, $you_guanxian)
    {
        //15度实验室密度
        $nowmidu = 0;
        //修正基准高度
        $nowjizhungao = 0;
        //修正空高
        $nowkonggao = 0;
        //体积修正系数
        $volume = 0;
        //膨胀体积修正系数
        $expand = 0;
        //纠正后的纵倾修正系数
        $nowzongxiu = 0;

        $nowjizhungao = round($jizhungao, 3);
        $this->remark .= '收到的基准高度为' . $jizhungao . "米，保留3位小数，所以修正后参与运算的基准高度为:" . $nowjizhungao . "\r\n";
        $nowkonggao = round($konggao, 3);
        $this->remark .= '收到的空高为' . $konggao . "米，保留3位小数，所以修正后参与运算的空高度为:" . $nowkonggao . "\r\n";

        /*
         * 该段算15度下的密度
         */
        $this->remark .= '第一步，密度转换:\r\n';
        if ($shiyanwendu == '20') {
            $this->remark .= '当前提供的密度为' . $midu . '，由于实验室温度为20摄氏度，转换成15摄氏度下的密度为密度÷0.9969，';
            $nowmidu = $midu / 0.9969;
            $this->remark .= '得到转换后的密度为' . $nowmidu . '\r\n';
        } elseif ($shiyanwendu == '25') {
            $this->remark .= '当前提供的密度为' . $midu . '，由于实验室温度为25摄氏度，转换成15摄氏度下的密度为密度÷0.9937，';
            $nowmidu = $midu / 0.9937;
            $this->remark .= '得到转换后的密度为' . $nowmidu . '\r\n';
        } elseif ($shiyanwendu == '15') {
            $this->remark .= '当前提供的密度为' . $midu . '，由于实验室温度为15摄氏度，密度不用转换\r\n';
            $nowmidu = $midu;
        }

        $this->remark .= '第二步，获得体积修正系数：\r\n实验室15度下密度为：' . $nowmidu . "，舱壁平均温度为：" . $wendu . '摄氏度，';
        // 获取体积修正(15度的密度、温度)
        $volume = $this->getVc($nowmidu, $wendu);
        $this->remark .= $this->function_remark;
        $this->remark .= "根据算法获得体积修正系数为" . $volume . "\r\n";

        $this->remark .= '第三步，获得膨胀体积修正系数：\r\n';
        // 膨胀修正
        $this->remark .= '膨胀倍数为：' . $nowmidu . "倍，舱壁平均温度为：" . $wendu . "摄氏度，";
        $expand = $this->getExpand($pzbeishu, $wendu);
        $this->remark .= $this->function_remark;
        $this->remark .= "根据算法获得膨胀体积修正系数为" . $expand . "\r\n";
        $this->function_remark = '';
        /*
         * 该段判断有无管线容量或者是否要加管线容量
         */
        $this->remark .= '第四步，确定是否要加管线容量：\r\n';
        if ($han_guanxian == false and $you_guanxian > 0) {
            // 船容量不包含管线，管线有货=舱管线容量+舱容量
            $gx = round($you_guanxian, 3);
            $this->remark .= "由于舱容量表内不包含管线，且管线内有货物，所以要加上管线容量" . $gx . "\r\n";
        } elseif ($han_guanxian == false and $you_guanxian = 0) {
            // 船容量不包含管线，管线无容量
            $gx = 0;
            $this->remark .= "由于舱容量表内不包含管线，且管线内没有货物，所以要加的管线容量为0\r\n";
        } elseif ($han_guanxian == true and $you_guanxian > 0) {
            // 船容量包含管线，管线有容量
            $gx = 0;
            $this->remark .= "由于舱容量表内包含管线，且管线内有货物，所以要加的管线容量为0\r\n";
        } elseif ($han_guanxian == true and $you_guanxian = 0) {
            // 船容量包含管线，管线无容量--容量=舱容量-舱管线容量
            // $gx = 0-$guan['pipe_line'];
            // 2018/12/18    根据三通809的管线计算错误做修改
            #todo 存在驳论，留此标记用于以后修复
            /*
             * 记录驳论，总共两种情况：
             * 1、既然录入员输入管线内无货，那么页面高度肯定没有高于管线，那么就不应该减去管线容量。
             * 2、如果录入员输入有管线容量，那么页面高度高于了管线，更不应该减去管线容量了。
             *
             * 除非有特殊情况，比如这条船在偷货等等。
             */
            $gx = 0;
            $this->remark .= "由于舱容量表内包含管线，且管线内没有货物，原本应该减去管线容量，但是由于一种驳论，所以要加的管线容量为0\r\n";
        }


        $this->remark .= '第五步，区分算法：\r\n';
        // 根据船信息区分算法
        switch ($suanfa) {
            case 'a':
                $this->remark .= "由于该船没有纵倾修正表和底量测量孔,所以执行算法A.\r\n";
                //当空高大于等于基准高度并且没有底量的时候
                if ($you_diliang > 0 and $nowjizhungao == $nowkonggao) {
                    $cabinweight = 0;
                    $this->remark .= "ops!由于空高等于基准高度且底量为空，所以舱容量为0\r\n";
                } else {
                    $this->remark .= '由于计算底量或者空高不等于基准高度，所以继续执行算法\r\n';
                    $cabinweight = $this->getBookTrimFixValue($rongliang, $chishui, $konggao, 'a', $gx);
                    $this->remark .= $this->function_remark;
                }
                // 计算标准容量   容量*体积*膨胀
                $standardcapacity = round($cabinweight * $volume * $expand, 3);
                $this->remark .= '第六步，舱容量修正计算开始：\r\n 仓容量*体积修正系数*膨胀修正系数，取3位小数，修正后的舱容量为：' . $standardcapacity . '\r\n';
                break;
            case $suanfa == 'b' and $suanfa == 'c':
                $this->remark .= "由于该船有纵倾修正表，但是没有底量测量孔,所以执行算法B.\r\n根据之前的计算，我们得到的纵倾修正插值为" . $zongxiu . "，将得到的纵倾修正值和空高-基准高度的差值比较，以最小的值为准\r\n";
                //根据纵修与空高-基准高的差值比较取小，算法用空高-基准高是因为纵修一般都为负数
                $chazhi = round(($konggao - $jizhungao), 3);
                $this->remark .= "空高-基准高后保留3位小数的结果为：" . $chazhi . "\r\n";
                if ($chazhi > $zongxiu) {
                    $nowzongxiu = $chazhi;
                    $this->remark .= "由于差值大于纵修值，所以修正后的纵修值为：" . $nowzongxiu . "\r\n";
                } elseif ($chazhi < $zongxiu) {
                    $nowzongxiu = $zongxiu;
                    $this->remark .= "由于差值小于纵修值，所以修正后的纵修值为：" . $nowzongxiu . "\r\n";
                } elseif ($chazhi == $zongxiu) {
                    $nowzongxiu = $chazhi;
                    $this->remark .= "由于差值大于纵修值，所以修正后的纵修值为：" . $nowzongxiu . "\r\n";
                }

                //得到修正空距 空距+纵倾修正值
                $xiukong = round($konggao - $nowzongxiu, 3);
                $this->remark .= "将空高-纵修值，修正后空高为：" . $xiukong . "\r\n";
                //当修正空高大于等于基准高度并且不计算底量的时候
                if ($you_diliang > 0 and $jizhungao == $xiukong) {
                    $cabinweight = 0;
                    $this->remark .= "ops!由于修正后空高等于基准高度且底量为空，所以舱容量为0\r\n";
                } else {
                    $this->remark .= '由于计算底量或者空高不等于基准高度，所以继续执行算法\r\n';
                    //根据容量表查询当前容量
                    $cabinweight = $this->getCapacity($rongliang, $chishui, $xiukong, $gx);
                    $this->remark .= $this->function_remark;
                }
                // 计算标准容量   容量*体积*膨胀
                $standardcapacity = round($cabinweight * $volume * $expand, 3);
                $this->remark .= '第六步，舱容量修正计算开始：\r\n 仓容量*体积修正系数*膨胀修正系数，取3位小数，修正后的舱容量为：' . $standardcapacity . '\r\n';
                break;
        }

        $total = round($standardcapacity * ($midu - 0.0011), 3);
        $this->remark .= '第七步，计算货物重量，最终的修正后舱容量*(密度-空气浮力)保留3位小数得到货物重量为' . $total . '吨\r\n';


        return array(
            'total' => $total,//总重量
            'standardCapacity' => $standardcapacity,//修正后舱容量
            ''
        );
    }


    /**
     * 根据录入的书本数据获得纵倾修正插值
     * @param array $trimData 书本纵倾表数组
     * @param int|float $chishui 当前吃水差
     * @param int|float $konggao 当前空高
     * @return int|float TrimFixValue 纵倾修正值插值
     */
    function getTrim($trimData, $chishui, $konggao)
    {
        $this->TrimFixValue = $this->getBookTrimFixValue($trimData, $chishui, $konggao, 'b');
        return $this->TrimFixValue;
    }

    /**
     *
     * 根据录入书本数据获得序列后的可以用来参与计算的纵倾修正值
     * 如果是A算法，则直接返回容量
     *
     * @param array $data 纵倾值书本数据
     * @param int|float $chishui 吃水差
     * @param int|float $konggao 测量空高
     * @param string $suanfa 算法
     * @param int|float $gx 管线内容量
     * @return int|float 返回纵修值或者容量
     */
    public function getBookTrimFixValue($data, $chishui, $konggao, $suanfa, $gx = 0)
    {
        $this->function_remark .= '';
        if ($chishui <= $data['draft1']) {
            $qiu[] = $chishui;
            $keys = array(
                0 => 'draft1'
            );
            $this->function_remark .= '由于吃水差（' . $chishui . '）小于或等于录入数据的最小极值(' . $data['draft1'] . ')，所以吃水差按极值算。\r\n';
            // 判断测试空高是否在数据中存在
            if ($konggao <= $data['ullage1']) {

                $ulist[] = array(
                    'ullage' => $data['ullage1'],   //输入的空高
                    'draft1' => $data['value1']
                );
                $this->function_remark .= '由于空高(' . $konggao . ')小于或等于录入数据的最小极值(' . $data['ullage1'] . ')，所以空高按极值算。\r\n';
            } elseif ($konggao >= $data['ullage2']) {
                $ulist[] = array(
                    'ullage' => $data['ullage2'],   //输入的空高
                    'draft1' => $data['value3']
                );
                $this->function_remark .= '由于空高(' . $konggao . ')大于或等于录入数据的最大极值(' . $data['ullage2'] . ')，所以空高按极值算。\r\n';
            } else {
                $ulist = array(
                    0 => array(
                        'ullage' => $data['ullage1'],   //输入的空高
                        'draft1' => $data['value1']
                    ),
                    1 => array(
                        'ullage' => $data['ullage2'],   //输入的空高
                        'draft1' => $data['value3']
                    )
                );
                $this->function_remark .= '由于空高(' . $konggao . ')在录入数据值的中间，所以空高需要计算插值\r\n';
            }
        } elseif ($chishui >= $data['draft2']) {

            $qiu[] = $chishui;
            // 下标
            $keys = array(
                0 => 'draft2'
            );
            $this->function_remark .= '由于吃水差（' . $chishui . '）超过录入数据的最大极值(' . $data['draft2'] . ')，所以吃水差按极值算。\r\n';
            // 判断测试空高是否在数据中存在
            if ($konggao <= $data['ullage1']) {
                $ulist[] = array(
                    'ullage' => $data['ullage1'],   //输入的空高
                    'draft2' => $data['value2']
                );
                $this->function_remark .= '由于空高(' . $konggao . ')小于或等于录入数据的最小极值(' . $data['ullage1'] . ')，所以空高按极值算。\r\n';
            } elseif ($konggao >= $data['ullage2']) {
                $ulist[] = array(
                    'ullage' => $data['ullage2'],   //输入的空高
                    'draft2' => $data['value4']
                );
                $this->function_remark .= '由于空高(' . $konggao . ')大于或等于录入数据的最大极值(' . $data['ullage2'] . ')，所以空高按极值算。\r\n';
            } else {
                $ulist = array(
                    0 => array(
                        'ullage' => $data['ullage1'],   //输入的空高
                        'draft2' => $data['value2']
                    ),
                    1 => array(
                        'ullage' => $data['ullage2'],   //输入的空高
                        'draft2' => $data['value4']
                    )
                );
                $this->function_remark .= '由于空高(' . $konggao . ')在录入数据值的中间，所以空高需要计算插值\r\n';
            }
        } else {
            $this->function_remark .= '由于吃水差（' . $chishui . '）在录入数据值的中间，所以吃水差需要计算插值\r\n';
            $qiu = array(
                'draft1' => $data['draft1'],
                'draft2' => $data['draft2']
            );
            // 下标
            $keys = array(
                0 => 'draft1',
                1 => 'draft2'
            );

            // 判断测试空高是否在数据中存在
            if ($konggao <= $data['ullage1']) {
                $ulist[] = array(
                    'ullage' => $data['ullage1'],   //输入的空高
                    'draft1' => $data['value1'],
                    'draft2' => $data['value2']
                );
                $this->function_remark .= '由于空高(' . $konggao . ')小于或等于录入数据的最小极值(' . $data['ullage1'] . ')，所以空高按极值算。\r\n';

            } elseif ($konggao >= $data['ullage2']) {
                $ulist[] = array(
                    'ullage' => $data['ullage2'],   //输入的空高
                    'draft1' => $data['value3'],
                    'draft2' => $data['value4']
                );
                $this->function_remark .= '由于空高(' . $konggao . ')大于或等于录入数据的最大极值(' . $data['ullage2'] . ')，所以空高按极值算。\r\n';

            } else {
                $ulist = array(
                    0 => array(
                        'ullage' => $data['ullage1'],   //输入的空高
                        'draft1' => $data['value1'],
                        'draft2' => $data['value2']
                    ),
                    1 => array(
                        'ullage' => $data['ullage2'],   //输入的空高
                        'draft1' => $data['value3'],
                        'draft2' => $data['value4']
                    )
                );
                $this->function_remark .= '由于空高(' . $konggao . ')在录入数据值的中间，所以空高需要计算插值\r\n';
            }
        }
        if ($suanfa == 'a') {
            //如果是算法A，由于表内自带纵倾刻度和容量，所以直接能拿到容量，不用单独算纵倾修正插值了
            $zongxiu = round($this->suanfa($qiu, $ulist, $keys, $konggao, $chishui), 3) + $gx;
            if ($zongxiu != false) {
                $this->function_remark .= '保留3位小数并且加上管线容量以后，算出容量为：' . $zongxiu . '\r\n';
            } else {
                exit(json_encode(array('error' => 5011, 'msg' => '容量区间算法处发生异常')));
            }
        } else {
            $zongxiu = round($this->suanfa($qiu, $ulist, $keys, $konggao, $chishui), 0) / 1000;
            if ($zongxiu != false) {
                $this->function_remark .= '四舍五入取整后再÷1000，算出的最终纵倾插值为：' . $zongxiu . '\r\n';
            } else {
                exit(json_encode(array('error' => 5012, 'msg' => '纵倾值区间算法处发生异常')));
            }
        }
        return $zongxiu;
    }

    /**
     * 根据给定的非A类算法的容/底量表数据获得初步船舱容量
     * @param array $data 容量表数据
     * @param int|float $chishui 吃水差
     * @param int|float $ullage 修正后的空高
     * @param int|float $gx 管线容量
     * @return float|int 初步船舱容量
     */
    public function getCapacity($data, $chishui, $ullage, $gx)
    {
        $this->function_remark .= '';
        // 判断容量大小先后  dt1代表大   dt2代表小
        if ($data['ullage1'] > $data['ullage2']) {
            $dt1 = array(
                'ullage' => $data['ullage1'],
                'capacity' => $data['capacity1']
            );
            $dt2 = array(
                'ullage' => $data['ullage2'],
                'capacity' => $data['capacity2']
            );
        } else {
            $dt1 = array(
                'ullage' => $data['ullage2'],
                'capacity' => $data['capacity2']
            );
            $dt2 = array(
                'ullage' => $data['ullage1'],
                'capacity' => $data['capacity1']
            );
        }
        $this->function_remark .= '首先，排序容量表，判断容量表的大小先后。\r\n成功排序后，判断修正后的空高是否在容量数据的区间：';

        // 判断修正后的空高是否在数据中存在
        if ($ullage >= $dt1['ullage']) {
            $ulist[] = array(
                'ullage' => $dt1['ullage'],   //输入的空高
                'capacity' => $dt1['capacity']
            );
            $this->function_remark .= '由于修正后的空高(' . $ullage . ')大于容量表内最大的空高(' . $dt1['ullage'] . ')，超过了极值，所以按照极值算' . $dt1['ullage'] . '\r\n';
        } elseif ($ullage <= $dt2['ullage']) {
            $ulist[] = array(
                'ullage' => $dt2['ullage'],   //输入的空高
                'capacity' => $dt2['capacity']
            );
            $this->function_remark .= '由于修正后的空高(' . $ullage . ')小于容量表内最小的空高(' . $dt2['ullage'] . ')，超过了极值，所以按照极值算为' . $dt2['ullage'] . '\r\n';
        } else {
            $ulist = array(
                0 => array(
                    'ullage' => $dt1['ullage'],   //输入的空高
                    'capacity' => $dt1['capacity']
                ),
                1 => array(
                    'ullage' => $dt2['ullage'],   //输入的空高
                    'capacity' => $dt2['capacity']
                )
            );
            $this->function_remark .= '由于修正后的空高(' . $ullage . ')小于容量表的空高区间内，开始计算区间容量：\r\n';
        }

        $qiu[] = array('capacity' => 1);
        // 下标--随意定义，只要是一位数组
        $keys[] = 'capacity';
        //根据提交数据计算
        //计算容量
        $cabinweight = round($this->suanfa($qiu, $ulist, $keys, $ullage, $chishui), 3) + $gx;
        if ($cabinweight == false) {
            exit(json_encode(array('error' => 5013, 'msg' => '非A类算法,容量区间计算处错误')));
        }
        $this->function_remark .= '保留3位小数并且加上管线容量以后，算出的容量为：' . $cabinweight . '\r\n';

        return $cabinweight;
    }


    /**
     * 计算船舱货物容量
     * @param array $qiu 表内刻度数组
     * @param array $ulist
     * @param array $keys
     * @param string $ullage
     * @param string $chishui
     * @return array|float|int|mixed
     */
    public function suanfa($qiu, $ulist, $keys = array(), $ullage = '', $chishui = '')
    {
        $this->function_remark .= '插值计算开始，步骤如下：\r\n';
        //四种情况计算容量
        if (count($qiu) == '1' and count($ulist) == '1') {
            //【1】纵倾（吃水差）查出一条，空高查出1条
            $res = $ulist[0][$keys[0]];
            $this->function_remark .= '检测到需要计算的吃水差插值只有1条，需要计算的空高插值只有1条，所以不需要计算直接按照极值返回结果：' . $res . '\r\n';
        } elseif (count($qiu) == '2' and count($ulist) == '2') {
            $this->function_remark .= '检测到需要计算的吃水差插值有2条，需要计算的空高插值只有2条，先计算较大空高下的中间插值：\r\n';
            //【2】纵倾（吃水差）查出2条，空高查出2条
            $hou = $this->getMiddleValue((float)$ulist[1][$keys[1]], (float)$ulist[0][$keys[1]], (float)$ulist[1]['ullage'], (float)$ulist[0]['ullage'], $ullage);
            $this->function_remark .= '根据插值计算算法得出较大刻度的中间插值为：' . $hou . '，然后计算较小的刻度中间插值：\r\n';
            $qian = $this->getMiddleValue((float)$ulist[1][$keys[0]], (float)$ulist[0][$keys[0]], (float)$ulist[1]['ullage'], (float)$ulist[0]['ullage'], $ullage);
            $this->function_remark .= '根据插值计算算法得出较小刻度的中间插值为：' . $qian . '，然后汇总两个刻度的空高插值计算当前刻度下的中间插值：\r\n';

            $res = $this->getMiddleValue($hou, $qian, $qiu[$keys[1]], $qiu[$keys[0]], $chishui);
            $this->function_remark .= '根据插值计算算法得当前空高的中间插值为：' . $res . '。\r\n';
        } elseif (count($qiu) == '1' and count($ulist) == '2') {
            $this->function_remark .= '检测到需要计算的吃水差插值有1条，需要计算的空高插值只有2条，所以计算当前空高下的中间插值：\r\n';
            //【3】纵倾（吃水差）查出1条，空高查出2条
            $res = $this->getMiddleValue((float)$ulist[1][$keys[0]], (float)$ulist[0][$keys[0]], (float)$ulist[1]['ullage'], (float)$ulist[0]['ullage'], $ullage);
            $this->function_remark .= '根据插值计算算法得当前空高的中间插值为：' . $res . '。\r\n';
        } elseif (count($qiu) == '2' and count($ulist) == '1') {
            $this->function_remark .= '检测到需要计算的吃水差插值有2条，需要计算的空高插值只有1条，所以计算当前空高下的中间插值：\r\n';
            //【4】纵倾（吃水差）查出2条，空高查出1条
            $res = $this->getMiddleValue($ulist[0][$keys[1]], $ulist[0][$keys[0]], $qiu[$keys[1]], $qiu[$keys[0]], $chishui);
            $this->function_remark .= '根据插值计算算法得当前空高的中间插值为：' . $res . '。\r\n';
        } else {
            //其他错误	2
            $res = false;
        }
        return $res;
    }


    /**
     * 根据当前密度和温度算出体积修正系数
     * @param float|int $M 密度
     * @param int $T 温度
     * @return float|int 体积修正系数
     */
    public function getVc($M, $T)
    {

        if ($M >= 0.966) {
            $this->function_remark = "由于密度($M)大于等于0.966，所以算法为：1.0094684142 - 6.33413410744 * 0.0001 * $T + 1.45710416212 * 0.0000001 * ($T * $T)，";
            $Vc = 1.0094684142 - 6.33413410744 * 0.0001 * $T + 1.45710416212 * 0.0000001 * ($T * $T);
        } else {
            $this->function_remark = "由于密度($M)小于0.966，所以算法为：1.0108020095 - 7.2343515319 * 0.0001 * $T + 2.1996598346 * 0.0000001 * ($T * $T)，";
            $Vc = 1.0108020095 - 7.2343515319 * 0.0001 * $T + 2.1996598346 * 0.0000001 * ($T * $T);
        }
        $this->function_remark .= "得到结果为:$Vc";
        $Vc = round($Vc, 4);
        $this->function_remark .= "取4位小数后，结果为：$Vc";
        return $Vc;
    }

    /**
     * 计算膨胀修正系数
     * @param int|float $V 膨胀倍数
     * @param int|float $T 当前温度
     * @return int|float 膨胀修正系数
     */
    public function getExpand($T, $V)
    {
        $this->function_remark = "膨胀体积修正算法开始,算法为：round((1 + 0.000012 * ($V) * (($T) - 20)), 6)，";
        $V = round((1 + 0.000012 * ($V) * (($T) - 20)), 6);
        $this->function_remark .= "得到结果$V";
        return $V;
    }

    /**
     * 插值计算函数,核心算法，根据大参数和小参数算出中间的参数
     * @param int|float $Cbig 大数值
     * @param int|float $Csmall 小数值
     * @param int|float $Xbig 大刻度
     * @param int|float $Xsmall 小刻度
     * @param int|float $X 当前刻度
     * @return float|int 中间插值
     */
    public function getMiddleValue($Cbig, $Csmall, $Xbig, $Xsmall, $X)
    {
        $this->function_remark .= "\r\n进入插值计算函数：round(($Cbig - ($Csmall)), 3) / ($Xbig - ($Xsmall)) * ($X - ($Xsmall)) + $Csmall ，\r\n";
        $suanfa = round(($Cbig - ($Csmall)), 3) / ($Xbig - ($Xsmall)) * ($X - ($Xsmall)) + $Csmall;
        $this->function_remark .= '\r\n得出结果为：' . $suanfa . '\r\n';
        return $suanfa;
    }


    /**
     * 给定范围数组和要搜索的值，根据要搜索的值获得数组内比它大的值对应的键和比它小的值对应的键，和另一个方法相比，此方法适用于3个或以上刻度的数组
     * @param array $data 范围数组
     * @param int|float $mid 要搜索的值
     * @return mixed $qiu 如果正好在数组内，则返回字符串，如果不在数组内，则给定范围数组.
     */
    public function getTrimFixValue($data, $mid)
    {

        // 计算纵倾修正
        // json转化数组
        $arrtb = $data;
        $array = array();
        $arrayxiao = array();
        $arrayda = array();
        // 判断数据是否在纵倾修正值数组内
        foreach ($arrtb as $key => $value) {
            if ($mid == $value) {
                $array[] = array(
                    $key => $value
                );
            } elseif ($mid > $value) {
                //获取所有比纵倾值小
                $arrayxiao[$key] = $value;
            } elseif ($mid < $value) {
                //获取所有比纵倾值大
                $arrayda[$key] = $value;
            }
        }
        //判断是否有对应的纵倾修正值
        if (count($array) == '1') {
            //①正巧取到纵倾修正值
            //舱容表对应的key与value
            $qiu = $array[0];
        } elseif (count($array) == '0' and count($arrayxiao) >= '1' and count($arrayda) >= '1') {
            // ②取到两条数据，最小的最大数据、最大的最小数据
            // 获取最小列表的最大值(比吃水值小)
            $k = array_search(max($arrayxiao), $arrayxiao);
            $qiu[$k] = $arrayxiao[$k];
            //获取最大列表的最小值(比吃水值大)
            $x = array_search(min($arrayda), $arrayda);
            $qiu[$x] = $arrayda[$x];
        } elseif (count($array) == '0' and count($arrayxiao) == '0' and count($arrayda) >= '1') {
            //③只取到一条最大的最小数据
            //获取最大列表的最小值(比吃水值大)
            $x = array_search(min($arrayda), $arrayda);
            $qiu[$x] = $arrayda[$x];
        } elseif (count($array) == '0' and count($arrayxiao) >= '1' and count($arrayda) == '0') {
            //④只取到一条最小的最大数据
            //获取最小列表的最大值(比吃水值小)
            $k = array_search(max($arrayxiao), $arrayxiao);
            $qiu[$k] = $arrayxiao[$k];
        }
        return $qiu;
    }
}


$reckon = new ReckonCargo();
$trimArr = array(
    'draft1' => isset($_POST['draft1']) ? $_POST['draft1'] : 0,
    'draft2' => isset($_POST['draft2']) ? $_POST['draft2'] : 0,
    'ullage1' => isset($_POST['ullage1']) ? $_POST['ullage1'] : 0,
    'ullage2' => isset($_POST['ullage2']) ? $_POST['ullage2'] : 0,
    'value1' => isset($_POST['value1']) ? $_POST['value1'] : 0,
    'value2' => isset($_POST['value2']) ? $_POST['value2'] : 0,
    'value3' => isset($_POST['value3']) ? $_POST['value3'] : 0,
    'value4' => isset($_POST['value4']) ? $_POST['value4'] : 0,
);
$konggao = isset($_POST['konggao']) ? $_POST['konggao'] : 0;
$chishui = isset($_POST['chishui']) ? $_POST['chishui'] : 0;
$trimFix = $reckon->getTrim($trimArr, $chishui, $konggao);
echo $trimFix . "<br/>";
echo str_replace(array('\r\n', '\n', ' '), array('<br/>', '<br/>', '&nbsp;'), $reckon->function_remark) . "<br/>";
echo "修正后空高为空高-修正值:" . $konggao . '-' . $trimFix . '=' . ($konggao - $trimFix);