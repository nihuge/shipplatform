<?php

namespace APP\Controller;

use Common\Controller\AppBaseController;

/**
 * 通知操作控制器
 */
class NoticeController extends AppBaseController
{
    /**
     * 获取站内信列表
     */
    public function index()
    {
        if(intval(I('post.uid')) and I('post.imei') and intval(I('post.statue'))){
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(intval(I('post.uid')),I('post.imei'));
            if($msg1['code'] == 1){
                $notice = new \Common\Model\NoticeModel();
                //成功
                $res =$notice->noticeList(intval(I('post.uid')),intval(I('post.statue')),intval(I('post.p')));
            }else{
                //用户验证失败
                $res = $msg1;
            }
        }else{
            //参数缺失
            $res = array(
                'code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 刷新用户的未读信息个数
     * @return mixed|void
     */
    public function unreadNum()
    {
        if(intval(I('post.uid')) and I('post.imei')){
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(intval(I('post.uid')),I('post.imei'));
            if($msg1['code'] == 1){
                $notice = new \Common\Model\NoticeModel();
                //成功
                $res = array(
                    'code'=>$this->ERROR_CODE_COMMON['SUCCESS'],
                    'num'=>(int)$notice->noticeCount(intval(I('post.uid')))
                );
            }else{
                //用户验证失败
                $res = $msg1;
            }
        }else{
            //参数缺失
            $res = array(
                'code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 获取站内信详细信息
     * @return mixed|void
     */
    public function msg()
    {
        if(intval(I('post.uid')) and I('post.imei') and intval(I('post.id'))){
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(intval(I('post.uid')),I('post.imei'));
            if($msg1['code'] == 1){
                $notice = new \Common\Model\NoticeModel();
                //成功
                $res = array(
                    'code'=>$this->ERROR_CODE_COMMON['SUCCESS'],
                    'content'=>$notice->noticeMsg(intval(I('post.id')))
                );
            }else{
                //用户验证失败
                $res = $msg1;
            }
        }else{
            //参数缺失
            $res = array(
                'code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 一键已读
     * @return mixed|void
     */
    public function allReaded()
    {
        if(intval(I('post.uid')) and I('post.imei')){
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(intval(I('post.uid')),I('post.imei'));
            if($msg1['code'] == 1){
                $notice = new \Common\Model\NoticeModel();
                //成功
                $res = $notice->noticeReaded(intval(I('post.uid')));
            }else{
                //用户验证失败
                $res = $msg1;
            }
        }else{
            //参数缺失
            $res = array(
                'code'=>$this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }
}