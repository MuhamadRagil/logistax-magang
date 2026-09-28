<?php

namespace App\Services;

use App\Models\OfficeLocation;
use Illuminate\Database\Eloquent\Collection;

/**
 * Office-location CRUD shared by Api\OfficeLocationController and the web
 * "Pengaturan > Lokasi Kantor" page (same Fase 5 pattern as
 * InternWorkflowService). No hard delete: "hapus"/nonaktifkan only flips
 * is_active, exactly like the API's DELETE always did.
 */
class OfficeLocationService
{
    /**
     * @return Collection<int, OfficeLocation>
     */
    public function all(): Collection
    {
        return OfficeLocation::orderBy('name')->get();
    }

    public function activeCount(): int
    {
        return OfficeLocation::where('is_active', true)->count();
    }

    public function create(array $data): OfficeLocation
    {
        return OfficeLocation::create($data);
    }

    public function update(OfficeLocation $location, array $data): OfficeLocation
    {
        $location->update($data);

        return $location->fresh();
    }

    public function deactivate(OfficeLocation $location): void
    {
        $location->update(['is_active' => false]);
    }

    public function activate(OfficeLocation $location): void
    {
        $location->update(['is_active' => true]);
    }
}
