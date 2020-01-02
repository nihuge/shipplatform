<?php
require_once 'cache/Cache.php';
require_once 'httpDo.php';

class HwToken
{
    public $token;
    public $hwConfig;
    public $url = "/v3/auth/tokens";//token获取接口地址


    public function getHwToken()
    {
        $this->hwConfig = require_once 'config.php';
        $result_arr = array();
        //初始化类
        $cache = new Cache(7000, 'runtime/cache/');
        //获取全局token
        $this->token = $cache->get('global_access_token');
        if (!$this->token) {
            $httpDo = new httpDo();
            $url = "https://" . $this->hwConfig['endPoint'] . $this->url;
            $data = array(
                "auth" => array(
                    "identity" => array(
                        "methods" => array("password"),
                        "password" => array(
                            "user" => array(
                                "name" => $this->hwConfig['userName'],
                                "password" => $this->hwConfig['password'],
                                "domain" => array(
                                    "name" => $this->hwConfig['userName']
                                )
                            )
                        )
                    )
                )
            );
            $result = $httpDo->auth($url, json_encode($data));
            if ($result === false) {
                $result_arr['error'] = '500';
                $result_arr['msg'] = $httpDo->httpError;
            } else {
                $result = json_decode($result, true);
                if (!empty($result['errcode'])) {
                    $result_arr['error'] = $result['errcode'];
                    $result_arr['msg'] = $result['errmsg'];
                } else {
                    $this->token = $result['access_token'];
                    if ($cache->put('global_access_token', $this->token)) {
                        $result_arr['error'] = 0;
                        $result_arr['msg'] = $this->token;
                    } else {
                        $result_arr['error'] = '501';
                        $result_arr['msg'] = '无法创建缓存文件';
                    }
                }
            }
        } else {
            $result_arr['error'] = 0;
            $result_arr['msg'] = $this->token;
        }
        return $result_arr;
    }
}