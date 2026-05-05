function syncList(){}
syncList.prototype.sync = function()
{

	for (var i=0; i < arguments.length-1; i++)	document.getElementById(arguments[i]).onchange = (function (o,id1,id2){return function(){o._sync(id1,id2);};})(this, arguments[i], arguments[i+1]);
	document.getElementById(arguments[0]).onchange();
}

syncList.prototype._sync = function (firstSelectId, secondSelectId)
{
	var firstSelect = document.getElementById(firstSelectId);
	var secondSelect = document.getElementById(secondSelectId);

	secondSelect.length = 0;

	if (firstSelect.length>0)
	{

		var optionData = this.dataList[ firstSelect.options[firstSelect.selectedIndex==-1 ? 0 : firstSelect.selectedIndex].value ];

		for (var key in optionData || null) secondSelect.options[secondSelect.length] = new Option(optionData[key], key);

			}

	secondSelect.onchange && secondSelect.onchange();
};
