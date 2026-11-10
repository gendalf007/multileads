@extends('admin.layout')

@section('title', 'Интеграция crmnew')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Интеграция crmnew: {{ $site->name }}</h2>
        <p class="text-muted mb-0">{{ $site->domain }}</p>
    </div>
    <div>
        <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Назад к сайту
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.sites.crmnew.store', $site) }}">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <!-- Подключение -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Подключение</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1"
                               {{ old('enabled', $crmnew['enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="enabled"><strong>Отправлять заявки в crmnew</strong></label>
                    </div>

                    <div class="mb-3">
                        <label for="api_token" class="form-label">API-токен подрядчика</label>
                        <input type="text" class="form-control" id="api_token" name="api_token"
                               value="{{ old('api_token', $crmnew['api_token'] ?? '') }}"
                               placeholder="Bearer-токен из crmnew">
                        <div class="form-text">
                            Если оставить пустым, используется глобальный токен из <code>.env</code> (CRMNEW_API_TOKEN):
                            @if($globalTokenSet)
                                <span class="badge bg-success">задан</span>
                            @else
                                <span class="badge bg-warning text-dark">не задан</span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="source_id" class="form-label">ID источника (source_id)</label>
                            <input type="number" min="1" class="form-control" id="source_id" name="source_id"
                                   value="{{ old('source_id', $crmnew['source_id'] ?? '') }}" placeholder="напр. 222">
                            <div class="form-text">Источник должен принадлежать подрядчику токена.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="application_type_id" class="form-label">ID типа заявки</label>
                            <input type="number" min="1" class="form-control" id="application_type_id" name="application_type_id"
                                   value="{{ old('application_type_id', $crmnew['application_type_id'] ?? '') }}" placeholder="опционально">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="entry_point" class="form-label">Точка входа (entry_point)</label>
                        <input type="text" class="form-control" id="entry_point" name="entry_point"
                               value="{{ old('entry_point', $crmnew['entry_point'] ?? '') }}"
                               placeholder="напр. lead-form/{{ $site->domain }}">
                    </div>
                </div>
            </div>

            <!-- Маппинг полей -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Маппинг полей</h5></div>
                <div class="card-body">
                    <p class="text-muted small">
                        Сопоставьте поля crmnew со значениями из заявки. Незаполненные поля заполняются
                        автоматически (имя разбивается на части, телефон нормализуется, email и поля сайта
                        попадают в комментарий).
                    </p>

                    <div class="row mb-2 fw-bold">
                        <div class="col-md-4">Поле crmnew</div>
                        <div class="col-md-5">Значение</div>
                        <div class="col-md-2">Тип</div>
                        <div class="col-md-1"></div>
                    </div>
                    <div id="mappingFields"></div>

                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMappingField()">
                        <i class="bi bi-plus-circle me-1"></i>Добавить поле
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Сохранить настройки
                </button>
            </div>
        </div>

        <!-- Справка -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Доступные переменные</h5></div>
                <div class="card-body">
                    <h6>Из имени:</h6>
                    <ul class="list-unstyled small">
                        <li><code>{first_name}</code> — имя</li>
                        <li><code>{last_name}</code> — фамилия</li>
                        <li><code>{middle_name}</code> — отчество</li>
                    </ul>
                    <h6>Системные:</h6>
                    <ul class="list-unstyled small">
                        <li><code>{source_id}</code> — ID заявки в lead-form</li>
                        <li><code>{site_name}</code> — название сайта</li>
                        <li><code>{site_domain}</code> — домен сайта</li>
                        <li><code>{site_entry_point}</code> — точка входа</li>
                    </ul>
                    <h6>Поля формы:</h6>
                    @if($fields->count() > 0)
                        <ul class="list-unstyled small">
                            @foreach($fields as $field)
                                <li><code>{{ '{' . $field->name . '}' }}</code> — {{ $field->label }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small">Нет настроенных полей</p>
                    @endif
                    <hr>
                    <p class="small text-muted mb-0">
                        <strong>Тип «Поле»</strong> — взять значение одного поля.<br>
                        <strong>«Шаблон»</strong> — текст с подстановкой <code>{переменных}</code>.<br>
                        <strong>«Статическое»</strong> — фиксированный текст.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
const TARGETS = @json($targets);

document.addEventListener('DOMContentLoaded', function () {
    const existing = @json(array_values($mappings));
    if (existing.length > 0) {
        existing.forEach(m => addMappingField(m.target, m.value, m.type));
    } else {
        // Дефолтный маппинг-подсказка
        addMappingField('customer.first_name', '{first_name}', 'template');
        addMappingField('customer.last_name', '{last_name}', 'template');
        addMappingField('customer.phone', '{phone}', 'field');
    }
});

function targetOptions(selected) {
    return Object.entries(TARGETS).map(([val, label]) =>
        `<option value="${val}" ${val === selected ? 'selected' : ''}>${label}</option>`
    ).join('');
}

function addMappingField(target = '', value = '', type = 'field') {
    const container = document.getElementById('mappingFields');
    const index = container.children.length;
    const html = `
        <div class="row mb-2 mapping-field align-items-center">
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="mappings[${index}][target]">
                    ${targetOptions(target)}
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control form-control-sm" name="mappings[${index}][value]"
                       placeholder="{phone}, {first_name}, текст…" value="${value.replace(/"/g, '&quot;')}">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="mappings[${index}][type]">
                    <option value="field" ${type === 'field' ? 'selected' : ''}>Поле</option>
                    <option value="template" ${type === 'template' ? 'selected' : ''}>Шаблон</option>
                    <option value="static" ${type === 'static' ? 'selected' : ''}>Статическое</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMappingField(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function removeMappingField(btn) {
    btn.closest('.mapping-field').remove();
    reindex();
}

function reindex() {
    document.querySelectorAll('.mapping-field').forEach((row, i) => {
        row.querySelectorAll('select')[0].name = `mappings[${i}][target]`;
        row.querySelector('input').name = `mappings[${i}][value]`;
        row.querySelectorAll('select')[1].name = `mappings[${i}][type]`;
    });
}
</script>
@endpush
