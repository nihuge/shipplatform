<?php
namespace Index\Controller;
use Common\Controller\IndexBaseController;

// 公司管理
class FirmController extends IndexBaseController 
{
    // 定义数据表
    private $db;

    // 构造函数 实例化FirmModel表
    public function __construct(){
        parent::__construct();
        $this->db = new \Common\Model\FirmModel();
    }

    /**
     * 查看信息
     */
    public function msg(){
        $user = new \Common\Model\UserModel();
        if (IS_POST) {
            $data = I('post.');
            $logo = I('post.logo');
            $img = I('post.img');
            $image = I('post.image');

            unset($data['logo']);
            unset($data['photo']);

            unset($data['photo1']);
            unset($data['img']);

            unset($data['photo2']);
            unset($data['image']);
            // 判断提交的数据是否含有特殊字符
            $res = judgeTwoString($data);
            if ($res == false) {
                $this->error('数据不能含有特殊字符');
                exit;
            }
            if (!empty($logo)) {
                $data['logo'] = $logo;
            }
            if (!empty($img)) {
                $data['img'] = $img;
            }
            if (!empty($image)) {
                $data['image'] = $image;
            }
            // 到期时间默认一周
            $data['expire_time'] = strtotime("+10years",strtotime(date('Y-m-d H:i:s',time())));

            // 对数据进行验证
            if (!$this->db->create($data)){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $this->error($this->db->getError());
            }else{
                // 验证通过 可以进行其他数据操作
                $map = array(
                    'id'    =>   $data['id']
                    );
                unset($data['id']); // 删除id
                $res = $this->db->editData($map,$data);
                if ($res !== false) {
                    $_SESSION['user_info']['firmname'] = $data['firmname'];
                    $_SESSION['user_info']['logo'] = $data['logo'];
                    $this->success('修改成功');
                } else {
                    $this->error('修改失败');
                }
            }
        } else {
            // 获取用户所属公司信息
            $id=$_SESSION['user_info']['id'];
            $firmid = $user->getFieldById($id,'firmid');
            $data = $this->db
                    ->field('id,firmname,people,phone,firmtype,balance,service,creditline,location,content,logo,img,shehuicode,image')
                    ->where(array('id'=>$firmid))
                    ->find();
            //如果通过域名访问进来则去除最后一个开头的路径
            if (is_Domain()) {
                $data['logo'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $data['logo']);
                $data['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $data['img']);
                $data['image'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $data['image']);
            }

            $this->assign('data',$data);
            $this->display();            
        }
    }
}