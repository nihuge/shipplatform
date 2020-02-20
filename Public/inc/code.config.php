<?php
/**
 * 返回码配置文件
 * _zh(display)后缀数组代表内容展示
 */
// 公共返回码
$error_code_common = array(
    'SUCCESS' => 1,                // 成功
    'ERROR_OTHER' => 2,                // 其它错误
    'DB_ERROR' => 3,                // 数据库错误
    'PARAMETER_ERROR' => 4,                // 参数不正确，参数缺失
    'NOT_SPECIAL' => 5,                // 不能含有特殊字符
    'RECHARGE_FAIL' => 6,                    // 充值失败
    'ERROR_DATA' => 7,                    // 数据格式有错
    'DEDUCTIONS_FAIL' => 8,                    // 扣费失败
    'UPLOAD_IMG_ERROR' => 9,                    // 上传失败
    'NO_FILE' => 10,                    // 没有上传文件
    'EDIT_FALL' => 11,                    // 数据修改失败
    'ADD_DATA_FALL' => 12,                    // 添加数据失败
);
define('error_code_common', json_encode($error_code_common));

$error_code_common_zh = array(
    1 => '成功',
    2 => '其它错误',
    3 => '数据库错误',
    4 => '参数不正确，参数缺失',
    5 => '不能含有特殊字符',
    6 => '充值失败',
    7 => '数据格式有错',
    8 => '扣费失败',
    9 => '上传失败',
    10 => '没有上传文件',
    11 => '数据修改失败',
    12 => '添加数据失败',
);
define('error_code_common_zh', json_encode($error_code_common_zh));

// 用户相关返回码
$error_code_user = array(
    'USER_NOT_EXIST' => 1001,   // 用户名或密码错误
    'USER_PASSWORD_NOT_MATCH' => 1002,     // 两次密码不相同
    'USER_ORIGINALPASSWORD_ERROR' => 1003,   // 原始密码不正确
    'USER_FROZEN' => 1004,     // 该用户被冻结
    'FIRM_EXPIRE' => 1005,     // 公司已到期
    'USER_IS_NOT' => 1006,   // 用户不存在
    'USER_IMEI_ERROR' => 1007,   // 用户标识错误
    'USER_NOT_GROUP' => 1008,   // 用户没有分组
    'NOT_FIRM' => 1009,   // 公司不存在
    'ERROR_MEMBERTYPE' => 1010,   // 公司会员费标准有误
    'MONEY_NOT_ENOUGH' => 1011,   // 公司余额不足
    'NUMBER_IS_EXISTENCE' => 1012,   // 充值单号已存在
    'USER_NOT_FIRM' => 1013,   // 用户没有归属公司
    'USER_NOT_OPERATION_FIRM' => 1014,   // 用户对该公司没有操作权限
    'USER_NOT_ADMIN' => 1015,   // 用户不是管理员
    'FIRM_NOT_ENOUGH' => 1016,   // 用户所属公司权限不足
);
define('error_code_user', json_encode($error_code_user));

$error_code_user_zh = array( // /提示信息需要对应修改
    1001 => '用户名或密码错误',
    1002 => '两次密码不相同',
    1003 => '原始密码不正确',
    1004 => '该用户被冻结',
    1005 => '公司已到期',
    1006 => '用户不存在',
    1007 => '用户标识错误',
    1008 => '用户没有分组',
    1009 => '公司不存在',
    1010 => '公司会员费标准有误',
    1011 => '公司余额不足',
    1012 => '充值单号已存在',
    1013 => '用户没有归属公司',
    1014 => '用户对该公司没有操作权限',
    1015 => '用户不是管理员',
    1016 => '用户所属公司权限不足',
);
define('error_code_user_zh', json_encode($error_code_user_zh));

