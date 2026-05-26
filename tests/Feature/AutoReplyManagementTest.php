<?php

namespace Tests\Feature;

use App\Models\AutoReply;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoReplyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_reply_rules_index_and_creation()
    {
        $response = $this->post(route('auto-reply.store'), [
            'keyword' => 'Halo',
            'match_type' => 'exact',
            'response_message' => 'Halo juga! Ada yang bisa kami bantu?',
        ]);

        $response->assertRedirect(route('auto-reply'));
        $this->assertDatabaseHas('auto_replies', [
            'keyword' => 'Halo',
            'match_type' => 'exact',
        ]);

        $response = $this->get(route('auto-reply'));
        $response->assertStatus(200);
        $response->assertSee('Halo');
        $response->assertSee('Halo juga!');
    }

    public function test_auto_reply_toggle_status()
    {
        $rule = AutoReply::create([
            'keyword' => 'Price',
            'match_type' => 'contains',
            'response_message' => 'Harga paket mulai dari 50rb.',
            'is_active' => true,
        ]);

        $response = $this->post(route('auto-reply.toggle', $rule->id));
        $response->assertStatus(200);

        $this->assertDatabaseHas('auto_replies', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }

    public function test_auto_reply_deletion()
    {
        $rule = AutoReply::create([
            'keyword' => 'Price',
            'match_type' => 'contains',
            'response_message' => 'Harga paket mulai dari 50rb.',
            'is_active' => true,
        ]);

        $response = $this->delete(route('auto-reply.destroy', $rule->id));
        $response->assertRedirect(route('auto-reply'));

        $this->assertDatabaseMissing('auto_replies', [
            'id' => $rule->id,
        ]);
    }

    public function test_webhook_incoming_message_triggers_auto_reply_and_logs_it()
    {
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->with('081234567890@c.us', 'Harga paket mulai dari 50rb.', null)
                ->once()
                ->andReturn(['status' => true]);
        });

        $rule = AutoReply::create([
            'keyword' => 'harga',
            'match_type' => 'exact',
            'response_message' => 'Harga paket mulai dari 50rb.',
            'is_active' => true,
        ]);

        // Post to message webhook
        $data = ['sender' => '081234567890', 'message' => 'harga'];
        $response = $this->postJson(route('webhook.whatsapp.message'), $data, $this->webhookHeaders($data));

        $response->assertStatus(200);

        // Verify contact is created/mapped
        $this->assertDatabaseHas('contacts', [
            'phone_number' => '6281234567890',
        ]);

        $contact = Contact::where('phone_number', '6281234567890')->first();

        // Verify message log is recorded
        $this->assertDatabaseHas('message_logs', [
            'campaign_id' => null,
            'contact_id' => $contact->id,
            'message_body' => 'Harga paket mulai dari 50rb.',
            'status' => 'sent',
        ]);

        // Verify trigger count is incremented
        $this->assertEquals(1, $rule->refresh()->trigger_count);
    }
}
