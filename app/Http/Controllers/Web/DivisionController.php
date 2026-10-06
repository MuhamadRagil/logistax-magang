<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DomainActionException;
use App\Http\Requests\Division\StoreDivisionRequest;
use App\Http\Requests\Division\UpdateDivisionRequest;
use App\Models\Division;
use App\Services\DivisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function __construct(private readonly DivisionService $divisions) {}

    public function index(): View
    {
        return view('settings.divisions', [
            'divisions' => $this->divisions->all(),
        ]);
    }

    public function store(StoreDivisionRequest $request): RedirectResponse
    {
        $division = $this->divisions->create($request->validated());

        return redirect()->route('settings.divisions.index')
            ->with('status', "Divisi \"{$division->name}\" berhasil ditambahkan.");
    }

    public function update(UpdateDivisionRequest $request, Division $division): RedirectResponse
    {
        $division = $this->divisions->update($division, $request->validated());

        return redirect()->route('settings.divisions.index')
            ->with('status', "Divisi \"{$division->name}\" berhasil diperbarui.");
    }

    public function destroy(Division $division): RedirectResponse
    {
        try {
            $this->divisions->delete($division);
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('settings.divisions.index')
            ->with('status', "Divisi \"{$division->name}\" berhasil dinonaktifkan.");
    }
}
