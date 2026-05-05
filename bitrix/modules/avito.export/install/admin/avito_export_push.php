<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

LocalRedirect(BX_ROOT . '/admin/avito_export_exchange.php?' . http_build_query(array_intersect_key($_GET, [
	'lang' => true,
])));
