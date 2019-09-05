<?php
function read_all_dir($dir)
{
    $num = 0;
    $handle = opendir($dir);//读资源
    if ($handle) {
        $file = readdir($handle);
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $cur_path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($cur_path)) {//判断是否为目录，递归读取文件
                    $num += read_all_dir($cur_path);
                } else {
                    if (time() - filemtime($cur_path) > 432000) {//如果此文件创建时间超过了5天则删除
                        @unlink($cur_path);
                        $num++;
                    }
//                    else{
//
//                    }
//                    $result['file'][] = $cur_path;
                }
            }
        }
        closedir($handle);
    }
    return $num;
}

//$result = read_all_dir("Upload\\firm");
//echo json_encode($result);
//echo $result;

//    echo $file;
function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
    if (!mkdirs(dirname($dir), $mode)) return FALSE;
    return @mkdir($dir, $mode);
}

var_dump(mkdirs('Upload\\firm'));