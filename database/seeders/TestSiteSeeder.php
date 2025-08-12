<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Site;
use App\Models\FormField;
use App\Models\FormRequest;

class TestSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем тестовый сайт
        $site = Site::create([
            'name' => 'Тестовый сайт',
            'domain' => 'test.local',
            'is_active' => true,
            'settings' => [
                'crm' => [
                    'api_url' => 'https://crm.example.com/api/leads',
                    'api_key' => 'test_crm_key',
                    'entry_point' => 'website_form',
                    'enabled' => true
                ],
                'design' => [
                    'primary_color' => '#667eea',
                    'secondary_color' => '#764ba2',
                    'button_style' => 'gradient'
                ],
                'notifications' => [
                    'email' => [
                        'enabled' => true,
                        'recipients' => ['manager@test.local']
                    ]
                ]
            ]
        ]);
        
        // Создаем поля формы
        $fields = [
            [
                'name' => 'name',
                'label' => 'Имя',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Введите ваше имя',
                'order' => 1
            ],
            [
                'name' => 'phone',
                'label' => 'Телефон',
                'type' => 'phone',
                'required' => true,
                'placeholder' => '+7 (___) ___-__-__',
                'order' => 2
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => false,
                'placeholder' => 'example@mail.com',
                'order' => 3
            ],
            [
                'name' => 'service',
                'label' => 'Выберите услугу',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'web', 'label' => 'Создание сайта'],
                    ['value' => 'seo', 'label' => 'SEO продвижение'],
                    ['value' => 'design', 'label' => 'Дизайн'],
                    ['value' => 'ads', 'label' => 'Контекстная реклама']
                ],
                'order' => 4
            ],
            [
                'name' => 'urgency',
                'label' => 'Срочность',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    ['value' => 'high', 'label' => 'Срочно'],
                    ['value' => 'medium', 'label' => 'Средняя'],
                    ['value' => 'low', 'label' => 'Не срочно']
                ],
                'order' => 5
            ],
            [
                'name' => 'comment',
                'label' => 'Комментарий',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Опишите вашу задачу',
                'order' => 6
            ]
        ];
        
        foreach ($fields as $fieldData) {
            FormField::create(array_merge($fieldData, ['site_id' => $site->id]));
        }
        
        $this->command->info('Тестовый сайт создан: test.local');
        $this->command->info('API ключ: ' . $site->api_key);
    }
}
