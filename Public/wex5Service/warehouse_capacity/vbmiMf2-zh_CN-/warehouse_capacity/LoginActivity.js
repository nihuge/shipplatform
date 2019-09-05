define(function(require) {
	require("cordova!cordova-plugin-device");
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");

	var Model = function() {
		this.callParent();
	};

	Model.prototype.modelLoad = function(event) {
		this.getElementByXid("userName").value = localStorage.getItem("userName");
		this.getElementByXid("userPwd").value = localStorage.getItem("userPwd");
	};

	/**
	 * 登录
	 */
	Model.prototype.btn_loginClick = function(event) {
		// 保存设备的UUID，后续操作均需要
		localStorage.setItem("UUID", device.uuid);
		var userName = this.getElementByXid("userName").value;
		var userPwd = this.getElementByXid("userPwd").value;
		if (userName == "" || userPwd == "") {
			justep.Util.hint("用户名或密码不能为空！", {
				"delay" : normerHintDelay,
				"style" : "color:red;"
			});
		} else {
			To_login(userName, userPwd, function LoginCallBack(id, title, userName, pwd) {
				justep.Util.hint("登录成功！", {
					"delay" : normerHintDelay,
				});
				// 用户id
				localStorage.setItem("id", id);
				// 用户名称
				localStorage.setItem("userName", title);
				// 用户名
				localStorage.setItem("title", userName);
				// 用户密码
				localStorage.setItem("userPwd", pwd);
				justep.Shell.closePage();
				// 刷新作业指令列表
				localStorage.setItem("is_flesh_instruction_list", "true");
				// 跳转到主界面
				justep.Shell.showPage("mainActivity");
			});
		}

	};

	/**
	 * 登录成功回调
	 */

	return Model;
});