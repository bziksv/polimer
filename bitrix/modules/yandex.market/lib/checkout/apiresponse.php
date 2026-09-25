<?php
namespace Yandex\Market\Checkout;

use Bitrix\Main\Web\Json;

/**
 * Результат работы обработчика API.
 *
 * Обработчики не печатают ответ сами, а возвращают объект этого класса.
 * Отправкой занимается Api::handleRequest() - единая точка вывода.
 */
class ApiResponse
{
    /** @var mixed Тело ответа */
    private $data;

    /** @var int HTTP-код ответа */
    private $httpCode;

    /**
     * @param mixed $data Тело ответа
     * @param int $httpCode HTTP-код ответа
     */
    private function __construct($data, $httpCode)
    {
        $this->data = $data;
        $this->httpCode = (int)$httpCode;
    }

    /**
     * Успешный ответ с произвольным кодом
     *
     * @param mixed $data Тело ответа
     * @param int $httpCode HTTP-код ответа
     * @return static
     */
    public static function create($data, $httpCode = 200)
    {
        return new static($data, $httpCode);
    }

    /**
     * Ответ 200 OK
     *
     * @param mixed $data Тело ответа
     * @return static
     */
    public static function ok($data)
    {
        return new static($data, 200);
    }

    /**
     * Ответ 201 Created
     *
     * @param mixed $data Тело ответа
     * @return static
     */
    public static function created($data)
    {
        return new static($data, 201);
    }

    /**
     * Ответ с ошибкой
     *
     * @param string $message Текст ошибки
     * @param int $httpCode HTTP-код ответа
     * @param array $extra Дополнительные поля тела ответа
     * @return static
     */
    public static function error($message, $httpCode = 500, array $extra = [])
    {
        return new static(array_merge(['error' => $message], $extra), $httpCode);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * Отправляет ответ клиенту
     *
     * Перед выводом отбрасывает всё, что могли напечатать в буфер ядро
     * и сторонние модули во время обработки запроса, иначе клиент получает
     * посторонний HTML перед JSON.
     *
     * @return void
     */
    public function send()
    {
        Api::resetOutputBuffer();

        if (!headers_sent()) {
            http_response_code($this->httpCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo Json::encode($this->data);
    }
}
