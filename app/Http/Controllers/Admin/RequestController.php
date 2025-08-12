<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormRequest;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FormRequest::with('site');
        
        // Фильтрация по сайту
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        
        // Фильтрация по дате
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Поиск
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(form_data, '$.name') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_EXTRACT(form_data, '$.phone') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_EXTRACT(form_data, '$.email') LIKE ?", ["%{$search}%"]);
            });
        }
        
        $requests = $query->latest()->paginate(20);
        
        return view('admin.requests.index', compact('requests'));
    }

    /**
     * Display the specified resource.
     */
    public function show(FormRequest $request)
    {
        $request->load('site');
        return view('admin.requests.show', compact('request'));
    }
}
