<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUser
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user has the User role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasRole('User')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. User access required.',
            ], 403);
        }

        return $next($request);
    }
}
