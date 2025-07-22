<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Technology',
                'description' => 'Articles about technology, programming, and software development',
            ],
            [
                'name' => 'Business',
                'description' => 'Business news, strategies, and entrepreneurship',
            ],
            [
                'name' => 'Health & Wellness',
                'description' => 'Health tips, fitness, and wellness articles',
            ],
            [
                'name' => 'Travel',
                'description' => 'Travel guides, destinations, and travel tips',
            ],
            [
                'name' => 'Education',
                'description' => 'Educational content, tutorials, and learning resources',
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Movies, music, games, and entertainment news',
            ],
            [
                'name' => 'Science',
                'description' => 'Scientific discoveries, research, and innovations',
            ],
            [
                'name' => 'Lifestyle',
                'description' => 'Lifestyle tips, fashion, and personal development',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
