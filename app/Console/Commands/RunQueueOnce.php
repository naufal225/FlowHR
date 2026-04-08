<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunQueueOnce extends Command
{
    protected $signature = 'queue:once';
    protected $description = 'Jalankan satu job queue sekali';

    public function handle()
    {
        $this->call('queue:work', [
            '--queue' => 'reports,default',
            '--once' => true, // hanya sekali jalan
            '--stop-when-empty' => true,
        ]);

        return Command::SUCCESS;
    }

}
