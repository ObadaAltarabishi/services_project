<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        return Service::with(['user', 'category', 'images'])
            ->latest()
            ->paginate(10);
    }

   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'price' => 'required|numeric|min:0',
        'exchange_time' => 'nullable',
        'exchange_with_service_id' => 'nullable|exists:services,id',
        'category_id' => 'required|exists:categories,id',
        'path' => 'required|file|image|max:10240', // Added 'image' validation
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $validatedData = $validator->validated();
        $validatedData['user_id'] = auth()->id();

        // Create the service first
        $service = Service::create($validatedData);

        // Handle the image upload
        if ($request->hasFile('path')) {
            $image = $request->file('path');
            
            // Store the image in storage/app/public/images
            $path = $image->store('public/images');
            
            // Create a public URL for the image
            $url = asset(str_replace('public', 'storage', $path));

            // Create image record in database
            $service->images()->create([
                'url' => $url,
            ]);
        }

        return response()->json(
            $service->load(['user', 'category', 'images']),
            201
        );

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Service creation failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function show(Service $service)
    {
        return response()->json(
            $service->load(['user', 'category', 'images', 'exchangeWithCategory'])
        );
    }

    public function update(Request $request, Service $service)
    {
        Gate::authorize('update', $service);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'exchange_time' => 'nullable|date_format:H:i:s',
            'exchange_with_service_id' => 'nullable|exists:services,id',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $service->update($validator->validated());

        return response()->json(
            $service->fresh()->load(['user', 'category', 'images'])
        );
    }

    public function destroy(Service $service)
    {
        Gate::authorize('delete', $service);

        try {
            $service->delete();
            return response()->json([
                'message' => 'Service deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete service',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}