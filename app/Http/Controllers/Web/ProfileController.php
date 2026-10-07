<?php

namespace App\Http\Controllers\Web;

use App\Models\AdminUser;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.index', ['admin' => $request->user('web')]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user('web');
        $oldName = $admin->name;
        $admin->update(['name' => $validated['name']]);

        ActivityLogger::log('profile.updated', $admin, $admin->name, [
            'field' => 'name',
            'old_name' => $oldName,
            'new_name' => $validated['name'],
        ]);

        return back()->with('status', 'Nama berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        $key = 'password-change:' . $admin->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->with('error', "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.");
        }

        RateLimiter::hit($key, 60);

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        if (Hash::check($request->password, $admin->password)) {
            return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan password lama.']);
        }

        $admin->update(['password' => Hash::make($request->password)]);

        $request->session()->regenerate();

        ActivityLogger::log('profile.password_changed', $admin, $admin->name);

        RateLimiter::clear($key);

        return back()->with('status', 'Password berhasil diubah.');
    }
}
