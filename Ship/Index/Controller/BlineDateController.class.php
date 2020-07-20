<?php

namespace Index\Controller;

use Think\Controller;

/**
 * 相亲
 * */
class BlineDateController extends Controller
{
    public $ERROR_CODE_COMMON = array();         // 公共返回码
    public $ERROR_CODE_COMMON_ZH = array();      // 公共返回码中文描述
    public $ERROR_CODE_USER = array();           // 用户相关返回码
    public $ERROR_CODE_USER_ZH = array();        // 用户相关返回码中文描述
    public $ERROR_CODE_RESULT = array();         // 作业相关返回码
    public $ERROR_CODE_RESULT_ZH = array();      // 作业相关返回码中文描述

    /**
     * 初始化方法
     */
    public function _initialize()
    {
//        parent::_initialize();
        // 返回码配置
        $this->ERROR_CODE_COMMON = json_decode(error_code_common, true);
        $this->ERROR_CODE_COMMON_ZH = json_decode(error_code_common_zh, true);
        $this->ERROR_CODE_USER = json_decode(error_code_user, true);
        $this->ERROR_CODE_USER_ZH = json_decode(error_code_user_zh, true);
        $this->ERROR_CODE_RESULT = json_decode(error_code_result, true);
        $this->ERROR_CODE_RESULT_ZH = json_decode(error_code_result_zh, true);
    }


    /**
     * 获取用户的openid
     * @param $js_code
     * @param $mini_code
     * @return array
     */
    function getOpenId($js_code)
    {
//        $appid = "wx851036bc7675361a";
//        $appsecret = "7949a85b1fd62a1bd72deff70ec131aa";
        $appid = "wxf266125b4f5fd211";
        $appsecret = "fedeb38ed14112ffb0abe21d38aa5fe6";
        if ($js_code) {
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . $js_code . "&grant_type=authorization_code";
            $result = curldo($url);
            \Think\Log::record("\r\n \r\n [ trans!!! ] " . json_encode($result) . " \r\n \r\n ", "DEBUG", true);
            if ($result['code'] == 1) {
                $content = json_decode($result['content'], true);
                if ($content == false) {
                    $res = array(
                        'code' => 2,
                        'error' => $result['content'],
                    );
                } else {
                    if (isset($content['errcode'])) {
                        $res = array(
                            'code' => $content['errcode'],
                            'error' => $content['errmsg'],
                        );
                    } else {
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                            'content' => json_decode($result['content'], true),
                        );
                    }
                }
            } else {
                $res = array(
                    'code' => $result['code'],
                    'error' => $result['error'],
                );
            }
        } else {
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        return $res;
    }


    /**
     * 表单提交
     */
    public function sub_data()
    {
        if (I('post.name') and I('post.age') and I('post.code') and intval(I('post.sex')) > 0 and intval(I('post.sex')) < 3 and I('post.phone') and I('post.constellation') and I('post.hobby') and I('post.specialty')) {
            $name = I('post.name');
            $age = intval(I('post.age'));
            $sex = intval(I('post.sex'));
            $code = I('post.code');

            if ($age < 18 || $age > 64) {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "年龄错误"
                );
                exit(jsonreturn($res));
            }

            if (mb_strlen($name) > 4) {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "名字太长啦，我记不住啊"
                );
                exit(jsonreturn($res));
            }

            if(mb_strlen(I('post.phone'))<8 or mb_strlen(I('post.phone'))>16){
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "手机号码格式不正确哦"
                );
                exit(jsonreturn($res));
            }
//
//            if(mb_strlen(I('post.id_number'))>18){
//                $res = array(
//                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
//                    'error' => "身份证号码不正确"
//                );
//                exit(jsonreturn($res));
//            }
//
            if(mb_strlen(I('post.constellation'))>3){
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "这星座不对吧？"
                );
                exit(jsonreturn($res));
            }

            if(mb_strlen(I('post.hobby'))>120){
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "爱好太多了，记不过来啦"
                );
                exit(jsonreturn($res));
            }

            if(mb_strlen(I('post.specialty'))>120){
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "特长太多了，记不过来啦"
                );
                exit(jsonreturn($res));
            }

            S(array('type' => 'file', 'expire' => 3600 * 24)); //缓存一天
            $open_id_msg = $this->getOpenId($code);

            if ($open_id_msg['code'] == 1) {
                $open_id = $open_id_msg['content']['openid'];
            } else {
                $res = array(
                    'code' => $this->ERROR_CODE_USER['USER_IMEI_ERROR'],
                    'error' => "信息错误，不要点太快导致多次报名哦"
                );
                exit(jsonreturn($res));
            }

