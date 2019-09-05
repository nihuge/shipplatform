/**
 * 登录
 * 
 * @param userName
 * @param userPwd
 * @param callBack
 */
function To_login(userName, userPwd, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"imei" : localStorage.getItem("UUID"),
			"title" : userName,
			"pwd" : userPwd,
		},
		"dataType" : "json",
		"url" : loginUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['content']['id'], data['content']['title'], data['content']['username'], userPwd);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 作业指令（查询）
 * 
 * @param shipname
 * @param voyage
 * @param starttime
 * @param endtime
 * @param page
 * @param locationname
 * @param callBack
 */
function To_InstructionList(shipname, voyage, starttime, endtime, page, locationname, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"shipname" : shipname,
			"voyage" : voyage,
			"starttime" : starttime,
			"endtime" : endtime,
			"p" : page,
			"locationname" : locationname
		},
		"dataType" : "json",
		"url" : instructionListPwdUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['content']);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 新增作业指令
 * 
 * @param shipid
 * @param locationname
 * @param voyage
 * @param start
 * @param objective
 * @param goodsname
 * @param transport
 * @param callBack
 */
function To_AddWorking(shipid, locationname, voyage, start, objective, goodsname, transport, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"shipid" : shipid,
			"locationname" : locationname,
			"voyage" : voyage,
			"start" : start,
			"objective" : objective,
			"goodsname" : goodsname,
			"transport" : transport,

		},
		"dataType" : "json",
		"url" : addWorkingUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 修改作业指令
 * 
 * @param resultid
 * @param shipid
 * @param locationname
 * @param voyage
 * @param start
 * @param objective
 * @param goodsname
 * @param transport
 * @param callBack
 */
function To_UpdataWorking(resultid, shipid, locationname, voyage, start, objective, goodsname, transport, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"resultid" : resultid,
			"shipid" : shipid,
			"locationname" : locationname,
			"voyage" : voyage,
			"start" : start,
			"objective" : objective,
			"goodsname" : goodsname,
			"transport" : transport,
		},
		"dataType" : "json",
		"url" : updataWorkingUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['content']);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 查看作业指令详情
 */
function To_WorkingDetail(InstructionId, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"resultid" : InstructionId
		},
		"dataType" : "json",
		"url" : workingDetailUrl,
		"success" : function(data) {
			// callBack(data);
			// alert(data)
			if (data['code'] == 1) {
				callBack(data['content'], data['resultmsg']);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 获取船列表
 * 
 * @param callBack
 */
function To_getShip(callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
		},
		"dataType" : "json",
		"url" : ShipUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['content']);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 水尺查询接口
 * 
 * @param resultid
 */
function To_ForntSearch(resultid, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"resultid" : resultid
		},
		"dataType" : "json",
		"url" : forntSearchPwdUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				if (data['content'] != null) {
					callBack(data['content']);
				}
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 水尺录入
 * 
 * @param resultid
 * @param solt
 * @param forntleft
 * @param forntright
 * @param afterleft
 * @param afterright
 * @param denty
 * @param temp
 * @param callBack
 */
function To_Draft(resultid, solt, forntleft, forntright, afterleft, afterright, denty, temp, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"resultid" : resultid,
			"solt" : solt,
			"forntleft" : forntleft,
			"forntright" : forntright,
			"afterleft" : afterleft,
			"afterright" : afterright,
			"density" : denty,
			"temperature" : temp
		},
		"dataType" : "json",
		"url" : DraftUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['content']);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 舱数据录入
 * 
 * @param resultid
 * @param shipid
 * @param cabinid
 * @param solt
 * @param qufen
 * @param ullage
 * @param sounding
 * @param temperature
 * @param quantity
 * @param altitudeheight
 * @param is_fugai
 * @param callBack
 */
