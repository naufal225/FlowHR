<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReportExport extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const MODULE_REIMBURSEMENT = 'reimbursement';
    public const MODULE_OVERTIME = 'overtime';
    public const MODULE_OFFICIAL_TRAVEL = 'official_travel';

    public const EXPORT_TYPE_SUMMARY = 'summary';
    public const EXPORT_TYPE_EVIDENCE = 'evidence';

    public const FORMAT_PDF = 'pdf';
    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_ZIP = 'zip';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'requested_by',
        'role_scope',
        'module',
        'export_type',
        'format',
        'filters_json',
        'status',
        'progress_percent',
        'processed_items',
        'total_items',
        'result_disk',
        'result_path',
        'file_name',
        'error_message',
        'started_at',
        'finished_at',
        'queued_at',
        'worker_app',
        'attempts',
        'last_heartbeat_at',
        'artifact_size_bytes',
    ];

    protected $casts = [
        'filters_json' => 'array',
        'progress_percent' => 'integer',
        'processed_items' => 'integer',
        'total_items' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'queued_at' => 'datetime',
        'attempts' => 'integer',
        'last_heartbeat_at' => 'datetime',
        'artifact_size_bytes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $export): void {
            if (! $export->getKey()) {
                $export->setAttribute($export->getKeyName(), (string) Str::uuid());
            }
        });
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getFiltersAttribute(): array
    {
        return (array) ($this->filters_json ?? []);
    }

    public function setFiltersAttribute(array $filters): void
    {
        $this->attributes['filters_json'] = json_encode($filters);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
