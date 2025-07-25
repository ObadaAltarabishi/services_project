<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        
//$schedule->command('orders:cancel-expired')->everyMinute();
    
    // For debugging only (remove in production)
    //$nextRun = $schedule->events()[0]->nextRunDate()->format('Y-m-d H:i:s');
        return Category::paginate(10);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            
        ]);

        return Category::create($validated);
    }

    public function show(Category $category)
    {
        return $category->load('services');
    }

    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            
        ]);

        $category->update($validated);

        return $category;
    }

    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
