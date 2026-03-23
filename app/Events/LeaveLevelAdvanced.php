<?php

namespace App\Events;

use App\Models\Leave;
use Illuminate\Broadcasting\Channel;
use App\Enums\Roles;
use App\Models\Division;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveLevelAdvanced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $leave, $divisionId, $newLevel; // 'manager' jika naik ke manager

    public function __construct(Leave $leave, int|null $divisionId , string $newLevel)
    {
        $this->leave = $leave->load('employee');
        $this->divisionId = $divisionId;
        $this->newLevel = $newLevel;
    }
    public function broadcastOn()
    {
        $employee = $this->leave->employee;
        $isLeader = $employee && Division::where('leader_id', $employee->id)->exists();
        $isApprover = $employee && $employee->roles()->where('name', Roles::Approver->value)->exists();

        if ($isLeader || $isApprover) {
            return new PrivateChannel('manager.approval');
        }

        $divId = $employee->division_id ?? $this->divisionId;
        return new PrivateChannel("approver.division.{$divId}");
    }
    public function broadcastAs()
    {
        return 'leave.level-advanced';
    }

    public function broadcastWith(): array
    {
        $d1 = \Carbon\Carbon::parse($this->leave->date_start);
        $d2 = \Carbon\Carbon::parse($this->leave->date_end);

        return [
            'leave' => $this->leave,
            'newLevel' => $this->newLevel,
        ];
    }

}
