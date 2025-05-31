<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        return $request->user()->notifications()->paginate(10);
    }

    public function show(Notification $notification)
    {
        if (!Gate::allows('view-notification', $notification)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $notification;
    }

    public function markAsSeen(Notification $notification)
    {
        if (!Gate::allows('update-notification', $notification)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['is_seen' => true]);

        return $notification;
    }

    public function destroy(Notification $notification)
    {
        if (!Gate::allows('delete-notification', $notification)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }
}