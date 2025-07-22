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
use Illuminate\Support\Str;

class GenerateSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $articleId,
        protected string $content
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
                Log::warning("Article {$this->articleId} not found for summary generation");
                return;
            }

            // Generate AI-powered summary
            $aiSummary = $aiService->generateSummary($this->content);
            
            // Update the article with the new summary
            $article->update(['summary' => $aiSummary]);
            
            Log::info("AI-generated summary for article {$this->articleId}: " . Str::limit($aiSummary, 50));
            
        } catch (\Exception $e) {
            Log::error("Summary generation failed for article {$this->articleId}: " . $e->getMessage());
            
            // Fallback to simple summary generation
            $this->generateFallbackSummary($article);
        }
    }

    /**
     * Generate fallback summary if AI generation fails
     */
    protected function generateFallbackSummary(Article $article): void
    {
        $fallbackSummary = \Illuminate\Support\Str::limit(strip_tags($this->content), 200);
        
        $article->update(['summary' => $fallbackSummary]);
        Log::info("Fallback summary generated for article {$this->articleId}: " . Str::limit($fallbackSummary, 50));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Summary generation job failed for article {$this->articleId}: " . $exception->getMessage());
        
        // Generate fallback summary
        $article = Article::find($this->articleId);
        if ($article) {
            $this->generateFallbackSummary($article);
        }
    }
}

      