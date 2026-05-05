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
			// console.log('success');
		},
		onfailure: function(){
			console.log('arturgolubev.ecommerce Request Error');
		}
	});
}