<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_real_counts_and_logs()
    {
        // Setup mock data
        Contact::create(['name' => 'Budi', 'phone_number' => '6281234567890']);
        Contact::create(['name' => 'Ani', 'phone_number' => '6281234567891']);

        $campaign = Campaign::create([
            'name' => 'Promo Diskon',
            'message_template' => 'Diskon 50%!',
            'status' => 'running',
            'schedule_type' => 'once',
        ]);

        MessageLog::create([
            'campaign_id' => $campaign->id,
            'contact_id' => 1,
            'message_body' => 'Diskon 50%!',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Verify counts are shown
        $response->assertSee('2'); // Total Contacts
        $response->assertSee('Promo Diskon'); // Recent activity campaign name
    }
}
