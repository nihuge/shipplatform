<?php
/**
 * 单文件、多个单文件、多文件上传函数的封装类
 * @package     tools_class
 */


class FileUpload
{
    /**
     * 构建上传文件信息
     * @return array 文件信息
     * */
    function getFiles()
    {
        $i = 0;
        $files = array();
        foreach ($_FILES as $file) {
            //因为这时$_FILES是个三维数组，并且上传单文件或多文件时，数组的第一维的类型不同，这样就可以拿来判断上传的是单文件还是多文件
            if (is_string($file['name'])) {
                if (!empty($file['name'])) {
                    //如果是单文件
                    $files[$i] = $file;
                }
                $i++;
            } elseif (is_array($file['name'])) {
                //如果是多文件
                foreach ($file['name'] as $key => $val) {
                    // 没有选择文件直接跳过
                    if (empty($file['name'][$key])) {
                        continue;
                    }
                    $files[$i]['name'] = $file['name'][$key];
                    $files[$i]['type'] = $file['type'][$key];
                    $files[$i]['tmp_name'] = $file['tmp_name'][$key];
                    $files[$i]['error'] = $file['error'][$key];
                    $files[$i]['size'] = $file['size'][$key];
                    $i++;
                }
            }
        }
        return $files;
    }

    /**
     * 针对于单文件、多个单文件、多文件的上传
     * @param array fileInfo 文件信息
     * @param string path 存储路径,默认上传保存的文件夹为本地的'uploads'文件夹
     * @param flag:true   检查上传的文件是否为真实的图片
     * @param array allowExt 默认允许上传的文件只为图片类型，并且只有这些图片类型:array('jpeg','jpg','png','gif')
     * @param int 允许上传文件的大小最大为2M
     * @param int 是否取文件原始名称
     * @return
     * */
    function uploadFile($fileInfo, $path = './Upload', $flag = true, $allowExt = array('jpeg', 'jpg', 'png', 'gif'), $maxSize = 209715200, $is_chong = '')
    {
        $res = array();
        //判断错误号
        if ($fileInfo['error'] === UPLOAD_ERR_OK) {
            //检测上传文件的大小
            if ($fileInfo['size'] > $maxSize) {
                $res['mes'] = $fileInfo['name'] . '上传文件过大';
            }

            $ext = array_pop(explode('.', $fileInfo['name']));

            //检测上传文件的文件类型
            if (!in_array($ext, $allowExt)) {
                $res['mes'] = $fileInfo['name'] . '非法文件类型';
            }
            //检测是否是真实的图片类型
            if ($flag) {
                if (!getimagesize($fileInfo['tmp_name'])) {
                    $res['mes'] = $fileInfo['name'] . '不是真实图片类型';
                }
            }
            //检测文件是否是通过HTTP POST上传上来的
            if (!is_uploaded_file($fileInfo['tmp_name'])) {
                $res['mes'] = $fileInfo['name'] . '文件不是通过HTTP POST方式上传上来的';
            }
            if (!empty($res)) {
                return $res;
                die;
            } //如果要不显示错误信息的话，用if( @$res ) return $res;

            // $path = $path.'/'.date('H-m-d');
            //如果没有这个文件夹，那么就创建一
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
                chmod($path, 0777);
            }


            if (empty($is_chong)) {
                //新文件名唯一
                $uniName = uniqid() . rand(100, 999);
            } else {
                $filenems = explode('.', $fileInfo['name']);
                array_pop($filenems);
                $uniName = implode('.', $filenems);
            }


            $destination = $path . '/' . $uniName . '.' . $ext;
            $filname = $uniName . '.' . $ext;
            // 判断文件是否存在，存在重命名
            if (file_exists(iconv('UTF-8', 'GB2312', $destination))) {
                $uniName = $uniName . '_' . rand(1, 100);
                $destination = $path . '/' . $uniName . '.' . $ext;
            }

            $res['mes'] = '上传成功';
            $res['dest'] = $destination;
            $res['name'] = $filname;

            // @符号是为了不让客户看到错误信，也可以删除
            if (!@move_uploaded_file($fileInfo['tmp_name'], $destination)) {
                // if(!@move_uploaded_file($fileInfo['tmp_name'],iconv("gb2312","UTF-8",$destination))){
                $res['mes'] = $fileInfo['name'] . '文件移动失败';
            }
            return $res;
        } else {
            //匹配错误信息
            //注意！错误信息没有5
            switch ($fileInfo['error']) {
                case 1:
                    $res['mes'] = '上传文件超过了PHP配置文件中upload_max_filesize选项的值';
                    break;
                case 2:
                    $res['mes'] = '超过了HTML表单MAX_FILE_SIZE限制的大小';
                    break;
                case 3:
                    $res['mes'] = '文件部分被上传';
                    break;
                case 4:
                    $res['mes'] = '没有选择上传文件';
                    break;
                case 6:
                    $res['mes'] = '没有找到临时目录';
                    break;
                case 7:
                    $res['mes'] = '文件写入失败';
                    break;
                case 8:
                    $res['mes'] = '上传的文件被PHP扩展程序中断';
                    break;
                default:
                    $res['mes'] = '上传文件对象错误';
                    break;
            }
            return $res;
        }
    }
}