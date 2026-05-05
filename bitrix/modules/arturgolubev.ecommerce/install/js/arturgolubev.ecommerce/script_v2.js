var agMetricRequestCount = 0;

BX.ready(function(){
	agMetricSetTimeout();
});

function agMetricSetTimeout(){	
	if(agMetricRequestCount < 60){
		setTimeout(function(){
			agMetricScriptRequest();
		}, 5000);
	}
	
	agMetricRequestCount++;
}

function agMetricScriptRequest(){	
	BX.ajax({   
		url: '/bitrix/tools/arturgolubev.ecommerce/getscripts_v2.php',
		data: {},
		method: 'POST',
		dataType: 'script',
		timeout: 30,
		async: true,
		processData: true,
		scriptsRunFirst: false,
		// emulateOnload: true,
		start: true,
		cache: false,
		onsuccess: function(data){
			agMetricSetTimeout();
		},
		onfailure: function(){
			console.log('arturgolubev.ecommerce Request Error');
		}
	});
}