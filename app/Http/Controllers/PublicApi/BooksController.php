<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class BooksController extends Controller
{
    /**
     * Display a listing of books (Public).
     *
     * @unauthenticated
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $title = $request->query('title');
            $author = $request->query('author');
            $category = $request->query('category');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $language = $request->query('language');
            $publisher = $request->query('publisher');
            $minPrice = $request->query('min_price');
            $maxPrice = $request->query('max_price');

            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            $allowedSortFields = ['id', 'title', 'isbn', 'publisher', 'price', 'rating', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Book::query();

            if ($title) {
                $query->where('title', $title);
            } elseif ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%'.$search.'%')
                        ->orWhere('description', 'LIKE', '%'.$search.'%')
                        ->orWhereJsonContains('authors', $search);
                });
            }

            if ($author) {
                $query->whereJsonContains('authors', $author);
            }

            if ($category) {
                $query->whereJsonContains('categories', $category);
            }

            if ($language) {
                $query->where('language', $language);
            }

            if ($publisher) {
                $query->where('publisher', 'LIKE', '%'.$publisher.'%');
            }

            if ($minPrice) {
                $query->where('price', '>=', $minPrice);
            }

            if ($maxPrice) {
                $query->where('price', '<=', $maxPrice);
            }

            $query->orderBy($sortBy, $sortOrder);

            $books = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedBooks = $books->map(function ($book) {
                return $this->formatResponse($book);
            });

            $pagination = [
                'current_page' => $books->currentPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
                'last_page' => $books->lastPage(),
                'from' => $books->firstItem(),
                'to' => $books->lastItem(),
                'total_pages' => $books->lastPage(),
                'has_more_pages' => $books->hasMorePages(),
                'has_previous_pages' => $books->currentPage() > 1,
            ];

            $links = [
                'first' => $books->url(1),
                'last' => $books->url($books->lastPage()),
                'prev' => $books->previousPageUrl(),
                'next' => $books->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $books->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $books->url($i),
                    'label' => $i,
                    'active' => $i == $books->currentPage(),
                ];
            }

            $meta = [
                'sorting' => [
                    'current_sort_by' => $sortBy,
                    'current_sort_order' => $sortOrder,
                    'available_sort_fields' => $allowedSortFields,
                ],
                'filters' => [
                    'title' => $title,
                    'search' => $search,
                    'author' => $author,
                    'category' => $category,
                    'language' => $language,
                    'publisher' => $publisher,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                ],
                'page_links' => $pageLinks,
            ];

            return $this->paginatedResponse(
                $formattedBooks,
                'Books retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Show book by slug (Public).
     *
     * @unauthenticated
     */
    public function show(string $slug)
    {
        try {
            $book = Book::where('slug', $slug)->first();

            if (! $book) {
                return $this->notFoundResponse('Book not found');
            }

            return $this->successResponse(
                $this->formatResponse($book),
                'Book retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    private function formatResponse(Book $data): array
    {
        return [
            'id' => $data->id,
            'uuid' => $data->uuid,
            'title' => $data->title,
            'slug' => $data->slug,
            'description' => $data->description,
            'isbn' => $data->isbn,
            'publisher' => $data->publisher,
            'published_date' => $data->published_date,
            'pages' => $data->pages,
            'language' => $data->language,
            'price' => $data->price,
            'stock_quantity' => $data->stock_quantity,
            'cover_image' => $data->cover_image,
            'cover_image_url' => $data->cover_image_url,
            'categories' => $data->categories,
            'authors' => $data->authors,
            'rating' => $data->rating,
            'rating_count' => $data->rating_count,
            'user_id' => $data->user_id,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
            'deleted_at' => $data->deleted_at,
        ];
    }
}
