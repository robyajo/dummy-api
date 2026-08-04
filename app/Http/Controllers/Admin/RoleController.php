<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles (Admin).
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

            $query = Role::with('permissions');

            if ($search) {
                $query->where('name', 'LIKE', '%'.$search.'%');
            }

            $query->orderBy($sortBy, $sortOrder);

            $roles = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedRoles = $roles->map(function ($role) {
                return $this->formatResponse($role);
            });

            $pagination = [
                'current_page' => $roles->currentPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'last_page' => $roles->lastPage(),
                'from' => $roles->firstItem(),
                'to' => $roles->lastItem(),
                'total_pages' => $roles->lastPage(),
                'has_more_pages' => $roles->hasMorePages(),
                'has_previous_pages' => $roles->currentPage() > 1,
            ];

            $links = [
                'first' => $roles->url(1),
                'last' => $roles->url($roles->lastPage()),
                'prev' => $roles->previousPageUrl(),
                'next' => $roles->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $roles->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $roles->url($i),
                    'label' => $i,
                    'active' => $i == $roles->currentPage(),
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
                $formattedRoles,
                'Roles retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created role (Admin).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            $role = Role::create(['name' => $validated['name']]);

            if (! empty($validated['permissions'])) {
                $role->givePermissionTo($validated['permissions']);
            }

            return $this->successResponse(
                $this->formatResponse($role->load('permissions')),
                'Role created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Show role by id (Admin).
     */
    public function show(int $id)
    {
        try {
            $role = Role::with('permissions')->find($id);

            if (! $role) {
                return $this->notFoundResponse('Role not found');
            }

            return $this->successResponse(
                $this->formatResponse($role),
                'Role retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Update the specified role (Admin).
     */
    public function update(Request $request, int $id)
    {
        try {
            $role = Role::with('permissions')->find($id);

            if (! $role) {
                return $this->notFoundResponse('Role not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:roles,name,'.$id,
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            if (isset($validated['name'])) {
                $role->update(['name' => $validated['name']]);
            }

            if (isset($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            return $this->successResponse(
                $this->formatResponse($role->fresh()->load('permissions')),
                'Role updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified role (Admin).
     */
    public function destroy(int $id)
    {
        try {
            $role = Role::find($id);

            if (! $role) {
                return $this->notFoundResponse('Role not found');
            }

            // Prevent deleting built-in roles
            $protectedRoles = ['Super Admin', 'Admin', 'User'];
            if (in_array($role->name, $protectedRoles)) {
                return $this->errorResponse('Cannot delete built-in role: '.$role->name, 403);
            }

            $role->delete();

            return $this->successResponse(
                null,
                'Role deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Format role data for API response.
     */
    private function formatResponse(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
        ];
    }
}
