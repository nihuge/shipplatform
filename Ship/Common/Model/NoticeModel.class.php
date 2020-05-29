<?php

namespace Common\Model;

use Common\Model\BaseModel;

/**
 * 通知Model
 * @author nihuge
 * */
class NoticeModel extends BaseModel
{
    /**
     * 自动验证
     */
    protected $_validate = array(
        array('title', '1,23', '标题长度不能超过23个字', 0, 'length'),//存在即验证 长度不能超过4个字符
        array('msg', '1,800', '信息正文不能超过800个字', 0, 'length'),//存在即验证 长度不能超过4个字符
        array('mini_program', '1,10', '小程序代码不能超过10个字符', 0, 'length'),//存在即验证 长度不能超过4个字符
        array('uid', 'require', '接收人ID不能为空', self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('title', 'require', '标题不能为空', self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('msg', 'require', '信息主体不能为空', self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('mini_program', 'require', '小程序代码不能为空', self::EXISTS_VALIDATE),//存在即验证 不能为空
    );

    /**
     * 模型create时自动处理，加入创建时间
     * @var array
     */
    protected $_auto = array(
        array('create_date', 'time', 1, 'function'),  // 对create_date字段在新增的时候写入当前时间戳
    );

    /**
     * 发送站内信
     * @param int $uid
     * @param string $title 站内信标题，最好不要超过20位，否则无法发送订阅消息
     * @param string $msg 站内信的详细信息
     * @param string $mini_program 属于哪个小程序
     * @param string $openid 如果存在，发送订阅消息给微信用户
     * @param string $other 审核结果，5个字符以内
     * @return array
     */
    public function sendNotice($uid, $title, $msg, $mini_program, $openid = "",$other ="")
    {
        $data = array(
            'title' => $title,
            'msg' => $msg,
            'uid' => $uid,
            'mini_program' => $mini_program,
        );
        if ($this->create($data)) {
            $result = $this->add();
            if ($result !== false) {
                // 如果传输了openid和token，则调用微信的订阅通知功能，通知到用户
                if ($openid) {
                    $wx_info = C("APP_INFO." . $mini_program);
                    if ($wx_info['APPID']) {
                        $curl_result = curldo("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $wx_info['APPID'] . "&secret=" . $wx_info['APPSECRET']);
                        if ($curl_result['code'] == 1) {
                            $wx_res = json_decode($curl_result['content'], true);
                            if ($wx_res['errcode'] == 0) {
                                $access_token = $wx_res['access_token'];
                                $user = new \Common\Model\UserModel();
                                $post_data = array(
                                    'access_token' => $access_token,
                                    'touser' => $openid,
                                    'template_id' => $wx_info['TEMPLATE_ID'],
                                    'data' => array(
                                        'thing1'=>array(
                                            'value'=>"复核结果通知"
                                        ),
                                        'phrase2'=>array(
                                            'value'=>$other
                                        ),
                                        'thing3'=>array(
                                            'value'=>$title
                                        ),
                                        'thing4'=>array(
                                            'value'=>$user->getFieldById($uid,"username")
                                        ),
                                    ),
                                );
                                //发送通知，不管对错，不处理
                                @curldo("https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=" . $access_token, 1, json_encode($post_data,JSON_UNESCAPED_UNICODE));
                            }
                        }
                    }
                }

                //成功
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'notice_id' => $result
                );
            } else {
                //数据库错误 3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                    'error' => $this->getDbError()
                );
            }
        } else {
            //数据格式有误 7
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['ERROR_DATA'],
                'error' => $this->getError()
            );
        }
        return $res;
    }

    /**
     * 获取站内信的列表
     * @param $uid
     * @param $statue
     * @return mixed
     */
    public function noticeList($uid, $statue)
    {
        $where = array(
            'uid' => $uid,
            'statue' => array('ELT', $statue)
        );
        $notice_list = $this
            ->where($where)
            ->select();
        $wx_info = C('WX_CONFIG.APP_INFO');
        //处理点击站内信时跳转小程序的APPID变量
        foreach ($notice_list as $key => $value) {
            $notice_list[$key]['msg'] = substr_format($value['msg'], 20);//正文最多显示20个字
            $notice_list[$key]['app_name'] = $wx_info[$value['mini_program']]['APPNAME'];//显示APP名字
        }
        return array('code' => $this->ERROR_CODE_COMMON['SUCCESS'], 'list' => $notice_list);
    }

    /**
     * 查看有多少条未读通知
     * @param $uid
     * @return mixed
     */
    public function noticeCount($uid)
    {
        $where = array(
            'uid' => $uid,
            'statue' => 1 //状态1为未读的状态
        );
        $notice_count = $this
            ->where($where)
            ->count();
        return $notice_count;
    }


    /**
     * 查看通知详情
     * @param $notice_id
     * @return mixed
     */
    public function noticeMsg($notice_id)
    {
        $where = array(
            'id' => $notice_id,
        );
        $notice_msg = $this
            ->where($where)
            ->find();
        $wx_info = C('WX_CONFIG.APP_INFO');
        //匹配需要跳转的APPID
        $notice_msg['jump_appid'] = $wx_info[$notice_msg['mini_program']]['APPID'];

        //自动将信息标为已读
        $data = array(
            'status' => 1
        );
        $this->editData($where, $data);

        return $notice_msg;
    }

    /**
     * 一键已读
     * @param $uid
     * @return mixed
     */
    public function noticeReaded($uid)
    {
        $where = array(
            'uid' => intval($uid),
        );

        //将信息标为已读
        $data = array(
            'status' => 2
        );

        $result = $this->editData($where, $data);
        if ($result === false) {
            //成功
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['SUCCESS']
            );
        } else {
            //数据库错误 3
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                'error' => $this->getDbError()
            );
        }
        return $res;
    }
}