// 业务相关返回码
$error_code_result = array(
    'SHIP_NOT_RANGE' => 2001,   // 该船不在查询范围之内
    'NOT_SHIP' => 2002,   // 船名输入有误，或不存在
    'IS_REPEAT' => 2003,   // 重复数据
    'IS_RESULT_IS' => 2004,   // 指令有作业，不能修改
    'NOT_FILE' => 2005,   // pdf文件失败
    'FIRM_NOT_PDF' => 2006,   // 该该作业所属公司没有pdf文件模板
    'IS_NO_SHIP' => 2007,   // 该用户没有船
    'NO_QIAN_CABIN' => 2008,   // 没有作业前数据
    'ULLAGE_ISNOT' => 2009,   // 空高有误
    'NO_SUANFA' => 2010,   // 船舶没有算法
    'EXCEED_NUM' => 2011,   // 船舶超过限制个数
    'NOT_HAVE_CABINDATA' => 2012,   // 没有舱容数据
    'CABIN_EXCEED_NUM' => 2013,   // 超过船舶限制舱数量
    'HAVE_SHIP' => 2014,   // 船舶已存在
    'EXPIRETIME_TIME_RONG' => 2015,   // 该船舱容表已过期，请更新后再作业。
    'NEED_IMG' => 2016,   // 电子签证不能为空
    'HAVE_IMG' => 2017,   // 电子签证已存在
    'IS_REPEAT_RESULT' => 2018,   // 已存在相同的作业！
    'NOT_EVAL' => 2019,   // 作业尚未完成
    'HAVE_CABIN' => 2020,   // 船舱已存在
    'NOT_ALL_CABIN' => 2021,   // 传入的船舱数据数量不等于建船时提供的船舱数
    'RE_RECKON_FALL' => 2022,   // 重新计算数据时失败
    'WAIT_REVIEW' => 2023,   // 请等待审核
    'OTHERS_OPERATE' => 2024,   // 不允许其他人操作
    'REVIEW_OVER' => 2025,   // 审核已结束或被删除
    'CAN_NOT_REDUCE_CABIN_NUM' => 2026,   // 不可以减少舱总数
    'NOT_FIND_CABIN' => 2027,   // 舱未找到
    'CAN_NOT_EDIT_NOT_WORK' => 2028,   // 不可以更改不作业的数据
    'WORK_COMPLETE' => 2029,   // 作业已完成
    'PERSON_INCOMPLETE' => 2030,   // 个性化字段不完整，请补充完整个性化字段
    'EVALUATE_ADD_FALL' => 2031,   // 评价记录添加失败
    'EVALUATE_EDIT_FALL' => 2032,   // 评价记录修改失败
    'RESULT_DELETED' => 2033,   //该作业已被软删除不可以操作
    'RESULT_FINISHED' => 2034,   //该作业已结束不可以操作
    'UNFINISH_PRE_RESULT' => 2035,   // 未结束上一个作业
    'UNFINISH_TO_MUCH' => 2036,   // 未结束的作业太多
);
define('error_code_result', json_encode($error_code_result));

$error_code_result_zh = array( // /提示信息需要对应修改
    2001 => '该船不在查询范围之内',
    2002 => '船名输入有误，或不存在',
    2003 => '重复数据',
    2004 => '指令有作业，不能修改',
    2005 => 'pdf文件失败',
    2006 => '该该作业所属公司没有pdf文件模板',
    2007 => '该用户没有船',
    2008 => '没有作业前数据',
    2009 => '空高有误',
    2010 => '船舶没有算法',
    2011 => '船舶超过限制个数',
    2012 => '没有舱容数据',
    2013 => '超过船舶限制舱数量',
    2014 => '船舶已存在',
    2015 => '该船舱容表已过期，请更新后再作业。',
    2016 => '电子签证不能为空',
    2017 => '电子签证已存在',
    2018 => '已存在相同的作业！',
    2019 => '作业尚未完成',
    2020 => '船舱已存在',
    2021 => '传入的船舱数据数量不等于建船时提供的船舱数',
    2022 => '重新计算数据时失败',
    2023 => '请等待审核',
    2024 => '不允许其他人操作',
    2025 => '审核已结束或被删除',
    2026 => '不可以减少舱总数',
    2027 => '舱未找到',
    2028 => '不可以更改不作业的数据',
    2029 => '作业已完成',
    2030 => '个性化字段不完整，请补充完整个性化字段',
    2031 => '评价记录添加失败',
    2032 => '评价记录修改失败',
    2033 => '该作业已被软删除不可以操作',
    2034 => '该作业已结束不可以操作',
    2035 => '未结束上一个作业',
    2036 => '未结束的作业太多',
);
define('error_code_result_zh', json_encode($error_code_result_zh));