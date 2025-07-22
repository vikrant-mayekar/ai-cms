<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\AIContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSlugJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $articleId,
        protected string $title,
        protected string $content = ''
    ) {
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(AIContentService $aiService): void
    {
        try {
            $article = Article::find($this->articleId);
            
            if (!$article) {
                Log::warning("Article {$this->articleId} not found for slug generation");
                return;
            }

            // Generate AI-powered slug
            $aiSlug = $aiService->generateSlug($this->title, $this->content);
            
            // Ensure slug is unique
            $uniqueSlug = $this->makeSlugUnique($aiSlug, $article->id);
            
            // Update the article with the new slug
            $article->update(['slug' => $uniqueSlug]);
            
            Log::info("AI-generated slug for article {$this->articleId}: {$uniqueSlug}");
            
        } catch (\Exception $e) {
            Log::error("Slug generation failed for article {$this->articleId}: " . $e->getMessage());
            
            // Fallback to simple slug generation
            $this->generateFallbackSlug($article);
        }
    }

    /**
     * Make slug unique by appending number if needed
     */
    protected function makeSlugUnique(string $slug, int $excludeId): string
    {
        $originalSlug = $slug;
        $counter = 1;
        
        while (Article::where('slug', $slug)->where('id', '!=', $excludeId)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Generate fallback slug if AI generation fails
     */
    protected function generateFallbackSlug(Article $article): void
    {
        $fallbackSlug = \Illuminate\Support\Str::slug($this->title);
        $uniqueSlug = $this->makeSlugUnique($fallbackSlug, $article->id);
        
        $article->update(['slug' => $uniqueSlug]);
        Log::info("Fallback slug generated for article {$this->articleId}: {$uniqueSlug}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Slug generation job failed for article {$this->articleId}: " . $exception->getMessage());
        
        // Generate fallback slug
        $article = Article::find($this->articleId);
        if ($article) {
            $this->generateFallbackSlug($article);
        }
    }
}
