<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Models\AdminUser;
use App\Models\Certificate;
use App\Models\Evaluation;
use App\Models\Intern;

/**
 * Create/update for intern evaluations, including the admin-vs-mentor edit
 * permission matrix. Extracted out of Api\EvaluationController so the web
 * "Penilaian" page enforces identical rules (who may edit, when a
 * certificate locks it, when needs_certificate_regeneration gets flagged).
 */
class EvaluationWorkflowService
{
    public function __construct(private readonly EvaluationScoringService $scoringService) {}

    public function create(Intern $intern, AdminUser $admin, array $data): Evaluation
    {
        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            throw new DomainActionException('Anda bukan mentor dari intern ini.', 403);
        }

        if (Evaluation::where('intern_id', $intern->id)->exists()) {
            throw new DomainActionException('Evaluasi untuk intern ini sudah ada, gunakan endpoint update.', 400);
        }

        $totalScore = $this->scoringService->calculateTotalScore(
            (float) $data['discipline_score'],
            (float) $data['performance_score'],
            (float) $data['attitude_score'],
            (float) $data['communication_score'],
        );

        return Evaluation::create([
            'intern_id' => $intern->id,
            'evaluated_by' => $admin->id,
            'discipline_score' => $data['discipline_score'],
            'performance_score' => $data['performance_score'],
            'attitude_score' => $data['attitude_score'],
            'communication_score' => $data['communication_score'],
            'total_score' => $totalScore,
            'grade' => $this->scoringService->calculateGrade($totalScore),
            'comments' => $data['comments'] ?? null,
        ]);
    }

    /**
     * Aturan izin edit (lihat README untuk penjelasan lengkap):
     * - admin_magang: selalu boleh edit. edited_by_admin=true, last_edited_by=auth id.
     *   Kalau intern sudah punya certificate, tandai needs_certificate_regeneration=true
     *   (certificate lama TIDAK dihapus di sini — itu tanggung jawab fase certificate).
     * - spv_mentor yang merupakan evaluated_by (penginput pertama) DAN belum ada
     *   certificate: boleh edit bebas, edited_by_admin tetap false.
     * - spv_mentor yang BUKAN evaluated_by: 403.
     * - siapa pun selain admin_magang, kalau certificate sudah terbit: 403 dengan
     *   pesan bahwa sertifikat perlu digenerate ulang.
     */
    public function update(Evaluation $evaluation, AdminUser $admin, array $data): Evaluation
    {
        $hasCertificate = Certificate::where('intern_id', $evaluation->intern_id)->exists();
        $isAdmin = $admin->role === 'admin_magang';
        $isOriginalMentor = $admin->role === 'spv_mentor' && $evaluation->evaluated_by === $admin->id;

        if ($admin->role === 'spv_mentor' && ! $isOriginalMentor) {
            throw new DomainActionException('Anda bukan mentor yang menginput evaluasi ini.', 403);
        }

        if (! $isAdmin && $hasCertificate) {
            throw new DomainActionException(
                'Sertifikat sudah terbit, hanya admin yang bisa mengubah nilai. Sertifikat perlu digenerate ulang setelah ini.',
                403
            );
        }

        $scores = [
            'discipline_score' => $data['discipline_score'] ?? $evaluation->discipline_score,
            'performance_score' => $data['performance_score'] ?? $evaluation->performance_score,
            'attitude_score' => $data['attitude_score'] ?? $evaluation->attitude_score,
            'communication_score' => $data['communication_score'] ?? $evaluation->communication_score,
        ];

        $totalScore = $this->scoringService->calculateTotalScore(
            (float) $scores['discipline_score'],
            (float) $scores['performance_score'],
            (float) $scores['attitude_score'],
            (float) $scores['communication_score'],
        );

        $attributes = array_merge($scores, [
            'total_score' => $totalScore,
            'grade' => $this->scoringService->calculateGrade($totalScore),
        ]);

        if (array_key_exists('comments', $data)) {
            $attributes['comments'] = $data['comments'];
        }

        if ($isAdmin) {
            $attributes['edited_by_admin'] = true;
            $attributes['last_edited_by'] = $admin->id;

            if ($hasCertificate) {
                $attributes['needs_certificate_regeneration'] = true;
            }
        }

        $evaluation->update($attributes);

        return $evaluation->fresh();
    }
}
