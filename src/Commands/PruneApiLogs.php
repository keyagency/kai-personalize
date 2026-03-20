<?php

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use KeyAgency\KaiPersonalize\Models\ApiLog;

class PruneApiLogs extends Command
{
    protected $signature = 'kai:prune-logs
                          {--days=30 : Number of days to keep logs}';

    protected $description = 'Prune old API logs';

    public function handle()
    {
        $days = $this->option('days');

        $this->info("Pruning API logs older than {$days} days...");

        $deleted = ApiLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} old API log entries.");

        return 0;
    }
}
