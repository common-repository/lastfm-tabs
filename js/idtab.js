/* idTabs ~ Sean Catchpole - Version 2.2 - MIT/GPL */
(function(){var dep={"jQuery":"http://code.jquery.com/jquery-latest.min.js"};var init=function(){(function(jQuery){jQuery.fn.idTabs=function(){var sx={};for(var i=0;i<arguments.length;++i){var a=arguments[i];switch(a.constructor){case Object:jQuery.extend(sx,a);break;case Boolean:sx.change=a;break;case Number:sx.start=a;break;case Function:sx.click=a;break;case String:if(a.charAt(0)=='.')sx.selected=a;else if(a.charAt(0)=='!')sx.event=a;else sx.start=a;break;}}
if(typeof sx['return']=="function")
sx.change=sx['return'];return this.each(function(){jQuery.idTabs(this,sx);});}
jQuery.idTabs=function(tabs,options){var meta=(jQuery.metadata)?jQuery(tabs).metadata():{};var sx=jQuery.extend({},jQuery.idTabs.settings,meta,options);if(sx.selected.charAt(0)=='.')sx.selected=sx.selected.substr(1);if(sx.event.charAt(0)=='!')sx.event=sx.event.substr(1);if(sx.start==null)sx.start=-1;var showId=function(){if(jQuery(this).is('.'+sx.selected))
return sx.change;var id="#"+this.href.split('#')[1];var aList=[];var idList=[];jQuery("a",tabs).each(function(){if(this.href.match(/#/)){aList.push(this);idList.push("#"+this.href.split('#')[1]);}});if(sx.click&&!sx.click.apply(this,[id,idList,tabs,sx]))return sx.change;for(i in aList)jQuery(aList[i]).removeClass(sx.selected);for(i in idList)jQuery(idList[i]).hide();jQuery(this).addClass(sx.selected);jQuery(id).show();return sx.change;}
var list=jQuery("a[href*='#']",tabs).unbind(sx.event,showId).bind(sx.event,showId);list.each(function(){jQuery("#"+this.href.split('#')[1]).hide();});var test=false;if((test=list.filter('.'+sx.selected)).length);else if(typeof sx.start=="number"&&(test=list.eq(sx.start)).length);else if(typeof sx.start=="string"&&(test=list.filter("[href*='#"+sx.start+"']")).length);if(test){test.removeClass(sx.selected);test.trigger(sx.event);}
return sx;}
jQuery.idTabs.settings={start:0,change:false,click:null,selected:".selected",event:"!click"};jQuery.idTabs.version="2.2";jQuery(function(){jQuery(".idTabs").idTabs();});})(jQuery);}
var check=function(o,sx){sx=sx.split('.');while(o&&sx.length)o=o[sx.shift()];return o;}
var head=document.getElementsByTagName("head")[0];var add=function(url){var sx=document.createElement("script");sx.type="text/javascript";sx.src=url;head.appendChild(sx);}
var sx=document.getElementsByTagName('script');var src=sx[sx.length-1].src;var ok=true;for(d in dep){if(check(this,d))continue;ok=false;add(dep[d]);}if(ok)return init();add(src);})();
