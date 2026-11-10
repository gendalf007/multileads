<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;

class CrmNewSettingController extends Controller
{
    /**
     * Целевые поля crmnew, доступные для маппинга (значение => подпись).
     */
    public const TARGET_FIELDS = [
        'customer.first_name' => 'Клиент: Имя',
        'customer.last_name' => 'Клиент: Фамилия',
        'customer.middle_name' => 'Клиент: Отчество',
        'customer.phone' => 'Клиент: Телефон',
        'customer.ip_address' => 'Клиент: IP-адрес',
        'application.comment' => 'Заявка: Комментарий',
        'application.entry_point' => 'Заявка: Точка входа',
        'application.source_id' => 'Заявка: ID источника',
        'additional.comment' => 'Доп.: Комментарий',
    ];

    /**
     * Страница настроек интеграции crmnew для сайта.
     */
    public function index(Site $site)
    {
        $fields = $site->activeFields()->get();
        $crmnew = $site->getCrmNewSettings();
        $mappings = $crmnew['mapping'] ?? [];

        // Есть ли глобальный токен (fallback) — чтобы подсказать в UI
        $globalTokenSet = ! empty(config('services.crmnew.token'));

        return view('admin.sites.crmnew.index', [
            'site' => $site,
            'fields' => $fields,
            'crmnew' => $crmnew,
            'mappings' => $mappings,
            'targets' => self::TARGET_FIELDS,
            'globalTokenSet' => $globalTokenSet,
        ]);
    }

    /**
     * Сохранить настройки и маппинг crmnew.
     */
    public function store(Request $request, Site $site)
    {
        $request->validate([
            'enabled' => 'nullable|boolean',
            'api_token' => 'nullable|string|max:255',
            'source_id' => 'nullable|integer|min:1',
            'application_type_id' => 'nullable|integer|min:1',
            'entry_point' => 'nullable|string|max:255',
            'mappings' => 'nullable|array',
            'mappings.*.target' => 'required_with:mappings|string|in:'.implode(',', array_keys(self::TARGET_FIELDS)),
            'mappings.*.value' => 'required_with:mappings|string|max:1000',
            'mappings.*.type' => 'required_with:mappings|in:field,static,template',
        ]);

        // Нормализуем маппинг
        $mapping = [];
        foreach ($request->input('mappings', []) as $row) {
            if (! empty($row['target']) && isset($row['value']) && $row['value'] !== '') {
                $mapping[] = [
                    'target' => $row['target'],
                    'value' => $row['value'],
                    'type' => $row['type'] ?? 'field',
                ];
            }
        }

        // Сливаем в settings, не затирая прочие ключи (crm/design/notifications)
        $settings = $site->settings ?? [];
        $settings['crmnew'] = [
            'enabled' => $request->boolean('enabled'),
            'api_token' => $request->filled('api_token') ? $request->input('api_token') : null,
            'source_id' => $request->filled('source_id') ? (int) $request->input('source_id') : null,
            'application_type_id' => $request->filled('application_type_id') ? (int) $request->input('application_type_id') : null,
            'entry_point' => $request->filled('entry_point') ? $request->input('entry_point') : null,
            'mapping' => $mapping,
        ];

        $site->update(['settings' => $settings]);

        return redirect()->route('admin.sites.crmnew.index', $site)
            ->with('success', 'Настройки интеграции crmnew сохранены');
    }
}
