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
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the wallet.
     */
    public function viwe(User $user, Wallet $wallet): bool
    {
        // Only wallet owner can view
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can create wallets.
     */
    public function create(User $user): bool
    {
        // Wallets should only be created during registration
        return false;
    }

    /**
     * Determine whether the user can update the wallet.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        // Only wallet owner can update
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can delete the wallet.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        // Only admins can delete wallets
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can add funds to the wallet.
     */
    // In App\Policies\WalletPolicy.php
   public function addFunds(User $user, Wallet $wallet)
    {
      return $user->id === $wallet->user_id;
    }
}
