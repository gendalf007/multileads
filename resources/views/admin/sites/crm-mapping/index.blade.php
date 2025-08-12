@extends('admin.layout')

@section('title', 'CRM Маппинг')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>CRM Маппинг: {{ $site->name }}</h2>
        <p class="text-muted mb-0">{{ $site->domain }}</p>
    </div>
    <div>
        <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-2"></i>Назад к сайту
        </a>

    </div>
</div>

<div class="row">
    <!-- Настройки маппинга -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Настройка маппинга полей</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sites.crm-mapping.store', $site) }}" id="mappingForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Маппинг полей CRM</label>
                        <div id="mappingContainer">
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <strong>Поле CRM</strong>
                                </div>
                                <div class="col-md-5">
                                    <strong>Значение</strong>
                                </div>
                                <div class="col-md-2">
                                    <strong>Тип</strong>
                                </div>
                                <div class="col-md-2">
                                    <strong>Действия</strong>
                                </div>
                            </div>
                            
                            <div id="mappingFields">
                                <!-- Поля маппинга будут добавляться динамически -->
                            </div>
                            
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMappingField()">
                                <i class="bi bi-plus-circle me-1"></i>Добавить поле
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Сохранить маппинг
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Справочная информация -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Доступные переменные</h5>
            </div>
            <div class="card-body">
                <h6>Системные данные:</h6>
                <ul class="list-unstyled small">
                    <li><code>{source_id}</code> - ID заявки</li>
                    <li><code>{site_entry_point}</code> - точка входа сайта</li>
                    <li><code>{site_name}</code> - название сайта</li>
                    <li><code>{site_domain}</code> - домен сайта</li>
                    <li><code>{form_data}</code> - все данные формы (JSON)</li>
                </ul>
                
                <h6>Поля формы сайта:</h6>
                @if($fields->count() > 0)
                    <ul class="list-unstyled small">
                        @foreach($fields as $field)
                            <li><code>{{ '{' . $field->name . '}' }}</code> - {{ $field->label }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted small">Нет настроенных полей</p>
                @endif
            </div>
        </div>
        

    </div>
</div>


@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Загружаем существующий маппинг из БД
    const existingMappings = @json($mappings);
    if (existingMappings.length > 0) {
        existingMappings.forEach(mapping => {
            addMappingField(mapping.crm_field, mapping.mapping_value, mapping.value_type);
        });
    } else {
        // Добавляем только системные поля
        addMappingField('source_id', '{source_id}', 'field');
        addMappingField('entry_point', '{site_entry_point}', 'field');
    }
});

function addMappingField(crmField = '', value = '', valueType = 'field') {
    const container = document.getElementById('mappingFields');
    const index = container.children.length;
    
    const fieldHtml = `
        <div class="row mb-2 mapping-field">
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" 
                       name="mappings[${index}][crm_field]" 
                       placeholder="phone, name_first, etc." 
                       value="${crmField}">
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control form-control-sm" 
                       name="mappings[${index}][mapping_value]" 
                       placeholder="{phone}, {name}, etc." 
                       value="${value}">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="mappings[${index}][value_type]">
                    <option value="field" ${valueType === 'field' ? 'selected' : ''}>Поле</option>
                    <option value="static" ${valueType === 'static' ? 'selected' : ''}>Статическое</option>
                    <option value="template" ${valueType === 'template' ? 'selected' : ''}>Шаблон</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMappingField(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', fieldHtml);
}

function removeMappingField(button) {
    button.closest('.mapping-field').remove();
    reindexMappingFields();
}

function reindexMappingFields() {
    const fields = document.querySelectorAll('.mapping-field');
    fields.forEach((field, index) => {
        const inputs = field.querySelectorAll('input');
        const selects = field.querySelectorAll('select');
        inputs[0].name = `mappings[${index}][crm_field]`;
        inputs[1].name = `mappings[${index}][mapping_value]`;
        selects[0].name = `mappings[${index}][value_type]`;
    });
}


</script>
@endpush
