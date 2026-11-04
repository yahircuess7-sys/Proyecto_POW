<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Bitácora de auditoría protegida de solo lectura e inmutable.
     * Accesible exclusivamente por el Owner del negocio (restringido a su empresa) o Super Admin.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view-audit-logs');

        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        $query = AuditLog::with(['user', 'business']);

        // Aislamiento: El Super Admin puede consultar globalmente si marca 'global=1'; los Owners siempre ven solo su empresa
        if (! $actor->isSuperAdmin() || ! $request->boolean('global')) {
            $query->where('business_id', $businessId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $users = User::where('status', 'active')
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereHas('businesses', fn ($b) => $b->where('businesses.id', $businessId)))
            ->orderBy('name')
            ->get();

        return view('admin.audit', compact('logs', 'users'));
    }

    /**
     * Exportación de la bitácora de auditoría protegida a CSV para cumplimiento legal y normativo.
     */
    public function exportCsv(Request $request): Response
    {
        Gate::authorize('view-audit-logs');

        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        $query = AuditLog::with(['user', 'business']);

        if (! $actor->isSuperAdmin() || ! $request->boolean('global')) {
            $query->where('business_id', $businessId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->orderByDesc('created_at')->take(1000)->get();

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fwrite($handle, "sep=;\r\n");

        fputcsv($handle, [
            'ID Evento',
            'Fecha y Hora (UTC/Local)',
            'Negocio (Tenant)',
            'Usuario Responsable',
            'Email',
            'Acción Registrada',
            'Descripción Detallada',
            'Dirección IP',
        ], ';', '"');

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->created_at->format('Y-m-d H:i:s'),
                $this->escapeCsvValue($log->business->name ?? 'Global / Sistema'),
                $this->escapeCsvValue($log->user->name ?? 'Sistema Automático'),
                $this->escapeCsvValue($log->user->email ?? '---'),
                $this->escapeCsvValue($log->action),
                $this->escapeCsvValue($log->description),
                $log->ip_address ?? '127.0.0.1',
            ], ';', '"');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="bitacora_auditoria_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    private function escapeCsvValue(?string $value): string
    {
        $value = trim($value ?? '');
        $value = str_replace('"', '""', $value);
        $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);
        return $value;
    }
}
