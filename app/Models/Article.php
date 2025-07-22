<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateSlugJob;
use App\Jobs\GenerateSummaryJob;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'summary',
        'status',
        'published_at',
        'author_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug and summary on creation
        static::creating(function ($article) {
            // Set author if not provided
            if (empty($article->author_id)) {
                $article->author_id = Auth::id();
            }
            
            // Generate basic slug and summary for immediate use
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            
            if (empty($article->summary)) {
                $article->summary = Str::limit(strip_tags($article->content), 200);
            }
        });

        // After creation, dispatch AI jobs for better slug and summary
        static::created(function ($article) {
            // Dispatch AI slug generation job
            GenerateSlugJob::dispatch($article->id, $article->title, $article->content);
            
            // Dispatch AI summary generation job
            GenerateSummaryJob::dispatch($article->id, $article->content);
        });

        // Update slug and summary on content changes
        static::updating(function ($article) {
            // If title changed, regenerate slug
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            
            // If content changed, regenerate summary
            if ($article->isDirty('content') && empty($article->summary)) {
                $article->summary = Str::limit(strip_tags($article->content), 200);
            }
        });

        // After update, dispatch AI jobs if content changed significantly
        static::updated(function ($article) {
            // If title changed significantly, regenerate AI slug
            if ($article->wasChanged('title')) {
                GenerateSlugJob::dispatch($article->id, $article->title, $article->content);
            }
            
            // If content changed significantly, regenerate AI summary
            if ($article->wasChanged('content')) {
                GenerateSummaryJob::dispatch($article->id, $article->content);
            }
        });
    }

    /**
     * Get the author of the article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the categories for the article.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_categories');
    }

    /**
     * Scope a query to only include published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft articles.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include archived articles.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Check if the article is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the article is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the article is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Publish the article.
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Archive the article.
     */
    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
        ]);
    }

    /**
     * Manually trigger AI slug regeneration.
     */
    public function regenerateSlug(): void
    {
        GenerateSlugJob::dispatch($this->id, $this->title, $this->content);
    }

    /**
     * Manually trigger AI summary regeneration.
     */
    public function regenerateSummary(): void
    {
        GenerateSummaryJob::dispatch($this->id, $this->content);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
