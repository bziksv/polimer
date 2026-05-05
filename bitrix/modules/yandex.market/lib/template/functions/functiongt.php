<?php
namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

/**
 * Class FunctionGt
 *
 * Шаблонная функция сравнения «больше чем».
 *
 * Использование в шаблоне:
 *   {=gt A B}  → вернёт true, если A > B, иначе false.
 *
 * Правила сравнения:
 * - Если оба операнда являются числовыми (is_numeric), используется числовое сравнение:
 *     (float)A > (float)B
 * - В остальных случаях — лексикографическое строковое сравнение:
 *     (string)A > (string)B
 *
 * Примеры:
 * - {=gt 5200 5000}                 → true
 * - {=gt "B" "A"}                   → true
 * - {=if {=gt catalog_price 3000} "premium" "base"}
 *
 * Возвращаемые значения:
 * - bool  — результат сравнения;
 * - null  — если передано менее двух аргументов.
 */
class FunctionGt extends Iblock\Template\Functions\FunctionBase implements HasConfiguration
{
    use Market\Reference\Concerns\HasLang;

    /**
     * Подключает языковые сообщения для текущего файла.
     *
     * @return void
     */
    protected static function includeMessages(): void
    {
        Main\Localization\Loc::loadMessages(__FILE__);
    }

    /**
     * Человекочитаемое название функции (для UI/подсказок).
     *
     * @return string
     */
    public function getTitle(): string
    {
        return static::getLang('TEMPLATE_FUNCTION_GT', null, 'gt');
    }

    /**
     * Признак множественности результата.
     * Для сравнения возвращается одно булево значение.
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return false;
    }

    /**
     * Выполняет сравнение A > B.
     *
     * @param array<int, mixed> $parameters Параметры функции:
     *   - [0] mixed $A Левый операнд (обязательный).
     *   - [1] mixed $B Правый операнд (обязательный).
     *
     * @return bool|null true, если A > B; false, если нет; null — если недостаточно аргументов.
     */
    public function calculate(array $parameters): bool|null
    {
        if (!isset($parameters[0], $parameters[1])) {
            return null;
        }

        [$a, $b] = $parameters;

        // Числовое сравнение при двух numeric-аргументах
        if (is_numeric($a) && is_numeric($b)) {
            return (float)$a > (float)$b;
        }

        // Иначе — строковое лексикографическое сравнение
        return (string)$a > (string)$b;
    }
}