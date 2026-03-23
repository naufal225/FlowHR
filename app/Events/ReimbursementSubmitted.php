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

class ReimbursementSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $reimbursement, $divisionId;

    /**
     * Create a new event instance.
     */
    public function __construct(Reimbursement $reimbursement, int $divisionId)
    {
        $this->reimbursement = $reimbursement->load('employee');
        $this->divisionId = $divisionId; // supaya channel spesifik divisi
    }

    public function broadcastOn()
    {
        return new PrivateChannel("approver.division.{$this->divisionId}");
    }
    public function broadcastAs()
    {
        return 'reimbursement.submitted';
    }

    public function broadcastWith()
    {
        return [
            'reimbursement' => $this->reimbursement,
            'detail_url' => route('approver.reimbursements.show', $this->reimbursement),
        ];
    }

}
