define(function(require) {
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");

	var Model = function() {
		this.callParent();
	};

	Model.prototype.backBtnClick = function(event) {
		justep.Shell.showPage("mainActivity");
	};
	Model.prototype.modelActive = function(event) {
		ClearInput(this);
	};

	Model.prototype.updata_pwdClick = function(event) {
		var me = this;
		var oldPwd = me.getElementByXid("userPwd").value;
		var password1 = me.getElementByXid("password1").value;
		var password2 = me.getElementByXid("password2").value;

		if (oldPwd == "") {
			ToastMsg("旧密码不能为空！", "true");
			return;
		}
		if (password1 == "") {
			ToastMsg("新密码不能为空！", "true");
			return;
		}
		if (password2 == "") {
			ToastMsg("确认密码不能为空！", "true");
			return;
		}

		To_ChangePwd(oldPwd, password1, password2, function callBack() {
			ToastMsg("修改成功！", "false");
			// 跳转到登录界面
			// justep.Shell.loadPage("UI2/warehouse_capacity/LoginActivity.w")
			localStorage.setItem("is_quit", "true");
			localStorage.setItem("userPwd", "");
			justep.Shell.showPage("mainActivity");

		});

	};

	function ClearInput(me) {
		me.getElementByXid("userPwd").value = "";
		me.getElementByXid("password1").value = "";
		me.getElementByXid("password2").value = "";
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