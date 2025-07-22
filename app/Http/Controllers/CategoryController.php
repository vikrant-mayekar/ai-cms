<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::withCount('articles');

        // Search by name
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        // Validate sort fields
        $allowedSortFields = ['name', 'articles_count', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'name';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        $categories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories,
            'user_role' => Auth::user()->role,
            'can_manage_categories' => Auth::user()->isAdmin(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only admins can create categories
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only administrators can create categories.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [],
            'user_role' => Auth::user()->role,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only admins can create categories
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only administrators can create categories.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
            'user_role' => Auth::user()->role,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::with(['articles.author'])->findOrFail($id);

        // Add role-based information
        $response = [
            'success' => true,
            'data' => $category,
            'user_role' => Auth::user()->role,
        ];

        // Add additional permissions for admins
        if (Auth::user()->isAdmin()) {
            $response['can_edit'] = true;
            $response['can_delete'] = $category->articles()->count() === 0;
        } else {
            $response['can_edit'] = false;
            $response['can_delete'] = false;
        }

        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Only admins can edit categories
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only administrators can edit categories.'
            ], 403);
        }

        $category = Category::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
            'user_role' => Auth::user()->role,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Only admins can update categories
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only administrators can update categories.'
            ], 403);
        }

        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
            'user_role' => Auth::user()->role,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Only admins can delete categories
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only administrators can delete categories.'
            ], 403);
        }

        $category = Category::findOrFail($id);

        // Check if category has articles
        if ($category->articles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing articles. Please reassign or delete the articles first.',
                'articles_count' => $category->articles()->count()
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
            'user_role' => Auth::user()->role,
        ]);
    }

    /**
     * Get categories with article counts for dropdown/select.
     */
    public function forSelect()
    {
        $categories = Category::select('id', 'name')
            ->withCount('articles')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'user_role' => Auth::user()->role,
        ]);
    }

    /**
     * Get category statistics.
     */
    public function statistics()
    {
        // Only admins can see category statistics
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only administrators can view category statistics.'
            ], 403);
        }

        $statistics = [
            'total_categories' => Category::count(),
            'categories_with_articles' => Category::has('articles')->count(),
            'empty_categories' => Category::doesntHave('articles')->count(),
            'most_used_category' => Category::withCount('articles')
                ->orderBy('articles_count', 'desc')
                ->first(),
            'recent_categories' => Category::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'user_role' => Auth::user()->role,
        ]);
    }
}
