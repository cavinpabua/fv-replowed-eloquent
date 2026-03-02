<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupDeletedWorldObjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'world:cleanup-deleted {--days=7 : Number of days after soft delete to hard delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard delete world_objects that have been soft-deleted (deleted=1) for more than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        // Hard delete objects where deleted=1 and updated_at is older than cutoff
        $deletedCount = DB::table('world_objects')
            ->where('deleted', 1)
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        $this->info("Hard deleted {$deletedCount} world objects that were soft-deleted more than {$days} days ago.");

        return 0;
    }
}
