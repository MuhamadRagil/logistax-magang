<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\Web\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Session-based login/logout for the Blade dashboard (admin_magang /
 * spv_mentor via browser). Deliberately separate from Api\AuthController,
 * which issues Sanctum tokens for the mobile app and intern accounts —
 * that controller/behavior is untouched. This one authenticates against the
 * same admin_users table, but via the 'web' session guard configured in
 * config/auth.php.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $admin = Auth::guard('web')->user();

        if (! $admin->is_active) {
            Auth::guard('web')->logout();

            return back()->withErrors([
                'email' => 'Akun admin tidak aktif.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
