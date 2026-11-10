<?php

namespace App\Services\CrmNew;

use App\Models\FormRequest;
use App\Models\Site;

/**
 * Преобразует заявку lead-form (плоская form_data) во вложенный payload
 * Contractor API сервиса crmnew (customer / application / car / additional).
 *
 * Поведение: сначала строится автоматический payload (имя → части, телефон
 * нормализуется, email и поля сайта → комментарий), затем поверх него
 * накладывается пользовательский маппинг из settings.crmnew.mapping (если задан).
 */
class LeadApplicationMapper
{
    /** Заглушка для обязательной фамилии, когда в имени один токен. */
    private const LAST_NAME_PLACEHOLDER = '—';

    /** Целочисленные поля payload — приводим к int. */
    private const INT_PATHS = ['application.source_id', 'application.application_type_id'];

    public function map(FormRequest $lead, Site $site): array
    {
        $data = $lead->getFormData();
        $crmnew = $site->getCrmNewSettings();

        // 1. Автоматический базовый payload
        $payload = $this->autoPayload($data, $lead, $site, $crmnew);

        // 2. Накладываем пользовательский маппинг (переопределяет авто-значения)
        foreach ($crmnew['mapping'] ?? [] as $entry) {
            if (empty($entry['target'])) {
                continue;
            }
            $resolved = $this->resolveValue($entry['value'] ?? '', $entry['type'] ?? 'field', $data, $lead, $site);
            if ($resolved === '' || $resolved === null) {
                continue;
            }
            $this->setByPath($payload, $entry['target'], $resolved);
        }

        // 3. Гарантии корректности под контракт crmnew
        return $this->finalize($payload, $lead);
    }

    /**
     * Базовый payload по соглашению (без пользовательского маппинга).
     */
    private function autoPayload(array $data, FormRequest $lead, Site $site, array $crmnew): array
    {
        [$first, $last, $middle] = $this->splitName($data['name'] ?? '');

        $sourceId = $crmnew['source_id'] ?? null;

        return [
            'customer' => [
                'first_name' => $first,
                'last_name' => $last,
                'middle_name' => $middle,
                'phone' => $data['phone'] ?? null,
                'ip_address' => $lead->ip_address,
            ],
            'application' => [
                'source_id' => $sourceId,
                'application_type_id' => $crmnew['application_type_id'] ?? null,
                'entry_point' => $crmnew['entry_point']
                    ?? config('services.crmnew.entry_point')
                    ?? $site->domain,
                // Идемпотентность: crmnew дедупит заявки по паре (source_id, external_ref).
                // Ставим ref только когда source_id задан; иначе null — finalize() его вычистит.
                'external_ref' => $this->externalRef($sourceId, $lead),
                'comment' => $this->buildComment($data, $site),
            ],
        ];
    }

    /**
     * Внешний идентификатор заявки для дедупа на стороне crmnew: "<source_id>:<lead_id>".
     * Возвращает null, если source_id не задан (повтор без source_id смысла не имеет).
     */
    private function externalRef(mixed $sourceId, FormRequest $lead): ?string
    {
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        return $sourceId.':'.$lead->id;
    }

    /**
     * Привести payload к валидному виду: нормализация телефона, непустые
     * имя/фамилия, валидный IP, int-поля, очистка пустых значений.
     */
    private function finalize(array $payload, FormRequest $lead): array
    {
        $customer = $payload['customer'] ?? [];

        $customer['phone'] = $this->normalizePhone($customer['phone'] ?? null);
        $customer['ip_address'] = $this->validIp($customer['ip_address'] ?? null);

        if (empty($customer['first_name'])) {
            $customer['first_name'] = 'Без имени';
        }
        if (empty($customer['last_name'])) {
            $customer['last_name'] = self::LAST_NAME_PLACEHOLDER;
        }

        $payload['customer'] = $customer;

        // int-поля
        foreach (self::INT_PATHS as $path) {
            [$section, $key] = explode('.', $path);
            if (isset($payload[$section][$key]) && is_numeric($payload[$section][$key])) {
                $payload[$section][$key] = (int) $payload[$section][$key];
            }
        }

        // Убираем пустые значения и пустые секции
        foreach (['customer', 'application', 'car', 'additional'] as $section) {
            if (! isset($payload[$section]) || ! is_array($payload[$section])) {
                continue;
            }
            $payload[$section] = array_filter(
                $payload[$section],
                fn ($v) => $v !== null && $v !== ''
            );
            if (empty($payload[$section])) {
                unset($payload[$section]);
            }
        }

        return $payload;
    }

