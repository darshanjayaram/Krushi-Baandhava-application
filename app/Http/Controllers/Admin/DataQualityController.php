<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\MarketPriceRaw;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataQualityController extends Controller
{
    /**
     * Display the data quality inspection screen for raw market feeds.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'rejected');
        $dataSourceId = $request->query('data_source_id');
        $search = $request->query('search');

        $query = MarketPriceRaw::with('dataSource')
            ->orderByDesc('received_at');

        if ($status && $status !== 'all') {
            $query->where('processing_status', $status);
        }

        if ($dataSourceId) {
            $query->where('data_source_id', $dataSourceId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('error_message', 'like', "%{$search}%")
                    ->orWhere('checksum', 'like', "%{$search}%");
            });
        }

        $records = $query->paginate(20)->withQueryString();

        $stats = [
            'rejected' => MarketPriceRaw::where('processing_status', 'rejected')->count(),
            'pending' => MarketPriceRaw::where('processing_status', 'pending')->count(),
            'duplicate' => MarketPriceRaw::where('processing_status', 'duplicate')->count(),
            'processed' => MarketPriceRaw::where('processing_status', 'processed')->count(),
        ];

        $dataSources = DataSource::orderBy('name')->get();

        return view('admin.data_quality.index', compact('records', 'stats', 'dataSources', 'status', 'dataSourceId', 'search'));
    }

    /**
     * Reprocess a single raw market price record.
     */
    public function reprocess(MarketPriceRaw $rawRecord, MarketPriceIngestionService $service): RedirectResponse
    {
        $result = $service->reprocessRawRecord($rawRecord);

        AuditLog::log(
            'raw_record.reprocess',
            'MarketPriceRaw',
            $rawRecord->id,
            ['old_status' => 'rejected'],
            ['result' => $result]
        );

        if ($result['success']) {
            return redirect()->back()->with('success', "Record #{$rawRecord->id} reprocessed successfully! Canonical price created for {$result['crop']} at {$result['market']}.");
        }

        return redirect()->back()->with('error', "Could not reprocess record #{$rawRecord->id}: " . ($result['error'] ?? 'Unknown error. Check mappings.'));
    }

    /**
     * Batch reprocess all rejected records.
     */
    public function reprocessAll(Request $request, MarketPriceIngestionService $service): RedirectResponse
    {
        $dataSourceId = $request->filled('data_source_id') ? (int) $request->data_source_id : null;
        $reasonKeyword = $request->filled('reason_keyword') ? trim($request->reason_keyword) : null;

        $results = $service->reprocessBatch($dataSourceId, $reasonKeyword);

        AuditLog::log(
            'raw_record.reprocess_batch',
            'MarketPriceRaw',
            null,
            null,
            $results
        );

        $msg = "Batch reprocessed {$results['total']} records: {$results['processed']} successfully normalized, {$results['still_rejected']} still rejected.";

        if ($results['processed'] > 0) {
            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back()->with('warning', $msg . ' Please verify commodity and mandi alias mappings.');
    }

    /**
     * Delete / dismiss a corrupted raw record.
     */
    public function destroy(MarketPriceRaw $rawRecord): RedirectResponse
    {
        $id = $rawRecord->id;
        $payloadSummary = $rawRecord->payload;

        $rawRecord->delete();

        AuditLog::log(
            'raw_record.delete',
            'MarketPriceRaw',
            $id,
            ['payload' => $payloadSummary],
            null
        );

        return redirect()->back()->with('success', "Raw record #{$id} dismissed and deleted.");
    }
}
