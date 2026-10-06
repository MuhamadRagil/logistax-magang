<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Collection;

class MentorService
{
    private const PASSWORD_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';

    public function all(): Collection
    {
        return AdminUser::where('role', 'spv_mentor')
            ->with('division')
            ->withCount([
                'mentoredInterns' => fn ($q) => $q->whereIn('status', ['active', 'extended']),
            ])
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): array
    {
        $password = $this->generatePassword();

        $mentor = AdminUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'role' => 'spv_mentor',
            'division_id' => $data['division_id'] ?? null,
            'is_active' => true,
        ]);

        ActivityLogger::log('mentor.created', $mentor, $mentor->name);

        return ['mentor' => $mentor, 'generated_password' => $password];
    }

    public function update(AdminUser $mentor, array $data): AdminUser
    {
        $old = $mentor->name;
        $mentor->update($data);
        $mentor = $mentor->fresh();

        ActivityLogger::log('mentor.updated', $mentor, $mentor->name, [
            'old_name' => $old,
            'new_name' => $mentor->name,
        ]);

        return $mentor;
    }

    public function delete(AdminUser $mentor): void
    {
        $activeCount = $mentor->mentoredInterns()
            ->whereIn('status', ['active', 'extended'])
            ->count();

        if ($activeCount > 0) {
            throw new DomainActionException(
                "Mentor \"{$mentor->name}\" tidak bisa dihapus karena masih membimbing {$activeCount} intern aktif/extended.",
                400
            );
        }

        $name = $mentor->name;
        $historicalCount = $mentor->mentoredInterns()
            ->whereIn('status', ['completed', 'failed'])
            ->count();

        $mentor->delete();

        ActivityLogger::log('mentor.deleted', $mentor, $name, [
            'historical_interns' => $historicalCount,
        ]);
    }

    private function generatePassword(): string
    {
        $chars = '';
        for ($i = 0; $i < 10; $i++) {
            $chars .= self::PASSWORD_CHARS[random_int(0, strlen(self::PASSWORD_CHARS) - 1)];
        }

        return $chars;
    }
}
