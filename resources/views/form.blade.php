<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $site->name }} - Форма заявки</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, {{ $site->getDesignSettings()['primary_color'] ?? '#667eea' }} 0%, {{ $site->getDesignSettings()['secondary_color'] ?? '#764ba2' }} 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-title {
            color: #333;
            font-weight: 600;
        }
        
        .form-control:focus {
            border-color: {{ $site->getDesignSettings()['primary_color'] ?? '#667eea' }};
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, {{ $site->getDesignSettings()['primary_color'] ?? '#667eea' }} 0%, {{ $site->getDesignSettings()['secondary_color'] ?? '#764ba2' }} 100%);
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .form-check-input:checked {
            background-color: {{ $site->getDesignSettings()['primary_color'] ?? '#667eea' }};
            border-color: {{ $site->getDesignSettings()['primary_color'] ?? '#667eea' }};
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="form-container p-4">
                    <div class="text-center mb-4">
                        <h2 class="form-title">{{ $site->name }}</h2>
                        <p class="text-muted">Оставьте заявку и мы свяжемся с вами</p>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('form.submit') }}" id="leadForm">
                        @csrf
                        
                        @foreach($fields as $field)
                            <div class="mb-3">
                                <label for="{{ $field->name }}" class="form-label">
                                    {{ $field->label }}
                                    @if($field->required)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                
                                @switch($field->type)
                                    @case('text')
                                    @case('email')
                                    @case('phone')
                                    @case('number')
                                    @case('url')
                                    @case('date')
                                        <input type="{{ $field->type === 'phone' ? 'tel' : $field->type }}" 
                                               class="form-control @error($field->name) is-invalid @enderror" 
                                               id="{{ $field->name }}" 
                                               name="{{ $field->name }}" 
                                               value="{{ old($field->name) }}"
                                               placeholder="{{ $field->placeholder }}"
                                               @if($field->required) required @endif
                                               @foreach($field->getHtmlAttributes() as $attr => $value)
                                                   @if($attr !== 'id' && $attr !== 'name' && $attr !== 'class' && $attr !== 'type')
                                                       {{ $attr }}="{{ $value }}"
                                                   @endif
                                               @endforeach>
                                        @break
                                        
                                    @case('textarea')
                                        <textarea class="form-control @error($field->name) is-invalid @enderror" 
                                                  id="{{ $field->name }}" 
                                                  name="{{ $field->name }}" 
                                                  rows="4"
                                                  placeholder="{{ $field->placeholder }}"
                                                  @if($field->required) required @endif>{{ old($field->name) }}</textarea>
                                        @break
                                        
                                    @case('select')
                                        <select class="form-select @error($field->name) is-invalid @enderror" 
                                                id="{{ $field->name }}" 
                                                name="{{ $field->name }}"
                                                @if($field->required) required @endif>
                                            <option value="">Выберите...</option>
                                            @foreach($field->getOptions() as $option)
                                                <option value="{{ $option['value'] }}" 
                                                        {{ old($field->name) == $option['value'] ? 'selected' : '' }}>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @break
                                        
                                    @case('radio')
                                        <div class="form-check-group">
                                            @foreach($field->getOptions() as $option)
                                                <div class="form-check">
                                                    <input class="form-check-input @error($field->name) is-invalid @enderror" 
                                                           type="radio" 
                                                           id="{{ $field->name }}_{{ $option['value'] }}" 
                                                           name="{{ $field->name }}" 
                                                           value="{{ $option['value'] }}"
                                                           {{ old($field->name) == $option['value'] ? 'checked' : '' }}
                                                           @if($field->required) required @endif>
                                                    <label class="form-check-label" for="{{ $field->name }}_{{ $option['value'] }}">
                                                        {{ $option['label'] }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @break
                                        
                                    @case('checkbox')
                                        <div class="form-check-group">
                                            @foreach($field->getOptions() as $option)
                                                <div class="form-check">
                                                    <input class="form-check-input @error($field->name) is-invalid @enderror" 
                                                           type="checkbox" 
                                                           id="{{ $field->name }}_{{ $option['value'] }}" 
                                                           name="{{ $field->name }}[]" 
                                                           value="{{ $option['value'] }}"
                                                           {{ in_array($option['value'], old($field->name, [])) ? 'checked' : '' }}
                                                           @if($field->required) required @endif>
                                                    <label class="form-check-label" for="{{ $field->name }}_{{ $option['value'] }}">
                                                        {{ $option['label'] }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @break
                                        
                                    @default
                                        <input type="text" 
                                               class="form-control @error($field->name) is-invalid @enderror" 
                                               id="{{ $field->name }}" 
                                               name="{{ $field->name }}" 
                                               value="{{ old($field->name) }}"
                                               placeholder="{{ $field->placeholder }}"
                                               @if($field->required) required @endif>
                                @endswitch
                                
                                @error($field->name)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send me-2"></i>Отправить заявку
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- jQuery Mask Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Маска для телефона
            $('input[type="tel"]').mask('+7 (000) 000-00-00', {
                placeholder: '+7 (___) ___-__-__'
            });
            
            // Анимация появления формы
            $('.form-container').hide().fadeIn(800);
        });
    </script>
</body>
</html>
