<?php

namespace App\Http\Controllers\Web;

use App\Models\ActivityLog;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query()->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->query('actor_id'));
        }

        $logs = $query->paginate(20)->withQueryString();

        $actors = AdminUser::query()
            ->whereIn('id', ActivityLog::query()->distinct()->pluck('actor_id')->filter())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('activity-logs.index', [
            'logs' => $logs,
            'actors' => $actors,
            'actions' => self::ACTION_LABELS,
            'hasFilters' => $request->filled('date_from') || $request->filled('date_to') || $request->filled('action') || $request->filled('actor_id'),
            'totalLogs' => ActivityLog::query()->count(),
        ]);
    }

    public const ACTION_LABELS = [
        'intern.approved' => 'Menyetujui pendaftaran',
        'intern.rejected' => 'Menolak pendaftaran',
        'intern.bulk_deleted' => 'Menghapus intern (permanen)',
        'intern.password_reset' => 'Mereset password intern',
        'intern.extended' => 'Memperpanjang masa magang',
        'intern.marked_failed' => 'Menandai intern gagal',
        'intern.marked_completed' => 'Menandai intern selesai',
        'division.created' => 'Membuat divisi',
        'division.updated' => 'Mengubah divisi',
        'division.deleted' => 'Menghapus divisi',
        'mentor.created' => 'Membuat mentor',
        'mentor.updated' => 'Mengubah mentor',
        'mentor.deleted' => 'Menghapus mentor',
        'profile.updated' => 'Mengubah profil',
        'profile.password_changed' => 'Mengubah password',
    ];
}
