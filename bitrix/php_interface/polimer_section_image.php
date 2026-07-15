<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

/**
 * Обработка картинок разделов: белый/светлый фон → PNG с альфой, единый холст.
 */
class PolimerSectionImageProcessor
{
	public const CANVAS = 512;
	public const PADDING_RATIO = 0.10;
	public const WHITE_THRESHOLD = 242;
	public const WHITE_SOFTNESS = 14;
	public const FLOOD_TOLERANCE = 24;

	public static function workDir(): string
	{
		$dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/polimer/section_images';
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		return $dir;
	}

	public static function sourceDir(): string
	{
		$dir = self::workDir() . '/source';
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		return $dir;
	}

	public static function outputDir(): string
	{
		$dir = self::workDir() . '/processed';
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		return $dir;
	}

	public static function loadImage(string $absPath)
	{
		if (!is_file($absPath)) {
			return null;
		}

		$info = @getimagesize($absPath);
		if (!$info) {
			return null;
		}

		switch ($info[2]) {
			case IMAGETYPE_JPEG:
				return @imagecreatefromjpeg($absPath);
			case IMAGETYPE_PNG:
				return @imagecreatefrompng($absPath);
			case IMAGETYPE_WEBP:
				if (function_exists('imagecreatefromwebp')) {
					return @imagecreatefromwebp($absPath);
				}
				return null;
			case IMAGETYPE_GIF:
				return @imagecreatefromgif($absPath);
			default:
				return null;
		}
	}

	/** Убирает светлый фон (flood с краёв), обрезает прозрачные поля, кладёт на квадратный холст. */
	public static function processFile(string $inputAbs, string $outputAbs): bool
	{
		$src = self::loadImage($inputAbs);
		if (!$src) {
			return false;
		}

		$w = imagesx($src);
		$h = imagesy($src);
		$cut = self::removeBackground($src, $w, $h);
		imagedestroy($src);

		if (!$cut) {
			return false;
		}

		$bounds = self::alphaBounds($cut);
		if (!$bounds) {
			imagedestroy($cut);
			return false;
		}

		$cropW = $bounds['x2'] - $bounds['x1'] + 1;
		$cropH = $bounds['y2'] - $bounds['y1'] + 1;
		$cropped = imagecreatetruecolor($cropW, $cropH);
		imagealphablending($cropped, false);
		imagesavealpha($cropped, true);
		$transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
		imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);
		imagecopy($cropped, $cut, 0, 0, $bounds['x1'], $bounds['y1'], $cropW, $cropH);
		imagedestroy($cut);

		$canvas = self::CANVAS;
		$pad = (int)round($canvas * self::PADDING_RATIO);
		$inner = $canvas - 2 * $pad;
		$scale = min($inner / $cropW, $inner / $cropH);
		$dstW = max(1, (int)round($cropW * $scale));
		$dstH = max(1, (int)round($cropH * $scale));
		$dstX = (int)(($canvas - $dstW) / 2);
		$dstY = (int)(($canvas - $dstH) / 2);

		$final = imagecreatetruecolor($canvas, $canvas);
		imagealphablending($final, false);
		imagesavealpha($final, true);
		imagefilledrectangle($final, 0, 0, $canvas, $canvas, $transparent);
		imagecopyresampled($final, $cropped, $dstX, $dstY, 0, 0, $dstW, $dstH, $cropW, $cropH);
		imagedestroy($cropped);

		$outDir = dirname($outputAbs);
		if (!is_dir($outDir)) {
			mkdir($outDir, 0755, true);
		}

		$ok = imagepng($final, $outputAbs, 6);
		imagedestroy($final);

