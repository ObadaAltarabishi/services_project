<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        // يمكن لأي مستخدم مصادق عليه رؤية قائمة الطلبات
        return true;
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // صاحب الطلب أو المدير يمكنهم رؤية الطلب
        return $user->id === $order->user_id   || $user->isAdmin();
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        // يمكن لأي مستخدم مصادق عليه إنشاء طلبات
        return true;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // فقط صاحب الطلب أو المدير يمكنهم التعديل
        // وحتى حالة الطلب تسمح بالتعديل (مثلاً لم يتم الموافقة عليه بعد)
        return ($user->id == $order->service->user_id)
        || ($user->id == $order->user_id && ($order->status == "pending" || $order->status == "active"));
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // فقط صاحب الطلب أو المدير يمكنهم الحذف
        // ويمكن الحذف فقط إذا كان الطلب في حالة معينة
        return ($user->id === $order->user_id || $user->isAdmin())
            && in_array($order->status, ['pending', 'canceled']);
    }

    /**
     * Determine whether the user can restore the order.
     */
    public function restore(User $user, Order $order): bool
    {
        // فقط المدير يمكنه استعادة الطلبات المحذوفة
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the order.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        // فقط المدير يمكنه الحذف الدائم
        return $user->isAdmin();
    }
}