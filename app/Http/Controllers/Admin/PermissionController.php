<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

#[Group('Admin', description: 'Endpoint khusus administrator.', weight: 3)]
class PermissionController extends Controller
{
    /**
     * Display a listing of permissions (Admin).
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            $allowedSortFields = ['id', 'name', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Permission::query();

            if ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            }

            $query->orderBy($sortBy, $sortOrder);

            $permissions = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedPermissions = $permissions->map(function ($permission) {
                return $this->formatResponse($permission);
            });

            $pagination = [
                'current_page' => $permissions->currentPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
                'last_page' => $permissions->lastPage(),
                'from' => $permissions->firstItem(),
                'to' => $permissions->lastItem(),
                'total_pages' => $permissions->lastPage(),
                'has_more_pages' => $permissions->hasMorePages(),
                'has_previous_pages' => $permissions->currentPage() > 1,
            ];

            $links = [
                'first' => $permissions->url(1),
                'last' => $permissions->url($permissions->lastPage()),
                'prev' => $permissions->previousPageUrl(),
                'next' => $permissions->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $permissions->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $permissions->url($i),
                    'label' => $i,
                    'active' => $i == $permissions->currentPage(),
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
                $formattedPermissions,
                'Permissions retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created permission (Admin).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name',
            ]);

            $permission = Permission::create(['name' => $validated['name']]);

            return $this->successResponse(
                $this->formatResponse($permission),
                'Permission created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show permission by id (Admin).
     */
    public function show(int $id)
    {
        try {
            $permission = Permission::find($id);

            if (! $permission) {
                return $this->notFoundResponse('Permission not found');
            }

            return $this->successResponse(
                $this->formatResponse($permission),
                'Permission retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Update the specified permission (Admin).
     */
    public function update(Request $request, int $id)
    {
        try {
            $permission = Permission::find($id);

            if (! $permission) {
                return $this->notFoundResponse('Permission not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:permissions,name,' . $id,
            ]);

            $permission->update($validated);

            return $this->successResponse(
                $this->formatResponse($permission->fresh()),
                'Permission updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified permission (Admin).
     */
    public function destroy(int $id)
    {
        try {
            $permission = Permission::find($id);

            if (! $permission) {
                return $this->notFoundResponse('Permission not found');
            }

            $permission->delete();

            return $this->successResponse(
                null,
                'Permission deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Format permission data for API response.
     */
    private function formatResponse(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'created_at' => $permission->created_at?->toISOString(),
            'updated_at' => $permission->updated_at?->toISOString(),
        ];
    }
}
