<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // All users can view categories
    }

    public function view(User $user, Category $category): bool
    {
        return true; // All users can view single category
    }

    public function create(User $user): bool
    {
        return $user->isAdmin(); // Only admins can create
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin(); // Only admins can update
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin(); // Only admins can delete
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->isAdmin(); // Only admins can restore
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $user->isAdmin(); // Only admins can force delete
    }
}