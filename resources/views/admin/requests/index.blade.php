@extends('admin.layout')

@section('title', 'Заявки')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Заявки</h2>
</div>

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.requests.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Поиск</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="{{ request('search') }}" placeholder="Имя, телефон, email...">
            </div>
            
            <div class="col-md-2">
                <label for="site_id" class="form-label">Сайт</label>
                <select class="form-select" id="site_id" name="site_id">
                    <option value="">Все сайты</option>
                    @foreach(\App\Models\Site::all() as $site)
                        <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                            {{ $site->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">Дата с</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="{{ request('date_from') }}">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">Дата по</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="{{ request('date_to') }}">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-2"></i>Фильтровать
                </button>
                <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Сбросить
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Список заявок -->
<div class="card">
    <div class="card-body">
        @if($requests->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Сайт</th>
                            <th>Имя</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Источник</th>
                            <th>IP адрес</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $request)
                            <tr>
                                <td>#{{ $request->id }}</td>
                                <td>
                                    @if($request->site)
                                        <span class="badge bg-secondary">{{ $request->site->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $request->getName() }}</strong>
                                </td>
                                <td>{{ $request->getPhone() }}</td>
                                <td>{{ $request->getFieldValue('email') }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $request->getSource() }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $request->ip_address }}</small>
                                </td>
                                <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.requests.show', $request) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $requests->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-envelope text-muted" style="font-size: 3rem;"></i>
                <h4 class="mt-3 text-muted">Заявки не найдены</h4>
                <p class="text-muted">Попробуйте изменить параметры фильтрации</p>
            </div>
        @endif
    </div>
</div>
@endsection
