define(function(require) {
	var $ = require("jquery");
	var justep = require("$UI/system/lib/justep");
	require("cordova!org.apache.cordova.file");
	require("cordova!org.apache.cordova.file-transfer");
	require("cordova!nl.x-services.plugins.socialsharing");

	var Model = function() {
		this.callParent();
		this._authority = window.PERSISTENT;// 默认权限，持久性文件
	};

	Model.prototype.backBtnClick = function(event) {
		justep.Shell.showPage("selectResult");
	};

	Model.prototype.modelActive = function(event) {
		clearInput(this);
		loadData(this);
	};

	/**
	 * 加载数据
	 */
	function loadData(me) {
		To_WorkingDetail(localStorage.getItem("instruction_id"), function callBack(data, data2) {
			me.getElementByXid("ship_name").innerText = data['shipname'];
			me.getElementByXid("voyage").innerText = data['voyage'];
			me.getElementByXid("locationname").innerText = data['locationname'];
			me.getElementByXid("start").innerText = data['start'];
			me.getElementByXid("objective").innerText = data['objective'];
			me.getElementByXid("goodsname").innerText = data['goodsname'];
			me.getElementByXid("transport").innerText = data['transport'];
			me.getElementByXid("weight").innerText = data['weight'];
			me.getElementByXid("username").innerText = data['username'];
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

	function clearInput(me) {
		me.getElementByXid("ship_name").innerText = "";
		me.getElementByXid("voyage").innerText = "";
		me.getElementByXid("locationname").innerText = "";
		me.getElementByXid("start").innerText = "";
		me.getElementByXid("objective").innerText = "";
		me.getElementByXid("goodsname").innerText = "";
		me.getElementByXid("transport").innerText = "";
		me.getElementByXid("weight").innerText = "";
		me.getElementByXid("username").innerText = "";
	}

	// 报表打印事件
	Model.prototype.btn_printClick = function(event) {
		var me = this;
		To_getFile(localStorage.getItem("instruction_id"), function callBack(filename) {
			downloadPDF(me, filename);
		});
	};

	/**
	 * pdf文件下载
	 */
	function downloadPDF(me, filename) {
		var filePath = 'cdvfile://localhost/persistent/capacity_PDF/';
		var url = encodeURI("http://121.41.22.2/ship/Public/pdf/" + filename);
		var fileTransfer = new FileTransfer();
		var newFileName = me.getElementByXid("ship_name").innerText + me.getElementByXid("voyage").innerText + ".pdf";
		// 调用对象的下载方法，开始下载
		fileTransfer.download(url, filePath + newFileName, function(entry) {
			ToastMsg("PDF下载完成", "false");
			// cdvfile文件转换为通常文件路径
			resolveLocalFileSystemURL(filePath + newFileName, function(entry) {
				var nativePath = entry.toURL();
				//alert(nativePath);
				plugins.socialsharing.share(null, null, nativePath, null);
			});
			// 分享文件通常
			// alert(filePath + newFileName);
			// plugins.socialsharing.share(null, null, filePath + newFileName,
			// null);
			// 指定分享
			// plugins.socialsharing.canShareVia("jp.co.canon.bsd.ad.pixmaprint.EulaActivity",
			// null, nativePath, null);
		}, function(error) {
			// 出错回调函数
			ToastMsg("下载失败！！！", "true");
		}, false, {
			headers : {
				"Authorization" : "Basic dGVzdHVzZXJuYW1lOnRlc3RwYXNzd29yZA=="
			}
		});
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