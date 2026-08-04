<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

class UserController extends Controller
{
    /**
     * Display a listing of users (Admin).
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $role = $request->query('role');
            $active = $request->query('active');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            $allowedSortFields = ['id', 'name', 'email', 'active', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = User::with('roles');

            // Search by name or email
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%'.$search.'%')
                        ->orWhere('email', 'LIKE', '%'.$search.'%');
                });
            }

            // Filter by role name
            if ($role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            }

            // Filter by active status
            if ($active !== null && $active !== '') {
                $query->where('active', $active);
            }

            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedUsers = $users->map(function ($user) {
                return $this->formatResponse($user);
            });

            $pagination = [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'total_pages' => $users->lastPage(),
                'has_more_pages' => $users->hasMorePages(),
                'has_previous_pages' => $users->currentPage() > 1,
            ];

            $links = [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $users->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $users->url($i),
                    'label' => $i,
                    'active' => $i == $users->currentPage(),
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
                    'role' => $role,
                    'active' => $active,
                ],
                'page_links' => $pageLinks,
            ];

            return $this->paginatedResponse(
                $formattedUsers,
                'Users retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created user (Admin).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'active' => 'nullable|string|in:active,inactive',
                'roles' => 'nullable|array',
                'roles.*' => 'string|exists:roles,name',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['email_verified_at'] = now();

            if (! isset($validated['active'])) {
                $validated['active'] = 'active';
            }

            if ($request->hasFile('avatar')) {
                $imageFile = $request->file('avatar');
                $filename = time().'_'.uniqid().'.'.$imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/avatars');

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image = Image::read($imageFile);
                $image->save($path.'/'.$filename);

                $validated['avatar'] = 'avatars/'.$filename;
            }

            $roles = $validated['roles'] ?? [];
            unset($validated['roles']);

            $user = User::create($validated);

            if (! empty($roles)) {
                $user->assignRole($roles);
            }

            return $this->successResponse(
                $this->formatResponse($user->load('roles')),
                'User created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Show user by uuid (Admin).
     */
    public function show(string $uuid)
    {
        try {
            $user = User::with('roles')->where('uuid', $uuid)->first();

            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            return $this->successResponse(
                $this->formatResponse($user),
                'User retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Update the specified user (Admin).
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $user = User::with('roles')->where('uuid', $uuid)->first();

            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:users,email,'.$user->id,
                'password' => 'nullable|string|min:6|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'active' => 'nullable|string|in:active,inactive',
                'roles' => 'nullable|array',
                'roles.*' => 'string|exists:roles,name',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            if ($request->hasFile('avatar')) {
                // Delete old avatar
                if ($user->avatar && ! str_starts_with($user->avatar, 'assets/') && file_exists(storage_path('app/public/'.$user->avatar))) {
                    @unlink(storage_path('app/public/'.$user->avatar));
                }

                $imageFile = $request->file('avatar');
                $filename = time().'_'.uniqid().'.'.$imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/avatars');

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image = Image::read($imageFile);
                $image->save($path.'/'.$filename);

                $validated['avatar'] = 'avatars/'.$filename;
            }

            $roles = $validated['roles'] ?? null;
            unset($validated['roles']);

            $user->update($validated);

            if ($roles !== null) {
                $user->syncRoles($roles);
            }

            return $this->successResponse(
                $this->formatResponse($user->fresh()->load('roles')),
                'User updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified user (Admin).
     */
    public function destroy(string $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();

            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            // Prevent deleting Super Admin
            if ($user->hasRole('Super Admin')) {
                return $this->errorResponse('Cannot delete Super Admin user', 403);
            }

            if ($user->avatar && ! str_starts_with($user->avatar, 'assets/') && file_exists(storage_path('app/public/'.$user->avatar))) {
                @unlink(storage_path('app/public/'.$user->avatar));
            }

            $user->delete();

            return $this->successResponse(
                null,
                'User deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Format user data for API response.
     */
    private function formatResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar_url,
            'active' => $user->active,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getPermissionsViaRoles()->pluck('name'),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }
}