		return $ok && is_file($outputAbs) && filesize($outputAbs) > 0;
	}

	private static function removeBackground($src, int $w, int $h)
	{
		$cut = imagecreatetruecolor($w, $h);
		imagealphablending($cut, false);
		imagesavealpha($cut, true);
		$transparent = imagecolorallocatealpha($cut, 0, 0, 0, 127);
		imagefilledrectangle($cut, 0, 0, $w, $h, $transparent);

		$thr = self::WHITE_THRESHOLD;
		$tol = self::FLOOD_TOLERANCE;
		$bgMask = array_fill(0, $w * $h, false);

		$cornerSamples = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]];
		$bgR = 0;
		$bgG = 0;
		$bgB = 0;
		foreach ($cornerSamples as [$cx, $cy]) {
			$rgba = imagecolorat($src, $cx, $cy);
			$bgR += ($rgba >> 16) & 0xFF;
			$bgG += ($rgba >> 8) & 0xFF;
			$bgB += $rgba & 0xFF;
		}
		$bgR = (int)round($bgR / 4);
		$bgG = (int)round($bgG / 4);
		$bgB = (int)round($bgB / 4);

		$seedPoints = [];
		for ($x = 0; $x < $w; $x++) {
			$seedPoints[] = [$x, 0];
			$seedPoints[] = [$x, $h - 1];
		}
		for ($y = 1; $y < $h - 1; $y++) {
			$seedPoints[] = [0, $y];
			$seedPoints[] = [$w - 1, $y];
		}

		foreach ($seedPoints as [$sx, $sy]) {
			$idx = $sy * $w + $sx;
			if ($bgMask[$idx]) {
				continue;
			}

			$rgba = imagecolorat($src, $sx, $sy);
			$sr = ($rgba >> 16) & 0xFF;
			$sg = ($rgba >> 8) & 0xFF;
			$sb = $rgba & 0xFF;

			if (!self::isBackgroundLike($sr, $sg, $sb, $bgR, $bgG, $bgB, $thr, $tol)) {
				continue;
			}

			$queue = [[$sx, $sy]];
			$bgMask[$idx] = true;

			while ($queue) {
				[$x, $y] = array_pop($queue);

				foreach ([[$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$nx, $ny]) {
					if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
						continue;
					}

					$nIdx = $ny * $w + $nx;
					if ($bgMask[$nIdx]) {
						continue;
					}

					$nrgba = imagecolorat($src, $nx, $ny);
					$nr = ($nrgba >> 16) & 0xFF;
					$ng = ($nrgba >> 8) & 0xFF;
					$nb = $nrgba & 0xFF;

					if (!self::isBackgroundLike($nr, $ng, $nb, $bgR, $bgG, $bgB, $thr, $tol)) {
						continue;
					}

					$bgMask[$nIdx] = true;
					$queue[] = [$nx, $ny];
				}
			}
		}

		for ($y = 0; $y < $h; $y++) {
			for ($x = 0; $x < $w; $x++) {
				$idx = $y * $w + $x;
				$rgba = imagecolorat($src, $x, $y);
				$r = ($rgba >> 16) & 0xFF;
				$g = ($rgba >> 8) & 0xFF;
				$b = $rgba & 0xFF;

				if ($bgMask[$idx] || self::matchesSampledBackground($r, $g, $b, $bgR, $bgG, $bgB, $tol + 6) || self::isLikelyBackground($r, $g, $b, $thr)) {
					imagesetpixel($cut, $x, $y, $transparent);
					continue;
				}

				$col = imagecolorallocatealpha($cut, $r, $g, $b, 0);
				imagesetpixel($cut, $x, $y, $col);
			}
		}

		self::stripGrayHalo($cut, $w, $h, $transparent);

		return $cut;
	}

	private static function stripGrayHalo($im, int $w, int $h, $transparent): void
	{
		$changed = true;
		while ($changed) {
			$changed = false;
			for ($y = 0; $y < $h; $y++) {
				for ($x = 0; $x < $w; $x++) {
					$rgba = imagecolorat($im, $x, $y);
					if ((($rgba & 0x7F000000) >> 24) >= 120) {
						continue;
					}

					$r = ($rgba >> 16) & 0xFF;
					$g = ($rgba >> 8) & 0xFF;
					$b = $rgba & 0xFF;
					if (!self::isGrayHaloPixel($r, $g, $b)) {
						continue;
					}

					foreach ([[$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$nx, $ny]) {
						if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
							imagesetpixel($im, $x, $y, $transparent);
							$changed = true;
							break;
						}

						$nAlpha = (imagecolorat($im, $nx, $ny) & 0x7F000000) >> 24;
						if ($nAlpha >= 120) {
							imagesetpixel($im, $x, $y, $transparent);
							$changed = true;
							break;
						}
					}
				}
			}
		}
	}

	private static function isGrayHaloPixel(int $r, int $g, int $b): bool
	{
		$spread = max($r, $g, $b) - min($r, $g, $b);
		$lightness = ($r + $g + $b) / 3;

		return $spread <= 24 && $lightness >= 30 && $lightness <= 225;
	}

	private static function matchesSampledBackground(int $r, int $g, int $b, int $bgR, int $bgG, int $bgB, int $tol): bool
	{
		if (self::colorDistance($r, $g, $b, $bgR, $bgG, $bgB) > $tol) {
			return false;
		}

		$bgLightness = ($bgR + $bgG + $bgB) / 3;
		$lightness = ($r + $g + $b) / 3;

		return $lightness <= $bgLightness + 10;
	}

	private static function isBackgroundLike(int $r, int $g, int $b, int $bgR, int $bgG, int $bgB, int $thr, int $tol): bool
	{
		if (self::isLikelyBackground($r, $g, $b, $thr)) {
			return true;
		}

		return self::colorDistance($r, $g, $b, $bgR, $bgG, $bgB) <= $tol;
	}

	private static function isLikelyBackground(int $r, int $g, int $b, int $thr): bool
	{
		$minRgb = min($r, $g, $b);
		$maxRgb = max($r, $g, $b);
		$lightness = ($r + $g + $b) / 3;

		return ($minRgb >= $thr) || ($maxRgb - $minRgb <= 10 && $lightness >= $thr - 18);
	}

	private static function colorDistance(int $r1, int $g1, int $b1, int $r2, int $g2, int $b2): float
	{
		return sqrt(($r1 - $r2) ** 2 + ($g1 - $g2) ** 2 + ($b1 - $b2) ** 2);
	}

	private static function alphaBounds($im): ?array
	{
		$w = imagesx($im);
		$h = imagesy($im);
		$x1 = $w;
		$y1 = $h;
		$x2 = 0;
		$y2 = 0;
		$found = false;

		for ($y = 0; $y < $h; $y++) {
			for ($x = 0; $x < $w; $x++) {
				$a = (imagecolorat($im, $x, $y) & 0x7F000000) >> 24;
				if ($a < 120) {
					$found = true;
					if ($x < $x1) {
						$x1 = $x;
					}
					if ($y < $y1) {
						$y1 = $y;
					}
					if ($x > $x2) {
						$x2 = $x;
					}
					if ($y > $y2) {
						$y2 = $y;
					}
				}
			}
		}

		return $found ? ['x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2] : null;
	}
}

