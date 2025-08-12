@extends('admin.layout')

@section('title', 'Просмотр сайта')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>{{ $site->name }}</h2>
    <div>
        <a href="{{ route('admin.sites.edit', $site) }}" class="btn btn-warning me-2">
            <i class="bi bi-pencil me-2"></i>Редактировать
        </a>
        <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Назад к списку
        </a>
    </div>
</div>

<div class="row">
    <!-- Основная информация -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Основная информация</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>ID:</strong></td>
                        <td>#{{ $site->id }}</td>
                    </tr>
                    <tr>
                        <td><strong>Название:</strong></td>
                        <td>{{ $site->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Домен:</strong></td>
                        <td><code>{{ $site->domain }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>Статус:</strong></td>
                        <td>
                            @if($site->is_active)
                                <span class="badge bg-success">Активен</span>
                            @else
                                <span class="badge bg-secondary">Неактивен</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>API ключ:</strong></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <code class="me-2">{{ $site->api_key }}</code>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="copyToClipboard('{{ $site->api_key }}')"
                                        title="Копировать API ключ">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Дата создания:</strong></td>
                        <td>{{ $site->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- CRM настройки -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">CRM интеграция</h5>
                <a href="{{ route('admin.sites.crm-mapping.index', $site) }}" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-gear me-1"></i>Настройки маппинга
                </a>
            </div>
            <div class="card-body">
                @php $crmSettings = $site->getCrmSettings() @endphp
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Включена:</strong></td>
                        <td>
                            @if($crmSettings['enabled'] ?? false)
                                <span class="badge bg-success">Да</span>
                            @else
                                <span class="badge bg-secondary">Нет</span>
                            @endif
                        </td>
                    </tr>
                    @if($crmSettings['api_url'] ?? false)
                        <tr>
                            <td><strong>API URL:</strong></td>
                            <td><code>{{ $crmSettings['api_url'] }}</code></td>
                        </tr>
                    @endif
                    @if($crmSettings['entry_point'] ?? false)
                        <tr>
                            <td><strong>Точка входа:</strong></td>
                            <td>{{ $crmSettings['entry_point'] }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td><strong>Маппинг полей:</strong></td>
                        <td>
                            @if($site->crmMappings()->count() > 0)
                                <span class="badge bg-success">{{ $site->crmMappings()->count() }} полей</span>
                            @else
                                <span class="badge bg-secondary">Стандартный</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Статистика</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h3 class="text-primary">{{ $site->requests_count }}</h3>
                            <p class="text-muted mb-0">Всего заявок</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <h3 class="text-success">{{ $site->fields_count ?? 0 }}</h3>
                        <p class="text-muted mb-0">Полей формы</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Поля формы -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Поля формы</h5>
                <a href="{{ route('admin.sites.fields.index', $site) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-gear me-1"></i>Управление полями
                </a>
            </div>
            <div class="card-body">
                @if($site->fields->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($site->fields as $field)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $field->label }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $field->name }} ({{ $field->type }})</small>
                                </div>
                                <div>
                                    @if($field->required)
                                        <span class="badge bg-danger me-1">Обязательное</span>
                                    @endif
                                    @if($field->is_active)
                                        <span class="badge bg-success">Активно</span>
                                    @else
                                        <span class="badge bg-secondary">Неактивно</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center">Поля формы не настроены</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Последние заявки -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Последние заявки</h5>
        <a href="{{ route('admin.requests.index', ['site_id' => $site->id]) }}" class="btn btn-sm btn-outline-primary">
            Все заявки
        </a>
    </div>
    <div class="card-body">
        @if($recentRequests->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Телефон</th>
                            <th>Источник</th>
                            <th>Дата</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRequests as $request)
                            <tr>
                                <td>#{{ $request->id }}</td>
                                <td>{{ $request->getName() }}</td>
                                <td>{{ $request->getPhone() }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $request->getSource() }}</span>
                                </td>
                                <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.requests.show', $request) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">Заявок пока нет</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 p-3';
        toast.style.zIndex = '1050';
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header">
                    <strong class="me-auto">Успешно</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    API ключ скопирован в буфер обмена
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 3000);
    });
}
</script>
@endpush