    /**
     * Вычислить значение маппинга по типу (field / static / template).
     */
    private function resolveValue(string $value, string $type, array $data, FormRequest $lead, Site $site): string
    {
        if ($type === 'static') {
            return $value;
        }

        $placeholders = $this->placeholders($data, $lead, $site);

        if ($type === 'field') {
            $name = trim($value, " \t{}");

            return $placeholders['{'.$name.'}'] ?? (string) ($data[$name] ?? '');
        }

        // template
        return strtr($value, $placeholders);
    }

    /**
     * Карта плейсхолдеров {ключ} => значение для маппинга.
     *
     * @return array<string,string>
     */
    private function placeholders(array $data, FormRequest $lead, Site $site): array
    {
        $map = [];
        foreach ($data as $k => $v) {
            if (is_scalar($v)) {
                $map['{'.$k.'}'] = (string) $v;
            }
        }

        $parts = preg_split('/\s+/', trim((string) ($data['name'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $map['{first_name}'] = $parts[0] ?? '';
        $map['{last_name}'] = $parts[1] ?? '';
        $map['{middle_name}'] = $parts[2] ?? '';

        $map['{source_id}'] = (string) $lead->id;
        $map['{site_name}'] = (string) $site->name;
        $map['{site_domain}'] = (string) $site->domain;
        $map['{site_entry_point}'] = (string) ($site->getCrmNewSettings()['entry_point'] ?? $site->domain);

        return $map;
    }

    /**
     * Записать значение по dot-пути (section.key) в payload.
     */
    private function setByPath(array &$payload, string $path, string $value): void
    {
        $segments = explode('.', $path);
        $ref = &$payload;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;

                return;
            }
            if (! isset($ref[$segment]) || ! is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    /**
     * Разбить имя на first/last/middle; crmnew требует непустую фамилию.
     *
     * @return array{0:string,1:string,2:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $first = $parts[0] ?? '';
        $last = $parts[1] ?? '';
        $middle = $parts[2] ?? '';

        if ($first === '') {
            $first = 'Без имени';
        }
        if ($last === '') {
            $last = self::LAST_NAME_PLACEHOLDER;
        }

        return [$first, $last, $middle];
    }

    /**
     * Собрать комментарий заявки: пользовательский комментарий + email
     * (в crmnew нет поля email) + контекстные поля сайта.
     */
    private function buildComment(array $data, Site $site): string
    {
        $lines = [];

        if (! empty($data['comment'])) {
            $lines[] = trim($data['comment']);
        }
        if (! empty($data['email'])) {
            $lines[] = 'Email: '.$data['email'];
        }

        $known = ['name', 'phone', 'email', 'comment'];
        foreach ($data as $key => $value) {
            if (in_array($key, $known, true) || $value === '' || $value === null || is_array($value)) {
                continue;
            }
            $lines[] = $this->fieldLabel($site, $key).': '.$value;
        }

        $lines[] = 'Источник: '.$site->name.' ('.$site->domain.')';

        return implode("\n", $lines);
    }

    /** Человекочитаемый label поля сайта (по form_fields), иначе — имя поля. */
    private function fieldLabel(Site $site, string $name): string
    {
        $field = $site->fields->firstWhere('name', $name);

        return $field->label ?? $name;
    }

    private function validIp(?string $ip): ?string
    {
        return $ip !== null && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    /**
     * Привести телефон к виду 7XXXXXXXXXX (только цифры) под regex crmnew
     * ^\+?[0-9]{10,15}$. Дублирует логику FormController::normalizePhone,
     * чтобы интеграция не зависела от того, был ли телефон нормализован при сохранении.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7'.substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        return $digits !== '' ? $digits : null;
    }
}
