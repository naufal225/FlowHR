<?php

namespace App\Events;

use App\Models\Overtime;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OvertimeSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $overtime, $divisionId;

    /**
     * Create a new event instance.
     */
    public function __construct(Overtime $overtime, int $divisionId)
    {
        $this->overtime = $overtime->load('employee');
        $this->divisionId = $divisionId; // supaya channel spesifik divisi
    }

    public function broadcastOn()
    {
        return new PrivateChannel("approver.division.{$this->divisionId}");
    }
    public function broadcastAs()
    {
        return 'overtime.submitted';
    }

    public function broadcastWith()
    {
        return [
            'overtime' => $this->overtime,
            'detail_url' => route('approver.overtimes.show', $this->overtime),
        ];
    }

}
