<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) {
    return;
}

/**
 * Class FunctionLt
 *
 * Реализация шаблонной функции {=lt A B}, возвращающей true если A < B.
 * Поддерживаются числовые и строковые сравнения.
 */
class FunctionLt extends Iblock\Template\Functions\FunctionBase implements HasConfiguration
{
    use Market\Reference\Concerns\HasLang;

    /**
     * Подключает языковые сообщения для функции
     *
     * @return void
     */
    protected static function includeMessages(): void
    {
        Main\Localization\Loc::loadMessages(__FILE__);
    }

    /**
     * Возвращает заголовок функции (для UI)
     *
     * @return string
     */
    public function getTitle(): string
    {
        return static::getLang('TEMPLATE_FUNCTION_LT', null, 'lt');
    }

    /**
     * Может ли функция возвращать несколько значений
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return false;
    }

    /**
     * Основная логика вычисления.
     *
     * @param array<int, mixed> $parameters Параметры функции:
     *   - [0] левый операнд (обязательный)
     *   - [1] правый операнд (обязательный)
     *
     * @return bool|null true, если левый меньше правого;
     *                   false, если нет;
     *                   null, если аргументов недостаточно
     */
    public function calculate(array $parameters): bool|null
    {
        if (!isset($parameters[0], $parameters[1])) {
            return null;
        }

        $left = $parameters[0];
        $right = $parameters[1];

        // Числовое сравнение
        if (is_numeric($left) && is_numeric($right)) {
            return (float)$left < (float)$right;
        }

        // Строковое сравнение
        return (string)$left < (string)$right;
    }
}