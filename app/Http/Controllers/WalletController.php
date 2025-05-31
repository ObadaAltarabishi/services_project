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
        if (!Gate::allows('view-wallet', $wallet)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $wallet;
    }

    public function update(Request $request, Wallet $wallet)
    {
        if (!Gate::allows('update-wallet', $wallet)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'balance' => 'required|numeric|min:0',
        ]);

        $wallet->update($request->only('balance'));

        return $wallet;
    }

    public function addFunds(Request $request, Wallet $wallet)
    {
        if (!Gate::allows('update-wallet', $wallet)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $wallet->increment('balance', $request->amount);

        return $wallet;
    }
}