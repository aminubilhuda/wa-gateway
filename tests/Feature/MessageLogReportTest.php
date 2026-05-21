<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageLogReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_calculates_dynamic_statistics_correctly()
    {
        $campaign1 = Campaign::create([
            'name' => 'Campaign Alpha',
            'message_template' => 'Hello',
            'status' => 'completed',
            'target_type' => 'manual',
            'target_value' => '6281234567890',
        ]);

        $campaign2 = Campaign::create([
            'name' => 'Campaign Beta',
            'message_template' => 'Hi',
            'status' => 'completed',
            'target_type' => 'manual',
            'target_value' => '6281234567891',
        ]);

        $contact = Contact::create([
            'name' => 'John Doe',
            'phone_number' => '6281234567890',
        ]);

        // Create 3 logs: 2 sent (1 alpha, 1 beta), 1 failed (alpha)
        MessageLog::create([
            'campaign_id' => $campaign1->id,
            'contact_id' => $contact->id,
            'message_body' => 'Hello Alpha',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        MessageLog::create([
            'campaign_id' => $campaign1->id,
            'contact_id' => $contact->id,
            'message_body' => 'Hello Alpha Fail',
            'status' => 'failed',
            'sent_at' => now(),
        ]);

        MessageLog::create([
            'campaign_id' => $campaign2->id,
            'contact_id' => $contact->id,
            'message_body' => 'Hello Beta',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // 1. Visit Reports without filters
        $response = $this->get(route('reports'));
        $response->assertStatus(200);

        // Verify all statistics are calculated from all logs
        $response->assertViewHas('totalLogs', 3);
        $response->assertViewHas('successCount', 2);
        $response->assertViewHas('failedCount', 1);
        $response->assertViewHas('successRate', 66.7);
        $response->assertViewHas('failedRate', 33.3);

        // 2. Filter by Campaign Alpha
        $responseFiltered = $this->get(route('reports', ['campaign_id' => $campaign1->id]));
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertViewHas('totalLogs', 2);
        $responseFiltered->assertViewHas('successCount', 1);
        $responseFiltered->assertViewHas('failedCount', 1);
        $responseFiltered->assertViewHas('successRate', 50.0);
        $responseFiltered->assertViewHas('failedRate', 50.0);

        // 3. Filter by Status "failed"
        $responseStatus = $this->get(route('reports', ['status' => 'failed']));
        $responseStatus->assertStatus(200);
        $responseStatus->assertViewHas('totalLogs', 1);
        $responseStatus->assertViewHas('successCount', 0);
        $responseStatus->assertViewHas('failedCount', 1);
        $responseStatus->assertViewHas('successRate', 0.0);
        $responseStatus->assertViewHas('failedRate', 100.0);
    }
}
