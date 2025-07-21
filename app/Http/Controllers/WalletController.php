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

    /* public function show(Wallet $wallet)
{
    // Get the authenticated user
    $user = auth()->user();

    // Check if the wallet belongs to the authenticated user

    if ($user->id !== $wallet->user_id) {
        return response()->json([
            'message' => 'Unauthorized - You can only view your own wallet',
            'success' => false
        ], 403);
    }

    // If authorized, return the wallet data
    return response()->json([
        'success' => true,
        'wallet' => [
            'id' => $wallet->id,
            'balance' => $wallet->balance,
            'user_id' => $wallet->user_id,
            'created_at' => $wallet->created_at,
            'updated_at' => $wallet->updated_at
        ]
    ]);
}
*/
    public function showWallet()
    {
        $user = \Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (auth()->id() != $wallet->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
                'success' => false,
                'debug' => [
                    'auth_id' => auth()->id(),
                    'wallet_user_id' => $wallet->user_id
                ]
            ], 403);
        }

        return response()->json([
            'success' => true,
            'balance' => $wallet->balance,
            'user_id' => $wallet->user_id
        ]);
    }

    /* public function update(Request $request, Wallet $wallet)
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
*/
    public function addFunds(Request $request)
    {
        // Manual authorization check (alternative to policy)
        // $wallet = Wallet::with('user')->findOrFail($id);
        $user = \Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (auth()->id() !== $wallet->user_id) {
            return response()->json([
                'message' => 'Unauthorized - You can only add funds to your own wallet',
                'success' => false
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:10000' // Better field name
        ]);

        $wallet->increment('balance', $validated['amount']);

        return response()->json([
            'success' => true,
            'message' => 'Funds added successfully',
            'new_balance' => $wallet->fresh()->balance,
            'added_amount' => $validated['amount']
        ], 201);
    }
}
