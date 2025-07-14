<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
         $query = Service::with(['user', 'category', 'images'])->latest();
            
        //if ($request->has('status')) {
            //if (Gate::allows('admin-action')) {
             //   $query->where('status', $request->status);
          //  }
        //} elseif (!Gate::allows('admin-action')) {
        //    $query->where('status', 'accepted');
        //}
            
        return $query->paginate(10);
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
            'path' => 'required|file|image|max:10240',
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
            $validatedData['status'] = 'pending';

            $service = Service::create($validatedData);

            if ($request->hasFile('path')) {
                $image = $request->file('path');
                $path = $image->store('public/images');
                $url = asset(str_replace('public', 'storage', $path));
                $service->images()->create(['url' => $url]);
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
        if ($service->status === 'pending' && 
            !Gate::allows('admin-action') && 
            !Gate::allows('view-service', $service)) {
            abort(403, 'This service is pending approval');
        }

        return response()->json(
            $service->load(['user', 'category', 'images', 'exchangeWithCategory'])
        );
    }

    public function update(Request $request, Service $service)
    {
        Gate::authorize('update-service', $service);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'exchange_time' => 'nullable|date_format:H:i:s',
            'exchange_with_service_id' => 'nullable|exists:services,id',
            'category_id' => 'sometimes|exists:categories,id',
            'status' => 'sometimes|in:pending,accepted,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('status') && !Gate::allows('admin-action')) {
            abort(403, 'Only admins can change service status');
        }

        $service->update($validator->validated());

        return response()->json(
            $service->fresh()->load(['user', 'category', 'images'])
        );
    }

    public function destroy(Service $service)
    {
        Gate::authorize('delete-service', $service);

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

    public function pendingServices()
    {
        Gate::authorize('admin-action');
        return Service::with(['user', 'category', 'images'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(10);
    }

    public function approveService(Service $service)
    {
        Gate::authorize('admin-action');
        
        $service->update(['status' => 'accepted']);
        
        // Send notification to service owner
        NotificationService::createServiceStatusNotification($service->user, $service, 'accepted');
        
        return response()->json([
            'message' => 'Service approved successfully',
            'service' => $service->fresh()
        ]);
    }

    public function rejectService(Service $service)
    {
        Gate::authorize('admin-action');
        
        $service->update(['status' => 'rejected']);
        
        // Send notification to service owner
        NotificationService::createServiceStatusNotification($service->user, $service, 'rejected');
        
        return response()->json([
            'message' => 'Service rejected successfully',
            'service' => $service->fresh()
        ]);
    }
}