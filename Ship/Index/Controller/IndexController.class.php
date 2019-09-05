<?php

namespace Index\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function index()
    {
        $article = new \Common\Model\ArticleModel();
        // 获取最新的9条资讯信息
        $data = $article->getPageData(1, 1, 9);
        $assign = array(
            'data' => $data
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 获取开始的统计图表
     * 展示各个货物的重量，用于展示占比等等
     *     legend: {
     *          data:['重量'],
     *          textStyle:{
     *            "color": ["#3cefff"],
     *          }
     *       },
     */
    public function countIndexPic()
    {
        $optionJson = <<<option
{
    "title": {
        "text": "港口数据统计",
        "textStyle":{
            "color":"#3cefff",
            "fontSize":40
        }
    },
    "backgroundColor": "rgb(20, 58, 110)",
    "color": ["#3cefff"],
    "tooltip": {},
    "xAxis": {
        "axisTick": {
            "alignWithLabel": true
        },
        "nameTextStyle": {
            "color": "#82b0ec"
        },
        "axisLine": {
            "lineStyle": {
                "color": "#82b0ec"
            }
        },
        "axisLabel": {
            "textStyle": {
                "color": "#82b0ec"
            }
        }
    },
    "yAxis": {
        "data": ["衬衫","羊毛衫","雪纺衫","裤子","高跟鞋","袜子"],
        "nameTextStyle": {
            "color": "#82b0ec"
        },
        "axisLine": {
            "lineStyle": {
                "color": "#82b0ec"
            }
        },
        "axisLabel": {
            "textStyle": {
                "color": "#82b0ec"
            }
        },
        "minInterval":30,
        "maxInterval":100
        
    },
    
    "series": [{
        "name": "重量",
        "type": "bar",
        "data": [5, 20, 36, 10, 10, 20],
        "label": {
            "normal": {
                "show": true,
                "position": "right",
                "formatter": "{c} T"
            }
        },
        "itemStyle":{
            "color":{
                "image": "g_cellBarImg0_y",
                "repeat": "repeat"
            }
        }
    }],
    "dataZoom":[{
            "orient":"vertical",
            "type":"inside"
        },
        {
            "orient":"vertical",
            "type":"slider",
            "dataBackground":{
                "lineStyle":{
                    "color":"#3cefff",
                    "width":2
                }
            },
            "textStyle":{
                "color":"#fff",
                "fontSize":20
            }
        }]
}
option;
//        print_r(json_decode($option,true));
////        exit();
        $option = json_decode($optionJson, true);

        $dataTable = M('testcount');
        $CountSql = "SELECT sum(weight)/1000 as totalweight,cargo FROM testcount GROUP BY cargo ORDER BY totalweight asc";
        $CargoWeight = $dataTable->query($CountSql);
        //开始构成图表数据数组
        $cargoData = array();
        $weightData = array();
        foreach ($CargoWeight as $key => $value) {
            if ($value['cargo'] == "") {
                $CargoWeight[$key]['cargo'] = "其他";
                $value['cargo'] = "其他";
            }
            $cargoData[] = $value['cargo'];
            $weightData[] = $value['totalweight'];
        }

//        exit(print_r($cargoData));
//        $option['yAxis']['data'] = array();
//        $option['series']['data'] = array();

//        print_r($option);
//        exit();

        $option["tooltip"] = (object)array();
        $option["xAxis"] = (object)array();
        $option['yAxis']['data'] = $cargoData;
        $option['series'][0]['data'] = $weightData;

//        $optionAsign = array(
//
//        );
        $this->assign("option", json_encode($option, JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    public function countGK()
    {
        $dataTable = M('testcount');
        $CountSql = "SELECT sum(weight)/1000 as totalweight,pier FROM testcount GROUP BY pier ORDER BY totalweight desc";
        $CargoWeight = $dataTable->query($CountSql);
        //开始构成图表数据数组
        $Data = array();
        foreach ($CargoWeight as $key => $value) {
            $Data[$key] = array();
            if ($value['cargo'] == "") {
                $CargoWeight[$key]['cargo'] = "其他";
                $value['cargo'] = "其他";
            }
            $Data[$key]['name'] = $value['pier'];
            $Data[$key]['value'] = (double)$value['totalweight'];
        }
        $this->assign("option", json_encode($Data, JSON_UNESCAPED_UNICODE));
        $this->display();
    }


    public function countGKdCargo()
    {

        /*
         * 天宇码头：119.0831932327,32.2136299597
         * 惠宁码头：118.8776496481,32.1713841944
         * 西坝码头：118.9021274516,32.1995042101
         * 杨巴码头：118.8605950000,32.2310160000
         * 龙翔码头：118.8741720000,32.2213790000
         * 四公司：118.8682800000,32.1665840000
         * 扬子石化：118.8445520000,32.2401730000
         */

//        $geoGobalData=array(
//            "天宇码头"=>array(119.0831932327,32.2136299597),
//            "惠宁码头"=>array(118.8776496481,32.1713841944),
//            "西坝码头"=>array(118.9021274516,32.1995042101),
//            "杨巴码头"=>array(118.8605950000,32.2310160000),
//            "龙翔码头"=>array(118.8741720000,32.2213790000),
//            "四公司"=>array(118.8682800000,32.1665840000),
//            "扬子石化"=>array(118.8445520000,32.2401730000),
//            "南化码头" => array(118.8751450000, 32.2209980000),
//        );

        $geoGobalData = array(
            "天宇码头" => array(118.8591932327, 32.1636299597),
            "惠宁码头" => array(118.9276496481, 32.3473841944),
            "西坝码头" => array(118.5221274516, 31.9995042101),
            "扬巴码头" => array(118.6605950000, 31.8110160000),
            "龙翔码头" => array(119.0841720000, 31.5513790000),
            "四公司" => array(118.7682800000, 32.3865840000),
            "扬子石化" => array(119.1045520000, 31.3401730000),
            "南化码头" => array(118.7951450000, 31.3209980000),
        );

        $dataTable = M('testcount');
        $CountSql = "SELECT 0 + CAST(sum(weight)/1000 as CHAR) as totalweight,pier FROM testcount GROUP BY pier ORDER BY totalweight desc";
        $CargoWeight = $dataTable->query($CountSql);
        //开始构成图表数据数组
        $Data = array();
        foreach ($CargoWeight as $key => $value) {
            $Data[$key] = array();
//            if ($value['cargo'] == "") {
//                $CargoWeight[$key]['cargo'] = "其他";
//                $value['cargo'] = "其他";
//            }
            $Data[$key]['name'] = $value['pier'];
            $Data[$key]['value'] = $value['totalweight'];
        }
        $this->assign("optionData", json_encode($Data, JSON_UNESCAPED_UNICODE));
        $this->assign("GobalData", json_encode($geoGobalData, JSON_UNESCAPED_UNICODE));
        $this->display();
    }


    public function countGKdCargoCount()
    {

        $optionJson = <<<option
{
    "title": {
        "show":false,
        "text": "港口货物比例汇总",
        "textStyle": {
            "color": "#fff",
            "fontSize": 16
        }
    },
    "backgroundColor": "rgb(20, 58, 110)",
    "grid": {
        "containLabel": true
    },
    "tooltip": {
        "trigger": "item"
    },
    "xAxis": {
        "splitLine": {
            "show": false,
            "lineStyle": {
                "type": "dashed",
                "color": "#3e86dd"
            }
        },
        "axisLine": {
            "show": false,
            "lineStyle": {
                "type": "dashed",
                "color": "#3e86dd"
            }
            
        },
        "axisLabel": {
            "show": false
        },
        "axisTick": {
            "show": false
        }
    },
    "yAxis": [
        {
            "type": "category",
            "inverse": false,
            "data": [
                
            ],
            "axisLine": {
                "show": false
            },
            "axisTick": {
                "show": false
            },
            "splitLine": {
                "show": false,
                "lineStyle": {
                    "type": "dashed",
                    "color": "#3e86dd"
                }
            },
            "axisLabel": {
                "margin": 0,
                "textStyle": {
                    "color": "#fff",
                    "fontSize": 14
                }
            }
        }
    ],
    "series": [
        {
            "tooltip": {
                "show": false
            },
            "z": 1,
            "type": "pictorialBar",
            "symbolSize": [
                85,
                20
            ],
            "symbolRepeat": 8,
            "data": [
                
            ]
        },
        {
            "z": 6,
            "type": "pictorialBar",
            "symbolSize": [
                85,
                20
            ],
            "animation": true,
            "symbolRepeat": 8,
            "symbolClip": true,
            "symbolPosition": "start",
            "symbolRepeatDirection":"start",
            "symbolOffset": [
                0,
                0
            ],
            "data": [
                
            ],
            "label": {
                "normal": {
                    "show": true,
                    "textStyle": {
                        "color": "#18fcff",
                        "fontSize": 14
                    },
                    "position": "right",
                    "offset": [
                        0,
                        0
                    ]
                }
            }
        }
    ],
    "dataZoom":[{
            "orient":"vertical",
            "type":"inside"
        },
        {
            "orient":"vertical",
            "type":"slider",
            "dataBackground":{
                "lineStyle":{
                    "color":"#3cefff",
                    "width":2
                }
            },
            "textStyle":{
                "color":"#fff",
                "fontSize":20
            }
        }]
}
option;
//        print_r(json_decode($option,true));
////        exit();

//        var_dump($option);
//        exit;
        $dataTable = M('testcount');
//        $seachpier = array('惠宁码头', '西坝码头', '扬子石化', '龙翔码头');

//        foreach ($seachpier as $key=>$value) {
        $option = json_decode($optionJson, true);
        $CountSql = "SELECT sum(weight)/1000 as totalweight,cargo FROM testcount where pier='天宇码头' and cargo!='' GROUP BY cargo ORDER BY totalweight desc";

        $CargoWeight = $dataTable->query($CountSql);


        //开始构成图表数据数组
        $cargoData = array();
        $weightData = array();
        $iconData = array();


        foreach ($CargoWeight as $key => $value) {
            //每页最多13个货物
            if ($key % 12 == 11 && $key > 0) {

                $option["tooltip"] = (object)array();
                $option["xAxis"] = (object)array();
                $option['yAxis'][0]['data'] = $cargoData;


                foreach ($option['series'] as $key => $value) {
                    if ($key == 0) {
                        $option['series'][$key]['data'] = $iconData;
                    } elseif ($key == 1) {
                        $option['series'][$key]['data'] = $weightData;
                    }
                }
                $data[] = json_encode($option, JSON_UNESCAPED_SLASHES);
                //初始化设置
                $option = json_decode($optionJson, true);
                $cargoData = array();
                $weightData = array();
                $iconData = array();
            }
            $data_arr = array();
            if ($value['cargo'] == "") {
                /*$CargoWeight[$key]['cargo'] = "其他";
                $value['cargo'] = "其他";*/
                //如果货名为空则跳过
                continue;
            }
            if (mb_strlen($value['cargo'], 'utf8') > 7) {
                $value['cargo'] = mb_substr($value['cargo'], 0, 7, 'utf8') . '...';
            }
            $data_arr['value'] = (float)$value['totalweight'];
            $data_arr['symbol'] = "image://tpl/default/Index/Public/image/light.png";
            array_unshift($cargoData, $value['cargo']);
            array_unshift($weightData, $data_arr);
            array_unshift($iconData, array(
                'value' => 1,
                'symbol' => "image://tpl/default/Index/Public/image/dark.png"
            ));
            /*$cargoData[] = $value['cargo'];
            $weightData[] = $data_arr;
            $iconData[] = array(
                'value' => 1,
                'symbol' => "image://tpl/default/Index/Public/image/dark.png"
            );*/
        }


        $option["tooltip"] = (object)array();
        $option["xAxis"] = (object)array();
        $option['yAxis'][0]['data'] = $cargoData;


        foreach ($option['series'] as $key => $value) {
            if ($key == 0) {
                $option['series'][$key]['data'] = $iconData;
            } elseif ($key == 1) {
                $option['series'][$key]['data'] = $weightData;
            }
        }
        $data[] = json_encode($option, JSON_UNESCAPED_SLASHES);

        $top = ceil(count($data) / 4) * 30;
//        exit(print_r($cargoData));
//        $option['yAxis']['data'] = array();
//        $option['series']['data'] = array();

//        print_r($option);
//        exit();


//        $optionAsign = array(
//
//        );
        $this->assign("option", $data);
        $this->assign("top", $top);
        $this->display();
    }


    public function GKdCargoCount($pier)
    {

        $optionJson = <<<option
{
    "title": {
        "show":true,
        "text": "港口货物比例汇总",
        "textStyle": {
            "color": "#fff",
            "fontSize": 16
        }
    },
    "backgroundColor": "rgb(32,51,106)",
    "grid": {
        "containLabel": true
    },
    "tooltip": {
        "trigger": "item"
    },
    "xAxis": {
        "splitLine": {
            "show": false,
            "lineStyle": {
                "type": "dashed",
                "color": "#3e86dd"
            }
        },
        "axisLine": {
            "show": false,
            "lineStyle": {
                "type": "dashed",
                "color": "#3e86dd"
            }
            
        },
        "axisLabel": {
            "show": false
        },
        "axisTick": {
            "show": false
        }
    },
    "yAxis": [
        {
            "type": "category",
            "inverse": false,
            "data": [
                
            ],
            "axisLine": {
                "show": false
            },
            "axisTick": {
                "show": false
            },
            "splitLine": {
                "show": false,
                "lineStyle": {
                    "type": "dashed",
                    "color": "#3e86dd"
                }
            },
            "axisLabel": {
                "margin": 0,
                "textStyle": {
                    "color": "#fff",
                    "fontSize": 14
                }
            }
        }
    ],
    "series": [
        {
            "tooltip": {
                "show": false
            },
            "z": 1,
            "type": "pictorialBar",
            "symbolSize": [
                45,
                20
            ],
            "symbolRepeat": 8,
            "data": [
                
            ]
        },
        {
            "z": 6,
            "type": "pictorialBar",
            "symbolSize": [
                45,
                20
            ],
            "animation": true,
            "symbolRepeat": 8,
            "symbolClip": true,
            "symbolPosition": "start",
            "symbolRepeatDirection":"start",
            "symbolOffset": [
                0,
                0
            ],
            "data": [
                
            ],
            "label": {
                "normal": {
                    "show": true,
                    "textStyle": {
                        "color": "#18fcff",
                        "fontSize": 14
                    },
                    "position": [300, -8]
                }
            }
        }
    ],
    "dataZoom":[{
            "orient":"vertical",
            "type":"inside"
        },
        {
            "orient":"vertical",
            "type":"slider",
            "dataBackground":{
                "lineStyle":{
                    "color":"#3cefff",
                    "width":2
                }
            },
            "textStyle":{
                "color":"#fff",
                "fontSize":20
            }
        }]
}
option;
//        print_r(json_decode($option,true));
////        exit();

//        var_dump($option);
//        exit;
        $dataTable = M('testcount');
//        $seachpier = array('惠宁码头', '西坝码头', '扬子石化', '龙翔码头');

//        foreach ($seachpier as $key=>$value) {
        $option = json_decode($optionJson, true);
        $CountSql = "SELECT sum(weight)/1000 as totalweight,cargo FROM testcount where pier='{$pier}' and cargo!='' GROUP BY cargo ORDER BY totalweight desc";

        $CargoWeight = $dataTable->query($CountSql);


        //开始构成图表数据数组
        $cargoData = array();
        $weightData = array();
        $iconData = array();


        foreach ($CargoWeight as $key => $value) {
            //每页最多13个货物
            if ($key % 12 == 11) {

                $option["tooltip"] = (object)array();
                $option["xAxis"] = (object)array();
                $option['yAxis'][0]['data'] = $cargoData;
                $option["title"]['text'] = $pier . "货物汇总";


                foreach ($option['series'] as $key => $value) {
                    if ($key == 0) {
                        $option['series'][$key]['data'] = $iconData;
                    } elseif ($key == 1) {
                        $option['series'][$key]['data'] = $weightData;
                    }
                }
                $data[] = $option;
                //初始化设置
                $option = json_decode($optionJson, true);
                $cargoData = array();
                $weightData = array();
                $iconData = array();
            }
            $data_arr = array();
            if ($value['cargo'] == "") {
                /*$CargoWeight[$key]['cargo'] = "其他";
                $value['cargo'] = "其他";*/
                //如果货名为空则跳过
                continue;
            }
            if (mb_strlen($value['cargo'], 'utf8') > 7) {
                $value['cargo'] = mb_substr($value['cargo'], 0, 7, 'utf8') . '...';
            }
            $data_arr['value'] = (float)$value['totalweight'];
            $data_arr['symbol'] = "image://tpl/default/Index/Public/image/light.png";
            array_unshift($cargoData, $value['cargo']);
            array_unshift($weightData, $data_arr);
            array_unshift($iconData, array(
                'value' => 1,
                'symbol' => "image://tpl/default/Index/Public/image/dark.png"
            ));
            /*$cargoData[] = $value['cargo'];
            $weightData[] = $data_arr;
            $iconData[] = array(
                'value' => 1,
                'symbol' => "image://tpl/default/Index/Public/image/dark.png"
            );*/
        }


        $option["tooltip"] = (object)array();
        $option["xAxis"] = (object)array();
        $option['yAxis'][0]['data'] = $cargoData;
        $option["title"]['text'] = $pier . "货物汇总";


        foreach ($option['series'] as $key => $value) {
            if ($key == 0) {
                $option['series'][$key]['data'] = $iconData;
            } elseif ($key == 1) {
                $option['series'][$key]['data'] = $weightData;
            }
        }
        $data[] = $option;

        $top = ceil(count($data) / 4) * 30;
//        exit(print_r($cargoData));
//        $option['yAxis']['data'] = array();
//        $option['series']['data'] = array();

//        print_r($option);
//        exit();


//        $optionAsign = array(
//
//        );
        /*$this->assign("option", $data);
        $this->assign("top", $top);
        $this->display();*/
        exit(ajaxReturn(array('option' => $data, 'top' => $top)));
    }

    public function newGKdCargoCount($pier)
    {

        $optionJson = <<<option
{
    "title": {
        "show":true,
        "text": "港口货物比例汇总",
        "textStyle": {
            "color": "#fff",
            "fontSize": 18
        },
        "top":"10",
        "left":"40%"
    },
    "backgroundColor": "rgb(32,51,106)",
    "grid": {
        "containLabel": true
    },
    "tooltip": {
        "trigger": "item"
    },
    "xAxis": {
        "show": false,
        "splitLine": {
            "show": false,
            "lineStyle": {
                "type": "dashed",
                "color": "#3e86dd"
            }
        },
        "axisLine": {
            "show": false,
            "lineStyle": {
                "type": "dashed",
                "color": "#3e86dd"
            }
            
        },
        "axisLabel": {
            "show": false
        },
        "axisTick": {
            "show": false
        }
    },
    "yAxis": [
        {
            "type": "category",
            "inverse": false,
            "data": [
                
            ],
            "axisLine": {
                "show": false
            },
            "axisTick": {
                "show": false
            },
            "splitLine": {
                "show": false,
                "lineStyle": {
                    "type": "dashed",
                    "color": "#3e86dd"
                }
            },
            "axisLabel": {
                "margin": 0,
                "textStyle": {
                    "color": "#fff",
                    "fontSize": 14
                }
            }
        }
    ],
    "series": [
        {
            "type": "bar",
            "symbolSize": [
                45,
                20
            ],
            "data": [
                
            ],
            "barWidth": 20,
            "itemStyle":{
                "color":{
                    "image": "g_cellBarImg0_y",
                    "repeat": "repeat"
                }
            },
            "label": {
                "normal": {
                    "show": true,
                    "textStyle": {
                        "color": "#18fcff",
                        "fontSize": 14
                    },
                    "position": "right"
                }
            }
        }
    ]
}
option;
//        print_r(json_decode($option,true));
////        exit();

//        var_dump($option);
//        exit;
        $dataTable = M('testcount');
//        $seachpier = array('惠宁码头', '西坝码头', '扬子石化', '龙翔码头');

//        foreach ($seachpier as $key=>$value) {
        $option = json_decode($optionJson, true);
        $CountSql = "SELECT sum(weight)/1000 as totalweight,cargo FROM testcount where pier='{$pier}' and cargo!='' GROUP BY cargo ORDER BY totalweight desc";

        $CargoWeight = $dataTable->query($CountSql);


        //开始构成图表数据数组
        $cargoData = array();
        $weightData = array();
        $iconData = array();


        foreach ($CargoWeight as $key => $value) {
            //每页最多13个货物
            if ($key % 12 == 11) {

                $option["tooltip"] = (object)array();
                $option["xAxis"] = (object)array();
                $option['yAxis'][0]['data'] = $cargoData;
                $option["title"]['text'] = $pier . "货物汇总";
                $option['series'][0]['data'] = $weightData;

                $data[] = $option;
                //初始化设置
                $option = json_decode($optionJson, true);
                $cargoData = array();
                $weightData = array();
                $iconData = array();
            }
            $data_arr = array();
            if ($value['cargo'] == "") {
                /*$CargoWeight[$key]['cargo'] = "其他";
                $value['cargo'] = "其他";*/
                //如果货名为空则跳过
                continue;
            }

            if (mb_strlen($value['cargo'], 'utf8') > 7) {
                $value['cargo'] = mb_substr($value['cargo'], 0, 7, 'utf8') . '...';
            }

            array_unshift($cargoData, $value['cargo']);
            array_unshift($weightData, (float)$value['totalweight']);

            /*$cargoData[] = $value['cargo'];
            $weightData[] = $data_arr;
            $iconData[] = array(
                'value' => 1,
                'symbol' => "image://tpl/default/Index/Public/image/dark.png"
            );*/
        }


        $option["tooltip"] = (object)array();
        $option["xAxis"] = (object)array();
        $option['yAxis'][0]['data'] = $cargoData;
        $option["title"]['text'] = $pier . "货物汇总";
        $option['series'][0]['data'] = $weightData;

        $data[] = $option;

        $top = ceil(count($data) / 4) * 30;
//        exit(print_r($cargoData));
//        $option['yAxis']['data'] = array();
//        $option['series']['data'] = array();

//        print_r($option);
//        exit();


//        $optionAsign = array(
//
//        );
        /*$this->assign("option", $data);
        $this->assign("top", $top);
        $this->display();*/
        exit(ajaxReturn(array('option' => $data, 'top' => $top)));
    }


    public function NewGKdCargo()
    {

        /*
         * 天宇码头：119.0831932327,32.2136299597
         * 惠宁码头：118.8776496481,32.1713841944
         * 西坝码头：118.9021274516,32.1995042101
         * 杨巴码头：118.8605950000,32.2310160000
         * 龙翔码头：118.8741720000,32.2213790000
         * 四公司：118.8682800000,32.1665840000
         * 扬子石化：118.8445520000,32.2401730000
         */

//        $geoGobalData=array(
//            "天宇码头"=>array(119.0831932327,32.2136299597),
//            "惠宁码头"=>array(118.8776496481,32.1713841944),
//            "西坝码头"=>array(118.9021274516,32.1995042101),
//            "杨巴码头"=>array(118.8605950000,32.2310160000),
//            "龙翔码头"=>array(118.8741720000,32.2213790000),
//            "四公司"=>array(118.8682800000,32.1665840000),
//            "扬子石化"=>array(118.8445520000,32.2401730000),
//            "南化码头" => array(118.8751450000, 32.2209980000),
//        );

        $geoGobalData = array(
            "天宇码头" => array(118.8591932327, 32.1636299597),
            "惠宁码头" => array(118.9276496481, 32.3473841944),
            "西坝码头" => array(118.5221274516, 31.9995042101),
            "扬巴码头" => array(118.6605950000, 31.8110160000),
            "龙翔码头" => array(119.0841720000, 31.5513790000),
            "四公司" => array(118.7682800000, 32.3865840000),
            "扬子石化" => array(119.1045520000, 31.3401730000),
            "南化码头" => array(118.7951450000, 31.3209980000),
        );

        $dataTable = M('testcount');
        $CountSql = "SELECT 0 + CAST(sum(weight)/1000 as CHAR) as totalweight,pier FROM testcount GROUP BY pier ORDER BY totalweight desc";
        $CargoWeight = $dataTable->query($CountSql);
        //开始构成图表数据数组
        $Data = array();
        foreach ($CargoWeight as $key => $value) {
            $Data[$key] = array();
//            if ($value['cargo'] == "") {
//                $CargoWeight[$key]['cargo'] = "其他";
//                $value['cargo'] = "其他";
//            }
            $Data[$key]['name'] = $value['pier'];
            $Data[$key]['value'] = $value['totalweight'];
        }
        $this->assign("optionData", json_encode($Data, JSON_UNESCAPED_UNICODE));
        $this->assign("GobalData", json_encode($geoGobalData, JSON_UNESCAPED_UNICODE));
        $this->display();
    }


    public function GKCargoPst($pier)
    {
        $optionJson = <<<option
{
    "backgroundColor": "rgb(18,28,75)",
    "title": {
        "text": "...",
        "left": "center",
        "top": 20,
        "textStyle": {
            "color": "#ccc"
        }
    },

    "tooltip" : {
        "trigger": "item",
        "formatter": "{a} <br/>{b} : {c} ({d}%)"
    },

    "visualMap": {
        "show": false,
        "min": 80,
        "max": 600,
        "inRange": {
            "colorLightness": [0, 1]
        }
    },
    "series" : [
        {
            "name":"货物占比",
            "type":"pie",
            "radius" : "55%",
            "center": ["50%", "50%"],
            "data":[
            
            ],
            "roseType": "radius",
            "label": {
                "normal": {
                    "textStyle": {
                        "color": "rgba(255, 255, 255, 0.3)"
                    }
                }
            },
            "labelLine": {
                "normal": {
                    "lineStyle": {
                        "color": "rgba(255, 255, 255, 0.3)"
                    },
                    "smooth": 0.2,
                    "length": 10,
                    "length2": 20
                }
            },
            "itemStyle": {
                "normal": {
                    "color": "#c23531",
                    "shadowBlur": 200,
                    "shadowColor": "rgba(0, 0, 0, 0.5)"
                }
            },

            "animationType": "scale",
            "animationEasing": "elasticOut",
            "animationDelay": {}
        }
    ]
}
option;
        $dataTable = M('testcount');
//        $seachpier = array('惠宁码头', '西坝码头', '扬子石化', '龙翔码头');

//        foreach ($seachpier as $key=>$value) {
        $option = json_decode($optionJson, true);
        $CountSql = "SELECT sum(weight)/1000 as totalweight,cargo FROM testcount where pier='{$pier}' and cargo!='' GROUP BY cargo ORDER BY totalweight desc";

        $CargoWeight = $dataTable->query($CountSql);


        //开始构成图表数据数组
        $cargoData = array();
        $weightData = array();

        $max = 0;
        $min = 0;


        foreach ($CargoWeight as $key => $value) {
            //每页最多13个货物
            if ($key % 12 == 11) {

                $option["title"]['text'] = $pier . "货物占比";

                $option["visualMap"]['max'] = $max;
                $option["visualMap"]['min'] = $min;

                $option['series'][0]['data'] = $weightData;
                $option['series'][0]['animationDelay'] = (object)array();

                $data[] = $option;
                //初始化设置
                $option = json_decode($optionJson, true);
                $cargoData = array();
                $weightData = array();
                $max = 0;
                $min = 0;
            }

            if ($max == 0 || (float)$value['totalweight'] > $max) {
                $max = (float)$value['totalweight'];
            }

            if ($min == 0 || (float)$value['totalweight'] < $min) {
                $min = (float)$value['totalweight'];
            }

            $data_arr = array();
            if ($value['cargo'] == "") {
                /*$CargoWeight[$key]['cargo'] = "其他";
                $value['cargo'] = "其他";*/
                //如果货名为空则跳过
                continue;
            }
            if (mb_strlen($value['cargo'], 'utf8') > 7) {
                $value['cargo'] = mb_substr($value['cargo'], 0, 7, 'utf8') . '...';
            }

            $weightData[] = array("name" => $value['cargo'], "value" => (float)$value['totalweight']);
//            array_unshift($cargoData, $value['cargo']);
//            array_unshift($weightData, (float)$value['totalweight']);

            /*$cargoData[] = $value['cargo'];
            $weightData[] = $data_arr;
            $iconData[] = array(
                'value' => 1,
                'symbol' => "image://tpl/default/Index/Public/image/dark.png"
            );*/
        }


        $option["visualMap"]['max'] = $max;
        $option["visualMap"]['min'] = $min;
        $option["title"]['text'] = $pier . "货物占比";
        $option['series'][0]['data'] = $weightData;
        $option['series'][0]['animationDelay'] = (object)array();

        $data[] = $option;

        $top = ceil(count($data) / 4) * 30;
//        exit(print_r($cargoData));
//        $option['yAxis']['data'] = array();
//        $option['series']['data'] = array();

//        print_r($option);
//        exit();


//        $optionAsign = array(
//
//        );
        /*$this->assign("option", $data);
        $this->assign("top", $top);
        $this->display();*/
        exit(ajaxReturn(array('option' => $data, 'top' => $top)));
    }

    public function proGKdCargo()
    {
        $optionJson = <<<option
{
    "title" : {
        "show":false
    },
    "tooltip" : {
        "trigger": "item",
        "formatter": "{b} : {c} ({d}%)",
        "textStyle":{
            "fontSize":17
        }
    },
    "legend": {
        "show":false,
        "orient": "vertical",
        "top":"middle",
        "left": "left",
        "data": []
    },
    "series" : [
        {
            "name": "码头货物占比",
            "type": "pie",
            "avoidLabelOverlap": false,
            "radius":["65%", "45%"],
            "center": ["50%", "60%"],
            "label":{
                "normal": {
                    "show": false,
                    "position": "center"
                },
                "emphasis": {
                    "show": false,
                    "textStyle": {
                        "fontSize": "30",
                        "fontWeight": "bold",
                        "color":"#FFF"
                    }
                }
            },
            "data":[
            ],
            "labelLine": {
                "normal": {
                    "show": false
                }
            }
        }
    ]
}
option;

        $fff = <<<aaa
"series" : [
        {
            "name": "码头货物占比",
            "type": "pie",
            "radius" : "55%",
            "center": ["50%", "60%"],
            "label":{
                "show":false,
                "formatter":"{b}: {d}%"
            },
            "data":[
            ],
            "itemStyle": {
                "emphasis": {
                    "shadowBlur": 10,
                    "shadowOffsetX": 0,
                    "shadowColor": "rgba(0, 0, 0, 0.5)"
                }
            }
        }
    ]
aaa;


        $option = json_decode($optionJson, true);

        $dataTable = M('testcount');
        $CountSql = "SELECT 0 + CAST(sum(weight)/1000 as CHAR) as totalweight,pier FROM testcount GROUP BY pier ORDER BY totalweight desc";

        $CargoWeight = $dataTable->query($CountSql);
        //开始构成图表数据数组
        $Data = array();
        $cargoName = array();

        foreach ($CargoWeight as $key => $value) {
            $Data[$key] = array();
//            if ($value['cargo'] == "") {
//                $CargoWeight[$key]['cargo'] = "其他";
//                $value['cargo'] = "其他";
//            }
            $cargoName[] = $value['pier'];
            $Data[$key]['name'] = $value['pier'];
            $Data[$key]['value'] = $value['totalweight'];
        }
        $option['legend']['data'] = $cargoName;
        $option['series'][0]['data'] = $Data;
        $this->assign("optionData", json_encode($option, JSON_UNESCAPED_UNICODE));
        $this->display();
    }


    public function proGKdCargo1()
    {
        $this->display();
    }


    public function GKCargoMouth($pier, $cargo)
    {
        $optionJson = <<<option
{
    "xAxis": {
        "type": "category",
        "boundaryGap": false,
        "data": []
    },
    "yAxis": {
        "type": "value"
    },
    "series": [{
        "data": [],
        "type": "line",
        "areaStyle": {}
    }]
}
option;
        $dataTable = M('testcount');
//        $seachpier = array('惠宁码头', '西坝码头', '扬子石化', '龙翔码头');

//        foreach ($seachpier as $key=>$value) {
        $option = json_decode($optionJson, true);
        $CountSql = "SELECT 0 + CAST(sum(weight)/1000 as CHAR) as totalweight,DATE_FORMAT(datetime,'%Y-%m') as months FROM testcount where pier='{$pier}' and cargo='{$cargo}' GROUP BY cargo,months ORDER BY cargo,months,totalweight desc";

        $CargoWeight = $dataTable->query($CountSql);


        //开始构成图表数据数组
        $cargoData = array();
        $weightData = array();


        foreach ($CargoWeight as $key => $value) {

            $data_arr = array();

            $weightData[] = (float)$value['totalweight'];
            $cargoData[] = $value['months'];

        }

        $option['xAxis']['data'] = $cargoData;
        $option["title"]['text'] = $cargo . "货物占比";
        $option['series'][0]['data'] = $weightData;

        $data[] = $option;

        $top = ceil(count($data) / 4) * 30;
//        exit(print_r($cargoData));
//        $option['yAxis']['data'] = array();
//        $option['series']['data'] = array();

//        print_r($option);
//        exit();


//        $optionAsign = array(
//
//        );
        /*$this->assign("option", $data);
        $this->assign("top", $top);
        $this->display();*/
        exit(ajaxReturn(array('option' => $data, 'top' => $top)));
    }


    public function countGKCargoMouth()
    {
        $optionJson = <<<option
{
    "xAxis": {
        "type": "category",
        "boundaryGap": false,
        "data": []
    },
    "yAxis": {
        "type": "value"
    },
    "series": [{
        "data": [],
        "type": "line",
        "areaStyle": {}
    }]
}
option;
        $dataTable = M('testcount');
//        $seachpier = array('惠宁码头', '西坝码头', '扬子石化', '龙翔码头');

//        foreach ($seachpier as $key=>$value) {
        $option = json_decode($optionJson, true);
        $CountSql = "SELECT 0 + CAST(sum(weight)/1000 as CHAR) as totalweight,DATE_FORMAT(datetime,'%Y-%m') as months FROM testcount where pier='天宇码头' and cargo='磷酸氢二铵' GROUP BY cargo,months ORDER BY cargo,months,totalweight desc";

        $CargoWeight = $dataTable->query($CountSql);


        //开始构成图表数据数组
        $cargoData = array();
        $weightData = array();


        foreach ($CargoWeight as $key => $value) {

            $data_arr = array();

            $weightData[] = (float)$value['totalweight'];
            $cargoData[] = $value['months'];

        }

        $option['xAxis']['data'] = $cargoData;
        $option["title"]['text'] = "磷酸氢二铵货物占比";
        $option['series'][0]['data'] = $weightData;

        $data[] = $option;

        $top = ceil(count($data) / 4) * 30;
//        exit(print_r($cargoData));
//        $option['yAxis']['data'] = array();
//        $option['series']['data'] = array();

//        print_r($option);
//        exit();


//        $optionAsign = array(
//
//        );
        /*$this->assign("option", $data);
        $this->assign("top", $top);
        $this->display();*/
        $this->assign("option", json_encode($option));
        $this->display();
    }
}