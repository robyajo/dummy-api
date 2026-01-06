<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Intervention\Image\Laravel\Facades\Image;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Check if user session is active
     *
     * @return JsonResponse
     */
    public function session(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('Unauthorized', 401, ['authenticated' => false]);
            }

            return $this->successResponse(['authenticated' => true]);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500, ['authenticated' => false]);
        }
    }
    /**
     * Get user permissions and role
     * 
     * @return JsonResponse
     */
    public function permission(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            $cacheKey = "user_permissions_{$user->id}";

            $data = Cache::remember($cacheKey, 60 * 60, function () use ($user) {
                $roleName = $user->roles->first()->name ?? null;

                if ($roleName === 'Super Admin') {
                    $permissions = Permission::all(['id', 'name']);
                } else {
                    $permissions = $user->getPermissionsViaRoles()
                        ->map(fn($p) => ['id' => $p->id, 'name' => $p->name]);
                }

                return [
                    'permissions' => $permissions,
                    'role'        => $roleName,
                ];
            });

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Register a new user.
     * @unauthenticated
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'email.unique' => 'Email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('User');

        $token = JWTAuth::fromUser($user);

        return $this->successResponse([
            'user'  => $this->formatUserResponse($user),
            'token' => $this->respondWithToken($token),
        ], 'User successfully registered', 201);
    }


    /**
     * Login user and get token.
     * 
     * @unauthenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $key = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::warning('Login attempt failed for IP: ' . $request->ip());
            return $this->errorResponse(
                'Too many login attempts. Please try again later.',
                429
            );
        }

        RateLimiter::hit($key, 60); // max 5x per 1 menit

        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
            ],
            'password' => 'required|min:3'
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 3 characters.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }
        // Cek apakah email terdaftar
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Authentication failed', 401, ['email' => ['This email is not registered. Please sign up first.']]);
        }

        // Email ada, cek password
        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Authentication failed', 401, ['password' => ['Password is incorrect.']]);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }
        } catch (JWTException $e) {
            return $this->errorResponse('Could not create token', 500, ['error' => $e->getMessage()]);
        }

        $user = Auth::user();

        return $this->successResponse([
            'user'  => $this->formatUserResponse([
                'id' => $user->id,
                'uuid' => $user->uuid,
                'role' => $user->roles->pluck('name')->first(),
                'active' => $user->active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]),
            'token' => $this->respondWithToken($token),
        ], 'Login successful');
    }
    /**
     * Update User
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(Request $request, string $uuid)
    {
        try {
            /** @var \App\Models\User $user */
            $user = User::with(['roles'])->where('uuid', $uuid)->first();

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'name.required' => 'Name is required.',
                'name.max' => 'Name must not exceed 255 characters.',
                'email.required' => 'Email is required.',
                'email.email' => 'Email format is invalid.',
                'email.unique' => 'Email is already taken.',
                'avatar.image' => 'Avatar must be an image.',
                'avatar.mimes' => 'Avatar must be jpeg, png, jpg, or gif.',
                'avatar.max' => 'Avatar size must not exceed 2MB.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar) {
                    $oldAvatarPath = storage_path('app/public/assets/images/user/avatar/' . $user->avatar);
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
                $user->avatar = $this->uploadAvatar($request->file('avatar'));
            }
            // Update user data
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
            Cache::forget("user_profile_{$user->id}");
            Cache::forget("user_permissions_{$user->id}");
            return $this->successResponse($this->formatUserResponse($user), 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user password
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed|different:current_password',
        ], [
            'new_password.different' => 'New password must be different from current password.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password does not match', 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->successResponse(null, 'Password updated successfully');
    }

    /**
     * Logout user (invalidate token).
     * 
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            $ttl   = JWTAuth::factory()->getTTL() * 60;

            Cache::put('jwt_blacklist_' . $token, true, $ttl);

            JWTAuth::invalidate($token);

            return $this->successResponse(null, 'User logged out successfully');
        } catch (JWTException $e) {
            return $this->errorResponse('Failed to logout', 500);
        }
    }


    /**
     * Refresh auth token.
     * 
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return $this->successResponse($this->respondWithToken($newToken), 'Token refreshed');
        } catch (JWTException $e) {
            return $this->errorResponse('Failed to refresh token', 401, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get authenticated user profile with Redis Cache.
     * 
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Cache key based on user ID
            $cacheKey = 'user_profile_' . $user->id;
            // Try to get from cache, or store if missing (TTL 60 minutes = 60 * 60 seconds)
            // Try to get from cache, or store if missing (TTL 10 minutes = 10 * 60 seconds)
            // Try to get from cache, or store if missing (TTL 5 minutes = 5 * 60 seconds)

            $userProfile = Cache::remember($cacheKey, 60 * 60, function () use ($user) {
                Log::info('REDIS MISS: Mengambil data user dari Database untuk ID: ' . $user->id);
                return $this->formatUserResponse($user);
            });

            return $this->successResponse($userProfile, 'User profile fetched (cached)');
        } catch (JWTException $e) {
            return $this->errorResponse('Token is invalid or expired', 401, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Forgot password
     * @unauthenticated
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'email.exists' => 'Email is not registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }
        try {
            $user = User::where('email', $request->email)->first();
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return $this->successResponse(null, 'Password updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upload and resize avatar image
     *
     * @param UploadedFile $avatar
     * @return string
     */
    private function uploadAvatar($avatar)
    {
        $filename = time() . '_' . Str::random(10) . '.' . $avatar->getClientOriginalExtension();
        $path = storage_path('app/public/assets/images/user/avatar/');

        // Create directory if not exists
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $image = Image::read($avatar);
        $image->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
        })->save($path . $filename);

        return $filename;
    }
    /**
     * Format user response data
     *
     * @param User $user
     * @return array
     */
    private function formatUserResponse($user)
    {
        $roleName = $user->roles->first()->name ?? null;

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar
                ? asset('storage/assets/images/user/avatar/' . $user->avatar)
                : null,
            'role' => $roleName,
            'active' => $user->active,
            // 'profile' => $user->profile, // Relationship not defined yet
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
    /**
     * Helper: format token response
     */
    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60, // dalam detik
        ];
    }
}
