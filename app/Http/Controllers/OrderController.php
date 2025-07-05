<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Service;
use App\Models\Notification;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        return Order::where('user_id',$request->user()->id)->with(['user', 'service', 'providedService'])->paginate(10);
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

    // Get the authenticated user (or default to user 1 if not authenticated)
    $userId = auth()->id();
    $user = User::with('wallet')->findOrFail($userId);
    
    // Get the service and its price
    $service = Service::with('user')->findOrFail($request->service_id);
    
    // Check if user has enough balance
    if ($user->wallet->balance < $service->price) {
        return response()->json([
            'message' => 'Insufficient wallet balance to create this order',
            'required_amount' => $service->price,
            'current_balance' => $user->wallet->balance
        ], 400);
    }

    // Create the order
    $orderData = array_merge(
        $validator->validated(),
        [
            'user_id' => $userId,
            'status' => 'pending'
        ]
    );

    $order = Order::create($orderData);

    // Send notification to the seller (service owner)
    Notification::create([
        'user_id' => $service->user->id,
        'order_id' => $order->id,
        'title' => 'New Order Received',
        'content' => 'You have a new order for your service: ' . $service->title,
        'type' => 'order_created',
        'date'=>'2025-06-13 19:05:05'
    ]);

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
    
    // Get the previous status before updating
    $previousStatus = $order->status;
    

    if($data['status'] === 'rejected' && $previousStatus !== "pending"){
        return response()->json([
            "message"=>"you can't reject this order"
        ],400);
    }

        if (isset($data['status']) && $data['status'] === 'rejected' && $previousStatus === "pending") {
        // Send notification to the buyer
        Notification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'title' => 'Order Rejected',
            'content' => 'Your order for ' . $order->service->title . ' has been rejected',
            'type' => 'order_rejected',
            'date'=>'2025-06-13 19:05:05'

        ]);
    }

    if (isset($data['status']) && $data['status'] === 'accepted' && $previousStatus !== 'accepted') {
        // Send notification to the buyer
        Notification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'title' => 'Order Accepted',
            'content' => 'Your order for ' . $order->service->title . ' has been accepted',
            'type' => 'order_accepted',
            'date'=>'2025-06-13 19:05:05'

        ]);
    }

    if (isset($data['status']) && $data['status'] === 'completed' && $previousStatus !== 'completed') {
        // Get the user's wallet
        $wallet = $order->user->wallet;
        
        // Get the service price
        $servicePrice = $order->service->price;
        
        // Deduct the amount from wallet
        $wallet->balance -= $servicePrice;
        $wallet->save();
        
        // Send notification to the buyer
        Notification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'title' => 'Order Completed',
            'content' => 'Your order for ' . $order->service->title . ' has been completed',
            'type' => 'order_completed',
            'date'=>'2025-06-13 19:05:05'
        ]);
        
        // Optionally send notification to the seller as well
        // $seller = $order->service->user;
        // Notification::create([
        //     'user_id' => $seller->id,
        //     'order_id' => $order->id,
        //     'title' => 'Order Completed',
        //     'message' => 'Your service ' . $order->service->title . ' has been marked as completed',
        //     'type' => 'order_completed'
        // ]);
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