class PolimerSectionImageTool
{
	public const IBLOCK_ID = 21;

	public static function run(): void
	{
		global $argv;

		$isCli = PHP_SAPI === 'cli';
		$action = $isCli ? ($argv[1] ?? 'help') : ($_GET['action'] ?? 'help');
		$argTail = $isCli ? array_slice($argv ?? [], 2) : $_GET;

		if (!$isCli && !self::canWebAccess()) {
			http_response_code(403);
			echo 'Forbidden';
			return;
		}

		if (!CModule::IncludeModule('iblock')) {
			self::out(['error' => 'iblock module'], $isCli);
			return;
		}

		switch ($action) {
			case 'list':
				self::out(self::actionList($argTail), $isCli);
				break;
			case 'export':
				self::out(self::actionExport($argTail), $isCli);
				break;
			case 'process':
				self::out(self::actionProcess($argTail), $isCli);
				break;
			case 'apply':
				self::out(self::actionApply($argTail), $isCli);
				break;
			case 'restore':
				self::out(self::actionRestore($argTail), $isCli);
				break;
			case 'pipeline':
				self::out(self::actionPipeline($argTail), $isCli);
				break;
			case 'preview':
				if ($isCli) {
					self::out(['error' => 'preview is web-only'], $isCli);
					break;
				}
				self::actionPreview($argTail);
				break;
			default:
				self::out([
					'usage' => [
						'list [--depth=2] [--limit=50] [--q=баки]',
						'export [--ids=1,2] [--all] [--depth=2]',
						'process [--ids=1,2] [--all]',
						'apply [--ids=1,2] [--all] [--dry-run]',
						'restore [--ids=1,2] [--all]',
						'pipeline [--ids=1,2] [--depth=2] [--limit=10] [--dry-run]',
					],
				], $isCli);
		}
	}

