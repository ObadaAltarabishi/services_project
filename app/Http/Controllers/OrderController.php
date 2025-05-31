<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        return Order::with(['user', 'service', 'providedService'])->paginate(10);
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
    
        // إنشاء بيانات الطلب مع الحقول الإضافية
        $orderData = array_merge(
            $validator->validated(), // البيانات التي تم التحقق منها
            [
                'user_id' => auth()->id() ?? 1, // إما المستخدم الحالي أو 1 كقيمة افتراضية
                'status' => 'actv' // حالة افتراضية
            ]
        );
    
        $order = Order::create($orderData);
    
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

        $request->validate([
            'status' => 'sometimes|in:pending,accepted,rejected,completed',
            'provided_service_id' => 'nullable|exists:services,id',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $order->update($request->all());

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
}