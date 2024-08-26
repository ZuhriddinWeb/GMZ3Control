<?php

namespace App\Observers;

use App\Models\GraphicTimes;
use App\Events\TimeUpdated;
class GraphicTimesObserver
{
    /**
     * Handle the GraphicTimes "created" event.
     */
    public function created(GraphicTimes $graphicTimes): void
    {
        //
    }

    /**
     * Handle the GraphicTimes "updated" event.
     */
    public function updated(GraphicTimes $graphicTimes)
    {
        $currentTime = now()->format('H:i');
        broadcast(new TimeUpdated($currentTime));
    }

    /**
     * Handle the GraphicTimes "deleted" event.
     */
    public function deleted(GraphicTimes $graphicTimes): void
    {
        //
    }

    /**
     * Handle the GraphicTimes "restored" event.
     */
    public function restored(GraphicTimes $graphicTimes): void
    {
        //
    }

    /**
     * Handle the GraphicTimes "force deleted" event.
     */
    public function forceDeleted(GraphicTimes $graphicTimes): void
    {
        //
    }
}
