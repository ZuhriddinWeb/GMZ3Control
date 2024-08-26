<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimeUpdated
{
    use Dispatchable, SerializesModels;

    public $currentTime;

    public function __construct($currentTime)
    {
        $this->currentTime = $currentTime;
    }

    public function broadcastOn()
    {
        return new Channel('time-channel');
    }
}
