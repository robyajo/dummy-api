<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Fiction', 'Non-Fiction', 'Science', 'Technology', 'Business', 'History', 'Biography', 'Self-Help', 'Programming', 'Design'];
        $authors = ['John Doe', 'Jane Smith', 'Robert Johnson', 'Emily Davis', 'Michael Wilson', 'Sarah Brown', 'David Lee', 'Lisa Anderson', 'James Taylor', 'Mary Martinez'];
        $publishers = ['Tech Press', 'Academic Publishing', 'Creative Books', 'Global Publishers', 'Digital Media', 'Classic Press', 'Modern Books', 'Future Publishing'];

        $books = [];

        for ($i = 1; $i <= 100; $i++) {
            $title = "Book Title " . $i;
            $selectedCategories = array_rand($categories, rand(1, 3));
            if (!is_array($selectedCategories)) {
                $selectedCategories = [$selectedCategories];
            }

            $selectedAuthors = array_rand($authors, rand(1, 2));
            if (!is_array($selectedAuthors)) {
                $selectedAuthors = [$selectedAuthors];
            }

            $books[] = [
                'uuid' => Str::uuid(),
                'title' => $title,
                'slug' => Str::slug($title) . '-' . $i,
                'description' => "This is a comprehensive description for book number {$i}. It covers various topics and provides valuable insights for readers interested in this subject matter.",
                'isbn' => '978' . str_pad($i, 10, '0', STR_PAD_LEFT),
                'publisher' => $publishers[array_rand($publishers)],
                'published_date' => now()->subDays(rand(0, 365))->format('Y-m-d'),
                'pages' => rand(100, 800),
                'language' => 'id',
                'price' => rand(50000, 500000) + (rand(0, 99) / 100),
                'stock_quantity' => rand(0, 100),
                'cover_image' => 'book-cover-' . $i . '.jpg',
                'cover_image_url' => asset('assets/template/sample-book.jpg'),
                'categories' => collect($selectedCategories)->map(fn($index) => $categories[$index])->toArray(),
                'authors' => collect($selectedAuthors)->map(fn($index) => $authors[$index])->toArray(),
                'rating' => rand(10, 50) / 10,
                'rating_count' => rand(0, 500),
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('books')->insert($books);
    }
}
