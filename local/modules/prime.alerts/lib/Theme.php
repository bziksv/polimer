<?php

namespace Prime\Alerts;

class Theme
{
	public const DEFAULT = 'polimer';

	/**
	 * @return array<string,array{
	 *   title:string,
	 *   hint:string,
	 *   accent:string,
	 *   bg:string,
	 *   border:string,
	 *   fact:string,
	 *   muted:string,
	 *   snooze:string,
	 *   text:string
	 * }>
	 */
	public static function all(): array
	{
		return [
			'polimer' => [
				'title' => 'Синий',
				'hint' => 'Классический синий акцент',
				'accent' => '#0464bb',
				'bg' => '#f5f8fc',
				'border' => '#7cadd9',
				'fact' => '#e8f0f8',
				'muted' => '#5a7a9a',
				'snooze' => '#0464bb',
				'text' => '#333333',
			],
			'shop' => [
				'title' => 'Терракота',
				'hint' => 'Тёплый кирпичный, под акценты магазина',
				'accent' => '#a64221',
				'bg' => '#fffdf6',
				'border' => '#e0c36a',
				'fact' => '#e8d7a0',
				'muted' => '#8a7a55',
				'snooze' => '#7a6a4a',
				'text' => '#404040',
			],
			'amber' => [
				'title' => 'Янтарь',
				'hint' => 'Классическое предупреждение',
				'accent' => '#b8860b',
				'bg' => '#fffbeb',
				'border' => '#e8d48a',
				'fact' => '#f0e2a8',
				'muted' => '#8a7340',
				'snooze' => '#7a6530',
				'text' => '#3f3a2a',
			],
			'graphite' => [
				'title' => 'Графит',
				'hint' => 'Сдержанный, без «тревожного» жёлтого',
				'accent' => '#3d3d3d',
				'bg' => '#f6f6f4',
				'border' => '#cfcfc9',
				'fact' => '#ddddd8',
				'muted' => '#6f6f6a',
				'snooze' => '#5c5c58',
				'text' => '#2e2e2c',
			],
			'forest' => [
				'title' => 'Хвойный',
				'hint' => 'Спокойный зелёный',
				'accent' => '#3f5d3a',
				'bg' => '#f4f7f2',
				'border' => '#c5d4bf',
				'fact' => '#d5e0d1',
				'muted' => '#5d6e58',
				'snooze' => '#4d5c49',
				'text' => '#2c352a',
			],
			'steel' => [
				'title' => 'Сталь',
				'hint' => 'Холодный сине-серый',
				'accent' => '#3d5a73',
				'bg' => '#f3f6f8',
				'border' => '#c5d2dc',
				'fact' => '#d5e0e8',
				'muted' => '#5a6d7a',
				'snooze' => '#4a5c68',
				'text' => '#2a343c',
			],
			'wine' => [
				'title' => 'Бордо',
				'hint' => 'Более строгий, «юридический» тон',
				'accent' => '#7a2430',
				'bg' => '#fbf6f6',
				'border' => '#e0c8cb',
				'fact' => '#ead6d8',
				'muted' => '#8a5e63',
				'snooze' => '#6e4a4e',
				'text' => '#3a2a2c',
			],
		];
	}

	public static function currentId(): string
	{
		return self::normalize(Config::get('color_scheme', self::DEFAULT));
	}

	public static function normalize(string $id): string
	{
		$id = strtolower(trim($id));
		$all = self::all();

		return isset($all[$id]) ? $id : self::DEFAULT;
	}

	/** @return array<string,string> */
	public static function current(): array
	{
		$all = self::all();

		return $all[self::currentId()];
	}

	public static function cssBlock(): string
	{
		$t = self::current();
		$lines = [
			'--pa-accent:' . $t['accent'],
			'--pa-bg:' . $t['bg'],
			'--pa-border:' . $t['border'],
			'--pa-fact:' . $t['fact'],
			'--pa-muted:' . $t['muted'],
			'--pa-snooze:' . $t['snooze'],
			'--pa-text:' . $t['text'],
		];

		return '<style id="prime-alerts-theme">:root{' . implode(';', $lines) . ';}</style>';
	}
}
