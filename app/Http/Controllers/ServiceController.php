<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        return Service::with(['user', 'category', 'images'])->paginate(10);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'exchange_time' => 'nullable',
            'exchange_with_service_id' => 'nullable',
            'phone_number'=>'required|numeric',
            'category_id' => 'required|exists:categories,id',
        ]);
    
        // إضافة user_id يدويًا بعد التحقق
        $validatedData['user_id'] = auth()->id(); // أو 1 إذا كنت تريد تحديد ID ثابت
        
        $service = Service::create($validatedData);
        
        return $service;
    }

    public function show(Service $service)
    {
        return $service->load(['user', 'category', 'images', 'orders', 'exchangeWithService', 'exchangeableServices']);
    }

    public function update(Request $request, Service $service)
    {
        if (!Gate::allows('update-service', $service)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'exchange_time' => 'nullable|date_format:H:i:s',
            'exchange_with_service_id' => 'nullable|exists:services,id',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        $service->update($request->all());

        return $service;
    }

    public function destroy(Service $service)
    {
        if (!Gate::allows('delete-service', $service)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }
}