<?php

namespace App\Events;

use App\Models\Leave;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveSubmitted implements ShouldBroadcast {
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $leave, $divisionId;

    public function __construct(Leave $leave, int $divisionId) {
        $this->leave = $leave->load('employee');
        $this->divisionId = $divisionId; // supaya channel spesifik divisi
    }
    public function broadcastOn() {
        return new PrivateChannel("approver.division.{$this->divisionId}");
    }
    public function broadcastAs() { return 'leave.submitted'; }

    public function broadcastWith()
    {
        return [
            'leave' => $this->leave,
            'detail_url' => route('approver.leaves.show', $this->leave),
        ];
    }

}
