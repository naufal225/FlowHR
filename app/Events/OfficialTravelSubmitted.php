<?php

namespace App\Events;

use App\Models\OfficialTravel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfficialTravelSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $officialTravel, $divisionId;

    /**
     * Create a new event instance.
     */
    public function __construct(OfficialTravel $officialTravel, int $divisionId)
    {
        $this->officialTravel = $officialTravel->load('employee');
        $this->divisionId = $divisionId; // supaya channel spesifik divisi
    }

    public function broadcastOn() {
        return new PrivateChannel("approver.division.{$this->divisionId}");
    }
    public function broadcastAs() { return 'official-travel.submitted'; }

    public function broadcastWith()
    {
        return [
            'official-travel' => $this->officialTravel,
            'detail_url' => route('approver.official-travels.show', $this->officialTravel),
        ];
    }
}
