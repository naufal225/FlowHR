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

class OfficialTravelLevelAdvanced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $officialTravel, $divisionId, $newLevel;

    /**
     * Create a new event instance.
     */
    public function __construct(OfficialTravel $officialTravel, int $divisionId, string $newLevel)
    {
        $this->officialTravel = $officialTravel->load('employee');
        $this->divisionId = $divisionId;
        $this->newLevel = $newLevel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): PrivateChannel
    {
        return $this->newLevel === 'manager'
            ? new PrivateChannel('manager.approval')
            : new PrivateChannel("approver.division.{$this->divisionId}");
    }

    public function broadcastAs()
    {
        return 'official-travel.level-advanced';
    }

    public function broadcastWith(): array
    {
        $d1 = \Carbon\Carbon::parse($this->officialTravel->date_start);
        $d2 = \Carbon\Carbon::parse($this->officialTravel->date_end);

        return [
            'officialTravel' => $this->officialTravel,
            'newLevel' => $this->newLevel,
        ];
    }
}
