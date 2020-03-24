<?php

namespace Index\Controller;

use Common\Controller\IndexBaseController;

/**
 * 首页上传照片控制器
 * 2019-11-13
 */
class UploadController extends IndexBaseController
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
        if (I('get.id') and I('get.shipid')) {
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
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    public function upload_cabin_img()
    {
        if (I('get.id') and I('get.shipid')) {
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
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }


    public function upload_sh_img()
    {
        if (I('get.id') and I('get.shipid')) {

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
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    public function create_ship_img()
    {
        if (I('get.shipid')) {

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
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    public function create_cabin_img()
    {
        if (I('get.shipid')) {
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
                    'review' => array('neq', 3)
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
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

    public function create_sh_img()
    {
        if (I('get.shipid')) {
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
        if (I('get.review_id')) {
            $uid = $_SESSION['user_info']['id'];
            $user = new \Common\Model\UserModel();
            $msg1 = $user->is_judge($uid);
            if ($msg1['code'] == '1') {
                //用户状态不能是正常状态 2041
                $res = array(
                    'code' => $this->ERROR_CODE_RESULT['STATUS_CANNOT_NORMAL'],
                    'error' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['STATUS_CANNOT_NORMAL']],
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
                                //获取用户上传的文件名
                                $filenems = explode('.', $file_info[0]['name']);
                                array_pop($filenems);
                                $fileName = implode('.', $filenems);

                                //将上传的图片路径放入数据库
                                $path = $res['dest'];
                                $files_path = M("files_path");
                                $data = array(
                                    'type_id' => $review_id,
                                    'path' => $path,
                                    'type' => 1,
                                    'file_name' => $fileName,
                                );

                                M()->startTrans();
                                if (!$files_path->create($data)) {
                                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                                    // $this->error($statistics->getError());
                                    //数据库连接错误   3
                                    $res = array(
                                        'code' => $this->ERROR_CODE_COMMON['DB_ERROR'],
                                        'error' => $files_path->getError()
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
                                            'error' => $e->getMessage()
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
                                            'error' => $files_path->getDbError(),
                                        );
                                    }
                                }
                            } else {
                                //上传图片失败，返回报错原因 9
                                $res = array(
                                    'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                                    'error' => $res['mes'],
                                );
                            }
                        } else {
                            //审核文件数量超出 2040
                            $res = array(
                                'code' => $this->ERROR_CODE_RESULT['REVIEW_FILE_EXCEED'],
                                'error' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['REVIEW_FILE_EXCEED']],
                            );
                        }
                    } else {
                        //审核已结束或ID不正确 2039
                        $res = array(
                            'code' => $this->ERROR_CODE_RESULT['REVIEW_RESULT_ERROR'],
                            'error' => $this->ERROR_CODE_RESULT_ZH[$this->ERROR_CODE_RESULT['REVIEW_RESULT_ERROR']],
                        );
                    }
                } else {
                    //上传图片失败，因为没有图片上传 9
                    $res = array(
                        'code' => $this->ERROR_CODE_COMMON['UPLOAD_IMG_ERROR'],
                        'error' => "需要上传一个文件"
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
     * 上传舱容表认领文件
     */
    public function table_file()
    {
        if (I('get.review_id')) {
            //只有未完善公司信息的账号可以上传
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
                        if ($review_msg['type'] == 1) {
                            //大小限制50M，不检测是否是图片，类型限制文档
                            $res = $Upload->uploadFile($file_info[0], './Upload/review', false, array('pdf', 'xls', 'xlsx', 'docx', 'doc'));
                        } else {
                            //大小限制50M，检测是否是图片，类型限制图片
                            $res = $Upload->uploadFile($file_info[0], './Upload/review');
                        }

                        if ($res['mes'] == "上传成功") {
                            //获取用户上传的文件名
                            $filenems = explode('.', $file_info[0]['name']);
                            array_pop($filenems);
                            $fileName = implode('.', $filenems);

                            //将上传的图片路径放入数据库
                            $path = $res['dest'];
                            $files_path = M("files_path");
                            $data = array(
                                'type_id' => $review_id,
                                'path' => $path,
                                'type' => 2,
                                'file_name' => $fileName,
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
            //参数不正确，参数缺失	4
            $res = array(
                'code' => $this->ERROR_CODE_COMMON['PARAMETER_ERROR']
            );
        }
        exit(jsonreturn($res));
    }

}