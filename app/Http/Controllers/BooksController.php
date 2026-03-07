<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Book;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;

class BooksController extends Controller
{
    use ApiResponse;


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Get query parameters
            $search = $request->query('search');
            $title = $request->query('title'); // Exact title parameter
            $author = $request->query('author');
            $category = $request->query('category');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $language = $request->query('language');
            $publisher = $request->query('publisher');
            $minPrice = $request->query('min_price');
            $maxPrice = $request->query('max_price');

            // Ordering parameters
            $sortBy = $request->query('sort_by', 'created_at'); // Default sort by created_at
            $sortOrder = $request->query('sort_order', 'desc'); // Default desc

            // Validate sort parameters
            $allowedSortFields = ['id', 'title', 'isbn', 'publisher', 'price', 'rating', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (!in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            // Build query
            $query = Book::query();

            // Exact search by title if provided (priority over general search)
            if ($title) {
                $query->where('title', $title);
            }
            // General search by title, description, or authors if provided (partial match)
            elseif ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%')
                        ->orWhereJsonContains('authors', $search);
                });
            }

            // Filter by author if provided
            if ($author) {
                $query->whereJsonContains('authors', $author);
            }

            // Filter by category if provided
            if ($category) {
                $query->whereJsonContains('categories', $category);
            }

            // Filter by language if provided
            if ($language) {
                $query->where('language', $language);
            }

            // Filter by publisher if provided
            if ($publisher) {
                $query->where('publisher', 'LIKE', '%' . $publisher . '%');
            }

            // Filter by price range if provided
            if ($minPrice) {
                $query->where('price', '>=', $minPrice);
            }
            if ($maxPrice) {
                $query->where('price', '<=', $maxPrice);
            }

            // Apply ordering
            $query->orderBy($sortBy, $sortOrder);

            // Paginate with custom page
            $books = $query->paginate($perPage, ['*'], 'page', $page);

            // Format books data
            $formattedBooks = $books->map(function ($book) {
                return $this->formatResponse($book);
            });

            // Build pagination data
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

            // Build links for pagination
            $links = [
                'first' => $books->url(1),
                'last' => $books->url($books->lastPage()),
                'prev' => $books->previousPageUrl(),
                'next' => $books->nextPageUrl(),
            ];

            // Build page links (for numbered pagination)
            $pageLinks = [];
            for ($i = 1; $i <= $books->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $books->url($i),
                    'label' => $i,
                    'active' => $i == $books->currentPage(),
                ];
            }

            // Meta data for filters and sorting
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
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'isbn' => 'nullable|string|max:255',
                'publisher' => 'nullable|string|max:255',
                'published_date' => 'nullable|date',
                'pages' => 'nullable|integer|min:1',
                'language' => 'nullable|string|max:100',
                'price' => 'required|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'cover_image_url' => 'nullable|url',
                'categories' => 'nullable|array',
                'authors' => 'nullable|array',
                'rating' => 'nullable|numeric|min:0|max:5',
                'rating_count' => 'nullable|integer|min:0',
            ]);

            $validated['slug'] = str($validated['title'])->slug() . '-' . uniqid();

            if ($request->user()) {
                $validated['user_id'] = $request->user()->id;
            }

            if ($request->hasFile('cover_image')) {
                $imageFile = $request->file('cover_image');
                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/books');

                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image = Image::read($imageFile);
                $image->save($path . '/' . $filename);

                $validated['cover_image'] = 'books/' . $filename;
                $validated['cover_image_url'] = url('storage/books/' . $filename);
            }

            $book = Book::create($validated);

            return $this->successResponse(
                $this->formatResponse($book),
                'Book created successfully',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show book by uuid (Admin).
     */
    public function show(string $uuid)
    {
        try {
            $book = Book::where('uuid', $uuid)->first();

            if (!$book) {
                return $this->notFoundResponse('Book not found');
            }

            return $this->successResponse(
                $this->formatResponse($book),
                'Book retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }






    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $book = Book::where('uuid', $uuid)->first();

            if (!$book) {
                return $this->notFoundResponse('Book not found');
            }

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'isbn' => 'nullable|string|max:255',
                'publisher' => 'nullable|string|max:255',
                'published_date' => 'nullable|date',
                'pages' => 'nullable|integer|min:1',
                'language' => 'nullable|string|max:100',
                'price' => 'sometimes|required|numeric|min:0',
                'stock_quantity' => 'sometimes|required|integer|min:0',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'cover_image_url' => 'nullable|url',
                'categories' => 'nullable|array',
                'authors' => 'nullable|array',
                'rating' => 'nullable|numeric|min:0|max:5',
                'rating_count' => 'nullable|integer|min:0',
            ]);

            if (isset($validated['title']) && $validated['title'] !== $book->title) {
                $validated['slug'] = str($validated['title'])->slug() . '-' . uniqid();
            }

            if ($request->hasFile('cover_image')) {
                // Delete old image
                if ($book->cover_image && file_exists(storage_path('app/public/' . $book->cover_image))) {
                    @unlink(storage_path('app/public/' . $book->cover_image));
                }

                $imageFile = $request->file('cover_image');
                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/books');

                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image = Image::read($imageFile);
                $image->save($path . '/' . $filename);

                $validated['cover_image'] = 'books/' . $filename;
                $validated['cover_image_url'] = url('storage/books/' . $filename);
            }

            $book->update($validated);

            return $this->successResponse(
                $this->formatResponse($book->fresh()),
                'Book updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $book = Book::where('uuid', $uuid)->first();

            if (!$book) {
                return $this->notFoundResponse('Book not found');
            }

            if ($book->cover_image && file_exists(storage_path('app/public/' . $book->cover_image))) {
                @unlink(storage_path('app/public/' . $book->cover_image));
            }

            $book->delete();

            return $this->successResponse(
                null,
                'Book deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }
    /**
     * Book (Public)
     * @unauthenticated
     */
    public function indexPublic(Request $request)
    {
        try {
            // Get query parameters
            $search = $request->query('search');
            $title = $request->query('title'); // Exact title parameter
            $author = $request->query('author');
            $category = $request->query('category');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $language = $request->query('language');
            $publisher = $request->query('publisher');
            $minPrice = $request->query('min_price');
            $maxPrice = $request->query('max_price');

            // Ordering parameters
            $sortBy = $request->query('sort_by', 'created_at'); // Default sort by created_at
            $sortOrder = $request->query('sort_order', 'desc'); // Default desc

            // Validate sort parameters
            $allowedSortFields = ['id', 'title', 'isbn', 'publisher', 'price', 'rating', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (!in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            // Build query
            $query = Book::query();

            // Exact search by title if provided (priority over general search)
            if ($title) {
                $query->where('title', $title);
            }
            // General search by title, description, or authors if provided (partial match)
            elseif ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%')
                        ->orWhereJsonContains('authors', $search);
                });
            }

            // Filter by author if provided
            if ($author) {
                $query->whereJsonContains('authors', $author);
            }

            // Filter by category if provided
            if ($category) {
                $query->whereJsonContains('categories', $category);
            }

            // Filter by language if provided
            if ($language) {
                $query->where('language', $language);
            }

            // Filter by publisher if provided
            if ($publisher) {
                $query->where('publisher', 'LIKE', '%' . $publisher . '%');
            }

            // Filter by price range if provided
            if ($minPrice) {
                $query->where('price', '>=', $minPrice);
            }
            if ($maxPrice) {
                $query->where('price', '<=', $maxPrice);
            }

            // Apply ordering
            $query->orderBy($sortBy, $sortOrder);

            // Paginate with custom page
            $books = $query->paginate($perPage, ['*'], 'page', $page);

            // Format books data
            $formattedBooks = $books->map(function ($book) {
                return $this->formatResponse($book);
            });

            // Build pagination data
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

            // Build links for pagination
            $links = [
                'first' => $books->url(1),
                'last' => $books->url($books->lastPage()),
                'prev' => $books->previousPageUrl(),
                'next' => $books->nextPageUrl(),
            ];

            // Build page links (for numbered pagination)
            $pageLinks = [];
            for ($i = 1; $i <= $books->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $books->url($i),
                    'label' => $i,
                    'active' => $i == $books->currentPage(),
                ];
            }

            // Meta data for filters and sorting
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
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }
    /**
     * Show book by slug (Public).
     * @unauthenticated
     */
    public function showPublic(string $slug)
    {
        try {
            $book = Book::where('slug', $slug)->first();

            if (!$book) {
                return $this->notFoundResponse('Book not found');
            }

            return $this->successResponse(
                $this->formatResponse($book),
                'Book retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }
    private function formatResponse($data)
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
