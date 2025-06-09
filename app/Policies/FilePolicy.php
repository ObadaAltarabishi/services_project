<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FilePolicy
{
    /**
     * Determine whether the user can view any files.
     */
    public function viewAny(User $user): bool
    {
        // يمكن لأي مستخدم مصادق عليه رؤية قائمة الملفات (سيتم تصفية النتائج حسب سياسة view)
        return true;
    }

    /**
     * Determine whether the user can view the file.
     */
    public function view(User $user, File $file): bool
    {
          // تأكد من تحميل العلاقة إذا لزم الأمر
    if (!$file->relationLoaded('order')) {
        $file->load('order');
    }
    return true;
    return $user->id === $file->uploader_id 
        || $user->id === $file->order->user_id;
    }

    /**
     * Determine whether the user can create files.
     */
    public function create(User $user): bool
    {
        // يمكن لأي مستخدم مصادق عليه رفع ملفات
        return true;
    }

    /**
     * Determine whether the user can update the file.
     */
    public function update(User $user, File $file): bool
    {
        // فقط رافع الملف يمكنه تعديله
        return $user->id === $file->uploader_id;
    }

    /**
     * Determine whether the user can delete the file.
     */
    public function delete(User $user, File $file): bool
    {
        // فقط رافع الملف أو صاحب الطلب يمكنه حذفه
        return $user->id === $file->uploader_id 
            || $user->id === $file->order->user_id;
    }

    /**
     * Determine whether the user can restore the file.
     */
    public function restore(User $user, File $file): bool
    {
        // نفس صلاحيات الحذف
        return $this->delete($user, $file);
    }

    /**
     * Determine whether the user can permanently delete the file.
     */
    public function forceDelete(User $user, File $file): bool
    {
        // فقط المدير يمكنه الحذف الدائم
        return $user->isAdmin();
    }
}