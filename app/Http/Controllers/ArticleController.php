<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Article::with(['author', 'categories']);

        // Role-based filtering: Authors can only see their own articles
        if (!Auth::user()->isAdmin()) {
            $query->where('author_id', Auth::id());
        }

        // Filter by status
        if ($request->has('status') && !empty($request->get('status'))) {
            $status = $request->get('status');
            if (in_array($status, ['draft', 'published', 'archived'])) {
                $query->where('status', $status);
            }
        }

        // Filter by category
        if ($request->has('category_id') && !empty($request->get('category_id'))) {
            $categoryId = $request->get('category_id');
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        // Filter by multiple categories
        if ($request->has('category_ids') && is_array($request->get('category_ids'))) {
            $categoryIds = $request->get('category_ids');
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Filter by date range
        if ($request->has('date_from') && !empty($request->get('date_from'))) {
            $dateFrom = Carbon::parse($request->get('date_from'))->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->has('date_to') && !empty($request->get('date_to'))) {
            $dateTo = Carbon::parse($request->get('date_to'))->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        // Filter by published date range
        if ($request->has('published_from') && !empty($request->get('published_from'))) {
            $publishedFrom = Carbon::parse($request->get('published_from'))->startOfDay();
            $query->where('published_at', '>=', $publishedFrom);
        }

        if ($request->has('published_to') && !empty($request->get('published_to'))) {
            $publishedTo = Carbon::parse($request->get('published_to'))->endOfDay();
            $query->where('published_at', '<=', $publishedTo);
        }

        // Search by title or content
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        // Filter by author (Admin only)
        if (Auth::user()->isAdmin() && $request->has('author_id') && !empty($request->get('author_id'))) {
            $query->where('author_id', $request->get('author_id'));
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['title', 'created_at', 'updated_at', 'published_at', 'status'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100

        $articles = $query->paginate($perPage);

        // Add filter metadata to response
        $filters = [
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'),
            'category_ids' => $request->get('category_ids'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'published_from' => $request->get('published_from'),
            'published_to' => $request->get('published_to'),
            'search' => $request->get('search'),
            'author_id' => Auth::user()->isAdmin() ? $request->get('author_id') : null,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'per_page' => $perPage,
        ];

        return response()->json([
            'success' => true,
            'data' => $articles,
            'filters' => $filters,
            'user_role' => Auth::user()->role,
            'can_manage_all' => Auth::user()->isAdmin(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        
        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'user_role' => Auth::user()->role,
                'can_manage_all' => Auth::user()->isAdmin(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'summary' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published,archived',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'summary' => $request->summary,
            'status' => $request->status,
            'author_id' => Auth::id(),
            'published_at' => $request->status === 'published' ? now() : null,
        ]);

        // Attach categories
        if ($request->has('category_ids')) {
            $article->categories()->attach($request->category_ids);
        }

        $article->load(['author', 'categories']);

        return response()->json([
            'success' => true,
            'message' => 'Article created successfully. AI-powered slug and summary generation is in progress.',
            'data' => $article
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $article = Article::with(['author', 'categories'])->findOrFail($id);

        // Role-based access: Authors can only view their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only view your own articles.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $article,
            'user_role' => Auth::user()->role,
            'can_edit' => Auth::user()->isAdmin() || $article->author_id === Auth::id(),
            'can_delete' => Auth::user()->isAdmin() || $article->author_id === Auth::id(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $article = Article::with(['categories'])->findOrFail($id);

        // Role-based access: Authors can only edit their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only edit your own articles.'
            ], 403);
        }

        $categories = Category::all();

        return response()->json([
            'success' => true,
            'data' => [
                'article' => $article,
                'categories' => $categories,
                'user_role' => Auth::user()->role,
                'can_manage_all' => Auth::user()->isAdmin(),
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $article = Article::findOrFail($id);

        // Role-based access: Authors can only update their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only update your own articles.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'summary' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published,archived',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $article->update([
            'title' => $request->title,
            'content' => $request->content,
            'summary' => $request->summary,
            'status' => $request->status,
            'published_at' => $request->status === 'published' ? now() : null,
        ]);

        // Sync categories
        if ($request->has('category_ids')) {
            $article->categories()->sync($request->category_ids);
        }

        $article->load(['author', 'categories']);

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully. AI-powered slug and summary regeneration is in progress.',
            'data' => $article
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $article = Article::findOrFail($id);

        // Role-based access: Authors can only delete their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only delete your own articles.'
            ], 403);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully'
        ]);
    }

    /**
     * Publish an article.
     */
    public function publish(string $id)
    {
        $article = Article::findOrFail($id);

        // Role-based access: Authors can only publish their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only publish your own articles.'
            ], 403);
        }

        $article->publish();

        return response()->json([
            'success' => true,
            'message' => 'Article published successfully',
            'data' => $article->load(['author', 'categories'])
        ]);
    }

    /**
     * Archive an article.
     */
    public function archive(string $id)
    {
        $article = Article::findOrFail($id);

        // Role-based access: Authors can only archive their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only archive your own articles.'
            ], 403);
        }

        $article->archive();

        return response()->json([
            'success' => true,
            'message' => 'Article archived successfully',
            'data' => $article->load(['author', 'categories'])
        ]);
    }

    /**
     * Manually regenerate AI-powered slug.
     */
    public function regenerateSlug(string $id)
    {
        $article = Article::findOrFail($id);

        // Role-based access: Authors can only regenerate slug for their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only regenerate slug for your own articles.'
            ], 403);
        }

        $article->regenerateSlug();

        return response()->json([
            'success' => true,
            'message' => 'AI-powered slug regeneration has been queued. The slug will be updated shortly.',
            'data' => $article->load(['author', 'categories'])
        ]);
    }

    /**
     * Manually regenerate AI-powered summary.
     */
    public function regenerateSummary(string $id)
    {
        $article = Article::findOrFail($id);

        // Role-based access: Authors can only regenerate summary for their own articles
        if (!Auth::user()->isAdmin() && $article->author_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only regenerate summary for your own articles.'
            ], 403);
        }

        $article->regenerateSummary();

        return response()->json([
            'success' => true,
            'message' => 'AI-powered summary regeneration has been queued. The summary will be updated shortly.',
            'data' => $article->load(['author', 'categories'])
        ]);
    }

    /**
     * Generate AI-powered summary from content (for create article page).
     */
    public function generateSummaryFromContent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Use the same AI logic as the Article model
            $content = strip_tags($request->content);
            
            // Simple summary generation (you can replace this with OpenAI API call)
            $summary = $this->generateAISummary($content);
            
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'message' => 'Summary generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate AI-powered slug from title and content (for create article page).
     */
    public function generateSlugFromContent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1',
            'content' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Use the same AI logic as the Article model
            $title = $request->title;
            $content = strip_tags($request->content);
            
            // Simple slug generation (you can replace this with OpenAI API call)
            $slug = $this->generateAISlug($title, $content);
            
            return response()->json([
                'success' => true,
                'slug' => $slug,
                'message' => 'Slug generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate slug: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate AI summary using OpenAI or fallback method.
     */
    private function generateAISummary($content)
    {
        // Check if OpenAI API key is configured
        if (config('services.openai.api_key')) {
            try {
                $client = \OpenAI::client(config('services.openai.api_key'));
                
                $response = $client->chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant that generates concise, engaging summaries of articles. Generate a summary that is 2-3 sentences long and captures the main points of the article.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Generate a summary for this article content:\n\n" . substr($content, 0, 2000)
                        ]
                    ],
                    'max_tokens' => 150,
                    'temperature' => 0.7,
                ]);

                return trim($response->choices[0]->message->content);
            } catch (\Exception $e) {
                // Fallback to simple summary if OpenAI fails
                return $this->generateSimpleSummary($content);
            }
        }

        // Fallback to simple summary
        return $this->generateSimpleSummary($content);
    }

    /**
     * Generate AI slug using OpenAI or fallback method.
     */
    private function generateAISlug($title, $content)
    {
        // Check if OpenAI API key is configured
        if (config('services.openai.api_key')) {
            try {
                $client = \OpenAI::client(config('services.openai.api_key'));
                
                $response = $client->chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant that generates SEO-friendly URL slugs. Generate a slug that is 3-5 words, uses hyphens, and is based on the article title and content.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Generate a slug for this article:\nTitle: {$title}\nContent: " . substr($content, 0, 500)
                        ]
                    ],
                    'max_tokens' => 50,
                    'temperature' => 0.7,
                ]);

                $slug = trim($response->choices[0]->message->content);
                // Clean the slug
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9\s-]/', '', $slug));
                $slug = preg_replace('/\s+/', '-', $slug);
                $slug = trim($slug, '-');
                
                return $slug;
            } catch (\Exception $e) {
                // Fallback to simple slug if OpenAI fails
                return $this->generateSimpleSlug($title);
            }
        }

        // Fallback to simple slug
        return $this->generateSimpleSlug($title);
    }

    /**
     * Generate simple summary as fallback.
     */
    private function generateSimpleSummary($content)
    {
        $content = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $summary = '';
        
        // Take first 2-3 sentences
        for ($i = 0; $i < min(3, count($sentences)); $i++) {
            if (trim($sentences[$i])) {
                $summary .= trim($sentences[$i]) . '. ';
            }
        }
        
        return trim($summary);
    }

    /**
     * Generate simple slug as fallback.
     */
    private function generateSimpleSlug($title)
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Get articles statistics for dashboard.
     */
    public function statistics()
    {
        $query = Article::query();

        // Role-based filtering: Authors can only see their own statistics
        if (!Auth::user()->isAdmin()) {
            $query->where('author_id', Auth::id());
        }

        $statistics = [
            'total_articles' => $query->count(),
            'published_articles' => (clone $query)->where('status', 'published')->count(),
            'draft_articles' => (clone $query)->where('status', 'draft')->count(),
            'archived_articles' => (clone $query)->where('status', 'archived')->count(),
            'recent_articles' => (clone $query)->where('created_at', '>=', now()->subDays(7))->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'user_role' => Auth::user()->role,
        ]);
    }
}

