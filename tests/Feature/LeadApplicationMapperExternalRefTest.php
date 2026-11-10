<?php

namespace Tests\Feature;

use App\Models\FormRequest;
use App\Models\Site;
use App\Services\CrmNew\LeadApplicationMapper;
use Tests\TestCase;

/**
 * Проверяет, что маппер проставляет application.external_ref = "<source_id>:<id>"
 * для идемпотентности на стороне crmnew и опускает его при пустом source_id.
 */
class LeadApplicationMapperExternalRefTest extends TestCase
{
    /** Собрать несохранённые модели заявки и сайта (map() в БД не ходит). */
    private function makeLead(array $crmnew): array
    {
        $site = new Site;
        $site->name = 'Тестовый сайт';
        $site->domain = 'example.test';
        $site->settings = ['crmnew' => $crmnew];
        // Отношение полей заполняем заранее — чтобы buildComment не ходил в БД.
        $site->setRelation('fields', collect());

        $lead = new FormRequest;
        $lead->id = 59456;
        $lead->ip_address = '10.0.0.1';
        $lead->form_data = ['name' => 'Иван Петров', 'phone' => '89001234567'];

        return [$lead, $site];
    }

    public function test_external_ref_is_set_when_source_id_present(): void
    {
        [$lead, $site] = $this->makeLead(['source_id' => 37]);

        $payload = (new LeadApplicationMapper)->map($lead, $site);

        $this->assertSame('37:59456', $payload['application']['external_ref']);
    }

    public function test_external_ref_is_absent_when_source_id_empty(): void
    {
        [$lead, $site] = $this->makeLead(['source_id' => null]);

        $payload = (new LeadApplicationMapper)->map($lead, $site);

        // finalize() вычищает пустые значения — ключа быть не должно вовсе.
        $this->assertArrayNotHasKey('external_ref', $payload['application']);
    }
}
