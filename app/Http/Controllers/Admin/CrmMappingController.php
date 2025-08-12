<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\CrmMapping;
use Illuminate\Http\Request;

class CrmMappingController extends Controller
{
    /**
     * Показать страницу настройки CRM маппинга
     */
    public function index(Site $site)
    {
        $mappings = $site->crmMappings()->orderBy('order')->get();
        $fields = $site->activeFields()->get();
        
        return view('admin.sites.crm-mapping.index', compact('site', 'mappings', 'fields'));
    }
    
    /**
     * Сохранить настройки CRM маппинга
     */
    public function store(Request $request, Site $site)
    {
        $request->validate([
            'mappings' => 'nullable|array',
            'mappings.*.crm_field' => 'required_with:mappings|string|max:255',
            'mappings.*.mapping_value' => 'required_with:mappings|string|max:1000',
            'mappings.*.value_type' => 'required_with:mappings|in:field,static,template',
        ]);
        
        // Удаляем старые маппинги
        $site->crmMappings()->delete();
        
        // Создаем новые маппинги
        if ($request->has('mappings')) {
            foreach ($request->input('mappings', []) as $index => $mapping) {
                if (!empty($mapping['crm_field']) && !empty($mapping['mapping_value'])) {
                    CrmMapping::create([
                        'site_id' => $site->id,
                        'crm_field' => $mapping['crm_field'],
                        'mapping_value' => $mapping['mapping_value'],
                        'value_type' => $mapping['value_type'] ?? 'field',
                        'is_active' => true,
                        'order' => $index
                    ]);
                }
            }
        }
        
        return redirect()->route('admin.sites.crm-mapping.index', $site)
            ->with('success', 'Настройки CRM маппинга сохранены');
    }
    

}
