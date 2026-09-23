<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiHealthLog;
use App\Models\DataSource;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyncLogController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'sync'); // 'sync' or 'health'
        $sourceId = $request->query('source_id');
        $status = $request->query('status');

        $dataSources = DataSource::orderBy('name')->get();

        $syncLogsQuery = SyncLog::with('dataSource')->latest('started_at');
        if ($sourceId) {
            $syncLogsQuery->where('data_source_id', $sourceId);
        }
        if ($status) {
            $syncLogsQuery->where('status', $status);
        }
        $syncLogs = $syncLogsQuery->paginate(15, ['*'], 'sync_page');

        $healthLogsQuery = ApiHealthLog::with('dataSource')->latest('created_at');
        if ($sourceId) {
            $healthLogsQuery->where('data_source_id', $sourceId);
        }
        if ($status) {
            $healthLogsQuery->where('status', $status);
        }
        $healthLogs = $healthLogsQuery->paginate(15, ['*'], 'health_page');

        return view('admin.datasources.logs', compact('syncLogs', 'healthLogs', 'dataSources', 'tab', 'sourceId', 'status'));
    }
}
