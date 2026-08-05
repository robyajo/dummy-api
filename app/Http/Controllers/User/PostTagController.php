<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

#[Group('User', description: 'Endpoint untuk pengguna yang sudah login.', weight: 2)]
class PostTagController extends Controller
{
    /**
     * Display a listing of post tags (User).
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

            $query = Tag::query()->with('user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            $query->orderBy($sortBy, $sortOrder);

            $tags = $query->paginate($perPage, ['*'], 'page', $page);

            $pagination = [
                'current_page' => $tags->currentPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
                'last_page' => $tags->lastPage(),
                'from' => $tags->firstItem(),
                'to' => $tags->lastItem(),
                'has_more_pages' => $tags->hasMorePages(),
                'has_previous_pages' => $tags->currentPage() > 1,
            ];

            $links = [
                'first' => $tags->url(1),
                'last' => $tags->url($tags->lastPage()),
                'prev' => $tags->previousPageUrl(),
                'next' => $tags->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $tags->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $tags->url($i),
                    'label' => $i,
                    'active' => $i == $tags->currentPage(),
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
                $tags->items(),
                'Post tags retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created post tag (User).
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

            $tag = Tag::create($validated);

            return $this->successResponse(
                $tag->load('user'),
                'Post tag created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show post tag by uuid (User).
     */
    public function show(string $uuid)
    {
        try {
            $tag = Tag::with('user')->where('uuid', $uuid)->first();

            if (! $tag) {
                return $this->notFoundResponse('Post tag not found');
            }

            return $this->successResponse(
                $tag,
                'Post tag retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Update the specified post tag in storage (User).
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $tag = Tag::where('uuid', $uuid)->first();

            if (! $tag) {
                return $this->notFoundResponse('Post tag not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:255',
            ]);

            if (isset($validated['name'])) {
                $validated['slug'] = str($validated['name'])->slug() . '-' . $tag->id;
            }

            $tag->update($validated);

            return $this->successResponse(
                $tag->fresh()->load('user'),
                'Post tag updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified post tag from storage (User).
     */
    public function destroy(string $uuid)
    {
        try {
            $tag = Tag::where('uuid', $uuid)->first();

            if (! $tag) {
                return $this->notFoundResponse('Post tag not found');
            }

            $tag->delete();

            return $this->successResponse(
                null,
                'Post tag deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }
}
