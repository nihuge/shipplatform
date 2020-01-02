<?php

namespace Index\Controller;

use Common\Controller\IndexBaseController;


class SearchController extends IndexBaseController
{
    // 定义数据表
    private $db;
    private $udb;
    private $rdb;
    private $shrdb;
    private $shipdb;
    private $sh_shipdb;

    // 构造函数 实例化FirmModel表
    public function __construct()
    {
        parent::__construct();
        $this->db = new \Common\Model\FirmModel();
        $this->udb = new \Common\Model\UserModel();
        $this->rdb = new \Common\Model\WorkModel();
        $this->shrdb = new \Common\Model\ShResultModel();
        $this->shipdb = new \Common\Model\ShipFormModel();
        $this->sh_shipdb = new \Common\Model\ShShipModel();
    }

    /**
     * 首页
     */
    public function index()
    {
        // 获取所有的检验公司
        $jian = $this->db
            ->field('id,firmname')
            ->where(array('firmtype' => '1', 'del_sign' => 1))
            ->select();
        // 获取所有的船舶公司
        $chuan = $this->db
            ->field('id,firmname')
            ->where(array('firmtype' => '2', 'del_sign' => 1))
            ->select();

        // 获取所有的船舶
        $shiplist = $this->shipdb->field("shipname")->where(array('del_sign' => 1))->select();
        // 获取所有的散货船舶
        $shshiplist = $this->sh_shipdb->field("shipname")->where(array('del_sign' => 1))->select();

        $assign = array(
            'shiplist' => $shiplist,
            'sh_shiplist' => $shshiplist,
            'jian' => $jian,
            'chuan' => $chuan
        );

        $this->assign($assign);
        $this->display();
    }

    /**
     * 检验公司列表
     */
    public function jian()
    {
        $where = array('f.firmtype' => '1', 'del_sign' => 1);
        if (I('get.firmname')) {
            $where['f.firmname'] = I('get.firmname');
        }
        $count = $this->db
            ->alias('f')
            ->join('left join firm_historical_sum s on s.firmid = f.id')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 20);

