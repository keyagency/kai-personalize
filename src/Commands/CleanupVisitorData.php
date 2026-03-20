<?php

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use KeyAgency\KaiPersonalize\Models\Log;
use KeyAgency\KaiPersonalize\Models\PageView;
use KeyAgency\KaiPersonalize\Models\Visitor;
use KeyAgency\KaiPersonalize\Models\VisitorSession;

class CleanupVisitorData extends Command
{
    protected $signature = 'kai:cleanup
                          {--days= : Number of days to keep data (overrides config)}
                          {--all : Clean up ALL data without date restriction}
                          {--force : Force cleanup without confirmation}';

    protected $description = 'Clean up old visitor tracking data based on retention settings, or all data with --all';

    public function handle()
    {
        $all = $this->option('all');
        $visitorDataDays = $this->option('days') ?? config('kai-personalize.retention.visitor_data_days', 365);
        $sessionDataDays = config('kai-personalize.retention.session_data_days', 90);
        $logDataDays = config('kai-personalize.retention.log_data_days', 30);

        if (! $this->option('force')) {
            $message = $all
                ? 'This will delete ALL visitor tracking data without any date restriction. Continue?'
                : "This will delete visitor data older than {$visitorDataDays} days. Continue?";

            if (! $this->confirm($message)) {
                $this->info('Cleanup cancelled.');

                return 0;
            }
        }

        $this->info('Starting cleanup...');

        // Clean up logs
        if ($all) {
            $logsDeleted = Log::query()->delete();
            $this->info("Deleted {$logsDeleted} log entries (all).");
        } else {
            $logsDeleted = Log::where('created_at', '<', now()->subDays($logDataDays))->delete();
            $this->info("Deleted {$logsDeleted} old log entries.");
        }

        // Clean up page views
        if ($all) {
            $pageViewsDeleted = PageView::query()->delete();
            $this->info("Deleted {$pageViewsDeleted} page views (all).");
        } else {
            $pageViewDays = config('kai-personalize.retention.page_view_data_days', 90);
            $pageViewsDeleted = PageView::where('viewed_at', '<', now()->subDays($pageViewDays))->delete();
            $this->info("Deleted {$pageViewsDeleted} old page views.");
        }

        // Clean up sessions
        if ($all) {
            $sessionsDeleted = VisitorSession::query()->delete();
            $this->info("Deleted {$sessionsDeleted} sessions (all).");
        } else {
            $sessionsDeleted = VisitorSession::where('created_at', '<', now()->subDays($sessionDataDays))->delete();
            $this->info("Deleted {$sessionsDeleted} old sessions.");
        }

        // Clean up visitors
        if ($all) {
            $visitorsDeleted = Visitor::query()->delete();
            $this->info("Deleted {$visitorsDeleted} visitors (all).");
        } else {
            $visitorsDeleted = Visitor::where('last_visit_at', '<', now()->subDays($visitorDataDays))->delete();
            $this->info("Deleted {$visitorsDeleted} old visitors.");
        }

        $this->info('Cleanup completed successfully!');

        return 0;
    }
}
