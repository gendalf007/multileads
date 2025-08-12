<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Site;
use App\Models\CrmMapping;

class CrmMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Найдем первый сайт
        $site = Site::first();
        
        if (!$site) {
            $this->command->info('Сайты не найдены. Сначала создайте сайт.');
            return;
        }
        
        // Создаем пример маппинга
        $mappings = [
            [
                'crm_field' => 'phone',
                'mapping_value' => '{phone}',
                'value_type' => 'field',
                'order' => 0
            ],
            [
                'crm_field' => 'name_first',
                'mapping_value' => '{first_name}',
                'value_type' => 'field',
                'order' => 1
            ],
            [
                'crm_field' => 'name_last',
                'mapping_value' => '{last_name}',
                'value_type' => 'field',
                'order' => 2
            ],
            [
                'crm_field' => 'name_middle',
                'mapping_value' => '{middle_name}',
                'value_type' => 'field',
                'order' => 3
            ],
            [
                'crm_field' => 'source_id',
                'mapping_value' => '{source_id}',
                'value_type' => 'field',
                'order' => 4
            ],
            [
                'crm_field' => 'entry_point',
                'mapping_value' => '{site_entry_point} {operator}',
                'value_type' => 'template',
                'order' => 5
            ],
            [
                'crm_field' => 'comment',
                'mapping_value' => '{comment}',
                'value_type' => 'field',
                'order' => 6
            ]

        ];
        
        foreach ($mappings as $mapping) {
            CrmMapping::create([
                'site_id' => $site->id,
                'crm_field' => $mapping['crm_field'],
                'mapping_value' => $mapping['mapping_value'],
                'value_type' => $mapping['value_type'],
                'is_active' => true,
                'order' => $mapping['order']
            ]);
        }
        
        $this->command->info("Создан маппинг CRM для сайта: {$site->name}");
    }
}
