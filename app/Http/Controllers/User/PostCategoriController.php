<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CategoriPost;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

#[Group('User', description: 'Endpoint untuk pengguna yang sudah login.', weight: 2)]
class PostCategoriController extends Controller
{
    /**
     * Display a listing of post categories (User).
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            $allowedSortFields = ['id', 'name', 'slug', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = CategoriPost::query()->with('user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            $query->orderBy($sortBy, $sortOrder);

            $categories = $query->paginate($perPage, ['*'], 'page', $page);

            $pagination = [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
                'has_more_pages' => $categories->hasMorePages(),
                'has_previous_pages' => $categories->currentPage() > 1,
            ];

            $links = [
                'first' => $categories->url(1),
                'last' => $categories->url($categories->lastPage()),
                'prev' => $categories->previousPageUrl(),
                'next' => $categories->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $categories->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $categories->url($i),
                    'label' => $i,
                    'active' => $i == $categories->currentPage(),
                ];
            }

            $meta = [
                'sorting' => [
                    'current_sort_by' => $sortBy,
                    'current_sort_order' => $sortOrder,
                    'available_sort_fields' => $allowedSortFields,
                ],
                'filters' => [
                    'search' => $search,
                ],
                'page_links' => $pageLinks,
            ];

            return $this->paginatedResponse(
                $categories->items(),
                'Post categories retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created post category (User).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
            ]);

            $validated['user_id'] = $request->user()->id;
            $validated['slug'] = str($validated['name'])->slug() . '-' . time();

            $category = CategoriPost::create($validated);

            return $this->successResponse(
                $category->load('user'),
                'Post category created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show post category by uuid (User).
     */
    public function show(string $uuid)
    {
        try {
            $category = CategoriPost::with('user')->where('uuid', $uuid)->first();

            if (! $category) {
                return $this->notFoundResponse('Post category not found');
            }

            return $this->successResponse(
                $category,
                'Post category retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Update the specified post category in storage (User).
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $category = CategoriPost::where('uuid', $uuid)->first();

            if (! $category) {
                return $this->notFoundResponse('Post category not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:255',
            ]);

            if (isset($validated['name'])) {
                $validated['slug'] = str($validated['name'])->slug() . '-' . $category->id;
            }

            $category->update($validated);

            return $this->successResponse(
                $category->fresh()->load('user'),
                'Post category updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified post category from storage (User).
     */
    public function destroy(string $uuid)
    {
        try {
            $category = CategoriPost::where('uuid', $uuid)->first();

            if (! $category) {
                return $this->notFoundResponse('Post category not found');
            }

            $category->delete();

            return $this->successResponse(
                null,
                'Post category deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }
}
