<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\TimeUpdated;

class SendTimeUpdate extends Command
{
    protected $signature = 'time:update';
    protected $description = 'Broadcast the current time';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $currentTime = now()->format('H:i'); 
        broadcast(new TimeUpdated($currentTime));
    }
}

?>