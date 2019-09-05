define(function(require) {
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");

	var Model = function() {
		this.callParent();
		this.ship_id = "";
	};

	Model.prototype.modelActive = function(event) {
		if (localStorage.getItem("is_add") == "true") {
			SetTitle(this, "新 增 作 业", "下一步");
			is_gone(this, "true");
			clearInput(this);
		} else {
			if (localStorage.getItem("is_updata") == "true") {
				SetTitle(this, "修 改 作 业", "修 改");
			} else {
				SetTitle(this, "作 业 详 情", "");
				// $("#div_btn").css("display", "none");
			}
			is_gone(this, localStorage.getItem("is_updata"));
			loadData(this);
		}

	};

	/**
	 * 加载数据
	 */
	function loadData(me) {
		To_WorkingDetail(localStorage.getItem("instruction_id"), function callBack(data) {
			me.getElementByXid("input_ship_name").value = data['shipname'];
			me.getElementByXid("input_vovy").value = data['voyage'];
			me.getElementByXid("input_location").value = data['locationname'];
			me.getElementByXid("input_cago_name").value = data['goodsname'];
			me.getElementByXid("input_yundan").value = data['transport'];
			me.getElementByXid("input_cago_weight").value = data['weight'];
			me.getElementByXid("input_begin_location").value = data['start'];
			me.getElementByXid("input_end_location").value = data['objective'];

		});
	}

	function clearInput(me) {
		me.getElementByXid("input_ship_name").value = "";
		me.getElementByXid("input_vovy").value = "";
		me.getElementByXid("input_location").value = "";
		me.getElementByXid("input_cago_name").value = "";
		me.getElementByXid("input_yundan").value = "";
		me.getElementByXid("input_cago_weight").value = "";
		me.getElementByXid("input_begin_location").value = "";
		me.getElementByXid("input_end_location").value = "";
	}

	// 设置title
	function SetTitle(me, titleName, btnName) {
		this.ship_id = ""
		me.comp('titleBar1').set({
			'title' : titleName
		});

		me.comp('btn_updata_or_add').set({
			label : btnName
		})
	}

	// 控件的显示或隐藏
	function is_gone(me, goen) {
		if (goen == "true") {
			//divhight(400);
			$("#ship_name_spanner").css("display", "block");
			$("#ship_name_input").css("display", "none");
			$("#div_cago_weight").css("display", "none");
			$("#div_btn_instruction").css("display", "block");
			// 加载spanner数据
			GetShipNameListToSpanner(me);
		} else {
			//divhight(440);
			$("#ship_name_spanner").css("display", "none");
			$("#ship_name_input").css("display", "block");
			$("#div_cago_weight").css("display", "block");
			$("#div_btn_instruction").css("display", "none");
		}
	}

	// 获取船列表
	function GetShipNameListToSpanner(me) {
		To_getShip(function callBack(data) {
			var shipData = me.comp("shipData");
			shipData.loadData(data);
			if (localStorage.getItem("is_updata") == "true") {
				me.comp("shipDataModer").setValue("id", localStorage.getItem("ship_id"));
			} else {
				me.comp("shipDataModer").setValue("id", "");
			}
		});
	}

	// 返回事件
	Model.prototype.backBtnClick = function(event) {
		justep.Shell.showPage("mainActivity");
	};

	/**
	 * 下拉框值的改变监听
	 */
	Model.prototype.selectChange = function(event) {
		this.ship_id = this.getElementByXid("demoSelect").value;
	};

	/**
	 * 修改、新增事件
	 */
	Model.prototype.btn_updata_or_addClick = function(event) {
		var me = this;
		var shipid = me.ship_id;
		var locationname = me.getElementByXid("input_location").value;
		var voyage = me.getElementByXid("input_vovy").value;
		var start = me.getElementByXid("input_begin_location").value;
		var objective = me.getElementByXid("input_end_location").value;
		var goodsname = me.getElementByXid("input_cago_name").value;
		var transport = me.getElementByXid("input_yundan").value;
		var cago_weight = me.getElementByXid("input_cago_weight").value;

		if (shipid == "") {
			showToast("请选择船舶！");
			return;
		}
		if (voyage == "") {
			showToast("请输入航次！");
			return;
		}
		if (locationname == "") {
			showToast("请输入作业地点！");
			return;
		}

		if (goodsname == "") {
			showToast("请输入货名！");
			return;
		}

		if (transport == "") {
			showToast("请输入运单量！");
			return;
		}

		if (start == "") {
			showToast("请输入起运港！");
			return;
		}

		if (objective == "") {
			showToast("请输入目的港！");
			return;
		}

		if (localStorage.getItem("is_add") == "true") {
			To_AddWorking(shipid, locationname, voyage, start, objective, goodsname, transport, function callBack(data) {
				localStorage.setItem("is_flesh_instruction_list", "true");
				localStorage.setItem("instruction_id", data['resultid']);
				localStorage.setItem("is_diliang", data['d']);
				localStorage.setItem("ship_id", shipid);
				justep.Shell.showPage("Draft");
			});
		} else {
			To_UpdataWorking(localStorage.getItem("instruction_id"), shipid, locationname, voyage, start, objective, goodsname, transport, function callBack(data) {
				justep.Util.hint("修改成功！", {
					"delay" : normerHintDelay,
				});
				localStorage.setItem("is_flesh_instruction_list", "true");
				justep.Shell.showPage("mainActivity");
			});

		}

	};

	function showToast(msg) {
		justep.Util.hint(msg, {
			"delay" : normerHintDelay,
			"style" : "color:red;"
		});
	}

	function divhight(height) {
		var o = document.getElementById('maiDiv');
		// var H = o.offsetHeight;// 获得原始宽
		o.style.height = height + 'px';// 设置宽度
	}

	return Model;
});