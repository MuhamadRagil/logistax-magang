<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Models\Division;
use Illuminate\Database\Eloquent\Collection;

class DivisionService
{
    public function all(): Collection
    {
        return Division::withCount([
            'interns' => fn ($q) => $q->whereIn('status', ['active', 'extended']),
            'mentors',
        ])->orderBy('name')->get();
    }

    public function create(array $data): Division
    {
        $division = Division::create($data);

        ActivityLogger::log('division.created', $division, $division->name);

        return $division;
    }

    public function update(Division $division, array $data): Division
    {
        $old = $division->name;
        $division->update($data);
        $division = $division->fresh();

        ActivityLogger::log('division.updated', $division, $division->name, [
            'old_name' => $old,
            'new_name' => $division->name,
        ]);

        return $division;
    }

    public function delete(Division $division): void
    {
        $internCount = $division->interns()->count();
        $mentorCount = $division->mentors()->count();

        if ($internCount > 0 || $mentorCount > 0) {
            $parts = [];
            if ($internCount > 0) {
                $parts[] = "{$internCount} intern";
            }
            if ($mentorCount > 0) {
                $parts[] = "{$mentorCount} mentor";
            }
            throw new DomainActionException(
                "Divisi \"{$division->name}\" tidak bisa dihapus karena masih memiliki " . implode(' dan ', $parts) . ' terkait.',
                400
            );
        }

        $name = $division->name;
        $division->update(['is_active' => false]);

        ActivityLogger::log('division.deleted', $division, $name);
    }
}
