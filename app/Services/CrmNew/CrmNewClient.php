<?php

namespace App\Services\CrmNew;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Тонкий HTTP-клиент Contractor API сервиса crmnew.
 *
 * @see http://crmnew.test/docs/api
 */
class CrmNewClient
{
    /**
     * Создать заявку в crmnew.
     *
     * @param  array  $payload  вложенная структура customer/application/car/additional
     * @param  string  $token   Bearer-токен подрядчика
     */
    public function createApplication(array $payload, string $token): Response
    {
        return Http::baseUrl(rtrim((string) config('services.crmnew.base_url'), '/'))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.crmnew.timeout', 15))
            ->post('/api/contractor/applications', $payload);
    }
}
