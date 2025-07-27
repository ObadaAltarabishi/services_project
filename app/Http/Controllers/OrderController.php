<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Wallet;
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
        $user = $request->user();

        $orders = Order::where(function ($query) use ($user) {
            // الطلبات التي أنشأها المستخدم
            $query->where('user_id', $user->id);
        })
            ->orWhereHas('service', function ($q) use ($user) {
                // الطلبات التي تخص خدمة يملكها المستخدم
                $q->where('user_id', $user->id);
            })
            ->with(['user', 'service', 'providedService', 'files'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        foreach ($orders as $order) {

            $userName = User::where("id", $order->service->user_id)->first()->name;
            $order['sallerName'] = $userName;
        }
        return $orders;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'provided_service_id' => 'nullable|exists:services,id',
            'start_date' => 'nullable|date|after_or_equal:today',
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
        $providedService = $request->provided_service_id
            ? Service::findOrFail($request->provided_service_id)
            : null;

        // Validate service-for-service conditions
        if ($providedService) {
            // Ensure the provided service belongs to the buyer
            if ($providedService->user_id !== $userId) {
                return response()->json([
                    'message' => 'You can only offer your own services for exchange'
                ], 403);
            }

            // Ensure not trying to exchange the same service
            if ($providedService->id === $service->id) {
                return response()->json([
                    'message' => 'Cannot exchange the same service'
                ], 400);
            }

            // Get durations from both services
            $serviceDuration = $service->exchange_time ?? '1 day'; // default if not set
            $providedServiceDuration = $providedService->exchange_time ?? '1 day';

            // Parse durations to days
            $serviceDays = $this->durationToDays($serviceDuration);
            $providedServiceDays = $this->durationToDays($providedServiceDuration);

            // Use the longer duration
            $longerDurationDays = max($serviceDays, $providedServiceDays);

            // Calculate dates
            $startDate = now()->startOfDay(); // Today at 00:00:00
            $endDate = $startDate->copy()->addDays($longerDurationDays);
        } else {
            // For cash payments, check wallet balance
            if ($user->wallet->balance < $service->price) {
                return response()->json([
                    'message' => 'Insufficient wallet balance to create this order',
                    'required_amount' => $service->price,
                    'current_balance' => $user->wallet->balance
                ], 400);
            }

            // Use provided dates for regular orders
            $startDate = $request->start_date;
            $endDate = $request->end_date;
        }

        $order = Order::create([
            'user_id' => $userId,
            'service_id' => $request->service_id,
            'provided_service_id' => $request->provided_service_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'pending',
            'is_service_exchange' => (bool)$providedService,
        ]);

        // Send appropriate notification
        if ($providedService) {
            NotificationService::createServiceExchangeNotification(
                $order,
                $providedService,
                $longerDurationDays
            );
        } else {
            NotificationService::createSellerOrderNotification($order, 'created');
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load(['service', 'providedService']),
            'duration_days' => $providedService ? $longerDurationDays : null,
        ], 201);
    }

    // Helper method to convert duration string to days
    protected function durationToDays(string $duration): int
    {
        $units = [
            'hour' => 1 / 24,
            'day' => 1,
            'week' => 7,
            'month' => 30, // approximation
        ];

        foreach ($units as $unit => $days) {
            if (str_contains($duration, $unit)) {
                $value = (int) preg_replace('/[^0-9]/', '', $duration);
                return (int) ceil($value * $days); // Round up to full days
            }
        }

        return 1; // default to 1 day if format not recognized
    }

    // Helper method to convert duration string to minutes
    protected function durationToMinutes(string $duration): int
    {
        $units = [
            'hour' => 60,
            'day' => 1440,
            'week' => 10080,
            'month' => 43200, // approx 30 days
        ];

        foreach ($units as $unit => $minutes) {
            if (str_contains($duration, $unit)) {
                $value = (int) preg_replace('/[^0-9]/', '', $duration);
                return $value * $minutes;
            }
        }

        return 60; // default to 1 hour if format not recognized
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
                        $order->status = 'rejected';
                        $order->save();
                        return response()->json(["message" => "You can't reject this order"], 400);
                    }
                    NotificationService::createOrderStatusNotification($order, 'rejected');
                    break;

                case 'accepted':

                    if ($previousStatus !== 'accepted') {
                        $order->status = 'accepted';

                        NotificationService::createOrderStatusNotification($order, 'accepted');
                        if ($order->provided_service_id !== null) {
                            //return $order->service->user_id;
                            //return $order->provided_service_id,
                            Order::create([
                                'user_id' => $order->service->user_id,
                                'service_id' => $order->provided_service_id,
                                'provided_service_id' => $order->service_id,
                                'status' => 'accepted',

                            ]);

                            $orderClone = $order;
                            //$order->provided_service_id=null;
                            $order->save();
                        }
                    }
                    break;

                case 'completed':
                    if ($previousStatus !== 'completed') {
                        $order->status = 'completed';
                        if ($order->provided_service_id == null) {
                            $order->user->wallet->decrement('balance', $order->service->price);
                            $sellerId = $order->service->user_id;
                            $sellerWallet = Wallet::where('user_id', $sellerId)->first();
                            $sellerWallet->increment('balance', $order->service->price);
                            $order->save();
                        }
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
        //return $order;
        return response()->json(
            $order->load(['user', 'service'])
        );
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
            ->where('status', 'canceled')
            ->latest()
            ->paginate(10);
    }

    public function wrongOrders(Request $request, Order $order)
    {

        $order->status = 'canceled';
        $order->save();


        return response()->json(
            $order->load(['user', 'service'])
        );
    }
}
