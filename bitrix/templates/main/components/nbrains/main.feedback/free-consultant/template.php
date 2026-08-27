<?
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */
$errorFields = array_fill_keys($arResult['ERROR_FIELDS'] ?? [], true);
$hasErrors = !empty($arResult['ERROR_MESSAGE']);
$showForm = $hasErrors;
?>

<?if(strlen($arResult["OK_MESSAGE"]) > 0){?>
	<div class="popup" id="<?=$arParams["IBLOCK_TYPE"].$arParams["IBLOCK_ID"].'OK'?>" style="display: block;width: 650px;margin: 0 0 0 -325px;">
		<a href="#" class="close">&nbsp;</a>
		<div class="title"></div>
		<div class="subtitle">
			<div class="mf-ok-text"><?=$arResult["OK_MESSAGE"]?></div>
		</div>
	</div>
<?}?>

<div class="popup" id="specialist"<?= $showForm ? ' style="display:block"' : '' ?>>
	<a href="#" class="close">&nbsp;</a>
	<div class="title">Бесплатная консультация</div>
	<div class="subtitle">Укажите ваши данные и наши консультанты свяжуться с вами в ближайшее время</div>
	<form action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data" class="js-polimer-feedback-form js-polimer-consent-form" novalidate>
		<?=bitrix_sessid_post()?>
		<fieldset>
			<?if($hasErrors):?>
			<div class="polimer-feedback-errors">
				<?foreach($arResult["ERROR_MESSAGE"] as $v):?>
					<div class="polimer-feedback-errors__item"><?=htmlspecialcharsbx(strip_tags($v))?></div>
				<?endforeach;?>
			</div>
			<?endif;?>

			<? foreach($arResult['USER_FIELD'] as $field):
				$fieldCode = (string)$field['CODE'];
				$fieldInvalid = !empty($errorFields[$fieldCode]);
			?>

				<? if($field['XML_ID'] == 'hidden'):?>

					<?if($field['CODE'] == 'PRODUCT'):?>
						<input type="hidden" name="<?=$field['CODE']?>" value="<?=$_REQUEST['TITLE']?>">
					<? else:?>
						<input type="hidden" name="<?=$field['CODE']?>" value="<?=$_REQUEST['URL']?>">
					<?endif;?>
				<? else: ?>

				<?if($field['PROPERTY_TYPE'] == "S"):?>

					<span class="line cl<?= $fieldInvalid ? ' is-invalid' : '' ?>">
     					<span class="label"><?=$field['NAME']?></span>
     					<span class="value">
							<?
								switch ($field['CODE']):
									case "PHONE":
										?>
											<input type="text" placeholder="+7-___-___-__-__" class="phone" name="<?=$field['CODE']?>" value="<?=$arResult[$field['CODE']]?>" required>
										<?
										break;
									case "EMAIL":
										?>
											<input type="email" placeholder="E-mail" name="<?=$field['CODE']?>" value="<?=$arResult[$field['CODE']]?>" required>
										<?
										break;
									case "FIO":
										?>
											<input type="text" placeholder="Пример: Иванов Иван (на кириллице)" class="name" name="<?=$field['CODE']?>" value="<?=$arResult[$field['CODE']]?>" required>
										<?
										break;
									default:
										?>
											<input type="text" placeholder="<?=$field['NAME']?>" name="<?=$field['CODE']?>" value="<?=$arResult[$field['CODE']]?>" required>
										<?
								endswitch;
							?>
						</span>
						<?if($fieldInvalid):?><span class="polimer-field-error">Заполните это поле</span><?endif;?>
     				</span>

				<? elseif($field['PROPERTY_TYPE'] == "L"):?>
					<?php
					$consentName = $field['CODE'];
					$consentChecked = !empty($arResult[$field['CODE']]);
					$consentInvalid = !empty($errorFields[$field['CODE']]);
					require $_SERVER['DOCUMENT_ROOT'] . '/include/legal/consent-checkbox.php';
					?>
				<? endif; ?>
			<? endif; ?>

			<? endforeach; ?>


			<span class="line submit">
				<input type="hidden" name="PARAMS_HASH" value="<?=$arResult["PARAMS_HASH"]?>">
				<input type="submit" name="submit" value="<?=GetMessage("MFT_SUBMIT")?>">
			</span>


		</fieldset>
	</form>
</div>


<div class="popup" id="<?=$arParams["IBLOCK_TYPE"].$arParams["IBLOCK_ID"]?>" style="display: none;width: 650px;margin: 0 0 0 -325px;">
	<a href="#" class="close">&nbsp;</a>
	<div class="title"></div>
	<div class="subtitle">
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.include",
			"",
			Array(
				"AREA_FILE_SHOW" => "file",
				"AREA_FILE_SUFFIX" => "inc",
				"EDIT_TEMPLATE" => "",
				"PATH" => "/include/rule.php"
			)
		);?>
	</div>
</div>
