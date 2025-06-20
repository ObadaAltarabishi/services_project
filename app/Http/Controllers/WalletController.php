<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show(Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        return $wallet;
    }

    public function update(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);

        $validated = $request->validate([
            'balance' => 'required|numeric|min:0',
        ]);

        $wallet->increment('balance',$validated['balance']);

        return response()->json([
            'message' => 'Wallet updated successfully',
            'wallet' => $wallet
        ]);
    }

    public function addFunds(Request $request, Wallet $wallet)
    {
        $this->authorize('addFunds', $wallet);

        $validated = $request->validate([
            'balance' => 'required|numeric|min:0.01', // Minimum amount to add
        ]);

        $wallet->increment('balance', $wallet['balance'] + $validated['balance']);

        return response()->json([
            'message' => 'Funds added successfully',
            'wallet' => $wallet->fresh()
        ]);
    }
}