window.__justep.__ResourceEngine.loadCss([{url: '/system/components/comp.min.css', include: '$model/system/components/justep/row/css/row,$model/system/components/justep/attachment/css/attachment,$model/system/components/justep/barcode/css/barcodeImage,$model/system/components/bootstrap/form/css/form,$model/system/components/justep/panel/css/panel,$model/system/components/bootstrap/accordion/css/accordion,$model/system/components/justep/common/css/scrollable,$model/system/components/bootstrap/pager/css/pager,$model/system/components/justep/scrollView/css/scrollView,$model/system/components/justep/input/css/datePickerPC,$model/system/components/bootstrap/navs/css/navs,$model/system/components/justep/contents/css/contents,$model/system/components/justep/popMenu/css/popMenu,$model/system/components/justep/lib/css/icons,$model/system/components/justep/titleBar/css/titleBar,$model/system/components/justep/dataTables/css/dataTables,$model/system/components/justep/dialog/css/dialog,$model/system/components/justep/messageDialog/css/messageDialog,$model/system/components/bootstrap/navbar/css/navbar,$model/system/components/justep/toolBar/css/toolBar,$model/system/components/justep/popOver/css/popOver,$model/system/components/justep/loadingBar/loadingBar,$model/system/components/justep/input/css/datePicker,$model/system/components/justep/dataTables/css/dataTables,$model/system/components/bootstrap/dialog/css/dialog,$model/system/components/justep/wing/css/wing,$model/system/components/bootstrap/scrollSpy/css/scrollSpy,$model/system/components/justep/menu/css/menu,$model/system/components/justep/numberSelect/css/numberList,$model/system/components/justep/list/css/list,$model/system/components/bootstrap/carousel/css/carousel,$model/system/components/bootstrap/dropdown/css/dropdown,$model/system/components/justep/common/css/forms,$model/system/components/justep/bar/css/bar,$model/system/components/bootstrap/tabs/css/tabs,$model/system/components/bootstrap/pagination/css/pagination'},{url: '/system/components/bootstrap.min.css', include: '$model/system/components/bootstrap/lib/css/bootstrap,$model/system/components/bootstrap/lib/css/bootstrap-theme'}]);window.__justep.__ResourceEngine.loadJs(['/system/components/comp.min.js','/system/common.min.js','/system/core.min.js']);define(function(require){
require('$model/UI2/system/components/justep/loadingBar/loadingBar');
require('$model/UI2/system/components/justep/row/row');
require('$model/UI2/system/components/justep/panel/panel');
require('$model/UI2/system/components/justep/panel/child');
require('$model/UI2/system/components/justep/list/list');
require('$model/UI2/system/components/justep/model/model');
require('$model/UI2/system/components/justep/window/window');
require('$model/UI2/system/components/justep/data/data');
require('$model/UI2/system/components/justep/titleBar/titleBar');
require('$model/UI2/system/components/justep/button/button');
var __parent1=require('$model/UI2/system/lib/base/modelBase'); 
var __parent0=require('$model/UI2/warehouse_capacity/WorkedDetailActivity'); 
var __result = __parent1._extend(__parent0).extend({
	constructor:function(contextUrl){
	this.__sysParam='true';
	this.__contextUrl=contextUrl;
	this.__id='__baseID__';
	this.__cid='cZ7bEni';
	this._flag_='cc54d66283c920c6c1ca1aae896c1b5d';
	this.callParent(contextUrl);
 var __Data__ = require("$UI/system/components/justep/data/data");new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"cabinname":{"define":"cabinname","label":"船名","name":"cabinname","relation":"cabinname","type":"String"},"correntkong":{"define":"correntkong","name":"correntkong","relation":"correntkong","type":"String"},"listcorrection":{"define":"listcorrection","name":"listcorrection","relation":"listcorrection","type":"String"},"resultid":{"define":"resultid","label":"指令id","name":"resultid","relation":"resultid","type":"String"},"sounding":{"define":"sounding","label":"空高","name":"sounding","relation":"sounding","type":"String"},"standardcapacity":{"define":"standardcapacity","name":"standardcapacity","relation":"standardcapacity","type":"String"},"temperature":{"define":"temperature","label":"温度","name":"temperature","relation":"temperature","type":"String"},"ullage":{"define":"ullage","label":"实高","name":"ullage","relation":"ullage","type":"String"},"xcorrentkong":{"define":"xcorrentkong","name":"xcorrentkong","relation":"xcorrentkong","type":"String"},"xlistcorrection":{"define":"xlistcorrection","name":"xlistcorrection","relation":"xlistcorrection","type":"String"},"xsounding":{"define":"xsounding","name":"xsounding","relation":"xsounding","type":"String"},"xstandardcapacity":{"define":"xstandardcapacity","name":"xstandardcapacity","relation":"xstandardcapacity","type":"String"},"xtemperature":{"define":"xtemperature","name":"xtemperature","relation":"xtemperature","type":"String"},"xullage":{"define":"xullage","name":"xullage","relation":"xullage","type":"String"}},"directDelete":false,"events":{},"idColumn":"resultid","initData":"[{\"id\":\"12\"}]","limit":20,"xid":"cangData"});
}}); 
return __result;});