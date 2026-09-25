<?php
namespace Yandex\Market\Checkout\Handlers;

use Yandex\Market\Checkout\ApiRouter;

/**
 * Отдаёт список поддерживаемых модулем эндпоинтов.
 * Доступен только при валидном JWT-токене.
 */
class EndpointsHandler extends BaseHandler
{
    public function handle($orderId = null)
    {
        if ($unauthorized = $this->checkAuthorization()) {
            return $unauthorized;
        }

        try {
            return $this->response(ApiRouter::getSupportedEndpoints());
        } catch (\Exception $e) {
            return $this->error('Failed to get endpoints: ' . $e->getMessage(), 500);
        }
    }
}
