<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Service;
use App\Services\NotificationService;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        return Order::where('user_id', $request->user()->id)
            ->with(['user', 'service', 'providedService'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'provided_service_id' => 'nullable|exists:services,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {  
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();
        $user = User::with('wallet')->findOrFail($userId);
        $service = Service::with('user')->findOrFail($request->service_id);
        
        if ($user->wallet->balance < $service->price) {
            return response()->json([
                'message' => 'Insufficient wallet balance to create this order',
                'required_amount' => $service->price,
                'current_balance' => $user->wallet->balance
            ], 400);
        }

        $order = Order::create(array_merge(
            $validator->validated(),
            ['user_id' => $userId, 'status' => 'pending']
        ));

        // Send notification to seller
        NotificationService::createSellerOrderNotification($order, 'created');

        return response()->json($order, 201);
    }

    public function show(Order $order)
    {
        if (!Gate::allows('view-order', $order)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $order->load(['user', 'service', 'providedService', 'files', 'notifications']);
    }

    public function update(Request $request, Order $order)
    {
        if (!Gate::allows('update-order', $order)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status' => 'sometimes|in:pending,active,accepted,rejected,canceled,completed',
            'provided_service_id' => 'nullable|exists:services,id',
            'end_date' => 'nullable|date|after:start_date',
        ]);
        
        $previousStatus = $order->status;

        if (isset($data['status'])) {
            switch ($data['status']) {
                case 'rejected':
                    if ($previousStatus !== "pending") {
                        return response()->json(["message" => "You can't reject this order"], 400);
                    }
                    NotificationService::createOrderStatusNotification($order, 'rejected');
                    break;
                    
                case 'accepted':
                    if ($previousStatus !== 'accepted') {
                        NotificationService::createOrderStatusNotification($order, 'accepted');
                    }
                    break;
                    
                case 'completed':
                    if ($previousStatus !== 'completed') {
                        $order->user->wallet->decrement('balance', $order->service->price);
                        NotificationService::createOrderStatusNotification($order, 'completed');
                        NotificationService::createSellerOrderNotification($order, 'completed');
                    }
                    break;
                    
                case 'canceled':
                    NotificationService::createOrderStatusNotification($order, 'canceled');
                    break;
            }
        }
        
        $order->update($data);
        return $order;
    }

    public function destroy(Order $order)
    {
        if (!Gate::allows('delete-order', $order)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function rejectedOrders(Request $request)
    {
        Gate::authorize('admin-action');
        return Order::with(['user', 'service', 'providedService'])
            ->where('status', 'rejected')
            ->latest()
            ->paginate(10);
    }
}