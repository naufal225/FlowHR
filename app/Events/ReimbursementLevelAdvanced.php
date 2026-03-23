<?php

namespace App\Events;

use App\Models\Reimbursement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReimbursementLevelAdvanced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $reimbursement, $divisionId, $newLevel;

    /**
     * Create a new event instance.
     */
    public function __construct(Reimbursement $reimbursement, int $divisionId, string $newLevel)
    {
        $this->reimbursement = $reimbursement->load('employee');
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
        return 'reimbursement.level-advanced';
    }

    public function broadcastWith(): array
    {
        $d1 = \Carbon\Carbon::parse($this->reimbursement->date_start);
        $d2 = \Carbon\Carbon::parse($this->reimbursement->date_end);

        return [
            'reimbursement' => [
                'reimbursement' => $this->reimbursement,
                'created_at_fmt' => optional($this->reimbursement->created_at)->format('M d, Y'),
            ],
            'newLevel' => $this->newLevel,
        ];
    }
}
