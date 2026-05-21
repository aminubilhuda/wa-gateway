<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_page_renders_with_contacts_and_segments()
    {
        // Seed contacts with different labels
        Contact::create(['name' => 'Budi Reseller', 'phone_number' => '6281234567890', 'label' => 'Reseller']);
        Contact::create(['name' => 'Ani Leads', 'phone_number' => '6281234567891', 'label' => 'Leads']);
        Contact::create(['name' => 'Rudi Reseller', 'phone_number' => '6281234567892', 'label' => 'Reseller']);

        $response = $this->get(route('contacts'));
        $response->assertStatus(200);

        // Verify contacts are visible in the view
        $response->assertSee('Budi Reseller');
        $response->assertSee('Ani Leads');
        $response->assertSee('Rudi Reseller');

        // Verify dynamic segments counts (Reseller = 2, Leads = 1)
        $response->assertViewHas('segments', function ($segments) {
            $reseller = $segments->firstWhere('label', 'Reseller');
            $leads = $segments->firstWhere('label', 'Leads');

            return $reseller->total === 2 && $leads->total === 1;
        });
    }

    public function test_contacts_page_filters_by_search_query()
    {
        Contact::create(['name' => 'Budi Reseller', 'phone_number' => '6281234567890', 'label' => 'Reseller']);
        Contact::create(['name' => 'Ani Leads', 'phone_number' => '6281234567891', 'label' => 'Leads']);

        // Search for "Budi"
        $response = $this->get(route('contacts', ['search' => 'Budi']));
        $response->assertStatus(200);
        $response->assertSee('Budi Reseller');
        $response->assertDontSee('Ani Leads');
    }

    public function test_contacts_page_filters_by_label()
    {
        Contact::create(['name' => 'Budi Reseller', 'phone_number' => '6281234567890', 'label' => 'Reseller']);
        Contact::create(['name' => 'Ani Leads', 'phone_number' => '6281234567891', 'label' => 'Leads']);

        // Filter by label "Leads"
        $response = $this->get(route('contacts', ['label' => 'Leads']));
        $response->assertStatus(200);
        $response->assertSee('Ani Leads');
        $response->assertDontSee('Budi Reseller');
    }

    public function test_contact_single_ajax_delete()
    {
        $contact = Contact::create(['name' => 'Budi Reseller', 'phone_number' => '6281234567890', 'label' => 'Reseller']);

        $response = $this->deleteJson(route('contacts.destroy', $contact));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_contacts_bulk_delete()
    {
        $contact1 = Contact::create(['name' => 'Budi Reseller', 'phone_number' => '6281234567890', 'label' => 'Reseller']);
        $contact2 = Contact::create(['name' => 'Ani Leads', 'phone_number' => '6281234567891', 'label' => 'Leads']);

        $response = $this->postJson(route('contacts.bulk-delete'), [
            'ids' => [$contact1->id, $contact2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('contacts', ['id' => $contact1->id]);
        $this->assertDatabaseMissing('contacts', ['id' => $contact2->id]);
    }

    public function test_contacts_import_from_csv()
    {
        $csvContent = "081234567890,Budi Santoso,Reseller\n085712345678,Ani Wijaya,Leads\n";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->post(route('contacts.import'), [
            'excel_file' => $file,
        ]);

        $response->assertRedirect(route('contacts'));
        $response->assertSessionHas('success');

        // Check if phone number gets cleaned and inserted
        $this->assertDatabaseHas('contacts', [
            'phone_number' => '6281234567890',
            'name' => 'Budi Santoso',
            'label' => 'Reseller',
        ]);

        $this->assertDatabaseHas('contacts', [
            'phone_number' => '6285712345678',
            'name' => 'Ani Wijaya',
            'label' => 'Leads',
        ]);
    }

    public function test_contacts_bulk_add_group()
    {
        $contact1 = Contact::create(['name' => 'Budi Reseller', 'phone_number' => '6281234567890', 'label' => 'Reseller']);
        $contact2 = Contact::create(['name' => 'Ani Leads', 'phone_number' => '6281234567891', 'label' => 'Leads']);

        $response = $this->postJson(route('contacts.bulk-add-group'), [
            'ids' => [$contact1->id, $contact2->id],
            'label' => 'VIP Customers',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact1->id,
            'label' => 'VIP Customers',
        ]);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact2->id,
            'label' => 'VIP Customers',
        ]);
    }

    public function test_contacts_bulk_broadcast()
    {
        $contact1 = Contact::create(['name' => 'Budi Reseller', 'phone_number' => '6281234567890', 'label' => 'Reseller']);
        $contact2 = Contact::create(['name' => 'Ani Leads', 'phone_number' => '6281234567891', 'label' => 'Leads']);

        $response = $this->postJson(route('contacts.bulk-broadcast'), [
            'ids' => [$contact1->id, $contact2->id],
            'message' => 'Halo pelanggan setia!',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check if logs were created
        $this->assertDatabaseHas('message_logs', [
            'contact_id' => $contact1->id,
            'message_body' => 'Halo pelanggan setia!',
        ]);
        $this->assertDatabaseHas('message_logs', [
            'contact_id' => $contact2->id,
            'message_body' => 'Halo pelanggan setia!',
        ]);
    }
}
