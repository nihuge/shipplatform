define(function(require) {
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");

	var Model = function() {
		this.callParent();
		this.ship_name = "";
		this.voyage = "";
		this.locationname = "";
		this.begintime = "";
		this.endtime = "";
		// 计算list数据大小
		this.ListDataLenght = 0;
		// 下拉条目大小
		this.pullNum = 5;
	};

	Model.prototype.modelActive = function(event) {
		InitTermData(this);
		this.ListDataLenght = 0;
		getData(this);
	};

	/**
	 * 获取查询条件
	 */
	function InitTermData(me) {
		me.ship_name = localStorage.getItem("select_ship_name");
		me.voyage = localStorage.getItem("select_voyage");
		me.locationname = localStorage.getItem("select_locationname");
		me.begintime = localStorage.getItem("select_begintime");
		me.endtime = localStorage.getItem("select_endtime");
	}

	/**
	 * 获取指令列表并展现
	 */
	function getData(me) {
		To_InstructionList(me.ship_name, me.voyage, me.begintime, me.endtime, "1", me.locationname, function callBack(data) {
			me.ListDataLenght = data.length;
			var homework_instructions = me.comp("homework_instructions");
			homework_instructions.loadData(data);
		});
	}

	/**
	 * 指令列表点击事件
	 */
	Model.prototype.li1Click = function(event) {
		localStorage.setItem("instruction_id", event.bindingContext.$object.val('id'));
		// alert(localStorage.getItem("instruction_id"));
		justep.Shell.showPage("WorkedDetail");
	};

	/**
	 * 上拉加载
	 */
	Model.prototype.scrollView1PullDown = function(event) {
		getData(this);
	};

	/**
	 * 下拉刷新
	 */
	Model.prototype.scrollView1PullUp = function(event) {
		var me = this;
		if (me.ListDataLenght > 0) {
			var CList = me.ListDataLenght / me.pullNum;// 除是判断页数
			var YList = me.ListDataLenght % me.pullNum;// 余是判断后续时候还有数据
			if (YList == 0) {
				To_InstructionList(me.ship_name, me.voyage, me.begintime, me.endtime, CList + 1, me.locationname, function callBack(data) {
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

	Model.prototype.backBtnClick = function(event) {
		justep.Shell.showPage("mainActivity");
	};

	return Model;
});