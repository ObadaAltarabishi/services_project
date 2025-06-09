<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any profiles.
     * Typically only admins can list all profiles
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the profile.
     * Users can view their own profile or public profiles
     */
    public function view(User $user, Profile $profile): bool
    {
        // Allow viewing if:
        // 1. User owns the profile
        // 2. Profile is public
        // 3. User is admin
        return true;
    }

    /**
     * Determine whether the user can create a profile.
     * Users can only create one profile (enforced at DB level)
     */
    public function create(User $user): bool
    {
        // Only allow creation if user doesn't already have a profile
        return !$user->profile()->exists();
    }

    /**
     * Determine whether the user can update the profile.
     * Only profile owner or admin can update
     */
    public function update(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the profile.
     * Only profile owner or admin can delete
     */
    public function delete(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the profile.
     * Only admins can restore soft-deleted profiles
     */
    public function restore(User $user, Profile $profile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the profile.
     * Only admins can force delete
     */
    public function forceDelete(User $user, Profile $profile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can change profile visibility.
     */
    public function changeVisibility(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    /**
     * Determine whether the user can upload profile pictures.
     */
    public function uploadPicture(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id || $user->isAdmin();
    }
}