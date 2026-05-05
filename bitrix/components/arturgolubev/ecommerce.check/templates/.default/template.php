<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$this->setFrameMode(true);

$frame = $this->createFrame()->begin('');
	if(CModule::IncludeModule('arturgolubev.ecommerce')):
		$finalScripts = CArturgolubevEcommerce::checkReadyEvents();
		if($finalScripts):
		?>
			<script>
				function agec_pageaction_script(){
					<?=$finalScripts?>
				}
			
				if (window.frameCacheVars !== undefined) {
					agec_pageaction_script();
				}else{
					document.addEventListener("DOMContentLoaded", function(){
						agec_pageaction_script();
					});
				}
			</script>
			<?
		endif;
	endif;
$frame->end();
?>