        $jian = $this->db
            ->field('f.id,f.firmname,s.grade,s.grade_num,s.weight,s.num')
            ->alias('f')
            ->join('left join firm_historical_sum s on s.firmid = f.id')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($jian as $key => $value) {
            // 求评分平均分
            $jian[$key]['pin'] = round($value['grade'] / $value['grade_num'], 1);
        }
        $assign = array(
            'data' => $jian,
            'page' => $page->show()
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 船舶公司列表
     */
    public function chuan()
    {
        $where = array('f.firmtype' => '2', 'del_sign' => 1);
        if (I('get.firmname')) {
            $where['f.firmname'] = I('get.firmname');
        }
        $count = $this->db
            ->alias('f')
            ->join('left join firm_historical_sum s on s.firmid = f.id')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 20);

        $jian = $this->db
            ->field('f.id,f.firmname,s.grade,s.grade_num,s.weight,s.num')
            ->alias('f')
            ->join('left join firm_historical_sum s on s.firmid = f.id')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($jian as $key => $value) {
            // 求评分平均分
            $jian[$key]['pin'] = round($value['grade'] / $value['grade_num'], 1);
        }
        $assign = array(
            'data' => $jian,
            'page' => $page->show()
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 船舶列表
     */
    public function ship()
    {
        $where = array('1', 'del_sign' => 1);
        if (I('get.shipname')) {
            $where['s.shipname'] = I('get.shipname');
        }
        $count = $this->shipdb
            ->alias('s')
            ->join('left join ship_historical_sum h on h.shipid = s.id')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 20);

        $jian = $this->shipdb
            ->field('s.id,s.shipname,s.type,s.weight as sweight,h.grade,h.grade_num,h.weight,h.num')
            ->alias('s')
            ->join('left join ship_historical_sum h on h.shipid = s.id')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($jian as $key => $value) {
            // 求评分平均分
            $jian[$key]['pin'] = round($value['grade'] / $value['grade_num'], 1);
        }
        $assign = array(
            'data' => $jian,
            'page' => $page->show()
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 船舶列表
     */
    public function sh_ship()
    {
        $where = array('1', 'del_sign' => 1);
        if (I('get.shipname')) {
            $where['shipname'] = I('get.shipname');
        }

        $count = $this->sh_shipdb
            ->where($where)
            ->count();

        // 分页
        $page = new \Org\Nx\Page($count, 20);

        $jian = $this->sh_shipdb
            ->field('id,shipname,weight,goodsname,make,lbp')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();

        $assign = array(
            'data' => $jian,
            'page' => $page->show()
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * ajax获取船舶列表
     */
    public function getShip()
    {
        if (IS_AJAX) {
            $page = I('post.pageNum');//当前页
            $where = '1 and del_sign=1';
            if ($_POST['strin'] != '') {
                $string = substr($_POST['strin'], 0, -1);    //字符串截取
                // $arr = explode(',', $string);
                $data = array();
                // foreach ($arr as $key => $value) {
                $v = explode(':', $string);
                $data[$v[0]] = $v[1];
                // }
                if (isset($data['shipname'])) {
                    $shipname = $data['shipname'];
                    $where .= " and s.shipname='$shipname'";
                }
            }
            $total = $this->shipdb
                ->alias('s')
                ->join('left join ship_historical_sum h on h.shipid = s.id')
                ->where($where)
                ->count();

            $pageSize = 10; //每页显示数
            $totalPage = ceil($total / $pageSize); //总页数
            $startPage = $page * $pageSize; //开始记录

            $list = $this->shipdb
                ->field('s.id,s.shipname,s.type,s.weight as sweight,h.grade,h.grade_num,h.weight,h.num')
                ->alias('s')
                ->join('left join ship_historical_sum h on h.shipid = s.id')
                ->where($where)
                ->limit($startPage, $pageSize)
                ->select();
            $num = 1;
            foreach ($list as $key => $value) {
                $pin = round($value['grade'] / $value['grade_num'], 1);
                // 求评分平均分
                $list[$key]['pin'] = $pin;
                $list[$key]['nn'] = $num;
                $num++;
            }
            //构造数组
            $arr['total'] = $total;
            $arr['pageSize'] = $pageSize;
            $arr['totalPage'] = $totalPage;

            $arr['list'] = $list;
            $this->ajaxReturn($arr);
        }
    }


    /**
     * ajax获取散货船舶列表
     */
    public function getShShip()
    {
        if (IS_AJAX) {
            $page = I('post.pageNum');//当前页
            $where = '1 and del_sign=1';
            if ($_POST['strin'] != '') {
                $string = substr($_POST['strin'], 0, -1);    //字符串截取
                $data = array();
                $v = explode(':', $string);
                $data[$v[0]] = $v[1];
                // }
                if (isset($data['shipname'])) {
                    $shipname = $data['shipname'];
                    $where .= " and shipname='$shipname'";
                }
            }
            $total = $this->sh_shipdb
                ->where($where)
                ->count();

            $pageSize = 10; //每页显示数
            $totalPage = ceil($total / $pageSize); //总页数
            $startPage = $page * $pageSize; //开始记录

            $list = $this->sh_shipdb
                ->field('id,shipname,goodsname,weight,lbp,make')
                ->where($where)
                ->limit($startPage, $pageSize)
                ->select();

            //构造数组
            $arr['total'] = $total;
            $arr['pageSize'] = $pageSize;
            $arr['totalPage'] = $totalPage;

            $arr['list'] = $list;
            $this->ajaxReturn($arr);
        }
    }

    /**
     * 检验公司详情
     */
    public function jianmsg()
    {
        $firmid = I('get.firmid');
        // 获取公司下所有的员
        $ulist = $this->udb
            ->where(array('firmid' => $firmid))
            ->getField('id', true);
        if (empty($ulist)) {
            $where['r.uid'] = array('in', array('a'));
        } else {
            $where['r.uid'] = array('in', $ulist);
        }


        $list = $this->rdb
            ->field('s.shipname,s.type,s.weight,s.img,r.grade2,r.grade1')
            ->alias('r')
            ->join('left join ship s on s.id = r.shipid')
            ->where($where)
            ->limit(6)
            ->select();

        //获取最近6个散货船作业个性化数据
        #todo 如果散货船作业可以被评价，请换成展示散货船的评价数据
        $sh_list = $this->shrdb
            ->alias('r')
            ->field('s.shipname,s.goodsname,s.weight,s.img,r.personality')
            ->join('left join sh_ship s on s.id = r.shipid')
            ->where($where)
            ->limit(6)
            ->select();

        foreach ($sh_list as $shkey => $shvalue) {
            $sh_list[$shkey]['personality'] = json_decode($shvalue['personality'], true);
        }

        // 获取公司信息
        $content = $this->db
            ->field('f.id,f.firmname,f.people,f.phone,f.location,f.content,h.grade,f.shehuicode,f.img,h.num,h.grade_num,h.weight,f.image')
            ->alias('f')
            ->where(array('f.id' => $firmid))
            ->join('left join firm_historical_sum h on h.firmid = f.id')
            ->find();

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            foreach ($list as $key => $value) {
                $list[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
            }
            foreach ($sh_list as $key1 => $value1) {
                $sh_list[$key1]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value1['img']);
            }

            $content['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $content['img']);
            $content['image'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['image']);
        }
        // 求评分平均分
        $content['pin'] = round($content['grade'] / $content['grade_num'], 1);
        // p($content);die;
        $assign = array(
            'list' => $list,
            'sh_list' => $sh_list,
            'content' => $content,
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 船舶公司详情
     */
    public function chuanmsg()
    {
        $firmid = I('get.firmid');
        // 获取公司下所有的船
        $shiplist = $this->shipdb
            ->field('s.shipname,s.type,h.grade,h.grade_num,s.id,s.weight,s.img')
            ->alias('s')
            ->where(array('firmid' => $firmid))
            ->join('left join ship_historical_sum h on h.shipid = s.id')
            ->limit(6)
            ->select();

        foreach ($shiplist as $key => $value) {
            $shiplist[$key]['pin'] = round($value['grade'] / $value['grade_num'], 1);
        }

        // 获取公司下所有的船
        $sh_shiplist = $this->sh_shipdb
            ->field('shipname,id,weight,img,goodsname')
            ->where(array('firmid' => $firmid))
            ->limit(6)
            ->select();


        // 获取公司信息
        $content = $this->db
            ->field('f.id,f.firmname,f.people,f.phone,f.location,f.content,h.grade,f.shehuicode,f.img,h.num,h.grade_num,h.weight,f.image')
            ->alias('f')
            ->where(array('f.id' => $firmid))
            ->join('left join firm_historical_sum h on h.firmid = f.id')
            ->find();

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            foreach ($shiplist as $key => $value) {
                $shiplist[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
            }
            $content['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $content['img']);
            $content['image'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $content['image']);
        }

        // 求评分平均分
        $content['pin'] = round($content['grade'] / $content['grade_num'], 1);
        // p($content);die;
        $assign = array(
            'list' => $shiplist,
            'sh_list' => $sh_shiplist,
            'content' => $content,
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 检验公司所有作业评价
     */
    public function morejian()
    {
        $firmid = I('get.firmid');
        // 获取公司下所有的员
        $ulist = $this->udb
            ->where(array('firmid' => $firmid))
            ->getField('id', true);

        $where['r.uid'] = array('in', $ulist);
        $count = $this->rdb
            ->alias('r')
            ->join('left join ship s on s.id = r.shipid')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 15);

        $list = $this->rdb
            ->field('s.shipname,s.type,s.weight,s.img,r.grade2,r.grade1')
            ->alias('r')
            ->join('left join ship s on s.id = r.shipid')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            foreach ($list as $key => $value) {
                $list[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
            }
        }

        $assign = array(
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 检验公司所有所有散货船检验记录
     */
    public function sh_morejian()
    {
        $firmid = I('get.firmid');
        // 获取公司下所有的员
        $ulist = $this->udb
            ->where(array('firmid' => $firmid))
            ->getField('id', true);

        $where['r.uid'] = array('in', $ulist);
        $count = $this->shrdb
            ->alias('r')
            ->join('left join sh_ship s on s.id = r.shipid')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 15);

        //获取最近6个散货船作业个性化数据
        #todo 如果散货船作业可以被评价，请换成展示散货船的评价数据
        $list = $this->shrdb
            ->alias('r')
            ->field('s.shipname,s.goodsname,s.weight,s.img,r.personality')
            ->join('left join sh_ship s on s.id = r.shipid')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();

        foreach ($list as $shkey => $shvalue) {
            $list[$shkey]['personality'] = json_decode($shvalue['personality'], true);
        }

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            foreach ($list as $key => $value) {
                $list[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
            }
        }

        $assign = array(
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 船公司所有的船舶
     */
    public function morechuan()
    {
        $firmid = I('get.firmid');

        $count = $this->shipdb
            ->alias('s')
            ->where(array('firmid' => $firmid, 'del_sign' => 1))
            ->join('left join ship_historical_sum h on h.shipid = s.id')
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 15);

        $data = $this->shipdb
            ->field('s.id,s.shipname,s.type,s.weight,h.grade,s.img')
            ->alias('s')
            ->where(array('firmid' => $firmid, 'del_sign' => 1))
            ->join('left join ship_historical_sum h on h.shipid = s.id')
            ->limit($page->firstRow, $page->listRows)
            ->select();

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            foreach ($data as $key => $value) {
                $data[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
            }
        }

        $assign = array(
            'data' => $data,
            'page' => $page->show(),
        );
        $this->assign($assign);

        $this->display();
    }

    /**
     * 船公司所有的散货船舶
     */
    public function sh_morechuan()
    {
        $firmid = I('get.firmid');

        $count = $this->sh_shipdb
            ->where(array('firmid' => $firmid, 'del_sign' => 1))
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 15);

        $data = $this->sh_shipdb
            ->field('id,shipname,weight,img,goodsname')
            ->where(array('firmid' => $firmid, 'del_sign' => 1))
            ->limit($page->firstRow, $page->listRows)
            ->select();

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            foreach ($data as $key => $value) {
                $data[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
            }
        }

        $assign = array(
            'data' => $data,
            'page' => $page->show(),
        );

        $this->assign($assign);
        $this->display();
    }

    /**
     * 船舶详情
     */
    public function shipmsg()
    {
        $id = I('get.shipid');
        $data = $this->shipdb
            ->field('s.shipname,s.type,s.weight,h.grade,s.img,s.make,s.shibie_num,s.cabinnum,h.mooring,h.weight as weights,h.num,f.firmname,s.firmid')
            ->alias('s')
            ->where(array('s.id' => $id))
            ->join('left join ship_historical_sum h on h.shipid = s.id')
            ->join('left join firm f on s.firmid = f.id')
            ->find();
        $data['mooring_num'] = count(explode(',', $data['mooring']));

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            $data['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $data['img']);
        }

        $assign = array(
            'data' => $data
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 船舶详情
     */
    public function sh_shipmsg()
    {
        $id = intval(I('get.shipid'));
        $data = $this->sh_shipdb
            ->field('s.id,s.shipname,s.goodsname,s.weight,s.img,s.make,s.shibie_num,s.cabinnum,0 + CAST(s.lbp AS CHAR) as lbp,0 + CAST(s.df AS CHAR) as df,0 + CAST(s.da AS CHAR) as da,0 + CAST(s.dm AS CHAR) as dm,0 + CAST(s.ptwd AS CHAR) as ptwd,f.firmname,s.firmid')
            ->alias('s')
            ->where(array('s.id' => $id))
            ->join('left join firm f on s.firmid = f.id')
            ->find();

        $data['result_count'] = $this->shrdb
            ->where(array('shipid' => $id, 'del_sign' => 1))
            ->count();

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            $data['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $data['img']);
        }

        $assign = array(
            'data' => $data
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 高级查询---船舶详情
     */
    public function msgship()
    {
        $id = intval(I('get.shipid'));
        $data = $this->shipdb
            ->field('s.id,s.shipname,s.type,s.weight,h.grade,s.img,s.make,s.shibie_num,s.cabinnum,h.mooring,h.weight as weights,h.num,f.firmname,s.firmid')
            ->alias('s')
            ->where(array('s.id' => $id))
            ->join('left join ship_historical_sum h on h.shipid = s.id')
            ->join('left join firm f on s.firmid = f.id')
            ->find();
        $data['mooring_num'] = count(explode(',', $data['mooring']));

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            $data['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $data['img']);
        }

        // 获取最近作业的20条数据
        $rlist = $this->rdb
            ->field('personality,weight')
            ->where(array('shipid' => $id, 'del_sign' => 1))
            ->limit(20)
            ->order('id desc')
            ->select();
        $cha = array();
        $voyage = array();
        foreach ($rlist as $key => $value) {
            $personality = json_decode($value['personality'], true);
            if (isset($personality['transport']) && $personality['transport'] != '') {
                $cha[] = abs($value['weight']) - $personality['transport'];
            } else {
                $cha[] = 0;
            }
            $voyage[] = $personality['voyage'];
        }
        $assign = array(
            'data' => $data,
            'cha' => $cha,
            'voyage' => $voyage
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 高级查询---散货船船舶详情
     */
    public function sh_msgship()
    {
        $id = intval(I('get.shipid'));
        $data = $this->sh_shipdb
            ->field('s.id,s.shipname,s.goodsname,s.weight,s.img,s.make,s.shibie_num,s.cabinnum,0 + CAST(s.lbp AS CHAR) as lbp,0 + CAST(s.df AS CHAR) as df,0 + CAST(s.da AS CHAR) as da,0 + CAST(s.dm AS CHAR) as dm,0 + CAST(s.ptwd AS CHAR) as ptwd,f.firmname,s.firmid')
            ->alias('s')
            ->where(array('s.id' => $id))
            ->join('left join firm f on s.firmid = f.id')
            ->find();

        /**
         * 处理域名访问无法获取用户上传图片的问题
         */
        if (is_Domain()) {
            $data['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $data['img']);
        }

        // 获取最近作业的20条数据
        $rlist = $this->shrdb
            ->field('personality,weight')
            ->where(array('shipid' => $id, 'del_sign' => 1))
            ->limit(20)
            ->order('id desc')
            ->select();
        //获取作业记录
        $data['result_count'] = $this->shrdb
            ->where(array('shipid' => $id, 'del_sign' => 1))
            ->count();

        $cha = array();
        $voyage = array();

        foreach ($rlist as $key => $value) {
            $personality = json_decode($value['personality'], true);
            if (isset($personality['transport']) && $personality['transport'] != '') {
                $cha[] = abs($value['weight']) - $personality['transport'];
            } else {
                $cha[] = 0;
            }
            $voyage[] = $personality['voyage'];
        }

        $assign = array(
            'data' => $data,
            'cha' => $cha,
            'voyage' => $voyage
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 船舶作业列表
     */
    public function result()
    {
        $shipid = I('get.shipid');

        $user = new \Common\Model\UserModel();
        //获取水尺数据
        $where = array(
            'r.shipid' => $shipid,
            'r.del_sign' => 1
        );

        $count = $this->rdb
            ->alias('r')
            ->join('left join ship s on r.shipid=s.id')
            ->join('left join user u on r.uid = u.id')
            ->join('left join firm f on u.firmid = f.id')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 15);

        //查询作业数据
        $list = $this->rdb
            ->alias('r')
            ->field('r.*,s.shipname,u.username,s.goodsname goodname,f.firmtype as ffirmtype')
            ->join('left join ship s on r.shipid=s.id')
            ->join('left join user u on r.uid = u.id')
            ->join('left join firm f on u.firmid = f.id')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order('id desc')
            ->select();
        $cabin = new \Common\Model\CabinModel();
        $persona = new \Common\Model\PersonalityModel();
        $resultlist = new \Common\Model\ResultlistModel();
        foreach ($list as $k1 => $v1) {
            // 个性化信息
            $personality = json_decode($v1['personality'], true);
            $personality_array = array();
            // 检索个性化名称
            foreach ($personality as $key => $value) {
                $title = $persona->getFieldByName($key, 'title');
                $personality_array[] = array(
                    'name' => $key,
                    'title' => $title,
                    'value' => $value
                );
                if ($key == 'voyage') {
                    $list[$k1]['voyage'] = $value;
                }
            }
            // 获取船驳所有舱列表
            $list[$k1]['personality_array'] = $personality_array;
            $cabinlist = $cabin
                ->field('id,cabinname')
                ->where(array('shipid' => $v1['shipid']))
                ->order('cabinname asc')
                ->select();
            $qian = array();
            $hou = array();
            foreach ($cabinlist as $key => $value) {
                $data = $resultlist
                    ->where(array('resultid' => $v1['id'], 'cabinid' => $value['id']))
                    ->select();
                if (empty($data)) {
                    $qian[] = array(
                        'cabinname' => $value['cabinname'],
                        'sounding' => '',
                        'ullage' => '',
                        'listcorrection' => '',
                        'temperature' => '',
                        'standardcapacity' => '',
                        'volume' => '',
                        'expand' => '',
                        'correntkong' => '',
                        'cabinweight' => '',
                        'ullageimg' => array(),
                        'soundingimg' => array(),
                        'temperatureimg' => array()
                    );
                    $hou[] = array(
                        'cabinname' => $value['cabinname'],
                        'sounding' => '',
                        'ullage' => '',
                        'listcorrection' => '',
                        'temperature' => '',
                        'standardcapacity' => '',
                        'volume' => '',
                        'expand' => '',
                        'correntkong' => '',
                        'cabinweight' => '',
                        'ullageimg' => array(),
                        'soundingimg' => array(),
                        'temperatureimg' => array()
                    );
                } else if (count($data) == '1') {
                    // 一条作业数据只会是作业前数据
                    // 循环获取舱列表数据
                    foreach ($data as $k => $v) {
                        // 获取作业照片
                        $listimg = M('resultlist_img')
                            ->where(array('resultlist_id' => $v['id']))
                            ->select();

                        /**
                         * 处理域名访问无法获取用户上传图片的问题
                         */
                        if (is_Domain()) {
                            foreach ($listimg as $key => $value) {
                                $listimg[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
                            }
                        }

                        $ullageimg = array();
                        $soundingimg = array();
                        $temperatureimg = array();
                        if (!empty($listimg)) {
                            foreach ($listimg as $ke => $valu) {
                                if ($valu['types'] == '1') {
                                    $ullageimg[] = $valu['img'];
                                } else if ($valu['types'] == '2') {
                                    $soundingimg[] = $valu['img'];
                                } else if ($valu['types'] == '3') {
                                    $temperatureimg[] = $valu['img'];
                                }
                            }
                        }
                        $qian[] = array(
                            'cabinname' => $value['cabinname'],
                            'sounding' => $v['sounding'],
                            'ullage' => $v['ullage'],
                            'listcorrection' => $v['listcorrection'],
                            'temperature' => $v['temperature'],
                            'standardcapacity' => $v['standardcapacity'],
                            'volume' => $v['volume'],
                            'expand' => $v['expand'],
                            'correntkong' => $v['correntkong'],
                            'cabinweight' => $v['cabinweight'],
                            'ullageimg' => $ullageimg,
                            'soundingimg' => $soundingimg,
                            'temperatureimg' => $temperatureimg
                        );
                    }

                    $hou[] = array(
                        'cabinname' => $value['cabinname'],
                        'sounding' => '',
                        'ullage' => '',
                        'listcorrection' => '',
                        'temperature' => '',
                        'standardcapacity' => '',
                        'volume' => '',
                        'expand' => '',
                        'correntkong' => '',
                        'cabinweight' => '',
                        'ullageimg' => array(),
                        'soundingimg' => array(),
                        'temperatureimg' => array()
                    );
                } else if (count($data) == '2') {
                    // 循环获取舱列表数据
                    foreach ($data as $k => $v) {
                        // 获取作业照片
                        $listimg = M('resultlist_img')
                            ->where(array('resultlist_id' => $v['id']))
                            ->select();

                        /**
                         * 处理域名访问无法获取用户上传图片的问题
                         */
                        if (is_Domain()) {
                            foreach ($listimg as $key => $value) {
                                $listimg[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
                            }
                        }

                        $ullageimg = array();
                        $soundingimg = array();
                        $temperatureimg = array();
                        if (empty($listimg)) {
                            foreach ($listimg as $ke => $valu) {
                                if ($valu['types'] == '1') {
                                    $ullageimg[] = $valu['img'];
                                } else if ($valu['types'] == '2') {
                                    $soundingimg[] = $valu['img'];
                                } else if ($valu['types'] == '3') {
                                    $temperatureimg[] = $valu['img'];
                                }
                            }
                        }
                        if ($v['solt'] == '1') {
                            $qian[] = array(
                                'cabinname' => $value['cabinname'],
                                'sounding' => $v['sounding'],
                                'ullage' => $v['ullage'],
                                'listcorrection' => $v['listcorrection'],
                                'temperature' => $v['temperature'],
                                'standardcapacity' => $v['standardcapacity'],
                                'volume' => $v['volume'],
                                'expand' => $v['expand'],
                                'correntkong' => $v['correntkong'],
                                'cabinweight' => $v['cabinweight'],
                                'ullageimg' => $ullageimg,
                                'soundingimg' => $soundingimg,
                                'temperatureimg' => $temperatureimg
                            );
                        } else {

                            $hou[] = array(
                                'cabinname' => $value['cabinname'],
                                'sounding' => $v['sounding'],
                                'ullage' => $v['ullage'],
                                'listcorrection' => $v['listcorrection'],
                                'temperature' => $v['temperature'],
                                'standardcapacity' => $v['standardcapacity'],
                                'volume' => $v['volume'],
                                'expand' => $v['expand'],
                                'correntkong' => $v['correntkong'],
                                'cabinweight' => $v['cabinweight'],
                                'ullageimg' => $ullageimg,
                                'soundingimg' => $soundingimg,
                                'temperatureimg' => $temperatureimg
                            );
                        }
                    }
                }
            }
            $uid = $_SESSION['user_info']['id'];
            $where1 = array('re.resultid' => $v1['id']);

            $resultmsg = $resultlist
                ->alias('re')
                ->field('re.*,c.cabinname')
                ->join('left join cabin c on c.id = re.cabinid')
                ->where($where1)
                ->order('re.solt asc,re.cabinid asc')
                ->select();
            //以舱区分数据（）
            foreach ($resultmsg as $k => $v) {
                $result[$v['cabinid']][] = $v;
            }
            // 获取水尺数据
            $shuichi = M('forntrecord')->where(array('resultid' => $v1['id']))->select();
            foreach ($shuichi as $key => $value) {
                $aa = array('forntleft' => $value['forntleft'], 'afterleft' => $value['afterleft']);
                if ($value['solt'] == '1') {
                    $list[$k1]['qianchi'] = $aa;
                } else {
                    $list[$k1]['houchi'] = $aa;
                }
            }
            $list[$k1]['qian'] = $qian;
            $list[$k1]['hou'] = $hou;
            // 获取水尺照片
            $datata = M('fornt_img')
                ->where(array('result_id' => $v1['id']))
                ->select();

            /**
             * 处理域名访问无法获取用户上传图片的问题
             */
            if (is_Domain()) {
                foreach ($datata as $key => $value) {
                    $datata[$key]['img'] = preg_replace("/^\/shipPlatform[^\/]*(\S+)/", "$1", $value['img']);
                }
            }

            if (empty($datata)) {
                $list[$k1]['firstfiles1'] = array();
                $list[$k1]['tailfiles1'] = array();
                $list[$k1]['firstfiles2'] = array();
                $list[$k1]['tailfiles2'] = array();
            } else {
                foreach ($datata as $key => $value) {
                    if ($value['solt'] == '1') {
                        if ($value['types'] == '1') {
                            $list[$k1]['firstfiles1'][] = $value['img'];
                        } else {
                            $list[$k1]['tailfiles1'][] = $value['img'];
                        }
                    } else {
                        if ($value['types'] == '1') {
                            $list[$k1]['firstfiles2'][] = $value['img'];
                        } else {
                            $list[$k1]['tailfiles2'][] = $value['img'];
                        }
                    }


                }
                if (empty($list[$k1]['firstfiles1'])) {
                    $list[$k1]['firstfiles1'] = array();
                }
                if (empty($list[$k1]['tailfiles1'])) {
                    $list[$k1]['tailfiles1'] = array();
                }
                if (empty($list[$k1]['firstfiles2'])) {
                    $list[$k1]['firstfiles2'] = array();
                }
                if (empty($list[$k1]['tailfiles2'])) {
                    $list[$k1]['tailfiles2'] = array();
                }
            }

        }
        $assign = array(
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($assign);
        $this->display();
    }


    /**
     * 散货船舶作业列表
     */
    public function sh_result()
    {
        $shipid = intval(I('get.shipid'));

        //获取水尺数据
        $where = array(
            'r.shipid' => $shipid,
            'r.del_sign' => 1
        );

        $count = $this->shrdb
            ->alias('r')
            ->join('left join sh_ship s on r.shipid=s.id')
            ->join('left join user u on r.uid = u.id')
            ->join('left join firm f on u.firmid = f.id')
            ->where($where)
            ->count();
        // 分页
        $page = new \Org\Nx\Page($count, 15);

        //查询作业数据
        $list = $this->shrdb
            ->alias('r')
            ->field('r.*,s.shipname,u.username,s.ptwd,s.goodsname goodname,f.firmtype as ffirmtype')
            ->join('left join sh_ship s on r.shipid=s.id')
            ->join('left join user u on r.uid = u.id')
            ->join('left join firm f on u.firmid = f.id')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order('id desc')
            ->select();

        $persona = new \Common\Model\PersonalityModel();
        $resultlist = new \Common\Model\ShResultlistModel();
        $resultrecord = M("sh_resultrecord");
        $fontrecord = M('sh_forntrecord');

        foreach ($list as $k1 => $v1) {
            // 个性化信息
            $personality = json_decode($v1['personality'], true);
            $personality_array = array();
            // 检索个性化名称
            foreach ($personality as $key => $value) {
                $title = $persona->getFieldByName($key, 'title');
                $personality_array[] = array(
                    'name' => $key,
                    'title' => $title,
                    'value' => $value
                );
                if ($key == 'voyage') {
                    $list[$k1]['voyage'] = $value;
                }
            }
            //加入个性化字段信息
            $list[$k1]['personality_array'] = $personality_array;

            $qian = array();
            $hou = array();

            $data_where = array('resultid' => $v1['id'], 'solt' => 1);
            //获取压载水数据 Ballast water
            $qian['bw'] = $resultlist
                ->field(true)
                ->where($data_where)
                ->select();

            //获取排水量表数据
            $qian['table'] = $resultrecord
                ->field('process', true)
                ->where($data_where)
                ->find();

            // 获取水尺数据
            $qian['fornt'] = $fontrecord
                ->field(true)
                ->where($data_where)
                ->find();

            //获取作业后的数据
            $data_where['solt'] = 2;
            //获取压载水数据 Ballast water
            $hou['bw'] = $resultlist
                ->field(true)
                ->where($data_where)
                ->select();

            //获取排水量表数据
            $hou['table'] = $resultrecord
                ->field('process', true)
                ->where($data_where)
                ->find();
            // 获取水尺数据
            $hou['fornt'] = $fontrecord->field(true)->where($data_where)->find();

            $list[$k1]['qian'] = $qian;
            $list[$k1]['hou'] = $hou;
        }
        $assign = array(
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($assign);
        $this->display();
    }
}