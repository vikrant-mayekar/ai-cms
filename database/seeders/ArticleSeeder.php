<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Article;
use App\Models\User;
use App\Models\Category;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();

        if ($users->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $articles = [
            [
                'title' => 'Getting Started with Laravel Development',
                'content' => '<h2>Introduction to Laravel</h2><p>Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:</p><ul><li>Simple, fast routing engine</li><li>Powerful dependency injection container</li><li>Multiple back-ends for session and cache storage</li><li>Expressive, intuitive database ORM</li><li>Database agnostic schema migrations</li><li>Robust background job processing</li><li>Real-time event broadcasting</li></ul><p>Laravel is accessible, powerful, and provides tools required for large, robust applications.</p>',
                'status' => 'published',
                'category_ids' => [1, 5], // Technology, Education
            ],
            [
                'title' => 'The Future of Artificial Intelligence',
                'content' => '<h2>AI Revolution</h2><p>Artificial Intelligence is transforming the way we live and work. From machine learning algorithms to natural language processing, AI is becoming an integral part of our daily lives.</p><p>Key areas where AI is making an impact:</p><ul><li>Healthcare and medical diagnosis</li><li>Autonomous vehicles</li><li>Smart home devices</li><li>Financial services</li><li>Education and learning</li></ul><p>As we move forward, the integration of AI will continue to accelerate, creating new opportunities and challenges for society.</p>',
                'status' => 'published',
                'category_ids' => [1, 7], // Technology, Science
            ],
            [
                'title' => 'Building a Successful Startup',
                'content' => '<h2>Entrepreneurship Guide</h2><p>Starting a business is one of the most challenging yet rewarding endeavors you can undertake. Success requires careful planning, execution, and perseverance.</p><p>Essential steps for startup success:</p><ol><li>Validate your idea with market research</li><li>Create a solid business plan</li><li>Build a strong team</li><li>Secure funding</li><li>Launch and iterate</li><li>Scale strategically</li></ol><p>Remember, most successful entrepreneurs faced multiple failures before achieving success. The key is to learn from each experience and keep moving forward.</p>',
                'status' => 'draft',
                'category_ids' => [2], // Business
            ],
            [
                'title' => 'Healthy Living in the Digital Age',
                'content' => '<h2>Wellness in Modern Times</h2><p>In today\'s fast-paced digital world, maintaining good health and wellness has become more important than ever. The constant use of technology can impact our physical and mental well-being.</p><p>Tips for healthy living:</p><ul><li>Take regular breaks from screens</li><li>Exercise regularly</li><li>Maintain a balanced diet</li><li>Get adequate sleep</li><li>Practice mindfulness</li><li>Stay hydrated</li></ul><p>Small changes in daily habits can lead to significant improvements in overall health and quality of life.</p>',
                'status' => 'published',
                'category_ids' => [3, 8], // Health & Wellness, Lifestyle
            ],
            [
                'title' => 'Top Travel Destinations for 2024',
                'content' => '<h2>Adventure Awaits</h2><p>Travel is one of the most enriching experiences life has to offer. It broadens our horizons, introduces us to new cultures, and creates lasting memories.</p><p>Must-visit destinations for 2024:</p><ul><li>Japan - Cherry blossom season</li><li>Iceland - Northern lights</li><li>Morocco - Cultural heritage</li><li>New Zealand - Natural beauty</li><li>Portugal - History and cuisine</li></ul><p>Each destination offers unique experiences that will enrich your understanding of the world and create unforgettable memories.</p>',
                'status' => 'published',
                'category_ids' => [4], // Travel
            ],
        ];

        foreach ($articles as $articleData) {
            $categoryIds = $articleData['category_ids'];
            unset($articleData['category_ids']);

            $article = Article::create([
                'title' => $articleData['title'],
                'content' => $articleData['content'],
                'status' => $articleData['status'],
                'author_id' => $users->random()->id,
                'published_at' => $articleData['status'] === 'published' ? now() : null,
            ]);

            // Attach categories
            $article->categories()->attach($categoryIds);
        }
    }
}
