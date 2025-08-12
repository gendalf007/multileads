<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\FormFieldController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\CrmMappingController;



// Аутентификация (только для админки)
Route::get('/login', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    $credentials = request()->only('email', 'password');
    
    if (auth()->attempt($credentials)) {
        return redirect()->intended('/admin');
    }
    
    return back()->withErrors([
        'email' => 'Неверные учетные данные.',
    ]);
})->name('login.post');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');

// Админка (требует аутентификации и прав администратора)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Управление сайтами
    Route::resource('sites', SiteController::class);
    Route::post('sites/{site}/regenerate-api-key', [SiteController::class, 'regenerateApiKey'])
        ->name('sites.regenerate-api-key');
    
    // Управление полями форм
    Route::resource('sites.fields', FormFieldController::class);
    Route::post('sites/{site}/fields/update-order', [FormFieldController::class, 'updateOrder'])
        ->name('sites.fields.update-order');
    Route::post('sites/{site}/fields/{field}/toggle-active', [FormFieldController::class, 'toggleActive'])
        ->name('sites.fields.toggle-active');
    
    // Просмотр заявок
    Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('requests/{request}', [RequestController::class, 'show'])->name('requests.show');
    
    // CRM маппинг
    Route::get('sites/{site}/crm-mapping', [CrmMappingController::class, 'index'])->name('sites.crm-mapping.index');
    Route::post('sites/{site}/crm-mapping', [CrmMappingController::class, 'store'])->name('sites.crm-mapping.store');
});

// API для получения конфигурации сайта
Route::get('/api/site/{domain}', [FormController::class, 'getSiteConfig'])->name('api.site.config');

// Обработка отправки форм
Route::post('/submit', [FormController::class, 'submitForm'])->name('form.submit');

// Обработка всех запросов через контроллер доменов
Route::get('/{any?}', [DomainController::class, 'handle'])
    ->where('any', '.*');