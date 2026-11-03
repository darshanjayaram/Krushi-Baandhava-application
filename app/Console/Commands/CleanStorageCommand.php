<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clean {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up oversized logs, compiled views, and expired cache to free up cPanel disk quota';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting Krushi Baandhava Storage & Disk Quota Cleanup...');

        $bytesFreed = 0;

        // 1. Truncate storage/logs/laravel.log
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            $size = File::size($logPath);
            File::put($logPath, '');
            $bytesFreed += $size;
            $this->line("  ✓ Truncated storage/logs/laravel.log (Freed " . $this->formatBytes($size) . ")");
        }

        // 2. Delete old daily log files older than 3 days
        $logFiles = File::glob(storage_path('logs/laravel-*.log'));
        foreach ($logFiles as $file) {
            if (File::lastModified($file) < strtotime('-3 days')) {
                $size = File::size($file);
                File::delete($file);
                $bytesFreed += $size;
                $this->line("  ✓ Deleted old log: " . basename($file) . " (Freed " . $this->formatBytes($size) . ")");
            }
        }

        // 3. Clear compiled views in storage/framework/views
        $viewFiles = File::glob(storage_path('framework/views/*.php'));
        $viewsFreed = 0;
        foreach ($viewFiles as $file) {
            $viewsFreed += File::size($file);
            File::delete($file);
        }
        $bytesFreed += $viewsFreed;
        $this->line("  ✓ Cleared " . count($viewFiles) . " compiled views (Freed " . $this->formatBytes($viewsFreed) . ")");

        // 4. Precompile critical error views so Blade never needs to write to disk when an error occurs
        try {
            $this->callSilently('view:cache');
            $this->line("  ✓ Pre-compiled Blade view cache for zero runtime compilation I/O");
        } catch (\Throwable $e) {
            $this->warn("  ! Could not pre-compile views: " . $e->getMessage());
        }

        $this->newLine();
        $this->info("✅ Cleanup Complete! Total Disk Space Recovered: " . $this->formatBytes($bytesFreed));

        return 0;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