//            S($open_id, null);

            if (S($open_id) !== false) {
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['IS_REPEAT'],
                    'error' => "您已经报名成功啦~，不用重复报名"
                );
                exit(jsonreturn($res));
            }

            //初始化文件上传类
            vendor("Nx.FileUpload");
            $Upload = new \FileUpload();
            $file_info = $Upload->getFiles();
            if (count($file_info) > 0) {
                //大小限制50M，检测是否是图片，类型限制图片
                $res = $Upload->uploadFile($file_info[0], './Upload/blind');
                if ($res['mes'] == "上传成功") {
                    //将上传的图片路径放入数据库
                    $path = $res['dest'];
                    if ($sex == 1) {
                        $form_db = M("man");
                    } elseif ($sex == 2) {
                        $form_db = M("woman");
                    }

                    $data = array(
                        'name' => $name,
                        'age' => $age,
                        'avatar' => $path,
                        'phone' => I('post.phone'),
                        'constellation' => I('post.constellation'),
                        'hobby' => I('post.hobby'),
                        'specialty' => I('post.specialty')
                    );

                    M()->startTrans();
                    if (!$form_db->create($data)) {
                        // 如果创建失败 表示验证没有通过 输出错误提示信息
                        //数据库连接错误   3
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                            'error' => $form_db->getError()
                        );
                    } else {
                        try {
                            //添加数据
                            $result = $form_db->add($data);
                        } catch (\Exception $e) {
                            //回档
                            M()->rollback();
                            //数据库错误   3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                'error' => $e->getMessage()
                            );
                            exit(jsonreturn($res));
                        }

                        if ($result === false) {
                            //回档
                            M()->rollback();
                            //数据库错误   3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                'error' => $form_db->getMessage()
                            );
                            exit(jsonreturn($res));
                        } else {
                            //提交
                            M()->commit();
                            S($open_id, "1");//记录openid，防止重复提交
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                            );
                        }
                    }
                } else {
                    //上传图片失败，返回报错原因 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'error' => $res['mes'],
                    );
                }
            } else {
                //上传图片失败，因为没有图片上传 9
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                    'error' => "需要上传一个图片"
                );
            }
            exit(jsonreturn($res));
        }
    }

    /**
     * 表单提交
     */
    public function test_sub_data()
    {
        if (I('post.name') and I('post.age') and intval(I('post.sex')) > 0 and intval(I('post.sex')) < 3) {
            $name = I('post.name');
            $age = intval(I('post.age'));
            $sex = intval(I('post.sex'));

            if ($age < 18 || $age > 64) {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "年龄错误"
                );
                exit(jsonreturn($res));
            }

            if (mb_strlen($name) > 4) {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                    'error' => "名字太长啦，我记不住啊"
                );
                exit(jsonreturn($res));
            }

            //初始化文件上传类
            if ($sex == 1) {
                $form_db = M("man");
                $path = "./Upload/blind/man.jpg";
            } elseif ($sex == 2) {
                $form_db = M("woman");
                $path = "./Upload/blind/woman.jpg";
            }

            $data = array(
                'name' => $name,
                'age' => $age,
                'avatar' => $path,
            );

            M()->startTrans();
            if (!$form_db->create($data)) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                // $this->error($statistics->getError());
                //数据库连接错误   3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                    'error' => $form_db->getError()
                );
            } else {
                try {
                    //添加数据
                    $result = $form_db->add($data);
                } catch (\Exception $e) {
                    //回档
                    M()->rollback();
                    //数据库错误   3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                        'error' => $e->getMessage()
                    );
                    exit(jsonreturn($res));
                }

                if ($result === false) {
                    //回档
                    M()->rollback();
                    //数据库错误   3
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                        'error' => $form_db->getMessage()
                    );
                    exit(jsonreturn($res));
                } else {
                    //提交
                    M()->commit();
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                    );
                }
            }
            exit(jsonreturn($res));
        }
    }


    /**
     * 获取参赛人数
     */
    public function get_list()
    {
        $man = M('man');
        $woman = M('woman');
        $woman_list = $woman->where('1=1')->select();
        $man_list = $man->where('1=1')->select();
        $data = array(
            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
            'man' => $man_list,
            'man_num' => count($man_list),
            'woman' => $woman_list,
            'woman_num' => count($woman_list)
        );
        exit(jsonreturn($data));
    }

    /**
     * 随机匹配一组
     */
    public function random_match()
    {
        $start_time = microtime(1);
        $mod = rand(1, 2);
        $man = M('man');
        $woman = M('woman');
        if ($mod == 1) {
            $man_adjust = $man
                ->where('1=1')
                ->order("age desc")
                ->limit(3)
                ->select();

            $man_age = end($man_adjust);

            $woman_info = $woman
                ->where('age<=' . (intval($man_age['age']) + 3))
                ->order('selected asc,RAND()')
                ->find();
            $man_map1 = array(
                '_string' => "age>=".(intval($woman_info['age'])-3),
            );
            $man_info = $man
                ->where($man_map1)
                ->order('selected asc,RAND()')
                ->limit(3)
                ->select();

            $ids = array();
            foreach ($man_info as $value) {
                array_push($ids, $value['id']);
            }

            $woman->where(array('id' => $woman_info['id']))->setInc('selected', 1);
            if (count($man_info) >= 1) {
                $man->where(array('id' => array('in', $ids)))->setInc('selected', 1);
            }

            $woman_info['match_list'] = $man_info;
            $data = array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                'info' => $woman_info,
                'mod' => 1,
                'time' => number_format(microtime(1) - $start_time, 6),
            );
        } else {
            $woman_adjust = $woman
                ->where('1=1')
                ->order("age asc")
                ->limit(3)
                ->select();
            $woman_age = end($woman_adjust);

            $man_info = $man
                ->where('age>=' . (intval($woman_age['age']) - 3))
                ->order('selected asc,RAND()')
                ->find();
            $woman_map1 = array(
                '_string' => "age<=" . (intval($man_info['age'])+3),
            );
            $woman_info = $woman
                ->where($woman_map1)
                ->order('selected asc,RAND()')
                ->limit(3)
                ->select();

            $ids = array();
            foreach ($woman_info as $value) {
                array_push($ids, $value['id']);
            }

            $man->where(array('id' => $man_info['id']))->setInc('selected', 1);
            if (count($woman_info) >= 1) {
                $woman->where(array('id' => array('in', $ids)))->setInc('selected', 1);
            }

            $man_info['match_list'] = $woman_info;
            $data = array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                'info' => $man_info,
                'mod' => 2,
                'time' => number_format(microtime(1) - $start_time, 6),
            );
        }


        exit(jsonreturn($data));
    }
}