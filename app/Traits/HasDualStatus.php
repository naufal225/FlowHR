<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasDualStatus
{
    /**
     * Override di model jika nama kolom bukan 'status_1' & 'status_2'.
     * Contoh:
     *   protected array $finalStatusColumns = ['approval_a'];              // single
     *   protected array $finalStatusColumns = ['approval_a', 'approval_b']; // dual
     */
    protected function finalStatusColumns(): array
    {
        return ['status_1', 'status_2'];
    }


    /**
     * Kembalikan [col1, col2|null]. Jika hanya 1 kolom, col2 = null (single-mode).
     */
    protected function getFinalStatusColumns(): array
    {
        $cols = array_values($this->finalStatusColumns());
        $count = count($cols);

        if ($count === 0) {
            throw new \RuntimeException(static::class . ' must define at least one status column');
        }
        if ($count === 1) {
            return [$cols[0], null];
        }

        // Ambil 2 pertama saja bila lebih dari 2 diberikan.
        return [$cols[0], $cols[1]];
    }

    /**
     * Scope filter final status: approved/rejected/pending (single/dual aware).
     */
    public function scopeFilterFinalStatus(Builder $query, ?string $status): Builder
    {
        if (!$status)
            return $query;

        [$s1, $s2] = $this->getFinalStatusColumns();

        // SINGLE MODE
        if ($s2 === null) {
            return match ($status) {
                'approved' => $query->where($s1, 'approved'),
                'rejected' => $query->where($s1, 'rejected'),
                'pending' => $query->where($s1, 'pending'),
                default => $query,
            };
        }

        // DUAL MODE
        return match ($status) {
            'approved' => $query->where($s1, 'approved')->where($s2, 'approved'),

            'rejected' => $query->where(function ($q) use ($s1, $s2) {
                    $q->where($s1, 'rejected')->orWhere($s2, 'rejected');
                }),

            'pending' => $query->where(function ($q) use ($s1, $s2) {
                    // pending = (ada pending) && (tidak ada rejected)
                    $q->where(function ($qq) use ($s1, $s2) {
                        $qq->where($s1, 'pending')->orWhere($s2, 'pending');
                    })->where(function ($qq) use ($s1, $s2) {
                        $qq->where($s1, '!=', 'rejected')->where($s2, '!=', 'rejected');
                    });
                }),

            default => $query,
        };
    }

    /**
     * Scope agregasi count approved/rejected/pending sekali query (mutually exclusive).
     * Otomatis pilih formula SINGLE/DUAL.
     */
    public function scopeWithFinalStatusCount(Builder $query): Builder
    {
        [$s1, $s2] = $this->getFinalStatusColumns();
        $table = $this->getTable();

        // Pakai COALESCE agar NULL dianggap 'pending' (lebih konsisten di agregasi)
        $c1 = "COALESCE({$table}.{$s1}, 'pending')";

        if ($s2 === null) {
            // SINGLE MODE
            $sql = "
                COUNT(*) AS total,
                SUM(CASE WHEN {$c1} = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN {$c1} = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                SUM(CASE WHEN {$c1} NOT IN ('approved','rejected') THEN 1 ELSE 0 END) AS pending
            ";
            return $query->selectRaw($sql);
        }

        // DUAL MODE
        $c2 = "COALESCE({$table}.{$s2}, 'pending')";

        $sql = "
            COUNT(*) AS total,
            SUM(
                CASE WHEN {$c1} = 'approved' AND {$c2} = 'approved' THEN 1 ELSE 0 END
            ) AS approved,
            SUM(
                CASE WHEN {$c1} = 'rejected' OR {$c2} = 'rejected' THEN 1 ELSE 0 END
            ) AS rejected,
            SUM(
                CASE
                    WHEN {$c1} = 'rejected' OR {$c2} = 'rejected' THEN 0
                    WHEN {$c1} = 'approved' AND {$c2} = 'approved' THEN 0
                    ELSE 1
                END
            ) AS pending
        ";

        return $query->selectRaw($sql);
    }

    /** (Opsional) Scope umum yang sering dipakai */
    public function scopeForLeader(Builder $query, int $leaderId): Builder
    {
        return $query->whereHas('employee.division', fn($q) => $q->where('leader_id', $leaderId));
    }

    public function scopeDateRange(Builder $query, ?string $fromDate, ?string $toDate, string $column = 'date_start'): Builder
    {
        // Catatan: sesuaikan timezone penyimpanan di DB kamu (UTC vs lokal).
        if ($fromDate) {
            $query->where($column, '>=', \Carbon\Carbon::parse($fromDate, 'Asia/Jakarta')->startOfDay());
        }
        if ($toDate) {
            $query->where($column, '<=', \Carbon\Carbon::parse($toDate, 'Asia/Jakarta')->endOfDay());
        }
        return $query;
    }

    /**
     * Accessor final_status (single/dual aware).
     */
    public function getFinalStatusAttribute(): string
    {
        [$s1, $s2] = $this->getFinalStatusColumns();

        $v1 = $this->{$s1} ?? 'pending';
        $v2 = $s2 ? ($this->{$s2} ?? 'pending') : null;

        if ($v1 === 'rejected' || $v2 === 'rejected')
            return 'rejected';
        if ($v2 === null)
            return $v1; // single mode
        if ($v1 === 'approved' && $v2 === 'approved')
            return 'approved';
        return 'pending';
    }

    /**
     * Notifikasi/badge "belum dilihat" untuk Approver (tahap 1).
     * Gunakan kolom status pertama (tidak hard-coded).
     */
    public function scopeUnseenForApprover(Builder $q): Builder
    {
        [$s1, $_] = $this->getFinalStatusColumns();

        return $q->whereNull('seen_by_approver_at')
            ->where($s1, 'pending');
    }

    /**
     * Notifikasi/badge "belum dilihat" untuk Manager (tahap 2).
     * Jika single-mode (tanpa tahap 2), kembalikan query apa adanya setelah cek seen_by_manager_at.
     */
    public function scopeUnseenForManager(Builder $q): Builder
    {
        [$s1, $s2] = $this->getFinalStatusColumns();

        $q->whereNull('seen_by_manager_at');

        if ($s2 === null) {
            // Single mode: tidak ada tahap 2
            return $q;
        }

        return $q->where($s1, 'approved')   // sudah lolos approver
            ->where($s2, 'pending');   // menunggu manager
    }
}
