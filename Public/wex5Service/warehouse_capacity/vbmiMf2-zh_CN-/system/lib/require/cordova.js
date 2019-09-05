/*! 
* WeX5 v3 (http://www.justep.com) 
* Copyright 2015 Justep, Inc.
* Licensed under Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0) 
*/ 
define(function() {

  //main api object
  var cordovaAPI = {};
  


  cordovaAPI.normalize = function(name, normalize) {
	  return normalize(name);
  }
  
  
  cordovaAPI.load = function(cssId, req, load, config) {
	  load();
  }

  return cordovaAPI;
});
