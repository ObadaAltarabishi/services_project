<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Service $service)
    {
        return $service->images()->paginate(10);
    }

    public function store(Request $request, Service $service)
    {
        if (!Gate::allows('update-service', $service)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $path = $request->file('image')->store('service_images', 'public');

        $image = $service->images()->create([
            'url' => Storage::url($path),
        ]);

        return $image;
    }

    public function show(Image $image)
    {
        return $image;
    }

    public function destroy(Image $image)
    {
        if (!Gate::allows('delete-image', $image)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $image->url));
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}