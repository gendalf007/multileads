<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormRequest;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    /**
     * Отправка заявки через API
     */
    public function submit(Request $request)
    {
        $site = $request->get('site');
        $fields = $site->activeFields()->get();
        
        // Динамическая валидация на основе полей формы
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field->name] = $field->getValidationRules();
        }
        
        $validated = $request->validate($rules);
        
        // Нормализация телефона если есть
        if (isset($validated['phone'])) {
            $validated['phone'] = $this->normalizePhone($validated['phone']);
        }
        
        // Сохранение заявки
        $formRequest = FormRequest::create([
            'site_id' => $site->id,
            'user_id' => auth()->id(), // Добавляем ID авторизованного пользователя
            'form_data' => $validated,
            'source' => $request->source ?? 'api',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Отправка в CRM если настроено
        if ($site->getCrmSettings()['enabled'] ?? false) {
            $this->sendToCrm($formRequest, $site);
        }
        
        return response()->json([
            'success' => true,
            'id' => $formRequest->id,
            'message' => 'Заявка успешно отправлена'
        ]);
    }
    
    /**
     * Получение конфигурации сайта
     */
    public function getSiteConfig($domain)
    {
        $site = Site::where('domain', $domain)
            ->where('is_active', true)
            ->first();
            
        if (!$site) {
            return response()->json(['error' => 'Сайт не найден'], 404);
        }
        
        $fields = $site->activeFields()->get();
        
        return response()->json([
            'site' => [
                'name' => $site->name,
                'domain' => $site->domain,
                'design' => $site->getDesignSettings()
            ],
            'fields' => $fields->map(function($field) {
                return [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'required' => $field->required,
                    'placeholder' => $field->placeholder,
                    'options' => $field->getOptions(),
                    'attributes' => $field->getHtmlAttributes()
                ];
            })
        ]);
    }
    
    /**
     * Нормализация телефона
     */
    private function normalizePhone($phone)
    {
        // Очищаем телефон от всех символов, оставляем только цифры
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Если номер начинается с 8, заменяем на 7
        if (strlen($phone) == 11 && substr($phone, 0, 1) == '8') {
            $phone = '7' . substr($phone, 1);
        }
        
        // Если номер 10 цифр, добавляем 7 в начало
        if (strlen($phone) == 10) {
            $phone = '7' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Отправка в CRM
     */
    private function sendToCrm($formRequest, $site)
    {
        try {
            $formData = $formRequest->getFormData();
            
            // Подготавливаем данные для CRM на основе маппинга
            $crmData = $this->prepareCrmData($formData, $formRequest, $site);
            
            // Логируем данные, которые будут отправлены в CRM
            \Log::info('CRM API request (API)', [
                'site_id' => $site->id,
                'request_id' => $formRequest->id,
                'user_id' => $formRequest->user_id,
                'crm_url' => $site->getCrmApiUrl(),
                'sent_data' => $crmData
            ]);
            
            // Отправляем запрос в CRM (раскомментировать когда будет настроен CRM)
            $response = Http::withHeaders([
                'Authorization' => $site->getCrmApiKey(),
                'Content-Type' => 'application/json'
            ])->post($site->getCrmApiUrl(), $crmData);
            
            // Логирование результата ответа (раскомментировать когда будет настроен CRM)
            \Log::info('CRM API response (API)', [
                'site_id' => $site->id,
                'request_id' => $formRequest->id,
                'status' => $response->status(),
                'response' => $response->json(),
                'sent_data' => $crmData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('CRM API error (API)', [
                'site_id' => $site->id,
                'request_id' => $formRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Подготовка данных для CRM на основе маппинга из БД
     */
    private function prepareCrmData($formData, $formRequest, $site)
    {
        // Получаем данные из БД
        $crmData = $site->getCrmData($formData, $formRequest);
        
        // Если маппинг не настроен, используем стандартный
        if (empty($crmData)) {
            return [
                "phone" => $formData['phone'] ?? '',
                "name_first" => $formData['name'] ?? '',
                "name_last" => "",
                "name_middle" => "",
                "source_id" => $formRequest->id,
                "entry_point" => $site->getCrmEntryPoint(),
                "comment" => $formData['comment'] ?? '',
                "email" => $formData['email'] ?? '',
                "additional_data" => $formData
            ];
        }
        
        return $crmData;
    }
}
