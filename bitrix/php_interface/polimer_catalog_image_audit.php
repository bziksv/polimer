<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

class PolimerCatalogImageAudit
{
	public const IBLOCK_ID = 21;
	public const CARD_W = 220;
	public const CARD_H = 293;
	public const TARGET_RATIO = 3 / 4;

	public const PRIORITY_CRITICAL = 'critical';
	public const PRIORITY_URGENT = 'urgent';
	public const PRIORITY_HIGH = 'high';
	public const PRIORITY_MEDIUM = 'medium';
	public const PRIORITY_LOW = 'low';

	private const CACHE_REL = '/bitrix/cache/polimer/catalog_image_audit.json';
	private const CACHE_TTL = 86400;

	public static function getCachePath(): string
	{
		return $_SERVER['DOCUMENT_ROOT'] . self::CACHE_REL;
	}

	public static function isAllowed(): bool
	{
		global $USER;

		$addr = $_SERVER['REMOTE_ADDR'] ?? '';
		if (in_array($addr, ['127.0.0.1', '::1'], true)) {
			return true;
		}

		if (is_object($USER) && $USER->IsAdmin()) {
			return true;
		}

		$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
		$expected = (string)getenv('POLIMER_AUDIT_TOKEN');
		if ($expected !== '' && hash_equals($expected, $token)) {
			return true;
		}

		return false;
	}

	public static function loadCache(bool $allowStale = true): ?array
	{
		$path = self::getCachePath();
		if (!is_file($path)) {
			return null;
		}

		$raw = file_get_contents($path);
		if ($raw === false || $raw === '') {
			return null;
		}

		$data = json_decode($raw, true);
		if (!is_array($data) || empty($data['generated_at'])) {
			return null;
		}

		if (!$allowStale && (time() - (int)$data['generated_at']) > self::CACHE_TTL) {
			return null;
		}

		return $data;
	}

	public static function saveCache(array $data): void
	{
		$path = self::getCachePath();
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		$data['generated_at'] = time();
		$data['generated_at_human'] = date('Y-m-d H:i:s');
		file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}

