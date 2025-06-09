<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Typically only admins can list all users
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     * Users can view their own profile or admins can view any
     */
    public function view(User $user, User $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Usually only admins can create users directly (registration is public)
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * Users can update their own profile or admins can update any
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * Only admins can delete users, and cannot delete themselves
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins can restore soft-deleted users
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete, and cannot delete themselves
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can change roles/permissions.
     * Only admins can modify roles and permissions
     */
    public function manageRoles(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can impersonate another user.
     * Only specific admin roles can impersonate
     */
    public function impersonate(User $user, User $model): bool
    {
        return $user->isAdmin() && 
               $user->id !== $model->id &&
               !$model->isAdmin();
    }
}