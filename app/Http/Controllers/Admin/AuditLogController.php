<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a paginated audit trail of all administrative events.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();

        $distinctActions = AuditLog::distinct('action')->pluck('action')->filter()->values();
        $distinctEntities = AuditLog::distinct('entity_type')->pluck('entity_type')->filter()->values();
        $admins = User::whereIn('role', ['super_admin', 'data_admin', 'forecast_admin', 'content_admin'])->get();

        return view('admin.audit_logs.index', compact(
            'logs',
            'distinctActions',
            'distinctEntities',
            'admins'
        ));
    }

    /**
     * Return JSON details for an audit log record.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user');

        return response()->json([
            'id' => $auditLog->id,
            'action' => $auditLog->action,
            'user' => $auditLog->user?->name ?? 'System',
            'ip_address' => $auditLog->ip_address,
            'user_agent' => $auditLog->user_agent,
            'entity_type' => $auditLog->entity_type,
            'entity_id' => $auditLog->entity_id,
            'old_values' => $auditLog->old_values,
            'new_values' => $auditLog->new_values,
            'created_at' => $auditLog->created_at->format('Y-m-d H:i:s'),
            'time_ago' => $auditLog->created_at->diffForHumans(),
        ]);
    }
}
