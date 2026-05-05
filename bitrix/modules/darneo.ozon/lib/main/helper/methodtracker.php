<?php

namespace Darneo\Ozon\Main\Helper;

use Bitrix\Main\Localization\Loc;

class MethodTracker
{
    public static function internalMethod(): string
    {
        $backtrace = debug_backtrace();
        $caller = $backtrace[1];

        $class = $caller['class'] ?? null;
        $function = $caller['function'];
        $file = $caller['file'];
        $line = $caller['line'];

        if ($class !== null) {
            return Loc::getMessage('DARNEO_OZON_MAIN_HELPER_TRACKER_LOG_1', [
                '#CLASS#' => $class,
                '#FUNCTION#' => $function,
                '#FILE#' => $file,
                '#LINE#' => $line
            ]);
        }

        return Loc::getMessage('DARNEO_OZON_MAIN_HELPER_TRACKER_LOG_2', [
            '#FUNCTION#' => $function,
            '#FILE#' => $file,
            '#LINE#' => $line
        ]);
    }
}
