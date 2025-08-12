<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmMapping extends Model
{
    protected $fillable = [
        'site_id',
        'crm_field',
        'mapping_value',
        'value_type',
        'is_active',
        'order'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
    
    /**
     * Отношение к сайту
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    
    /**
     * Получить активные маппинги для сайта
     */
    public static function getActiveForSite($siteId)
    {
        return static::where('site_id', $siteId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }
    
    /**
     * Проверить, является ли значение статическим
     */
    public function isStatic()
    {
        return $this->value_type === 'static';
    }
    
    /**
     * Проверить, является ли значение шаблоном
     */
    public function isTemplate()
    {
        return $this->value_type === 'template';
    }
    
    /**
     * Проверить, является ли значение полем формы
     */
    public function isField()
    {
        return $this->value_type === 'field';
    }
    
    /**
     * Получить значение для отправки в CRM
     */
    public function getProcessedValue($formData, $formRequest, $site)
    {
        if ($this->isStatic()) {
            return $this->mapping_value;
        }
        
        // Проверяем, содержит ли mapping_value шаблонные переменные
        if ($this->containsTemplateVariables($this->mapping_value)) {
            // Если содержит переменные - обрабатываем как шаблон
            return $this->processTemplate($this->mapping_value, $formData, $formRequest, $site);
        }
        
        if ($this->isField()) {
            // Для простого поля формы - извлекаем имя поля из шаблона
            $fieldName = trim($this->mapping_value, '{}');
            return $formData[$fieldName] ?? '';
        }
        
        // Для шаблонов - обрабатываем через processTemplate
        return $this->processTemplate($this->mapping_value, $formData, $formRequest, $site);
    }
    
    /**
     * Проверить, содержит ли строка шаблонные переменные
     */
    private function containsTemplateVariables($value)
    {
        return preg_match('/\{[^}]+\}/', $value);
    }
    
    /**
     * Обработка шаблона с подстановкой переменных
     */
    private function processTemplate($template, $formData, $formRequest, $site)
    {
        $replacements = [
            // Системные данные
            '{source_id}' => $formRequest->id,
            '{site_entry_point}' => $site->getCrmEntryPoint(),
            '{site_name}' => $site->name,
            '{site_domain}' => $site->domain,
            
            // Все поля формы
            '{form_data}' => json_encode($formData),
            '{all_fields}' => json_encode($formData),
        ];
        
        // Добавляем все поля формы как переменные
        foreach ($formData as $fieldName => $fieldValue) {
            $replacements['{' . $fieldName . '}'] = $fieldValue;
            
            // Специальная обработка для поля name
            if ($fieldName === 'name') {
                $parts = explode(' ', trim($fieldValue));
                $replacements['{first_name}'] = $parts[0] ?? '';
                $replacements['{last_name}'] = $parts[1] ?? '';
                $replacements['{middle_name}'] = $parts[2] ?? '';
                $replacements['{operator_name}'] = $fieldValue;
            }
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
