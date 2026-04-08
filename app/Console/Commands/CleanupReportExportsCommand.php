<?php

namespace App\Console\Commands;

use App\Models\ReportExport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupReportExportsCommand extends Command
{
    protected $signature = 'report-exports:cleanup {--days=7 : Retention in days for completed/failed exports}';

    protected $description = 'Cleanup old generated report export files and records';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $exports = ReportExport::query()
            ->whereIn('status', [ReportExport::STATUS_COMPLETED, ReportExport::STATUS_FAILED])
            ->where(function ($query) use ($cutoff): void {
                $query->where('finished_at', '<', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff): void {
                        $inner->whereNull('finished_at')
                            ->where('updated_at', '<', $cutoff);
                    });
            })
            ->get();

        $deletedCount = 0;
        foreach ($exports as $export) {
            if ($export->result_disk && $export->result_path) {
                Storage::disk($export->result_disk)->delete($export->result_path);
            }

            $export->delete();
            $deletedCount++;
        }

        $this->info('Cleaned up ' . $deletedCount . ' report export records older than ' . $days . ' days.');

        return self::SUCCESS;
    }
}

