<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Fmt;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Journal d'audit — directeur uniquement (protégé par le groupe de routes role:directeur).
 * Lecture seule : aucune modification ni suppression (RG11 / dictionnaire : entrées immuables).
 */
class AuditController extends Controller
{
    private const PER_PAGE = 30;
    private const PDF_MAX_ROWS = 1000;

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $logs = $this->query($filters)->paginate(self::PER_PAGE)->withQueryString();

        return view('admin.audit.index', [
            'logs'        => $logs,
            'filters'     => $filters,
            'users'       => User::orderBy('name')->get(['id', 'name']),
            'actionTypes' => AuditLog::distinct()->orderBy('action_type')->pluck('action_type'),
            'entityTypes' => AuditLog::distinct()->orderBy('entity_type')->pluck('entity_type'),
            'hasFilters'  => collect($filters)->filter()->isNotEmpty(),
        ]);
    }

    public function pdf(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->query($filters);

        if ($query->count() > self::PDF_MAX_ROWS) {
            return back()->with('error', 'Trop d\'entrées pour un PDF (max ' . self::PDF_MAX_ROWS . '). Affinez les filtres, par exemple en réduisant la période.');
        }

        $logs = $query->get();

        $parts = [];
        if ($filters['from'] || $filters['to']) {
            $parts[] = 'Du ' . ($filters['from'] ? Carbon::parse($filters['from'])->format('d/m/Y') : 'début')
                     . ' au ' . ($filters['to'] ? Carbon::parse($filters['to'])->format('d/m/Y') : 'aujourd\'hui');
        } else {
            $parts[] = 'Toute la période';
        }
        if ($filters['user_id']) {
            $parts[] = optional(User::find($filters['user_id']))->name;
        }
        if ($filters['action_type']) {
            $parts[] = AuditLog::actionLabel($filters['action_type']);
        }
        if ($filters['entity_type']) {
            $parts[] = AuditLog::entityLabelOf($filters['entity_type']);
        }
        if ($filters['q']) {
            $parts[] = '« ' . $filters['q'] . ' »';
        }

        return Pdf::loadView('admin.audit.pdf', [
            'logs'        => $logs,
            'subtitle'    => implode(' · ', array_filter($parts)),
            'logo'        => Fmt::logo(),
            'generatedBy' => auth()->user()->name,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream('journal-audit-' . now()->toDateString() . '.pdf');
    }

    // ------------------------------------------------------------------

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'action_type' => ['nullable', 'string', 'max:60'],
            'entity_type' => ['nullable', 'string', 'max:40'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date', 'after_or_equal:from'],
            'q'           => ['nullable', 'string', 'max:100'],
        ], [
            'to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        return [
            'user_id'     => $data['user_id'] ?? null,
            'action_type' => $data['action_type'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'from'        => $data['from'] ?? null,
            'to'          => $data['to'] ?? null,
            'q'           => isset($data['q']) ? trim($data['q']) : null,
        ];
    }

    private function query(array $f)
    {
        return AuditLog::with('user:id,name,username')
            ->when($f['user_id'], fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['action_type'], fn ($q, $v) => $q->where('action_type', $v))
            ->when($f['entity_type'], fn ($q, $v) => $q->where('entity_type', $v))
            ->when($f['from'], fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($f['to'], fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->when($f['q'], function ($q, $v) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $v) . '%';
                $q->where(fn ($w) => $w->where('entity_label', 'like', $like)->orWhere('details', 'like', $like));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
