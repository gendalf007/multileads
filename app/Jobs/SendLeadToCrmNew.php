<?php

namespace App\Jobs;

use App\Models\FormRequest;
use App\Services\CrmNew\CrmNewClient;
use App\Services\CrmNew\LeadApplicationMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Отправляет заявку (FormRequest) в CRM crmnew через Contractor API.
 * Отдельный канал, не связанный с legacy-методом FormController::sendToCrm.
 */
class SendLeadToCrmNew implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Число попыток (повторов) при временных ошибках. */
    public int $tries = 3;

    public function __construct(public int $formRequestId)
    {
    }

    /**
     * Удобный фабричный метод — принимает модель или id.
     */
    public static function for(FormRequest|int $lead): self
    {
        return new self($lead instanceof FormRequest ? $lead->id : $lead);
    }

    /** Задержки между повторами, сек. */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(CrmNewClient $client, LeadApplicationMapper $mapper): void
    {
        $lead = FormRequest::with('site')->find($this->formRequestId);

        if (! $lead || ! $lead->site) {
            return;
        }

        $site = $lead->site;
        $settings = $site->getCrmNewSettings();

        if (! ($settings['enabled'] ?? false)) {
            return;
        }

        $token = $settings['api_token'] ?? config('services.crmnew.token');

        if (empty($token)) {
            Log::warning('crmnew: пропуск отправки — не задан api_token', [
                'site_id' => $site->id,
                'request_id' => $lead->id,
            ]);

            return;
        }

        $payload = $mapper->map($lead, $site);
        $response = $client->createApplication($payload, $token);

        if ($response->successful()) {
            Log::info('crmnew: заявка отправлена', [
                'site_id' => $site->id,
                'request_id' => $lead->id,
                'application_id' => $response->json('data.application_id'),
                'customer_id' => $response->json('data.customer_id'),
            ]);

            return;
        }

        // 422 — данные не пройдут и при повторе: фиксируем и не ретраим.
        if ($response->status() === 422) {
            Log::warning('crmnew: заявка отклонена валидацией (422)', [
                'site_id' => $site->id,
                'request_id' => $lead->id,
                'errors' => $response->json('errors'),
                'payload' => $payload,
            ]);

            $this->fail(new \RuntimeException('crmnew validation failed (422)'));

            return;
        }

        // 401/403/5xx/сетевые — бросаем исключение для авто-повтора.
        Log::error('crmnew: ошибка отправки, будет повтор', [
            'site_id' => $site->id,
            'request_id' => $lead->id,
            'status' => $response->status(),
            'body' => $response->body(),
            'attempt' => $this->attempts(),
        ]);

        throw new \RuntimeException('crmnew request failed with status '.$response->status());
    }
}
