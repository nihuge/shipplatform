<?php
file_put_contents("data_log.txt",date('Y-m-d H:i:s')."传输的服务信息\r\n",FILE_APPEND);
    file_put_contents("data_log.txt",json_encode($_SERVER),FILE_APPEND);
file_put_contents("data_log.txt","\r\n".date('Y-m-d H:i:s')."传输的参数信息\r\n",FILE_APPEND);
file_put_contents("data_log.txt",json_encode($_REQUEST),FILE_APPEND);
