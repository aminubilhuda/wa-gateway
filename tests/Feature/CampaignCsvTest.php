<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CampaignCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_can_be_stored_with_csv_custom_columns()
    {
        // Mock FonnteService to prevent actual HTTP request
        $this->mock(FonnteService::class, function ($mock) {
            $mock->shouldReceive('sendBulkMessages')->andReturn(['status' => true]);
        });

        // Create a mock CSV file
        $csvContent = "081234567890,Budi,Rp 200.000\n081234567891,Ani,Rp 100.000";
        $csvFile = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Invoice Blast',
            'message_template' => 'Hai {{B}}, untuk invoice kamu sebesar {{C}} sudah jatuh tempo.',
            'target_type' => 'excel',
            'excel_file' => $csvFile,
            'is_scheduled' => '0',
            'schedule_type' => 'once',
        ]);

        $response->assertRedirect(route('campaigns'));
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Invoice Blast',
            'target_type' => 'excel',
        ]);

        $campaign = Campaign::first();
        // Check target_value is stored as JSON array with columns mapped
        $this->assertStringStartsWith('[', $campaign->target_value);
        $decoded = json_decode($campaign->target_value, true);
        $this->assertCount(2, $decoded);
        $this->assertEquals('6281234567890', $decoded[0]['A']);
        $this->assertEquals('Budi', $decoded[0]['B']);
        $this->assertEquals('Rp 200.000', $decoded[0]['C']);

        // Check if logs are created with correct dynamic messages
        $this->assertDatabaseHas('message_logs', [
            'message_body' => 'Hai Budi, untuk invoice kamu sebesar Rp 200.000 sudah jatuh tempo.',
        ]);
        $this->assertDatabaseHas('message_logs', [
            'message_body' => 'Hai Ani, untuk invoice kamu sebesar Rp 100.000 sudah jatuh tempo.',
        ]);
    }
}
