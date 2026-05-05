<?php
namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

/**
 * Class FunctionLte
 *
 * Шаблонная функция сравнения «меньше или равно».
 *
 * Использование:
 *   {=lte A B} → вернёт true, если A <= B, иначе false.
 *
 * Правила сравнения:
 * - Оба операнда numeric → числовое сравнение: (float)A <= (float)B
 * - Иначе → строковое лексикографическое сравнение: (string)A <= (string)B
 *
 * Возвращаемые значения:
 * - bool  — результат сравнения;
 * - null  — если передано < 2 аргументов.
 */
class FunctionLte extends Iblock\Template\Functions\FunctionBase implements HasConfiguration
{
    use Market\Reference\Concerns\HasLang;

    /** Подключает языковые сообщения для текущего файла. */
    protected static function includeMessages(): void
    {
        Main\Localization\Loc::loadMessages(__FILE__);
    }

    /** Человекочитаемое название функции (для UI/подсказок). */
    public function getTitle(): string
    {
        return static::getLang('TEMPLATE_FUNCTION_LTE', null, 'lte');
    }

    /** Функция возвращает одиночное булево значение. */
    public function isMultiple(): bool
    {
        return false;
    }

    /**
     * Выполняет сравнение A <= B.
     *
     * @param array<int, mixed> $parameters Параметры функции:
     *   - [0] mixed $A Левый операнд (обязательный).
     *   - [1] mixed $B Правый операнд (обязательный).
     *
     * @return bool|null true, если A <= B; false — иначе; null — если мало аргументов.
     */
    public function calculate(array $parameters): ?bool
    {
        if (!isset($parameters[0], $parameters[1])) {
            return null;
        }

        [$a, $b] = $parameters;

        if (is_numeric($a) && is_numeric($b)) {
            return (float)$a <= (float)$b;
        }

        return (string)$a <= (string)$b;
    }
}