<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\ReimbursementType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReimbursementSeeder extends Seeder
{
    /**
     * Trend target:
     * - Feb 2026 < Mar 2026 < Apr 2026
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('reimbursements')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::query()
            ->select(['id', 'name', 'division_id'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('Seeder reimbursement dilewati: tidak ada user aktif.');

            return;
        }

        $typeIds = ReimbursementType::query()->orderBy('id')->pluck('id')->all();
        if ($typeIds === []) {
            $this->command->warn('Seeder reimbursement dilewati: reimbursement type belum tersedia.');

            return;
        }

        $invoiceSource = public_path('contoh-invoice/contoh-invoice.jpeg');
        if (! is_file($invoiceSource)) {
            $this->command->warn('Seeder reimbursement dilewati: file invoice contoh tidak ditemukan di public/contoh-invoice/contoh-invoice.jpeg.');

            return;
        }

        Storage::disk('public')->deleteDirectory('reimbursement_invoices/showcase');
        Storage::disk('public')->makeDirectory('reimbursement_invoices/showcase');

        $akbarId = User::query()->where('name', 'Akbar')->value('id');
        $cholidId = User::query()->where('name', 'Cholid')->value('id');
        $fallbackApproverId = $akbarId ?? $cholidId ?? (int) $users->first()->id;

        $monthConfigs = [
            '2026-02' => [
                'start' => CarbonImmutable::parse('2026-02-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-02-28 23:59:59', 'Asia/Jakarta'),
                'count_min' => 1,
                'count_max' => 2,
                'approved_pct' => 72,
                'pending_pct' => 18,
                'amount_min' => 90000,
                'amount_max' => 350000,
            ],
            '2026-03' => [
                'start' => CarbonImmutable::parse('2026-03-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-03-31 23:59:59', 'Asia/Jakarta'),
                'count_min' => 2,
                'count_max' => 3,
                'approved_pct' => 66,
                'pending_pct' => 21,
                'amount_min' => 120000,
                'amount_max' => 520000,
            ],
            '2026-04' => [
                'start' => CarbonImmutable::parse('2026-04-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-04-08 23:59:59', 'Asia/Jakarta'),
                'count_min' => 3,
                'count_max' => 4,
                'approved_pct' => 52,
                'pending_pct' => 30,
                'amount_min' => 180000,
                'amount_max' => 800000,
            ],
        ];

        $customers = [
            'PT Nusantara Solusi Prima',
            'PT Bumi Sani Teknologi',
            'PT Mitra Integrasi Digital',
            'PT Arunika Data Services',
            'PT Cakrawala Sukses Indonesia',
        ];

        $rows = [];
        foreach ($users as $user) {
            [$approver1Id, $approver2Id] = $this->resolveApprovers(
                user: $user,
                fallbackApproverId: $fallbackApproverId,
                secondFallbackApproverId: $cholidId ?? $fallbackApproverId,
            );

            foreach ($monthConfigs as $monthKey => $monthConfig) {
                $count = $this->seededInt(
                    key: "rb-count-{$user->id}-{$monthKey}",
                    min: $monthConfig['count_min'],
                    max: $monthConfig['count_max'],
                );

                for ($i = 0; $i < $count; $i++) {
                    $requestDate = $this->pickDayInRange(
                        start: $monthConfig['start'],
                        end: $monthConfig['end'],
                        key: "rb-date-{$user->id}-{$monthKey}-{$i}",
                    );

                    $createdAt = $this->buildCreatedAt(
                        monthStart: $monthConfig['start'],
                        referenceAt: $requestDate->setTime(10, 0),
                        key: "rb-created-{$user->id}-{$monthKey}-{$i}",
                    );

                    $invoicePath = $this->storeInvoiceCopy(
                        invoiceSource: $invoiceSource,
                        monthKey: $monthKey,
                        userId: (int) $user->id,
                        sequence: $i,
                    );

                    $statusPayload = $this->buildStatusPayload(
                        monthKey: $monthKey,
                        approvedPercentage: $monthConfig['approved_pct'],
                        pendingPercentage: $monthConfig['pending_pct'],
                        createdAt: $createdAt,
                        key: "rb-status-{$user->id}-{$monthKey}-{$i}",
                    );

                    $rows[] = array_merge([
                        'employee_id' => $user->id,
                        'approver_1_id' => $approver1Id,
                        'approver_2_id' => $approver2Id,
                        'date' => $requestDate->toDateString(),
                        'total' => $this->seededInt(
                            key: "rb-total-{$user->id}-{$monthKey}-{$i}",
                            min: $monthConfig['amount_min'],
                            max: $monthConfig['amount_max'],
                        ),
                        'invoice_path' => $invoicePath,
                        'reimbursement_type_id' => $typeIds[$this->seededInt("rb-type-{$user->id}-{$monthKey}-{$i}", 0, count($typeIds) - 1)],
                        'customer' => $customers[$this->seededInt("rb-customer-{$user->id}-{$monthKey}-{$i}", 0, count($customers) - 1)],
                        'locked_by' => null,
                        'locked_at' => null,
                        'created_at' => $createdAt->format('Y-m-d H:i:s'),
                        'updated_at' => $createdAt->addHours($this->seededInt("rb-upd-{$user->id}-{$monthKey}-{$i}", 1, 36))->format('Y-m-d H:i:s'),
                    ], $statusPayload);
                }
            }
        }

        DB::table('reimbursements')->insert($rows);
        $this->command->info(sprintf('Reimbursement showcase data seeded: %d rows + invoice files.', count($rows)));
    }

    private function storeInvoiceCopy(string $invoiceSource, string $monthKey, int $userId, int $sequence): string
    {
        $relativePath = sprintf(
            'reimbursement_invoices/showcase/%s/u%03d-%02d-%s.jpeg',
            $monthKey,
            $userId,
            $sequence + 1,
            substr(sha1("inv-{$monthKey}-{$userId}-{$sequence}"), 0, 8)
        );

        Storage::disk('public')->put($relativePath, file_get_contents($invoiceSource));

        return $relativePath;
    }

    private function resolveApprovers(User $user, int $fallbackApproverId, int $secondFallbackApproverId): array
    {
        $leaderId = null;
        if ($user->division_id !== null) {
            $leaderId = Division::query()->where('id', $user->division_id)->value('leader_id');
        }

        $approver1Id = $leaderId ?: $fallbackApproverId;
        if ($approver1Id === $user->id) {
            $approver1Id = $fallbackApproverId !== $user->id ? $fallbackApproverId : $secondFallbackApproverId;
        }

        $approver2Id = $fallbackApproverId !== $approver1Id ? $fallbackApproverId : $secondFallbackApproverId;
        if ($approver2Id === $user->id) {
            $approver2Id = $secondFallbackApproverId !== $user->id ? $secondFallbackApproverId : $approver1Id;
        }

        return [(int) $approver1Id, (int) $approver2Id];
    }

    private function buildStatusPayload(
        string $monthKey,
        int $approvedPercentage,
        int $pendingPercentage,
        CarbonImmutable $createdAt,
        string $key,
    ): array {
        $roll = $this->seededInt($key . '-roll', 1, 100);
        $approvedLimit = $approvedPercentage;
        $pendingLimit = $approvedPercentage + $pendingPercentage;

        if ($roll <= $approvedLimit) {
            $approvedDate = $createdAt->addHours($this->seededInt($key . '-approved-h', 5, 72));

            return [
                'status_1' => 'approved',
                'status_2' => 'approved',
                'note_1' => 'Bukti valid, disetujui approver.',
                'note_2' => 'Disetujui manager.',
                'seen_by_approver_at' => $approvedDate->subHours(3)->format('Y-m-d H:i:s'),
                'seen_by_manager_at' => $approvedDate->subHour()->format('Y-m-d H:i:s'),
                'approved_date' => $approvedDate->format('Y-m-d H:i:s'),
                'rejected_date' => null,
                'marked_down' => $this->seededBool($key . "-mark-{$monthKey}", $monthKey !== '2026-04' ? 38 : 12),
            ];
        }

        if ($roll <= $pendingLimit) {
            $stageTwoPending = $this->seededBool($key . '-stage2-pending', 45);
            if ($stageTwoPending) {
                return [
                    'status_1' => 'approved',
                    'status_2' => 'pending',
                    'note_1' => 'Sudah diverifikasi level 1.',
                    'note_2' => null,
                    'seen_by_approver_at' => $createdAt->addHours($this->seededInt($key . '-seen-a', 1, 18))->format('Y-m-d H:i:s'),
                    'seen_by_manager_at' => null,
                    'approved_date' => null,
                    'rejected_date' => null,
                    'marked_down' => false,
                ];
            }

            return [
                'status_1' => 'pending',
                'status_2' => 'pending',
                'note_1' => null,
                'note_2' => null,
                'seen_by_approver_at' => null,
                'seen_by_manager_at' => null,
                'approved_date' => null,
                'rejected_date' => null,
                'marked_down' => false,
            ];
        }

        $rejectedDate = $createdAt->addHours($this->seededInt($key . '-rejected-h', 3, 30));
        $rejectedAtLevelTwo = $this->seededBool($key . '-reject-l2', 42);

        return [
            'status_1' => $rejectedAtLevelTwo ? 'approved' : 'rejected',
            'status_2' => $rejectedAtLevelTwo ? 'rejected' : 'pending',
            'note_1' => $rejectedAtLevelTwo ? 'Approved level 1.' : 'Nominal tidak sesuai kebijakan.',
            'note_2' => $rejectedAtLevelTwo ? 'Ditolak manager: dokumen kurang lengkap.' : null,
            'seen_by_approver_at' => $createdAt->addHours($this->seededInt($key . '-seen-rej-a', 1, 12))->format('Y-m-d H:i:s'),
            'seen_by_manager_at' => $rejectedAtLevelTwo ? $rejectedDate->subHour()->format('Y-m-d H:i:s') : null,
            'approved_date' => null,
            'rejected_date' => $rejectedDate->format('Y-m-d H:i:s'),
            'marked_down' => false,
        ];
    }

    private function pickDayInRange(CarbonImmutable $start, CarbonImmutable $end, string $key): CarbonImmutable
    {
        $days = [];
        for ($cursor = $start->startOfDay(); $cursor->lte($end); $cursor = $cursor->addDay()) {
            $days[] = $cursor;
        }

        if ($days === []) {
            return $start->startOfDay();
        }

        return $days[$this->seededInt($key, 0, count($days) - 1)];
    }

    private function buildCreatedAt(CarbonImmutable $monthStart, CarbonImmutable $referenceAt, string $key): CarbonImmutable
    {
        $createdAt = $referenceAt
            ->subDays($this->seededInt($key . '-days', 0, 4))
            ->setTime($this->seededInt($key . '-hour', 8, 17), $this->seededInt($key . '-min', 0, 59));

        if ($createdAt->lt($monthStart)) {
            $createdAt = $monthStart->setTime(8, 30);
        }

        return $createdAt;
    }

    private function seededInt(string $key, int $min, int $max): int
    {
        $hash = sprintf('%u', crc32('flowhr-showcase|' . $key));

        return $min + ((int) $hash % (($max - $min) + 1));
    }

    private function seededBool(string $key, int $percentageTrue): bool
    {
        return $this->seededInt($key, 1, 100) <= $percentageTrue;
    }
}

