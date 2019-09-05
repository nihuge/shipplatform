define(function(require) {
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");
	require("cordova!uk.co.whiteoctober.cordova.appversion");
	var Model = function() {
		this.callParent();
		// 将滑动和点击时间区别开来
		this.isHuaDong = true;
		// 作业指令界面
		this.isFistLoadData = true;
		// 计算list数据大小
		this.ListDataLenght = 0;
		// 下拉条目大小
		this.pullNum = 5;
	};
	// 初始化界面
	Model.prototype.modelLoad = function(event) {
		// 隐藏新增作业按钮
		$("#add_working").css("display", "none");
		this.isFistLoadData = true;
	};

	Model.prototype.modelActive = function(event) {

		if (localStorage.getItem("is_quit") == "true") {
			localStorage.setItem("is_quit", "false");
			justep.Shell.showPage("login");

		} else {
			if (localStorage.getItem("is_flesh_instruction_list") == "true") {
				this.ListDataLenght = 0;
				this.isFistLoadData = true;
				getInstruction(this);
			}
			localStorage.setItem("is_flesh_instruction_list", "fale");
		}

	};

	// ----------------------------------------------------------------------------------首页begin
	// 跳转到作业指令列表
	Model.prototype.div_workingClick = function(event) {
		this.comp('contents1').to(1);
	};
	// 跳转到查询
	Model.prototype.div_selectClick = function(event) {
		this.comp('contents1').to(2);
	};

	// 跳转到个人中心
	Model.prototype.div_ownClick = function(event) {
		this.comp('contents1').to(3);
	};

	// ----------------------------------------------------------------------------------首页end

	// ----------------------------------------------------------------------------------作业指令列表begin

	Model.prototype.scrollView1PullUp = function(event) {
		var me = this;
		me.isFistLoadData = true;
		if (me.ListDataLenght > 0) {
			var CList = me.ListDataLenght / me.pullNum;// 除是判断页数
			var YList = me.ListDataLenght % me.pullNum;// 余是判断后续时候还有数据
			if (YList == 0) {
				To_InstructionList("", "", "", "", CList + 1, "", function callBack(data) {
					if (data.length > 0) {
						me.ListDataLenght = me.ListDataLenght + data.length;
						var homework_instructions = me.comp("homework_instructions");
						homework_instructions.loadData(data, true);
					} else {
						ToastMsg("没有啦，亲~", "false");
					}
				});
			} else {
				ToastMsg("没有啦，亲~", "false");
			}
		}
	};
	// 作业列表上拉刷新
	Model.prototype.scrollView1PullDown = function(event) {
		this.isFistLoadData = true;
		getInstruction(this);
	};

	// 获取作业列表
	function getInstruction(me) {
		if (me.isFistLoadData) {
			To_InstructionList("", "", "", "", "1", "", function callBack(data) {
				me.ListDataLenght = data.length;
				var homework_instructions = me.comp("homework_instructions");
				homework_instructions.loadData(data);
			});
		}

		me.isFistLoadData = false;
	}
	// 新增作业指令
	Model.prototype.add_workingClick = function(event) {
		localStorage.setItem("is_add", "true");
		localStorage.setItem("is_updata", "fale");
		localStorage.setItem("is_detail", "fale");
		justep.Shell.showPage("InstructionDetail");
	};

	// 作业指令详情
	Model.prototype.btn_detailClick = function(event) {
		// 获取当前船id、shipid等
		localStorage.setItem("is_add", "fale");
		localStorage.setItem("is_updata", "fale");
		localStorage.setItem("is_detail", "true");
		localStorage.setItem("instruction_id", event.bindingContext.$object.val('id'));
		localStorage.setItem("ship_id", event.bindingContext.$object.val('shipid'));
		localStorage.setItem("is_diliang", event.bindingContext.$object.val('is_diliang'));
		justep.Shell.showPage("InstructionDetail");

	};
	// 修改作业指令
	Model.prototype.btn_updataClick = function(event) {
		var is_worked = Number(event.bindingContext.$object.val('nums'));
		if (is_worked > 0) {
			codeMsg(12);
		} else {
			localStorage.setItem("is_add", "fale");
			localStorage.setItem("is_updata", "true");
			localStorage.setItem("is_detail", "fale");
			localStorage.setItem("instruction_id", event.bindingContext.$object.val('id'));
			localStorage.setItem("ship_id", event.bindingContext.$object.val('shipid'));
			justep.Shell.showPage("InstructionDetail");
		}
	};

	// 作业录入
	Model.prototype.btn_inputClick = function(event) {
		localStorage.setItem("instruction_id", event.bindingContext.$object.val('id'));
		localStorage.setItem("ship_id", event.bindingContext.$object.val('shipid'));
		localStorage.setItem("is_diliang", event.bindingContext.$object.val('is_diliang'));
		justep.Shell.showPage("Draft");
	};

	// ----------------------------------------------------------------------------------作业指令列表首页end

	// ----------------------------------------------------------------------------------查询begin

	Model.prototype.btn_selectClick = function(event) {
		var me = this;
		localStorage.setItem("select_ship_name", me.getElementByXid("select_ship_name").value);
		localStorage.setItem("select_voyage", me.getElementByXid("select_voyage").value);
		localStorage.setItem("select_locationname", me.getElementByXid("select_locationname").value);
		localStorage.setItem("select_begintime", me.getElementByXid("select_begintime").value);
		localStorage.setItem("select_endtime", me.getElementByXid("select_endtime").value);
		justep.Shell.showPage("selectResult");
	};

	// 起始时间选择
	Model.prototype.btn_calendar_begintimeClick = function(event) {
		$('#input1').shijian();

	};

	// 结束时间选择
	Model.prototype.btn_calendar_endtimeClick = function(event) {
		// justep.Shell.showPage("login");
	};

	// 清除输入框内容
	function clearInput(me) {
		me.getElementByXid("select_ship_name").value = "";
		me.getElementByXid("select_voyage").value = "";
		me.getElementByXid("select_locationname").value = "";
		me.getElementByXid("select_begintime").value = "";
		me.getElementByXid("select_endtime").value = "";
		// me.comp("date").val("20180101");
	}

	// ----------------------------------------------------------------------------------查询end

	// ----------------------------------------------------------------------------------个人中心begin

	function initOwnData(me) {
		// 用户名
		me.getElementByXid("own_userName").innerText = localStorage.getItem("title");
	}

	Model.prototype.div_quitClick = function(event) {
		this.comp("messageDialog1").show({
			"title" : "温馨提示",
			"message" : "确认退出" + localStorage.getItem("title") + "账号？"
		});
	};

	Model.prototype.div_updata_pwdClick = function(event) {
		justep.Shell.showPage("UpdataPwd");
	};

	Model.prototype.div_clear_cacheClick = function(event) {
		var me = this;
		cordova.getAppVersion.getVersionNumber(function(version) {
			me.comp("messageDialog2").show({
				"title" : "版本信息",
				"message" : "液货计量1.0.1"
			});
		});

	};

	Model.prototype.messageDialog1Yes = function(event) {
		localStorage.setItem("userPwd", "");
		justep.Shell.showPage("login");
	};
	// ----------------------------------------------------------------------------------个人中心end

	// 底部按钮事件，图片的切换
	Model.prototype.bottom_indexClick = function(event) {
		ChangedIcon(0, this);
		this.isHuaDong = false;
	};

	Model.prototype.bottom_workingClick = function(event) {
		ChangedIcon(1, this);
		this.isHuaDong = false;
	};

	Model.prototype.bottom_selectClick = function(event) {
		ChangedIcon(2, this);
		this.isHuaDong = false;
	};

	Model.prototype.bottom_ownClick = function(event) {
		ChangedIcon(3, this);
		this.isHuaDong = false;
	};

	// contents滑动事件
	Model.prototype.contents1ActiveChanged = function(event) {
		if (this.isHuaDong) {
			var contents = this.comp("contents1");
			var active = contents.get('active');
			ChangedIcon(active, this);
		}
		this.isHuaDong = true;
	};
	/**
	 * 底部按钮点击后调用，用于切换底部图片，加载对应页面的数据等
	 */
	function ChangedIcon(active, me) {
		var bottom_index = me.comp("bottom_index");
		var bottom_working = me.comp("bottom_working");
		var bottom_select = me.comp("bottom_select");
		var bottom_own = me.comp("bottom_own");
		switch (active) {
		case 0:
			SetTitle(me, active, "液 货 计 量");
			bottom_index.set({
				"icon" : "img:$UI/warehouse_capacity/img/home.png|"
			});
			bottom_working.set({
				"icon" : "img:$UI/warehouse_capacity/img/zuoye_none.png|"
			});
			bottom_select.set({
				"icon" : "img:$UI/warehouse_capacity/img/search_none.png|"
			});
			bottom_own.set({
				"icon" : "img:$UI/warehouse_capacity/img/me_none.png|"
			});
			break;
		case 1:
			SetTitle(me, active, "作 业 列 表");
			bottom_index.set({
				"icon" : "img:$UI/warehouse_capacity/img/home_none.png|"
			});
			bottom_working.set({
				"icon" : "img:$UI/warehouse_capacity/img/zuoye.png|"
			});
			bottom_select.set({
				"icon" : "img:$UI/warehouse_capacity/img/search_none.png|"
			});
			bottom_own.set({
				"icon" : "img:$UI/warehouse_capacity/img/me_none.png|"
			});
			getInstruction(me);
			break;
		case 2:
			SetTitle(me, active, "查 询 筛 选");
			bottom_index.set({
				"icon" : "img:$UI/warehouse_capacity/img/home_none.png|"
			});
			bottom_working.set({
				"icon" : "img:$UI/warehouse_capacity/img/zuoye_none.png|"
			});
			bottom_select.set({
				"icon" : "img:$UI/warehouse_capacity/img/search.png|"
			});
			bottom_own.set({
				"icon" : "img:$UI/warehouse_capacity/img/me_none.png|"
			});
			clearInput(me);
			break;
		case 3:
			SetTitle(me, active, "个 人 中 心");
			bottom_index.set({
				"icon" : "img:$UI/warehouse_capacity/img/home_none.png|"
			});
			bottom_working.set({
				"icon" : "img:$UI/warehouse_capacity/img/zuoye_none.png|"
			});
			bottom_select.set({
				"icon" : "img:$UI/warehouse_capacity/img/search_none.png|"
			});
			bottom_own.set({
				"icon" : "img:$UI/warehouse_capacity/img/me.png|"
			});
			initOwnData(me);
			break;

		default:
			SetTitle(me, active, "液 货 计 量");
			bottom_index.set({
				"icon" : "img:$UI/warehouse_capacity/img/home.png|"
			});
			bottom_working.set({
				"icon" : "img:$UI/warehouse_capacity/img/zuoye_none.png|"
			});
			bottom_select.set({
				"icon" : "img:$UI/warehouse_capacity/img/search_none.png|"
			});
			bottom_own.set({
				"icon" : "img:$UI/warehouse_capacity/img/me_none.png|"
			});
			break;
		}
	}

	// 设置title
	function SetTitle(me, active, titleName) {
		me.comp('titleBar1').set({
			'title' : titleName
		});
		// 显示和隐藏新增作业按钮
		if (active == 1) {
			$("#add_working").css("display", "block");
		} else {
			$("#add_working").css("display", "none");
		}
	}

	/**
	 * 弹出信息
	 * 
	 * @param msg
	 */
	function ToastMsg(msg, isWORRY) {
		if (isWORRY == "true") {
			justep.Util.hint(msg, {
				"delay" : normerHintDelay,
				"style" : "color:red;"
			});
		} else {
			justep.Util.hint(msg, {
				"delay" : normerHintDelay,
			});
		}

	}

	return Model;
});