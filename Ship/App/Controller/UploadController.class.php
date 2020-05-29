<?php

namespace APP\Controller;

use Common\Controller\AppBaseController;

/**
 * 上传照片控制器
 * 2019-07-24
 */
class UploadController extends AppBaseController
{
    public function upload_img()
    {
        vendor("Nx.FileUpload");
        $Upload = new \FileUpload();
        $file_info = $Upload->getFiles();
        if (count($file_info) > 0) {
            $res = $Upload->uploadFile($file_info[0], './Upload/review');
            if ($res['mes'] == "上传成功") {
                exit(json_encode(
                    array(
                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                        'msg' => $res['mes'],
                        'path' => $res['dest']
                    ))
                );
            } else {
                exit(json_encode(
                    array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => $res,
                        'info' => $file_info
                    ))
                );
            }
        } else {
            exit(json_encode(
                array(
                    'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                    'msg' => "haven't img"
                ))
            );
        }
    }

    /**
     * 上传液货船舶复核照片
     */
    public function upload_ship_img()
    {
        if (I('get.uid') and I('get.imei') and I('get.id') and I('get.shipid')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $shipid = trimall(I('get.shipid'));
                $id = trimall(I('get.id'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断提交的审核ID是否可以上传，否则不接收
                    $review = M("ship_review");
                    $review_where = array(
                        'id' => $id,
                        'shipid' => $shipid,
                        'status' => 1
                    );
                    $review_count = $review->field('id,data_status,cabin_picture,picture')->where($review_where)->find();
                    //判断此id是否在审核中
                    if (count($review_count) > 0) {
                        $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        if ($res['mes'] == "上传成功") {
                            //将上传的图片路径放入数据库
                            $path = $res['dest'];
                            $review_img = M("review_img");

                            $data = array(
                                'review_id' => $id,
                                'type' => 1,//1代表船修改的照片
                                'ship_id' => $shipid,
                                'img' => $path
                            );

                            if ($review_count['data_status'] == 3 and $review_count['cabin_picture'] == 2) {
                                //如果状态为只上传了舱信息并且有舱照片，则改为都上传了信息
                                $review_data = array(
                                    'picture' => 2,
                                    'data_status' => 2
                                );
                            } else {
                                $review_data = array(
                                    'picture' => 2
                                );
                            }
                            M()->startTrans();
                            if (!$review_img->create($data) or !$review->create($review_data)) {
                                // 如果创建失败 表示验证没有通过 输出错误提示信息
                                // $this->error($statistics->getError());
                                //数据库连接错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $review_img->getError()
                                );
                            } else {
                                try {
                                    $result = $review_img->add($data);
                                    $review_result = $review->where($review_where)->save($review_data);
                                } catch (\Exception $e) {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $e->getMessage()
                                    );
                                    exit(jsonreturn($res));
                                }

                                if ($result !== false and $review_result !== false) {
                                    //提交
                                    M()->commit();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'msg' => $res['mes'],
                                        'path' => $path
                                    );
                                } else {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    );
                                }
                            }
                        } else {
                            //上传图片失败，返回报错原因 9
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                'msg' => $res['mes'],
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2025
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_OVER'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 上传液货船舶舯舱的复核照片
     */
    public function upload_cabin_img()
    {
        if (I('get.uid') and I('get.imei') and I('get.id') and I('get.shipid')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $shipid = trimall(I('get.shipid'));
                $id = trimall(I('get.id'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断提交的审核ID是否可以上传，否则不接收
                    $review = M("ship_review");
                    $review_where = array(
                        'id' => $id,
                        'shipid' => $shipid,
                        'status' => 1
                    );
                    $review_count = $review->field('id,data_status,cabin_picture,picture')->where($review_where)->find();
                    //判断此id是否在审核中
                    if (count($review_count) > 0) {
                        $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        if ($res['mes'] == "上传成功") {
                            //将上传的图片路径放入数据库
                            $path = $res['dest'];
                            $review_img = M("review_img");

                            $data = array(
                                'review_id' => $id,
                                'type' => 2,//2代表舱修改的照片
                                'ship_id' => $shipid,
                                'img' => $path
                            );

                            if ($review_count['data_status'] == 1 and $review_count['picture'] == 2) {
                                $review_data = array(
                                    'cabin_picture' => 2,
                                    'data_status' => 2,
                                );
                            } else {
                                $review_data = array(
                                    'cabin_picture' => 2
                                );
                            }

                            M()->startTrans();
                            if (!$review_img->create($data) and !$review->create($review_data)) {
                                // 如果创建失败 表示验证没有通过 输出错误提示信息
                                // $this->error($statistics->getError());
                                //数据库连接错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $review_img->getError()
                                );
                            } else {
                                try {
                                    $result = $review_img->add($data);
                                    $review_result = $review->where($review_where)->save($review_data);
                                } catch (\Exception $e) {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $e->getMessage()
                                    );
                                    exit(jsonreturn($res));
                                }

                                if ($result !== false and $review_result !== false) {
                                    //提交
                                    M()->commit();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'msg' => $res['mes'],
                                        'path' => $path
                                    );
                                } else {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    );
                                }
                            }
                        } else {
                            //上传图片失败，返回报错原因 9
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                'msg' => $res['mes'],
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2025
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_OVER'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 上传散货船舶的复核照片
     */
    public function upload_sh_img()
    {
        if (I('get.uid') and I('get.imei') and I('get.id') and I('get.shipid')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $shipid = trimall(I('get.shipid'));
                $id = trimall(I('get.id'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断提交的审核ID是否可以上传，否则不接收
                    $review = M("sh_review");
                    $review_where = array(
                        'id' => $id,
                        'shipid' => $shipid,
                        'status' => 1
                    );

                    $review_count = $review->where($review_where)->count();
                    //判断此id是否在审核中
                    if ($review_count > 0) {
                        $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        if ($res['mes'] == "上传成功") {
                            //将上传的图片路径放入数据库
                            $path = $res['dest'];
                            $review_img = M("sh_review_img");
                            $data = array(
                                'review_id' => $id,
                                'shipid' => $shipid,
                                'img' => $path
                            );

                            $review_data = array(
                                'picture' => 2
                            );

                            M()->startTrans();
                            if (!$review_img->create($data) and !$review->create($review_data)) {
                                // 如果创建失败 表示验证没有通过 输出错误提示信息
                                // $this->error($statistics->getError());
                                //数据库连接错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $review_img->getError()
                                );
                            } else {
                                try {
                                    //先删除之前上传的审核
                                    $result = $review_img->add($data);
                                    $review_result = $review->where($review_where)->save($review_data);
                                } catch (\Exception $e) {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $e->getMessage()
                                    );
                                    exit(jsonreturn($res));
                                }

                                if ($result !== false and $review_result !== false) {
                                    //提交
                                    M()->commit();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'msg' => $res['mes'],
                                        'path' => $path
                                    );
                                } else {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    );
                                }
                            }
                        } else {
                            //上传图片失败，返回报错原因 9
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                'msg' => $res['mes'],
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2025
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_OVER'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 上传创建液货船的复核照片
     */
    public function create_ship_img()
    {
        if (I('get.uid') and I('get.imei') and I('get.shipid')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $shipid = trimall(I('get.shipid'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断提交船ID是否被审核过，审核过不接收
                    $ship = new \Common\Model\ShipFormModel();
                    $ship_where = array(
                        'id' => $shipid,
                        'review' => 1
                    );

                    $review_count = $ship->where($ship_where)->count();
                    //判断此id是否在审核中
                    if ($review_count > 0) {
                        $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        if ($res['mes'] == "上传成功") {
                            //将上传的图片路径放入数据库
                            $path = $res['dest'];
                            $ship_img = M("ship_img");
                            $data = array(
                                'shipid' => $shipid,
                                'img' => $path
                            );


                            M()->startTrans();
                            if (!$ship_img->create($data)) {
                                // 如果创建失败 表示验证没有通过 输出错误提示信息
                                // $this->error($statistics->getError());
                                //数据库连接错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $ship_img->getError()
                                );
                            } else {
                                try {
                                    //先删除之前上传的审核
                                    $result = $ship_img->add($data);
                                } catch (\Exception $e) {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $e->getMessage()
                                    );
                                    exit(jsonreturn($res));
                                }

                                if ($result !== false) {
                                    //提交
                                    M()->commit();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'msg' => $res['mes'],
                                        'path' => $path
                                    );
                                } else {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    );
                                }
                            }
                        } else {
                            //上传图片失败，返回报错原因 9
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                'msg' => $res['mes'],
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2025
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_OVER'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 上传创建液货船中舱的复核照片
     */
    public function create_cabin_img()
    {
        if (I('get.uid') and I('get.imei') and I('get.shipid')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $shipid = trimall(I('get.shipid'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断提交船ID是否被审核过，审核过不接收
                    $ship = new \Common\Model\ShipFormModel();
                    $ship_where = array(
                        'id' => $shipid,
                        'review' => 2
                    );

                    $review_count = $ship->where($ship_where)->count();
                    //判断此id是否在审核中
                    if ($review_count > 0) {
                        $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        if ($res['mes'] == "上传成功") {
                            //将上传的图片路径放入数据库
                            $path = $res['dest'];
                            $ship_img = M("ship_img");
                            $data = array(
                                'type' => 2,
                                'shipid' => $shipid,
                                'img' => $path
                            );

                            M()->startTrans();
                            if (!$ship_img->create($data)) {
                                // 如果创建失败 表示验证没有通过 输出错误提示信息
                                // $this->error($statistics->getError());
                                //数据库连接错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $ship_img->getError()
                                );
                            } else {
                                try {
                                    //先删除之前上传的审核
                                    $result = $ship_img->add($data);
                                } catch (\Exception $e) {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $e->getMessage()
                                    );
                                    exit(jsonreturn($res));
                                }

                                if ($result !== false) {
                                    //提交
                                    M()->commit();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'msg' => $res['mes'],
                                        'path' => $path
                                    );
                                } else {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    );
                                }
                            }
                        } else {
                            //上传图片失败，返回报错原因 9
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                'msg' => $res['mes'],
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2025
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_OVER'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 上传创建散货船的复核照片
     */
    public function create_sh_img()
    {
        if (I('get.uid') and I('get.imei') and I('get.shipid')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $shipid = trimall(I('get.shipid'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断提交船ID是否被审核过，审核过不接收
                    $ship = new \Common\Model\ShShipModel();
                    $ship_where = array(
                        'id' => $shipid,
                        'review' => 1
                    );

                    $review_count = $ship->where($ship_where)->count();
                    //判断此id是否在审核中
                    if ($review_count > 0) {
                        $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        if ($res['mes'] == "上传成功") {
                            //将上传的图片路径放入数据库
                            $path = $res['dest'];
                            $ship_img = M("sh_ship_img");
                            $data = array(
                                'shipid' => $shipid,
                                'img' => $path
                            );

                            M()->startTrans();
                            if (!$ship_img->create($data)) {
                                // 如果创建失败 表示验证没有通过 输出错误提示信息
                                // $this->error($statistics->getError());
                                //数据库连接错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $ship_img->getError()
                                );
                            } else {
                                try {
                                    //先删除之前上传的审核
                                    $result = $ship_img->add($data);
                                } catch (\Exception $e) {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $e->getMessage()
                                    );
                                    exit(jsonreturn($res));
                                }

                                if ($result !== false) {
                                    //提交
                                    M()->commit();
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                        'msg' => $res['mes'],
                                        'path' => $path
                                    );
                                } else {
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    );
                                }
                            }
                        } else {
                            //上传图片失败，返回报错原因 9
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                'msg' => $res['mes'],
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2025
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_OVER'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }


    /**
     * 上传公司认领文件
     */
    public function claimed_file()
    {
        if (I('get.uid') and I('get.imei') and I('get.review_id')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['STATUS_CANNOT_NORMAL']
                );
            } elseif ($msg1['code'] == $this->ERROR_CODE_USER['NOT_FIRM']) {
                //只有未完善公司信息的账号可以上传
                $review_id = trimall(I('get.review_id'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断此ID是否是正确的审核ID
                    $firm_review = M('firm_review');
                    $review_where = array(
                        'id' => $review_id,
                    );

                    $review_msg = $firm_review->where($review_where)->find();
                    //判断此id是否还未审核,未审核可以上传，否则不行
                    if ($review_msg['result'] == 1) {
                        if ($review_msg['file_count'] + 1 <= 10) {
                            $res = $Upload->uploadFile($file_info[0], './Upload/review', false, array('pdf', 'xls', 'xlsx', 'docx', 'doc'));
                            if ($res['mes'] == "上传成功") {

                                //将上传的图片路径放入数据库
                                $path = $res['dest'];
                                $files_path = M("files_path");
                                $data = array(
                                    'type_id' => $review_id,
                                    'path' => $path,
                                    'type' => 1,
                                    'file_name' => $file_info[0]['name'],
                                );

                                M()->startTrans();
                                if (!$files_path->create($data)) {
                                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                                    // $this->error($statistics->getError());
                                    //数据库连接错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $files_path->getError()
                                    );
                                } else {
                                    try {
                                        //添加数据
                                        $result = $files_path->add($data);
                                    } catch (\Exception $e) {
                                        //回档
                                        M()->rollback();
                                        //数据库错误   3
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                            'msg' => $e->getMessage()
                                        );
                                        exit(jsonreturn($res));
                                    }

                                    if ($result !== false) {
                                        $firm_review->where($review_where)->setInc('file_count');//文件数量+1
                                        //提交
                                        M()->commit();
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                            'msg' => $res['mes'],
                                            'path' => $path
                                        );
                                    } else {
                                        //回档
                                        M()->rollback();
                                        //数据库错误   3
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        );
                                    }
                                }
                            } else {
                                //上传图片失败，返回报错原因 9
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                    'msg' => $res['mes'],
                                );
                            }
                        } else {
                            //审核文件数量超出 2040
                            $res = array(
                                'code' => $this->ERROR_CODE_RESULT['REVIEW_FILE_EXCEED']
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2039
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_RESULT_ERROR'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }


    /**
     * 上传舱容表认领文件 完整上传限制为文档，部分上传限制为图片
     */
    public function table_file()
    {
        if (I('get.uid') and I('get.imei') and I('get.review_id')) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $review_id = trimall(I('get.review_id'));
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //判断此ID是否是正确的审核ID
                    $firm_review = M('table_review');
                    $review_where = array(
                        'id' => $review_id,
                    );

                    $review_msg = $firm_review->where($review_where)->find();
                    //判断此id是否还未审核,未审核可以上传，否则不行
                    if ($review_msg['result'] == 1) {
                        if ($review_msg['file_count'] + 1 <= 10) {
                            //判断用户选择的是完整上传还是部分上传
                            if($review_msg['type'] == 1){
                                //大小限制50M，不检测是否是图片，类型限制文档
                                $res = $Upload->uploadFile($file_info[0], './Upload/review', false, array('pdf', 'xls', 'xlsx', 'docx', 'doc'));
                            }else{
                                //大小限制50M，检测是否是图片，类型限制图片
                                $res = $Upload->uploadFile($file_info[0], './Upload/review');
                            }

                            if ($res['mes'] == "上传成功") {
                                //将上传的图片路径放入数据库
                                $path = $res['dest'];
                                $files_path = M("files_path");
                                $data = array(
                                    'type_id' => $review_id,
                                    'path' => $path,
                                    'type' => 2,
                                    'file_name' =>$file_info[0]['name'],
                                );

                                M()->startTrans();
                                if (!$files_path->create($data)) {
                                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                                    // $this->error($statistics->getError());
                                    //数据库连接错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $files_path->getError()
                                    );
                                } else {
                                    try {
                                        //添加数据
                                        $result = $files_path->add($data);
                                    } catch (\Exception $e) {
                                        //回档
                                        M()->rollback();
                                        //数据库错误   3
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                            'msg' => $e->getMessage()
                                        );
                                        exit(jsonreturn($res));
                                    }

                                    if ($result !== false) {
                                        $firm_review->where($review_where)->setInc('file_count');//文件数量+1
                                        //提交
                                        M()->commit();
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                                            'msg' => $res['mes'],
                                            'path' => $path
                                        );
                                    } else {
                                        //回档
                                        M()->rollback();
                                        //数据库错误   3
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        );
                                    }
                                }
                            } else {
                                //上传图片失败，返回报错原因 9
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                    'msg' => $res['mes'],
                                );
                            }
                        } else {
                            //审核文件数量超出 2040
                            $res = array(
                                'code' => $this->ERROR_CODE_RESULT['REVIEW_FILE_EXCEED']
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2039
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_RESULT_ERROR'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传一个图片"
                    );
                }

            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }


    /**
     * 协议修量上传接口
     *
     */
    public function add_weight()
    {
        if (I('get.uid') and I('get.imei') and I('get.resultid') and I('get.add_weight')!=="" and I('get.add_weight')!==null) {
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                $result = new \Common\Model\WorkModel();
                $resultid = intval(I('get.resultid'));
                $result_info = $result->field('del_sign,finish_sign')->where(array('id' => $resultid))->find();
                //如果作业被删除了，不可以操作 2033
                if ($result_info['del_sign'] == 2) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_DELETED'])));
                //如果作业结束了，不可以操作 2034
                if ($result_info['finish_sign'] == 1) exit(jsonreturn(array('code' => $this->ERROR_CODE_RESULT['RESULT_FINISHED'])));

                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                if (count($file_info) > 0) {
                    //大小限制50M，检测是否是图片，类型限制图片
                    $res = $Upload->uploadFile($file_info[0], './Upload/add_weight');
                    if ($res['mes'] == "上传成功") {
                        //将上传的图片路径放入数据库
                        $path = $res['dest'];
                        $data = array(
                            'add_weight' => trimall(I('get.add_weight')),
                            'add_weight_signature' => $path,
                        );

                        M()->startTrans();
                        if (!$result->create($data)) {
                            // 如果创建失败 表示验证没有通过 输出错误提示信息
                            // $this->error($statistics->getError());
                            //数据库连接错误   3
                            $res = array(
                                'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                'msg' => $result->getError()
                            );
                        } else {
                            try {
                                //添加数据
                                $res = $result->editData(array('id'=>$resultid),$data);
                                if($res === false){
                                    //回档
                                    M()->rollback();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'msg' => $result->getDbError()
                                    );
                                    exit(jsonreturn($res));
                                }else{
                                    M()->commit();
                                    //数据库错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                                    );
                                    exit(jsonreturn($res));
                                }
                            } catch (\Exception $e) {
                                //回档
                                M()->rollback();
                                //数据库错误   3
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                    'msg' => $e->getMessage()
                                );
                                exit(jsonreturn($res));
                            }
                        }
                    } else {
                        //上传图片失败，返回报错原因 9
                        $res = array(
                            'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                            'msg' => $res['mes'],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'msg' => "需要上传签名"
                    );
                }
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    /**
     * 电子签证
     * @param string imei 标识
     * @param int shipid 船ID
     * @return array
     * @return array code
     */
    public function electronic_visa()
    {
        if (I('get.resultid') and I('get.uid') and I('get.imei')) {
            //判断用户状态、是否到期、标识比对
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judges(I('get.uid'), I('get.imei'));
            if ($msg1['code'] == '1') {
                //初始化文件上传类
                vendor("Nx.FileUpload");
                $Upload = new \FileUpload();
                $file_info = $Upload->getFiles();
                // 电子签证照片
                if (count($file_info) > 0) {
                    $result_id = intval(I('get.resultid'));
                    $uid = intval(I('get.uid'));
                    $result = new \Common\Model\WorkModel();
                    $result_info = $result->field("uid,shipid,del_sign")->where(array('id' => $result_id))->find();
                    if ($result_info['del_sign'] == 2) {
                        //软删除的作业不可以被操作
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['RESULT_DELETED']
                        );
                    } else {
                        //判断是否是创建人操作的签名
                        if ($uid == $result_info['uid']) {
                            // 上传签证
                            $path_h = "./Upload/img/" . date('Y-m-d', time()) . '/';
                            //大小限制50M，检测是否是图片，类型限制图片
                            $res_h = $Upload->uploadFile($file_info[0], $path_h);
                            if ($res_h['mes'] != "上传成功") {
                                //图片上传失败
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR']
                                );
                            } else {
                                M()->startTrans();

                                // 判断电子签证是否存在
                                $count = M('electronic_visa')
                                    ->where(array('resultid' => $result_id))
                                    ->count();
                                if ($count >= 1) {
                                    // 电子签证已存在。删除原先数据
                                    M('electronic_visa')
                                        ->where(array('resultid' => $result_id))->delete();
                                    unlink($count['img']);
                                } else {
                                    $count_where = array(
                                        'uid' => $uid,
                                        'finish_sign' => 1,
                                        'count_sign' => 0,
                                        'del_sign' => 1,//不统计已删除的作业
                                        'id' => array('LT', $result_id),
                                    );
                                    //查找该用户未被统计的记录
                                    $pre_result_status = $result
                                        ->field('id,finish_sign,shipid')
                                        ->where($count_where)
                                        ->order('id desc')
                                        ->select();
                                    $resultlist = new \Common\Model\ResultlistModel();
                                    $cabin = new \Common\Model\CabinModel();
                                    $evaluate = M('evaluation');
                                    //逐个统计
                                    foreach ($pre_result_status as $k => $v) {
                                        //开始统计作业的经验底量
                                        $bottom_list = $resultlist->get_base_volume_list($v['id']);

                                        $map = array('result_id' => $v['id']);
                                        // 获取作业的舱容表准确度评价
                                        $table_accuracy = $evaluate->field('table_accuracy')->where($map)->find();

                                        //计入统计
                                        if ($table_accuracy['table_accuracy'] > 0) {
                                            M('ship_historical_sum')->where(array('shipid' => $v['shipid']))->setInc('table_accuracy', $table_accuracy['table_accuracy']);
                                            M('ship_historical_sum')->where(array('shipid' => $v['shipid']))->setInc('accuracy_num');
                                        }

                                        foreach ($bottom_list as $value) {
                                            $cabin->where(array('id' => $value['cabinid']))->setInc('base_volume', $value['standardcapacity']);
                                            $cabin->where(array('id' => $value['cabinid']))->setInc('base_count');
                                        }
                                        $pre_count_result = $result->editData(array('id' => $v['id']), array('count_sign' => 1));
                                        //如果更改失败,回档，删除上传的图片，报错数据库错误 3
                                        if ($pre_count_result === false) {
                                            M()->rollback();
                                            unlink($res_h ['dst']);
                                            exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'])));
                                        }
                                    }

                                    //添加当前作业结束标志
                                    $finish_result = $result->editData(array('id' => $result_id), array('finish_sign' => 1));
                                    //如果更改失败,回档，删除上传的图片，报错数据库错误 3
                                    if ($finish_result === false) {
                                        M()->rollback();
                                        unlink($res_h ['dst']);
                                        exit(jsonreturn(array('code' => $this->ERROR_CODE_COMMON['DB_ERROR'])));
                                    }


                                }
                                $img = $res_h ['dst'];
                                // 新增电子签证
                                $data = array(
                                    'resultid' => $result_id,
                                    'img' => $img,
                                );
                                $arr = M('electronic_visa')->add($data);
                                if ($arr) {
                                    // 作业数据汇总
                                    $result = new \Common\Model\WorkModel();
                                    $res1 = $result->weight($result_id);
                                    if ($res1['code'] == '1') {
                                        M()->commit();
                                        //成功 1
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['SUCCESS']
                                        );
                                    } else {
                                        M()->rollback();
                                        // 其它错误  2
                                        $res = array(
                                            'code' => $this->ERROR_CODE_COMMON['ERROR_OTHER']
                                        );
                                    }
                                } else {
                                    M()->rollback();
                                    //上传失败 1
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR']
                                    );
                                }
                            }
                        } else {
                            //不允许其他人操作 2024
                            $res = array(
                                'code' => $this->ERROR_CODE_RESULT['OTHERS_OPERATE']
                            );
                        }
                    }
                } else {
                    // 电子签证不能为空
                    $res = array(
                        'code' => $this->ERROR_CODE_RESULT['NEED_IMG']
                    );
                }
            } else {
                // 错误信息返回码
                $res = $msg1;
            }
        } else {
            //参数不正确，参数缺失    4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR'],
                'data' => I('post.')
            );
        }

        echo jsonreturn($res);
    }
}