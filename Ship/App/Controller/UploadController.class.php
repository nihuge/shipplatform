<?php

namespace APP\Controller;

use Common\Controller\AppBaseController;
use mysql_xdevapi\Exception;

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
                            }else{
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
                                'ship_id' => $shipid,
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
                                'ship_id' => $shipid,
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
                                'ship_id' => $shipid,
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
                                'ship_id' => $shipid,
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
}