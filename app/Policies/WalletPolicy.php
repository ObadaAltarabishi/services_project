<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Access\Response;

class WalletPolicy
{
    /**
     * Determine whether the user can view any wallets.
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view all wallets
        return false; // Make sure you have isAdmin() method in User model
    }

    /**
     * Determine whether the user can view the wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        // Only wallet owner can view
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can create wallets.
     */
    public function create(User $user): bool
    {
        // Users can only create wallets through registration
        return true;
    }

    /**
     * Determine whether the user can update the wallet.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        // Only wallet owner can update
        // return $user->id === $wallet->user_id;
        return true;
    }

    /**
     * Determine whether the user can delete the wallet.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        // Wallets shouldn't be deletable (or only by admins)
        return $user->isAdmin();
    }

    /**
     * Additional method for adding funds
     */
    public function addFunds(User $user, Wallet $wallet): bool
    {
        // Only wallet owner can add funds
        return $user->id === $wallet->user_id;
    }
}