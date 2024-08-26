<?php
namespace App\Listeners;

use App\Events\TimeUpdated;

class SomeWebSocketListener
{
    public function handle($event)
    {
        $currentTime = now()->format('H:i');
        broadcast(new TimeUpdated($currentTime));
    }
}

