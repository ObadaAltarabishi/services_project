<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // يمكن لأي مستخدم مصادق عليه رؤية قائمة الخدمات
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Service $service): bool
    {
        // يمكن لأي مستخدم رؤية الخدمة (حتى غير المسجلين)
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // يمكن لأي مستخدم مصادق عليه إنشاء خدمات
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Service $service): bool
    {
        // فقط مقدم الخدمة (صاحبها) يمكنه التعديل
        return $user->id === $service->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Service $service): bool
    {
        // فقط مقدم الخدمة (صاحبها) يمكنه الحذف
        return $user->id === $service->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Service $service): bool
    {
        // نفس صلاحيات الحذف
        return $this->delete($user, $service);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Service $service): bool
    {
        // نفس صلاحيات الحذف العادي
        return $this->delete($user, $service);
    }
}