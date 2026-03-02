<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupOldChatMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete chat messages older than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $deletedCount = ChatMessage::where('created_at', '<', $sevenDaysAgo)->delete();

        $this->info("Deleted {$deletedCount} chat messages older than 7 days.");

        return 0;
    }
}
