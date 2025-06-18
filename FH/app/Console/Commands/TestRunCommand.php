<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestRunCommand extends Command
{
    protected $signature = 'test:run';
    protected $description = 'A test command to run every minute';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        \Log::info('Test command is running every minute.');
        // Your test logic here
    }
}
