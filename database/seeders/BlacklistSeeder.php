<?php

namespace KeyAgency\KaiPersonalize\Database\Seeders;

use Illuminate\Database\Seeder;
use KeyAgency\KaiPersonalize\Models\Blacklist;

class BlacklistSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // SEO/Marketing bots
            ['type' => 'bot_name', 'pattern' => 'semrush', 'description' => 'Semrush SEO bot'],
            ['type' => 'bot_name', 'pattern' => 'ahrefsbot', 'description' => 'Ahrefs SEO bot'],
            ['type' => 'bot_name', 'pattern' => 'mj12bot', 'description' => 'Majestic SEO bot'],
            ['type' => 'bot_name', 'pattern' => 'dotbot', 'description' => 'Moz SEO bot'],
            ['type' => 'bot_name', 'pattern' => 'blerp', 'description' => 'SEO crawler'],

            // Monitoring/Uptime
            ['type' => 'bot_name', 'pattern' => 'uptimerobot', 'description' => 'UptimeRobot monitoring'],
            ['type' => 'bot_name', 'pattern' => 'pingdom', 'description' => 'Pingdom monitoring'],
            ['type' => 'bot_name', 'pattern' => 'statuscake', 'description' => 'StatusCake monitoring'],
            ['type' => 'bot_name', 'pattern' => 'uptrends', 'description' => 'Uptrends monitoring'],
            ['type' => 'bot_name', 'pattern' => 'site24x7', 'description' => 'Site24x7 monitoring'],

            // AI scrapers
            ['type' => 'bot_name', 'pattern' => 'chatgpt', 'description' => 'ChatGPT bot'],
            ['type' => 'bot_name', 'pattern' => 'gptbot', 'description' => 'GPTBot'],
            ['type' => 'bot_name', 'pattern' => 'ccbot', 'description' => 'CommonCrawl bot'],
            ['type' => 'bot_name', 'pattern' => 'anthropic', 'description' => 'Anthropic AI'],
            ['type' => 'bot_name', 'pattern' => 'claude', 'description' => 'Claude AI'],

            // Archive/Research
            ['type' => 'bot_name', 'pattern' => 'archive.org', 'description' => 'Internet Archive'],
            ['type' => 'bot_name', 'pattern' => 'archivebot', 'description' => 'ArchiveBot'],

            // User agent patterns
            ['type' => 'user_agent', 'pattern' => 'scrapy', 'description' => 'Scrapy framework'],
            ['type' => 'user_agent', 'pattern' => 'curl', 'description' => 'cURL requests'],
            ['type' => 'user_agent', 'pattern' => 'wget', 'description' => 'Wget requests'],
            ['type' => 'user_agent', 'pattern' => 'python-requests', 'description' => 'Python requests'],
            ['type' => 'user_agent', 'pattern' => 'go-http-client', 'description' => 'Go HTTP client'],
        ];

        foreach ($items as $item) {
            Blacklist::firstOrCreate(
                ['type' => $item['type'], 'pattern' => $item['pattern']],
                [
                    'description' => $item['description'],
                    'is_active' => false,
                ]
            );
        }
    }
}
