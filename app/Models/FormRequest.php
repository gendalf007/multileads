<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormRequest extends Model
{
    protected $fillable = [
        'site_id', 
        'user_id',
        'form_data', 
        'source', 
        'ip_address', 
        'user_agent'
    ];
    
    protected $casts = [
        'form_data' => 'array'
    ];
    
    /**
     * Отношение к сайту
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    
    /**
     * Отношение к пользователю
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Получить данные формы
     */
    public function getFormData()
    {
        return $this->form_data ?? [];
    }
    
    /**
     * Получить значение поля из form_data
     */
    public function getFieldValue($fieldName)
    {
        $formData = $this->getFormData();
        return $formData[$fieldName] ?? null;
    }
    
    /**
     * Получить имя (для обратной совместимости)
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }
    
    /**
     * Получить телефон (для обратной совместимости)
     */
    public function getPhone()
    {
        return $this->getFieldValue('phone');
    }
    
    /**
     * Получить комментарий (для обратной совместимости)
     */
    public function getComment()
    {
        return $this->getFieldValue('comment');
    }
    
    /**
     * Получить источник заявки
     */
    public function getSource()
    {
        return $this->source ?? 'form';
    }
}
