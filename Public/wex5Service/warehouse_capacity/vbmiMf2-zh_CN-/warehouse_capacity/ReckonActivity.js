define(function(require) {
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");

	var Model = function() {
		this.callParent();
		this.cangId = "";
		this.rongOrdi = "rongliang";
		this.quantity = "1";
		this.position = -1;
		this.cangIdArray = new Array();
		this.altitudeheightArray = new Array();
		this.dialtitudeheightArray = new Array();
		this.altitudeheight = -1;
		this.dialtitudeheight = -1;
		this.Height = -1;
		this.solt = "1";
		this.ullage = "";
		this.sounding = "";
		this.temp = "";
		this.Laltitudeheight = "";
	};

	Model.prototype.modelLoad = function(event) {
	};

	Model.prototype.modelActive = function(event) {
		InitData(this);
		changeBorderColor(0, this);
		getCangData(this);
	};

	/**
	 * 切换到录入数据界面
	 */
	Model.prototype.col2Click = function(event) {
		changeBorderColor(0, this);
	};

	/**
	 * 切换到查看仓数据界面
	 */
	Model.prototype.col4Click = function(event) {
		changeBorderColor(1, this);
		loadData(this);
	};

	/**
	 * 仓号选择监听
	 */

	Model.prototype.cangDataModerValueChange = function(event) {
		this.cangId = event.newValue;
		// 选择后清空实高和空高
		clearHeight(this);
		// 获取position
		for (var int = 0; int < this.cangIdArray.length; int++) {
			if (event.newValue == this.cangIdArray[int]) {
				this.position = int;
				SetAltitudeOrDialtitudeHeight(this);
				return;
			}
		}

	};

	/**
	 * 是否为底量计算选择监听
	 */
	Model.prototype.rongOrdiDataModerValueChange = function(event) {
		this.rongOrdi = event.newValue;
		// 选择后清空实高和空高
		clearHeight(this);
		SetAltitudeOrDialtitudeHeight(this);
	};

	/**
	 * 空高input监听
	 */
	Model.prototype.input_ullageMouseout = function(event) {
		autoHeight(this, "input_ullage", "input_sounding");
	};

	/**
	 * 实高input监听
	 */
	Model.prototype.input_soundingMouseout = function(event) {
		autoHeight(this, "input_sounding", "input_ullage");
	};

	/**
	 * 有无底量监听
	 */
	Model.prototype.YNDiDataModerValueChange = function(event) {
		this.quantity = event.newValue;
	};
	/**
	 * 提交数据
	 */
	Model.prototype.btn_registClick = function(event) {
		var me = this;
		me.ullage = me.getElementByXid("input_ullage").value;
		me.sounding = me.getElementByXid("input_sounding").value;
		me.temp = me.getElementByXid("input_temp").value;
		if (me.cangId == "") {
			showToast("请选择船仓！");
			return;
		}
		if (me.rongOrdi == "rongliang") {
			me.Laltitudeheight = me.altitudeheight;
		} else {
			me.Laltitudeheight = me.dialtitudeheight;
		}

		if (me.ullage == "") {
			showToast("空高不能为空！");
			return;
		}
		if (isNaN(me.ullage)) {
			showToast("请输入正确数值！");
			return;
		}

		if (me.sounding == "") {
			showToast("实高不能为空！");
			return;
		}
		if (isNaN(me.sounding)) {
			showToast("请输入正确数值！");
			return;
		}
		if (me.temp == "") {
			showToast("温度不能为空！");
			return;
		}

		To_Reckon(localStorage.getItem("instruction_id"), localStorage.getItem("ship_id"), me.cangId, me.solt, me.rongOrdi, me.ullage, me.sounding, me.temp, me.quantity, me.Laltitudeheight, "N",
				function callBack(data) {

					if (data == 1) {
						justep.Util.hint("录入成功！", {
							"delay" : normerHintDelay,
						});
						localStorage.setItem("is_flesh_instruction_list", "true");
						clearInput(me);
						return;
					}
					if (data == 9) {
						// 弹出对话框
						me.comp("messageDialog1").show({
							"title" : "温馨提示",
							"message" : "存在作业数据，是否覆盖？"
						});
					}
				});

	};

	/**
	 * 覆盖数据确定
	 */
	Model.prototype.messageDialog1Yes = function(event) {
		var me = this;
		To_Reckon(localStorage.getItem("instruction_id"), localStorage.getItem("ship_id"), me.cangId, me.solt, me.rongOrdi, me.ullage, me.sounding, me.temp, me.quantity, me.Laltitudeheight, "Y",
				function callBack(data) {
					justep.Util.hint("覆盖成功！", {
						"delay" : normerHintDelay,
					});
					clearInput(me);
					return;
				});
	};
	
	
	/**
	 * 加载舱数据
	 */
	function loadData(me) {
		To_WorkingDetail(localStorage.getItem("instruction_id"), function callBack(data, data2) {
			LoadCangData(me, data2);
		});
	}
	
	
	function LoadCangData(me, data) {
		var cangData = me.comp("cangData");
		var AllData = '[';
		for (var int = 0; int < data.length; int++) {
			var Cangitem = '{';
			for (var int2 = 0; int2 < data[int].length; int2++) {
				var sounding = data[int][int2]['sounding'];
				var ullage = data[int][int2]['ullage'];
				var listcorrection = data[int][int2]['listcorrection'];
				var correntkong = data[int][int2]['correntkong'];
				var temperature = data[int][int2]['temperature'];
				var standardcapacity = data[int][int2]['standardcapacity'];
				if (sounding == null) {
					sounding = "";
				}
				if (ullage == null) {
					ullage = "";
				}
				if (listcorrection == null) {
					listcorrection = "";
				}
				if (correntkong == null) {
					correntkong = "";
				}
				if (temperature == null) {
					temperature = "";
				}
				if (standardcapacity == null) {
					standardcapacity = "";
				}

				if (data[int][int2]['solt'] == 1) {
					// 存在作业后数据，否则不存在,这时候要重新构建数据
					var Cangitem = Cangitem + "\"resultid\":\"" + data[int][int2]['resultid'] + "\",\"cabinname\":\"" + data[int][int2]['cabinname'] + "\",\"sounding\":\"" + sounding
							+ "\",\"ullage\":\"" + ullage + "\",\"listcorrection\":\"" + listcorrection + "\",\"correntkong\":\"" + correntkong + "\",\"temperature\":\"" + temperature
							+ "\",\"standardcapacity\":\"" + standardcapacity + "\"";

				}
				if (data[int][int2]['solt'] == 2) {
					var Cangitem = Cangitem + "\"xcabinname\":\"" + data[int][int2]['cabinname'] + "\",\"xsounding\":\"" + sounding + "\",\"xullage\":\"" + ullage + "\",\"xlistcorrection\":\""
							+ listcorrection + "\",\"xcorrentkong\":\"" + correntkong + "\",\"xtemperature\":\"" + temperature + "\",\"xstandardcapacity\":\"" + standardcapacity + "\"";
				}

				if (data[int].length > 1 && int2 == 0) {
					Cangitem = Cangitem + ",";
				}
			}
			// 整个各舱数据，统一放进json数组里，结束后加载舱列表
			if (int == data.length - 1) {
				Cangitem = Cangitem + '}';
			} else {
				Cangitem = Cangitem + '},';
			}
			AllData = AllData + Cangitem;
		}
		AllData = AllData + ']';
		cangData.loadData(JSON.parse(AllData));
	}

	// 按钮的选定底边框的改变
	function changeBorderColor(contentNum, me) {
		switch (contentNum) {
		case 0:
			me.getElementByXid("do1").style.borderColor = "#fdd120";
			me.getElementByXid("do2").style.color = "#FFFFFF";
			me.comp('contents1').to(0);
			break;
		case 1:
			me.getElementByXid("do1").style.borderColor = "#FFFFFF";
			me.getElementByXid("do2").style.color = "#fdd120";
			me.comp('contents1').to(1);
			break;

		default:
			me.getElementByXid("do1").style.borderColor = "#fdd120";
			me.getElementByXid("do2").style.color = "#FFFFFF";
			me.comp('contents1').to(0);
			break;
		}
	}

	/**
	 * json解析为数组
	 */
	function DataToJsonArray(me, data) {
		for (var int = 0; int < data.length; int++) {
			me.cangIdArray.push(data[int]['id']);
			me.altitudeheightArray.push(data[int]['altitudeheight']);
			me.dialtitudeheightArray.push(data[int]['dialtitudeheight']);
		}
	}

	/**
	 * 初始化数据
	 */
	function InitData(me) {
		me.solt = localStorage.getItem("solt");
		me.cangId = "";
		me.rongOrdi = "rongliang";
		me.quantity = "1";
		me.position = -1;
		me.cangIdArray.slice(0, me.cangIdArray.length);
		me.altitudeheightArray.slice(0, me.altitudeheightArray.length);
		me.dialtitudeheightArray.slice(0, me.dialtitudeheightArray.length);
		this.altitudeheight = -1;
		this.dialtitudeheight = -1;
		this.Height = -1;
		clearInput(me);
		if (localStorage.getItem("is_diliang") == "1") {
			$("#one").css("display", "block");
			$("#two").css("display", "none");
		} else {
			$("#two").css("display", "block");
			$("#one").css("display", "none");
		}
	}

	function clearInput(me) {
		clearHeight(me);
		me.getElementByXid("input_temp").value = "";
	}

	/**
	 * 清除空高和实高
	 */
	function clearHeight(me) {
		me.getElementByXid("input_ullage").value = "";
		me.getElementByXid("input_sounding").value = "";
	}

	/**
	 * 给对象赋值基准高度
	 */
	function SetAltitudeOrDialtitudeHeight(me) {
		if (me.rongOrdi == "rongliang" && me.position != -1) {
			me.altitudeheight = Number(me.altitudeheightArray[me.position]);
		} else {
			me.dialtitudeheight = Number(me.dialtitudeheightArray[me.position]);
		}
	}

	/**
	 * 自动计算空、实高
	 */
	function autoHeight(me, input1Name, input2Name) {
		me.Height = Number(me.getElementByXid(input1Name).value);
		if (isNaN(me.Height)) {
			showToast("请输入正确数值!");
			return;
		}
		if (me.rongOrdi = "rongliang") {
			if (me.Height > me.altitudeheight) {
				showToast("输入值超过基准高度!");
				me.getElementByXid(input2Name).value = "";
				return;
			} else {
				me.getElementByXid(input2Name).value = (me.altitudeheight - me.Height).toFixed(4); 
			}

		} else {
			if (me.Height > me.dialtitudeheight) {
				showToast("输入值超过基准高度!");
				me.getElementByXid(input2Name).value = "";
				return;
			} else {
				me.getElementByXid(input2Name).value = (me.dialtitudeheight - me.Height).toFixed(4);
			}
		}
	}

	/**
	 * 获取仓数据
	 */
	function getCangData(me) {
		var cangData = me.comp("cangData1");
		var cangDataModer = me.comp("cangDataModer");
		To_GetCabinList(localStorage.getItem("ship_id"), function callBack(data) {
			DataToJsonArray(me, data);
			var value = data[0]['id'];
			cangData.loadData(data);
			cangDataModer.setValue("id", value);
			me.cangId = value;
		});
	}

	function showToast(msg) {
		justep.Util.hint(msg, {
			"delay" : normerHintDelay,
			"style" : "color:red;"
		});
	}

	Model.prototype.backBtnClick = function(event) {
		justep.Shell.showPage("Draft");
	};

	return Model;
});