	private static function canWebAccess(): bool
	{
		global $USER;
		if (is_object($USER) && $USER->IsAdmin()) {
			return true;
		}
		$token = getenv('POLIMER_AUDIT_TOKEN') ?: '';
		if ($token !== '' && isset($_GET['token']) && hash_equals($token, (string)$_GET['token'])) {
			return true;
		}
		return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
	}

	private static function parseArgs(array $args): array
	{
		$out = [];
		foreach ($args as $key => $arg) {
			if (is_string($key) && !is_numeric($key) && $key !== 'action' && $key !== 'token') {
				$out[$key] = (string)$arg;
				continue;
			}
			if (!is_string($arg) || strpos($arg, '--') !== 0) {
				continue;
			}
			$pair = explode('=', substr($arg, 2), 2);
			$out[$pair[0]] = $pair[1] ?? '1';
		}

		return $out;
	}

	private static function parseIds(array $params): array
	{
		if (empty($params['ids'])) {
			return [];
		}

		return array_values(array_filter(array_map('intval', preg_split('/[\s,;]+/', (string)$params['ids']))));
	}

	private static function fetchSections(array $params): array
	{
		$filter = ['IBLOCK_ID' => self::IBLOCK_ID, 'ACTIVE' => 'Y'];
		if (!empty($params['depth'])) {
			$filter['DEPTH_LEVEL'] = (int)$params['depth'];
		}
		if (!empty($params['q'])) {
			$filter['%NAME'] = $params['q'];
		}

		$ids = self::parseIds($params);
		if ($ids) {
			$filter['ID'] = $ids;
		}

		$limit = !empty($params['all']) ? 0 : (int)($params['limit'] ?? 50);
		$sections = [];
		$res = CIBlockSection::GetList(
			['LEFT_MARGIN' => 'ASC'],
			$filter,
			false,
			['ID', 'NAME', 'DEPTH_LEVEL', 'PICTURE', 'CODE', 'IBLOCK_SECTION_ID']
		);

		while ($row = $res->Fetch()) {
			if ($limit > 0 && count($sections) >= $limit) {
				break;
			}
			$pictureId = (int)($row['PICTURE'] ?? 0);
			$path = $pictureId > 0 ? (string)CFile::GetPath($pictureId) : '';
			$sections[] = [
				'id' => (int)$row['ID'],
				'name' => $row['NAME'],
				'depth' => (int)$row['DEPTH_LEVEL'],
				'picture_id' => $pictureId,
				'picture_path' => $path,
				'has_file' => $pictureId > 0 && $path !== '' && is_file($_SERVER['DOCUMENT_ROOT'] . $path),
			];
		}

		return $sections;
	}

	private static function sectionPaths(int $sectionId): array
	{
		$base = PolimerSectionImageProcessor::sourceDir() . '/' . $sectionId;
		return [
			'source' => $base . '_source' . self::guessExt($base . '_source'),
			'processed' => PolimerSectionImageProcessor::outputDir() . '/' . $sectionId . '.png',
		];
	}

