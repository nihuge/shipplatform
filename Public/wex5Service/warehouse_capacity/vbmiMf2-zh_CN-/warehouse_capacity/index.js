define(function(require) {
	require("$UI/warehouse_capacity/ajax/jquery-1");
	var justep = require("$UI/system/lib/justep");
	var ShellImpl = require('$UI/system/lib/portal/shellImpl');

	var Model = function() {
		this.callParent();
		var shellImpl = new ShellImpl(this, {
			"contentsXid" : "pages",
			"pageMappings" : {

				"mainActivity" : {
					url : require.toUrl('./mainActivity.w')
				},
				"login" : {
					// 登录页面
					url : require.toUrl('./LoginActivity.w')
				},
				"InstructionDetail" : {
					// 指令详情页面
					url : require.toUrl('./InstructionDetail.w')
				},
				"Draft" : {
					// 水尺页面
					url : require.toUrl('./DraftActivity.w')
				},
				"Reckon" : {
					// 舱录入界面
					url : require.toUrl('./ReckonActivity.w')
				},
				"selectResult" : {
					// 查询指令结果页面
					url : require.toUrl('./selectResultActivity.w')
				},"WorkedDetail" : {
					// 查询指令结果页面
					url : require.toUrl('./WorkedDetailActivity.w')
				},"UpdataPwd" : {
					// 查询指令结果页面
					url : require.toUrl('./UpdataPwdActivity.w')
				}

			}
		});
	};

	return Model;
});