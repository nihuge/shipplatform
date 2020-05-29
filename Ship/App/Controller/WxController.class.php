<?php

namespace APP\Controller;

use Common\Controller\AppBaseController;

/**
 * 微信登录操作,结合现有验证逻辑
 * 2019-07-24
 */
class WxController extends AppBaseController
{
    public function index()
    {
        return "success";
    }


    /**
     * 获取openid接口
     * @param string code 用户登录的JS_code
     */
    public function getSession()
    {
        $appid = "wxa81a46b43e2ea143";
        $appsecret = "262422fd46c412128ad5d665993c7203";

        if (I('post.code')) {
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . I('post.code') . "&grant_type=authorization_code";
            $result = curldo($url);
            if ($result['code'] == 1) {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => json_decode($result['content'],true),
                );
//                echo $result['content'];
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
        echo jsonreturn($res);
    }

    /**
     *获取并解密unionid
     * @param string code 用户登录的JS_code
     * @param string encryptedData 密文
     * @param string iv 初始化加密向量
     */
    public function decrypt()
    {
        $appid = "wx4f0fa7859f05b5fa";
        $appsecret = "b54662a17f8729c07e7d251714560159";

        if (I('post.code') and I('post.encryptedData') and I('post.iv')) {
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . I('post.code') . "&grant_type=authorization_code";
            $result = curldo($url);
            if ($result['code'] == 1) {
                $session_content = json_decode($result['content'], true);
                $session_key = $session_content['session_key'];
                $openid = $session_content['openid'];
                $encryptedData = I('post.encryptedData');
                $iv = I('post.iv');
                vendor('WxDeCode.WXBizDataCrypt');
                $pc = new \WXBizDataCrypt($appid, $session_key);
                $errCode = $pc->decryptData($encryptedData, $iv, $data);
                if ($errCode == 0) {
                    $decryptedData = json_decode($data,true);
                    $unionid = $decryptedData['unionid'];
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'decryptedData' => $decryptedData
                    );
                } else {
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER'],
                        'wx_error' => $errCode
                    );
                }
//                echo $result['content'];
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
        echo jsonreturn($res);
    }

    function test(){

    }
}