	private static function guessExt(string $prefix): string
	{
		foreach (['.jpg', '.jpeg', '.png', '.webp', '.gif'] as $ext) {
			if (is_file($prefix . $ext)) {
				return $ext;
			}
		}

		return '.jpg';
	}

	private static function actionList(array $args): array
	{
		$params = self::parseArgs($args);
		$sections = self::fetchSections($params);
		$with = 0;
		foreach ($sections as $s) {
			if ($s['has_file']) {
				$with++;
			}
		}

		return [
			'count' => count($sections),
			'with_picture_file' => $with,
			'work_dir' => PolimerSectionImageProcessor::workDir(),
			'sections' => $sections,
		];
	}

	private static function actionExport(array $args): array
	{
		$params = self::parseArgs($args);
		if (empty($params['all']) && !self::parseIds($params) && empty($params['depth']) && empty($params['q'])) {
			$params['depth'] = 2;
			$params['limit'] = 20;
		}

		$sections = self::fetchSections($params);
		$exported = [];
		$skipped = [];

		foreach ($sections as $section) {
			if (!$section['has_file']) {
				$skipped[] = ['id' => $section['id'], 'reason' => 'no_picture'];
				continue;
			}

			$srcAbs = $_SERVER['DOCUMENT_ROOT'] . $section['picture_path'];
			$ext = strtolower(pathinfo($srcAbs, PATHINFO_EXTENSION));
			if ($ext === '') {
				$ext = 'jpg';
			}
			$dest = PolimerSectionImageProcessor::sourceDir() . '/' . $section['id'] . '_source.' . $ext;

			if (!@copy($srcAbs, $dest)) {
				$skipped[] = ['id' => $section['id'], 'reason' => 'copy_failed'];
				continue;
			}

			$exported[] = [
				'id' => $section['id'],
				'name' => $section['name'],
				'source' => $dest,
			];
		}

		return ['exported' => count($exported), 'skipped' => $skipped, 'items' => $exported];
	}

	private static function actionProcess(array $args): array
	{
		$params = self::parseArgs($args);
		$sections = self::fetchSections($params);
		$done = [];
		$failed = [];

		foreach ($sections as $section) {
			$paths = self::sectionPaths($section['id']);
			$source = $paths['source'];
			if (!is_file($source)) {
				$exts = glob(PolimerSectionImageProcessor::sourceDir() . '/' . $section['id'] . '_source.*') ?: [];
				$source = $exts[0] ?? '';
			}
			if ($source === '' || !is_file($source)) {
				if ($section['has_file']) {
					$srcAbs = $_SERVER['DOCUMENT_ROOT'] . $section['picture_path'];
					$ext = pathinfo($srcAbs, PATHINFO_EXTENSION) ?: 'jpg';
					$source = PolimerSectionImageProcessor::sourceDir() . '/' . $section['id'] . '_source.' . $ext;
					@copy($srcAbs, $source);
				}
			}

			if (!is_file($source)) {
				$failed[] = ['id' => $section['id'], 'reason' => 'no_source'];
				continue;
			}

			if (PolimerSectionImageProcessor::processFile($source, $paths['processed'])) {
				$done[] = [
					'id' => $section['id'],
					'name' => $section['name'],
					'output' => $paths['processed'],
					'size' => filesize($paths['processed']),
				];
			} else {
				$failed[] = ['id' => $section['id'], 'reason' => 'process_failed'];
			}
		}

		return ['processed' => count($done), 'failed' => $failed, 'items' => $done];
	}

