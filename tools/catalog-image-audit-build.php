<?php
/**
 * CLI: сборка кэша аудита картинок (без nginx timeout).
 *
 * php tools/catalog-image-audit-build.php
 * /opt/php82/bin/php -d short_open_tag=On tools/catalog-image-audit-build.php
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	echo "CLI only\n";
	exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_PUBLIC_MODE', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/polimer_catalog_image_audit.php';

set_time_limit(0);
PolimerCatalogImageAudit::touchBuildLock(getmypid());

$started = microtime(true);
$log = static function (string $message): void {
	$line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
	file_put_contents(PolimerCatalogImageAudit::getBuildLogPath(), $line, FILE_APPEND);
	// В интерактивном терминале — ещё и на экран (но не при nohup >> log)
	if (defined('STDOUT') && function_exists('posix_isatty') && @posix_isatty(STDOUT)) {
		fwrite(STDOUT, $line);
	}
};

try {
	$log('Build started');
	$report = PolimerCatalogImageAudit::buildReport(static function (int $checked) use ($log): void {
		if ($checked % 1000 === 0) {
			$log('Progress: ' . $checked . ' items');
		}
	});
	PolimerCatalogImageAudit::saveCache($report);
	$stats = $report['stats'] ?? [];
	$log(sprintf(
		'Build finished in %.1fs: checked=%d issues=%d',
		microtime(true) - $started,
		(int)($stats['checked'] ?? 0),
		(int)($stats['issues'] ?? 0)
	));
} catch (Throwable $e) {
	$log('Build failed: ' . $e->getMessage());
	exit(1);
} finally {
	PolimerCatalogImageAudit::clearBuildLock();
}

exit(0);
