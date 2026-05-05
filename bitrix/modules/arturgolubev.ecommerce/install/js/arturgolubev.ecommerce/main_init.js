window.ymCCount = window.ymCCount || 0;

function agec_init_ordersteps(items){
	// console.log('agec_init_ordersteps', items);
	
	var deliveryInputs = document.querySelectorAll("[name=DELIVERY_ID]");
	if(deliveryInputs.length){
		deliveryInputs.forEach(function(item){
			var clickItem = item.parentElement;
			if(!clickItem.classList.contains('agec_init')){
				clickItem.classList.add('agec_init');
				
				BX.bind(clickItem, "click", function(){
					var deliveryID = this.querySelector('input').value;
					BX.ajax({
						url: '/bitrix/tools/arturgolubev.ecommerce/ajax.php',
						data: {'action': 'get_delivery_name', 'deliveryID': deliveryID},
						
						method: 'POST',
						dataType: "json",
						
						async: true, processData: true, scriptsRunFirst: false, emulateOnload: false, start: true, cache: false,
						
						onsuccess: function(data){
							// console.log("AGEC send delivery change", data.delivery_name);
							var agec_add_shipping_info = {"shipping_tier": data.delivery_name, "items": items}; 
							gtag("event", "add_shipping_info", agec_add_shipping_info);
						},
						onfailure: function(){
							console.log('arturgolubev.ecommerce Request Error');
						},
					});
				});
			}
		});
	}
	
	// payment
	var paymentInputs = document.querySelectorAll("[name=PAY_SYSTEM_ID]");
	if(paymentInputs.length){
		paymentInputs.forEach(function(item){
			var clickItem = item.parentElement;
			if(!clickItem.classList.contains('agec_init')){
				clickItem.classList.add('agec_init');
				
				BX.bind(clickItem, "click", function(){
					var paymentID = this.querySelector('input').value;
					
					BX.ajax({
						url: '/bitrix/tools/arturgolubev.ecommerce/ajax.php',
						data: {'action': 'get_payment_name', 'paymentID': paymentID},
						
						method: 'POST',
						dataType: "json",
						
						async: true, processData: true, scriptsRunFirst: false, emulateOnload: false, start: true, cache: false,
						
						onsuccess: function(data){
							// console.log("AGEC send payment change", data.payment_name);
							var agec_add_payment_info = {"payment_type": data.payment_name, "items": items};
							gtag("event", "add_payment_info", agec_add_payment_info);
						},
						onfailure: function(){
							console.log('arturgolubev.ecommerce Request Error');
						},
					});
				});
			}
		});
	}
}

function ymChecker(callback, obj){
	if(obj == 'ym')
		var work = (typeof ym == "undefined");
	else
		var work = (typeof Ya == "undefined");

	if(work){
		window.ymCCount = window.ymCCount + 1;
		if(window.ymCCount <= 30){
			setTimeout(function(){
				ymChecker(callback, obj);
			}, 500);
		}
	}else{
		callback();
	}
}

function agec_ym_goal(counter, goal, debug){
	counter = parseInt(counter);
	if(counter){
		ymChecker(function(){
			ym(counter, "reachGoal", goal);

			if(debug){
				console.log('EC debug: ym send goal '+goal);
			}
		}, 'ym');
	}else{
		ymChecker(function(){
			Ya._metrika.getCounters().forEach(function(el){
				ym(el.id, "reachGoal", goal);

				if(debug){
					console.log('EC debug: ya send goal '+goal);
				}
			});
		}, 'ya');
	}
}