	private static function actionApply(array $args): array
	{
		$params = self::parseArgs($args);
		$dryRun = !empty($params['dry-run']) || !empty($params['dry_run']);
		$sections = self::fetchSections($params);
		$applied = [];
		$failed = [];

		foreach ($sections as $section) {
			$processed = PolimerSectionImageProcessor::outputDir() . '/' . $section['id'] . '.png';
			if (!is_file($processed)) {
				$failed[] = ['id' => $section['id'], 'reason' => 'no_processed_png'];
				continue;
			}

			if ($dryRun) {
				$applied[] = ['id' => $section['id'], 'name' => $section['name'], 'dry_run' => true, 'file' => $processed];
				continue;
			}

			$fileArray = CFile::MakeFileArray($processed);
			if (empty($fileArray['tmp_name'])) {
				$failed[] = ['id' => $section['id'], 'reason' => 'make_file_array'];
				continue;
			}

			$fileArray['name'] = 'section_' . $section['id'] . '.png';
			$fileArray['MODULE_ID'] = 'iblock';

			$oldPictureId = $section['picture_id'];
			$bs = new CIBlockSection();
			if (!$bs->Update($section['id'], ['PICTURE' => $fileArray])) {
				$failed[] = ['id' => $section['id'], 'reason' => 'section_update', 'error' => $bs->LAST_ERROR];
				continue;
			}

			$newPictureId = 0;
			$updated = CIBlockSection::GetList([], ['ID' => $section['id'], 'IBLOCK_ID' => self::IBLOCK_ID], false, ['ID', 'PICTURE'])->Fetch();
			if ($updated) {
				$newPictureId = (int)($updated['PICTURE'] ?? 0);
			}

			if ($oldPictureId > 0 && $oldPictureId !== $newPictureId) {
				CFile::Delete($oldPictureId);
			}

			self::clearImageCaches();

			$applied[] = [
				'id' => $section['id'],
				'name' => $section['name'],
				'new_picture_id' => $newPictureId,
				'path' => $newPictureId > 0 ? (string)CFile::GetPath($newPictureId) : '',
			];
		}

		return ['applied' => count($applied), 'dry_run' => $dryRun, 'failed' => $failed, 'items' => $applied];
	}

	private static function actionRestore(array $args): array
	{
		$params = self::parseArgs($args);
		$ids = self::parseIds($params);
		$sources = glob(PolimerSectionImageProcessor::sourceDir() . '/*_source.*') ?: [];
		$restored = [];
		$failed = [];

		foreach ($sources as $sourcePath) {
			if (!preg_match('/\/(\d+)_source\./', $sourcePath, $match)) {
				continue;
			}

			$sectionId = (int)$match[1];
			if ($ids && !in_array($sectionId, $ids, true)) {
				continue;
			}

			$section = CIBlockSection::GetList(
				[],
				['ID' => $sectionId, 'IBLOCK_ID' => self::IBLOCK_ID],
				false,
				['ID', 'NAME', 'PICTURE']
			)->Fetch();

			if (!$section) {
				$failed[] = ['id' => $sectionId, 'reason' => 'no_section'];
				continue;
			}

			$fileArray = CFile::MakeFileArray($sourcePath);
			if (empty($fileArray['tmp_name'])) {
				$failed[] = ['id' => $sectionId, 'reason' => 'make_file_array'];
				continue;
			}

			$fileArray['MODULE_ID'] = 'iblock';
			$oldPictureId = (int)($section['PICTURE'] ?? 0);
			$bs = new CIBlockSection();
			if (!$bs->Update($sectionId, ['PICTURE' => $fileArray])) {
				$failed[] = ['id' => $sectionId, 'reason' => 'section_update', 'error' => $bs->LAST_ERROR];
				continue;
			}

			$newPictureId = 0;
			$updated = CIBlockSection::GetList([], ['ID' => $sectionId, 'IBLOCK_ID' => self::IBLOCK_ID], false, ['ID', 'PICTURE'])->Fetch();
			if ($updated) {
				$newPictureId = (int)($updated['PICTURE'] ?? 0);
			}

			if ($oldPictureId > 0 && $oldPictureId !== $newPictureId) {
				CFile::Delete($oldPictureId);
			}

			$restored[] = [
				'id' => $sectionId,
				'name' => $section['NAME'],
				'new_picture_id' => $newPictureId,
				'path' => $newPictureId > 0 ? (string)CFile::GetPath($newPictureId) : '',
				'source' => $sourcePath,
			];
		}

		if ($restored) {
			self::clearImageCaches();
		}

		return ['restored' => count($restored), 'failed' => $failed, 'items' => $restored];
	}

