window.__justep.__ResourceEngine.loadCss([{url: '/system/components/comp.min.css', include: '$model/system/components/justep/row/css/row,$model/system/components/justep/attachment/css/attachment,$model/system/components/justep/barcode/css/barcodeImage,$model/system/components/bootstrap/form/css/form,$model/system/components/justep/panel/css/panel,$model/system/components/bootstrap/accordion/css/accordion,$model/system/components/justep/common/css/scrollable,$model/system/components/bootstrap/pager/css/pager,$model/system/components/justep/scrollView/css/scrollView,$model/system/components/justep/input/css/datePickerPC,$model/system/components/bootstrap/navs/css/navs,$model/system/components/justep/contents/css/contents,$model/system/components/justep/popMenu/css/popMenu,$model/system/components/justep/lib/css/icons,$model/system/components/justep/titleBar/css/titleBar,$model/system/components/justep/dataTables/css/dataTables,$model/system/components/justep/dialog/css/dialog,$model/system/components/justep/messageDialog/css/messageDialog,$model/system/components/bootstrap/navbar/css/navbar,$model/system/components/justep/toolBar/css/toolBar,$model/system/components/justep/popOver/css/popOver,$model/system/components/justep/loadingBar/loadingBar,$model/system/components/justep/input/css/datePicker,$model/system/components/justep/dataTables/css/dataTables,$model/system/components/bootstrap/dialog/css/dialog,$model/system/components/justep/wing/css/wing,$model/system/components/bootstrap/scrollSpy/css/scrollSpy,$model/system/components/justep/menu/css/menu,$model/system/components/justep/numberSelect/css/numberList,$model/system/components/justep/list/css/list,$model/system/components/bootstrap/carousel/css/carousel,$model/system/components/bootstrap/dropdown/css/dropdown,$model/system/components/justep/common/css/forms,$model/system/components/justep/bar/css/bar,$model/system/components/bootstrap/tabs/css/tabs,$model/system/components/bootstrap/pagination/css/pagination'},{url: '/system/components/bootstrap.min.css', include: '$model/system/components/bootstrap/lib/css/bootstrap,$model/system/components/bootstrap/lib/css/bootstrap-theme'}]);window.__justep.__ResourceEngine.loadJs(['/system/components/comp.min.js','/system/common.min.js','/system/core.min.js']);define(function(require){
require('$model/UI2/system/components/justep/labelEdit/labelEdit');
require('$model/UI2/system/components/justep/loadingBar/loadingBar');
require('$model/UI2/system/components/justep/panel/panel');
require('$model/UI2/system/components/justep/panel/child');
require('$model/UI2/system/components/justep/messageDialog/messageDialog');
require('$model/UI2/system/components/justep/select/select');
require('$model/UI2/system/components/justep/model/model');
require('$model/UI2/system/components/justep/select/radioGroup');
require('$model/UI2/system/components/justep/window/window');
require('$model/UI2/system/components/justep/button/radio');
require('$model/UI2/system/components/justep/titleBar/titleBar');
require('$model/UI2/system/components/justep/data/data');
require('$model/UI2/system/components/justep/button/button');
require('$model/UI2/system/components/justep/input/input');
var __parent1=require('$model/UI2/system/lib/base/modelBase'); 
var __parent0=require('$model/UI2/warehouse_capacity/DraftActivity'); 
var __result = __parent1._extend(__parent0).extend({
	constructor:function(contextUrl){
	this.__sysParam='true';
	this.__contextUrl=contextUrl;
	this.__id='__baseID__';
	this.__cid='ciUVnUv';
	this._flag_='d7523855b5e6d8d90ef3958b035d16ae';
	this.callParent(contextUrl);
 var __Data__ = require("$UI/system/components/justep/data/data");new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"sname":{"define":"sname","label":"作业作态名称","name":"sname","relation":"sname","type":"String"},"solt":{"define":"solt","label":"作业状态","name":"solt","relation":"solt","type":"String"}},"directDelete":false,"events":{},"idColumn":"solt","initData":"[{\"solt\":\"1\",\"sname\":\"作业前录入\"},{\"solt\":\"2\",\"sname\":\"作业后录入\"}]","limit":20,"xid":"workingStatus"});
 new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"temp":{"define":"temp","label":"值","name":"temp","relation":"temp","type":"String"}},"directDelete":false,"events":{},"idColumn":"temp","initData":"[{\"temp\":\"15℃\"},{\"temp\":\"20℃\"},{\"temp\":\"25℃\"}]","limit":20,"xid":"itemTemData"});
 new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"fName":{"define":"fName","label":"col0","name":"fName","relation":"fName","type":"String"},"fValue":{"define":"fValue","name":"fValue","relation":"fValue","type":"String"}},"directDelete":false,"events":{},"idColumn":"fName","initData":"[{\"fName\":\"作业前录入\",\"fValue\":\"1\"},{\"fName\":\"作业后录入\",\"fValue\":\"2\"}]","limit":20,"xid":"data1"});
 new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"fValue":{"define":"fValue","label":"col0","name":"fValue","relation":"fValue","type":"String"}},"directDelete":false,"events":{"onValueChange":"dataValueChange"},"idColumn":"fValue","initData":"[{\"fValue\":\"1\"},{\"fValue\":\"2\"}]","limit":20,"xid":"data2"});
 new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"temp":{"define":"temp","name":"temp","relation":"temp","type":"String"}},"directDelete":false,"events":{},"idColumn":"temp","initData":"[{\"temp\":\"15℃\"}]","limit":20,"xid":"itemTemDataModer"});
 var justep = require('$UI/system/lib/justep');if(!this['__justep__']) this['__justep__'] = {};if(!this['__justep__'].selectOptionsAfterRender)	this['__justep__'].selectOptionsAfterRender = function($element) {		var comp = justep.Component.getComponent($element);		if(comp) comp._addDefaultOption();	};if(!this['__justep__'].selectOptionsBeforeRender)	this['__justep__'].selectOptionsBeforeRender = function($element) {		var comp = justep.Component.getComponent($element);		if(comp) comp._optionsBeforeRender();	};
}}); 
return __result;});
