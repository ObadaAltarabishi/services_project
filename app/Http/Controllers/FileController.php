<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Order $order)
    {
        Gate::authorize('view-order', $order);
        return $order->files()->paginate(10);
    }

    public function store(Request $request, Order $order)
    {
        Gate::authorize('update-order', $order);

        $validator = Validator::make($request->all(), [
            'path' => 'required|max:10240',
            'receiver_id' => 'required|exists:users,id'
            // تم إزالة التحقق من order_id لأنه يأتي من параметр المسار
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        // $uploadedFile = $request->file('path');
        // $path = $uploadedFile->store('orders/files', 'public');

        $name = $request->path->getClientOriginalName();
        $newName = rand(9999999999, 99999999999) . $name;
        $request->path->move(public_path('orders/files'), $newName);
        $request->merge([
            'path2' => URL::to('orders/files/' . $newName)
        ]);
        $file = $order->files()->create([
            'uploader_id' => auth()->id(),
            'receiver_id' => $validatedData['receiver_id'],
            'path' => $request->path2,
            'order_id' => $order->id, // استخدام order_id من параметр المسار بدلاً من القيمة الثابتة
            'original_name' => $name,
            'mime_type' => null,
            'size' => null
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $file,
            'download_url' => $file->download_url
        ], 201);
    }

    public function show(File $file)
    {
        Gate::authorize('view-file', $file);
        return response()->json([
            'file' => $file,
            'download_url' => $file->download_url
        ]);
    }

    public function destroy(File $file)
    {
        Gate::authorize('delete-file', $file);

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json([
            'message' => 'File deleted successfully'
        ]);
    }

    public function download(File $file)
    {
        Gate::authorize('view-file', $file);

        if (!Storage::disk('public')->exists($file->path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $file->path,
            $file->original_name
        );
    }
}