function To_Reckon(resultid, shipid, cabinid, solt, qufen, ullage, sounding, temperature, quantity, altitudeheight, is_fugai, callBack) {
	// alert("指令id：" + resultid + " 船id：" + shipid + " 舱id：" + cabinid + "作业状态："
	// + solt + " 是否有底量计算：" + qufen + " 空高：" + ullage + " 实高：" + sounding
	// + " 温度：" + temperature + " 是否有底量：" + quantity
	// + " 基准高度：" + altitudeheight + " 是否覆盖：" + is_fugai);
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"resultid" : resultid,
			"shipid" : shipid,
			"cabinid" : cabinid,
			"solt" : solt,
			"qufen" : qufen,
			"ullage" : ullage,
			"sounding" : sounding,
			"temperature" : temperature,
			"quantity" : quantity,
			"altitudeheight" : altitudeheight,
			"is_fugai" : is_fugai
		},
		"dataType" : "json",
		"url" : ReckonUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['code']);
			} else {
				if ((data['code']) == 9) {
					callBack(data['code']);
				} else {
					codeMsg(data['code']);
				}
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 获取船的舱列表接口
 * 
 * @param shipid
 * @param callBack
 */
function To_GetCabinList(shipid, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"shipid" : shipid
		},
		"dataType" : "json",
		"url" : CabinListUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['content']);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 获取下载地址接口
 * 
 * @param file_IMEI
 * @param file_uid
 * @param file_id
 * @return
 */

function To_getFile(resultid, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"resultid" : resultid
		},
		"dataType" : "json",
		"url" : FileUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack(data['filename']);
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 修改密码
 * 
 * @param update_oldpwd
 * @param update_pwd1
 * @param update_pwd2
 * @param callBack
 */
function To_ChangePwd(update_oldpwd, update_pwd1, update_pwd2, callBack) {
	$.ajax({
		"type" : "post",
		"timeout" : 5000,
		"async" : true,
		"data" : {
			"uid" : localStorage.getItem("id"),
			"imei" : localStorage.getItem("UUID"),
			"oldpwd" : update_oldpwd,
			"newpwd" : update_pwd1,
			"repeatpwd" : update_pwd2
		},
		"dataType" : "json",
		"url" : updataPwdUrl,
		"success" : function(data) {
			// callBack(data);
			if (data['code'] == 1) {
				callBack();
			} else {
				codeMsg(data['code']);
			}
		},
		"complete" : function(XMLHttpRequest, status) {
			// alert(status);
		}
	});
}

/**
 * 返回码信息
 * 
 * @param code
 */
function codeMsg(code) {
	switch (code) {
	case 4:
		ToastWorringMsg("失败：" + code);
		break;
	case 1001:
		ToastWorringMsg("用户名或密码错误");
		break;
	case 1002:
		ToastWorringMsg("两次密码不相同");
		break;
	case 1003:
		ToastWorringMsg("原始密码不正确！");
		break;
	case 1004:
		ToastWorringMsg("该用户被冻结");
		break;
	case 1005:
		ToastWorringMsg("该用户已到期");
		break;
	case 1006:
		ToastWorringMsg("该用户不存在！");
		break;
	case 1007:
		ToastWorringMsg("该用户重复登录，请重新登录！");
		break;
	case 6:
		ToastWorringMsg("pdf文件生成失败！");
		break;
	case 7:
		ToastWorringMsg("请先完成作业前数据！");
		break;
	case 8:
		ToastWorringMsg("空高有误！");
		break;
	case 10:
		ToastWorringMsg("该用户下没有船舶！");
		break;
	case 11:
		ToastWorringMsg("已有作业数据，修改失败！");
		break;
	case 12:
		ToastWorringMsg("已有作业数据,不允许修改！");
		break;
	case 3001:
		ToastWorringMsg("网络异常！");
		break;
	case 3002:
		ToastWorringMsg("服务器异常！");
		break;
	case 3003:
		ToastWorringMsg("json解析异常！");
		break;
	default:
		ToastWorringMsg(code + "未知错误");
		break;
	}
}

/**
 * 弹出信息
 * 
 * @param msg
 */
function ToastWorringMsg(msg) {
	justep.Util.hint(msg, {
		"delay" : normerHintDelay,
		"style" : "color:red;"
	});
}