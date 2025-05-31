<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Order $order)
    {
        if (!Gate::allows('view-order', $order)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $order->files()->paginate(10);
    }

    public function store(Request $request, Order $order)
    {
        if (!Gate::allows('update-order', $order)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240',
            'receiver_id' => 'required|exists:users,id',
        ]);

        $file = $request->file('file');
        $path = $file->store('order_files', 'public');

        $uploadedFile = $order->files()->create([
            'uploader_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'path' => Storage::url($path),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return $uploadedFile;
    }

    public function show(File $file)
    {
        if (!Gate::allows('view-file', $file)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $file;
    }

    public function destroy(File $file)
    {
        if (!Gate::allows('delete-file', $file)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $file->path));
        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }
}