<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;
use Illuminate\Support\Facades\URL;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $query = Service::with(['user', 'category', 'images'])->latest();
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        // Price sorting - takes priority over default sorting
        if ($request->has('sort_price')) {
            $sortDirection = strtolower($request->sort_price) === 'desc' ? 'desc' : 'asc';
            $query->orderBy('price', $sortDirection);
        }
        // Default sorting (only applied if no price sort specified)
        else {
            $query->latest();
        }

        if ($request->has('status')) {
            if (Gate::allows('admin-action')) {
                $query->where('status', $request->status);
            }
        } elseif (!Gate::allows('admin-action')) {
            $query->where('status', 'accepted');
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'exchange_time' => 'required',
            'exchange_with_category_id' => 'nullable|exists:categories,id',
            'category_id' => 'required|exists:categories,id',
            'path' => 'nullable|max:10240',
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

            // if ($request->hasFile('path')) {
            $name = $request->path->getClientOriginalName();
            $newName = rand(9999999999, 99999999999) . $name;
            $request->path->move(public_path('images'), $newName);

            // $image = $request->file('path');
            // $path = $image->store('public/images');
            // $url = asset(str_replace('public', 'storage', $path));
            $service->images()->create(['url' => URl::to('images', $newName)]);
            // }

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
        // If service is pending and user is neither admin nor owner
        if (
            $service->status === 'pending' &&
            !Gate::allows('admin-action') &&
            !Gate::allows('view-service', $service)
        ) {
            return response()->json([
                'message' => 'wating the admin to approve'
            ], 403);
        }

        // If service is accepted, or user is admin/owner
        if (
            $service->status === 'accepted' ||
            Gate::allows('admin-action') ||
            Gate::allows('view-service', $service)
        ) {
            return response()->json(
                $service->load(['user', 'category', 'images', 'exchangeWithCategory'])
            );
        }

        // For any other case (like rejected services)
        return response()->json([
            'message' => 'Service not available'
        ], 404);
    }

    public function update(Request $request, Service $service)
    {

        Gate::authorize('update-service', $service);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'exchange_time' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:',
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

    public function pendingServices(Request $request)
    {
        $service = Service::with(['user', 'category', 'images'])
            ->where('status', 'pending');
        // Gate::authorize('admin-action');
        if ($request->has('search')) {
            $service->where('name', 'like', '%' . $request->search . '%');
        }
        return $service->get();
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
