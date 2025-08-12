<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sites = Site::withCount('requests')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('admin.sites.index', compact('sites'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sites.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:sites,domain',
        ]);

        // Подготавливаем маппинг CRM
        $crmMapping = [];
        if ($request->has('crm.mapping')) {
            foreach ($request->input('crm.mapping', []) as $mapping) {
                if (!empty($mapping['field']) && !empty($mapping['value'])) {
                    $crmMapping[$mapping['field']] = $mapping['value'];
                }
            }
        }
        
        $site = Site::create([
            'name' => $request->name,
            'domain' => $request->domain,
            'api_key' => Str::random(64),
            'is_active' => $request->boolean('is_active', true),
            'settings' => [
                'crm' => [
                    'api_url' => $request->input('crm.api_url'),
                    'api_key' => $request->input('crm.api_key'),
                    'entry_point' => $request->input('crm.entry_point', 'website_form'),
                    'entry_point_template' => $request->input('crm.entry_point_template', '{site_entry_point} {operator_name}'),
                    'enabled' => $request->boolean('crm.enabled', false),
                    'mapping' => $crmMapping
                ],
                'design' => [
                    'primary_color' => $request->input('design.primary_color', '#667eea'),
                    'secondary_color' => $request->input('design.secondary_color', '#764ba2'),
                    'button_style' => $request->input('design.button_style', 'gradient')
                ],
                'notifications' => [
                    'email' => [
                        'enabled' => $request->boolean('notifications.email.enabled', false),
                        'recipients' => array_filter(explode(',', $request->input('notifications.email.recipients', '')))
                    ]
                ]
            ]
        ]);

        return redirect()->route('admin.sites.index')
            ->with('success', 'Сайт успешно создан. API ключ: ' . $site->api_key);
    }

    /**
     * Display the specified resource.
     */
    public function show(Site $site)
    {
        $site->load(['fields' => function($query) {
            $query->orderBy('order');
        }]);
        
        // Загружаем последние заявки отдельно
        $recentRequests = $site->requests()
            ->latest()
            ->take(20)
            ->get();
        
        // Добавляем счетчики
        $site->requests_count = $site->requests()->count();
        $site->fields_count = $site->fields()->count();
        
        return view('admin.sites.show', compact('site', 'recentRequests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        return view('admin.sites.edit', compact('site'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:sites,domain,' . $site->id,
        ]);

        // Подготавливаем маппинг CRM
        $crmMapping = [];
        if ($request->has('crm.mapping')) {
            foreach ($request->input('crm.mapping', []) as $mapping) {
                if (!empty($mapping['field']) && !empty($mapping['value'])) {
                    $crmMapping[$mapping['field']] = $mapping['value'];
                }
            }
        }
        
        $site->update([
            'name' => $request->name,
            'domain' => $request->domain,
            'is_active' => $request->boolean('is_active', true),
            'settings' => [
                'crm' => [
                    'api_url' => $request->input('crm.api_url'),
                    'api_key' => $request->input('crm.api_key'),
                    'entry_point' => $request->input('crm.entry_point', 'website_form'),
                    'entry_point_template' => $request->input('crm.entry_point_template', '{site_entry_point} {operator_name}'),
                    'enabled' => $request->boolean('crm.enabled', false),
                    'mapping' => $crmMapping
                ],
                'design' => [
                    'primary_color' => $request->input('design.primary_color', '#667eea'),
                    'secondary_color' => $request->input('design.secondary_color', '#764ba2'),
                    'button_style' => $request->input('design.button_style', 'gradient')
                ],
                'notifications' => [
                    'email' => [
                        'enabled' => $request->boolean('notifications.email.enabled', false),
                        'recipients' => array_filter(explode(',', $request->input('notifications.email.recipients', '')))
                    ]
                ]
            ]
        ]);

        return redirect()->route('admin.sites.index')
            ->with('success', 'Сайт успешно обновлен');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        $site->delete();
        
        return redirect()->route('admin.sites.index')
            ->with('success', 'Сайт успешно удален');
    }
    
    /**
     * Regenerate API key
     */
    public function regenerateApiKey(Site $site)
    {
        $site->update(['api_key' => Str::random(64)]);
        
        return redirect()->back()
            ->with('success', 'API ключ обновлен: ' . $site->api_key);
    }
}
