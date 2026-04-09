<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveSeeder extends Seeder
{
    /**
     * Trend target:
     * - Feb 2026 > Mar 2026 > Apr 2026
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('leaves')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::query()
            ->select(['id', 'name', 'division_id'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('Seeder leave dilewati: tidak ada user aktif.');

            return;
        }

        $akbarId = User::query()->where('name', 'Akbar')->value('id');
        $cholidId = User::query()->where('name', 'Cholid')->value('id');
        $fallbackApproverId = $akbarId ?? $cholidId ?? (int) $users->first()->id;

        $monthConfigs = [
            '2026-02' => [
                'start' => CarbonImmutable::parse('2026-02-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-02-28 23:59:59', 'Asia/Jakarta'),
                'approved_pct' => 74,
                'pending_pct' => 16,
                'count_strategy' => 'high',
            ],
            '2026-03' => [
                'start' => CarbonImmutable::parse('2026-03-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-03-31 23:59:59', 'Asia/Jakarta'),
                'approved_pct' => 66,
                'pending_pct' => 18,
                'count_strategy' => 'medium',
            ],
            '2026-04' => [
                'start' => CarbonImmutable::parse('2026-04-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-04-08 23:59:59', 'Asia/Jakarta'),
                'approved_pct' => 58,
                'pending_pct' => 22,
                'count_strategy' => 'low',
            ],
        ];

        $reasons = [
            'Keperluan keluarga inti',
            'Kontrol kesehatan berkala',
            'Urusan administrasi pribadi',
            'Kondisi badan kurang fit',
            'Pendampingan keluarga',
            'Izin urusan rumah tangga',
        ];

        $rows = [];
        foreach ($users as $user) {
            $approver1Id = $this->resolveApprover(
                user: $user,
                fallbackApproverId: $fallbackApproverId,
                secondFallbackApproverId: $cholidId ?? $fallbackApproverId,
            );

            foreach ($monthConfigs as $monthKey => $monthConfig) {
                $count = $this->resolveMonthlyCount(
                    strategy: $monthConfig['count_strategy'],
                    key: "leave-count-{$user->id}-{$monthKey}",
                );

                for ($i = 0; $i < $count; $i++) {
                    $dateStart = $this->pickWeekdayInRange(
                        start: $monthConfig['start'],
                        end: $monthConfig['end'],
                        key: "leave-date-{$user->id}-{$monthKey}-{$i}",
                    );

                    $durationDays = $this->seededInt("leave-dur-{$user->id}-{$monthKey}-{$i}", 1, 3);
                    $dateEnd = $dateStart->addDays($durationDays - 1);
                    if ($dateEnd->gt($monthConfig['end']->startOfDay())) {
                        $dateEnd = $monthConfig['end']->startOfDay();
                    }

                    $createdAt = $this->buildCreatedAt(
                        monthStart: $monthConfig['start'],
                        referenceAt: $dateStart->setTime(9, 0),
                        key: "leave-created-{$user->id}-{$monthKey}-{$i}",
                    );

                    $statusPayload = $this->buildStatusPayload(
                        approvedPercentage: $monthConfig['approved_pct'],
                        pendingPercentage: $monthConfig['pending_pct'],
                        createdAt: $createdAt,
                        key: "leave-status-{$user->id}-{$monthKey}-{$i}",
                    );

                    $rows[] = array_merge([
                        'employee_id' => $user->id,
                        'approver_1_id' => $approver1Id,
                        'date_start' => $dateStart->toDateString(),
                        'date_end' => $dateEnd->toDateString(),
                        'reason' => $reasons[$this->seededInt("leave-reason-{$user->id}-{$monthKey}-{$i}", 0, count($reasons) - 1)],
                        'created_at' => $createdAt->format('Y-m-d H:i:s'),
                        'updated_at' => $createdAt->addHours($this->seededInt("leave-upd-{$user->id}-{$monthKey}-{$i}", 1, 24))->format('Y-m-d H:i:s'),
                    ], $statusPayload);
                }
            }
        }

        DB::table('leaves')->insert($rows);
        $this->command->info(sprintf('Leave showcase data seeded: %d rows (tren menurun).', count($rows)));
    }

    private function resolveMonthlyCount(string $strategy, string $key): int
    {
        return match ($strategy) {
            'high' => $this->seededInt($key, 1, 2),
            'medium' => $this->seededBool($key, 56) ? 1 : 0,
            'low' => $this->seededBool($key, 28) ? 1 : 0,
            default => 0,
        };
    }

    private function resolveApprover(User $user, int $fallbackApproverId, int $secondFallbackApproverId): int
    {
        $leaderId = null;
        if ($user->division_id !== null) {
            $leaderId = Division::query()->where('id', $user->division_id)->value('leader_id');
        }

        $approver1Id = $leaderId ?: $fallbackApproverId;
        if ($approver1Id === $user->id) {
            $approver1Id = $fallbackApproverId !== $user->id ? $fallbackApproverId : $secondFallbackApproverId;
        }

        return (int) $approver1Id;
    }

    private function buildStatusPayload(
        int $approvedPercentage,
        int $pendingPercentage,
        CarbonImmutable $createdAt,
        string $key,
    ): array {
        $roll = $this->seededInt($key . '-roll', 1, 100);
        $approvedLimit = $approvedPercentage;
        $pendingLimit = $approvedPercentage + $pendingPercentage;

        if ($roll <= $approvedLimit) {
            $approvedDate = $createdAt->addHours($this->seededInt($key . '-approved-h', 3, 54));

            return [
                'status_1' => 'approved',
                'note_1' => 'Disetujui atasan.',
                'note_2' => null,
                'seen_by_approver_at' => $approvedDate->subHours(2)->format('Y-m-d H:i:s'),
                'seen_by_manager_at' => null,
                'approved_date' => $approvedDate->format('Y-m-d H:i:s'),
                'rejected_date' => null,
            ];
        }

        if ($roll <= $pendingLimit) {
            return [
                'status_1' => 'pending',
                'note_1' => null,
                'note_2' => null,
                'seen_by_approver_at' => null,
                'seen_by_manager_at' => null,
                'approved_date' => null,
                'rejected_date' => null,
            ];
        }

        $rejectedDate = $createdAt->addHours($this->seededInt($key . '-rejected-h', 2, 36));

        return [
            'status_1' => 'rejected',
            'note_1' => 'Ditolak: kebutuhan operasional belum memungkinkan.',
            'note_2' => null,
            'seen_by_approver_at' => $createdAt->addHours($this->seededInt($key . '-seen-rej-a', 1, 10))->format('Y-m-d H:i:s'),
            'seen_by_manager_at' => null,
            'approved_date' => null,
            'rejected_date' => $rejectedDate->format('Y-m-d H:i:s'),
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
            ->subDays($this->seededInt($key . '-days', 1, 7))
            ->setTime($this->seededInt($key . '-hour', 8, 16), $this->seededInt($key . '-min', 0, 59));

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

