<?php

namespace Tests\Feature;

use App\Models\Blacklist;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CRUD operations for Message Templates.
     */
    public function test_message_templates_crud()
    {
        // 1. Create
        $response = $this->post(route('templates.store'), [
            'title' => 'Template Promo Baru',
            'message_body' => 'Halo [Name], ini adalah promo baru khusus untukmu.',
        ]);

        $response->assertRedirect(route('templates'));
        $this->assertDatabaseHas('message_templates', [
            'title' => 'Template Promo Baru',
        ]);

        $template = MessageTemplate::first();

        // 2. Read
        $response = $this->get(route('templates'));
        $response->assertStatus(200);
        $response->assertSee('Template Promo Baru');

        // 3. Update
        $response = $this->put(route('templates.update', $template->id), [
            'title' => 'Template Promo Updated',
            'message_body' => 'Halo [Name], info promo sudah diperbarui.',
        ]);

        $response->assertRedirect(route('templates'));
        $this->assertDatabaseHas('message_templates', [
            'id' => $template->id,
            'title' => 'Template Promo Updated',
        ]);

        // 4. Delete
        $response = $this->delete(route('templates.destroy', $template->id));
        $response->assertRedirect(route('templates'));
        $this->assertDatabaseMissing('message_templates', [
            'id' => $template->id,
        ]);
    }

    /**
     * Test CRUD operations for Blacklist.
     */
    public function test_blacklist_crud()
    {
        // 1. Create / Store
        $response = $this->post(route('blacklist.store'), [
            'phone_number' => '081299998888',
            'reason' => 'Pelanggan minta unsubscribe',
        ]);

        $response->assertRedirect(route('blacklist'));
        $this->assertDatabaseHas('blacklists', [
            'phone_number' => '6281299998888',
            'reason' => 'Pelanggan minta unsubscribe',
        ]);

        $blacklist = Blacklist::first();

        // 2. Read
        $response = $this->get(route('blacklist'));
        $response->assertStatus(200);
        $response->assertSee('6281299998888');

        // 3. Delete / Restore
        $response = $this->delete(route('blacklist.destroy', $blacklist->id));
        $response->assertRedirect(route('blacklist'));
        $this->assertDatabaseMissing('blacklists', [
            'phone_number' => '6281299998888',
        ]);
    }

    /**
     * Test incoming webhook message with "STOP" keyword.
     */
    public function test_webhook_incoming_message_stop_triggers_blacklist()
    {
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->with('081277777777@c.us', 'Anda telah berhasil keluar dari daftar penerima pesan kami. Anda tidak akan menerima pesan blast lagi.')
                ->once()
                ->andReturn(['status' => true]);
        });

        // Trigger message webhook with 'stop'
        $data = ['sender' => '081277777777', 'message' => 'stop'];
        $response = $this->postJson(route('webhook.whatsapp.message'), $data, $this->webhookHeaders($data));

        $response->assertStatus(200);

        // Assert number is added to blacklist
        $this->assertDatabaseHas('blacklists', [
            'phone_number' => '6281277777777',
        ]);

        // Assert message log is saved
        $this->assertDatabaseHas('message_logs', [
            'message_body' => 'Anda telah berhasil keluar dari daftar penerima pesan kami. Anda tidak akan menerima pesan blast lagi.',
            'status' => 'sent',
        ]);
    }

    /**
     * Test CSV Export route.
     */
    public function test_message_logs_csv_export()
    {
        $contact = Contact::create(['name' => 'John Doe', 'phone_number' => '62812345678']);
        MessageLog::create([
            'campaign_id' => null,
            'contact_id' => $contact->id,
            'message_body' => 'Test CSV message content',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get(route('reports.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Capture streamed response content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('Test CSV message content', $content);
    }

    /**
     * Test scheduler round-robin sending to active devices and skipping blacklisted numbers.
     */
    public function test_scheduler_round_robin_and_blacklist_skipping()
    {
        // Create 2 active devices
        Device::create([
            'name' => 'Device A',
            'token' => 'token-device-a',
            'status' => 'connected',
            'delay_seconds' => '2-5',
        ]);

        Device::create([
            'name' => 'Device B',
            'token' => 'token-device-b',
            'status' => 'connected',
            'delay_seconds' => '5-10',
        ]);

        // Add 1 number to blacklist
        Blacklist::create([
            'phone_number' => '6281211111111',
            'reason' => 'Blacklisted',
        ]);

        // Create campaign targeting manual numbers (one blacklisted, two normal)
        $campaign = Campaign::create([
            'name' => 'Rotated Campaign',
            'message_template' => 'Halo [Name] dari server.',
            'target_type' => 'manual',
            'target_value' => '081211111111,081222222222,081233333333', // 1st is blacklisted, 2nd and 3rd are normal
            'schedule_type' => 'minute',
            'interval_value' => 1,
            'is_active' => true,
            'status' => 'scheduled',
        ]);

        // Mock WhatsAppService. WhatsApp should send to the 2 non-blacklisted recipients.
        // It should rotate them:
        // Recipient 1: 628122222222 -> Device A (token-device-a, delay '2-5')
        // Recipient 2: 628123333333 -> Device B (token-device-b, delay '5-10')
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('setToken')
                ->with('token-device-a')
                ->once()
                ->andReturnSelf();

            $mock->shouldReceive('setToken')
                ->with('token-device-b')
                ->once()
                ->andReturnSelf();

            $mock->shouldReceive('sendBulkMessages')
                ->twice()
                ->andReturnUsing(function ($bulk) {
                    $target = $bulk[0]['target'];
                    $delay = $bulk[0]['delay'];

                    if ($target === '6281222222222') {
                        $this->assertEquals('2-5', $delay);
                    } elseif ($target === '6281233333333') {
                        $this->assertEquals('5-10', $delay);
                    } else {
                        $this->fail('Unexpected target number sent to sendBulkMessages: '.$target);
                    }

                    return ['status' => true];
                });
        });

        // Execute dispatch command directly
        Artisan::call('campaigns:dispatch');

        // Verify the campaign state is updated
        $campaign->refresh();
        $this->assertEquals('scheduled', $campaign->status);

        // Verify message logs
        // Blacklisted number should not have a log
        $this->assertDatabaseMissing('message_logs', [
            'message_body' => 'Halo Recipient 081211111111 dari server.',
        ]);

        // Non-blacklisted numbers should have sent logs
        $this->assertDatabaseHas('message_logs', [
            'message_body' => 'Halo Recipient 081222222222 dari server.',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('message_logs', [
            'message_body' => 'Halo Recipient 081233333333 dari server.',
            'status' => 'sent',
        ]);
    }
}