	public static function buildReport(?callable $progress = null): array
	{
		if (!CModule::IncludeModule('iblock')) {
			throw new RuntimeException('iblock module not loaded');
		}

		$items = [];
		$stats = [
			'total' => 0,
			'checked' => 0,
			'issues' => 0,
			'by_priority' => [
				self::PRIORITY_CRITICAL => 0,
				self::PRIORITY_URGENT => 0,
				self::PRIORITY_HIGH => 0,
				self::PRIORITY_MEDIUM => 0,
				self::PRIORITY_LOW => 0,
			],
			'by_issue' => [],
		];

		$rs = CIBlockElement::GetList(
			['ID' => 'ASC'],
			['IBLOCK_ID' => self::IBLOCK_ID, 'ACTIVE' => 'Y'],
			false,
			false,
			['ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
		);

		while ($el = $rs->GetNext()) {
			$stats['total']++;
			$row = self::analyzeElement($el);
			if ($row['score'] >= 250) {
				$items[] = $row;
				$stats['issues']++;
				$stats['by_priority'][$row['priority']]++;
				foreach ($row['issues'] as $issue) {
					$code = $issue['code'];
					$stats['by_issue'][$code] = ($stats['by_issue'][$code] ?? 0) + 1;
				}
			}
			$stats['checked']++;

			if ($progress && ($stats['checked'] % 200) === 0) {
				$progress($stats['checked'], $stats['total']);
			}
		}

		usort($items, static function (array $a, array $b): int {
			if ($a['score'] !== $b['score']) {
				return $b['score'] <=> $a['score'];
			}
			return strcmp($a['name'], $b['name']);
		});

		return [
			'generated_at' => time(),
			'generated_at_human' => date('Y-m-d H:i:s'),
			'stats' => $stats,
			'items' => $items,
			'rules' => self::getRulesDescription(),
		];
	}

	public static function analyzeElement(array $el): array
	{
		$previewId = (int)($el['PREVIEW_PICTURE'] ?? 0);
		$detailId = (int)($el['DETAIL_PICTURE'] ?? 0);
		$fileId = $previewId ?: $detailId;

		$issues = [];
		$score = 0;

		if ($fileId <= 0) {
			$issues[] = self::issue('no_image', 'Нет превью и детальной картинки', 1000, self::PRIORITY_CRITICAL);
			return self::buildRow($el, $fileId, null, $issues, 1000, self::PRIORITY_CRITICAL);
		}

		if (!isImageExists($fileId)) {
			$issues[] = self::issue('file_missing', 'Файл картинки отсутствует на диске', 950, self::PRIORITY_CRITICAL);
			return self::buildRow($el, $fileId, null, $issues, 950, self::PRIORITY_CRITICAL);
		}

		$file = CFile::GetFileArray($fileId);
		$abs = $_SERVER['DOCUMENT_ROOT'] . CFile::GetPath($fileId);
		$meta = self::analyzeMeta($file, $abs);
		$issues = array_merge($issues, $meta['issues']);
		$score += $meta['score'];

		if ($meta['needs_fill_scan']) {
			$fill = self::analyzeFill($abs);
			if ($fill) {
				$meta = array_merge($meta, $fill);
				$fillIssues = self::issuesFromFill($fill);
				$issues = array_merge($issues, $fillIssues['issues']);
				$score += $fillIssues['score'];
			}
		}

		$priority = self::scoreToPriority($score, $issues);

		return self::buildRow($el, $fileId, $meta, $issues, $score, $priority);
	}

	private static function buildRow(array $el, int $fileId, ?array $meta, array $issues, int $score, string $priority): array
	{
		$previewSrc = '';
		if ($fileId > 0 && isImageExists($fileId)) {
			$previewSrc = resizeCatalogCardImage($fileId, self::CARD_W, self::CARD_H);
		}

		return [
			'id' => (int)$el['ID'],
			'name' => (string)$el['NAME'],
			'code' => (string)$el['CODE'],
			'url' => (string)$el['DETAIL_PAGE_URL'],
			'admin_url' => '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . self::IBLOCK_ID . '&type=catalog&ID=' . (int)$el['ID'] . '&lang=ru',
			'file_id' => $fileId,
			'preview_src' => $previewSrc,
			'original_src' => $fileId > 0 ? (CFile::GetPath($fileId) ?: '') : '',
			'score' => $score,
			'priority' => $priority,
			'priority_label' => self::priorityLabel($priority),
			'issues' => $issues,
			'width' => (int)($meta['width'] ?? 0),
			'height' => (int)($meta['height'] ?? 0),
			'ratio' => round((float)($meta['ratio'] ?? 0), 3),
			'card_fill_w' => round((float)($meta['card_fill_w'] ?? 0), 1),
			'card_fill_h' => round((float)($meta['card_fill_h'] ?? 0), 1),
			'pad_h_pct' => round((float)($meta['pad_h_pct'] ?? 0), 1),
			'pad_v_pct' => round((float)($meta['pad_v_pct'] ?? 0), 1),
			'subject_fill_w' => round((float)($meta['subject_fill_w'] ?? 0), 1),
			'subject_fill_h' => round((float)($meta['subject_fill_h'] ?? 0), 1),
		];
	}

	private static function analyzeMeta(array $file, string $absPath): array
	{
		$w = (int)($file['WIDTH'] ?? 0);
		$h = (int)($file['HEIGHT'] ?? 0);
		$issues = [];
		$score = 0;
		$needsFillScan = true;

		if ($w <= 0 || $h <= 0) {
			$info = @getimagesize($absPath);
			if ($info) {
				$w = (int)$info[0];
				$h = (int)$info[1];
			}
		}

		if ($w <= 0 || $h <= 0) {
			$issues[] = self::issue('bad_meta', 'Не удалось прочитать размеры файла', 620, self::PRIORITY_HIGH);
			return [
				'width' => 0,
				'height' => 0,
				'ratio' => 0,
				'issues' => $issues,
				'score' => 620,
				'needs_fill_scan' => false,
			];
		}

		$ratio = $w / $h;
		$minSide = min($w, $h);
		$cardFill = self::cardFill($w, $h);

		if ($minSide < 150) {
			$issues[] = self::issue('tiny', 'Слишком маленькая картинка (<150px)', 900, self::PRIORITY_CRITICAL);
			$score += 900;
			$needsFillScan = false;
		} elseif ($minSide < 220) {
			$issues[] = self::issue('very_small', 'Маленькая картинка (<220px)', 700, self::PRIORITY_URGENT);
			$score += 700;
		}

		if ($ratio < 0.45 || $ratio > 2.2) {
			$issues[] = self::issue('extreme_ratio', 'Экстремальные пропорции кадра', 650, self::PRIORITY_URGENT);
			$score += 650;
			$needsFillScan = false;
		} elseif ($ratio > 1.1) {
			$landscape = self::landscapeIssues($w, $h, $cardFill);
			$issues = array_merge($issues, $landscape['issues']);
			$score += $landscape['score'];
			$needsFillScan = false;
		} elseif ($cardFill['fill_h'] < 72 || $cardFill['fill_w'] < 72) {
			$issues[] = self::issue('letterbox', 'В карточке много пустого поля из‑за пропорций', 500, self::PRIORITY_HIGH);
			$score += 500;
		}

		// Анализ «внутренних» отступов только для более-менее портретных кадров.
		if ($needsFillScan && $ratio >= 0.55 && $ratio <= 0.9) {
			$needsFillScan = true;
		} else {
			$needsFillScan = false;
		}

		return [
			'width' => $w,
			'height' => $h,
			'ratio' => $ratio,
			'card_fill_w' => $cardFill['fill_w'],
			'card_fill_h' => $cardFill['fill_h'],
			'issues' => $issues,
			'score' => $score,
			'needs_fill_scan' => $needsFillScan,
		];
	}

	private static function cardFill(int $w, int $h): array
	{
		$fitScale = min(self::CARD_W / $w, self::CARD_H / $h);
		$renderW = $w * $fitScale;
		$renderH = $h * $fitScale;

		return [
			'fill_w' => ($renderW / self::CARD_W) * 100,
			'fill_h' => ($renderH / self::CARD_H) * 100,
			'render_w' => $renderW,
			'render_h' => $renderH,
		];
	}

	private static function predictScaleClass(int $w, int $h): string
	{
		$cardFill = self::cardFill($w, $h);
		$widthFill = $cardFill['fill_w'] / 100;
		$heightFill = $cardFill['fill_h'] / 100;
		$imageRatio = $w / $h;

		if ($imageRatio > 1.05) {
			if ($heightFill <= 0.5) {
				return 'wide';
			}
			return 'mid';
		}

		if ($heightFill >= 0.88 && $widthFill >= 0.5) {
			return 'compact';
		}
		if ($heightFill >= 0.88 && $widthFill < 0.5) {
			return 'tall';
		}
		if ($heightFill <= 0.7 || ($widthFill >= 0.78 && $heightFill <= 0.75)) {
			return 'tall';
		}
		if ($widthFill < 0.72 && $heightFill < 0.72) {
			return 'tall';
		}

		return 'mid';
	}

	private static function landscapeIssues(int $w, int $h, array $cardFill): array
	{
		$issues = [];
		$score = 0;
		$ratio = $w / $h;
		$scaleClass = self::predictScaleClass($w, $h);
		$scaleMap = [
			'compact' => 0.88,
			'tall' => 1.25,
			'wide' => 0.94,
			'mid' => 1.0,
		];
		$scale = $scaleMap[$scaleClass] ?? 1.0;
		$scaledW = $cardFill['render_w'] * $scale;

		// Старая логика давала scale-tall и обрезала края.
		$legacyWouldCrop = ($ratio > 1.1 && $cardFill['fill_h'] < 72 && $scaledW > self::CARD_W * 1.02)
			|| ($h > 0 && $w / $h > 1.1 && $cardFill['fill_h'] < 72);

		if ($legacyWouldCrop || ($ratio > 1.3 && $cardFill['fill_h'] < 60)) {
			$issues[] = self::issue(
				'landscape_crop',
				'Горизонтальная картинка обрезается в карточке — нужен кадр 3:4',
				820,
				self::PRIORITY_URGENT
			);
			$score += 820;
		} elseif ($ratio > 1.1 && $cardFill['fill_h'] < 72) {
			$issues[] = self::issue(
				'landscape_reframe',
				'Горизонтальный кадр — лучше переверстать в 3:4',
				720,
				self::PRIORITY_URGENT
			);
			$score += 720;
		}

		return ['issues' => $issues, 'score' => $score];
	}

	private static function analyzeFill(string $absPath): ?array
	{
		if (!function_exists('imagecreatefromjpeg')) {
			return null;
		}

		$info = @getimagesize($absPath);
		if (!$info) {
			return null;
		}

		$srcW = (int)$info[0];
		$srcH = (int)$info[1];
		$im = self::loadImage($absPath, (int)$info[2]);
		if (!$im) {
			return null;
		}

		$w = $srcW;
		$h = $srcH;
		$maxSide = 240;
		if ($w > $maxSide || $h > $maxSide) {
			$scale = min($maxSide / $w, $maxSide / $h);
			$nw = max(1, (int)round($w * $scale));
			$nh = max(1, (int)round($h * $scale));
			$scaled = imagescale($im, $nw, $nh);
			imagedestroy($im);
			if (!$scaled) {
				return null;
			}
			$im = $scaled;
			$w = $nw;
			$h = $nh;
		}

		$bg = self::detectBackground($im, $w, $h);
		$bounds = self::detectSubjectBounds($im, $w, $h, $bg);
		imagedestroy($im);

		if ($bounds === null) {
			return null;
		}

		$subjectW = $bounds['right'] - $bounds['left'] + 1;
		$subjectH = $bounds['bottom'] - $bounds['top'] + 1;
		$padL = $bounds['left'];
		$padR = $w - 1 - $bounds['right'];
		$padT = $bounds['top'];
		$padB = $h - 1 - $bounds['bottom'];

		return [
			'subject_fill_w' => ($subjectW / $w) * 100,
			'subject_fill_h' => ($subjectH / $h) * 100,
			'pad_h_pct' => (($padL + $padR) / 2 / $w) * 100,
			'pad_v_pct' => (($padT + $padB) / 2 / $h) * 100,
			'pad_left_pct' => ($padL / $w) * 100,
			'pad_right_pct' => ($padR / $w) * 100,
			'pad_top_pct' => ($padT / $h) * 100,
			'pad_bottom_pct' => ($padB / $h) * 100,
		];
	}

	private static function issuesFromFill(array $fill): array
	{
		$issues = [];
		$score = 0;

		if ($fill['subject_fill_h'] < 72 || $fill['subject_fill_w'] < 78) {
			$issues[] = self::issue('low_subject_fill', 'Много пустого поля вокруг товара', 560, self::PRIORITY_HIGH);
			$score += 560;
		}

		if ($fill['pad_v_pct'] > 10 || $fill['pad_h_pct'] > 10) {
			$issues[] = self::issue('excess_padding', 'Слишком большие отступы от краёв (>10%)', 420, self::PRIORITY_MEDIUM);
			$score += 420;
		} elseif ($fill['pad_v_pct'] > 8 || $fill['pad_h_pct'] > 8) {
			$issues[] = self::issue('padding_high', 'Отступы больше нормы (>8%)', 280, self::PRIORITY_MEDIUM);
			$score += 280;
		} elseif ($fill['pad_v_pct'] > 6 || $fill['pad_h_pct'] > 6) {
			$issues[] = self::issue('padding_mid', 'Отступы чуть больше оптимума (>6%)', 180, self::PRIORITY_LOW);
			$score += 180;
		}

		return ['issues' => $issues, 'score' => $score];
	}

	private static function loadImage(string $path, int $type)
	{
		switch ($type) {
			case IMAGETYPE_JPEG:
				return @imagecreatefromjpeg($path);
			case IMAGETYPE_PNG:
				return @imagecreatefrompng($path);
			case IMAGETYPE_WEBP:
				return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
			case IMAGETYPE_GIF:
				return @imagecreatefromgif($path);
			default:
				return null;
		}
	}

	private static function detectBackground($im, int $w, int $h): array
	{
		$margin = max(2, (int)round(min($w, $h) * 0.04));
		$rs = [];
		$gs = [];
		$bs = [];

		for ($y = 0; $y < $margin; $y++) {
			for ($x = 0; $x < $margin; $x++) {
				foreach ([
					[$x, $y],
					[$w - 1 - $x, $y],
					[$x, $h - 1 - $y],
					[$w - 1 - $x, $h - 1 - $y],
				] as $pt) {
					$rgb = imagecolorat($im, $pt[0], $pt[1]);
					$rs[] = ($rgb >> 16) & 0xFF;
					$gs[] = ($rgb >> 8) & 0xFF;
					$bs[] = $rgb & 0xFF;
				}
			}
		}

		sort($rs);
		sort($gs);
		sort($bs);
		$mid = (int)floor(count($rs) / 2);

		return ['r' => $rs[$mid], 'g' => $gs[$mid], 'b' => $bs[$mid]];
	}

	private static function detectSubjectBounds($im, int $w, int $h, array $bg, int $delta = 28, int $step = 2): ?array
	{
		$minX = $w;
		$maxX = -1;
		$minY = $h;
		$maxY = -1;

		for ($y = 0; $y < $h; $y += $step) {
			for ($x = 0; $x < $w; $x += $step) {
				$rgb = imagecolorat($im, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;

				$isBg = abs($r - $bg['r']) <= $delta
					&& abs($g - $bg['g']) <= $delta
					&& abs($b - $bg['b']) <= $delta;

				if (!$isBg && $lum < 245) {
					if ($x < $minX) {
						$minX = $x;
					}
					if ($x > $maxX) {
						$maxX = $x;
					}
					if ($y < $minY) {
						$minY = $y;
					}
					if ($y > $maxY) {
						$maxY = $y;
					}
				}
			}
		}

		if ($maxX < 0) {
			return null;
		}

		return [
			'left' => $minX,
			'right' => $maxX,
			'top' => $minY,
			'bottom' => $maxY,
		];
	}

	private static function issue(string $code, string $label, int $weight, string $priority): array
	{
		return [
			'code' => $code,
			'label' => $label,
			'weight' => $weight,
			'priority' => $priority,
		];
	}

	private static function scoreToPriority(int $score, array $issues = []): string
	{
		foreach ($issues as $issue) {
			if (in_array($issue['code'], ['no_image', 'file_missing', 'tiny'], true)) {
				return self::PRIORITY_CRITICAL;
			}
		}

		if ($score >= 650) {
			return self::PRIORITY_URGENT;
		}
		if ($score >= 450) {
			return self::PRIORITY_HIGH;
		}
		if ($score >= 250) {
			return self::PRIORITY_MEDIUM;
		}
		return self::PRIORITY_LOW;
	}

	public static function priorityLabel(string $priority): string
	{
		$labels = [
			self::PRIORITY_CRITICAL => 'Срочно',
			self::PRIORITY_URGENT => 'Очень важно',
			self::PRIORITY_HIGH => 'Важно',
			self::PRIORITY_MEDIUM => 'Средне',
			self::PRIORITY_LOW => 'Низкий приоритет',
		];

		return $labels[$priority] ?? $priority;
	}

	public static function getRulesDescription(): array
	{
		return [
			'card' => self::CARD_W . '×' . self::CARD_H . ' px, object-fit: contain',
			'target_ratio' => '3:4',
			'optimal_padding' => '4–6% от краёв',
			'priorities' => [
				self::PRIORITY_CRITICAL => 'Нет картинки / файл отсутствует / <150px',
				self::PRIORITY_URGENT => 'Горизонтальные обрезаются, <220px, экстремальные пропорции',
				self::PRIORITY_HIGH => 'Много пустого поля вокруг товара или letterbox в карточке',
				self::PRIORITY_MEDIUM => 'Отступы >8% или плохо вписывается в карточку',
				self::PRIORITY_LOW => 'Небольшие отклонения отступов (>6%)',
			],
		];
	}
}
