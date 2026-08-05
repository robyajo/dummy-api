<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PostStatusController extends Controller
{
    /**
     * Display a listing of post statuses (User).
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            $allowedSortFields = ['id', 'name', 'slug', 'type', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Status::query()->with('user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%'.$search.'%')
                        ->orWhere('description', 'LIKE', '%'.$search.'%');
                });
            }

            $query->orderBy($sortBy, $sortOrder);

            $statuses = $query->paginate($perPage, ['*'], 'page', $page);

            $pagination = [
                'current_page' => $statuses->currentPage(),
                'per_page' => $statuses->perPage(),
                'total' => $statuses->total(),
                'last_page' => $statuses->lastPage(),
                'from' => $statuses->firstItem(),
                'to' => $statuses->lastItem(),
                'has_more_pages' => $statuses->hasMorePages(),
                'has_previous_pages' => $statuses->currentPage() > 1,
            ];

            $links = [
                'first' => $statuses->url(1),
                'last' => $statuses->url($statuses->lastPage()),
                'prev' => $statuses->previousPageUrl(),
                'next' => $statuses->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $statuses->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $statuses->url($i),
                    'label' => $i,
                    'active' => $i == $statuses->currentPage(),
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
                $statuses->items(),
                'Post statuses retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created post status (User).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'type' => 'required|in:draft,review,published,archived',
            ]);

            $validated['user_id'] = $request->user()->id;
            $validated['slug'] = str($validated['name'])->slug().'-'.time();

            $status = Status::create($validated);

            return $this->successResponse(
                $status->load('user'),
                'Post status created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Show post status by uuid (User).
     */
    public function show(string $uuid)
    {
        try {
            $status = Status::with('user')->where('uuid', $uuid)->first();

            if (! $status) {
                return $this->notFoundResponse('Post status not found');
            }

            return $this->successResponse(
                $status,
                'Post status retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Update the specified post status in storage (User).
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $status = Status::where('uuid', $uuid)->first();

            if (! $status) {
                return $this->notFoundResponse('Post status not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:255',
                'type' => 'sometimes|required|in:draft,review,published,archived',
            ]);

            if (isset($validated['name'])) {
                $validated['slug'] = str($validated['name'])->slug().'-'.$status->id;
            }

            $status->update($validated);

            return $this->successResponse(
                $status->fresh()->load('user'),
                'Post status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified post status from storage (User).
     */
    public function destroy(string $uuid)
    {
        try {
            $status = Status::where('uuid', $uuid)->first();

            if (! $status) {
                return $this->notFoundResponse('Post status not found');
            }

            $status->delete();

            return $this->successResponse(
                null,
                'Post status deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }
}
