<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OvertimeSeeder extends Seeder
{
    /**
     * Trend target:
     * - Feb 2026 < Mar 2026 < Apr 2026
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('overtimes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::query()
            ->select(['id', 'name', 'division_id'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('Seeder overtime dilewati: tidak ada user aktif.');

            return;
        }

        $akbarId = User::query()->where('name', 'Akbar')->value('id');
        $cholidId = User::query()->where('name', 'Cholid')->value('id');
        $fallbackApproverId = $akbarId ?? $cholidId ?? (int) $users->first()->id;

        $monthConfigs = [
            '2026-02' => [
                'start' => CarbonImmutable::parse('2026-02-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-02-28 23:59:59', 'Asia/Jakarta'),
                'count_min' => 1,
                'count_max' => 2,
                'approved_pct' => 74,
                'pending_pct' => 17,
            ],
            '2026-03' => [
                'start' => CarbonImmutable::parse('2026-03-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-03-31 23:59:59', 'Asia/Jakarta'),
                'count_min' => 2,
                'count_max' => 3,
                'approved_pct' => 67,
                'pending_pct' => 20,
            ],
            '2026-04' => [
                'start' => CarbonImmutable::parse('2026-04-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-04-08 23:59:59', 'Asia/Jakarta'),
                'count_min' => 3,
                'count_max' => 4,
                'approved_pct' => 52,
                'pending_pct' => 30,
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
                    key: "ot-count-{$user->id}-{$monthKey}",
                    min: $monthConfig['count_min'],
                    max: $monthConfig['count_max'],
                );

                for ($i = 0; $i < $count; $i++) {
                    $workDate = $this->pickWeekdayInRange(
                        start: $monthConfig['start'],
                        end: $monthConfig['end'],
                        key: "ot-date-{$user->id}-{$monthKey}-{$i}",
                    );

                    $startHour = $this->seededInt("ot-start-hour-{$user->id}-{$monthKey}-{$i}", 17, 19);
                    $startMinute = [0, 15, 30, 45][$this->seededInt("ot-start-min-{$user->id}-{$monthKey}-{$i}", 0, 3)];
                    $durationMinutes = $this->seededInt("ot-dur-{$user->id}-{$monthKey}-{$i}", 90, 240);
                    $dateStart = $workDate->setTime($startHour, $startMinute);
                    $dateEnd = $dateStart->addMinutes($durationMinutes);

                    $createdAt = $this->buildCreatedAt(
                        monthStart: $monthConfig['start'],
                        referenceAt: $dateStart,
                        key: "ot-created-{$user->id}-{$monthKey}-{$i}",
                    );

                    $statusPayload = $this->buildStatusPayload(
                        monthKey: $monthKey,
                        approvedPercentage: $monthConfig['approved_pct'],
                        pendingPercentage: $monthConfig['pending_pct'],
                        createdAt: $createdAt,
                        key: "ot-status-{$user->id}-{$monthKey}-{$i}",
                    );

                    $rows[] = array_merge([
                        'employee_id' => $user->id,
                        'approver_1_id' => $approver1Id,
                        'approver_2_id' => $approver2Id,
                        'date_start' => $dateStart->format('Y-m-d H:i:s'),
                        'date_end' => $dateEnd->format('Y-m-d H:i:s'),
                        'total' => round($durationMinutes / 60, 2),
                        'customer' => $customers[$this->seededInt("ot-customer-{$user->id}-{$monthKey}-{$i}", 0, count($customers) - 1)],
                        'locked_by' => null,
                        'locked_at' => null,
                        'created_at' => $createdAt->format('Y-m-d H:i:s'),
                        'updated_at' => $createdAt->addHours($this->seededInt("ot-upd-{$user->id}-{$monthKey}-{$i}", 1, 48))->format('Y-m-d H:i:s'),
                    ], $statusPayload);
                }
            }
        }

        DB::table('overtimes')->insert($rows);
        $this->command->info(sprintf('Overtime showcase data seeded: %d rows.', count($rows)));
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
            $approvedDate = $createdAt->addHours($this->seededInt($key . '-approved-h', 4, 60));

            return [
                'status_1' => 'approved',
                'status_2' => 'approved',
                'note_1' => 'Disetujui team lead.',
                'note_2' => 'Disetujui manager.',
                'seen_by_approver_at' => $approvedDate->subHours(2)->format('Y-m-d H:i:s'),
                'seen_by_manager_at' => $approvedDate->subHour()->format('Y-m-d H:i:s'),
                'approved_date' => $approvedDate->format('Y-m-d H:i:s'),
                'rejected_date' => null,
                'marked_down' => $this->seededBool($key . "-mark-{$monthKey}", $monthKey !== '2026-04' ? 34 : 8),
            ];
        }

        if ($roll <= $pendingLimit) {
            $stageTwoPending = $this->seededBool($key . '-stage2-pending', 48);
            if ($stageTwoPending) {
                return [
                    'status_1' => 'approved',
                    'status_2' => 'pending',
                    'note_1' => 'Sudah diverifikasi team lead.',
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

        $rejectedDate = $createdAt->addHours($this->seededInt($key . '-rejected-h', 3, 36));
        $rejectedAtLevelTwo = $this->seededBool($key . '-reject-l2', 45);

        return [
            'status_1' => $rejectedAtLevelTwo ? 'approved' : 'rejected',
            'status_2' => $rejectedAtLevelTwo ? 'rejected' : 'pending',
            'note_1' => $rejectedAtLevelTwo ? 'Disetujui di level 1.' : 'Tidak sesuai urgensi lembur.',
            'note_2' => $rejectedAtLevelTwo ? 'Pengajuan ditolak setelah review manager.' : null,
            'seen_by_approver_at' => $createdAt->addHours($this->seededInt($key . '-seen-rej-a', 1, 12))->format('Y-m-d H:i:s'),
            'seen_by_manager_at' => $rejectedAtLevelTwo ? $rejectedDate->subHour()->format('Y-m-d H:i:s') : null,
            'approved_date' => null,
            'rejected_date' => $rejectedDate->format('Y-m-d H:i:s'),
            'marked_down' => false,
        ];
    }

    private function pickWeekdayInRange(CarbonImmutable $start, CarbonImmutable $end, string $key): CarbonImmutable
    {
        $days = [];
        for ($cursor = $start->startOfDay(); $cursor->lte($end); $cursor = $cursor->addDay()) {
            if (! $cursor->isWeekend()) {
                $days[] = $cursor;
            }
        }

        if ($days === []) {
            return $start->startOfDay();
        }

        return $days[$this->seededInt($key, 0, count($days) - 1)];
    }

    private function buildCreatedAt(CarbonImmutable $monthStart, CarbonImmutable $referenceAt, string $key): CarbonImmutable
    {
        $createdAt = $referenceAt
            ->subDays($this->seededInt($key . '-days', 0, 3))
            ->setTime($this->seededInt($key . '-hour', 8, 17), $this->seededInt($key . '-min', 0, 59));

        if ($createdAt->lt($monthStart)) {
            $createdAt = $monthStart->setTime(8, 0);
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

