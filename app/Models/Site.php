<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Site extends Model
{
    protected $fillable = [
        'name', 
        'domain', 
        'api_key', 
        'is_active', 
        'settings'
    ];
    
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean'
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        // Автоматически генерируем API ключ при создании
        static::creating(function ($site) {
            if (empty($site->api_key)) {
                $site->api_key = Str::random(64);
            }
        });
    }
    
    /**
     * Отношение к полям формы
     */
    public function fields()
    {
        return $this->hasMany(FormField::class);
    }
    
    /**
     * Отношение к заявкам
     */
    public function requests()
    {
        return $this->hasMany(FormRequest::class);
    }
    
    /**
     * Получить активные поля формы
     */
    public function activeFields()
    {
        return $this->fields()->where('is_active', true)->orderBy('order');
    }
    
    /**
     * Получить настройки CRM
     */
    public function getCrmSettings()
    {
        return $this->settings['crm'] ?? [];
    }
    
    /**
     * Получить настройки дизайна
     */
    public function getDesignSettings()
    {
        return $this->settings['design'] ?? [];
    }
    
    /**
     * Получить настройки уведомлений
     */
    public function getNotificationSettings()
    {
        return $this->settings['notifications'] ?? [];
    }
    
    /**
     * Получить настройки валидации
     */
    public function getValidationSettings()
    {
        return $this->settings['validation'] ?? [];
    }
    
    /**
     * Получить маппинги CRM из БД
     */
    public function crmMappings()
    {
        return $this->hasMany(CrmMapping::class);
    }
    
    /**
     * Получить активные маппинги CRM
     */
    public function getActiveCrmMappings()
    {
        return $this->crmMappings()->where('is_active', true)->orderBy('order')->get();
    }
    
    /**
     * Получить данные для отправки в CRM
     */
    public function getCrmData($formData, $formRequest)
    {
        $mappings = $this->getActiveCrmMappings();
        $crmData = [];
        
        foreach ($mappings as $mapping) {
            $crmData[$mapping->crm_field] = $mapping->getProcessedValue($formData, $formRequest, $this);
        }
        
        return $crmData;
    }
    
    /**
     * Проверить, активен ли сайт
     */
    public function isActive()
    {
        return $this->is_active;
    }
    
    /**
     * Получить URL CRM API
     */
    public function getCrmApiUrl()
    {
        return $this->getCrmSettings()['api_url'] ?? config('app.crm_api_url');
    }
    
    /**
     * Получить ключ CRM API
     */
    public function getCrmApiKey()
    {
        return $this->getCrmSettings()['api_key'] ?? config('app.crm_api_key');
    }
    
    /**
     * Получить точку входа CRM
     */
    public function getCrmEntryPoint()
    {
        return $this->getCrmSettings()['entry_point'] ?? config('app.crm_api_entry_point');
    }
}
