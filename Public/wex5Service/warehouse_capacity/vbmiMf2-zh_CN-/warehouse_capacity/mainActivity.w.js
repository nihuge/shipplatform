window.__justep.__ResourceEngine.loadCss([{url: '/system/components/comp.min.css', include: '$model/system/components/justep/row/css/row,$model/system/components/justep/attachment/css/attachment,$model/system/components/justep/barcode/css/barcodeImage,$model/system/components/bootstrap/form/css/form,$model/system/components/justep/panel/css/panel,$model/system/components/bootstrap/accordion/css/accordion,$model/system/components/justep/common/css/scrollable,$model/system/components/bootstrap/pager/css/pager,$model/system/components/justep/scrollView/css/scrollView,$model/system/components/justep/input/css/datePickerPC,$model/system/components/bootstrap/navs/css/navs,$model/system/components/justep/contents/css/contents,$model/system/components/justep/popMenu/css/popMenu,$model/system/components/justep/lib/css/icons,$model/system/components/justep/titleBar/css/titleBar,$model/system/components/justep/dataTables/css/dataTables,$model/system/components/justep/dialog/css/dialog,$model/system/components/justep/messageDialog/css/messageDialog,$model/system/components/bootstrap/navbar/css/navbar,$model/system/components/justep/toolBar/css/toolBar,$model/system/components/justep/popOver/css/popOver,$model/system/components/justep/loadingBar/loadingBar,$model/system/components/justep/input/css/datePicker,$model/system/components/justep/dataTables/css/dataTables,$model/system/components/bootstrap/dialog/css/dialog,$model/system/components/justep/wing/css/wing,$model/system/components/bootstrap/scrollSpy/css/scrollSpy,$model/system/components/justep/menu/css/menu,$model/system/components/justep/numberSelect/css/numberList,$model/system/components/justep/list/css/list,$model/system/components/bootstrap/carousel/css/carousel,$model/system/components/bootstrap/dropdown/css/dropdown,$model/system/components/justep/common/css/forms,$model/system/components/justep/bar/css/bar,$model/system/components/bootstrap/tabs/css/tabs,$model/system/components/bootstrap/pagination/css/pagination'},{url: '/system/components/bootstrap.min.css', include: '$model/system/components/bootstrap/lib/css/bootstrap,$model/system/components/bootstrap/lib/css/bootstrap-theme'}]);window.__justep.__ResourceEngine.loadJs(['/system/components/comp2.min.js','/system/components/comp.min.js','/system/common.min.js','/system/core.min.js']);define(function(require){
require('$model/UI2/system/components/justep/loadingBar/loadingBar');
require('$model/UI2/system/components/justep/labelEdit/labelEdit');
require('$model/UI2/system/components/justep/panel/panel');
require('$model/UI2/system/components/justep/button/buttonGroup');
require('$model/UI2/system/components/justep/list/list');
require('$model/UI2/system/components/justep/window/window');
require('$model/UI2/system/components/justep/data/data');
require('$model/UI2/system/components/justep/titleBar/titleBar');
require('$model/UI2/system/components/justep/contents/contents');
require('$model/UI2/system/components/justep/panel/child');
require('$model/UI2/system/components/justep/scrollView/scrollView');
require('$model/UI2/system/components/justep/contents/content');
require('$model/UI2/system/components/justep/messageDialog/messageDialog');
require('$model/UI2/system/components/justep/model/model');
require('$model/UI2/system/components/bootstrap/carousel/carousel');
require('$model/UI2/system/components/justep/button/button');
require('$model/UI2/system/components/justep/input/input');
var __parent1=require('$model/UI2/system/lib/base/modelBase'); 
var __parent0=require('$model/UI2/warehouse_capacity/mainActivity'); 
var __result = __parent1._extend(__parent0).extend({
	constructor:function(contextUrl){
	this.__sysParam='true';
	this.__contextUrl=contextUrl;
	this.__id='__baseID__';
	this.__cid='cbmU7vq';
	this._flag_='28147f268c178c9fe36252b131e779b3';
	this.callParent(contextUrl);
 var __Data__ = require("$UI/system/components/justep/data/data");new __Data__(this,{"autoLoad":false,"autoNew":true,"confirmDelete":true,"confirmRefresh":false,"defCols":{"id":{"define":"id","name":"id","relation":"id","type":"String"},"is_diliang":{"define":"is_diliang","label":"是否存在底量","name":"is_diliang","relation":"is_diliang","type":"String"},"locationname":{"define":"locationname","label":"作业地点","name":"locationname","relation":"locationname","type":"String"},"nums":{"define":"nums","label":"作业条目","name":"nums","relation":"nums","type":"String"},"shipid":{"define":"shipid","label":"船id","name":"shipid","relation":"shipid","type":"String"},"shipname":{"define":"shipname","label":"船名","name":"shipname","relation":"shipname","type":"String"},"voyage":{"define":"voyage","label":"航       次","name":"voyage","relation":"voyage","type":"String"}},"directDelete":false,"events":{},"idColumn":"id","limit":20,"xid":"homework_instructions"});
 new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"fDate":{"define":"fDate","label":"fDate","name":"fDate","relation":"fDate","rules":{"datetime":true},"type":"DateTime"},"id":{"define":"id","label":"id","name":"id","relation":"id","type":"String"}},"directDelete":false,"events":{},"idColumn":"id","initData":"[{\"id\":\"1\",\"fString\":\"test\",\"fInteger\":22,\"fFloat\":22.2,\"fDecimal\":\"\",\"fDate\":\"\",\"fText\":\"test.....\"}]","limit":20,"xid":"data"});
 new __Data__(this,{"autoLoad":true,"confirmDelete":true,"confirmRefresh":true,"defCols":{"fDate":{"define":"fDate","label":"fDate","name":"fDate","relation":"fDate","rules":{"datetime":true},"type":"DateTime"},"id":{"define":"id","label":"id","name":"id","relation":"id","type":"String"}},"directDelete":false,"events":{},"idColumn":"id","initData":"[{\"id\":\"1\",\"fString\":\"test\",\"fInteger\":22,\"fFloat\":22.2,\"fDecimal\":\"\",\"fDate\":\"\",\"fText\":\"test.....\"}]","limit":20,"xid":"data1"});
}}); 
return __result;});
