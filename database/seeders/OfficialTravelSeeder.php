<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficialTravelSeeder extends Seeder
{
    /**
     * Trend target:
     * - Flat / datar per bulan (jumlah request setara).
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('official_travels')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::query()
            ->select(['id', 'name', 'division_id'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('Seeder official travel dilewati: tidak ada user aktif.');

            return;
        }

        $akbarId = User::query()->where('name', 'Akbar')->value('id');
        $cholidId = User::query()->where('name', 'Cholid')->value('id');
        $fallbackApproverId = $akbarId ?? $cholidId ?? (int) $users->first()->id;

        $monthConfigs = [
            '2026-02' => [
                'start' => CarbonImmutable::parse('2026-02-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-02-28 23:59:59', 'Asia/Jakarta'),
                'approved_pct' => 70,
                'pending_pct' => 18,
            ],
            '2026-03' => [
                'start' => CarbonImmutable::parse('2026-03-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-03-31 23:59:59', 'Asia/Jakarta'),
                'approved_pct' => 68,
                'pending_pct' => 20,
            ],
            '2026-04' => [
                'start' => CarbonImmutable::parse('2026-04-01 00:00:00', 'Asia/Jakarta'),
                'end' => CarbonImmutable::parse('2026-04-08 23:59:59', 'Asia/Jakarta'),
                'approved_pct' => 57,
                'pending_pct' => 28,
            ],
        ];

        $monthlyTarget = max(8, (int) round($users->count() * 0.45));
        $customers = [
            'PT Nusantara Solusi Prima',
            'PT Bumi Sani Teknologi',
            'PT Mitra Integrasi Digital',
            'PT Arunika Data Services',
            'PT Cakrawala Sukses Indonesia',
        ];

        $rows = [];
        foreach ($monthConfigs as $monthKey => $monthConfig) {
            $selectedUsers = $this->pickUsersForMonth($users, $monthlyTarget, $monthKey);

            foreach ($selectedUsers as $index => $user) {
                [$approver1Id, $approver2Id] = $this->resolveApprovers(
                    user: $user,
                    fallbackApproverId: $fallbackApproverId,
                    secondFallbackApproverId: $cholidId ?? $fallbackApproverId,
                );

                $dateStart = $this->pickWeekdayInRange(
                    start: $monthConfig['start'],
                    end: $monthConfig['end'],
                    key: "otrav-date-{$monthKey}-{$user->id}-{$index}",
                );

                $durationDays = $this->seededInt("otrav-dur-{$monthKey}-{$user->id}-{$index}", 1, 3);
                $dateEnd = $dateStart->addDays($durationDays - 1);
                if ($dateEnd->gt($monthConfig['end']->startOfDay())) {
                    $dateEnd = $monthConfig['end']->startOfDay();
                }

                $createdAt = $this->buildCreatedAt(
                    monthStart: $monthConfig['start'],
                    referenceAt: $dateStart->setTime(9, 30),
                    key: "otrav-created-{$monthKey}-{$user->id}-{$index}",
                );

                $tripDays = max(1, $dateStart->diffInDays($dateEnd) + 1);
                $dailyBase = $this->seededInt("otrav-daily-{$monthKey}-{$user->id}-{$index}", 220000, 420000);
                $total = $tripDays * $dailyBase;

                $statusPayload = $this->buildStatusPayload(
                    approvedPercentage: $monthConfig['approved_pct'],
                    pendingPercentage: $monthConfig['pending_pct'],
                    createdAt: $createdAt,
                    key: "otrav-status-{$monthKey}-{$user->id}-{$index}",
                );

                $rows[] = array_merge([
                    'employee_id' => $user->id,
                    'approver_1_id' => $approver1Id,
                    'approver_2_id' => $approver2Id,
                    'date_start' => $dateStart->toDateString(),
                    'date_end' => $dateEnd->toDateString(),
                    'total' => $total,
                    'customer' => $customers[$this->seededInt("otrav-customer-{$monthKey}-{$user->id}-{$index}", 0, count($customers) - 1)],
                    'locked_by' => null,
                    'locked_at' => null,
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $createdAt->addHours($this->seededInt("otrav-upd-{$monthKey}-{$user->id}-{$index}", 1, 36))->format('Y-m-d H:i:s'),
                ], $statusPayload);
            }
        }

        DB::table('official_travels')->insert($rows);
        $this->command->info(sprintf(
            'Official travel showcase data seeded: %d rows (%d per bulan, datar).',
            count($rows),
            $monthlyTarget
        ));
    }

    private function pickUsersForMonth(Collection $users, int $target, string $monthKey): Collection
    {
        return $users
            ->sortBy(fn (User $user) => $this->seededInt("otrav-select-{$monthKey}-{$user->id}", 1, 100000))
            ->take($target)
            ->values();
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
        int $approvedPercentage,
        int $pendingPercentage,
        CarbonImmutable $createdAt,
        string $key,
    ): array {
        $roll = $this->seededInt($key . '-roll', 1, 100);
        $approvedLimit = $approvedPercentage;
        $pendingLimit = $approvedPercentage + $pendingPercentage;

        if ($roll <= $approvedLimit) {
            $approvedDate = $createdAt->addHours($this->seededInt($key . '-approved-h', 6, 72));

            return [
                'status_1' => 'approved',
                'status_2' => 'approved',
                'note_1' => 'Disetujui team lead.',
                'note_2' => 'Disetujui manager.',
                'seen_by_approver_at' => $approvedDate->subHours(4)->format('Y-m-d H:i:s'),
                'seen_by_manager_at' => $approvedDate->subHour()->format('Y-m-d H:i:s'),
                'approved_date' => $approvedDate->format('Y-m-d H:i:s'),
                'rejected_date' => null,
                'marked_down' => $this->seededBool($key . '-mark', 26),
            ];
        }

        if ($roll <= $pendingLimit) {
            $stageTwoPending = $this->seededBool($key . '-stage2-pending', 44);
            if ($stageTwoPending) {
                return [
                    'status_1' => 'approved',
                    'status_2' => 'pending',
                    'note_1' => 'Sudah disetujui level 1.',
                    'note_2' => null,
                    'seen_by_approver_at' => $createdAt->addHours($this->seededInt($key . '-seen-a', 1, 16))->format('Y-m-d H:i:s'),
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

        $rejectedDate = $createdAt->addHours($this->seededInt($key . '-rejected-h', 3, 42));
        $rejectedAtLevelTwo = $this->seededBool($key . '-reject-l2', 46);

        return [
            'status_1' => $rejectedAtLevelTwo ? 'approved' : 'rejected',
            'status_2' => $rejectedAtLevelTwo ? 'rejected' : 'pending',
            'note_1' => $rejectedAtLevelTwo ? 'Approved di level 1.' : 'Agenda perjalanan belum prioritas.',
            'note_2' => $rejectedAtLevelTwo ? 'Ditolak manager: jadwal bentrok.' : null,
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
            ->subDays($this->seededInt($key . '-days', 1, 5))
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

