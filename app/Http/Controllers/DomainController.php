<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Site;

class DomainController extends Controller
{
    /**
     * Обработать запрос по домену
     */
    public function handle(Request $request)
    {
        $host = $request->getHost();
        
        // Если это APP_URL - показываем админку
        if ($host === parse_url(config('app.url'), PHP_URL_HOST)) {
            if (auth()->check()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('login');
        }
        
        // Ищем сайт по домену
        $site = Site::where('domain', $host)->where('is_active', true)->first();
        
        if ($site) {
            // Если найден активный сайт, показываем форму
            $fields = $site->activeFields()->get();
            return view('form', compact('site', 'fields'));
        }
        
        // Если сайт не найден, показываем 404
        abort(404, 'Сайт не найден');
    }
}
