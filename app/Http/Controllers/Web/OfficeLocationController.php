<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\OfficeLocation\StoreOfficeLocationRequest;
use App\Models\OfficeLocation;
use App\Services\OfficeLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Pengaturan > Lokasi Kantor (admin_magang only, see routes/web.php). Same
 * OfficeLocationService as the API. Both store and update use the API's
 * StoreOfficeLocationRequest: the web modal always submits every field, so
 * the "all required" rules are the right ones for editing too.
 */
class OfficeLocationController extends Controller
{
    public function __construct(private readonly OfficeLocationService $locations) {}

    public function index(): View
    {
        $locations = $this->locations->all();

        return view('settings.office-locations', [
            'locations' => $locations,
            'activeCount' => $locations->where('is_active', true)->count(),
        ]);
    }

    public function store(StoreOfficeLocationRequest $request): RedirectResponse
    {
        $location = $this->locations->create($request->validated());

        return redirect()->route('settings.office-locations.index')
            ->with('status', "Lokasi \"{$location->name}\" berhasil ditambahkan.");
    }

    public function update(StoreOfficeLocationRequest $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $location = $this->locations->update($officeLocation, $request->validated());

        return redirect()->route('settings.office-locations.index')
            ->with('status', "Lokasi \"{$location->name}\" berhasil diperbarui.");
    }

    public function deactivate(OfficeLocation $officeLocation): RedirectResponse
    {
        $this->locations->deactivate($officeLocation);

        return redirect()->route('settings.office-locations.index')
            ->with('status', "Lokasi \"{$officeLocation->name}\" dinonaktifkan.");
    }

    public function activate(OfficeLocation $officeLocation): RedirectResponse
    {
        $this->locations->activate($officeLocation);

        return redirect()->route('settings.office-locations.index')
            ->with('status', "Lokasi \"{$officeLocation->name}\" diaktifkan.");
    }
}
