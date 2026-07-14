<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class TenantContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = Contact::factory()->count(1000)->create();

        foreach ($contacts as $contact) {
            $contact->address()->save(
                Address::factory()->make()
            );
        }

        foreach ($contacts as $contact) {
            $contact->client()->create(
                // Client::factory()->make()
                ['contact_id' => $contact->id]
            );
        }
    }
}
