var url = "http://121.41.22.2/ship/app.php";// url

var urlPDF = "http://121.41.22.2/ship/Public/pdf/"; // 下载PDF

var versionUrl = url + "?c=Result&a=get_config"; // 获取版本信息

var getNewApkUrl = "http://121.41.22.2/ship/Public/apk/warehouse_capacity.apk"; // 下载最新版本apkurl

// 登录接口IP
var loginUrl = url + "?c=User&a=login";
// 修改密码接口
var updataPwdUrl = url + "?c=User&a=changepwd";
// 作业指令（查询）接口
var instructionListPwdUrl = url + "?c=Result&a=resultlist";
// 水尺查询接口
var forntSearchPwdUrl = url + "?c=Result&a=forntsearch";
// 预览作业详情接口
var workingDetailUrl = url + "?c=Result&a=resultsearch";
// 作业指令修改接口
var updataWorkingUrl = url + "?c=Result&a=editresult";
// 新增作业指令接口
var addWorkingUrl = url + "?c=Result&a=addresult";
// 打印接口
var FileUrl = url + "?c=Result&a=pdf";
// 获取用户的船列表接口
var ShipUrl = url + "?c=Result&a=shiplist";
// 水尺录入接口
var DraftUrl = url + "?c=Result&a=fornt";
// 获取船的舱列表接口
var CabinListUrl = url + "?c=Result&a=cabinlist";
// 录入仓数据（计算)接口
var ReckonUrl = url + "?c=Result&a=reckon";

// 退出登录接口 0:成功;104:用户不存在
var quitUrl = url + "/app.php?c=User&a=loginout";

var NETWORK_EXCEPTION = "3001"; // 网络异常
var INTERFACE_EXCEPTION = "3002"; // 接口异常
var JSON_EXCEPTION = "3003"; // json解析异常
// 下载pdf保存到sd的路径
var SDpath = Environment.getExternalStorageDirectory() + "/PDF/";
// 下载pdf的文件名
var fileName = "20161027.pdf";

var dataType = "json";
//弹出提示信息的时间
var normerHintDelay = 1000;
