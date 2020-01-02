<?php

class httpDo
{
    public $httpError;

    public function get($url)
    {
        // 1. 初始化
        $ch = curl_init();
        // 2. 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HEADER, 0);

        //从证书中检查SSL加密算法
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 3. 获取内容
        $output = curl_exec($ch);
        if ($output === FALSE) {
            $this->httpError = curl_error($ch);
        }
        // 4. 释放curl句柄
        curl_close($ch);

        return $output;
    }

    public function post($url, $post_data)
    {
        // 1. 初始化
        $ch = curl_init();
        //设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, 0);

        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        //从证书中检查SSL加密算法
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        //执行命令
        $data = curl_exec($ch);
        if ($data === FALSE) {
            $this->httpError = curl_error($ch);
        }
        // 4. 释放curl句柄
        curl_close($ch);

        return $data;
    }

    public function auth($url, $post_data,array $header=array("Content-Type: application/json;charset=utf8"))
    {
        // 1. 初始化
        $ch = curl_init();
        //设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        //从证书中检查SSL加密算法
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        //执行命令
        $data = curl_exec($ch);

        if ($data !== FALSE) {
//            $data =curl_getinfo($ch);
        }else{
            $this->httpError = curl_error($ch);
        }
        // 4. 释放curl句柄
        curl_close($ch);

        return $data;
    }
}