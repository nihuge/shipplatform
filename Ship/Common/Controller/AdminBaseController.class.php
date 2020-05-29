<?php

namespace Common\Controller;

/**
 * 基类Controller
 */
class AdminBaseController extends BaseController
{
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    public function __construct()
    {
        parent::__construct();
        if (!session('adminuid')) {
            $this->error('请先登录系统！', U('Login/login'));
        }
        if (MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Index' && ACTION_NAME == 'index') {
//            return true;
        }
        if (MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Index' && ACTION_NAME == 'loginout') {
            return true;
        }
        if (MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Index' && ACTION_NAME == 'cleancache') {
            return true;
        }
        if (MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Upload' && ACTION_NAME == 'cabin_op') {
            return true;
        }
        if (MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Search' && ACTION_NAME == 'getFirmShip') {
            return true;
        }
        if (MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Firm' && ACTION_NAME == 'upload_ajax') {
            return true;
        }
        // if(MODULE_NAME=='Admin' && CONTROLLER_NAME=='Finance' && ACTION_NAME=='install1'){
        //     return true;
        // }
        // if(MODULE_NAME=='Admin' && CONTROLLER_NAME=='Finance' && ACTION_NAME=='install2'){
        //     return true;
        // }
        $auth = new \Think\Auth();
        $rule_name = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
        $result = $auth->check($rule_name, $_SESSION['adminuid']);
        if (!$result) {
            $this->error('您没有权限访问', U('Admin/Index/index'));
        }
        //根据用户的ID获取用户的信息
        $admin = new \Common\Model\AdminModel();
        $adminmsg = $admin
            ->alias('a')
            ->field('a.name,a.id,g.group_id')
            ->join("left join auth_group_access as g on a.id=g.uid")
            ->where(array('a.id' => $_SESSION['adminuid']))
            ->find();
        $this->assign('adminmsg', $adminmsg);


        $ship_review = M("ship_review");
        $sh_ship_review = M("sh_review");
        $ship = new \Common\Model\ShipFormModel();
        $sh_ship = new \Common\Model\ShShipModel();
        $work = new \Common\Model\WorkModel();
        $sh_result = new \Common\Model\ShResultModel();

        /**
         * 获取符合条件的修改审核数量
         */

        //获取油船的符合条件的审核数量
        $ship_review_where = array('_string' => 'status=1 AND ((data_status = 1 and picture=2) or (data_status=2 AND picture=2 and cabin_picture=2) or (data_status=3 AND cabin_picture=2))');

        //得到数量
        $ship_review_count = $ship_review->where($ship_review_where)->count();

        //获取散货船的符合条件的审核数量
        $sh_ship_review_where = array('_string' => 'status=1 AND picture=2');

        //获得数量
        $sh_ship_review_count = $sh_ship_review->where($sh_ship_review_where)->count();

        /**
         * 获取符合条件的油船新建审核数量
         */
        $ship_where = array(
            's.is_lock' => 2
        );

        /**
         * 构建子查询sql,查询哪些船处于待审核状态
         *
         * 生成sql：(select id from ship where review = 1)
         **/
//        $sub_sql = $ship
//            ->alias("s")
//            ->field('s.id')
//            ->join('left join cabin as c on c.shipid=s.id')
//            ->where($ship_where)
//            ->group('s.id')
//            ->having('count(c.id)>0')
//            ->buildSql();
//        /*
//         * 结合子查询查询作业次数小于2的待审核船
//         *
//         * 生成sql:select count(1) as a,shipid FROM result WHERE shipid in((select id from ship where review = 1)) GROUP BY shipid HAVING count(1)<2
//         */
//        try {
//            $ship_count = $ship->alias('s')
//                ->field('s.id')
//                ->join('left join result as r on s.id=r.shipid')
//                ->where(array('s.id' => array('exp', 'in (' . $sub_sql . ')')))
//                ->group('s.id')
//                ->having('count(r.shipid)<2')
//                ->select();
//        } catch (\Exception $e) {
//            $ship_count = array();
//        }
//        //我count我自己
//        $ship_count = count($ship_count);

        $ship_count = $ship
            ->alias("s")
            ->field('s.id')
            ->join('left join cabin as c on c.shipid=s.id')
            ->where($ship_where)
            ->group('s.id')
            ->having('count(c.id)>0')
            ->select();
        //我count我自己
        $ship_count = count($ship_count);

        $ship_where = array(
            'is_lock' => 2
        );


//        /*
//         * 构建子查询sql,查询哪些船处于待审核状态
//         *
//         * 生成sql：(select id from ship where review = 1)
//         */
//        $sub_sql = $sh_ship->field('id')->where($ship_where)->buildSql();
//
//        /*
//         * 结合子查询查询作业次数小于2的待审核船
//         *
//         * 生成sql:select count(1) as a,shipid FROM result WHERE shipid in((select id from ship where review = 1)) GROUP BY shipid HAVING count(1)<2
//         */
//        try {
//            $sh_ship_count = $sh_ship
//                ->alias('s')
//                ->field('s.id')
//                ->join('left join sh_result as r on s.id=r.shipid')
//                ->where(array('s.id' => array('exp', 'in (' . $sub_sql . ')')))
//                ->group('s.id')
//                ->having('count(r.shipid)<2')
//                ->select();
//        } catch (\Exception $e) {
//            $sh_ship_count = array();
//        }
//        //我count我自己
//        $sh_ship_count = count($sh_ship_count);
        $sh_ship_count= $sh_ship->field('id')->where($ship_where)->select();
        //我count我自己
        $sh_ship_count = count($sh_ship_count);
        /*
         * 公司认领审核部分
         */
        $firm_review_count_where = array(
            'result' => 1,//统计待审核记录
            'file_count' => array('gt', 0),//统计有文件的记录
        );
        $firm_review = M('firm_review');
        $firm_review_count = $firm_review->where($firm_review_count_where)->count();

        /*
         * 舱容表审核部分
         */
        $table_review_count_where = array(
            'result' => 1,//统计待审核记录
            'file_count' => array('gt', 0),//统计有文件的记录
        );
        $table_review = M('table_review');
        $table_review_count = $table_review->where($table_review_count_where)->count();


        //初始化审核计数渲染数组
        $review_count_arr = array();

        $review_count_arr['sh_ship_review_count'] = $sh_ship_review_count;
        $review_count_arr['ship_review_count'] = $ship_review_count;
        $review_count_arr['sh_ship_count'] = $sh_ship_count;
        $review_count_arr['ship_count'] = $ship_count;
        $review_count_arr['firm_review_count'] = $firm_review_count;
        $review_count_arr['table_review_count'] = $table_review_count;


        //修改审核总数
        $review_count_arr['review_count'] = $sh_ship_review_count + $ship_review_count + $firm_review_count;

        //新建审核总数
        $review_count_arr['create_count'] = $sh_ship_count + $ship_count + $table_review_count;


        //总审核总数
        $review_count_arr['all_review_count'] = $review_count_arr['review_count'] + $review_count_arr['create_count'];


        $this->assign('review_count_arr', $review_count_arr);

    }
}