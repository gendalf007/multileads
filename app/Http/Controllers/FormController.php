<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormRequest;
use App\Models\Site;
use Illuminate\Support\Facades\Http;

class FormController extends Controller
{
    /**
     * Показать форму для конкретного домена (по пути)
     */
    public function showByDomain($siteDomain)
    {
        $site = Site::where('domain', $siteDomain)->where('is_active', true)->firstOrFail();
        $fields = $site->activeFields()->get();
        
        return view('form', compact('site', 'fields'));
    }
    
    /**
     * Показать форму для поддомена
     */
    public function showBySubdomain($subdomain, $domain)
    {
        $fullDomain = $subdomain . '.' . $domain;
        $site = Site::where('domain', $fullDomain)->where('is_active', true)->firstOrFail();
        $fields = $site->activeFields()->get();
        
        return view('form', compact('site', 'fields'));
    }
    
    /**
     * Отправить форму
     */
    public function submitForm(Request $request)
    {
        // Определяем сайт по домену
        $host = $request->getHost();
        $site = Site::where('domain', $host)->where('is_active', true)->firstOrFail();
        
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
            'form_data' => $validated,
            'source' => 'form',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Отправка в CRM если настроено
        if ($site->getCrmSettings()['enabled'] ?? false) {
            $this->sendToCrm($formRequest, $site);
        }
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно отправлена'
            ]);
        }
        
        return redirect()->back()->with('success', 'Заявка успешно отправлена');
    }
    
    /**
     * Получить конфигурацию сайта для API
     */
    public function getSiteConfig($domain)
    {
        $site = Site::where('domain', $domain)->where('is_active', true)->firstOrFail();
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
            
            $response = Http::withHeaders([
                'Authorization' => $site->getCrmApiKey(),
                'Content-Type' => 'application/json'
            ])->post($site->getCrmApiUrl(), $crmData);

            // Логирование результата
            \Log::info('CRM API response', [
                'site_id' => $site->id,
                'request_id' => $formRequest->id,
                'status' => $response->status(),
                'response' => $response->json(),
                'sent_data' => $crmData
            ]);
            
        } catch (\Exception $e) {
            
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
