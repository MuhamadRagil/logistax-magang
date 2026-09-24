<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * Usage: middleware('admin.role:admin_magang') or middleware('admin.role:admin_magang,spv_mentor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof AdminUser) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Akun admin tidak aktif.',
            ], 403);
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized. Insufficient role.',
            ], 403);
        }

        return $next($request);
    }
}
