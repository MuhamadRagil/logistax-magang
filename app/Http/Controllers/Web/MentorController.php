<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DomainActionException;
use App\Models\AdminUser;
use App\Models\Division;
use App\Services\MentorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MentorController extends Controller
{
    public function __construct(private readonly MentorService $mentors) {}

    public function index(): View
    {
        return view('settings.mentors', [
            'mentors' => $this->mentors->all(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admin_users,email'],
            'division_id' => ['nullable', 'exists:divisions,id'],
        ]);

        $result = $this->mentors->create($request->only('name', 'email', 'division_id'));

        return redirect()->route('settings.mentors.index')
            ->with('status', "Mentor \"{$result['mentor']->name}\" berhasil ditambahkan. Password: {$result['generated_password']} (simpan sekarang, tidak akan ditampilkan lagi).");
    }

    public function update(Request $request, AdminUser $mentor): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'division_id' => ['nullable', 'exists:divisions,id'],
        ]);

        $mentor = $this->mentors->update($mentor, $request->only('name', 'division_id'));

        return redirect()->route('settings.mentors.index')
            ->with('status', "Mentor \"{$mentor->name}\" berhasil diperbarui.");
    }

    public function destroy(AdminUser $mentor): RedirectResponse
    {
        try {
            $this->mentors->delete($mentor);
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('settings.mentors.index')
            ->with('status', "Mentor \"{$mentor->name}\" berhasil dihapus.");
    }
}
