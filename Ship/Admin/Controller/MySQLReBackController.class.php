<?php
/*
 * 数据库备份/还原/删除/下载
 * */
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

class MySQLReBackController extends AdminBaseController
{

    public function index() {
        $DataDir = "databak/";
        mkdir($DataDir);
        if (!empty($_GET['Action'])) {
            // P();die;
            $files = $_GET['File'].'.sql';
            // 数据库信息
            $config = array(
                'host' => C('DB_HOST'),
                'port' => C('DB_PORT'),
                'userName' => C('DB_USER'),
                'userPassword' => C('DB_PWD'),
                'dbprefix' => C('DB_PREFIX'),
                'charset' => 'UTF8',
                'path' => $DataDir,
                'isCompress' => 0, //是否开启gzip压缩
                'isDownload' => 0
            );
            // 实例化备份还原类
            $mr = new \Org\Nx\MySQLReback($config);
            $mr->setDBName(C('DB_NAME'));
            if ($_GET['Action'] == 'backup') {
                $mr->backup();
                echo "<script>alert('数据库备份成功！');document.location.href='" . U("MySQLReBack/index") . "'</script>";

            } elseif ($_GET['Action'] == 'RL') {
                
                $mr->recover($files);
                echo "<script>alert('数据库还原成功！');document.location.href='" . U("MySQLReBack/index") . "'</script>";

            } elseif ($_GET['Action'] == 'Del') {
                if (@unlink($DataDir . $files)) {
                    echo "<script>document.location.href='" . U("MySQLReBack/index") . "'</script>";
                } else {
                    $this->error('删除失败！');
                }
            }
            if ($_GET['Action'] == 'download') {

                function DownloadFile($fileName) {
                    ob_end_clean();
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Length: ' . filesize($fileName));
                    header('Content-Disposition: attachment; filename=' . basename($fileName));
                    readfile($fileName);
                }
                DownloadFile($DataDir . $files);
                exit();
            }
        }

        $lists = $this->MyScandir('databak/');
        $this->assign("datadir",$DataDir);
        $this->assign("lists", $lists);
        $this->display();
    }

    private function MyScandir($FilePath = './', $Order = 0) {
        $FilePath = opendir($FilePath);
        while (false !== ($filename = readdir($FilePath))) {
            $FileAndFolderAyy[] = $filename;
        }
        $Order == 0 ? sort($FileAndFolderAyy) : rsort($FileAndFolderAyy);
        return $FileAndFolderAyy;
    }

}

?>