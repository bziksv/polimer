window.ag_ec_request_count = 0;
window.ag_ec_request_active = 1;
window.ag_ec_request_sended = 0;

function ag_ecommerce_request(){
	if(!window.ag_ec_request_active){
		return false;
	}
	if(window.ag_ec_request_count < 1){
		return false;
	}
	
	// console.log('AG_Ecommerce: start r: c = ' + window.ag_ec_request_count + ', a = ' + window.ag_ec_request_active);
	
	window.ag_ec_request_active = 0;
	window.ag_ec_request_sended++;
	
	var x = window.location.pathname;
	if(window.location.search){
		x = x + window.location.search;
	}
	
	BX.ajax({   
		url: '/bitrix/tools/arturgolubev.ecommerce/getscripts_v2.php',
		data: {'loc': x, 'rnum': window.ag_ec_request_sended},
		method: 'POST',
		dataType: 'script',
		timeout: 30,
		async: true,
		processData: true,
		scriptsRunFirst: true,
		// emulateOnload: true,
		start: true,
		cache: false,
		onsuccess: function(data){
			if(window.ag_ec_request_count > 1){
				window.ag_ec_request_count = 1;
			}else{
				window.ag_ec_request_count--;
			}
			
			window.ag_ec_request_active = 1;
			
			setTimeout('ag_ecommerce_request();', 1000);
		},
		onfailure: function(){
			if(window.ag_ec_request_count > 1){
				window.ag_ec_request_count = 1;
			}else{
				window.ag_ec_request_count--;
			}
			
			window.ag_ec_request_active = 1;
		}
	});
}


document.addEventListener('DOMContentLoaded', function(){
	BX.addCustomEvent('onAjaxSuccess', function(e, params){
		var work = 1;
		
		if(typeof params === 'object' && params !== null){
			if(params.url){
				// console.log(params);
				// console.log(params.data.indexOf("q="));
				// console.log(params.data.indexOf("INPUT_ID="));
				
				if(params.url == '/bitrix/tools/arturgolubev.ecommerce/getscripts_v2.php') work = 0;
				if(params.url.indexOf("analytics.bitrix.info") >= 0) work = 0;
				if(params.url.indexOf("logictim/bonus.ajax") >= 0) work = 0;
				if(params.url.indexOf("tools/public_session.php") >= 0) work = 0;
				if(params.url.indexOf("hotlab.remarketing") >= 0) work = 0;
				if(params.url.indexOf("arturgolubev.cssinliner") >= 0) work = 0;
				if(params.url.indexOf("arturgolubev.abandoned") >= 0) work = 0;
				
				if(typeof params.data == 'string'){
					if(params.data.indexOf("q=") >= 0 && params.data.indexOf("INPUT_ID=") >= 0) work = 0; // search
				}
			}
		}
		
		if(work){
			// console.log('params', params);
			// console.log('BX Ajax', params.url);
			window.ag_ec_request_count++;
			ag_ecommerce_request();
		}
	});

	try {
		$(document).ajaxSuccess(function(event, request, settings){
			var work = 1;
			
			if(!!settings){
				if(settings.url.indexOf("dadata.ru") >= 0) work = 0;
				
				if(settings.url.indexOf("ipol.") >= 0) work = 0;
				if(settings.url.indexOf("corsik.") >= 0) work = 0;
				if(settings.url.indexOf("up.boxberrydelivery") >= 0) work = 0;
				
				if(settings.url.indexOf("concept.phoenix/ajax/hide_adv.php") >= 0) work = 0;
				if(settings.url.indexOf("concept.phoenix/ajax/basket/get_ajax_basket.php") >= 0) work = 0;
				
				if(settings.url.indexOf("/ajax/getAjaxBasket.php") >= 0) work = 0; // aspro
				if(settings.url.indexOf("/ajax/UpdateBakset.php") >= 0) work = 0; // deluxe
			}
			
			if(work){
				// console.log('settings', settings);
				// console.log('JQuery Ajax', settings.url);
				window.ag_ec_request_count++;
				ag_ecommerce_request();
			}
		});
	}catch(err){
		// console.log("AG_Ecommerce: JQuery ajax not defined");
	}
});

BX.ready(function(){
	// console.log('AG_Ecommerce: start page request');
	
	window.ag_ec_request_count++;
	ag_ecommerce_request();
});