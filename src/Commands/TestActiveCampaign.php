<?php

declare(strict_types=1);

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use KeyAgency\KaiPersonalize\Services\ActiveCampaignService;

class TestActiveCampaign extends Command
{
    protected $signature = 'kai:test-activecampaign
                          {--email= : Email address to lookup}
                          {--test-cookie : Test with a simulated cookie value}';

    protected $description = 'Test ActiveCampaign API connection and data retrieval';

    public function handle(): int
    {
        $service = app(ActiveCampaignService::class);

        $this->info('ActiveCampaign API Test');
        $this->line('========================');
        $this->line('');

        // Check if enabled
        if (! $service->isEnabled()) {
            $this->error('ActiveCampaign is not configured or enabled!');
            $this->line('');
            $this->line('Please set the following environment variables:');
            $this->line('  KAI_ACTIVECAMPAIGN_ENABLED=true');
            $this->line('  KAI_ACTIVECAMPAIGN_URL=https://your-account.api-us1.com');
            $this->line('  KAI_ACTIVECAMPAIGN_API_KEY=your_api_key');
            $this->line('');

            return self::FAILURE;
        }

        // Test connection
        $this->info('Testing API connection...');

        $result = $service->test();

        if (! $result['success']) {
            $this->error('Connection failed!');
            $this->line('  '.$result['message']);

            return self::FAILURE;
        }

        $this->line('  <info>✔</info> Connection successful!');
        $this->line('');

        // Test email lookup if provided
        if ($email = $this->option('email')) {
            $this->info("Looking up contact: {$email}");
            $this->line('');

            $contact = $service->getContactByEmail($email);

            if (! $contact) {
                $this->warn('No contact found with this email address.');
                $this->line('');
                $this->line('This could mean:');
                $this->line('  - The contact does not exist in ActiveCampaign');
                $this->line('  - The API credentials do not have permission to view contacts');

                return self::FAILURE;
            }

            $this->displayContact($contact);

            return self::SUCCESS;
        }

        // Test cookie simulation if requested
        if ($this->option('test-cookie')) {
            $this->info('Testing cookie-based contact retrieval...');
            $this->line('');

            // Set a test cookie
            $testEmail = $this->ask('Enter an email address to simulate in cookie:');

            if (! $testEmail) {
                $this->warn('No email provided. Skipping cookie test.');

                return self::FAILURE;
            }

            $service->setTestCookie($testEmail);

            $contact = $service->getContactFromCookie();

            if (! $contact) {
                $this->warn('No contact found with this email address.');
                $this->line('');
                $this->line('This could mean:');
                $this->line('  - The contact does not exist in ActiveCampaign');
                $this->line('  - The API credentials do not have permission to view contacts');

                return self::FAILURE;
            }

            $this->displayContact($contact);

            return self::SUCCESS;
        }

        // Show configuration
        $this->info('Configuration:');
        $this->table(['Setting', 'Value'], [
            ['API URL', config('kai-personalize.activecampaign.api_url')],
            ['Cookie Name', config('kai-personalize.activecampaign.cookie_name', 'vgo_ee')],
            ['Cache TTL', config('kai-personalize.activecampaign.cache_ttl', 1440).' minutes'],
            ['Feature Enabled', config('kai-personalize.features.activecampaign', false) ? 'Yes' : 'No'],
        ]);

        $this->line('');
        $this->line('To test email lookup, use:');
        $this->line('  php artisan kai:test-activecampaign --email=user@example.com');
        $this->line('');
        $this->line('To test cookie retrieval, use:');
        $this->line('  php artisan kai:test-activecampaign --test-cookie');

        return self::SUCCESS;
    }

    protected function displayContact(array $contact): void
    {
        $this->info('Contact Data:');

        $rows = [];

        // Basic fields
        $fields = [
            'ac_contact_id' => 'Contact ID',
            'ac_email' => 'Email',
            'ac_first_name' => 'First Name',
            'ac_last_name' => 'Last Name',
            'ac_phone' => 'Phone',
            'ac_created_at' => 'Created',
            'ac_updated_at' => 'Updated',
        ];

        foreach ($fields as $key => $label) {
            if (isset($contact[$key]) && $contact[$key] !== null) {
                $rows[] = [$label, $contact[$key]];
            }
        }

        if (! empty($rows)) {
            $this->table(['Field', 'Value'], $rows);
        }

        // Tags
        if (! empty($contact['ac_tags'])) {
            $this->line('');
            $this->info('Tags: '.implode(', ', $contact['ac_tags']));
        }

        // Lists
        if (! empty($contact['ac_lists'])) {
            $this->line('');
            $this->info('Lists:');

            $listRows = [];
            foreach ($contact['ac_lists'] as $name => $data) {
                $status = match ($data['status']) {
                    1 => 'Subscribed',
                    2 => 'Unsubscribed',
                    default => 'Unknown',
                };
                $listRows[] = [$name, $status];
            }

            $this->table(['List', 'Status'], $listRows);
        }

        // Custom fields
        if (! empty($contact['ac_custom_fields'])) {
            $this->line('');
            $this->info('Custom Fields:');

            $fieldRows = [];
            foreach ($contact['ac_custom_fields'] as $name => $value) {
                if ($value !== null) {
                    $fieldRows[] = [$name, $value];
                }
            }

            if (! empty($fieldRows)) {
                $this->table(['Field', 'Value'], $fieldRows);
            }
        }

        $this->line('');
        $this->line('Data stored as visitor attributes (type: crm).');
        $this->line('Available in templates via: {{ kai:visitor }}');
    }
}
