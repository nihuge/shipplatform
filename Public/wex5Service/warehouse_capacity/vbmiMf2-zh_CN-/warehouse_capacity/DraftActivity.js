define(function(require) {
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");

	var Model = function() {
		this.callParent();
		this.clearFirst = "true";
		this.is_fugai = "false";
		this.Upsolt = "";
		this.solt = "1";
		this.forntleft = "";
		this.forntright = "";
		this.afterleft = "";
		this.afterright = "";
		this.denty = "";
		this.temp = "15℃";
	};
	Model.prototype.modelActive = function(event) {
		this.clearFirst = "true";
		this.is_fugai = "false";
		this.solt = "1";
		InitData(this);
	};

	Model.prototype.backBtnClick = function(event) {
		justep.Shell.showPage("mainActivity");
	};

	/**
	 * 温度选择下拉框
	 */
	Model.prototype.selectChange = function(event) {
		this.temp = this.getElementByXid("demoSelect").value;
	};

	// 初始化数据
	function InitData(me) {
		clearInput(me);
		// this.temp = "";
		loadDataHistory(me);
	}

	// 清除input组件数据
	function clearInput(me) {
		me.getElementByXid("input_draft_forntleft").value = "";
		me.getElementByXid("input_draft_forntright").value = "";
		me.getElementByXid("input_draft_afterleft").value = "";
		me.getElementByXid("input_draft_afterright").value = "";
		me.getElementByXid("input_draft_denty").value = "";
	}

	// 加载历史数据
	function loadDataHistory(me) {
		To_ForntSearch(localStorage.getItem("instruction_id"), function callBack(data) {
			me.getElementByXid("input_draft_forntleft").value = data['forntleft'];
			me.getElementByXid("input_draft_forntright").value = data['forntright'];
			me.getElementByXid("input_draft_afterleft").value = data['afterleft'];
			me.getElementByXid("input_draft_afterright").value = data['afterright'];
			if (data['solt'] == "1") {
				me.getElementByXid("input_draft_denty").value = data['qiandensity'];
				me.temp = data['qiantemperature'];
				me.comp('data2').setValue("fValue", data['solt']);
				me.is_fugai = "true";
			} else if (data['solt'] == "2") {
				me.getElementByXid("input_draft_denty").value = data['houdensity'];
				me.temp = data['houtemperature'];
				me.comp('data2').setValue("fValue", data['solt']);
				me.is_fugai = "true";
			} else {
				me.comp('data2').setValue("fValue", "1");
			}
			me.Upsolt = data['solt'];

		});
	}

	// 下一步
	Model.prototype.btn_nextClick = function(event) {
		var me = this;
		me.forntleft = me.getElementByXid("input_draft_forntleft").value;
		me.forntright = me.getElementByXid("input_draft_forntright").value;
		me.afterleft = me.getElementByXid("input_draft_afterleft").value;
		me.afterright = me.getElementByXid("input_draft_afterright").value;
		me.denty = me.getElementByXid("input_draft_denty").value;

		if (me.forntleft == "" && me.forntright == "") {
			showToast("前水尺请选填一项！");
			return;
		}
		if (me.afterleft == "" && me.afterright == "") {
			showToast("后水尺请选填一项！");
			return;
		}
		if (me.denty == "") {
			showToast("密度不能为空！");
			return;
		}
		if (me.temp == "") {
			showToast("请选择标准温度！");
			return;
		}
		// 上传之前判断是否要覆盖,弹出对话框的形式
		if (me.is_fugai == "true") {
			var msg = "";
			if (me.solt == "1") {
				msg = "存在作业前水尺数据，是否覆盖？";
			} else {
				msg = "存在作业后水尺数据，是否覆盖？";
			}
			me.comp("messageDialog1").show({
				"title" : "温馨提示",
				"message" : msg
			});
		} else {
			UpIntoDraf(me);
		}
	};

	/**
	 * 上传水尺数据
	 */
	function UpIntoDraf(me) {
		To_Draft(localStorage.getItem("instruction_id"), me.solt, me.forntleft, me.forntright, me.afterleft, me.afterright, me.denty, me.temp, function callBack(data) {
			localStorage.setItem("solt", me.solt);
			justep.Shell.showPage("Reckon");
		});
	}

	/**
	 * 是否覆盖对话框 覆盖监听
	 */
	Model.prototype.messageDialog1Yes = function(event) {
		UpIntoDraf(this);
	};

	function showToast(msg) {
		justep.Util.hint(msg, {
			"delay" : normerHintDelay,
			"style" : "color:red;"
		});
	}

	/**
	 * 监听单选框按钮值的改变
	 */
	Model.prototype.dataValueChange = function(event) {
		if (this.Upsolt != event.newValue) {
			this.clearFirst = "false";
		}
		if (this.clearFirst == "true") {
			this.clearFirst = "false";
		} else if (this.clearFirst == "false" && this.is_fugai == "true") {
			this.is_fugai = "false";
			clearInput(this);
		}

		this.solt = event.newValue;
	};

	return Model;
});