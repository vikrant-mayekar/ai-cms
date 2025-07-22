<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIContentService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Generate a unique slug using AI
     */
    public function generateSlug(string $title, string $content = ''): string
    {
        try {
            $prompt = "Generate a unique, SEO-friendly URL slug for this article. The slug should be:\n";
            $prompt .= "- 3-5 words maximum\n";
            $prompt .= "- All lowercase\n";
            $prompt .= "- Words separated by hyphens\n";
            $prompt .= "- Descriptive and relevant to the content\n";
            $prompt .= "- No special characters except hyphens\n\n";
            $prompt .= "Title: {$title}\n";
            if (!empty($content)) {
                $prompt .= "Content preview: " . Str::limit(strip_tags($content), 200) . "\n\n";
            }
            $prompt .= "Generate only the slug, no additional text:";

            $response = $this->callOpenAI($prompt, 50);

            if ($response) {
                // Clean the response and ensure it's a valid slug
                $slug = Str::slug($response);
                return $slug ?: Str::slug($title);
            }
        } catch (\Exception $e) {
            Log::error('AI Slug generation failed: ' . $e->getMessage());
        }

        // Fallback to title-based slug
        return Str::slug($title);
    }

    /**
     * Generate article summary using AI
     */
    public function generateSummary(string $content): string
    {
        try {
            $prompt = "Generate a brief, engaging summary of this article content in 2-3 sentences. ";
            $prompt .= "The summary should:\n";
            $prompt .= "- Be 100-150 characters maximum\n";
            $prompt .= "- Capture the main points\n";
            $prompt .= "- Be written in a professional tone\n";
            $prompt .= "- Not include HTML tags\n\n";
            $prompt .= "Content: " . strip_tags($content) . "\n\n";
            $prompt .= "Summary:";

            $response = $this->callOpenAI($prompt, 200);

            if ($response) {
                // Clean the response
                $summary = trim(strip_tags($response));
                return Str::limit($summary, 200);
            }
        } catch (\Exception $e) {
            Log::error('AI Summary generation failed: ' . $e->getMessage());
        }

        // Fallback to simple content extraction
        return Str::limit(strip_tags($content), 200);
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $prompt, int $maxTokens = 100): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            } else {
                Log::error('OpenAI API error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API call failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate slug asynchronously using queue
     */
    public function generateSlugAsync(string $title, string $content = '', callable $callback = null): void
    {
        dispatch(function () use ($title, $content, $callback) {
            $slug = $this->generateSlug($title, $content);
            if ($callback) {
                $callback($slug);
            }
        })->onQueue('ai-processing');
    }

    /**
     * Generate summary asynchronously using queue
     */
    public function generateSummaryAsync(string $content, callable $callback = null): void
    {
        dispatch(function () use ($content, $callback) {
            $summary = $this->generateSummary($content);
            if ($callback) {
                $callback($summary);
            }
        })->onQueue('ai-processing');
    }
} 
