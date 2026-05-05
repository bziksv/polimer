var kdaIEModuleName = 'esol.allimportexport';
var kdaIEModuleFilePrefix = 'esol_allimportexport_export';
var kdaIEModuleAddPath = 'list/';
var kdaIEModuleUMClass = 'kda-ee-updates-message';

$(document).ready(function(){
	if($('#'+kdaIEModuleUMClass).length > 0)
	{
		$.post('/bitrix/admin/'+kdaIEModuleFilePrefix+'.php?lang='+BX.message('LANGUAGE_ID'), 'MODE=AJAX&ACTION=SHOW_MODULE_MESSAGE', function(data){
			data = $(data);
			var inner = $('#'+kdaIEModuleUMClass+'-inner', data);
			if(inner.length > 0 && inner.html().length > 0)
			{
				$('#'+kdaIEModuleUMClass+'-inner').replaceWith(inner);
				$('#'+kdaIEModuleUMClass).show();
			}
		});
	}
	
	BX.adminList.prototype.ShowSettings = function(url)
	{
		(new BX.CDialog({
			content_url: url,
			resizable: true,
			height: 480,
			width: 950
		})).Show();
	};
});