<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session-based counterpart to Api\EnsureAdminRole: same role check, but
 * redirects to the login page / aborts 403 with an HTML error page instead
 * of returning JSON — appropriate for the browser-facing dashboard routes in
 * routes/web.php. The API's EnsureAdminRole (routes/api.php) is untouched.
 */
class EnsureAdminRoleWeb
{
    /**
     * Usage: middleware('admin.role.web:admin_magang') or middleware('admin.role.web:admin_magang,spv_mentor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user('web');

        if (! $user instanceof AdminUser) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            auth('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Akun admin tidak aktif.']);
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