	private static function actionPipeline(array $args): array
	{
		$params = self::parseArgs($args);
		if (empty($params['ids']) && empty($params['all'])) {
			$params['depth'] = $params['depth'] ?? 2;
			$params['limit'] = $params['limit'] ?? 10;
		}

		return [
			'export' => self::actionExport($params),
			'process' => self::actionProcess($params),
			'apply' => self::actionApply($params),
		];
	}

	private static function actionPreview(array $args): void
	{
		$params = self::parseArgs($args);
		$sections = self::fetchSections($params);
		header('Content-Type: text/html; charset=utf-8');
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Section images preview</title>';
		echo '<style>
			body{font-family:system-ui,sans-serif;margin:24px;background:#f5f7fa}
			.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
			.card{background:#fff;border:1px solid #dde8f2;border-radius:12px;padding:16px}
			.card h3{margin:0 0 12px;font-size:15px}
			.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
			.box{border:1px solid #e5edf5;border-radius:8px;padding:12px;text-align:center;background:
				repeating-conic-gradient(#eee 0% 25%, #fff 0% 50%) 50% / 16px 16px}
			.box img{max-width:100%;max-height:140px;object-fit:contain}
			.lbl{font-size:12px;color:#64748b;margin-bottom:8px}
			.meta{font-size:12px;color:#475569;margin-top:10px}
			.actions{margin:16px 0 24px}
			.actions a{margin-right:12px}
		</style></head><body>';
		echo '<h1>Превью картинок разделов (до / после)</h1>';
		echo '<div class="actions"><a href="?action=list">JSON list</a>';
		echo ' <a href="?action=preview&depth=2&limit=20">depth=2</a></div>';
		echo '<div class="grid">';

		foreach ($sections as $section) {
			$processed = PolimerSectionImageProcessor::outputDir() . '/' . $section['id'] . '.png';
			$processedRel = is_file($processed)
				? '/upload/polimer/section_images/processed/' . $section['id'] . '.png'
				: '';
			echo '<div class="card"><h3>' . htmlspecialcharsbx($section['name']) . ' #' . (int)$section['id'] . '</h3><div class="row">';
			echo '<div><div class="lbl">Было</div><div class="box">';
			if ($section['has_file']) {
				echo '<img src="' . htmlspecialcharsbx($section['picture_path']) . '" alt="">';
			} else {
				echo '—';
			}
			echo '</div></div>';
			echo '<div><div class="lbl">Стало (PNG)</div><div class="box">';
			if ($processedRel !== '') {
				echo '<img src="' . htmlspecialcharsbx($processedRel) . '?t=' . filemtime($processed) . '" alt="">';
			} else {
				echo 'не обработано';
			}
			echo '</div></div></div>';
			echo '<div class="meta">apply: <code>php tools/catalog-section-images.php apply --ids=' . (int)$section['id'] . '</code></div>';
			echo '</div>';
		}

		echo '</div></body></html>';
	}

	private static function clearImageCaches(): void
	{
		$roots = [
			$_SERVER['DOCUMENT_ROOT'] . '/upload/resize_cache/menu_webp',
			$_SERVER['DOCUMENT_ROOT'] . '/upload/resize_cache/iblock',
		];
		foreach ($roots as $root) {
			if (is_dir($root)) {
				self::rmDirContents($root);
			}
		}
	}

	private static function rmDirContents(string $dir): void
	{
		$items = @scandir($dir);
		if (!$items) {
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$path = $dir . '/' . $item;
			if (is_dir($path)) {
				self::rmDirContents($path);
				@rmdir($path);
			} else {
				@unlink($path);
			}
		}
	}

	private static function out(array $data, bool $isCli): void
	{
		if ($isCli) {
			echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
			return;
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}
}
